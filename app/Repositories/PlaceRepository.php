<?php

namespace App\Repositories;

use Mail;
use App\User;
use App\Place;
use App\Mail\NewPlace;
use App\Filters\PlaceFilters;
use App\Services\EventCollector\Exceptions\GeocoderException;

class PlaceRepository extends Repository
{
    public function __construct(Place $place)
    {
        $this->model = $place;
    }

    public function getGeocoder()
    {
        return app('geocoder');
    }

    protected function getTagIds($tags)
    {
        if (is_array($tags)) {
            return $tags;
        }

        return array_reduce(explode(',', $tags), function($ids, $name) {
            $tag = $this->tags->store(['name' => $name]);
            $ids[] = $tag->id;
            return $ids;
        }, []);
    }

    /**
     * Get all.
     *
     * @return Collection
     * @param  \App\Filters\PlaceFilters  $filters
     */
    public function all(PlaceFilters $filters)
    {
        return $this->model->filter($filters)->get();
    }

    /**
     * Get all of the places for the user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  \App\Filters\PlaceFilters  $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function forUser(User $user, PlaceFilters $filters)
    {
        return $user->places()->filter($filters)->get();
    }

    /**
     * Create or update the place.
     *
     * @param  \App\Place $place
     * @param  array  $inputs
     * @param  integer  $user_id
     * @return \App\Place
     */
    protected function save(Place $place, array $inputs, $user_id = null)
    {
        $place->fill($inputs);

        if ($user_id) {
            $place->user_id = $user_id;
        }

        $place->save();

        return $place;
    }

    /**
     * Create a new place for the given user and data.
     *
     * @param  array  $data
     * @return \App\Place
     */
    public function store(array $data, $user_id)
    {
        $place = $this->findByName($data);
        if ($place) return $place;

        if (!isset($data['latitude']) && !isset($data['longitude'])) {
            $data = array_merge($this->getGeocoder()->getData($data['name']), $data);
        }

        $place = $this->findByLocation($data);
        if ($place) return $place;

        $place = $this->save(new Place(), $data, $user_id);

        // Attach tags
        if (array_key_exists('tags', $data)) {
            $place->tags()->attach($this->getTagIds($data['tags']));
        }

        Mail::to(env('MAIL_CONTACT_ADDRESS'))->send(new NewPlace($place));

        return $place;
    }

    /**
     * Update the place.
     *
     * @param  array  $data
     * @return \App\Place
     */
    public function update(array $data, Place $place)
    {
        $place = $this->save($place, $data);

        // Update tags
        if (array_key_exists('tags', $data)) {
            $place->tags()->sync($this->getTagIds($data['tags']));
        }

        return $place;
    }

    /**
     * Destroy the place.
     *
     * @param  Place $place
     * @return boolean
     */
    public function destroy(Place $place)
    {
        return $place->delete();
    }

     /**
     * Find a place by name
     *
     * @param  array $data
     * @return \App\Place | null
     */
    public function findByName(array $data)
    {
        return $this->model->where('name', 'like', $data['name'])->first();
    }

     /**
     * Find a place by coordinates
     *
     * @param  array $data
     * @return \App\Place | null
     */
    public function findByLocation(array $data)
    {
        $place = null;

        if (isset($data['street'])) {
            $place = $this->model
                ->where('street', $data['street'])
                ->first();
            if ($place) return $place;
        }

        if (isset($data['latitude']) && isset($data['longitude'])) {
            // Try to find by coordinates.
            $place = $this->model
                ->whereRaw('TRUNCATE(latitude, 2) = ' . bcdiv($data['latitude'], 1, 3))
                ->whereRaw('TRUNCATE(longitude, 2) = ' . bcdiv($data['longitude'], 1, 3))
                ->first();
            if ($place) return $place;
        }

        return $place;
    }

    /**
     * Find an existing place or new one up
     *
     * @param  array $data
     * @return \App\Place
     */
    public function findOrNew(array $data)
    {
        $place = $this->findByName($data);
        if ($place) return $place;

        if (!isset($data['latitude']) && !isset($data['longitude'])) {
            $data = array_merge($data, $this->getGeocoder()->getData($data['name']));
        }

        $place = $this->findByLocation($data);
        if ($place) return $place;

        return new Place($data);
    }
}

<template>
  <div style="margin-bottom: 2rem;">

    <div class="head-bar">
      <div class="head-bar-container">
          <h1 class="head-bar-title">
            <svg class="icon icon-calendar"><use xlink:href="/images/icons.svg#icon-pin"></use></svg>
            Places
          </h1>
      </div>
    </div>

    <div class="top-bar">
      <div class="top-bar-container">
        <div class="top-bar-right">
          <ul class="menu">
            <li>
              <dropdown>
                <a class="dropdown">
                  <svg class="icon"><use xlink:href="/images/icons.svg#icon-funnel"></use></svg>
                  Filter
                </a>
                <div slot="menu">
                    <place-filters></place-filters>
                </div>
              </dropdown>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <places ref="places"></places>

    <!-- Create Place Modal -->
    <modal v-if="createModal">
      <div class="modal-head">
        <div class="modal-title">Create Place</div>
      </div>
      <form @submit.prevent="store" @input="createForm.errors.clear($event.target.name)">
        <div class="modal-section">

          <label :class="{'is-invalid-label': createForm.errors.has('name')}">Name
            <input type="text" v-model="createForm.name" :class="{'is-invalid-input': createForm.errors.has('name')}"></textarea>
            <span :class="['form-error', {'is-visible': createForm.errors.has('name')}]" v-text="createForm.errors.get('name')"></span>
          </label>

          <label :class="{'is-invalid-label': createForm.errors.has('street')}">Street
            <input type="text" v-model="createForm.street" :class="{'is-invalid-input': createForm.errors.has('street')}"></textarea>
            <span :class="['form-error', {'is-visible': createForm.errors.has('street')}]" v-text="createForm.errors.get('street')"></span>
          </label>

          <div class="grid-x grid-margin-x">
            <div class="small-12 medium-4 cell">

              <label :class="{'is-invalid-label': createForm.errors.has('city')}">City
                <input type="text" v-model="createForm.city" :class="{'is-invalid-input': createForm.errors.has('city')}"></textarea>
                <span :class="['form-error', {'is-visible': createForm.errors.has('city')}]" v-text="createForm.errors.get('city')"></span>
              </label>

            </div>
            <div class="small-12 medium-4 cell">

              <label :class="{'is-invalid-label': createForm.errors.has('state')}">State
                <input type="text" v-model="createForm.state" :class="{'is-invalid-input': createForm.errors.has('state')}"></textarea>
                <span :class="['form-error', {'is-visible': createForm.errors.has('state')}]" v-text="createForm.errors.get('state')"></span>
              </label>

            </div>
            <div class="small-12 medium-4 cell">

              <label :class="{'is-invalid-label': createForm.errors.has('zip')}">Zip
                <input type="text" v-model="createForm.zip" :class="{'is-invalid-input': createForm.errors.has('zip')}"></textarea>
                <span :class="['form-error', {'is-visible': createForm.errors.has('zip')}]" v-text="createForm.errors.get('zip')"></span>
              </label>

            </div>
          </div>

          <label :class="{'is-invalid-label': createForm.errors.has('profile.website')}">Website
            <input type="text" v-model="createForm.profile.website" :class="{'is-invalid-input': createForm.errors.has('profile.website')}"></textarea>
            <span :class="['form-error', {'is-visible': createForm.errors.has('profile.website')}]" v-text="createForm.errors.get('profile.website')"></span>
          </label>


          <label :class="{'is-invalid-label': createForm.errors.has('profile.email')}">Email
            <input type="email" v-model="createForm.profile.email" :class="{'is-invalid-input': createForm.errors.has('profile.email')}"></textarea>
            <span :class="['form-error', {'is-visible': createForm.errors.has('profile.email')}]" v-text="createForm.errors.get('profile.email')"></span>
          </label>

          <label :class="{'is-invalid-label': createForm.errors.has('profile.phone')}">Phone
            <input type="text" v-model="createForm.profile.phone" :class="{'is-invalid-input': createForm.errors.has('profile.phone')}"></textarea>
            <span :class="['form-error', {'is-visible': createForm.errors.has('profile.phone')}]" v-text="createForm.errors.get('profile.phone')"></span>
          </label>

          <div>
            <label for="place-create-tags" :class="{'is-invalid-label': createForm.errors.has('tags')}">Tags</label>
            <div id="place-create-tags">
              <label v-for="tag in tags" :class="{'is-invalid-label': createForm.errors.has('tags')}">
                <input type="checkbox" :value="tag.id" v-model="createForm.tags" :class="{'is-invalid-input': createForm.errors.has('tags')}">
                {{ tag.name }}
              </label>
            </div>
            <span :class="['form-error', {'is-visible': createForm.errors.has('tags')}]" v-text="createForm.errors.get('tags')"></span>
          </div>

        </div>
        <div class="modal-foot">
          <div class="modal-foot-right">
            <button type="button"
              class="button secondary"
              @click="createModal = false"
              :disabled="createForm.isSubmitting()">
              Cancel
            </button>
            <button class="button loading"
              :disabled="createForm.isSubmitting()">
              Save Changes
            </button>
          </div>
        </div>
      </form>
    </modal>

  </div>
</template>

<script>
import Modal from '../Modal.vue'
import Form from '../../utilities/Form'
import Places from '../places/Places.vue'
import PlaceFilters from '../places/PlaceFilters.vue'

export default {
  components: { Places, PlaceFilters, Modal },

  data () {
    return {
      tags: false,

      createForm: new Form({
        name: '',
        street: '',
        city: '',
        state: '',
        zip: '',
        tags: [],
        profile: {}
      }),

      createModal: false
    }
  },

  methods: {
    /**
     * Get all tags.
     */
    getTags () {
      return axios.get('/api/tags')
        .then(({data}) => {
          this.tags = data
          return data
        })
    },

    /**
     * Create the given resource.
     */
    create (place) {
      this.getTags()

      this.createModal = true
    },

    /**
     * Store the given resource.
     */
    store () {
      this.createForm.post('/api/places')
        .then(place => {
          this.$refs.places.getPlaces()
          this.createModal = false
          window.flash('Place updated.')
        })
        .catch(error => {
          this.createModal = error.response.status === 422
          window.flash('Updating place failed.', 'alert')
        })
    }
  }
}
</script>

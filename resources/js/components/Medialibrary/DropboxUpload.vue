<template>
  <button
      type="button"
      class="dropbox-upload-btn btn btn-default btn-blue ml-3"
      :class="{loading}"
      :disabled="loading || !context.resourceId"
      @click="chooser"
  >
    <span>{{
        !context.resourceId ? __('Dropbox uploads (available after creation)') : __('Upload from Dropbox')
      }}</span>
    <span>{{ __('Uploading...') }}</span>
  </button>
</template>

<script>
import { context } from './Context'

export default {
  inject: {
    context,
  },
  mounted () {
    const s = document.createElement('script')
    s.setAttribute('id', 'dropboxjs')
    s.setAttribute('data-app-key', '2a8kvaor8ksauzn')
    s.setAttribute('src', 'https://www.dropbox.com/static/api/2/dropins.js')
    document.head.appendChild(s)
  },
  data () {
    return {
      loading: false
    }
  },
  methods: {
    chooser () {
      Dropbox.choose({

        // Required. Called when a user selects an item in the Chooser.
        success: (files) => {
          this.upload(files)
        },

        // Optional. "preview" (default) is a preview link to the document for sharing,
        // "direct" is an expiring link to download the contents of the file. For more
        // information about link types, see Link types below.
        linkType: 'direct', // or "direct"

        // Optional. A value of false (default) limits selection to a single file, while
        // true enables multiple file selection.
        multiselect: true, // or true

        // Optional. This is a list of file extensions. If specified, the user will
        // only be able to select files with these extensions. You may also specify
        // file types, such as "video" or "images" in the list. For more information,
        // see File types below. By default, all extensions are allowed.
        extensions: ['images'],

        // Optional. A value of false (default) limits selection to files,
        // while true allows the user to select both folders and files.
        // You cannot specify `linkType: "direct"` when using `folderselect: true`.
        folderselect: false, // or true

        // Optional. A limit on the size of each file that may be selected, in bytes.
        // If specified, the user will only be able to select files with size
        // less than or equal to this limit.
        // For the purposes of this option, folders have size zero.
        sizeLimit: 104857600, // or any positive number
      })
    },
    async upload (files) {
      const { attribute, collectionName } = this.$parent.context.field
      const { resourceName, resourceId } = this.$parent.context

      this.loading = true

      for (const file of files) {
        if (typeof file.link === 'undefined' || !file.link) {
          return
        }

        const request = {
          file_name: file.name,
          file_url: file.link,
          collection_name: collectionName
        }

        const uploadingPromise = Nova
            .request()
            .post(`/nova-vendor/dmitrybubyakin/nova-medialibrary-field/${resourceName}/${resourceId}/media/${attribute}/dropbox-upload`, request)
            .then(response => {
              Nova.$emit(`nova-medialibrary-field:refresh:${attribute}`)
            })
            .catch(error => Nova.error(error.response.data.message))
            .then(() => {
              this.loading = false
            })

        if (this.$parent.synchronousUploading) {
          await uploadingPromise
        }
      }
    }
  }
}
</script>

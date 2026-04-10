<template>
	<NcAppNavigationItem :name="title"
		:icon="icon"
		:editable="true"
		:edit-label="rename"
		:to="{ name: 'list', params: { listId: groceryList.id.toString() } }"
		@click="openGroceryList(groceryList)"
		@update:name="onRename">
		<template #actions>
			<NcActionButton @click="openSettings(groceryList)">
				<template #icon>
					<Cog :size="20" />
				</template>
				{{ t('grocerylist', 'Settings') }}
			</NcActionButton>
		</template>
		<template v-if="groceryList.uncheckedCount > 0" #counter>
			<NcCounterBubble :count="groceryList.uncheckedCount" />
		</template>
	</NcAppNavigationItem>
</template>
<script>
import { showError, showInfo } from '@nextcloud/dialogs'
import { emit, subscribe, unsubscribe } from '@nextcloud/event-bus'
import {
	NcActionButton,
	NcAppNavigationItem,
	NcCounterBubble,
} from '@nextcloud/vue'
import Cog from 'vue-material-design-icons/Cog.vue'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export default {
	name: 'NavigationGroceryListItem',
	components: {
		NcActionButton,
		NcAppNavigationItem,
		NcCounterBubble,
		Cog,
	},
	props: {
		groceryList: {
			type: Object,
			required: true,
		},
		currentGroceryListId: {
			type: Number,
			required: true,
		},
	},
	data() {
		return {}
	},
	mounted() {
		subscribe('grocerylist:item-checked-changed', this.onItemCheckedChanged)
	},
	beforeUnmount() {
		unsubscribe('grocerylist:item-checked-changed', this.onItemCheckedChanged)
	},
	computed: {
		icon() {
			return 'icon-toggle-filelist'
		},
		rename() {
			return 'Rename ' + this.groceryList.title
		},
		title() {
			return this.groceryList.title
		},
		titleWithCount() {
			if (this.groceryList.uncheckedCount > 0) {
				return this.groceryList.title + ' (' + this.groceryList.uncheckedCount + ')'
			} else {
				return this.groceryList.title
			}
		},
	},
	methods: {
    onItemCheckedChanged({ listId, checked }) {
      if (listId !== this.groceryList.id) {
        return
      }
      const current = this.groceryList.uncheckedCount ?? 0
      this.groceryList.uncheckedCount = checked
        ? Math.max(0, current - 1)
        : current + 1
    },
    async onRename(newTitle) {
      showInfo('rename to ' + newTitle)
      console.error('rename to ' + newTitle)
      this.updating = true
      this.groceryList.title = newTitle

      try {
        if (this.groceryList.id === -1) {
          const response = await axios.post(generateUrl('/apps/grocerylist/api/lists'), this.groceryList)
          this.currentGroceryListId = response.data.id
        } else {
          await axios.post(generateUrl(`/apps/grocerylist/api/lists/${this.groceryList.id}`), this.groceryList)
        }
      } catch (e) {
        console.error(e)
        showError(t('grocerylist', 'Could not create groceryList'))
      }
      this.updating = false
    },
    openSettings(groceryList) {
      this.$router.push({
        name: 'settings',
        params: {
          listId: groceryList.id,
        },
      })
    },
    openGroceryList(groceryList) {
      this.$router.push({
        name: 'list',
        params: {
          listId: groceryList.id,
        },
      })

      if (this.isMobile()) {
        emit('toggle-navigation', {
          open: false,
        })
      }
    },
    isMobile() {
      if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
        return true
      } else {
        return false
      }
    },
  }
}
</script>

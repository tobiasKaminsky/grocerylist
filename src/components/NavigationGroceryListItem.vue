<!--
  - SPDX-FileCopyrightText: 2021 Tobias Kaminsky <tobias@kaminsky.me>
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcAppNavigationItem
		:name="title"
		:icon="icon"
		:editable="true"
		:editLabel="rename"
		:to="{ name: 'list', params: { listId: groceryList.id.toString() } }"
		@click="openGroceryList(groceryList)"
		@update:name="onRename">
		<template #actions>
			<NcActionButton v-if="isOwner" @click="openSharing(groceryList)">
				<template #icon>
					<ShareVariant :size="20" />
				</template>
				{{ t('grocerylist', 'Share list') }}
			</NcActionButton>
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
import { getCurrentUser } from '@nextcloud/auth'
import { emit } from '@nextcloud/event-bus'
import {
	NcActionButton,
	NcAppNavigationItem,
	NcCounterBubble,
} from '@nextcloud/vue'
import Cog from 'vue-material-design-icons/Cog.vue'
import ShareVariant from 'vue-material-design-icons/ShareVariant.vue'

export default {
	name: 'NavigationGroceryListItem',
	components: {
		NcActionButton,
		NcAppNavigationItem,
		NcCounterBubble,
		Cog,
		ShareVariant,
	},

	props: {
		groceryList: {
			type: Object,
			required: true,
		},

	},

	emits: ['rename'],

	data() {
		return {}
	},

	computed: {
		icon() {
			return 'icon-toggle-filelist'
		},

		isOwner() {
			return this.groceryList.userId === getCurrentUser()?.uid
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
		onRename(newTitle) {
			this.$emit('rename', newTitle)
		},

		openSettings(groceryList) {
			this.$router.push({
				name: 'settings',
				params: {
					listId: groceryList.id,
				},
			})
		},

		openSharing(groceryList) {
			this.$router.push({
				name: 'settings',
				params: {
					listId: groceryList.id,
				},
				hash: '#sharing',
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
	},
}
</script>

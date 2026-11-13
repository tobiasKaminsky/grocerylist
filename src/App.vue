<!--
  - SPDX-FileCopyrightText: 2020 Tobias Kaminsky <tobias@kaminsky.me>
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcContent appName="grocerylist">
		<NcAppNavigation>
			<NcAppNavigationNew
				:text="t('grocerylist', 'New list')"
				:disabled="loading"
				@click="showNewListModal">
				<template #icon>
					<IconPlus :size="20" />
				</template>
			</NcAppNavigationNew>
			<template #list>
				<!-- Loading indicator while lists are not fetched -->
				<NcAppNavigationItem v-if="loading" :name="t('grocerylist', 'Loading lists…')" :loading="true" />
				<!-- List of available grocery lists -->
				<ul v-else>
					<NavigationGroceryListItem
						v-for="groceryList in groceryLists"
						:key="groceryList.id"
						:title="groceryList.title ? groceryList.title : t('grocerylist', 'New list')"
						:groceryList="groceryList"
						@rename="(title) => renameGroceryList(groceryList, title)" />
				</ul>
			</template>
		</NcAppNavigation>
		<NcAppContent>
			<router-view />
		</NcAppContent>
		<NcModal
			v-if="newListModal"
			:name="t('grocerylist', 'New list')"
			@close="closeNewListModal">
			<form class="new-list-modal__content" @submit.prevent="createNewList">
				<NcTextField
					v-model="newListName"
					:label="t('grocerylist', 'List name')"
					:placeholder="t('grocerylist', 'My grocery list')" />
				<NcCheckboxRadioSwitch
					v-model="newListShowOnlyUnchecked"
					type="switch">
					{{ t('grocerylist', 'Show only unchecked') }}
				</NcCheckboxRadioSwitch>
				<NcCheckboxRadioSwitch
					v-if="!newListShowOnlyUnchecked"
					v-model="newListHideCategory"
					type="switch">
					{{ t('grocerylist', 'Hide category') }}
				</NcCheckboxRadioSwitch>
				<div class="new-list-modal__actions">
					<NcButton
						variant="primary"
						type="submit"
						:disabled="newListName.trim() === ''">
						<template #icon>
							<IconPlus :size="20" />
						</template>
						{{ t('grocerylist', 'Create list') }}
					</NcButton>
				</div>
			</form>
		</NcModal>
	</NcContent>
</template>

<script>
import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { generateUrl } from '@nextcloud/router'
import {
	NcAppContent,
	NcAppNavigation,
	NcAppNavigationItem,
	NcAppNavigationNew,
	NcButton,
	NcCheckboxRadioSwitch,
	NcContent,
	NcModal,
	NcTextField,
} from '@nextcloud/vue'
import IconPlus from 'vue-material-design-icons/Plus.vue'
import NavigationGroceryListItem from './components/NavigationGroceryListItem.vue'
import logger from './logger.js'

export default {
	name: 'App',
	components: {
		IconPlus,
		NcButton,
		NcCheckboxRadioSwitch,
		NcContent,
		NcAppContent,
		NcAppNavigation,
		NcAppNavigationItem,
		NcAppNavigationNew,
		NcModal,
		NcTextField,
		NavigationGroceryListItem,
	},

	data() {
		return {
			groceryLists: [],
			items: [],
			categories: [],
			currentGroceryListId: -1,
			updating: false,
			loading: true,
			newListModal: false,
			newListName: '',
			newListShowOnlyUnchecked: false,
			newListHideCategory: false,
			lastOpenedListStorageKey: 'grocerylist:lastOpenedListId',
		}
	},

	computed: {
		/**
		 * Return the currently selected groceryList object
		 *
		 * @return {object | null}
		 */
		currentGroceryList() {
			if (this.currentGroceryListId === null) {
				return null
			}
			return this.groceryLists.find((groceryList) => groceryList.id === this.currentGroceryListId)
		},

		/**
		 * Returns true if a list is selected and its title is not empty
		 *
		 * @return {boolean}
		 */
		savePossible() {
			return this.currentGroceryList && this.currentGroceryList.title !== ''
		},
	},

	watch: {
		$route(to) {
			if (to.name === 'list' && to.params.listId) {
				localStorage.setItem(this.lastOpenedListStorageKey, String(to.params.listId))
			}
		},
	},

	created() {
		this.onCreated()
	},

	/**
	 * Fetch list of groceryLists when the component is loaded
	 */
	async mounted() {
		subscribe('grocerylist:list-deleted', this.onListDeleted)
		subscribe('grocerylist:item-checked-changed', this.onItemCheckedChanged)
		try {
			if (this.$route.name === 'lists') {
				logger.debug('show single list' + this.$route.params)
			}

			const { data } = await axios.get(generateUrl('/apps/grocerylist/api/lists'))
			this.groceryLists = data
			this.restoreLastOpenedList()
		} catch (e) {
			logger.error('Could not fetch groceryLists', { error: e })
			showError(t('grocerylist', 'Could not fetch groceryLists'))
		}
		this.loading = false
	},

	beforeUnmount() {
		unsubscribe('grocerylist:list-deleted', this.onListDeleted)
		unsubscribe('grocerylist:item-checked-changed', this.onItemCheckedChanged)
	},

	methods: {
		onCreated() {
			if (this.$route.name === 'lists') {
				logger.debug('show single list' + this.$route.params)
			}
		},

		restoreLastOpenedList() {
			if (this.$route.name === 'list') {
				return
			}
			const stored = localStorage.getItem(this.lastOpenedListStorageKey)
			if (stored === null) {
				return
			}
			const listId = Number.parseInt(stored, 10)
			if (Number.isNaN(listId)) {
				return
			}
			if (!this.groceryLists.some((list) => list.id === listId)) {
				localStorage.removeItem(this.lastOpenedListStorageKey)
				return
			}
			this.$router.replace({ name: 'list', params: { listId: listId.toString() } })
		},

		async renameGroceryList(list, title) {
			const previousTitle = list.title
			list.title = title
			try {
				await axios.post(generateUrl(`/apps/grocerylist/api/lists/${list.id}`), list)
			} catch (e) {
				list.title = previousTitle
				logger.error('Could not rename groceryList', { error: e })
				showError(t('grocerylist', 'Could not rename groceryList'))
			}
		},

		onItemCheckedChanged({ listId, checked }) {
			const list = this.groceryLists.find(({ id }) => id === listId)
			if (!list) {
				return
			}
			const current = list.uncheckedCount ?? 0
			list.uncheckedCount = checked
				? Math.max(0, current - 1)
				: current + 1
		},

		deleteGroceryList(list) {
			// Remove list from our list of grocery lists
			this.groceryLists = [...this.groceryLists.filter(({ id }) => id !== list.id)]
			const stored = localStorage.getItem(this.lastOpenedListStorageKey)
			if (stored !== null && Number.parseInt(stored, 10) === list.id) {
				localStorage.removeItem(this.lastOpenedListStorageKey)
			}
		},

		onListDeleted({ listId }) {
			this.deleteGroceryList({ id: listId })
		},

		showNewListModal() {
			this.newListName = ''
			this.newListShowOnlyUnchecked = false
			this.newListHideCategory = false
			this.newListModal = true
		},

		closeNewListModal() {
			this.newListModal = false
		},

		async createNewList() {
			if (this.newListName.trim() === '') {
				return
			}

			try {
				const { data } = await axios.post(generateUrl('/apps/grocerylist/api/lists'), { title: this.newListName.trim() })
				this.groceryLists.push(data)

				if (this.newListShowOnlyUnchecked) {
					await axios.post(generateUrl(`/apps/grocerylist/api/lists/${data.id}/visibility`), {
						showOnlyUnchecked: 1,
					})
				}

				this.closeNewListModal()
				this.$router.push({
					name: 'list',
					params: { listId: data.id.toString() },
					query: this.newListHideCategory && !this.newListShowOnlyUnchecked ? { hideCategory: '1' } : {},
				})
			} catch (e) {
				logger.error('Could not create groceryList', { error: e })
				showError(t('grocerylist', 'Could not create groceryList'))
			}
		},
	},
}
</script>

<style lang="scss">
// Prevent the app content area from scrolling — the inner .page-items handles its own scrolling
.app-content {
	overflow: hidden !important;
}

.new-list-modal__content {
	padding: calc(var(--default-grid-baseline) * 4);
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline) * 4);
}

.new-list-modal__actions {
	display: flex;
	justify-content: end;
}
</style>

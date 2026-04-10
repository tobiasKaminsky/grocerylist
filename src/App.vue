<template>
	<NcContent app-name="grocerylist">
		<NcAppNavigation>
			<NcAppNavigationNew :text="t('grocerylist', 'New list')"
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
					<NavigationGroceryListItem v-for="groceryList in groceryLists"
						:key="groceryList.id"
						:title="groceryList.title ? groceryList.title : t('grocerylist', 'New list')"
						:grocery-list="groceryList"
						:current-grocery-list-id="currentGroceryListId"
						@delete="deleteGroceryList(groceryList)" />
				</ul>
			</template>
		</NcAppNavigation>
		<NcAppContent>
			<router-view />
		</NcAppContent>
		<NcModal v-if="newListModal"
			:name="t('grocerylist', 'New list')"
			@close="closeNewListModal">
			<form class="new-list-modal__content" @submit.prevent="createNewList">
				<NcTextField v-model="newListName"
					:label="t('grocerylist', 'List name')"
					:placeholder="t('grocerylist', 'My grocery list')" />
				<NcCheckboxRadioSwitch v-model="newListShowOnlyUnchecked"
					type="switch">
					{{ t('grocerylist', 'Show only unchecked') }}
				</NcCheckboxRadioSwitch>
				<NcCheckboxRadioSwitch v-model="newListHideCategory"
					type="switch">
					{{ t('grocerylist', 'Hide category') }}
				</NcCheckboxRadioSwitch>
				<div class="new-list-modal__actions">
					<NcButton type="primary"
						native-type="submit"
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
import { showError } from '@nextcloud/dialogs'
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

import axios from '@nextcloud/axios'
import NavigationGroceryListItem from './components/NavigationGroceryListItem.vue'
import IconPlus from 'vue-material-design-icons/Plus.vue'

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
	watch: {
		$route(to) {
			if (to.name === 'list' && to.params.listId) {
				localStorage.setItem(this.lastOpenedListStorageKey, String(to.params.listId))
			}
		},
	},
	computed: {
		/**
		 * Return the currently selected groceryList object
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
		 * @return {boolean}
		 */
		savePossible() {
			return this.currentGroceryList && this.currentGroceryList.title !== ''
		},
	},
	created() {
		this.onCreated()
	},
	/**
	 * Fetch list of groceryLists when the component is loaded
	 */
	async mounted() {
		try {
			if (this.$route.name === 'lists') {
				console.warn('show single list' + this.$route.params)
			}

			const { data } = await axios.get(generateUrl('/apps/grocerylist/api/lists'))
			this.groceryLists = data
			this.restoreLastOpenedList()
		} catch (e) {
			console.error(e)
			showError(t('grocerylist', 'Could not fetch groceryLists'))
		}
		this.loading = false
	},
	methods: {
		onCreated() {
			if (this.$route.name === 'lists') {
				console.warn('show single list' + this.$route.params)
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
		renameGroceryList(id, value) {
			try {
				axios.post(generateUrl('/apps/grocerylist/api/list/' + id),
					{
						title: value,
					})
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', 'Could not rename groceryList'))
			}
		},
		deleteGroceryList(list) {
			// Remove list from our list of grocery lists
			this.groceryLists = [...this.groceryLists.filter(({ id }) => id !== list.id)]
			const stored = localStorage.getItem(this.lastOpenedListStorageKey)
			if (stored !== null && Number.parseInt(stored, 10) === list.id) {
				localStorage.removeItem(this.lastOpenedListStorageKey)
			}
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
					query: this.newListHideCategory ? { hideCategory: '1' } : {},
				})
			} catch (e) {
				console.error(e)
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

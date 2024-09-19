<template>
	<NcContent app-name="grocerylist">
		<NcAppNavigation>
			<NcAppNavigationNew :text="t('grocerylist', 'New list')"
				:disabled="loading"
				@click="newGroceryList">
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
	NcContent,
} from '@nextcloud/vue'

import axios from '@nextcloud/axios'
import NavigationGroceryListItem from './components/NavigationGroceryListItem.vue'
import IconPlus from 'vue-material-design-icons/Plus.vue'

export default {
	name: 'App',
	components: {
		IconPlus,
		NcContent,
		NcAppContent,
		NcAppNavigation,
		NcAppNavigationItem,
		NcAppNavigationNew,
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
			modal: false,
		}
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
		},
		async newGroceryList() {
			try {
				const { data } = await axios.post(generateUrl('/apps/grocerylist/api/lists'), { title: 'New list' })
				this.groceryLists.push(data)
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', 'Could not create groceryList'))
			}
		}
		,
	},
}
</script>
<style>
/*
#app-content > div {
	width: 100%;
	height: 100%;
	padding: 20px;
	display: flex;
	flex-direction: column;
	flex-grow: 1;
}
*/
</style>

<template>
	<NcContent app-name="grocerylist">
		<NcAppNavigation>
			<NcAppNavigationNew v-if="!loading"
				:text="t('grocerylist', 'New list')"
				:disabled="false"
				button-id="new-grocerylist-button"
				button-class="icon-add"
				@click="newGroceryList" />
			<ul>
				<NavigationGroceryListItem v-for="groceryList in groceryLists"
					:key="groceryList.id"
					:title="groceryList.title ? groceryList.title : t('grocerylist', 'New list')"
					:grocery-list="groceryList"
					:current-grocery-list-id="currentGroceryListId" />
			</ul>
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
	NcAppNavigationNew,
	NcAppNavigation,
	NcAppContent,
	NcContent,
} from '@nextcloud/vue'

import axios from '@nextcloud/axios'
import NavigationGroceryListItem from './components/NavigationGroceryListItem.vue'

export default {
	name: 'App',
	components: {
		NcContent,
		NcAppContent,
		NcAppNavigation,
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

			const response = await axios.get(generateUrl('/apps/grocerylist/api/lists'))
			this.groceryLists = response.data
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
		async newGroceryList() {
			try {
				await axios.post(generateUrl('/apps/grocerylist/api/lists'), { title: '' })
				// this.currentGroceryListId = response.data.id
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', 'Could not create groceryList'))
			}
			/*
    this.groceryLists.push({
      id: -1,
      title: '',
      user_id: '',
      showOnlyUnchecked: false,
    })
       */
		}
		,
	},
}
</script>
<style scoped>
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

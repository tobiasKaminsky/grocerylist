<template>
	<div id="content" class="app-grocerylist">
		<AppNavigation>
			<AppNavigationNew v-if="!loading"
												:text="t('grocerylist', 'New list')"
												:disabled="false"
												button-id="new-grocerylist-button"
												button-class="icon-add"
												@click="newGroceryList"/>
			<ul>
				<NavigationGroceryListItem v-for="groceryList in groceryLists"
																	 :key="groceryList.id"
																	 :title="groceryList.title ? groceryList.title : t('grocerylist', 'New list')"
																	 :groceryList="groceryList"
																	 :currentGroceryListId="currentGroceryListId"
				/>
			</ul>
		</AppNavigation>
		<AppContent>
			<router-view/>
		</AppContent>
	</div>
</template>

<script>
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import AppContent from '@nextcloud/vue/dist/Components/AppContent'
import AppNavigation from '@nextcloud/vue/dist/Components/AppNavigation'
import AppNavigationItem from '@nextcloud/vue/dist/Components/AppNavigationItem'
import AppNavigationNew from '@nextcloud/vue/dist/Components/AppNavigationNew'
import Content from '@nextcloud/vue'
import dropdown from 'vue-dropdowns'
import axios from '@nextcloud/axios'
import Multiselect from '@nextcloud/vue/dist/Components/Multiselect'
import NavigationGroceryListItem from "./components/NavigationGroceryListItem"
import VueRouter from 'vue-router';

export default {
	name: 'App',
	components: {
		ActionButton,
		AppContent,
		AppNavigation,
		AppNavigationItem,
		AppNavigationNew,
		Multiselect,
		NavigationGroceryListItem,
		Content
	},
	data: function () {
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
		 * @returns {Object|null}
		 */
		currentGroceryList () {
			if (this.currentGroceryListId === null) {
				return null
			}
			return this.groceryLists.find((groceryList) => groceryList.id === this.currentGroceryListId)
		},

		/**
		 * Returns true if a list is selected and its title is not empty
		 * @returns {Boolean}
		 */
		savePossible () {
			return this.currentGroceryList && this.currentGroceryList.title !== ''
		},
	},
	created () {
		this.onCreated()
	},
	/**
	 * Fetch list of groceryLists when the component is loaded
	 */
	async mounted () {
		try {
			if (this.$route.name === "lists") {
				console.warn("show single list" + this.$route.params)
			}

			const response = await axios.get(OC.generateUrl('/apps/grocerylist/lists'))
			this.groceryLists = response.data
		} catch (e) {
			console.error(e)
			OCP.Toast.error(t('grocerylist', 'Could not fetch groceryLists'))
		}
		this.loading = false
	},
	methods: {
		onCreated () {
			if (this.$route.name === "lists") {
				console.warn("show single list" + this.$route.params)
			}
		},
		newGroceryList () {
			this.currentGroceryListId = -1
			this.groceryLists.push({
				id: -1,
				title: '',
				user_id: '',
				showOnlyUnchecked: false,
			})
		},
	},
}
</script>
<style scoped>
#app-content > div {
	width: 100%;
	height: 100%;
	padding: 20px;
	display: flex;
	flex-direction: column;
	flex-grow: 1;
}
</style>

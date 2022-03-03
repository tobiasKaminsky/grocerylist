<template>
	<AppNavigationItem
			:title="title"
			:icon="icon"
			:editable="true"
			:editLabel="rename"
			:to="{ name: 'list', params: { id: groceryList.id.toString() } }"
			:class="{active: currentGroceryListId === groceryList.id}"
			@click="openGroceryList(groceryList)"
			@update:title="onRename">
		<template #actions>
			<ActionButton
					icon="icon-rename"
					@click="openSettings(groceryList)">
				{{ t('grocerylist', 'Settings') }}
			</ActionButton>
			<ActionButton
					icon="icon-delete"
					@click="deleteGroceryList(groceryList)">
				{{ t('grocerylist', 'Delete list') }}
			</ActionButton>
		</template>
	</AppNavigationItem>
</template>
<script>
import {
	ActionButton,
	ActionSeparator,
	AppNavigationItem,
} from '@nextcloud/vue'
import axios from "@nextcloud/axios";

export default {
	name: 'NavigationGroceryListItem',
	components: {
		ActionButton,
		ActionSeparator,
		AppNavigationItem,
	},
	props: {
		groceryList: {
			type: Object,
			required: true,
		},
		currentGroceryListId: {
			type: Number,
			required: true,
		}
	},
	data () {
		return {}
	},
	computed: {
		icon () {
			return 'icon-toggle-filelist';
		},
		rename () {
			return "Rename " + this.groceryList.title;
		},
		title () {
			return this.groceryList.title;
		}
	},
	methods: {
		async onRename (newTitle) {
			this.updating = true
			this.groceryList.title = newTitle

			try {
				if (this.groceryList.id === -1) {
					const response = await axios.post(OC.generateUrl(`/apps/grocerylist/lists`), this.groceryList)
					this.currentGroceryListId = response.data.id
				} else {
					await axios.post(OC.generateUrl(`/apps/grocerylist/lists/${this.groceryList.id}`), this.groceryList)
				}
			} catch (e) {
				console.error(e)
				OCP.Toast.error(t('grocerylist', 'Could not create groceryList'))
			}
			this.updating = false
		},
		async deleteGroceryList (groceryList) {
			try {
				await axios.delete(OC.generateUrl(`/apps/grocerylist/lists/${this.groceryList.id}`))
				// this.groceryLists.splice(this.groceryLists.indexOf(groceryList), 1)
				// if (this.currentGroceryListId === groceryList.id) {
				// 	this.currentGroceryListId = null
				// }
				OCP.Toast.success(t('grocerylist', 'GroceryList deleted'))
			} catch (e) {
				console.error(e)
				OCP.Toast.error(t('grocerylist', 'Could not delete groceryList'))
			}
		},
		openSettings (groceryList) {
			this.$router.push({
				name: "settings",
				params: {
					listId: groceryList.id
				}
			})
		},
		openGroceryList (groceryList) {
			this.$router.push({
				name: "list",
				params: {
					listId: groceryList.id
				}
			})
		}
	}
}
</script>


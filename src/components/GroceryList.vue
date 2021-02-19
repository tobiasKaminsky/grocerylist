<template>
	<div>
		<h1>{{ title }}</h1>
		<div>
			<ActionButton icon="icon-toggle"
										:style="{opacity: groceryList != null && groceryList.showOnlyUnchecked ? '1': '0.2'}"
										@click="toggleVisibility()">
				Show only unchecked
			</ActionButton>
			<input v-model="newItemQuantity"
						 placeholder="Quantity…"
						 :disabled="updating"
						 style="width: 20%">
			<input v-model="newItemName"
						 v-on:keyup.enter="onSaveItem()"
						 placeholder="Item…"
						 :disabled="updating"
						 style="width: 30%">
			<dropdown :options="allCategories"
								:label="name"
								:selected="object"
								v-model="newItemCategory"
								:closeOnOutsideClick="true"
								v-on:updateOption="updateNewItemCategory"
								id="dropdown"
								style="width: 20%">
			</dropdown>
			<ActionButton icon="icon-add"
										@click="onSaveItem()">
			</ActionButton>

			<br/>
			<span v-for="category in filteredCategories">
				<span style="font-size: larger; text-transform: uppercase;">
					{{ category.name }}
				</span>
				<ul>
						<li v-for="item in filteredItems"
								v-if="item.category === category.id">
								<input type="checkbox"
											 :checked="item.checked === true"
											 @click="checkItem(item)">
								<span @click="editItem(item)"
											v-bind:style="(item.checked === true) ? 'text-decoration: line-through' : ''">
									<span v-if="item.quantity !==  ''">
										{{ item.quantity }}
									</span>
									{{ item.name }}
								</span>
						</li>
					</ul>
				</span>
		</div>
	</div>
</template>

<script>
import axios from "@nextcloud/axios";
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton';
import dropdown from "vue-dropdowns";

export default {
	name: "GroceryList",
	components: {
		ActionButton,
		dropdown,
	},
	listId: '',
	computed: {
		name () {
			return "Test"
		},
		title () {
			return this.groceryList === null ? "" : this.groceryList.title;
		},
		filteredCategories () {
			if (this.categories == null) {
				return
			}

			return this.categories.filter(category => {
				if (this.groceryList.showOnlyUnchecked) {
					return this.filteredItems.filter(item => {
						return item.category === category.id
					}).length > 0
				} else {
					return true
				}
			})
		},
		filteredItems () {
			if (this.items == null) {
				return
			}

			return this.items.filter(item => {
				if (this.groceryList.showOnlyUnchecked) {
					return item.checked === false
				} else {
					return true
				}
			})
		}
	},
	data: function () {
		return {
			listId: this.$attrs['listId'],
			groceryList: null,
			categories: null,
			allCategories: null,
			items: null,
			itemsAll: null,
			updating: false,
			category: '',
			newCategoryId: -1,
			loading: false,
			newItemId: -1,
			newItemName: '',
			newItemQuantity: '',
			newItemCategory: null,
			object: {
				name: 'Select a category…',
			},
		}
	},
	async mounted () {
		console.warn("Mounted GroceryList " + this.listId)
		await this.loadGroceryList(this.listId)
		await this.loadCategories(this.listId)
		await this.loadAllCategories(this.listId)
		await this.loadItems(this.listId)
	},
	watch: {
		$route (to, from) {
			if (to.name !== from.name || to.params.listId !== from.params.listId) {
				console.warn("Route: " + to.params.listId)
				this.listId = to.params.listId
				this.loadGroceryList(this.listId)
				this.loadCategories(this.listId)
				this.loadAllCategories(this.listId)
				this.loadItems(this.listId)
			}
		}
	},
	methods: {
		toggleVisibility () {
			this.groceryList.showOnlyUnchecked = !this.groceryList.showOnlyUnchecked ? 1 : 0
			this.updateGroceryList(this.groceryList)
		},
		async updateGroceryList (groceryList) {
			this.updating = true
			try {
				await axios.post(OC.generateUrl(`/apps/grocerylist/lists/${groceryList.id}`), groceryList)
			} catch (e) {
				console.error(e)
				OCP.Toast.error(t('grocerylist', 'Could not update groceryList'))
			}
			this.updating = false
		},
		updateNewItemCategory (category) {
			this.newItemCategory = category;
		},
		async loadGroceryList (id) {
			try {
				const response = await axios.get(OC.generateUrl('/apps/grocerylist/list/' + id))
				this.groceryList = response.data
			} catch (e) {
				console.error(e)
				OCP.Toast.error(t('grocerylist', ('Could not fetch list ' + id)))
			}
			this.loading = false
		},
		async loadCategories (id) {
			try {
				const response = await axios.get(OC.generateUrl('/apps/grocerylist/categories/' + id))
				this.categories = response.data
			} catch (e) {
				console.error(e)
				OCP.Toast.error(t('grocerylist', ('Could not fetch categories for groceryList ' + id)))
			}
			this.loading = false
		},
		async loadAllCategories (id) {
			try {
				const response = await axios.get(OC.generateUrl('/apps/grocerylist/all_categories/' + id))
				this.allCategories = response.data
				this.newItemCategory = this.allCategories[0]
			} catch (e) {
				console.error(e)
				OCP.Toast.error(t('grocerylist', ('Could not fetch all categories for groceryList ' + id)))
			}
			this.loading = false
		},
		async loadItems (id) {
			try {
				const response = await axios.get(OC.generateUrl('/apps/grocerylist/items/' + id))
				console.warn("Load items for " + id)
				this.items = response.data;
				console.warn("Size: " + this.items.length)

				this.items.sort((a, b) => {
					if (a.checked === b.checked) {
						if (a.category === b.category) {
							return a.name > b.name ? 1 : -1;
						} else {
							return a.category - b.category;
						}
					} else {
						return a.checked - b.checked;
					}
				});
			} catch (e) {
				console.error(e)
				OCP.Toast.error(t('grocerylist', ('Could not fetch items for groceryList ' + id)))
			}
			this.loading = false
		},
		async checkItem (item) {
			this.updating = true
			if (item.checked === true) {
				item.checked = false
			} else {
				item.checked = true
			}
			try {
				await axios.post(OC.generateUrl(`/apps/grocerylist/item/check`),
						{id: item.id, checked: item.checked}
				)

				this.items.sort((a, b) => {
					if (a.checked === b.checked) {
						if (a.category === b.category) {
							return a.name > b.name ? 1 : -1;
						} else {
							return a.category - b.category;
						}
					} else {
						return a.checked - b.checked;
					}
				});

				this.updating = false
			} catch (e) {
				console.error(e)
				OCP.Toast.error(t('grocerylist', 'Could not check item'))
			}
		},
		async editItem (item) {
			this.newItemId = item.id;
			this.newItemName = item.name;
			this.newItemQuantity = item.quantity;
			this.object = this.categories.find(i => i.id === item.category);
			this.newItemCategory = item.category;
		},
		async onSaveItem () {
			if (this.newItemId === -1) {
				await this.addItem();
			} else {
				await this.updateItem();
			}
		},
		async addItem () {
			this.updating = true
			try {
				const response = await axios.post(OC.generateUrl(`/apps/grocerylist/item/add`),
						{
							name: this.newItemName,
							quantity: this.newItemQuantity,
							category: this.newItemCategory.id,
							list: this.listId
						}
				)

				await this.loadItems(this.listId);
				await this.loadCategories(this.listId)

				this.newItemName = "";
				this.newItemQuantity = "";
			} catch (e) {
				console.error(e)
				OCP.Toast.error(t('grocerylist', 'Could not add item'))
			}
			this.updating = false
		},
		async updateItem () {
			this.updating = true
			try {
				const response = await axios.post(OC.generateUrl(`/apps/grocerylist/item/update`),
						{
							id: this.newItemId,
							name: this.newItemName,
							quantity: this.newItemQuantity,
							category: this.newItemCategory.id
						}
				)

				await this.loadItems(this.listId);
				this.newItemName = "";
				this.newItemQuantity = "";
				this.newItemCategory = null;
			} catch (e) {
				console.error(e)
				OCP.Toast.error(t('grocerylist', 'Could not add item'))
			}
			this.updating = false
		},
	}
}
</script>
<style lang="scss">
.modal, .content {
	background: #fff;
	width: 80%;
	max-width: 480px;
	max-height: 60%;
	box-shadow: 0 15px 30px 0 rgba(0, 0, 0, 0.11), 0 5px 15px 0 rgba(0, 0, 0, 0.08);
	border-radius: 3px;
	border: 1px solid darkslategray;
	padding: 1rem;
	position: absolute;
	top: 0;
	right: 0;
	left: 0;
	bottom: 0;
	margin: auto;

	code {
		background: #e4e4e4;
		border-radius: 3px;
		padding: 0.25rem 0.5rem;
		margin: 0.5rem 0;
		color: crimson;
	}
}
</style>
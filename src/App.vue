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
				<AppNavigationItem v-for="groceryList in groceryLists"
								   :key="groceryList.id"
								   :title="groceryList.title ? groceryList.title : t('grocerylist', 'New list')"
								   :class="{active: currentGroceryListId === groceryList.id}"
								   @click="openGroceryList(groceryList)">
					<template slot="actions">
						<ActionButton v-if="groceryList.id === -1"
									  icon="icon-close"
									  @click="cancelNewGroceryList(groceryList)">
							{{ t('grocerylist', 'Cancel list creation') }}
						</ActionButton>
						<ActionButton v-else
									  icon="icon-delete"
									  @click="deleteGroceryList(groceryList)">
							{{ t('grocerylist', 'Delete list') }}
						</ActionButton>
					</template>
				</AppNavigationItem>
			</ul>
		</AppNavigation>
		<AppContent>
			<div v-if="currentGroceryList">
				<input ref="title"
					   v-model="currentGroceryList.title"
					   type="text"
					   :disabled="updating"
                       style="width: 10%">
                <input type="button"
                       class="primary"
                       :value="t('grocerylist', 'Save')"
                       :disabled="updating || !savePossible"
                       @click="saveGroceryList"
                style="width: 5%">
                <Modal btnText="Edit categories"
					   icon="icon-delete"
                       :closeBtn="true"
                       closeBtnContent="<span>X</span>"
                >
					<EditCategories
							:listId="currentGroceryListId"
							:categories="categories"
					/>
                </Modal>
                <span>
                    <input v-model="newItemQuantity"
                           placeholder="Quantity…"
                           :disabled="updating"
                           style="width: 20%">
                    <input v-model="newItemName"
                           placeholder="Item…"
                           :disabled="updating"
                           style="width: 30%">
                    <dropdown :options="categories"
                              :selected="object"
                              v-model="newItemCategory"
                              :placeholder="'Select a category'"
                              :closeOnOutsideClick="true"
                              v-on:updateOption="updateNewItemCategory"
                              id="dropdown"
                           style="width: 20%">
                    </dropdown>
                    <ActionButton icon="icon-add"
                                  @click="onSaveItem()">
                    </ActionButton>
                </span>
                    <span v-for="category in categories">
                        <div style="font-size: larger; text-transform: uppercase;">{{ category.name }}</div>
                        <ul>
                            <li v-for="item in items"
                                v-if="item.category == category.id">
                                <input type="checkbox" :checked="item.checked === true" @click="checkItem(item)">
                                <span @click="editItem(item)">
									<span v-if="item.quantity !==  ''"> {{ item.quantity }} </span>
									{{ item.name  }}
								</span>
                            </li>
                        </ul>

                    </span>
			</div>
			<div v-else id="emptycontent">
				<div class="icon-file"/>
				<h2>{{ t('grocerylist', 'Create a list to get started') }}</h2>
			</div>
		</AppContent>
	</div>
</template>

<script>
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import AppContent from '@nextcloud/vue/dist/Components/AppContent'
import AppNavigation from '@nextcloud/vue/dist/Components/AppNavigation'
import AppNavigationItem from '@nextcloud/vue/dist/Components/AppNavigationItem'
import AppNavigationNew from '@nextcloud/vue/dist/Components/AppNavigationNew'
import dropdown from 'vue-dropdowns'
import Modal from '@melmacaluso/vue-modal'
import axios from '@nextcloud/axios'
import EditCategories from "./components/EditCategories";

export default {
	name: 'App',
	components: {
        EditCategories,
		ActionButton,
		AppContent,
		AppNavigation,
		AppNavigationItem,
		AppNavigationNew,
        dropdown,
	},
	data: function () {
		return {
		    newItemId: -1,
            newItemName: '',
            newItemQuantity: '',
            newItemCategory: null,
            groceryLists: [],
            items: [],
            categories: [],
			currentGroceryListId: null,
			updating: false,
			loading: true,
            object: {
                name: 'Select a category…',
            },
		}
	},
	computed: {
		/**
		 * Return the currently selected groceryList object
		 * @returns {Object|null}
		 */
		currentGroceryList() {
			if (this.currentGroceryListId === null) {
				return null
			}
			return this.groceryLists.find((groceryList) => groceryList.id === this.currentGroceryListId)
		},

		/**
		 * Returns true if a list is selected and its title is not empty
		 * @returns {Boolean}
		 */
		savePossible() {
			return this.currentGroceryList && this.currentGroceryList.title !== ''
		},
	},
	/**
	 * Fetch list of groceryLists when the component is loaded
	 */
	async mounted() {
		try {
			const response = await axios.get(OC.generateUrl('/apps/grocerylist/lists'))
			this.groceryLists = response.data
		} catch (e) {
			console.error(e)
			OCP.Toast.error(t('grocerylist', 'Could not fetch groceryLists'))
		}
		this.loading = false
	},
	methods: {
	    updateNewItemCategory(category) {
	        this.newItemCategory = category;
        },
		/**
		 * Create a new groceryList and focus the list content field automatically
		 * @param {Object} groceryList GroceryList object
		 */
		openGroceryList(groceryList) {
			if (this.updating) {
				return
			}
			this.currentGroceryListId = groceryList.id

            this.loadItems(groceryList.id)
            this.loadCategories(groceryList.id)
		},
		/**
		 * Action triggered when clicking the save button
		 * create a new groceryList or save
		 */
		saveGroceryList() {
			if (this.currentGroceryListId === -1) {
				this.createGroceryList(this.currentGroceryList)
			} else {
				this.updateGroceryList(this.currentGroceryList)
			}
		},
		/**
		 * Create a new groceryList and focus the groceryList content field automatically
		 * The groceryList is not yet saved, therefore an id of -1 is used until it
		 * has been persisted in the backend
		 */
		newGroceryList() {
			if (this.currentGroceryListId !== -1) {
				this.currentGroceryListId = -1
				this.groceryLists.push({
					id: -1,
					title: '',
					content: '',
				})
				this.$nextTick(() => {
					this.$refs.title.focus()
				})
			}
		},
		/**
		 * Abort creating a new groceryList
		 */
		cancelNewGroceryList() {
			this.groceryLists.splice(this.groceryLists.findIndex((groceryList) => groceryList.id === -1), 1)
			this.currentGroceryListId = null
		},
        editItem(item) {
            this.newItemId = item.id;
            this.newItemName = item.name;
            this.newItemQuantity = item.quantity;
            this.object = this.categories.find(i => i.id === item.category);
            this.newItemCategory = item.category;
        },
        async onSaveItem() {
		    if (this.newItemId === -1) {
		        this.addItem();
            } else {
		        this.updateItem();
            }
        },
        async addItem() {
            this.updating = true
            try {
                const response = await axios.post(OC.generateUrl(`/apps/grocerylist/lists/add`),
                        {name: this.newItemName,
                            quantity: this.newItemQuantity,
                            category: this.newItemCategory.id,
                            list: this.currentGroceryListId }
                )

                await this.loadItems(this.currentGroceryListId);
                this.newItemName = "";
                this.newItemQuantity = "";
                this.newItemCategory = null;
            } catch (e) {
                console.error(e)
                OCP.Toast.error(t('grocerylist', 'Could not add item'))
            }
            this.updating = false
        },
        async updateItem() {
            this.updating = true
            try {
                const response = await axios.post(OC.generateUrl(`/apps/grocerylist/item/update`),
                        {id: this.newItemId,
                            name: this.newItemName,
                            quantity: this.newItemQuantity,
                            category: this.newItemCategory.id}
                            )

                await this.loadItems(this.currentGroceryListId);
                this.newItemName = "";
                this.newItemQuantity = "";
                this.newItemCategory = null;
            } catch (e) {
                console.error(e)
                OCP.Toast.error(t('grocerylist', 'Could not add item'))
            }
            this.updating = false
        },
        async checkItem(item) {
            this.updating = true
            if (item.checked === true) {
                item.checked = false
            } else {
                item.checked = true
            }
            try {
                await axios.post(OC.generateUrl(`/apps/grocerylist/item/check`),
                        {id: item.id, checked: item.checked }
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
		/**
		 * Create a new groceryList by sending the information to the server
		 * @param {Object} groceryList GroceryList object
		 */
		async createGroceryList(groceryList) {
			this.updating = true
			try {
				const response = await axios.post(OC.generateUrl(`/apps/grocerylist/lists`), groceryList)
				const index = this.groceryLists.findIndex((match) => match.id === this.currentGroceryListId)
				this.$set(this.groceryLists, index, response.data)
				this.currentGroceryListId = response.data.id
			} catch (e) {
				console.error(e)
				OCP.Toast.error(t('grocerylist', 'Could not create groceryList'))
			}
			this.updating = false
		},
		/**
		 * Update an existing groceryList on the server
		 * @param {Object} groceryList GroceryList object
		 */
		async updateGroceryList(groceryList) {
			this.updating = true
			try {
				await axios.put(OC.generateUrl(`/apps/grocerylist/lists/${groceryList.id}`), groceryList)
			} catch (e) {
				console.error(e)
				OCP.Toast.error(t('grocerylist', 'Could not update groceryList'))
			}
			this.updating = false
		},
		/**
		 * Delete a groceryList, remove it from the frontend and show a hint
		 * @param {Object} groceryList GroceryList object
		 */
		async deleteGroceryList(groceryList) {
			try {
				await axios.delete(OC.generateUrl(`/apps/grocerylist/lists/${groceryList.id}`))
				this.groceryLists.splice(this.groceryLists.indexOf(groceryList), 1)
				if (this.currentGroceryListId === groceryList.id) {
					this.currentGroceryListId = null
				}
				OCP.Toast.success(t('grocerylist', 'GroceryList deleted'))
			} catch (e) {
				console.error(e)
				OCP.Toast.error(t('grocerylist', 'Could not delete groceryList'))
			}
		},
        /**
         * Fetch items of a groceryList
         */
        async loadItems(id) {
            try {
                const response = await axios.get(OC.generateUrl('/apps/grocerylist/items/' + id))
                this.items = response.data;

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
        /**
         * Fetch categories of a groceryList
         */
        async loadCategories(id) {
            try {
                const response = await axios.get(OC.generateUrl('/apps/grocerylist/categories/'+id))
                this.categories = response.data
            } catch (e) {
                console.error(e)
                OCP.Toast.error(t('grocerylist', ('Could not fetch categories for groceryList ' + id)))
            }
            this.loading = false
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

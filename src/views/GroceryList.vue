<template>
	<div class="page-wrapper">
		<div>
			<NcButton aria-label="Show add/edit modal"
				@click="showAddModal">
				<template #icon>
					<Plus :size="20" />
				</template>
				Add
			</NcButton>
		</div>
		<h1>{{ groceryList?.title ?? t('grocerylist', 'Grocery list') }}</h1>
		<div style="display: flex; flex-wrap: wrap;">
			<NcCheckboxRadioSwitch :checked="!!groceryList?.showOnlyUnchecked" type="switch" @update:checked="toggleVisibility">
				{{ t('grocerylist', 'Show only unchecked') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch v-if="!groceryList?.showOnlyUnchecked"
				type="switch"
				:checked.sync="hideCategory">
				{{ t('grocerylist', 'Hide category') }}
			</NcCheckboxRadioSwitch>
			<NcModal v-if="modal"
				ref="modalRef"
				:name="t('grocerylist', 'Add item')"
				@close="closeModal">
				<form class="modal__content"
					@submit.prevent="onSaveItem()">
					<p class="quantityRow">
						<NcTextField :value.sync="newItemQuantity"
							label="Quantity…"
							@keyup.up="increaseQuantity()"
							@keyup.down="decreaseQuantity()" />
						<NcButton aria-label="Increase quantity"
							style="display:inline-block;"
							type="tertiary"
							@click="increaseQuantity()">
							<template #icon>
								<Plus :size="20" />
							</template>
						</NcButton>
						<NcButton aria-label="Decrease quantity"
							style="display:inline-block;"
							type="tertiary"
							@click="decreaseQuantity()">
							<template #icon>
								<Minus :size="20" />
							</template>
						</NcButton>
					</p>
					<p>
						<NcTextField :value.sync="newItemName"
							label="Item…"
							@keyup.enter="onSaveItem()" />
					</p>
					<p>
						<NcSelect id="dropdown"
							v-model="newItemCategory"
							:options="allCategories"
							label="name"
							:value="object"
							:close-on-outside-click="true"
							@updateOption="updateNewItemCategory" />
					</p>
					<p class="button-list">
						<NcButton type="primary" native-type="submit" aria-label="Add item">
							<template #icon>
								<Plus :size="20" />
							</template>
							{{ t('grocerylist', 'Add item') }}
						</NcButton>
						<NcButton v-if="showDeleteButton"
							id="deleteButton"
							type="error"
							aria-label="Delete item"
							:hidden="newItemId === -1"
							@click="deleteItem()">
							<template #icon>
								<Delete :size="20" />
							</template>
							{{ t('grocerylist', 'Delete item') }}
						</NcButton>
					</p>
				</form>
			</NcModal>
		</div>
		<div>
			<div v-if="hideCategory">
				<ul class="list-item">
					<ListItem v-for="item in sortedItems"
						:key="item.id"
						:item="item"
						@edit="() => editItem(item)"
						@update="loadItems(listId)" />
				</ul>
			</div>
			<div v-else>
				<span v-for="category in filteredCategories" :key="category.id">
					<h2 style="font-size: larger; text-transform: uppercase;">
						{{ category.name }}
					</h2>
					<ul class="item-list">
						<ListItem v-for="item in filteredItems(category.id)"
							:key="item.id"
							:item="item"
							@edit="() => editItem(item)"
							@update="loadItems(listId)" />
					</ul>
				</span>
			</div>
		</div>
		<div class="fixed">
			<NcButton aria-label="Show add/edit modal"
				@click="showAddModal">
				<template #icon>
					<Plus :size="20" />
				</template>
			</NcButton>
		</div>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { showError, showInfo } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import {
	NcSelect,
	NcCheckboxRadioSwitch,
	NcButton,
	NcModal,
	NcTextField,
} from '@nextcloud/vue'
import Plus from 'vue-material-design-icons/Plus.vue'
import Minus from 'vue-material-design-icons/Minus.vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import { ref } from 'vue'
import ListItem from './../components/ListItem.vue'

export default {
	name: 'GroceryList',

	components: {
		NcCheckboxRadioSwitch,
		NcSelect,
		NcButton,
		NcTextField,
		Plus,
		Minus,
		Delete,
		NcModal,
		ListItem,
	},

	props: {
		listId: {
			type: Number,
			required: true,
		},
	},
	setup() {
		return {
			modalRef: ref(null),
		}
	},

	data() {
		return {
			groceryList: null,
			categories: null,
			allCategories: [],
			items: null,
			itemsAll: null,
			newCategoryId: -1,
			loading: false,
			newItemId: -1,
			newItemName: '',
			newItemQuantity: '',
			newItemCategory: null,
			object: {
				name: 'Select a category…',
			},
			showDeleteButton: false,
			modal: false,
			hideCategory: false,
		}
	},
	computed: {
		isSnoozed() {
			return (item) => item.hidden > (Math.floor(Date.now() / 1000) - (15 * 60))
		},
		name() {
			return 'Test'
		},
		title() {
			return this.groceryList === null ? '' : this.groceryList.title
		},
		allItems() {
			return this.items
		},
    sortedItems() {
      return this.items.sort((a, b) => {
        return a.name.toLowerCase().localeCompare(b.name.toLowerCase())
      })
    },
		filteredCategories() {
			if (this.categories == null) {
				return
			}

			return this.categories.filter(category => {
				if (this.groceryList.showOnlyUnchecked) {
					return this.filteredItems().filter(item => {
						return item.category === category.id
					}).length > 0
				} else {
					return true
				}
			})
		},
		filteredItems() {
			return (category) => {
				if (this.items == null) {
					return
				}

				return this.items.filter(item => {
					if (category && category !== item.category) {
						return false
					}

					if (this.groceryList.showOnlyUnchecked) {
						return item.checked === false && !this.isSnoozed(item)
					} else {
						return true
					}
				})
			}
		},
	},
	watch: {
		$route(to, from) {
			if (to.name !== from.name || to.params.listId !== from.params.listId) {
				console.warn('Route: ' + to.params.listId)
				// this.listId = to.params.listId
				this.loadGroceryList(this.listId)
				this.loadItems(this.listId)
				this.loadCategories(this.listId)
				this.loadAllCategories(this.listId)
			}
		},
	},
	async mounted() {
		console.warn('Mounted GroceryList ' + this.listId)
		await this.loadGroceryList(this.listId)
		await this.loadItems(this.listId)
		await this.loadCategories(this.listId)
		await this.loadAllCategories(this.listId)
	},
	methods: {
		showAddModal() {
			this.newItemId = -1
			this.newItemName = ''
			this.newItemQuantity = ''
			this.modal = true
		},
		closeModal() {
			this.modal = false
		},
		toggleVisibility() {
			this.groceryList.showOnlyUnchecked = !this.groceryList.showOnlyUnchecked ? 1 : 0
			this.updateGroceryList(this.listId, this.groceryList.showOnlyUnchecked)
			this.hideCategory = false
		},
		async updateGroceryList(id, value) {
			try {
				await axios.post(generateUrl('/apps/grocerylist/api/lists/' + id + '/visibility'),
					{
						showOnlyUnchecked: value,
					})
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', 'Could not update groceryList'))
			}
		},
		updateNewItemCategory(category) {
			this.newItemCategory = category
		},
		async loadGroceryList(id) {
			try {
				const response = await axios.get(generateUrl('/apps/grocerylist/api/list/' + id))
				this.groceryList = response.data
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', ('Could not fetch list ' + id)))
			}
			this.loading = false
		},
		async loadCategories(id) {
			try {
				const response = await axios.get(generateUrl('/apps/grocerylist/api/categories/' + id))
				this.categories = response.data
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', ('Could not fetch categories for groceryList ' + id)))
			}
			this.loading = false
		},
		async loadAllCategories(id) {
			try {
				const response = await axios.get(generateUrl('/apps/grocerylist/api/all_categories/' + id))
				this.allCategories = response.data
				this.newItemCategory = this.allCategories[0]
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', ('Could not fetch all categories for groceryList ' + id)))
			}
			this.loading = false
		},
		async loadItems(id) {
			try {
				const response = await axios.get(generateUrl('/apps/grocerylist/api/items/' + id))
				console.warn('Load items for ' + id)
				this.items = response.data
				console.warn('Size: ' + this.items.length)

				this.items.sort((a, b) => {
					if (a.checked === b.checked) {
						if (a.category === b.category) {
							return a.name.toLowerCase().localeCompare(b.name.toLowerCase())
						} else {
							return a.category - b.category
						}
					} else {
						return a.checked - b.checked
					}
				})
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', ('Could not fetch items for groceryList ' + id)))
			}
			this.loading = false
		},
		async editItem(item) {
			this.showDeleteButton = true
			this.newItemId = item.id
			this.newItemName = item.name
			this.newItemQuantity = item.quantity
			this.object.name = this.allCategories.find(i => i.id === item.category).name
			this.newItemCategory = this.allCategories.find(i => i.id === item.category)
			this.modal = true
		},
		increaseQuantity() {
			if (this.newItemQuantity === '') {
				this.newItemQuantity = 1
			}

			if (this.newItemQuantity >= 1000) {
				this.newItemQuantity = this.newItemQuantity + 1000
			} else if (this.newItemQuantity >= 100) {
				this.newItemQuantity = this.newItemQuantity + 100
			} else if (this.newItemQuantity >= 10) {
				this.newItemQuantity = this.newItemQuantity + 10
			} else {
				this.newItemQuantity = this.newItemQuantity + 1
			}
		},
		decreaseQuantity() {
			if (this.newItemQuantity > 1000) {
				this.newItemQuantity = this.newItemQuantity - 1000
			} else if (this.newItemQuantity > 100) {
				this.newItemQuantity = this.newItemQuantity - 100
			} else if (this.newItemQuantity > 10) {
				this.newItemQuantity = this.newItemQuantity - 10
			} else if (this.newItemQuantity > 2) {
				this.newItemQuantity = this.newItemQuantity - 1
			} else {
				this.newItemQuantity = ''
			}
		},
		async onSaveItem() {
			if (this.newItemName === '') {
				showInfo('Cannot add empty item!')
				return
			}

			if (this.newItemId === -1) {
				await this.addItem()
			} else {
				await this.updateItem()
			}

			this.closeModal()
		},
		async addItem() {
			try {
				await axios.post(generateUrl('/apps/grocerylist/api/item/add'),
					{
						name: this.newItemName.trim(),
						quantity: this.newItemQuantity,
						category: this.newItemCategory.id,
						list: this.listId,
					},
				)

				await this.loadItems(this.listId)
				await this.loadCategories(this.listId)

				this.newItemName = ''
				this.newItemQuantity = ''
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', 'Could not add item'))
			}
		},
		async updateItem() {
			try {
				await axios.post(generateUrl('/apps/grocerylist/api/item/update'),
					{
						id: this.newItemId,
						name: this.newItemName.trim(),
						quantity: this.newItemQuantity.toString(),
						category: this.newItemCategory.id,
					},
				)

				await this.loadItems(this.listId)
				this.newItemName = ''
				this.newItemQuantity = ''
				this.newItemCategory = null
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', 'Could not add item'))
			}
		},
		async deleteItem() {
			try {
				await axios.delete(generateUrl('/apps/grocerylist/api/item/' + this.newItemId))

				await this.loadItems(this.listId)
				this.newItemName = ''
				this.newItemQuantity = ''
				this.newItemCategory = null
				this.object.name = 'Select a category…'
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', 'Could not delete item'))
			}

			this.showDeleteButton = false
			this.modal = false
		},
	},
}
</script>

<style lang="scss">
// Wrapper around all of the view content
.page-wrapper {
  width: 100%;
  max-width: 900px;
  // center
  margin-inline: auto;
  margin-block: var(--app-navigation-padding);
  // ensure we do not conflict with App Navigation toggle
  padding-inline: calc(44px + 2 * var(--app-navigation-padding));
}

h1 {
  color: var(--color-text-light);
  font-weight: bold;
  font-size: 24px;
  line-height: 30px;
  text-align: center;
  // to align with the toggle we need 44px (the toggle) - 30px (h2 line-height) / 2 + padding => 7px + padding
  margin-block: calc(7px + var(--app-navigation-padding)) 12px;
}

.modal__content {
  margin: 50px;

  p {
    margin-bottom: calc(var(--default-grid-baseline) * 4);

    &.quantityRow {
      display: flex;

      input {
        flex-grow: 1;
      }
    }
  }
}

.button-list {
	display: flex;
	justify-content: end;
}

div.fixed {
  position: fixed;
  bottom: 30px;
  right: 30px;
}

.item-list li {
	display: flex;
	align-items: center;
}

.item-list li.snoozed {
	opacity: 0.7;
}
</style>

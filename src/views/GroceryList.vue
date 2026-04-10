<template>
	<div class="page-wrapper">
		<div class="page-header">
			<h1>{{ groceryList?.title ?? t('grocerylist', 'Grocery list') }}</h1>
			<NcButton type="primary"
				:aria-label="t('grocerylist', 'Add item')"
				@click="showAddModal">
				<template #icon>
					<Plus :size="20" />
				</template>
				{{ t('grocerylist', 'Add') }}
			</NcButton>
		</div>
		<div class="page-items">
			<div v-if="hideCategory">
				<draggable v-model="sortedItemsList"
					tag="ul"
					class="item-list"
					item-key="id"
					handle=".drag-handle"
					:animation="200">
					<template #item="{ element }">
						<ListItem :item="element"
							:category-color="categoryColorForItem(element)"
							@edit="() => editItem(element)"
							@update="loadItems(listId)" />
					</template>
				</draggable>
			</div>
			<div v-else>
				<div v-if="uncategorizedItems.length > 0">
					<h2 class="category-heading" @click="toggleCollapsed('uncategorized')">
						<ChevronDown v-if="!collapsedCategories['uncategorized']" :size="20" />
						<ChevronRight v-else :size="20" />
						{{ t('grocerylist', 'Uncategorized') }}
					</h2>
					<draggable v-show="!collapsedCategories['uncategorized']"
						:model-value="uncategorizedItems"
						tag="ul"
						class="item-list"
						item-key="id"
						group="items"
						handle=".drag-handle"
						:animation="200"
						@change="(evt) => onDragChange(evt, 0)">
						<template #item="{ element }">
							<ListItem :item="element"
								@edit="() => editItem(element)"
								@update="loadItems(listId)" />
						</template>
					</draggable>
				</div>
				<div v-for="category in filteredCategories" :key="category.id">
					<h2 class="category-heading" @click="toggleCollapsed(category.id)">
						<ChevronDown v-if="!collapsedCategories[category.id]" :size="20" :style="category.color ? { color: category.color } : {}" />
						<ChevronRight v-else :size="20" :style="category.color ? { color: category.color } : {}" />
						{{ category.name }}
					</h2>
					<draggable v-show="!collapsedCategories[category.id]"
						:model-value="filteredItems(category.id)"
						tag="ul"
						class="item-list"
						item-key="id"
						group="items"
						handle=".drag-handle"
						:animation="200"
						@change="(evt) => onDragChange(evt, category.id)">
						<template #item="{ element }">
							<ListItem :item="element"
								:category-color="category.color"
								@edit="() => editItem(element)"
								@update="loadItems(listId)" />
						</template>
					</draggable>
				</div>
			</div>
		</div>
		<div class="page-options">
			<NcCheckboxRadioSwitch :model-value="!!groceryList?.showOnlyUnchecked" type="switch" @update:model-value="toggleVisibility">
				{{ t('grocerylist', 'Show only unchecked') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch v-model="hideCategory"
				type="switch">
				{{ t('grocerylist', 'Hide category') }}
			</NcCheckboxRadioSwitch>
		</div>
		<NcModal v-if="modal"
			ref="modalRef"
			:name="newItemId === -1 ? t('grocerylist', 'Add item') : t('grocerylist', 'Edit item')"
			@close="closeModal">
			<form class="modal__content"
				@submit.prevent="onSaveItem()">
				<NcTextField v-model="newItemName"
					:label="t('grocerylist', 'Item name')"
					:placeholder="t('grocerylist', 'e.g. Milk, Bread, Eggs…')"
					@keyup.enter="onSaveItem()" />
				<div class="quantityRow">
					<NcTextField v-model="newItemQuantity"
						:label="t('grocerylist', 'Quantity')"
						:placeholder="t('grocerylist', '1')"
						@keyup.up="increaseQuantity()"
						@keyup.down="decreaseQuantity()" />
					<NcButton :aria-label="t('grocerylist', 'Increase quantity')"
						type="tertiary"
						@click="increaseQuantity()">
						<template #icon>
							<Plus :size="20" />
						</template>
					</NcButton>
					<NcButton :aria-label="t('grocerylist', 'Decrease quantity')"
						type="tertiary"
						@click="decreaseQuantity()">
						<template #icon>
							<Minus :size="20" />
						</template>
					</NcButton>
				</div>
				<NcSelect id="dropdown"
					v-model="newItemCategory"
					:options="allCategories"
					label="name"
					:placeholder="t('grocerylist', 'Select or create a category…')"
					:taggable="true"
					:create-option="(name) => ({ name, id: null })"
					:close-on-outside-click="true" />
				<div class="button-list">
					<NcButton type="primary" native-type="submit" :aria-label="t('grocerylist', 'Add item')">
						<template #icon>
							<Plus :size="20" />
						</template>
						{{ t('grocerylist', 'Add item') }}
					</NcButton>
					<NcButton v-if="showDeleteButton"
						id="deleteButton"
						type="error"
						:aria-label="t('grocerylist', 'Delete item')"
						:hidden="newItemId === -1"
						@click="deleteItem()">
						<template #icon>
							<Delete :size="20" />
						</template>
						{{ t('grocerylist', 'Delete item') }}
					</NcButton>
				</div>
			</form>
		</NcModal>
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
import ChevronDown from 'vue-material-design-icons/ChevronDown.vue'
import ChevronRight from 'vue-material-design-icons/ChevronRight.vue'
import Plus from 'vue-material-design-icons/Plus.vue'
import Minus from 'vue-material-design-icons/Minus.vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import { ref } from 'vue'
import draggable from 'vuedraggable'
import ListItem from './../components/ListItem.vue'

export default {
	name: 'GroceryList',

	components: {
		NcCheckboxRadioSwitch,
		NcSelect,
		NcButton,
		NcTextField,
		ChevronDown,
		ChevronRight,
		Plus,
		Minus,
		Delete,
		NcModal,
		ListItem,
		draggable,
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
			showDeleteButton: false,
			modal: false,
			hideCategory: false,
			collapsedCategories: {},
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
		sortedItemsList: {
			get() {
				if (!this.items) return []
				return [...this.items].sort((a, b) => {
					return a.name.toLowerCase().localeCompare(b.name.toLowerCase())
				})
			},
			set(value) {
				this.items = value
			},
		},
		uncategorizedItems() {
			if (this.items == null) {
				return []
			}
			return this.items.filter(item => {
				if (item.category) {
					return false
				}
				if (this.groceryList.showOnlyUnchecked) {
					return item.checked === false && !this.isSnoozed(item)
				}
				return true
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
		if (this.$route.query.hideCategory === '1') {
			this.hideCategory = true
		}
		await this.loadGroceryList(this.listId)
		await this.loadItems(this.listId)
		await this.loadCategories(this.listId)
		await this.loadAllCategories(this.listId)
	},
	methods: {
		showAddModal() {
			this.newItemId = -1
			this.newItemName = ''
			this.newItemQuantity = 1
			this.showDeleteButton = false
			this.modal = true
		},
		closeModal() {
			this.modal = false
		},
		toggleVisibility() {
			this.groceryList.showOnlyUnchecked = !this.groceryList.showOnlyUnchecked ? 1 : 0
			this.updateGroceryList(this.listId, this.groceryList.showOnlyUnchecked)
			this.hideCategory = !this.groceryList.showOnlyUnchecked
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
		toggleCollapsed(key) {
			this.collapsedCategories = {
				...this.collapsedCategories,
				[key]: !this.collapsedCategories[key],
			}
		},
		categoryColorForItem(item) {
			if (!item.category || !this.allCategories) {
				return null
			}
			const cat = this.allCategories.find(c => c.id === item.category)
			return cat?.color || null
		},
		async ensureCategoryExists() {
			if (!this.newItemCategory) {
				return 0
			}
			if (this.newItemCategory.id !== null) {
				return this.newItemCategory.id
			}
			const { data } = await axios.post(
				generateUrl(`/apps/grocerylist/api/category/${this.listId}/add`),
				{ name: this.newItemCategory.name },
			)
			this.allCategories = data
			const created = data.find((c) => c.name === this.newItemCategory.name)
			this.newItemCategory = created
			return created.id
		},
		async addItem() {
			try {
				const categoryId = await this.ensureCategoryExists()
				await axios.post(generateUrl('/apps/grocerylist/api/item/add'),
					{
						name: this.newItemName.trim(),
						quantity: this.newItemQuantity.toString(),
						category: categoryId,
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
				const categoryId = await this.ensureCategoryExists()
				await axios.post(generateUrl('/apps/grocerylist/api/item/update'),
					{
						id: this.newItemId,
						name: this.newItemName.trim(),
						quantity: this.newItemQuantity.toString(),
						category: categoryId,
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
		async onDragChange(evt, categoryId) {
			if (evt.added) {
				const item = evt.added.element
				if (item.category !== categoryId) {
					item.category = categoryId
					try {
						await axios.post(generateUrl('/apps/grocerylist/api/item/update'), {
							id: item.id,
							name: item.name,
							quantity: item.quantity?.toString() ?? '',
							category: categoryId,
						})
						await this.loadItems(this.listId)
						await this.loadCategories(this.listId)
					} catch (e) {
						console.error(e)
						showError(t('grocerylist', 'Could not update item category'))
					}
				}
			}
		},
		async deleteItem() {
			try {
				await axios.delete(generateUrl('/apps/grocerylist/api/item/' + this.newItemId))

				await this.loadItems(this.listId)
				this.newItemName = ''
				this.newItemQuantity = ''
				this.newItemCategory = null
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
  display: flex;
  flex-direction: column;
  width: 100%;
  max-width: 900px;
  height: 100%;
  overflow: hidden;
  // center
  margin-inline: auto;
  // ensure we do not conflict with App Navigation toggle
  padding-inline: calc(44px + 2 * var(--app-navigation-padding));

  @media (max-width: 768px) {
    padding-inline: calc(44px + var(--default-grid-baseline) * 2) calc(var(--default-grid-baseline) * 3);
  }
}

.page-header {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-block: calc(7px + var(--app-navigation-padding)) 12px;

  h1 {
    color: var(--color-text-light);
    font-weight: bold;
    font-size: 24px;
    line-height: 30px;
    margin: 0;
  }

  @media (max-width: 768px) {
    padding-block: 8px;

    h1 {
      font-size: 20px;
    }
  }
}


.page-items {
  flex: 1;
  overflow-y: auto;
  min-height: 0;
  // improve touch scrolling
  -webkit-overflow-scrolling: touch;
}

.page-options {
  flex-shrink: 0;
  display: flex;
  flex-wrap: wrap;
  gap: calc(var(--default-grid-baseline) * 2);
  padding-block: calc(var(--default-grid-baseline) * 2);

  @media (max-width: 768px) {
    flex-direction: column;
    gap: 0;
  }
}

.modal__content {
  padding: calc(var(--default-grid-baseline) * 4);
  display: flex;
  flex-direction: column;
  gap: calc(var(--default-grid-baseline) * 4);

  .quantityRow {
    display: flex;
    align-items: end;
    gap: calc(var(--default-grid-baseline) * 2);

    .input-field {
      flex-grow: 1;
    }
  }

  .v-select {
    width: 100%;
  }

  @media (max-width: 768px) {
    padding: calc(var(--default-grid-baseline) * 3);
    gap: calc(var(--default-grid-baseline) * 3);
  }
}

.button-list {
	display: flex;
	justify-content: end;
}

.category-heading {
	display: flex;
	align-items: center;
	gap: calc(var(--default-grid-baseline) * 1);
	font-size: larger;
	text-transform: uppercase;
	cursor: pointer;
	user-select: none;
	min-height: 44px;
}

.item-list {
	min-height: 10px;
}

.item-list li.snoozed {
	opacity: 0.7;
}

.sortable-ghost {
	opacity: 0.5;
	background: var(--color-background-hover);
}
</style>

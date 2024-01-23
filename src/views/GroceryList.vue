<template>
	<div class="page-wrapper">
		<h1>{{ groceryList?.title ?? t('grocerylist', 'Grocery list') }}</h1>
		<div>
			<NcCheckboxRadioSwitch :checked="!!groceryList?.showOnlyUnchecked" type="switch" @update:checked="toggleVisibility">
				{{ t('grocerylist', 'Show only unchecked') }}
			</NcCheckboxRadioSwitch>
			<input v-model="newItemQuantity"
				placeholder="Quantity…"
				:disabled="updating"
				style="width: 20%">
			<input v-model="newItemName"
				placeholder="Item…"
				:disabled="updating"
				style="width: 30%"
				@keyup.enter="onSaveItem()">
			<NcSelect id="dropdown"
				v-model="newItemCategory"
				:options="allCategories"
				label="name"
				:value="object"
				:close-on-outside-click="true"
				style="width: 20%"
				@updateOption="updateNewItemCategory" />
			<NcButton icon="icon-add"
				:disabled="!canSave"
				style="display:inline-block;"
				@click="onSaveItem()" />
			<NcButton v-if="showDeleteButton"
				id="deleteButton"
				icon="icon-delete"
				:disabled="!canSave"
				style="display:inline-block;"
				@click="deleteItem()" />

			<br>
			<span v-for="category in filteredCategories" :key="category.id">
				<h2 style="font-size: larger; text-transform: uppercase;">
					{{ category.name }}
				</h2>
				<ul>
					<li v-for="item in filteredItems"
						v-if="item.category === category.id">
						<div>
							<NcButton aria-label="Snooze"
								type="tertiary"
								style="display:inline-block;"
								@click="hideItem(item)">
								<template #icon>
									<AlarmSnooze :size="20" />
								</template>
							</NcButton>
							<NcCheckboxRadioSwitch :checked="item.checked === true"
								style="display:inline-block;"
								@update:checked="checkItem(item)" />
							<span @click="editItem(item)">
								<span v-if="item.quantity !== ''">
									{{ item.quantity }}
								</span>
								{{ item.name }}
							</span>
						</div>
					</li>
				</ul>
			</span>
		</div>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import {
	NcSelect,
	NcCheckboxRadioSwitch,
	NcButton,
} from '@nextcloud/vue'
import AlarmSnooze from 'vue-material-design-icons/AlarmSnooze.vue'

export default {
	name: 'GroceryList',

	components: {
		NcCheckboxRadioSwitch,
		NcSelect,
		NcButton,
		AlarmSnooze,
	},

	props: {
		listId: {
			type: Number,
			required: true,
		},
	},

	data() {
		return {
			groceryList: null,
			categories: null,
			allCategories: [],
			items: null,
			itemsAll: null,
			updating: false,
			canSave: true,
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
			showDeleteButton: false,
		}
	},
	computed: {
		name() {
			return 'Test'
		},
		title() {
			return this.groceryList === null ? '' : this.groceryList.title
		},
		filteredCategories() {
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
		filteredItems() {
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
		},
	},
	watch: {
		$route(to, from) {
			if (to.name !== from.name || to.params.listId !== from.params.listId) {
				console.warn('Route: ' + to.params.listId)
				this.listId = to.params.listId
				this.loadGroceryList(this.listId)
				this.loadCategories(this.listId)
				this.loadAllCategories(this.listId)
				this.loadItems(this.listId)
			}
		},
	},
	async mounted() {
		console.warn('Mounted GroceryList ' + this.listId)
		await this.loadGroceryList(this.listId)
		await this.loadCategories(this.listId)
    await this.loadItems(this.listId)
    await this.loadAllCategories(this.listId)
	},
	methods: {
		toggleSaveButton() {
			if (this.newItemName !== '') {
				this.canSave = true
			} else {
				this.canSave = false
			}
		},
		toggleVisibility() {
			this.groceryList.showOnlyUnchecked = !this.groceryList.showOnlyUnchecked ? 1 : 0
			this.updateGroceryList(this.listId, this.groceryList.showOnlyUnchecked)
		},
		async updateGroceryList(id, value) {
			this.updating = true
			try {
				await axios.post(generateUrl('/apps/grocerylist/api/lists/' + id + '/visibility'),
					{
						showOnlyUnchecked: value,
					})
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', 'Could not update groceryList'))
			}
			this.updating = false
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
		async checkItem(item) {
			this.updating = true
			if (item.checked === true) {
				item.checked = false
			} else {
				item.checked = true
			}
			try {
				await axios.post(generateUrl('/apps/grocerylist/api/item/check'),
					{ id: item.id, checked: item.checked },
				)

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

				this.updating = false
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', 'Could not check item'))
			}
		},
		async hideItem(item) {
			this.updating = true
			try {
				await axios.post(generateUrl('/apps/grocerylist/api/item/hide'),
					{ id: item.id },
				)

				await this.loadItems(this.listId)
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', 'Could not add item'))
			}
			this.updating = false
		},
		async editItem(item) {
			this.showDeleteButton = true
			this.newItemId = item.id
			this.newItemName = item.name
			this.newItemQuantity = item.quantity
			this.object.name = this.allCategories.find(i => i.id === item.category).name
			this.newItemCategory = this.allCategories.find(i => i.id === item.category)

			this.toggleSaveButton()
		},
		async onSaveItem() {
			if (this.newItemId === -1) {
				await this.addItem()
			} else {
				await this.updateItem()
			}

			this.toggleSaveButton()
		},
		async addItem() {
			this.updating = true
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
			this.updating = false
		},
		async updateItem() {
			this.updating = true
			try {
				await axios.post(generateUrl('/apps/grocerylist/api/item/update'),
					{
						id: this.newItemId,
						name: this.newItemName.trim(),
						quantity: this.newItemQuantity,
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
			this.updating = false
		},
		async deleteItem() {
			this.updating = true

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
			this.updating = false
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
</style>

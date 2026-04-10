<template>
	<div class="page-wrapper">
		<h2>{{ t('grocerylist', 'Categories') }}</h2>

		<NcEmptyContent v-if="loadingCategories" :name="t('grocerylist', 'Categories loading…')">
			<template #icon>
				<NcLoadingIcon :size="40" />
			</template>
		</NcEmptyContent>
		<NcEmptyContent v-else-if="categories.length === 0" class="category-list" :name="t('grocerylist', 'No categories, yet')">
			<template #icon>
				<IconTagOff :size="40" />
			</template>
		</NcEmptyContent>
		<draggable v-else
			:value="categories"
			tag="ul"
			class="category-list"
			handle=".list-category__drag-handle"
			ghost-class="list-category--ghost"
			@input="onReorder">
			<ListCategory v-for="category in categories" :key="category.id" :category="category" />
		</draggable>

		<ListCategoryNew :list-id="listId" />

		<div class="danger-zone">
			<h2>{{ t('grocerylist', 'Danger zone') }}</h2>
			<NcButton type="error"
				:disabled="deleting"
				:aria-label="t('grocerylist', 'Delete list')"
				@click="onDeleteList">
				<template #icon>
					<IconDelete :size="20" />
				</template>
				{{ t('grocerylist', 'Delete list') }}
			</NcButton>
		</div>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { DialogSeverity, getDialogBuilder, showError, showSuccess } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'
import { generateUrl } from '@nextcloud/router'
import { NcButton, NcEmptyContent, NcLoadingIcon } from '@nextcloud/vue'
import { mapStores } from 'pinia'
import draggable from 'vuedraggable'
import { useCategoryStore } from '../store/categoryStore.ts'
import IconDelete from 'vue-material-design-icons/Delete.vue'
import IconTagOff from 'vue-material-design-icons/TagOff.vue'

import ListCategory from '../components/ListCategory.vue'
import ListCategoryNew from '../components/ListCategoryNew.vue'

export default {
	name: 'ListSettings',

	components: {
		draggable,
		IconDelete,
		IconTagOff,
		ListCategory,
		ListCategoryNew,
		NcButton,
		NcEmptyContent,
		NcLoadingIcon,
	},

	props: {
		listId: {
			type: Number,
			required: true,
		},
	},

	data() {
		return {
			updating: false,
			loadingCategories: true,
			deleting: false,
			groceryList: null,
			sharees: null,
		}
	},

	computed: {
		// as we do not use the Composition API, this makes the store available using "this.categoryStore" (store id + 'Store')
		...mapStores(useCategoryStore),

		categories() {
			return this.categoryStore.categories[this.listId] ?? []
		},
	},

	watch: {
		listId() {
			this.loadGroceryList()
			this.loadCategories()
			this.loadSharees()
		},
	},

	mounted() {
		this.loadGroceryList()
		this.loadCategories()
		this.loadSharees()
	},

	methods: {
		/**
		 * Load the grocery list itself so we can show its title in the
		 * delete confirmation dialog.
		 */
		async loadGroceryList() {
			try {
				const response = await axios.get(generateUrl(`/apps/grocerylist/api/list/${this.listId}`))
				this.groceryList = response.data
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', 'Could not fetch list {id}', { id: this.listId }))
			}
		},

		/**
		 * Handle loading categories, sets the loading state and triggers store updates
		 */
		async loadCategories() {
			this.loadingCategories = true
			try {
				await this.categoryStore.loadCategories(this.listId)
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', ('Could not fetch categories')))
			}
			this.loadingCategories = false
		},

		async loadSharees() {
			try {
				const response = await axios.get(generateUrl(`/apps/grocerylist/api/sharees/${this.listId}`))
				this.sharees = response.data
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', ('Could not fetch sharees')))
			}
			this.loading = false
		},

		/**
		 * Persist the new category order after a drag-and-drop.
		 *
		 * @param {Array} orderedCategories The categories in their new order.
		 */
		async onReorder(orderedCategories) {
			try {
				await this.categoryStore.reorderCategories(this.listId, orderedCategories)
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', 'Could not save category order'))
				// Reload to get back to the authoritative order on failure
				this.loadCategories()
			}
		},

		/**
		 * Ask the user for confirmation and, if confirmed, delete the
		 * current grocery list. On success, navigate away from the now
		 * stale settings page and broadcast a `grocerylist:list-deleted`
		 * event on the Nextcloud event bus so the app navigation can
		 * drop the list from its sidebar.
		 */
		async onDeleteList() {
			const confirmed = await this.confirmListDeletion()
			if (!confirmed) {
				return
			}

			this.deleting = true
			try {
				await axios.delete(generateUrl(`/apps/grocerylist/api/lists/${this.listId}`))
				emit('grocerylist:list-deleted', { listId: this.listId })
				showSuccess(t('grocerylist', 'List deleted'))
				this.$router.replace('/')
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', 'Could not delete list'))
				this.deleting = false
			}
		},

		/**
		 * Build and show a confirmation dialog for deleting the list.
		 *
		 * @return {Promise<boolean>} Resolves to `true` when the user
		 * confirms the deletion, `false` otherwise (including when the
		 * dialog is dismissed without a button click).
		 */
		confirmListDeletion() {
			const title = this.groceryList?.title
			const text = title
				? t('grocerylist', 'Are you sure you want to delete the list "{title}"? This will also remove all its items and categories.', { title })
				: t('grocerylist', 'Are you sure you want to delete this list? This will also remove all its items and categories.')

			return new Promise((resolve) => {
				const dialog = getDialogBuilder(t('grocerylist', 'Delete list'))
					.setText(text)
					.setSeverity(DialogSeverity.Warning)
					.setButtons([
						{
							label: t('grocerylist', 'Cancel'),
							type: 'secondary',
							callback: () => resolve(false),
						},
						{
							label: t('grocerylist', 'Delete'),
							type: 'error',
							callback: () => resolve(true),
						},
					])
					.build()
				// The show() promise rejects when the dialog is closed
				// without pressing a button – treat that like a cancel.
				dialog.show().catch(() => resolve(false))
			})
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

.category-list {
	// Prevent issues when there are no categories and you add one (otherwise the content would "jump")
	min-height: 140px;

	// sortablejs placeholder while dragging
	.list-category--ghost {
		opacity: 0.4;
		background: var(--color-background-hover);
	}
}

.danger-zone {
	margin-top: 32px;
	padding-top: 16px;
	border-top: 1px solid var(--color-border);
}

h2 {
	margin-block: 10px 7px;
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

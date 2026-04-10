<template>
	<div class="settings-wrapper">
		<h2>{{ t('grocerylist', 'Categories') }}</h2>

		<ListCategoryNew :list-id="listId" />

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
			:model-value="categories"
			tag="ul"
			class="category-list"
			item-key="id"
			handle=".list-category__drag-handle"
			ghost-class="list-category--ghost"
			@change="onReorderEvent">
			<template #item="{ element }">
				<ListCategory :category="element" />
			</template>
		</draggable>

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
		 * vuedraggable v4 emits a `change` event with `{ moved: { oldIndex, newIndex } }`
		 * for in-list reordering. Compute the new order and delegate to onReorder.
		 *
		 * @param {object} event The vuedraggable change payload.
		 */
		onReorderEvent(event) {
			if (!event?.moved) {
				return
			}
			const { oldIndex, newIndex } = event.moved
			const reordered = [...this.categories]
			const [moved] = reordered.splice(oldIndex, 1)
			reordered.splice(newIndex, 0, moved)
			this.onReorder(reordered)
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
<style lang="scss" scoped>
.settings-wrapper {
	width: 100%;
	max-width: 900px;
	margin-inline: auto;
	padding-block: var(--app-navigation-padding);
	padding-inline: calc(44px + 2 * var(--app-navigation-padding));
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline) * 3);

	@media (max-width: 768px) {
		padding-inline: var(--default-grid-baseline, 4px);
	}
}

h2 {
	font-size: 24px;
	font-weight: bold;
	color: var(--color-text-light);
	margin: 0;
}

.category-list {
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline) * 1);
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
</style>

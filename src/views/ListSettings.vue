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
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import { NcEmptyContent, NcLoadingIcon } from '@nextcloud/vue'
import { mapStores } from 'pinia'
import draggable from 'vuedraggable'
import { useCategoryStore } from '../store/categoryStore.ts'
import IconTagOff from 'vue-material-design-icons/TagOff.vue'

import ListCategory from '../components/ListCategory.vue'
import ListCategoryNew from '../components/ListCategoryNew.vue'

export default {
	name: 'ListSettings',

	components: {
		draggable,
		IconTagOff,
		ListCategory,
		NcEmptyContent,
		NcLoadingIcon,
		ListCategoryNew,
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
			this.loadCategories()
			this.loadSharees()
		},
	},

	mounted() {
		this.loadCategories()
		this.loadSharees()
	},

	methods: {
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

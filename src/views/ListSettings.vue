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
		<ul v-else class="category-list">
			<ListCategory v-for="category in categories" :key="category.name" :category="category" />
		</ul>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import { NcEmptyContent, NcLoadingIcon } from '@nextcloud/vue'
import { mapStores } from 'pinia'
import { useCategoryStore } from '../store/categoryStore.ts'
import IconTagOff from 'vue-material-design-icons/TagOff.vue'

import ListCategory from '../components/ListCategory.vue'
import ListCategoryNew from '../components/ListCategoryNew.vue'

export default {
	name: 'ListSettings',

	components: {
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
}
</style>

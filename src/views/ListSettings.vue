<template>
	<div class="page-wrapper">
		<h1>{{ t('grocerylist', 'Settings for list {list}', { list: listId }) }}</h1>
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
		<ul v-else class="category-list">
			<ListCategory v-for="category in categories" :key="category.name" :category="category" />
		</ul>

		<h3>{{ t('grocerylist', 'Add a new category') }}</h3>
		<ListCategoryNew :list-id="listId" />

<!--		<h2>{{ t('grocerylist', 'Shares') }}</h2>-->
<!--		<span v-for="sharee in sharees" :key="sharee.userId">-->
<!--			<ul>-->
<!--				<li>{{ sharee.userId }}</li>-->
<!--			</ul>-->
<!--		</span>-->
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

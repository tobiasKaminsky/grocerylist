<template>
	<div class="page-wrapper">
		<h2>Categories for {{ listId }}</h2>

		<ul>
      {{ sortUp() }}
			<ListCategory v-for="category in categories.sort((a, b) => a.order > b.order)"
                    :key="category.id"
                    :category="category"
                    @sortUp="sortUp()"
                    @sortDown="sortDown()"
      />
		</ul>

		<div class="new-category__wrapper">
			<NcTextField :value.sync="newCategoryName"
				:disabled="updating"
				:label="t('grocerylist', 'Category name')"
				:show-trailing-button="newCategoryName !== ''"
				trailing-button-icon="close"
				@trailing-button-click="newCategoryName = ''" />

			<NcButton :aria-label="t('Add category {category}', { category: newCategoryName })">
				<template #icon>
					<IconPlus :size="20" />
				</template>
				{{ t('Add') }}
			</NcButton>
		</div>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import { NcButton, NcTextField } from '@nextcloud/vue'
import { mapStores } from 'pinia'
import { useCategoryStore } from '../store/categoryStore.ts'
import IconPlus from 'vue-material-design-icons/Plus.vue'

import ListCategory from '../components/ListCategory.vue'

export default {
	name: 'ListSettings',

	components: {
		IconPlus,
		ListCategory,
		NcButton,
		NcTextField,
	},

	props: {
		listId: {
			type: String,
			default: '',
		},
		categories: {
			type: Array,
			default: () => [],
		},
	},

	data() {
		return {
			updating: false,
			newCategoryName: '',
			newCategoryId: -1,
		}
	},

	computed: {
		// as we do not use the Composition API, this makes the store available using "this.categoryStore" (store id + 'Store')
		...mapStores(useCategoryStore),
	},

	methods: {
		editCategory(category) {
			this.newCategoryId = category.id
			this.category = category.name
		},
    sortUp() {
      console.error("sortup ")
    },
    sortDown() {
      console.error("sort down")
    },
		async onSaveCategory() {
			if (this.newCategoryId === -1) {
				this.addCategory()
			} else {
				this.updateCategory()
			}
		},

		/**
		 * Handle adding a new category
		 */
		async addCategory() {
			this.updating = true
			try {
				await this.categoryStore.createCategory(this.listId, this.category)
				this.category = ''
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', `Could not add category to list: ${this.listId}`))
			}
			this.updating = false
		},
		async updateCategory(listId) {
			this.updating = true
			try {
				const response = await axios.post(generateUrl('/apps/grocerylist/api/category/update'),
					{
						id: this.newCategoryId,
						newName: this.category,
					},
				)

				this.categories = response.data
				this.category = ''
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', 'Could not update category'))
			}
			this.updating = false
		},
	},
}
</script>
<style lang="scss" scoped>
.page-wrapper {
	max-width: 900px;
	margin-inline: auto;
}

.new-category__wrapper {
	display: flex;
	gap: 12px;
}
</style>

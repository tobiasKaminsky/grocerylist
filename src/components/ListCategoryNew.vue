<template>
	<div class="new-category">
		<NcTextField class="new-category__input"
			:value.sync="newCategoryName"
			:disabled="updating"
			:label="t('grocerylist', 'Category name')"
			:show-trailing-button="newCategoryName !== ''"
			trailing-button-icon="close"
			@trailing-button-click="newCategoryName = ''" />

		<NcButton :aria-label="t('grocerylist', 'Add category {category}', { category: newCategoryName })" @click="addCategory">
			<template #icon>
				<IconPlus :size="20" />
			</template>
			{{ t('grocerylist', 'Add') }}
		</NcButton>
	</div>
</template>

<script>
import { showError } from '@nextcloud/dialogs'
import { mapStores } from 'pinia'
import { useCategoryStore } from '../store/categoryStore.ts'
import {
	NcButton,
	NcTextField,
} from '@nextcloud/vue'

import IconPlus from 'vue-material-design-icons/Plus.vue'

export default {
	name: 'ListCategoryNew',

	components: {
		IconPlus,
		NcButton,
		NcTextField,
	},

	props: {
		/**
		 * ID of the list the category should be added
		 */
		listId: {
			type: Number,
			required: true,
		},
	},

	data() {
		return {
			/**
			 * Name of the new category
			 */
			newCategoryName: '',
		}
	},

	computed: {
		// as we do not use the Composition API, this makes the store available using "this.categoryStore" (store id + 'Store')
		...mapStores(useCategoryStore),
	},

	methods: {
		/**
		 * Handle adding a new category
		 */
		 async addCategory() {
			this.updating = true
			try {
				await this.categoryStore.createCategory(this.listId, this.newCategoryName)
				this.newCategoryName = ''
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', `Could not add category to list ${this.listId}`))
			}
			this.updating = false
		},
	},
}
</script>

<style scoped lang="scss">
.new-category {
	display: flex;
	align-items: center;
	gap: 12px;

	&__input {
		width: fit-content!important;
		// prevent resize on type (because of "clear"-button)
		min-width: 245px;
	}
}
</style>

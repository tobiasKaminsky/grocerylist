<!--
  - SPDX-FileCopyrightText: 2023 Tobias Kaminsky <tobias@kaminsky.me>
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="new-category">
		<NcTextField
			v-model="newCategoryName"
			class="new-category__input"
			:disabled="updating"
			:label="t('grocerylist', 'Category name')"
			:showTrailingButton="newCategoryName !== ''"
			trailingButtonIcon="close"
			@trailingButtonClick="newCategoryName = ''" />

		<NcButton
			variant="tertiary"
			:aria-label="t('grocerylist', 'Add category {category}', { category: newCategoryName })"
			@click="addCategory">
			<template #icon>
				<IconPlus :size="20" />
			</template>
		</NcButton>
	</div>
</template>

<script>
import { showError } from '@nextcloud/dialogs'
import {
	NcButton,
	NcTextField,
} from '@nextcloud/vue'
import { mapStores } from 'pinia'
import IconPlus from 'vue-material-design-icons/Plus.vue'
import logger from '../logger.js'
import { useCategoryStore } from '../store/categoryStore.ts'

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
				logger.error(`Could not add category to list ${this.listId}`, { error: e })
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
		flex: 1;
	}
}
</style>

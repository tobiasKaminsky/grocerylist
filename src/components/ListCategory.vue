<template>
	<li class="list-category">
		<span class="list-category__drag-handle"
			:aria-label="t('grocerylist', 'Drag to reorder')"
			:title="t('grocerylist', 'Drag to reorder')">
			<IconDrag :size="20" />
		</span>
		<NcColorPicker v-model="categoryColor"
			:aria-label="t('grocerylist', 'Category color')"
			@update:model-value="updateColor">
			<NcButton type="tertiary"
				:aria-label="t('grocerylist', 'Pick color')">
				<template #icon>
					<Circle :size="24" :fill-color="categoryColor || '#aaa'" />
				</template>
			</NcButton>
		</NcColorPicker>
		<NcTextField v-if="isEditing"
			ref="input"
			class="list-category__input"
			:label="t('grocerylist', 'Category name')"
			:loading="loading"
			v-model="newCategoryName" />
		<span v-else class="list-category__name">
			{{ category.name }}
		</span>
		<NcButton type="tertiary"
			:aria-label="isEditing ? t('grocerylist', 'Save changed category') : t('grocerylist', 'Edit category')"
			@click="toggleEditMode">
			<template #icon>
				<IconCheck v-if="isEditing" :size="20" />
				<IconPencil v-else :size="20" />
			</template>
		</NcButton>
		<NcButton type="tertiary"
			:aria-label="t('grocerylist', 'Delete category')"
			:disabled="deleting"
			@click="onDelete">
			<template #icon>
				<IconDelete :size="20" />
			</template>
		</NcButton>
	</li>
</template>

<script>
import { DialogSeverity, getDialogBuilder, showError } from '@nextcloud/dialogs'
import { NcButton, NcColorPicker, NcTextField } from '@nextcloud/vue'
import { mapStores } from 'pinia'
import { useCategoryStore } from '../store/categoryStore.ts'

import Circle from 'vue-material-design-icons/Circle.vue'
import IconCheck from 'vue-material-design-icons/Check.vue'
import IconDelete from 'vue-material-design-icons/Delete.vue'
import IconDrag from 'vue-material-design-icons/Drag.vue'
import IconPencil from 'vue-material-design-icons/Pencil.vue'

export default {
	name: 'ListCategory',

	components: {
		Circle,
		IconCheck,
		IconDelete,
		IconDrag,
		IconPencil,
		NcButton,
		NcColorPicker,
		NcTextField,
	},

	props: {
		category: {
			type: Object,
			required: true,
		},
	},

	data() {
		return {
			isEditing: false,
			loading: false,
			deleting: false,
			newCategoryName: '',
			categoryColor: this.category.color || '#aaa',
		}
	},

	computed: {
		...mapStores(useCategoryStore),
	},

	methods: {
		async updateColor(color) {
			try {
				await this.categoryStore.updateCategoryColor(this.category.id, color, this.category.grocery_list)
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', 'Could not update category color'))
			}
		},

		async onDelete() {
			const itemCount = this.category.itemCount ?? 0
			if (itemCount > 0) {
				const confirmed = await this.confirmDeletion(itemCount)
				if (!confirmed) {
					return
				}
			}

			this.deleting = true
			try {
				await this.categoryStore.deleteCategory(this.category)
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', 'Could not delete category'))
			}
			this.deleting = false
		},

		/**
		 * Ask the user whether they really want to delete a non-empty
		 * category together with its items. Resolves to `true` if the user
		 * confirms, `false` otherwise.
		 *
		 * @param {number} itemCount Number of items inside the category.
		 * @return {Promise<boolean>}
		 */
		confirmDeletion(itemCount) {
			return new Promise((resolve) => {
				const dialog = getDialogBuilder(t('grocerylist', 'Delete category'))
					.setText(
						n(
							'grocerylist',
							'This category contains %n item. Deleting the category will also delete this item. Continue?',
							'This category contains %n items. Deleting the category will also delete these items. Continue?',
							itemCount,
						),
					)
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

		async toggleEditMode() {
			if (!this.isEditing) {
				this.newCategoryName = this.category.name
				this.$nextTick(() => this.$nextTick(() => this.$refs.input?.$el?.querySelector('input')?.focus()))
			} else if (this.newCategoryName !== this.category.name) {
				this.loading = true
				try {
					this.categoryStore.editCategory({ ...this.category, name: this.newCategoryName })
				} catch (e) {
					console.error(e)
					showError(t('grocerylist', 'Could not rename category'))
				}
				this.loading = false
			}

			this.isEditing = !this.isEditing
		},
	},
}
</script>

<style scoped lang="scss">
.list-category {
	display: flex;
	align-items: center;
	gap: 12px;

	&__drag-handle {
		display: inline-flex;
		align-items: center;
		cursor: grab;
		color: var(--color-text-maxcontrast);

		&:active {
			cursor: grabbing;
		}
	}

	&__name {
		flex: 1;
		padding-block: 6px;
	}

	&__input {
		flex: 1;
	}
}
</style>

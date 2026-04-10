<template>
	<li class="list-category">
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
	</li>
</template>

<script>
import { showError } from '@nextcloud/dialogs'
import { NcButton, NcColorPicker, NcTextField } from '@nextcloud/vue'
import { mapStores } from 'pinia'
import { useCategoryStore } from '../store/categoryStore.ts'

import Circle from 'vue-material-design-icons/Circle.vue'
import IconCheck from 'vue-material-design-icons/Check.vue'
import IconPencil from 'vue-material-design-icons/Pencil.vue'

export default {
	name: 'ListCategory',

	components: {
		Circle,
		IconCheck,
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

	&__name {
		flex: 1;
		padding-block: 6px;
	}

	&__input {
		flex: 1;
	}
}
</style>

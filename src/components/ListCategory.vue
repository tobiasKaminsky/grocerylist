<template>
	<li class="list-category">
		<NcTextField v-if="isEditing"
			ref="input"
			class="list-category__input"
			:label="t('grocerylist', 'Category name')"
			:loading="loading"
			:value.sync="newCategoryName" />
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
import { NcButton, NcTextField } from '@nextcloud/vue'
import { mapStores } from 'pinia'
import { nextTick } from 'vue'
import { useCategoryStore } from '../store/categoryStore.ts'

import IconCheck from 'vue-material-design-icons/Check.vue'
import IconPencil from 'vue-material-design-icons/Pencil.vue'

export default {
	name: 'ListCategory',

	components: {
		IconCheck,
		IconPencil,
		NcButton,
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
		}
	},

	computed: {
		// as we do not use the Composition API, this makes the store available using "this.categoryStore" (store id + 'Store')
		...mapStores(useCategoryStore),
	},

	methods: {
		async toggleEditMode() {
			if (!this.isEditing) {
				this.newCategoryName = this.category.name
				// Set focus to input element
				// eslint-disable-next-line vue/valid-next-tick
				this.$nextTick(() => this.$nextTick(() => this.$refs.input.$el.querySelector('input').focus()))
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
		padding-block: 6px;
		min-width: 245px;
	}

	&__input {
		min-width: 245px;
		width: fit-content!important;
	}
}
</style>

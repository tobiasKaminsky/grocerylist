<template>
	<div>
		<h1>Categories for {{ listId }}</h1>
		<input ref="name"
			v-model="category"
			type="text"
			:disabled="updating"
			style="width: 30%">
		<NcActionButton icon="icon-add"
			@click="onSaveCategory()" />
		<span v-for="subCategory in categories" :key="subCategory.id">
			<ul>
				<li @click="editCategory(subCategory)">{{ subCategory.name }}</li>
			</ul>

		</span>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import { NcActionButton } from '@nextcloud/vue'

export default {
	name: 'EditCategories',
	components: {
		NcActionButton,
	},
	listId: '',
	data() {
		return {
			listId: this.$attrs.listId,
			categories: this.$attrs.categories,
			updating: false,
			category: '',
			newCategoryId: -1,
		}
	},
	methods: {
		editCategory(category) {
			this.newCategoryId = category.id
			this.category = category.name
		},
		async onSaveCategory() {
			if (this.newCategoryId === -1) {
				this.addCategory()
			} else {
				this.updateCategory()
			}
		},
		async addCategory() {
			this.updating = true
			try {
				const response = await axios.post(generateUrl(`/apps/grocerylist/api/category/${this.listId}/add`),
					{
						name: this.category,
					},
				)
				this.categories = response.data
				this.category = ''
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', 'Could not add category to list '.this.listId))
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
/*
.modal, .content {
	background: #fff;
	width: 80%;
	max-width: 480px;
	max-height: 60%;
	box-shadow: 0 15px 30px 0 rgba(0, 0, 0, 0.11), 0 5px 15px 0 rgba(0, 0, 0, 0.08);
	border-radius: 3px;
	border: 1px solid darkslategray;
	padding: 1rem;
	position: absolute;
	top: 0;
	right: 0;
	left: 0;
	bottom: 0;
	margin: auto;

	code {
		background: #e4e4e4;
		border-radius: 3px;
		padding: 0.25rem 0.5rem;
		margin: 0.5rem 0;
		color: crimson;
	}
}
 */
</style>

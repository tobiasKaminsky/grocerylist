<template>
	<div>
		<h1>Categories for {{ listId }}</h1>
		<input ref="name"
					 v-model="category"
					 type="text"
					 :disabled="updating"
					 v-on:keyup.enter="onSaveCategory()"
					 style="width: 30%">
		<ActionButton icon="icon-add"
									@click="onSaveCategory()"></ActionButton>
		<span v-for="category in categories">
			<ul>
			<li @click="editCategory(category)">{{ category.name }}</li>
			</ul>
		</span>

		<br/>
		<br/>
		<br/>
		<br/>
		<br/>
		<br/>
		<h1>Share</h1>
		<span v-for="sharee in sharees">
			<ul>
			<li>{{ sharee.userId }}</li>
			</ul>
		</span>

	</div>
</template>

<script>
import axios from "@nextcloud/axios";
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton';

export default {
	name: "Settings",
	components: {
		ActionButton
	},
	listId: '',
	data: function () {
		return {
			listId: this.$attrs['listId'],
			categories: null,
			updating: false,
			category: '',
			newCategoryId: -1,
			sharees: null,
		}
	},
	async mounted () {
		await this.loadCategories(this.listId)
		await this.loadSharees(this.listId)
	},
	watch: {
		$route (to, from) {
			if (to.name !== from.name || to.params.listId !== from.params.listId) {
				this.listId = to.params.listId
				this.loadCategories(this.listId)
				this.loadSharees(this.listId)
			}
		}
	},
	methods: {
		editCategory (category) {
			this.newCategoryId = category.id;
			this.category = category.name;
		},
		async onSaveCategory () {
			if (this.newCategoryId === -1) {
				await this.addCategory();
			} else {
				await this.updateCategory();
			}
		},
		async addCategory () {
			this.updating = true
			try {
				const response = await axios.post(OC.generateUrl(`/apps/grocerylist/category/${this.listId}/add`),
						{
							name: this.category
						}
				)
				this.categories = response.data;
				this.category = "";
			} catch (e) {
				console.error(e)
				OCP.Toast.error(t('grocerylist', 'Could not add category to list '.this.listId))
			}
			this.updating = false
		},
		async updateCategory (listId) {
			this.updating = true
			try {
				const response = await axios.post(OC.generateUrl(`/apps/grocerylist/category/update`),
						{
							id: this.newCategoryId,
							newName: this.category,
						}
				)

				this.categories = response.data;
				this.category = "";
			} catch (e) {
				console.error(e)
				OCP.Toast.error(t('grocerylist', 'Could not update category'))
			}
			this.updating = false
		},
		async loadCategories (id) {
			try {
				const response = await axios.get(OC.generateUrl('/apps/grocerylist/all_categories/' + id))
				this.categories = response.data
			} catch (e) {
				console.error(e)
				OCP.Toast.error(t('grocerylist', ('Could not fetch categories for groceryList ' + id)))
			}
			this.loading = false
		},
		async loadSharees (id) {
			try {
				const response = await axios.get(OC.generateUrl('/apps/grocerylist/sharees/' + id))
				this.sharees = response.data
			} catch (e) {
				console.error(e)
				OCP.Toast.error(t('grocerylist', ('Could not fetch sharees for groceryList ' + id)))
			}
			this.loading = false
		},
	}
}
</script>
<style lang="scss">
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
</style>
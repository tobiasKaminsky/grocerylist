import axios from '@nextcloud/axios'

import { generateUrl } from '@nextcloud/router'
import { defineStore } from 'pinia'

export interface ICategory {
	id: number
	order: number
	name: string
	grocery_list: number
}

export const useCategoryStore = defineStore('category', {
	state() {
		return {
			/**
			 * All available categories as { listId: categories[], ... }
			 */
			categories: {} as Record<number, ICategory[]>,
		}
	},
	actions: {
		/**
		 * Method to create a new category for a given list
		 *
		 * @param listId List to create the category for
		 * @param name Name of the category
		 */
		async createCategory(listId: number, name: string) {
			const response = await axios.post<ICategory[]>(
				generateUrl(`/apps/grocerylist/api/category/${listId}/add`),
				{
					name,
				},
			)
			this.categories = { ...this.categories, [listId]: response.data }
		},

		/**
		 * Edit an existing category
		 * @param category The edited category (id must exist)
		 */
		async editCategory(category: ICategory) {
			const { data } = await axios.post<ICategory[]>(
				generateUrl('/apps/grocerylist/api/category/update'),
				{
					id: category.id,
					newName: category.name,
				},
			)
			this.categories = { ...this.categories, [category.grocery_list]: data }
		},

		async loadCategories(listId: number) {
			const response = await axios.get<ICategory[]>(
				generateUrl(`/apps/grocerylist/api/all_categories/${listId}`),
			)
			this.categories[listId] = response.data
		},
	},
})

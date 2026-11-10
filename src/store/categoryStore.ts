import axios from '@nextcloud/axios'

import { generateUrl } from '@nextcloud/router'
import { defineStore } from 'pinia'

export interface ICategory {
	id: number
	order: number
	name: string
	color: string | null
	grocery_list: number
	itemCount: number
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
		async createCategory(listId: number, name: string, color?: string | null) {
			const response = await axios.post<ICategory[]>(
				generateUrl(`/apps/grocerylist/api/category/${listId}/add`),
				{
					name,
					color: color ?? null,
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
					color: category.color,
				},
			)
			this.categories = { ...this.categories, [category.grocery_list]: data }
		},

		async updateCategoryColor(categoryId: number, color: string, listId: number) {
			const { data } = await axios.post<ICategory[]>(
				generateUrl('/apps/grocerylist/api/category/color'),
				{
					id: categoryId,
					color,
				},
			)
			this.categories = { ...this.categories, [listId]: data }
		},

		async loadCategories(listId: number) {
			const response = await axios.get<ICategory[]>(
				generateUrl(`/apps/grocerylist/api/all_categories/${listId}`),
			)
			this.categories[listId] = response.data
		},

		/**
		 * Delete an existing category. The backend also deletes every item
		 * that references the category, so the caller is expected to have
		 * asked the user for confirmation if the category still has items.
		 *
		 * @param category The category to delete.
		 */
		async deleteCategory(category: ICategory) {
			const { data } = await axios.delete<ICategory[]>(
				generateUrl(`/apps/grocerylist/api/category/${category.id}`),
			)
			this.categories = { ...this.categories, [category.grocery_list]: data }
		},

		/**
		 * Persist a new category order for the given list.
		 *
		 * The ordered array is applied to the local store immediately so
		 * the UI stays responsive; the server is then asked to persist the
		 * order and returns the authoritative list which replaces the
		 * optimistic one.
		 *
		 * @param listId The list whose categories are being reordered.
		 * @param orderedCategories Categories in the desired order.
		 */
		async reorderCategories(listId: number, orderedCategories: ICategory[]) {
			this.categories = { ...this.categories, [listId]: orderedCategories }
			const { data } = await axios.post<ICategory[]>(
				generateUrl(`/apps/grocerylist/api/categories/${listId}/reorder`),
				{
					orderedCategoryIds: orderedCategories.map((category) => category.id),
				},
			)
			this.categories = { ...this.categories, [listId]: data }
		},
	},
})

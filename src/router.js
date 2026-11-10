import { createRouter, createWebHistory } from 'vue-router'
import { generateUrl } from '@nextcloud/router'

import GroceryList from './views/GroceryList.vue'
import ListSettings from './views/ListSettings.vue'

const router = createRouter({
	history: createWebHistory(generateUrl('apps/grocerylist')),
	linkActiveClass: 'active',
	routes: [
		{
			path: '/settings/:listId',
			name: 'settings',
			component: ListSettings,
			// Get the property type right by parsing the string to number
			props: (route) => ({ listId: Number.parseInt(route.params.listId) }),
		},
		{
			path: '/list/:listId',
			name: 'list',
			components: {
				default: GroceryList,
			},
			props: {
				// Get the property type right by parsing the string to number
				default: (route) => ({ listId: Number.parseInt(route.params.listId) }),
			},
		},
	],
})

export default router

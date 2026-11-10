/**
 * SPDX-FileCopyrightText: 2021 Tobias Kaminsky <tobias@kaminsky.me>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { generateUrl } from '@nextcloud/router'
import { createRouter, createWebHistory } from 'vue-router'
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

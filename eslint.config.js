/**
 * SPDX-FileCopyrightText: 2026 Tobias Kaminsky <tobias@kaminsky.me>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { recommended } from '@nextcloud/eslint-config'

export default [
	...recommended,
	{
		files: ['**/*.vue'],
		rules: {
			// Object props (e.g. the grocery list) are intentionally updated in place
			'vue/no-mutating-props': ['error', { shallowOnly: true }],
		},
	},
]

/**
 * SPDX-FileCopyrightText: 2018 John Molakvoæ <skjnldsv@protonmail.com>
 * SPDX-FileCopyrightText: 2020 Tobias Kaminsky <tobias@kaminsky.me>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createPinia } from 'pinia'
import { createApp } from 'vue'
import App from './App.vue'
import router from './router.js'

const app = createApp(App)
const pinia = createPinia()

app.config.globalProperties.t = window.t
app.config.globalProperties.n = window.n
app.config.globalProperties.OC = window.OC
app.config.globalProperties.OCA = window.OCA

app.use(router)
app.use(pinia)

app.mount('#content')

<!--
  - SPDX-FileCopyrightText: 2026 Tobias Kaminsky <tobias@kaminsky.me>
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="list-sharing">
		<NcSelectUsers
			v-if="isOwner"
			:modelValue="null"
			:options="searchResults"
			:loading="searching"
			:inputLabel="t('grocerylist', 'Share with')"
			:placeholder="t('grocerylist', 'Search for users …')"
			@search="onSearch"
			@update:modelValue="onShare" />

		<NcLoadingIcon v-if="loading" :size="32" />
		<ul v-else class="list-sharing__entries">
			<li class="list-sharing__entry">
				<NcAvatar :user="owner.userId" :displayName="owner.displayName" :size="32" />
				<div class="list-sharing__name">
					<span>{{ owner.displayName }}</span>
					<span class="list-sharing__subname">
						{{ isOwner ? t('grocerylist', 'Owner (you)') : t('grocerylist', 'Shared with you by {owner}', { owner: owner.displayName }) }}
					</span>
				</div>
			</li>
			<li v-for="sharee in sharees" :key="sharee.userId" class="list-sharing__entry">
				<NcAvatar :user="sharee.userId" :displayName="sharee.displayName" :size="32" />
				<div class="list-sharing__name">
					<span>{{ sharee.displayName }}</span>
					<span v-if="sharee.userId === currentUserId" class="list-sharing__subname">
						{{ t('grocerylist', 'You') }}
					</span>
				</div>
				<NcButton
					v-if="isOwner"
					variant="tertiary"
					:disabled="pending"
					:aria-label="t('grocerylist', 'Unshare with {name}', { name: sharee.displayName })"
					:title="t('grocerylist', 'Unshare')"
					@click="onUnshare(sharee)">
					<template #icon>
						<IconClose :size="20" />
					</template>
				</NcButton>
			</li>
		</ul>
		<p v-if="!loading && isOwner && sharees.length === 0" class="list-sharing__hint">
			{{ t('grocerylist', 'Share this list with other users so you can shop together. Everyone it is shared with can add, edit and check off items.') }}
		</p>
	</div>
</template>

<script>
import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import { NcAvatar, NcButton, NcLoadingIcon, NcSelectUsers } from '@nextcloud/vue'
import IconClose from 'vue-material-design-icons/Close.vue'
import logger from '../logger.js'

export default {
	name: 'ListSharing',

	components: {
		IconClose,
		NcAvatar,
		NcButton,
		NcLoadingIcon,
		NcSelectUsers,
	},

	props: {
		listId: {
			type: Number,
			required: true,
		},
	},

	emits: ['loaded'],

	data() {
		return {
			loading: true,
			pending: false,
			searching: false,
			searchTimeout: null,
			searchResults: [],
			owner: { userId: '', displayName: '' },
			sharees: [],
		}
	},

	computed: {
		currentUserId() {
			return getCurrentUser()?.uid ?? ''
		},

		isOwner() {
			return this.owner.userId === this.currentUserId
		},
	},

	watch: {
		listId() {
			this.searchResults = []
			this.loadSharees()
		},
	},

	mounted() {
		this.loadSharees()
	},

	beforeUnmount() {
		clearTimeout(this.searchTimeout)
	},

	methods: {
		apiUrl(path = '') {
			return generateUrl(`/apps/grocerylist/api/sharees/${this.listId}${path}`)
		},

		setSharees(data) {
			this.owner = data.owner
			this.sharees = data.sharees
			this.$emit('loaded', { owner: this.owner, isOwner: this.isOwner })
		},

		async loadSharees() {
			this.loading = true
			try {
				const { data } = await axios.get(this.apiUrl())
				this.setSharees(data)
			} catch (e) {
				logger.error('Could not fetch sharees', { error: e })
				showError(t('grocerylist', 'Could not fetch sharees'))
			}
			this.loading = false
		},

		onSearch(query) {
			clearTimeout(this.searchTimeout)
			if (query.trim() === '') {
				this.searchResults = []
				this.searching = false
				return
			}
			this.searching = true
			this.searchTimeout = setTimeout(() => this.search(query.trim()), 300)
		},

		async search(query) {
			try {
				const { data } = await axios.get(this.apiUrl('/search'), { params: { search: query } })
				this.searchResults = data.map((user) => ({
					id: user.userId,
					user: user.userId,
					displayName: user.displayName,
					subname: user.subname,
				}))
			} catch (e) {
				logger.error('Could not search users', { error: e })
				showError(t('grocerylist', 'Could not search users'))
			}
			this.searching = false
		},

		async onShare(user) {
			if (!user) {
				return
			}
			this.pending = true
			try {
				const { data } = await axios.post(this.apiUrl(), { userId: user.id })
				this.setSharees(data)
				this.searchResults = []
			} catch (e) {
				logger.error('Could not share list', { error: e })
				showError(t('grocerylist', 'Could not share list with {name}', { name: user.displayName }))
			}
			this.pending = false
		},

		async onUnshare(sharee) {
			this.pending = true
			try {
				const { data } = await axios.delete(this.apiUrl(`/${encodeURIComponent(sharee.userId)}`))
				this.setSharees(data)
			} catch (e) {
				logger.error('Could not unshare list', { error: e })
				showError(t('grocerylist', 'Could not unshare list with {name}', { name: sharee.displayName }))
			}
			this.pending = false
		},
	},
}
</script>

<style lang="scss" scoped>
.list-sharing {
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline) * 2);

	&__entries {
		display: flex;
		flex-direction: column;
		gap: calc(var(--default-grid-baseline) * 1);
	}

	&__entry {
		display: flex;
		align-items: center;
		gap: calc(var(--default-grid-baseline) * 3);
		min-height: var(--default-clickable-area);
	}

	&__name {
		display: flex;
		flex-direction: column;
		flex: 1 1 auto;
		min-width: 0;

		span {
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
		}
	}

	&__subname,
	&__hint {
		color: var(--color-text-maxcontrast);
	}
}
</style>

<template>
	<li :class="{'snoozed': isSnoozed}" class="list-item">
		<span class="drag-handle" :style="categoryColor ? { color: categoryColor } : {}">
			<DragVerticalVariant :size="20" />
		</span>
		<NcCheckboxRadioSwitch :model-value="item.checked === true"
			@update:model-value="checkItem(item)" />
		<span class="list-item__label" @click="$emit('edit')">
			<span v-if="item.quantity !== ''">
				{{ item.quantity }}
			</span>
			{{ item.name }}
		</span>
		<NcButton :aria-label="t('grocerylist', 'Snooze')"
			type="tertiary"
			@click="hideItem(item)">
			<template #icon>
				<AlarmSnooze :size="20" />
			</template>
		</NcButton>
	</li>
</template>
<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { showError } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'
import AlarmSnooze from 'vue-material-design-icons/AlarmSnooze.vue'
import DragVerticalVariant from 'vue-material-design-icons/DragVerticalVariant.vue'

import {
	NcCheckboxRadioSwitch,
	NcButton,
} from '@nextcloud/vue'
export default {
	name: 'ListItem',
	components: {
		AlarmSnooze,
		DragVerticalVariant,
		NcCheckboxRadioSwitch,
		NcButton,
	},
	props: {
		item: {
			type: Object,
			required: true,
		},
		categoryColor: {
			type: String,
			default: null,
		},
	},
	computed: {
		isSnoozed() {
			return this.item?.hidden > (Math.floor(Date.now() / 1000) - (15 * 60))
		},
	},
	methods: {
		async checkItem(item) {
			if (item.checked === true) {
				item.checked = false
			} else {
				item.checked = true
			}
			try {
				await axios.post(generateUrl('/apps/grocerylist/api/item/check'),
					{ id: item.id, checked: item.checked },
				)
				emit('grocerylist:item-checked-changed', {
					listId: item.list,
					checked: item.checked,
				})
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', 'Could not check item'))
			}
		},
		async hideItem(item) {
			try {
				await axios.post(generateUrl('/apps/grocerylist/api/item/hide'),
					{ id: item.id },
				)

				this.$emit('update')
			} catch (e) {
				console.error(e)
				showError(t('grocerylist', 'Could not add item'))
			}
		},
	},
}
</script>
<style scoped lang="scss">
.list-item {
	display: flex;
	align-items: center;
	min-height: 44px;
	padding-block: calc(var(--default-grid-baseline) * 1);
	padding-inline-end: calc(var(--default-grid-baseline) * 1);

	.drag-handle {
		display: flex;
		align-items: center;
		cursor: grab;
		color: var(--color-text-maxcontrast);
		transition: opacity 0.15s ease;
		// ensure touch target
		min-width: 24px;
		min-height: 44px;
		justify-content: center;

		&:active {
			cursor: grabbing;
		}
	}

	&__label {
		flex: 1;
		cursor: default;
		padding-inline: calc(var(--default-grid-baseline) * 1);
		// prevent long item names from squeezing buttons
		min-width: 0;
		overflow-wrap: break-word;
	}
}
</style>

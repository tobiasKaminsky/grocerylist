<template>
	<li :class="{'snoozed': isSnoozed}">
		<NcButton aria-label="Snooze"
			type="tertiary"
			style="display:inline-block;"
			@click="hideItem(item)">
			<template #icon>
				<AlarmSnooze :size="20" />
			</template>
		</NcButton>
		<NcCheckboxRadioSwitch :checked="item.checked === true"
			style="display:inline-block;"
			@update:checked="checkItem(item)" />
		<span @click="$emit('edit')">
			<span v-if="item.quantity !== ''">
				{{ item.quantity }}
			</span>
			{{ item.name }}
		</span>
	</li>
</template>
<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { showError } from '@nextcloud/dialogs'
import AlarmSnooze from 'vue-material-design-icons/AlarmSnooze.vue'

import {
	NcCheckboxRadioSwitch,
	NcButton,
} from '@nextcloud/vue'
export default {
	name: 'ListItem',
	components: {
		AlarmSnooze,
		NcCheckboxRadioSwitch,
		NcButton,
	},
	props: {
		item: {
			type: Object,
			required: true,
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

</style>

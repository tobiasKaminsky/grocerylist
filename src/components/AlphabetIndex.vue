<template>
	<nav
		class="alphabet-index"
		:aria-label="t('grocerylist', 'Jump to letter')"
		@pointerdown="onPointerDown"
		@pointermove="onPointerMove"
		@pointerup="onPointerUp"
		@pointercancel="onPointerUp">
		<button
			v-for="letter in letters"
			:key="letter"
			type="button"
			class="alphabet-index__letter"
			:class="{ 'alphabet-index__letter--empty': !available.includes(letter) }"
			:data-letter="letter"
			:aria-label="t('grocerylist', 'Jump to {letter}', { letter })"
			@click="select(letter)">
			{{ letter }}
		</button>
		<span v-if="activeLetter" class="alphabet-index__bubble" aria-hidden="true">
			{{ activeLetter }}
		</span>
	</nav>
</template>

<script>
export const LETTERS = [...'ABCDEFGHIJKLMNOPQRSTUVWXYZ', '#']

/**
 * Map a name to its index letter: first character without diacritics,
 * upper-cased, or "#" if it is not A–Z.
 *
 * @param {string} name Item name
 * @return {string}
 */
export function indexLetter(name) {
	const first = (name ?? '').trim().charAt(0).normalize('NFD').charAt(0).toUpperCase().charAt(0)
	return first >= 'A' && first <= 'Z' ? first : '#'
}

export default {
	name: 'AlphabetIndex',

	props: {
		/**
		 * Letters that have at least one item
		 */
		available: {
			type: Array,
			required: true,
		},
	},

	emits: ['select'],

	data() {
		return {
			letters: LETTERS,
			activeLetter: null,
		}
	},

	methods: {
		select(letter) {
			// Jump to the closest following letter that has items, else the closest preceding one
			const index = this.letters.indexOf(letter)
			const target = this.letters.slice(index).find((l) => this.available.includes(l))
				?? this.letters.slice(0, index).reverse().find((l) => this.available.includes(l))
			if (target) {
				this.$emit('select', target)
			}
		},

		letterAt(event) {
			const element = document.elementFromPoint(event.clientX, event.clientY)
			return element?.closest?.('.alphabet-index__letter')?.dataset.letter ?? null
		},

		onPointerDown(event) {
			if (event.pointerType === 'mouse') {
				// mouse uses plain click
				return
			}
			event.preventDefault()
			this.$el.setPointerCapture(event.pointerId)
			this.onPointerMove(event)
		},

		onPointerMove(event) {
			if (!this.$el.hasPointerCapture(event.pointerId)) {
				return
			}
			const letter = this.letterAt(event)
			if (letter && letter !== this.activeLetter) {
				this.activeLetter = letter
				this.select(letter)
			}
		},

		onPointerUp(event) {
			if (this.$el.hasPointerCapture(event.pointerId)) {
				this.$el.releasePointerCapture(event.pointerId)
			}
			this.activeLetter = null
		},
	},
}
</script>

<style scoped lang="scss">
.alphabet-index {
	position: absolute;
	top: 50%;
	right: 4px;
	transform: translateY(-50%);
	z-index: 10;
	display: flex;
	flex-direction: column;
	max-height: calc(100% - 32px);
	padding: 4px 0;
	border-radius: var(--border-radius-pill);
	background-color: var(--color-main-background);
	touch-action: none;
	user-select: none;

	&__letter {
		flex: 1 1 auto;
		min-height: 0;
		min-width: 0;
		width: 24px;
		margin: 0;
		padding: 0;
		border: none;
		background: none;
		font-size: 11px;
		font-weight: bold;
		line-height: 16px;
		color: var(--color-primary-element);
		cursor: pointer;

		&:hover,
		&:focus-visible {
			color: var(--color-primary-element-hover);
			background-color: var(--color-background-hover);
		}

		&--empty {
			color: var(--color-text-maxcontrast);
			font-weight: normal;
			opacity: 0.6;
		}
	}

	&__bubble {
		position: absolute;
		top: 50%;
		right: 40px;
		transform: translateY(-50%);
		width: 56px;
		height: 56px;
		border-radius: 50%;
		background-color: var(--color-primary-element);
		color: var(--color-primary-element-text);
		font-size: 28px;
		line-height: 56px;
		text-align: center;
		pointer-events: none;
	}
}
</style>

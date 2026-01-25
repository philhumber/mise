<script lang="ts">
	interface Props {
		value: string;
	}

	let { value = $bindable('') }: Props = $props();

	// Initialize from prop, then manage locally
	let inputValue = $state(value);
	let debounceTimer: ReturnType<typeof setTimeout> | null = null;

	// Cleanup debounce timer on unmount
	$effect(() => {
		return () => {
			if (debounceTimer) {
				clearTimeout(debounceTimer);
			}
		};
	});

	function handleInput(event: Event) {
		const target = event.target as HTMLInputElement;
		inputValue = target.value;

		// Debounce the parent update
		if (debounceTimer) {
			clearTimeout(debounceTimer);
		}
		debounceTimer = setTimeout(() => {
			value = inputValue;
		}, 200);
	}

	function handleClear() {
		inputValue = '';
		value = '';
		if (debounceTimer) {
			clearTimeout(debounceTimer);
		}
	}
</script>

<div class="search-bar">
	<svg
		class="search-icon"
		width="18"
		height="18"
		viewBox="0 0 24 24"
		fill="none"
		stroke="currentColor"
		stroke-width="2"
		aria-hidden="true"
	>
		<circle cx="11" cy="11" r="8" />
		<path d="M21 21l-4.35-4.35" />
	</svg>

	<input
		type="search"
		class="search-input"
		placeholder="Search recipes..."
		bind:value={inputValue}
		oninput={handleInput}
		aria-label="Search recipes"
	/>

	{#if inputValue}
		<button type="button" class="clear-button" onclick={handleClear} aria-label="Clear search">
			<svg
				width="20"
				height="20"
				viewBox="0 0 24 24"
				fill="none"
				stroke="currentColor"
				stroke-width="2"
				aria-hidden="true"
			>
				<path d="M18 6L6 18M6 6l12 12" />
			</svg>
		</button>
	{/if}
</div>

<style>
	.search-bar {
		position: relative;
		display: flex;
		align-items: center;
	}

	.search-icon {
		position: absolute;
		left: var(--spacing-md);
		color: var(--color-text-tertiary);
		pointer-events: none;
	}

	.search-input {
		width: 100%;
		min-height: 44px;
		padding: var(--spacing-sm) var(--spacing-3xl);
		font-family: var(--font-body);
		font-size: var(--font-size-body);
		color: var(--color-text-primary);
		background-color: var(--color-surface);
		border: 1px solid var(--color-border);
		border-radius: var(--radius-md);
		transition: all var(--transition-fast);
	}

	.search-input::placeholder {
		color: var(--color-text-tertiary);
	}

	.search-input:hover {
		border-color: var(--color-border-strong);
	}

	.search-input:focus {
		border-color: var(--color-accent);
		outline: none;
	}

	/* Hide default search cancel button */
	.search-input::-webkit-search-cancel-button {
		display: none;
	}

	.clear-button {
		position: absolute;
		right: 0;
		display: flex;
		align-items: center;
		justify-content: center;
		width: 44px;
		height: 44px;
		padding: 0;
		color: var(--color-text-tertiary);
		background: transparent;
		border: none;
		border-radius: var(--radius-sm);
		cursor: pointer;
		transition: all var(--transition-fast);
	}

	.clear-button:hover {
		color: var(--color-text-secondary);
		background-color: var(--color-highlight);
	}
</style>

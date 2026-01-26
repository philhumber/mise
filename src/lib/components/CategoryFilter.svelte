<script lang="ts">
	interface Props {
		categories: string[];
		activeCategory: string;
	}

	let { categories, activeCategory = $bindable('all') }: Props = $props();
</script>

<nav class="filters" aria-label="Filter by category">
	{#each categories as category (category)}
		<button
			type="button"
			class="pill"
			class:active={activeCategory === category}
			aria-pressed={activeCategory === category}
			onclick={() => (activeCategory = category)}
		>
			{category}
		</button>
	{/each}
</nav>

<style>
	.filters {
		display: flex;
		gap: var(--spacing-sm);
		overflow-x: auto;
		scroll-snap-type: x mandatory;
		-webkit-overflow-scrolling: touch;
		scrollbar-width: none;
		padding-block: var(--spacing-xs);
	}

	.filters::-webkit-scrollbar {
		display: none;
	}

	.pill {
		flex-shrink: 0;
		scroll-snap-align: start;
	}
</style>

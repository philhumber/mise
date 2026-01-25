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
		flex-wrap: wrap;
		gap: var(--spacing-sm);
		justify-content: center;
	}
</style>

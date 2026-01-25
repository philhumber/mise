<script lang="ts">
	import RecipeCard from '$lib/components/RecipeCard.svelte';

	let { data } = $props();

	const categories = ['all', 'main', 'starter', 'dessert', 'side', 'drink', 'sauce'];
	let activeCategory = $state('all');

	const filteredRecipes = $derived(
		activeCategory === 'all'
			? data.recipes
			: data.recipes.filter((r) => r.category === activeCategory)
	);
</script>

<div class="home">
	<section class="hero">
		<h1 class="text-display hero-title">Recipes</h1>
		<p class="text-meta hero-subtitle">A curated collection for serious home cooks</p>
	</section>

	<nav class="filters" aria-label="Filter by category">
		{#each categories as category (category)}
			<button
				class="pill"
				class:active={activeCategory === category}
				onclick={() => (activeCategory = category)}
			>
				{category}
			</button>
		{/each}
	</nav>

	<section class="recipes" aria-label="Recipe list">
		{#each filteredRecipes as recipe (recipe.slug)}
			<RecipeCard {recipe} />
		{/each}

		{#if filteredRecipes.length === 0}
			<p class="text-meta empty">No recipes in this category yet.</p>
		{/if}
	</section>
</div>

<style>
	.home {
		max-width: 800px;
		margin: 0 auto;
	}

	.hero {
		margin-bottom: var(--spacing-3xl);
		text-align: center;
	}

	.hero-title {
		margin: 0 0 var(--spacing-sm);
		font-size: 40px;
		color: var(--color-text-primary);
	}

	.hero-subtitle {
		margin: 0;
	}

	.filters {
		display: flex;
		flex-wrap: wrap;
		gap: var(--spacing-sm);
		margin-bottom: var(--spacing-2xl);
		justify-content: center;
	}

	.recipes {
		display: grid;
		gap: var(--spacing-xl);
	}

	.empty {
		text-align: center;
		padding: var(--spacing-3xl);
	}
</style>

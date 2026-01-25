<script lang="ts">
	import RecipeCard from '$lib/components/RecipeCard.svelte';
	import type { RecipeMeta } from '$lib/types';

	// Mock data for visual demo
	const mockRecipes: RecipeMeta[] = [
		{
			slug: 'kombu-cod',
			title: 'Kombu-Cured Low-Temp Cod',
			subtitle: 'Agar-Kombu Emulsion, Parsley Oil, Crispy Skin',
			category: 'main',
			difficulty: 'advanced',
			active_time: '35 min',
			total_time: '48h',
			serves: 2,
			tags: ['seafood', 'sous-vide', 'make-ahead']
		},
		{
			slug: 'yuzu-granite',
			title: 'Yuzu Green Tea Granité',
			subtitle: 'Matcha Cream, Candied Ginger',
			category: 'dessert',
			difficulty: 'easy',
			active_time: '15 min',
			total_time: '4h',
			serves: 4,
			tags: ['frozen', 'make-ahead', 'refreshing']
		},
		{
			slug: 'kitchen-hydration',
			title: 'Kitchen Hydration Drink',
			subtitle: 'Electrolyte Replacement for Long Service',
			category: 'drink',
			difficulty: 'easy',
			active_time: '5 min',
			total_time: '5 min',
			serves: 1,
			tags: ['quick', 'essential', 'hydration']
		}
	];

	const categories = ['all', 'main', 'starter', 'dessert', 'side', 'drink', 'sauce'];
	let activeCategory = $state('all');

	const filteredRecipes = $derived(
		activeCategory === 'all'
			? mockRecipes
			: mockRecipes.filter((r) => r.category === activeCategory)
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

<script lang="ts">
	import RecipeCard from '$lib/components/RecipeCard.svelte';
	import SearchBar from '$lib/components/SearchBar.svelte';
	import CategoryFilter from '$lib/components/CategoryFilter.svelte';
	import { searchRecipes } from '$lib/utils/search';

	let { data } = $props();

	const categories = ['all', 'main', 'starter', 'dessert', 'side', 'drink', 'sauce'];
	let searchQuery = $state('');
	let activeCategory = $state('all');

	const filteredRecipes = $derived(
		(() => {
			// First, apply search if query exists
			const searched = searchQuery.trim() ? searchRecipes(data.recipes, searchQuery) : data.recipes;

			// Then, apply category filter
			return activeCategory === 'all'
				? searched
				: searched.filter((r) => r.category === activeCategory);
		})()
	);
</script>

<div class="home">
	<section class="hero">
		<h1 class="text-display hero-title">Recipes</h1>
		<p class="text-meta hero-subtitle">A curated collection for serious home cooks</p>
	</section>

	<div class="search-section">
		<SearchBar bind:value={searchQuery} />
	</div>

	<CategoryFilter {categories} bind:activeCategory />

	<section class="recipes" aria-label="Recipe list">
		{#each filteredRecipes as recipe (recipe.slug)}
			<RecipeCard {recipe} />
		{/each}

		{#if filteredRecipes.length === 0}
			<p class="text-meta empty">No recipes found.</p>
		{/if}
	</section>
</div>

<style>
	.home {
		max-width: 800px;
		margin: 0 auto;
	}

	.hero {
		margin-bottom: var(--spacing-2xl);
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

	.search-section {
		margin-bottom: var(--spacing-xl);
	}

	:global(.filters) {
		margin-bottom: var(--spacing-2xl);
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

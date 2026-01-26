<script lang="ts">
	import { onMount } from 'svelte';
	import RecipeCard from '$lib/components/RecipeCard.svelte';
	import SearchBar from '$lib/components/SearchBar.svelte';
	import CategoryFilter from '$lib/components/CategoryFilter.svelte';
	import { searchRecipes } from '$lib/utils/search';
	import { fetchUserRecipes } from '$lib/api/recipes';
	import type { RecipeMeta } from '$lib/types';

	const categories = ['all', 'main', 'starter', 'dessert', 'side', 'drink', 'sauce'];
	let searchQuery = $state('');
	let activeCategory = $state('all');
	let recipes = $state<RecipeMeta[]>([]);
	let isLoading = $state(true);

	// Load recipes from API on mount
	onMount(async () => {
		recipes = await fetchUserRecipes();
		isLoading = false;
	});

	const filteredRecipes = $derived(
		(() => {
			// First, apply search if query exists
			const searched = searchQuery.trim() ? searchRecipes(recipes, searchQuery) : recipes;

			// Then, apply category filter
			return activeCategory === 'all'
				? searched
				: searched.filter((r) => r.category === activeCategory);
		})()
	);
</script>

<div class="home">
	<div class="search-section">
		<SearchBar bind:value={searchQuery} />
	</div>

	<CategoryFilter {categories} bind:activeCategory />

	<section class="recipes" aria-label="Recipe list">
		{#if isLoading}
			<p class="text-meta empty">Loading recipes...</p>
		{:else}
			{#each filteredRecipes as recipe (recipe.slug)}
				<RecipeCard {recipe} />
			{/each}

			{#if filteredRecipes.length === 0}
				<p class="text-meta empty">No recipes found.</p>
			{/if}
		{/if}
	</section>
</div>

<style>
	.home {
		max-width: 800px;
		margin: 0 auto;
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

<script lang="ts">
	import type { MealSnapshot } from '$lib/types';

	let { snapshot }: { snapshot: MealSnapshot } = $props();

	// Track which recipes are expanded (all collapsed by default)
	let expandedRecipes = $state.raw<Record<string, boolean>>({});

	function toggleRecipe(event: MouseEvent, slug: string) {
		event.stopPropagation();
		expandedRecipes = { ...expandedRecipes, [slug]: !expandedRecipes[slug] };
	}

	// Get recipes with their components and ingredients
	function getRecipeGroups() {
		const groups: Array<{
			recipe: { title: string; slug: string };
			components: Array<{
				name: string;
				ingredients: string[];
			}>;
		}> = [];

		for (const recipe of snapshot.recipes) {
			if (recipe.is_deleted) continue;

			const components: Array<{ name: string; ingredients: string[] }> = [];

			for (const [componentName, ingredients] of Object.entries(recipe.ingredients)) {
				if (ingredients.length > 0) {
					components.push({
						name: componentName,
						ingredients
					});
				}
			}

			if (components.length > 0) {
				groups.push({
					recipe: { title: recipe.title, slug: recipe.slug },
					components
				});
			}
		}

		return groups;
	}

	const recipeGroups = getRecipeGroups();
</script>

<div class="meal-ingredients">
	<h2>Ingredients</h2>

	{#if recipeGroups.length === 0}
		<p class="empty">No ingredients found</p>
	{:else}
		{#each recipeGroups as group}
			<section class="recipe-section" id="recipe-{group.recipe.slug}">
				<button class="recipe-header" onclick={(e) => toggleRecipe(e, group.recipe.slug)}>
					<span class="recipe-name">{group.recipe.title}</span>
					<span class="chevron" class:expanded={expandedRecipes[group.recipe.slug]}>&#9660;</span>
				</button>

				{#if expandedRecipes[group.recipe.slug]}
					<div class="recipe-content">
						{#each group.components as component}
							<div class="component-group">
								<h4 class="component-name">{component.name}</h4>
								<ul class="ingredient-list">
									{#each component.ingredients as ingredient}
										<li>{ingredient}</li>
									{/each}
								</ul>
							</div>
						{/each}
					</div>
				{/if}
			</section>
		{/each}
	{/if}
</div>

<style>
	.meal-ingredients h2 {
		font-family: var(--font-display);
		font-size: 1.25rem;
		margin: 0 0 var(--spacing-lg);
	}

	.empty {
		color: var(--color-text-tertiary);
		font-style: italic;
	}

	.recipe-section {
		border-left: 3px solid var(--color-border);
		margin-bottom: var(--spacing-sm);
		scroll-margin-top: var(--spacing-xl);
	}

	.recipe-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		width: 100%;
		padding: var(--spacing-sm) var(--spacing-md);
		background: var(--color-surface);
		border: none;
		cursor: pointer;
		text-align: left;
		font-family: var(--font-display);
		font-size: 1rem;
		font-weight: 500;
		color: var(--color-text-primary);
		min-height: 44px;
	}

	.recipe-header:hover {
		background: var(--color-bg);
	}

	.recipe-name {
		flex: 1;
	}

	.chevron {
		font-size: 0.75rem;
		color: var(--color-text-tertiary);
		transition: transform 0.2s;
		margin-left: var(--spacing-sm);
	}

	.chevron.expanded {
		transform: rotate(180deg);
	}

	.recipe-content {
		padding: var(--spacing-sm) var(--spacing-md) var(--spacing-md);
	}

	.component-group {
		margin-bottom: var(--spacing-md);
	}

	.component-group:last-child {
		margin-bottom: 0;
	}

	.component-name {
		font-family: var(--font-body);
		font-size: 0.8125rem;
		font-weight: 600;
		color: var(--color-text-secondary);
		margin: 0 0 var(--spacing-xs);
		text-transform: uppercase;
		letter-spacing: 0.03em;
	}

	.ingredient-list {
		margin: 0;
		padding: 0 0 0 var(--spacing-lg);
		list-style: disc;
	}

	.ingredient-list li {
		font-size: 0.875rem;
		line-height: 1.5;
		color: var(--color-text-primary);
		padding: 2px 0;
	}
</style>

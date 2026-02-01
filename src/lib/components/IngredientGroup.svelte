<script lang="ts">
	import type { IngredientGroup } from '$lib/types';

	interface Props {
		groups: IngredientGroup[];
	}

	let { groups }: Props = $props();
</script>

{#if groups.length > 0}
	<section class="ingredient-groups" aria-labelledby="ingredients-heading">
		<h2 id="ingredients-heading" class="section-title">Ingredients</h2>

		<div class="groups-grid">
			{#each groups as group (group.name)}
				<div class="ingredient-group texture-paper">
					<h3 class="group-name">{group.name}</h3>
					<ul class="ingredient-list">
						{#each group.ingredients as ingredient (ingredient)}
							<li class="ingredient-item">{ingredient}</li>
						{/each}
					</ul>
				</div>
			{/each}
		</div>
	</section>
{/if}

<style>
	.ingredient-groups {
		margin-bottom: var(--spacing-3xl);
	}

	.section-title {
		margin: 0 0 var(--spacing-xl);
		font-family: var(--font-display);
		font-size: var(--font-size-title);
		font-weight: 600;
		color: var(--color-text-primary);
		border-bottom: 1px solid var(--color-border);
		padding-bottom: var(--spacing-sm);
	}

	.groups-grid {
		display: grid;
		gap: var(--spacing-lg);
	}

	@media (min-width: 768px) {
		.groups-grid {
			grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
		}
	}

	.ingredient-group {
		position: relative;
		background-color: var(--color-surface);
		border: 1px solid var(--color-border);
		border-radius: var(--radius-md);
		padding: var(--spacing-lg);
	}

	.group-name {
		margin: 0 0 var(--spacing-md);
		font-family: var(--font-display);
		font-size: var(--font-size-body);
		font-weight: 600;
		color: var(--color-accent);
		border-left: 3px solid var(--color-accent);
		padding-left: var(--spacing-md);
	}

	.ingredient-list {
		margin: 0;
		padding-left: var(--spacing-xl);
		list-style: disc;
	}

	.ingredient-item {
		margin-bottom: var(--spacing-sm);
		font-family: var(--font-body);
		font-size: var(--font-size-body);
		color: var(--color-text-secondary);
		line-height: 1.6;
	}

	.ingredient-item::marker {
		color: var(--color-text-tertiary);
	}

	.ingredient-item:last-child {
		margin-bottom: 0;
	}
</style>

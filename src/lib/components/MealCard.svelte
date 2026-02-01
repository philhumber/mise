<script lang="ts">
	import type { MealMeta } from '$lib/types';
	import { resolveRoute } from '$app/paths';

	let { meal }: { meal: MealMeta } = $props();
</script>

<a href={resolveRoute('/meals/[slug]', { slug: meal.slug })} class="meal-card texture-paper">
	<h2 class="title">{meal.title}</h2>

	{#if meal.description}
		<p class="description">{meal.description}</p>
	{/if}

	<div class="meta">
		<span class="recipe-count"
			>{meal.recipe_count} {meal.recipe_count === 1 ? 'course' : 'courses'}</span
		>
		<span class="separator">·</span>
		<span class="timeline-span">{meal.timeline_span}</span>

		{#if meal.is_stale}
			<span class="stale-badge">Updates available</span>
		{/if}
	</div>
</a>

<style>
	.meal-card {
		display: block;
		padding: var(--spacing-xl);
		border-radius: var(--radius-md);
		border: 1px solid var(--color-border);
		text-decoration: none;
		color: inherit;
		transition:
			border-color 0.2s,
			box-shadow 0.2s,
			transform 0.2s;
		position: relative;
	}

	.meal-card::before {
		content: '';
		position: absolute;
		left: 0;
		top: 0;
		bottom: 0;
		width: 3px;
		background: var(--color-accent);
		border-radius: var(--radius-md) 0 0 var(--radius-md);
		opacity: 0;
		transition: opacity 0.2s;
	}

	.meal-card:hover {
		border-color: var(--color-accent);
		transform: translateY(-2px);
	}

	.meal-card:hover::before {
		opacity: 1;
	}

	.title {
		font-family: var(--font-display);
		font-size: 1.25rem;
		font-weight: 500;
		margin: 0 0 var(--spacing-sm);
		color: var(--color-text-primary);
	}

	.description {
		font-size: 0.875rem;
		color: var(--color-text-secondary);
		margin: 0 0 var(--spacing-md);
		line-height: 1.5;
		display: -webkit-box;
		-webkit-line-clamp: 2;
		-webkit-box-orient: vertical;
		overflow: hidden;
	}

	.meta {
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		gap: var(--spacing-xs);
		font-size: 0.75rem;
		color: var(--color-text-tertiary);
	}

	.separator {
		opacity: 0.5;
	}

	.stale-badge {
		background: var(--color-accent);
		color: white;
		padding: 2px var(--spacing-xs);
		border-radius: var(--radius-sm);
		font-weight: 500;
		margin-left: var(--spacing-sm);
	}
</style>

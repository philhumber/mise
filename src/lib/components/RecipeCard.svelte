<script lang="ts">
	import { resolveRoute } from '$app/paths';
	import type { RecipeMeta } from '$lib/types';

	interface Props {
		recipe: RecipeMeta;
	}

	let { recipe }: Props = $props();

	const difficultyDots = {
		easy: 1,
		intermediate: 2,
		advanced: 3
	};
</script>

<!-- eslint-disable-next-line svelte/no-navigation-without-resolve -->
<a href={resolveRoute('/recipe/[slug]', { slug: recipe.slug })} class="card texture-paper" data-sveltekit-reload>
	<div class="card-content">
		<span class="category pill">{recipe.category}</span>

		<h2 class="title text-display">{recipe.title}</h2>

		{#if recipe.subtitle}
			<p class="subtitle text-meta">{recipe.subtitle}</p>
		{/if}

		<div class="meta">
			<div class="stats">
				<span class="stat">
					<span class="stat-label">Active</span>
					<span class="stat-value">{recipe.active_time}</span>
				</span>
				<span class="stat">
					<span class="stat-label">Total</span>
					<span class="stat-value">{recipe.total_time}</span>
				</span>
				<span class="stat">
					<span class="stat-label">Serves</span>
					<span class="stat-value">{recipe.serves}</span>
				</span>
			</div>

			<div class="difficulty" title={recipe.difficulty}>
				{#each [0, 1, 2] as i (i)}
					<span class="dot" class:filled={i < difficultyDots[recipe.difficulty]}></span>
				{/each}
			</div>
		</div>
	</div>

	<div class="accent-bar"></div>
</a>

<style>
	.card {
		display: block;
		overflow: hidden;
		text-decoration: none;
		color: var(--color-text-primary);
		background-color: var(--color-surface);
		border: 1px solid var(--color-border);
		border-radius: var(--radius-md);
		box-shadow: var(--shadow-card);
		transition: all var(--transition-base);
	}

	.card:hover {
		box-shadow: var(--shadow-card-hover);
		transform: translateY(-2px);
	}

	.card:hover .accent-bar {
		opacity: 1;
	}

	.card-content {
		position: relative;
		z-index: 1;
		padding: var(--spacing-xl);
	}

	.category {
		margin-bottom: var(--spacing-md);
		font-size: var(--font-size-small);
		text-transform: capitalize;
	}

	.title {
		margin: 0 0 var(--spacing-xs);
		font-size: var(--font-size-title);
		color: var(--color-text-primary);
	}

	.subtitle {
		margin: 0 0 var(--spacing-lg);
	}

	.meta {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding-top: var(--spacing-md);
		border-top: 1px solid var(--color-border);
	}

	.stats {
		display: flex;
		gap: var(--spacing-lg);
	}

	.stat {
		display: flex;
		flex-direction: column;
		gap: 2px;
	}

	.stat-label {
		font-family: var(--font-body);
		font-size: var(--font-size-small);
		color: var(--color-text-tertiary);
		text-transform: uppercase;
		letter-spacing: 0.05em;
	}

	.stat-value {
		font-family: var(--font-display);
		font-size: var(--font-size-body);
		color: var(--color-text-primary);
	}

	.difficulty {
		display: flex;
		gap: 4px;
	}

	.dot {
		width: 8px;
		height: 8px;
		background-color: var(--color-border);
		border-radius: 50%;
		transition: background-color var(--transition-fast);
	}

	.dot.filled {
		background-color: var(--color-accent);
	}

	.accent-bar {
		height: 3px;
		background-color: var(--color-accent);
		opacity: 0;
		transition: opacity var(--transition-fast);
	}
</style>

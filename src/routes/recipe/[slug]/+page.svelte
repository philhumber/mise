<script lang="ts">
	import { base } from '$app/paths';
	import Timeline from '$lib/components/Timeline.svelte';

	let { data } = $props();

	const difficultyDots = {
		easy: 1,
		intermediate: 2,
		advanced: 3
	};
</script>

<div class="recipe-detail">
	<header class="recipe-header">
		<!-- eslint-disable-next-line svelte/no-navigation-without-resolve -- using base for non-parameterized route -->
		<a href={base || '/'} class="back-link">
			<svg
				viewBox="0 0 24 24"
				fill="none"
				stroke="currentColor"
				stroke-width="2"
				stroke-linecap="round"
				stroke-linejoin="round"
				class="back-icon"
			>
				<path d="m15 18-6-6 6-6"></path>
			</svg>
			Back
		</a>
	</header>

	<div class="recipe-title-block">
		<h1 class="text-display recipe-title">{data.recipe.title}</h1>
		{#if data.recipe.subtitle}
			<p class="text-meta recipe-subtitle">{data.recipe.subtitle}</p>
		{/if}
	</div>

	<div class="stats-bar">
		<div class="stat">
			<span class="stat-value">{data.recipe.active_time}</span>
			<span class="stat-label">Active</span>
		</div>
		<div class="stat">
			<span class="stat-value">{data.recipe.total_time}</span>
			<span class="stat-label">Total</span>
		</div>
		<div class="stat">
			<span class="stat-value">{data.recipe.serves}</span>
			<span class="stat-label">Serves</span>
		</div>
		<div class="stat">
			<div class="difficulty-dots">
				{#each [0, 1, 2] as i (i)}
					<span class="dot" class:filled={i < difficultyDots[data.recipe.difficulty]}></span>
				{/each}
			</div>
			<span class="stat-label">{data.recipe.difficulty}</span>
		</div>
	</div>

	<div class="tags">
		{#each data.recipe.tags as tag (tag)}
			<span class="pill">{tag}</span>
		{/each}
	</div>

	<div class="recipe-layout">
		<Timeline content={data.recipe.content} />

		<article class="recipe-content">
			<!-- eslint-disable-next-line svelte/no-at-html-tags -- trusted content from repo markdown files, see docs/recipe-format.md for sanitization notes -->
			{@html data.recipe.content}
		</article>
	</div>
</div>

<style>
	.recipe-detail {
		max-width: 800px;
		margin: 0 auto;
	}

	@media (min-width: 768px) {
		.recipe-detail {
			max-width: 1060px; /* 220px sidebar + 40px gap + 800px content */
		}
	}

	/* Smooth scroll for anchor links with offset for visual breathing room */
	:global(html) {
		scroll-behavior: smooth;
		scroll-padding-top: var(--spacing-2xl);
	}

	@media (prefers-reduced-motion: reduce) {
		:global(html) {
			scroll-behavior: auto;
		}
	}

	.recipe-header {
		margin-bottom: var(--spacing-2xl);
	}

	.back-link {
		display: inline-flex;
		align-items: center;
		min-height: 44px;
		gap: var(--spacing-xs);
		padding: var(--spacing-sm) 0;
		font-family: var(--font-body);
		font-size: var(--font-size-body);
		color: var(--color-text-secondary);
		text-decoration: none;
		transition: color var(--transition-fast);
	}

	.back-link:hover {
		color: var(--color-accent);
	}

	.back-icon {
		width: 20px;
		height: 20px;
	}

	.recipe-title-block {
		margin-bottom: var(--spacing-2xl);
	}

	.recipe-title {
		margin: 0 0 var(--spacing-sm);
		font-size: 36px;
		color: var(--color-text-primary);
	}

	.recipe-subtitle {
		margin: 0;
		font-size: var(--font-size-body);
	}

	.stats-bar {
		display: flex;
		flex-wrap: wrap;
		gap: var(--spacing-xl);
		padding: var(--spacing-lg) 0;
		margin-bottom: var(--spacing-xl);
		border-top: 1px solid var(--color-border);
		border-bottom: 1px solid var(--color-border);
	}

	.stat {
		display: flex;
		flex-direction: column;
		gap: 2px;
	}

	.stat-value {
		font-family: var(--font-display);
		font-size: var(--font-size-body);
		color: var(--color-text-primary);
	}

	.stat-label {
		font-family: var(--font-body);
		font-size: var(--font-size-small);
		color: var(--color-text-tertiary);
		text-transform: capitalize;
	}

	.difficulty-dots {
		display: flex;
		gap: 4px;
	}

	.dot {
		width: 8px;
		height: 8px;
		background-color: var(--color-border);
		border-radius: 50%;
	}

	.dot.filled {
		background-color: var(--color-accent);
	}

	.tags {
		display: flex;
		flex-wrap: wrap;
		gap: var(--spacing-sm);
		margin-bottom: var(--spacing-2xl);
	}

	/* Recipe layout - grid on desktop for timeline sidebar */
	.recipe-layout {
		display: grid;
		gap: var(--spacing-2xl);
	}

	/* Only apply two-column layout when desktop timeline is present */
	@media (min-width: 768px) {
		.recipe-layout:has(:global(.timeline-desktop)) {
			grid-template-columns: 220px 1fr;
			gap: var(--spacing-3xl);
		}
	}

	/* Recipe content markdown styling */
	.recipe-content {
		line-height: 1.7;
		min-width: 0; /* Prevent grid blowout */
	}

	/* Only add over-scroll padding when timeline navigation exists */
	.recipe-layout:has(:global(.timeline-desktop)) .recipe-content {
		padding-bottom: 70vh;
	}

	.recipe-content :global(h2) {
		margin: var(--spacing-3xl) 0 var(--spacing-lg);
		font-family: var(--font-display);
		font-size: var(--font-size-title);
		font-weight: 600;
		color: var(--color-text-primary);
		border-bottom: 1px solid var(--color-border);
		padding-bottom: var(--spacing-sm);
	}

	.recipe-content :global(h3) {
		margin: var(--spacing-2xl) 0 var(--spacing-md);
		font-family: var(--font-display);
		font-size: var(--font-size-body);
		font-weight: 600;
		color: var(--color-text-primary);
	}

	.recipe-content :global(ul) {
		margin: 0 0 var(--spacing-xl);
		padding-left: var(--spacing-xl);
	}

	.recipe-content :global(li) {
		margin-bottom: var(--spacing-sm);
		color: var(--color-text-secondary);
	}

	.recipe-content :global(ol) {
		margin: 0 0 var(--spacing-xl);
		padding-left: var(--spacing-xl);
	}

	.recipe-content :global(ol li) {
		margin-bottom: var(--spacing-lg);
	}

	.recipe-content :global(p) {
		margin: 0 0 var(--spacing-lg);
		color: var(--color-text-secondary);
	}

	.recipe-content :global(strong) {
		color: var(--color-text-primary);
		font-weight: 600;
	}
</style>

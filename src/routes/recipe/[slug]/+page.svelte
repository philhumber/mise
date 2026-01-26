<script lang="ts">
	import { onMount, onDestroy } from 'svelte';
	import { goto } from '$app/navigation';
	import { resolve } from '$app/paths';
	import Timeline from '$lib/components/Timeline.svelte';
	import { fetchUserRecipe, checkAuth, deleteRecipe } from '$lib/api/recipes';
	import { setPageTitle, clearPageTitle } from '$lib/stores/pageTitle';
	import type { Recipe } from '$lib/types';

	let { data } = $props();

	let recipe = $state<Recipe | null>(null);
	let fetchComplete = $state(false);
	let notFound = $state(false);
	let isAuthenticated = $state(false);
	let showDeleteConfirm = $state(false);
	let isDeleting = $state(false);

	const isLoading = $derived(!recipe && !fetchComplete);

	// Set page title when recipe changes
	$effect.pre(() => {
		if (recipe) {
			setPageTitle(recipe.title, recipe.subtitle || null);
		} else {
			// Show back button immediately while loading
			setPageTitle(null, null);
		}
	});

	onMount(async () => {
		// Check auth status
		isAuthenticated = await checkAuth();

		// Fetch recipe from API
		if (data.slug) {
			const apiRecipe = await fetchUserRecipe(data.slug);
			if (apiRecipe) {
				recipe = apiRecipe;
			} else {
				notFound = true;
			}
		}
		fetchComplete = true;
	});

	onDestroy(() => {
		clearPageTitle();
	});

	const difficultyDots = {
		easy: 1,
		intermediate: 2,
		advanced: 3
	};

	async function handleDelete() {
		if (!data.slug || isDeleting) return;

		isDeleting = true;
		const success = await deleteRecipe(data.slug);

		if (success) {
			goto(resolve('/'));
		} else {
			isDeleting = false;
			showDeleteConfirm = false;
			alert('Failed to delete recipe');
		}
	}
</script>

{#if isLoading}
	<div class="recipe-detail">
		<div class="loading">
			<p class="text-meta">Loading recipe...</p>
		</div>
	</div>
{:else if notFound || !recipe}
	<div class="recipe-detail">
		<div class="not-found">
			<h1 class="text-display">Recipe not found</h1>
			<p class="text-meta">The recipe "{data.slug}" could not be found.</p>
		</div>
	</div>
{:else}
	<div class="recipe-detail">
		<div class="stats-bar">
			<div class="stat">
				<span class="stat-value">{recipe.active_time}</span>
				<span class="stat-label">Active</span>
			</div>
			<div class="stat">
				<span class="stat-value">{recipe.total_time}</span>
				<span class="stat-label">Total</span>
			</div>
			<div class="stat">
				<span class="stat-value">{recipe.serves}</span>
				<span class="stat-label">Serves</span>
			</div>
			<div class="stat">
				<div class="difficulty-dots">
					{#each [0, 1, 2] as i (i)}
						<span class="dot" class:filled={i < difficultyDots[recipe.difficulty]}></span>
					{/each}
				</div>
				<span class="stat-label">{recipe.difficulty}</span>
			</div>
		</div>

		<div class="tags">
			{#each recipe.tags as tag (tag)}
				<span class="pill">{tag}</span>
			{/each}
		</div>

		{#if isAuthenticated}
			<div class="recipe-actions">
				<button
					class="delete-button"
					onclick={() => { showDeleteConfirm = true; }}
					aria-label="Delete recipe"
				>
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 11v6" />
					</svg>
					Delete
				</button>
			</div>
		{/if}

		<div class="recipe-layout">
			<Timeline content={recipe.content} />

			<article class="recipe-content">
				<!-- eslint-disable-next-line svelte/no-at-html-tags -- content is sanitized by API or from trusted build-time files -->
				{@html recipe.content}
			</article>
		</div>
	</div>
{/if}

<!-- Delete confirmation dialog -->
{#if showDeleteConfirm}
	<div class="confirm-overlay" onclick={() => { if (!isDeleting) showDeleteConfirm = false; }} role="presentation">
		<div class="confirm-dialog" onclick={(e) => e.stopPropagation()} role="alertdialog" aria-modal="true" tabindex="-1">
			<p class="confirm-title">Delete this recipe?</p>
			<p class="confirm-text">This action can be undone by an administrator.</p>
			<div class="confirm-actions">
				<button
					class="btn btn-secondary"
					onclick={() => { showDeleteConfirm = false; }}
					disabled={isDeleting}
				>
					Cancel
				</button>
				<button
					class="btn btn-danger"
					onclick={handleDelete}
					disabled={isDeleting}
				>
					{isDeleting ? 'Deleting...' : 'Delete'}
				</button>
			</div>
		</div>
	</div>
{/if}

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

	.loading,
	.not-found {
		padding: var(--spacing-3xl) 0;
		text-align: center;
	}

	.not-found h1 {
		margin: 0 0 var(--spacing-lg);
		font-size: var(--font-size-title);
		color: var(--color-text-primary);
	}

	/* Recipe actions (delete button) */
	.recipe-actions {
		margin-bottom: var(--spacing-2xl);
	}

	.delete-button {
		display: inline-flex;
		align-items: center;
		gap: var(--spacing-xs);
		padding: var(--spacing-sm) var(--spacing-md);
		font-family: var(--font-body);
		font-size: var(--font-size-small);
		color: var(--color-text-secondary);
		background: transparent;
		border: 1px solid var(--color-border);
		border-radius: 6px;
		cursor: pointer;
		transition:
			color var(--transition-fast),
			border-color var(--transition-fast),
			background-color var(--transition-fast);
	}

	.delete-button:hover {
		color: #dc2626;
		border-color: #dc2626;
		background-color: rgba(220, 38, 38, 0.05);
	}

	.delete-button svg {
		flex-shrink: 0;
	}

	/* Confirmation dialog overlay */
	.confirm-overlay {
		position: fixed;
		inset: 0;
		background: rgba(0, 0, 0, 0.5);
		display: flex;
		align-items: center;
		justify-content: center;
		z-index: 1000;
		padding: var(--spacing-lg);
	}

	.confirm-dialog {
		background: var(--color-surface);
		border-radius: 6px;
		padding: var(--spacing-xl);
		max-width: 320px;
		width: 100%;
		box-shadow: 0 4px 24px rgba(0, 0, 0, 0.15);
	}

	.confirm-title {
		margin: 0 0 var(--spacing-sm);
		font-family: var(--font-display);
		font-size: var(--font-size-body);
		font-weight: 600;
		color: var(--color-text-primary);
	}

	.confirm-text {
		margin: 0 0 var(--spacing-xl);
		font-family: var(--font-body);
		font-size: var(--font-size-small);
		color: var(--color-text-secondary);
	}

	.confirm-actions {
		display: flex;
		gap: var(--spacing-sm);
		justify-content: flex-end;
	}

	.btn {
		padding: var(--spacing-sm) var(--spacing-lg);
		font-family: var(--font-body);
		font-size: var(--font-size-small);
		font-weight: 500;
		border-radius: 6px;
		border: none;
		cursor: pointer;
		transition:
			background-color var(--transition-fast),
			opacity var(--transition-fast);
	}

	.btn:disabled {
		opacity: 0.6;
		cursor: not-allowed;
	}

	.btn-secondary {
		background: var(--color-border);
		color: var(--color-text-primary);
	}

	.btn-secondary:hover:not(:disabled) {
		background: var(--color-text-tertiary);
	}

	.btn-danger {
		background: #dc2626;
		color: white;
	}

	.btn-danger:hover:not(:disabled) {
		background: #b91c1c;
	}
</style>

<script lang="ts">
	import { onMount, onDestroy } from 'svelte';
	import { goto } from '$app/navigation';
	import { resolve } from '$app/paths';
	import Timeline from '$lib/components/Timeline.svelte';
	import EditModal from '$lib/components/EditModal.svelte';
	import IngredientGroup from '$lib/components/IngredientGroup.svelte';
	import { fetchUserRecipe, fetchRecipeForEdit, checkAuth, deleteRecipe } from '$lib/api/recipes';
	import { setPageTitle, clearPageTitle } from '$lib/stores/pageTitle';
	import { requestWakeLock, releaseWakeLock, getWakeLockEnabled } from '$lib/stores/wakeLock';
	import { parseIngredients } from '$lib/utils/ingredients';
	import type { Recipe } from '$lib/types';

	let { data } = $props();

	let recipe = $state<Recipe | null>(null);
	let fetchComplete = $state(false);
	let notFound = $state(false);
	let isAuthenticated = $state(false);
	let showDeleteConfirm = $state(false);
	let isDeleting = $state(false);

	// Edit modal state
	let showEditModal = $state(false);
	let recipeMarkdown = $state('');
	let isLoadingMarkdown = $state(false);

	const isLoading = $derived(!recipe && !fetchComplete);
	const ingredientGroups = $derived(recipe ? parseIngredients(recipe.content) : []);

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

		// Auto-activate wake lock if user preference enabled
		if (getWakeLockEnabled() && recipe && !notFound) {
			await requestWakeLock();
		}
	});

	onDestroy(() => {
		clearPageTitle();
		releaseWakeLock();
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

	async function handleEditClick() {
		if (!data.slug || isLoadingMarkdown) return;

		isLoadingMarkdown = true;
		const editData = await fetchRecipeForEdit(data.slug);
		isLoadingMarkdown = false;

		if (editData) {
			recipeMarkdown = editData.markdown;
			showEditModal = true;
		} else {
			alert('Failed to load recipe for editing');
		}
	}

	async function handleEditSuccess() {
		showEditModal = false;
		// Refresh the recipe data
		if (data.slug) {
			const updated = await fetchUserRecipe(data.slug);
			if (updated) {
				recipe = updated;
			}
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
		<div class="recipe-header">
			{#if isAuthenticated}
				<div class="ghost-actions">
					<button
						class="ghost-btn"
						onclick={handleEditClick}
						disabled={isLoadingMarkdown}
						aria-label="Edit recipe"
					>
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" />
							<path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
						</svg>
					</button>
					<button
						class="ghost-btn ghost-btn-danger"
						onclick={() => { showDeleteConfirm = true; }}
						aria-label="Delete recipe"
					>
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 11v6" />
						</svg>
					</button>
				</div>
			{/if}

			<p class="recipe-meta">
				<span class="meta-value">{recipe.active_time}</span> active
				<span class="meta-sep">·</span>
				<span class="meta-value">{recipe.total_time}</span> total
				<span class="meta-sep">·</span>
				Serves <span class="meta-value">{recipe.serves}</span>
				<span class="meta-sep">·</span>
				<span class="difficulty-inline">
					{#each [0, 1, 2] as i (i)}
						<span class="dot-sm" class:filled={i < difficultyDots[recipe.difficulty]}></span>
					{/each}
				</span>
				{recipe.difficulty}
			</p>

			{#if recipe.tags.length > 0}
				<p class="recipe-tags">{recipe.tags.join(', ')}</p>
			{/if}
		</div>

		<div class="recipe-layout">
			<Timeline content={recipe.content} />

			<div class="recipe-main">
				<IngredientGroup groups={ingredientGroups} />

				<article class="recipe-content">
					<!-- eslint-disable-next-line svelte/no-at-html-tags -- content is sanitized by API or from trusted build-time files -->
					{@html recipe.content}
				</article>
			</div>
		</div>
	</div>
{/if}

<!-- Edit modal -->
<EditModal
	open={showEditModal}
	slug={data.slug}
	initialMarkdown={recipeMarkdown}
	onClose={() => { showEditModal = false; }}
	onSuccess={handleEditSuccess}
/>

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

	/* Recipe header - compact meta line */
	.recipe-header {
		position: relative;
		margin-bottom: var(--spacing-lg);
		padding-right: 80px; /* Space for ghost actions */
	}

	.recipe-meta {
		font-family: var(--font-body);
		font-size: var(--font-size-meta);
		color: var(--color-text-secondary);
		margin: 0;
		line-height: 1.6;
	}

	.meta-value {
		font-family: var(--font-display);
		font-weight: 500;
		color: var(--color-text-primary);
	}

	.meta-sep {
		margin: 0 var(--spacing-sm);
		color: var(--color-text-tertiary);
	}

	.difficulty-inline {
		display: inline-flex;
		gap: 3px;
		margin-right: var(--spacing-xs);
		vertical-align: middle;
	}

	.dot-sm {
		width: 6px;
		height: 6px;
		background: var(--color-border);
		border-radius: 50%;
	}

	.dot-sm.filled {
		background: var(--color-accent);
	}

	.recipe-tags {
		font-family: var(--font-body);
		font-size: var(--font-size-small);
		color: var(--color-text-tertiary);
		margin: var(--spacing-sm) 0 0;
		font-style: italic;
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

	/* Recipe main column */
	.recipe-main {
		min-width: 0; /* Prevent grid blowout */
	}

	/* Only add over-scroll padding when timeline navigation exists */
	.recipe-layout:has(:global(.timeline-desktop)) .recipe-main {
		padding-bottom: 70vh;
	}

	/* Recipe content markdown styling */
	.recipe-content {
		line-height: 1.7;
	}

	/* Hide Ingredients section from raw HTML (rendered separately by IngredientGroup) */
	.recipe-content :global(h2#ingredients),
	.recipe-content :global(h2#ingredients ~ h3:not([id^='t-']):not(#day-of):not(#service)),
	.recipe-content :global(h2#ingredients ~ ul) {
		display: none;
	}

	/* Stop hiding at the Method section */
	.recipe-content :global(h2#method),
	.recipe-content :global(h2#method ~ *) {
		display: block;
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

	/* Ghost action buttons - top right */
	.ghost-actions {
		position: absolute;
		top: 0;
		right: 0;
		display: flex;
		gap: var(--spacing-xs);
	}

	.ghost-btn {
		width: 36px;
		height: 36px;
		display: flex;
		align-items: center;
		justify-content: center;
		background: transparent;
		border: none;
		border-radius: var(--radius-sm);
		color: var(--color-text-tertiary);
		opacity: 0.4;
		cursor: pointer;
		transition: all var(--transition-fast);
	}

	.ghost-btn:hover:not(:disabled) {
		opacity: 1;
		color: var(--color-accent);
		background: var(--color-highlight);
	}

	.ghost-btn-danger:hover:not(:disabled) {
		color: #dc2626;
		background: rgba(220, 38, 38, 0.08);
	}

	.ghost-btn:disabled {
		opacity: 0.2;
		cursor: not-allowed;
	}

	.ghost-btn svg {
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

	/* ===== Method Section with Timeline Border ===== */

	/* Method ordered lists get subtle timeline styling */
	.recipe-content :global(h2#method ~ ol) {
		position: relative;
		padding-left: var(--spacing-2xl);
		border-left: 2px solid var(--color-border);
		margin-left: var(--spacing-xs);
	}

	/* Method step items */
	.recipe-content :global(h2#method ~ ol li) {
		margin-bottom: var(--spacing-xl);
	}

	/* Timeline section headings (T-24h, Day-of, Service) */
	.recipe-content :global(h3[id^='t-']),
	.recipe-content :global(h3#day-of),
	.recipe-content :global(h3#service) {
		color: var(--color-accent);
		margin-top: var(--spacing-3xl);
	}
</style>

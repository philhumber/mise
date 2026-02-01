<script lang="ts">
	import { onMount, onDestroy } from 'svelte';
	import { goto } from '$app/navigation';
	import { base } from '$app/paths';
	import { fetchMeal, deleteMeal, refreshMealSnapshot } from '$lib/api/meals';
	import { checkAuth } from '$lib/api/recipes';
	import { setPageTitle, clearPageTitle } from '$lib/stores/pageTitle';
	import type { Meal } from '$lib/types';
	import MealModal from '$lib/components/MealModal.svelte';
	import MealTimeline from '$lib/components/MealTimeline.svelte';
	import MealIngredients from '$lib/components/MealIngredients.svelte';

	let { data } = $props();

	let meal = $state<Meal | null>(null);
	let loading = $state(true);
	let isAuthenticated = $state(false);
	let showEditModal = $state(false);
	let refreshing = $state(false);
	let deleting = $state(false);

	onMount(async () => {
		const [mealData, authStatus] = await Promise.all([fetchMeal(data.slug), checkAuth()]);

		meal = mealData;
		isAuthenticated = authStatus;
		loading = false;

		if (meal) {
			setPageTitle(meal.title, null, true);
		}
	});

	onDestroy(() => {
		clearPageTitle();
	});

	async function handleRefresh() {
		if (!meal) return;
		refreshing = true;

		const refreshed = await refreshMealSnapshot(meal.slug);
		if (refreshed) {
			meal = refreshed;
		}

		refreshing = false;
	}

	async function handleDelete() {
		if (!meal || !confirm('Delete this meal plan?')) return;

		deleting = true;
		const success = await deleteMeal(meal.slug);

		if (success) {
			goto(`${base}/meals`);
		} else {
			deleting = false;
			alert('Failed to delete meal');
		}
	}

	function handleMealUpdated(updatedMeal: Meal) {
		meal = updatedMeal;
		showEditModal = false;
	}
</script>

<div class="meal-detail">
	{#if loading}
		<p class="loading">Loading meal...</p>
	{:else if !meal}
		<div class="not-found">
			<p>Meal not found</p>
			<a href="{base}/meals">Back to meals</a>
		</div>
	{:else}
		<!-- Stale Banner -->
		{#if meal.is_stale}
			<div class="stale-banner">
				{#if refreshing}
					<p>Refreshing meal plan...</p>
				{:else}
					<p>Some recipes in this meal have been updated.</p>
					<button onclick={handleRefresh}>Refresh meal plan</button>
				{/if}
			</div>
		{/if}

		<!-- Header -->
		<header class="meal-header">
			<div class="title-section">
				<h1>{meal.title}</h1>
				{#if meal.description}
					<p class="description">{meal.description}</p>
				{/if}
			</div>

			{#if isAuthenticated}
				<div class="actions">
					<button class="refresh-btn" onclick={handleRefresh} disabled={refreshing}>
						{refreshing ? 'Refreshing...' : 'Refresh'}
					</button>
					<button class="edit-btn" onclick={() => (showEditModal = true)}>Edit</button>
					<button class="delete-btn" onclick={handleDelete} disabled={deleting}>
						{deleting ? 'Deleting...' : 'Delete'}
					</button>
				</div>
			{/if}
		</header>

		<!-- Course Overview -->
		<section class="courses-section">
			<h2>Courses</h2>
			<ol class="course-list">
				{#each meal.snapshot.recipes as recipe (recipe.slug)}
					<li class:deleted={recipe.is_deleted}>
						{#if recipe.is_deleted}
							<span class="deleted-label">Deleted</span>
						{/if}
						<span class="course-title">{recipe.title}</span>
						{#if recipe.subtitle}
							<span class="course-subtitle">{recipe.subtitle}</span>
						{/if}
					</li>
				{/each}
			</ol>
		</section>

		<!-- Ingredients -->
		<section class="ingredients-section">
			<MealIngredients snapshot={meal.snapshot} />
		</section>

		<!-- Timeline -->
		<section class="timeline-section">
			<MealTimeline snapshot={meal.snapshot} />
		</section>
	{/if}
</div>

{#if showEditModal && meal}
	<MealModal
		mode="edit"
		{meal}
		onClose={() => (showEditModal = false)}
		onSuccess={handleMealUpdated}
	/>
{/if}

<style>
	.meal-detail {
		max-width: 900px;
		margin: 0 auto;
		padding: var(--spacing-xl);
	}

	.loading,
	.not-found {
		text-align: center;
		padding: var(--spacing-3xl);
		color: var(--color-text-secondary);
	}

	.not-found a {
		color: var(--color-accent);
	}

	.stale-banner {
		background: #fef3c7;
		border: 1px solid #fcd34d;
		border-radius: var(--radius-md);
		padding: var(--spacing-md) var(--spacing-lg);
		margin-bottom: var(--spacing-xl);
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: var(--spacing-lg);
	}

	.stale-banner p {
		margin: 0;
		color: #92400e;
	}

	.stale-banner button {
		background: #92400e;
		color: white;
		border: none;
		border-radius: 4px;
		padding: var(--spacing-xs) var(--spacing-md);
		font-size: 0.875rem;
		cursor: pointer;
		white-space: nowrap;
		min-height: 44px;
	}

	.meal-header {
		display: flex;
		justify-content: space-between;
		align-items: flex-start;
		gap: var(--spacing-lg);
		margin-bottom: var(--spacing-2xl);
	}

	.meal-header h1 {
		font-family: var(--font-display);
		font-size: 2rem;
		margin: 0 0 var(--spacing-sm);
	}

	.description {
		color: var(--color-text-secondary);
		margin: 0;
	}

	.actions {
		display: flex;
		gap: var(--spacing-sm);
	}

	.refresh-btn,
	.edit-btn,
	.delete-btn {
		padding: var(--spacing-xs) var(--spacing-md);
		border-radius: 4px;
		font-size: 0.875rem;
		cursor: pointer;
		min-height: 44px;
	}

	.refresh-btn {
		background: var(--color-accent);
		border: 1px solid var(--color-accent);
		color: white;
	}

	.refresh-btn:disabled {
		opacity: 0.5;
		cursor: not-allowed;
	}

	.edit-btn {
		background: var(--color-surface);
		border: 1px solid var(--color-border);
		color: var(--color-text-primary);
	}

	.delete-btn {
		background: none;
		border: 1px solid #fecaca;
		color: #dc2626;
	}

	.delete-btn:disabled {
		opacity: 0.5;
		cursor: not-allowed;
	}

	section {
		margin-bottom: var(--spacing-2xl);
	}

	section h2 {
		font-family: var(--font-display);
		font-size: 1.25rem;
		margin: 0 0 var(--spacing-lg);
		color: var(--color-text-primary);
	}

	.course-list {
		margin: 0;
		padding-left: var(--spacing-xl);
	}

	.course-list li {
		padding: var(--spacing-sm) 0;
	}

	.course-list li.deleted {
		opacity: 0.5;
	}

	.deleted-label {
		background: #fecaca;
		color: #dc2626;
		font-size: 0.625rem;
		padding: 2px 6px;
		border-radius: var(--radius-sm);
		margin-right: var(--spacing-sm);
		text-transform: uppercase;
		font-weight: 600;
	}

	.course-title {
		font-weight: 500;
	}

	.course-subtitle {
		color: var(--color-text-secondary);
		font-size: 0.875rem;
	}

	.course-subtitle::before {
		content: ' — ';
	}
</style>

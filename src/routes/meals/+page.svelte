<script lang="ts">
	import { onMount, onDestroy } from 'svelte';
	import { fetchMeals } from '$lib/api/meals';
	import { checkAuth } from '$lib/api/recipes';
	import { setPageTitle, clearPageTitle } from '$lib/stores/pageTitle';
	import type { MealMeta } from '$lib/types';
	import MealCard from '$lib/components/MealCard.svelte';
	import MealModal from '$lib/components/MealModal.svelte';

	let meals = $state<MealMeta[]>([]);
	let loading = $state(true);
	let isAuthenticated = $state(false);
	let showModal = $state(false);

	onMount(async () => {
		setPageTitle(null);

		const [mealsData, authStatus] = await Promise.all([fetchMeals(), checkAuth()]);

		meals = mealsData;
		isAuthenticated = authStatus;
		loading = false;
	});

	onDestroy(() => {
		clearPageTitle();
	});

	async function handleMealCreated() {
		showModal = false;
		meals = await fetchMeals();
	}
</script>

<div class="meals-page">
	<div class="page-header">
		<h1>Meals</h1>
		{#if isAuthenticated}
			<button class="new-meal-btn" onclick={() => (showModal = true)}> New Meal </button>
		{/if}
	</div>

	{#if loading}
		<p class="loading">Loading meals...</p>
	{:else if meals.length === 0}
		<div class="empty-state">
			<p>No meal plans yet.</p>
			{#if isAuthenticated}
				<button class="create-btn" onclick={() => (showModal = true)}>
					Create your first meal
				</button>
			{/if}
		</div>
	{:else}
		<div class="meals-grid">
			{#each meals as meal (meal.slug)}
				<MealCard {meal} />
			{/each}
		</div>
	{/if}
</div>

{#if showModal}
	<MealModal mode="create" onClose={() => (showModal = false)} onSuccess={handleMealCreated} />
{/if}

<style>
	.meals-page {
		max-width: 1200px;
		margin: 0 auto;
		padding: var(--spacing-xl);
	}

	.page-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: var(--spacing-2xl);
	}

	.page-header h1 {
		font-family: var(--font-display);
		font-size: 1.75rem;
		font-weight: 500;
		margin: 0;
	}

	.new-meal-btn,
	.create-btn {
		font-family: var(--font-body);
		font-size: 0.875rem;
		font-weight: 500;
		padding: var(--spacing-sm) var(--spacing-lg);
		background: var(--color-accent);
		color: white;
		border: none;
		border-radius: var(--radius-md);
		cursor: pointer;
		transition: opacity 0.2s;
		min-height: 44px;
	}

	.new-meal-btn:hover,
	.create-btn:hover {
		opacity: 0.9;
	}

	.loading {
		text-align: center;
		color: var(--color-text-secondary);
		padding: var(--spacing-3xl);
	}

	.empty-state {
		text-align: center;
		padding: var(--spacing-4xl) var(--spacing-xl);
		color: var(--color-text-secondary);
	}

	.empty-state p {
		margin-bottom: var(--spacing-lg);
	}

	.meals-grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
		gap: var(--spacing-lg);
	}
</style>

<script lang="ts">
	import { fetchUserRecipes } from '$lib/api/recipes';
	import { createMeal, updateMeal } from '$lib/api/meals';
	import type { Meal, RecipeMeta, ValidationError } from '$lib/types';

	interface Props {
		mode: 'create' | 'edit';
		meal?: Meal;
		onClose: () => void;
		onSuccess: (meal: Meal) => void;
	}

	let { mode, meal, onClose, onSuccess }: Props = $props();

	let title = $state(meal?.title ?? '');
	let description = $state(meal?.description ?? '');
	let selectedSlugs = $state<string[]>(meal?.snapshot.recipes.map((r) => r.slug) ?? []);

	let availableRecipes = $state<RecipeMeta[]>([]);
	let loading = $state(true);
	let submitting = $state(false);
	let errors = $state<ValidationError[]>([]);

	$effect(() => {
		loadRecipes();
	});

	async function loadRecipes() {
		availableRecipes = await fetchUserRecipes();
		loading = false;
	}

	function toggleRecipe(slug: string) {
		if (selectedSlugs.includes(slug)) {
			selectedSlugs = selectedSlugs.filter((s) => s !== slug);
		} else {
			selectedSlugs = [...selectedSlugs, slug];
		}
	}

	function moveUp(index: number) {
		if (index === 0) return;
		const newOrder = [...selectedSlugs];
		[newOrder[index - 1], newOrder[index]] = [newOrder[index], newOrder[index - 1]];
		selectedSlugs = newOrder;
	}

	function moveDown(index: number) {
		if (index === selectedSlugs.length - 1) return;
		const newOrder = [...selectedSlugs];
		[newOrder[index], newOrder[index + 1]] = [newOrder[index + 1], newOrder[index]];
		selectedSlugs = newOrder;
	}

	function removeRecipe(slug: string) {
		selectedSlugs = selectedSlugs.filter((s) => s !== slug);
	}

	async function handleSubmit() {
		errors = [];

		if (!title.trim()) {
			errors = [{ field: 'title', message: 'Title is required' }];
			return;
		}

		if (selectedSlugs.length === 0) {
			errors = [{ field: 'recipe_slugs', message: 'Select at least one recipe' }];
			return;
		}

		submitting = true;

		const input = {
			title: title.trim(),
			description: description.trim() || undefined,
			recipe_slugs: selectedSlugs
		};

		const result = mode === 'create' ? await createMeal(input) : await updateMeal(meal!.slug, input);

		submitting = false;

		if (result.success && result.meal) {
			onSuccess(result.meal);
		} else {
			errors = result.errors ?? [{ field: 'general', message: 'Operation failed' }];
		}
	}

	function getRecipeTitle(slug: string): string {
		return availableRecipes.find((r) => r.slug === slug)?.title ?? slug;
	}

	function handleKeydown(event: KeyboardEvent) {
		if (event.key === 'Escape') {
			onClose();
		}
	}
</script>

<svelte:window onkeydown={handleKeydown} />

<!-- svelte-ignore a11y_click_events_have_key_events -->
<!-- svelte-ignore a11y_no_static_element_interactions -->
<div class="modal-overlay" onclick={onClose}>
	<div class="modal" onclick={(e) => e.stopPropagation()}>
		<header class="modal-header">
			<h2>{mode === 'create' ? 'New Meal' : 'Edit Meal'}</h2>
			<button class="close-btn" onclick={onClose}>&times;</button>
		</header>

		<form
			class="modal-body"
			onsubmit={(e) => {
				e.preventDefault();
				handleSubmit();
			}}
		>
			{#if errors.length > 0}
				<div class="error-list">
					{#each errors as error}
						<p class="error">{error.message}</p>
					{/each}
				</div>
			{/if}

			<div class="field">
				<label for="title">Title *</label>
				<input id="title" type="text" bind:value={title} placeholder="Sunday Dinner" />
			</div>

			<div class="field">
				<label for="description">Description</label>
				<textarea
					id="description"
					bind:value={description}
					placeholder="Optional description..."
					rows="2"
				></textarea>
			</div>

			<div class="recipe-picker">
				<div class="picker-section">
					<h3>Available Recipes</h3>
					{#if loading}
						<p class="loading-text">Loading...</p>
					{:else if availableRecipes.length === 0}
						<p class="empty-text">No recipes available. Upload some recipes first.</p>
					{:else}
						<div class="recipe-list">
							{#each availableRecipes as recipe (recipe.slug)}
								<label class="recipe-option">
									<input
										type="checkbox"
										checked={selectedSlugs.includes(recipe.slug)}
										onchange={() => toggleRecipe(recipe.slug)}
									/>
									<span class="recipe-title">{recipe.title}</span>
								</label>
							{/each}
						</div>
					{/if}
				</div>

				<div class="picker-section">
					<h3>Course Order ({selectedSlugs.length})</h3>
					{#if selectedSlugs.length === 0}
						<p class="empty-text">Select recipes from the left</p>
					{:else}
						<div class="selected-list">
							{#each selectedSlugs as slug, index (slug)}
								<div class="selected-item">
									<span class="course-num">Course {index + 1}</span>
									<span class="recipe-name">{getRecipeTitle(slug)}</span>
									<div class="item-actions">
										<button
											type="button"
											class="move-btn"
											disabled={index === 0}
											onclick={() => moveUp(index)}
											title="Move up"
										>
											&#8593;
										</button>
										<button
											type="button"
											class="move-btn"
											disabled={index === selectedSlugs.length - 1}
											onclick={() => moveDown(index)}
											title="Move down"
										>
											&#8595;
										</button>
										<button
											type="button"
											class="remove-btn"
											onclick={() => removeRecipe(slug)}
											title="Remove"
										>
											&times;
										</button>
									</div>
								</div>
							{/each}
						</div>
					{/if}
				</div>
			</div>

			<div class="actions">
				<button type="button" class="cancel-btn" onclick={onClose}> Cancel </button>
				<button
					type="submit"
					class="submit-btn"
					disabled={submitting || !title.trim() || selectedSlugs.length === 0}
				>
					{submitting ? 'Saving...' : mode === 'create' ? 'Create Meal' : 'Save Changes'}
				</button>
			</div>
		</form>
	</div>
</div>

<style>
	.modal-overlay {
		position: fixed;
		inset: 0;
		background: rgba(0, 0, 0, 0.5);
		display: flex;
		align-items: center;
		justify-content: center;
		z-index: 1000;
		padding: var(--spacing-lg);
	}

	.modal {
		background: var(--color-surface);
		border-radius: var(--radius-md);
		width: 100%;
		max-width: 800px;
		max-height: 90vh;
		overflow: hidden;
		display: flex;
		flex-direction: column;
	}

	.modal-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: var(--spacing-lg);
		border-bottom: 1px solid var(--color-border);
	}

	.modal-header h2 {
		font-family: var(--font-display);
		font-size: 1.25rem;
		margin: 0;
	}

	.close-btn {
		font-size: 1.5rem;
		background: none;
		border: none;
		color: var(--color-text-tertiary);
		cursor: pointer;
		padding: var(--spacing-xs);
		line-height: 1;
		min-width: 44px;
		min-height: 44px;
	}

	.modal-body {
		padding: var(--spacing-lg);
		overflow-y: auto;
		display: flex;
		flex-direction: column;
		gap: var(--spacing-lg);
	}

	.error-list {
		background: #fef2f2;
		border: 1px solid #fecaca;
		border-radius: var(--radius-md);
		padding: var(--spacing-md);
	}

	.error {
		color: #dc2626;
		margin: 0;
		font-size: 0.875rem;
	}

	.field {
		display: flex;
		flex-direction: column;
		gap: var(--spacing-xs);
	}

	.field label {
		font-size: 0.875rem;
		font-weight: 500;
		color: var(--color-text-secondary);
	}

	.field input,
	.field textarea {
		padding: var(--spacing-sm) var(--spacing-md);
		border: 1px solid var(--color-border);
		border-radius: var(--radius-md);
		font-family: var(--font-body);
		font-size: 1rem;
		background: var(--color-bg);
		color: var(--color-text-primary);
	}

	.recipe-picker {
		display: grid;
		grid-template-columns: 1fr 1fr;
		gap: var(--spacing-lg);
		min-height: 200px;
	}

	.picker-section {
		border: 1px solid var(--color-border);
		border-radius: var(--radius-md);
		padding: var(--spacing-md);
		display: flex;
		flex-direction: column;
		min-width: 0; /* Allow grid column to shrink */
		overflow: hidden;
	}

	.picker-section h3 {
		font-size: 0.875rem;
		font-weight: 500;
		margin: 0 0 var(--spacing-md);
		color: var(--color-text-secondary);
	}

	.recipe-list,
	.selected-list {
		flex: 1;
		overflow-y: auto;
		display: flex;
		flex-direction: column;
		gap: var(--spacing-xs);
		min-width: 0; /* Allow content to shrink */
	}

	.recipe-option {
		display: flex;
		align-items: center;
		gap: var(--spacing-sm);
		padding: var(--spacing-sm);
		border-radius: 4px;
		cursor: pointer;
		transition: background 0.2s;
		min-height: 44px;
		min-width: 0; /* Allow flex children to shrink */
	}

	.recipe-option:hover {
		background: var(--color-bg);
	}

	.recipe-option input[type='checkbox'] {
		flex-shrink: 0;
	}

	.recipe-title {
		font-size: 0.875rem;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}

	.selected-item {
		display: flex;
		align-items: center;
		gap: var(--spacing-sm);
		padding: var(--spacing-sm);
		background: var(--color-bg);
		border-radius: 4px;
		min-width: 0; /* Allow flex children to shrink */
	}

	.course-num {
		font-size: 0.75rem;
		font-weight: 500;
		color: var(--color-accent);
		white-space: nowrap;
		flex-shrink: 0;
	}

	.recipe-name {
		flex: 1;
		font-size: 0.875rem;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
		min-width: 0; /* Critical for text-overflow to work in flex */
	}

	.item-actions {
		display: flex;
		gap: 2px;
		flex-shrink: 0;
	}

	.move-btn,
	.remove-btn {
		font-size: 0.875rem;
		padding: 4px 8px;
		background: none;
		border: 1px solid var(--color-border);
		border-radius: 4px;
		cursor: pointer;
		color: var(--color-text-secondary);
		min-width: 32px;
		min-height: 32px;
	}

	.move-btn:disabled {
		opacity: 0.3;
		cursor: not-allowed;
	}

	.remove-btn {
		color: #dc2626;
	}

	.loading-text,
	.empty-text {
		font-size: 0.875rem;
		color: var(--color-text-tertiary);
		text-align: center;
		padding: var(--spacing-lg);
	}

	.actions {
		display: flex;
		justify-content: flex-end;
		gap: var(--spacing-md);
		padding-top: var(--spacing-md);
		border-top: 1px solid var(--color-border);
	}

	.cancel-btn {
		padding: var(--spacing-sm) var(--spacing-lg);
		background: none;
		border: 1px solid var(--color-border);
		border-radius: var(--radius-md);
		font-family: var(--font-body);
		cursor: pointer;
		color: var(--color-text-secondary);
		min-height: 44px;
	}

	.submit-btn {
		padding: var(--spacing-sm) var(--spacing-lg);
		background: var(--color-accent);
		color: white;
		border: none;
		border-radius: var(--radius-md);
		font-family: var(--font-body);
		font-weight: 500;
		cursor: pointer;
		min-height: 44px;
	}

	.submit-btn:disabled {
		opacity: 0.5;
		cursor: not-allowed;
	}

	@media (max-width: 640px) {
		.recipe-picker {
			grid-template-columns: 1fr;
		}
	}
</style>

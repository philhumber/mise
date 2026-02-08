<script lang="ts">
	import type { MealSnapshot } from '$lib/types';

	interface Props {
		snapshot: MealSnapshot;
		onRecipeNavigate?: (recipeSlug: string) => void;
	}

	let { snapshot, onRecipeNavigate }: Props = $props();

	// Get non-deleted recipes
	const recipes = $derived(snapshot.recipes.filter((r) => !r.is_deleted));

	// Mobile drawer state
	let isDrawerOpen = $state(false);

	function toggleDrawer() {
		isDrawerOpen = !isDrawerOpen;
	}

	function closeDrawer() {
		isDrawerOpen = false;
	}

	function handleLinkClick(event: MouseEvent, recipeSlug: string) {
		event.preventDefault();
		isDrawerOpen = false;
		onRecipeNavigate?.(recipeSlug);
	}

	function handleKeydown(event: KeyboardEvent) {
		if (event.key === 'Escape' && isDrawerOpen) {
			closeDrawer();
		}
	}
</script>

<svelte:window onkeydown={handleKeydown} />

{#if recipes.length > 0}
	<!-- Desktop: Sticky sidebar -->
	<aside class="course-nav course-nav-desktop" aria-labelledby="course-nav-heading-desktop">
		<h2 id="course-nav-heading-desktop" class="course-nav-title">Courses</h2>
		<nav>
			<ol class="course-nav-list">
				{#each recipes as recipe, index (recipe.slug)}
					<li class="course-nav-item">
						<a href="#recipe-{recipe.slug}" class="course-nav-link">
							<span class="course-nav-number">{index + 1}</span>
							<span class="course-nav-name">{recipe.title}</span>
						</a>
					</li>
				{/each}
			</ol>
		</nav>
	</aside>

	<!-- Mobile: Floating pill + Bottom sheet drawer -->
	<div class="course-nav-mobile-container">
		<!-- Floating trigger pill -->
		<button
			class="course-nav-pill"
			onclick={toggleDrawer}
			aria-expanded={isDrawerOpen}
			aria-controls="course-nav-drawer"
		>
			<svg
				class="course-nav-pill-icon"
				viewBox="0 0 24 24"
				fill="none"
				stroke="currentColor"
				stroke-width="2"
				stroke-linecap="round"
				stroke-linejoin="round"
			>
				<path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"></path>
				<path d="M7 2v20"></path>
				<path d="M21 15V2v0a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"></path>
			</svg>
			<span class="course-nav-pill-text">Courses</span>
			<span class="course-nav-pill-count">{recipes.length}</span>
		</button>

		<!-- Backdrop -->
		{#if isDrawerOpen}
			<div
				class="course-nav-backdrop"
				onclick={closeDrawer}
				onkeydown={(e) => e.key === 'Escape' && closeDrawer()}
				role="button"
				tabindex="-1"
				aria-label="Close courses"
			></div>
		{/if}

		<!-- Bottom sheet drawer -->
		<div
			id="course-nav-drawer"
			class="course-nav-drawer"
			class:course-nav-drawer-open={isDrawerOpen}
			aria-hidden={!isDrawerOpen}
		>
			<div class="course-nav-drawer-header">
				<h2 class="course-nav-drawer-title">Courses</h2>
				<button class="course-nav-drawer-close" onclick={closeDrawer} aria-label="Close courses">
					<svg
						viewBox="0 0 24 24"
						fill="none"
						stroke="currentColor"
						stroke-width="2"
						stroke-linecap="round"
						stroke-linejoin="round"
					>
						<line x1="18" y1="6" x2="6" y2="18"></line>
						<line x1="6" y1="6" x2="18" y2="18"></line>
					</svg>
				</button>
			</div>
			<nav class="course-nav-drawer-content">
				<ol class="course-nav-drawer-list">
					{#each recipes as recipe, index (recipe.slug)}
						<li class="course-nav-drawer-item">
							<a
								href="#recipe-{recipe.slug}"
								class="course-nav-drawer-link"
								onclick={(e) => handleLinkClick(e, recipe.slug)}
							>
								<span class="course-nav-drawer-number">{index + 1}</span>
								<div class="course-nav-drawer-info">
									<span class="course-nav-drawer-name">{recipe.title}</span>
									{#if recipe.subtitle}
										<span class="course-nav-drawer-subtitle">{recipe.subtitle}</span>
									{/if}
								</div>
							</a>
						</li>
					{/each}
				</ol>
			</nav>
		</div>
	</div>
{/if}

<style>
	/* ===== DESKTOP COURSE NAV (Sticky Sidebar) ===== */
	.course-nav-desktop {
		display: none;
		background-color: var(--color-highlight);
		border-left: 3px solid var(--color-accent);
		border-radius: var(--radius-md);
		padding: var(--spacing-lg);
	}

	@media (min-width: 768px) {
		.course-nav-desktop {
			display: block;
			position: sticky;
			top: var(--spacing-xl);
			align-self: start;
		}
	}

	.course-nav-title {
		margin: 0 0 var(--spacing-md);
		font-family: var(--font-body);
		font-size: var(--font-size-small);
		font-weight: 600;
		text-transform: uppercase;
		letter-spacing: 0.05em;
		color: var(--color-text-secondary);
	}

	.course-nav-list {
		margin: 0;
		padding: 0;
		list-style: none;
		counter-reset: course;
	}

	.course-nav-item + .course-nav-item {
		margin-top: var(--spacing-xs);
	}

	.course-nav-link {
		display: flex;
		align-items: center;
		gap: var(--spacing-sm);
		min-height: 44px;
		padding: var(--spacing-sm) var(--spacing-md);
		text-decoration: none;
		border-radius: var(--radius-sm);
		transition: background-color var(--transition-fast);
	}

	.course-nav-link:hover {
		background-color: rgba(0, 0, 0, 0.05);
	}

	:global([data-theme='dark']) .course-nav-link:hover {
		background-color: rgba(255, 255, 255, 0.05);
	}

	.course-nav-number {
		display: flex;
		align-items: center;
		justify-content: center;
		width: 24px;
		height: 24px;
		background-color: var(--color-accent);
		color: white;
		border-radius: 50%;
		font-family: var(--font-display);
		font-size: var(--font-size-small);
		font-weight: 700;
		flex-shrink: 0;
	}

	.course-nav-name {
		font-family: var(--font-display);
		font-size: var(--font-size-small);
		font-weight: 500;
		color: var(--color-text-primary);
		line-height: 1.3;
	}

	/* ===== MOBILE COURSE NAV (Floating Pill + Bottom Sheet) ===== */
	.course-nav-mobile-container {
		display: block;
	}

	@media (min-width: 768px) {
		.course-nav-mobile-container {
			display: none;
		}
	}

	/* Floating pill button */
	.course-nav-pill {
		position: fixed;
		bottom: max(var(--spacing-xl), env(safe-area-inset-bottom));
		right: max(var(--spacing-lg), env(safe-area-inset-right));
		z-index: 100;
		display: flex;
		align-items: center;
		gap: var(--spacing-sm);
		padding: var(--spacing-sm) var(--spacing-lg);
		background-color: var(--color-accent);
		color: white;
		border: none;
		border-radius: 50px;
		font-family: var(--font-body);
		font-size: var(--font-size-small);
		font-weight: 600;
		cursor: pointer;
		box-shadow:
			0 4px 12px rgba(0, 0, 0, 0.15),
			0 2px 4px rgba(0, 0, 0, 0.1);
		transition:
			transform 0.2s ease,
			box-shadow 0.2s ease;
	}

	.course-nav-pill:hover {
		transform: translateY(-2px);
		box-shadow:
			0 6px 16px rgba(0, 0, 0, 0.2),
			0 3px 6px rgba(0, 0, 0, 0.12);
	}

	.course-nav-pill:active {
		transform: translateY(0);
	}

	.course-nav-pill-icon {
		width: 18px;
		height: 18px;
	}

	.course-nav-pill-text {
		letter-spacing: 0.02em;
	}

	.course-nav-pill-count {
		display: flex;
		align-items: center;
		justify-content: center;
		min-width: 20px;
		height: 20px;
		padding: 0 6px;
		background-color: rgba(255, 255, 255, 0.25);
		border-radius: 10px;
		font-size: 11px;
		font-weight: 700;
	}

	/* Backdrop overlay */
	.course-nav-backdrop {
		position: fixed;
		inset: 0;
		z-index: 200;
		background-color: rgba(0, 0, 0, 0.4);
		animation: fadeIn 0.2s ease;
	}

	:global([data-theme='dark']) .course-nav-backdrop {
		background-color: rgba(0, 0, 0, 0.6);
	}

	@keyframes fadeIn {
		from {
			opacity: 0;
		}
		to {
			opacity: 1;
		}
	}

	/* Bottom sheet drawer */
	.course-nav-drawer {
		position: fixed;
		bottom: 0;
		left: 0;
		right: 0;
		z-index: 300;
		max-height: 70vh;
		background-color: var(--color-surface);
		border-radius: var(--radius-md) var(--radius-md) 0 0;
		box-shadow: 0 -4px 24px rgba(0, 0, 0, 0.15);
		transform: translateY(100%);
		transition: transform 0.3s cubic-bezier(0.32, 0.72, 0, 1);
		overflow: hidden;
	}

	.course-nav-drawer-open {
		transform: translateY(0);
	}

	.course-nav-drawer-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: var(--spacing-lg) var(--spacing-xl);
		border-bottom: 1px solid var(--color-border);
		position: relative;
	}

	.course-nav-drawer-header::before {
		content: '';
		position: absolute;
		top: var(--spacing-sm);
		left: 50%;
		transform: translateX(-50%);
		width: 36px;
		height: 4px;
		background-color: var(--color-border);
		border-radius: 2px;
	}

	.course-nav-drawer-title {
		margin: 0;
		font-family: var(--font-display);
		font-size: var(--font-size-title);
		font-weight: 600;
		color: var(--color-text-primary);
	}

	.course-nav-drawer-close {
		display: flex;
		align-items: center;
		justify-content: center;
		width: 44px;
		height: 44px;
		background-color: var(--color-highlight);
		border: none;
		border-radius: 50%;
		color: var(--color-text-secondary);
		cursor: pointer;
		transition: background-color var(--transition-fast);
	}

	.course-nav-drawer-close:hover {
		background-color: var(--color-border);
	}

	.course-nav-drawer-close svg {
		width: 20px;
		height: 20px;
	}

	.course-nav-drawer-content {
		padding: var(--spacing-lg) var(--spacing-xl);
		padding-bottom: max(var(--spacing-2xl), env(safe-area-inset-bottom));
		overflow-y: auto;
		max-height: calc(70vh - 70px);
	}

	.course-nav-drawer-list {
		margin: 0;
		padding: 0;
		list-style: none;
	}

	.course-nav-drawer-item {
		position: relative;
	}

	/* Vertical connector line */
	.course-nav-drawer-item::before {
		content: '';
		position: absolute;
		left: 15px;
		top: 36px;
		bottom: 0;
		width: 2px;
		background-color: var(--color-border);
	}

	.course-nav-drawer-item:last-child::before {
		display: none;
	}

	.course-nav-drawer-link {
		display: flex;
		align-items: flex-start;
		gap: var(--spacing-md);
		padding: var(--spacing-md) 0;
		text-decoration: none;
		transition: opacity var(--transition-fast);
	}

	.course-nav-drawer-link:active {
		opacity: 0.7;
	}

	.course-nav-drawer-number {
		flex-shrink: 0;
		display: flex;
		align-items: center;
		justify-content: center;
		width: 32px;
		height: 32px;
		background-color: var(--color-accent);
		color: white;
		border-radius: 50%;
		font-family: var(--font-display);
		font-size: var(--font-size-small);
		font-weight: 700;
	}

	.course-nav-drawer-info {
		flex: 1;
		min-width: 0;
		padding-top: 4px;
	}

	.course-nav-drawer-name {
		display: block;
		font-family: var(--font-display);
		font-size: var(--font-size-body);
		font-weight: 600;
		color: var(--color-text-primary);
		margin-bottom: 2px;
	}

	.course-nav-drawer-subtitle {
		display: block;
		font-family: var(--font-body);
		font-size: var(--font-size-small);
		color: var(--color-text-secondary);
		line-height: 1.4;
	}
</style>

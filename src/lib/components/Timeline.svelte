<script lang="ts">
	import { parseTimeline } from '$lib/utils/timeline';

	interface Props {
		content: string;
	}

	let { content }: Props = $props();

	const timelineItems = $derived(parseTimeline(content));

	// Mobile drawer state
	let isDrawerOpen = $state(false);

	function toggleDrawer() {
		isDrawerOpen = !isDrawerOpen;
	}

	function closeDrawer() {
		isDrawerOpen = false;
	}

	function handleLinkClick() {
		// Close drawer after navigation on mobile
		isDrawerOpen = false;
	}

	function handleKeydown(event: KeyboardEvent) {
		if (event.key === 'Escape' && isDrawerOpen) {
			closeDrawer();
		}
	}
</script>

<svelte:window on:keydown={handleKeydown} />

{#if timelineItems.length > 0}
	<!-- Desktop: Sticky sidebar -->
	<aside class="timeline timeline-desktop" aria-labelledby="timeline-heading-desktop">
		<h2 id="timeline-heading-desktop" class="timeline-title">Timeline</h2>
		<nav>
			<ol class="timeline-list">
				{#each timelineItems as item (item.anchorId)}
					<li class="timeline-item">
						<a href="#{item.anchorId}" class="timeline-link">
							<span class="timeline-marker">{item.marker}</span>
							<span class="timeline-desc">{item.description}</span>
						</a>
					</li>
				{/each}
			</ol>
		</nav>
	</aside>

	<!-- Mobile: Floating pill + Bottom sheet drawer -->
	<div class="timeline-mobile-container">
		<!-- Floating trigger pill -->
		<button
			class="timeline-pill"
			onclick={toggleDrawer}
			aria-expanded={isDrawerOpen}
			aria-controls="timeline-drawer"
		>
			<svg
				class="timeline-pill-icon"
				viewBox="0 0 24 24"
				fill="none"
				stroke="currentColor"
				stroke-width="2"
				stroke-linecap="round"
				stroke-linejoin="round"
			>
				<circle cx="12" cy="12" r="10"></circle>
				<polyline points="12 6 12 12 16 14"></polyline>
			</svg>
			<span class="timeline-pill-text">Timeline</span>
			<span class="timeline-pill-count">{timelineItems.length}</span>
		</button>

		<!-- Backdrop -->
		{#if isDrawerOpen}
			<div
				class="timeline-backdrop"
				onclick={closeDrawer}
				onkeydown={(e) => e.key === 'Escape' && closeDrawer()}
				role="button"
				tabindex="-1"
				aria-label="Close timeline"
			></div>
		{/if}

		<!-- Bottom sheet drawer -->
		<div
			id="timeline-drawer"
			class="timeline-drawer"
			class:timeline-drawer-open={isDrawerOpen}
			aria-hidden={!isDrawerOpen}
		>
			<div class="timeline-drawer-header">
				<h2 class="timeline-drawer-title">Timeline</h2>
				<button class="timeline-drawer-close" onclick={closeDrawer} aria-label="Close timeline">
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
			<nav class="timeline-drawer-content">
				<ol class="timeline-drawer-list">
					{#each timelineItems as item, index (item.anchorId)}
						<li class="timeline-drawer-item">
							<a href="#{item.anchorId}" class="timeline-drawer-link" onclick={handleLinkClick}>
								<span class="timeline-drawer-step">{index + 1}</span>
								<div class="timeline-drawer-info">
									<span class="timeline-drawer-marker">{item.marker}</span>
									<span class="timeline-drawer-desc">{item.description}</span>
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
	/* ===== DESKTOP TIMELINE (Sticky Sidebar) ===== */
	.timeline-desktop {
		display: none;
		background-color: var(--color-highlight);
		border-left: 3px solid var(--color-accent);
		border-radius: var(--radius-md);
		padding: var(--spacing-lg);
	}

	@media (min-width: 768px) {
		.timeline-desktop {
			display: block;
			position: sticky;
			top: var(--spacing-xl);
			align-self: start;
		}
	}

	.timeline-title {
		margin: 0 0 var(--spacing-md);
		font-family: var(--font-body);
		font-size: var(--font-size-small);
		font-weight: 600;
		text-transform: uppercase;
		letter-spacing: 0.05em;
		color: var(--color-text-secondary);
	}

	.timeline-list {
		margin: 0;
		padding: 0;
		list-style: none;
	}

	.timeline-item + .timeline-item {
		margin-top: var(--spacing-xs);
	}

	.timeline-link {
		display: block;
		padding: var(--spacing-sm) var(--spacing-md);
		text-decoration: none;
		border-radius: var(--radius-sm);
		transition: background-color var(--transition-fast);
	}

	.timeline-link:hover {
		background-color: rgba(0, 0, 0, 0.05);
	}

	:global([data-theme='dark']) .timeline-link:hover {
		background-color: rgba(255, 255, 255, 0.05);
	}

	.timeline-marker {
		display: block;
		font-family: var(--font-display);
		font-size: var(--font-size-body);
		font-weight: 600;
		color: var(--color-accent);
		margin-bottom: 2px;
	}

	.timeline-desc {
		display: block;
		font-family: var(--font-body);
		font-size: var(--font-size-small);
		color: var(--color-text-secondary);
		line-height: 1.4;
	}

	/* ===== MOBILE TIMELINE (Floating Pill + Bottom Sheet) ===== */
	.timeline-mobile-container {
		display: block;
	}

	@media (min-width: 768px) {
		.timeline-mobile-container {
			display: none;
		}
	}

	/* Floating pill button */
	.timeline-pill {
		position: fixed;
		bottom: var(--spacing-xl);
		right: var(--spacing-lg);
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

	.timeline-pill:hover {
		transform: translateY(-2px);
		box-shadow:
			0 6px 16px rgba(0, 0, 0, 0.2),
			0 3px 6px rgba(0, 0, 0, 0.12);
	}

	.timeline-pill:active {
		transform: translateY(0);
	}

	.timeline-pill-icon {
		width: 18px;
		height: 18px;
	}

	.timeline-pill-text {
		letter-spacing: 0.02em;
	}

	.timeline-pill-count {
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
	.timeline-backdrop {
		position: fixed;
		inset: 0;
		z-index: 200;
		background-color: rgba(0, 0, 0, 0.4);
		animation: fadeIn 0.2s ease;
	}

	:global([data-theme='dark']) .timeline-backdrop {
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
	.timeline-drawer {
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

	.timeline-drawer-open {
		transform: translateY(0);
	}

	.timeline-drawer-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: var(--spacing-lg) var(--spacing-xl);
		border-bottom: 1px solid var(--color-border);
	}

	.timeline-drawer-header::before {
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

	.timeline-drawer-title {
		margin: 0;
		font-family: var(--font-display);
		font-size: var(--font-size-title);
		font-weight: 600;
		color: var(--color-text-primary);
	}

	.timeline-drawer-close {
		display: flex;
		align-items: center;
		justify-content: center;
		width: 36px;
		height: 36px;
		background-color: var(--color-highlight);
		border: none;
		border-radius: 50%;
		color: var(--color-text-secondary);
		cursor: pointer;
		transition: background-color var(--transition-fast);
	}

	.timeline-drawer-close:hover {
		background-color: var(--color-border);
	}

	.timeline-drawer-close svg {
		width: 18px;
		height: 18px;
	}

	.timeline-drawer-content {
		padding: var(--spacing-lg) var(--spacing-xl) var(--spacing-2xl);
		overflow-y: auto;
		max-height: calc(70vh - 70px);
	}

	.timeline-drawer-list {
		margin: 0;
		padding: 0;
		list-style: none;
	}

	.timeline-drawer-item {
		position: relative;
	}

	/* Vertical timeline connector line */
	.timeline-drawer-item::before {
		content: '';
		position: absolute;
		left: 15px;
		top: 36px;
		bottom: 0;
		width: 2px;
		background-color: var(--color-border);
	}

	.timeline-drawer-item:last-child::before {
		display: none;
	}

	.timeline-drawer-link {
		display: flex;
		align-items: flex-start;
		gap: var(--spacing-md);
		padding: var(--spacing-md) 0;
		text-decoration: none;
		transition: opacity var(--transition-fast);
	}

	.timeline-drawer-link:active {
		opacity: 0.7;
	}

	.timeline-drawer-step {
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

	.timeline-drawer-info {
		flex: 1;
		min-width: 0;
		padding-top: 4px;
	}

	.timeline-drawer-marker {
		display: block;
		font-family: var(--font-display);
		font-size: var(--font-size-body);
		font-weight: 600;
		color: var(--color-text-primary);
		margin-bottom: 2px;
	}

	.timeline-drawer-desc {
		display: block;
		font-family: var(--font-body);
		font-size: var(--font-size-small);
		color: var(--color-text-secondary);
		line-height: 1.4;
	}
</style>

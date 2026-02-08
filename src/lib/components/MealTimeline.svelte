<script lang="ts">
	import type { MealSnapshot } from '$lib/types';

	let { snapshot }: { snapshot: MealSnapshot } = $props();

	// Compute default expanded markers (all expanded)
	const defaultExpandedMarkers = Object.fromEntries(
		snapshot.timeline_markers.map((m) => [m, true])
	);

	// Track expanded state - all markers expanded by default, all components collapsed
	let expandedMarkers = $state.raw<Record<string, boolean>>(defaultExpandedMarkers);
	let expandedComponents = $state.raw<Record<string, boolean>>({});

	function toggleMarker(event: MouseEvent, marker: string) {
		event.stopPropagation();
		expandedMarkers = { ...expandedMarkers, [marker]: !expandedMarkers[marker] };
	}

	function toggleComponent(event: MouseEvent, key: string) {
		event.stopPropagation();
		expandedComponents = { ...expandedComponents, [key]: !expandedComponents[key] };
	}

	/**
	 * Split step into title and body
	 * Title is the first sentence (ending in . ! or ?)
	 */
	function parseStep(step: string): { title: string; body: string } {
		// Find first sentence ending
		const match = step.match(/^(.+?[.!?])\s*(.*)$/s);
		if (match) {
			return { title: match[1], body: match[2] };
		}
		// No sentence ending found - treat entire text as title
		return { title: step, body: '' };
	}

	function getComponentsForMarker(marker: string) {
		const components: Array<{
			recipe: { title: string; slug: string; is_deleted: boolean };
			component: string;
			steps: string[];
		}> = [];

		for (const recipe of snapshot.recipes) {
			if (recipe.is_deleted) continue;

			const markerData = recipe.timeline?.[marker];
			if (!markerData) continue;

			for (const [component, steps] of Object.entries(markerData)) {
				components.push({
					recipe: { title: recipe.title, slug: recipe.slug, is_deleted: recipe.is_deleted },
					component,
					steps
				});
			}
		}

		return components;
	}
</script>

<div class="meal-timeline">
	<h2>Timeline</h2>

	{#if snapshot.timeline_markers.length === 0}
		<p class="empty">No timeline information available</p>
	{:else}
		{#each snapshot.timeline_markers as marker (marker)}
			{@const components = getComponentsForMarker(marker)}
			{#if components.length > 0}
				<section class="marker-section">
					<button class="marker-header" onclick={(e) => toggleMarker(e, marker)}>
						<span class="marker-name">{marker}</span>
						<span class="chevron" class:expanded={expandedMarkers[marker]}>&#9660;</span>
					</button>

					{#if expandedMarkers[marker]}
						<div class="marker-content">
							{#each components as { recipe, component, steps }, idx}
								{@const key = `${marker}-${recipe.slug}-${component}-${idx}`}
								<div class="component-block">
									<button class="component-header" onclick={(e) => toggleComponent(e, key)}>
										<span class="component-name">{recipe.title} — {component}</span>
										<span class="chevron small" class:expanded={expandedComponents[key]}
											>&#9660;</span
										>
									</button>

									{#if expandedComponents[key]}
										<ul class="steps">
											{#each steps as step}
												{@const parsed = parseStep(step)}
												<li>
													<strong>{parsed.title}</strong>
													{#if parsed.body}
														{' '}{parsed.body}
													{/if}
												</li>
											{/each}
										</ul>
									{/if}
								</div>
							{/each}
						</div>
					{/if}
				</section>
			{/if}
		{/each}
	{/if}
</div>

<style>
	.meal-timeline h2 {
		font-family: var(--font-display);
		font-size: 1.25rem;
		margin: 0 0 var(--spacing-lg);
	}

	.empty {
		color: var(--color-text-tertiary);
		font-style: italic;
	}

	.marker-section {
		border-left: 3px solid var(--color-accent);
		margin-bottom: var(--spacing-lg);
	}

	.marker-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		width: 100%;
		padding: var(--spacing-md) var(--spacing-lg);
		background: var(--color-surface);
		border: none;
		cursor: pointer;
		text-align: left;
		font-family: var(--font-display);
		font-size: 1.125rem;
		font-weight: 500;
		color: var(--color-text-primary);
		min-height: 44px;
	}

	.marker-header:hover {
		background: var(--color-bg);
	}

	.chevron {
		font-size: 0.75rem;
		color: var(--color-text-tertiary);
		transition: transform 0.2s;
	}

	.chevron.expanded {
		transform: rotate(180deg);
	}

	.chevron.small {
		font-size: 0.625rem;
	}

	.marker-content {
		padding-left: var(--spacing-lg);
	}

	.component-block {
		border-bottom: 1px solid var(--color-border);
	}

	.component-block:last-child {
		border-bottom: none;
	}

	.component-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		width: 100%;
		padding: var(--spacing-sm) var(--spacing-md);
		background: none;
		border: none;
		cursor: pointer;
		text-align: left;
		font-family: var(--font-body);
		font-size: 0.875rem;
		font-weight: 500;
		color: var(--color-text-secondary);
		min-height: 44px;
	}

	.component-header:hover {
		color: var(--color-text-primary);
	}

	.steps {
		margin: 0;
		padding: var(--spacing-sm) var(--spacing-md) var(--spacing-md) var(--spacing-2xl);
		list-style: disc;
	}

	.steps li {
		font-size: 0.875rem;
		line-height: 1.6;
		color: var(--color-text-primary);
		padding: var(--spacing-xs) 0;
	}
</style>

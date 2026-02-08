<!--
	CookModeStep - Single step display for Cook Mode

	Large, readable step display designed for kitchen use.
	Phone propped up, readable at arm's length.
-->
<script lang="ts">
	import type { CookModeStep } from '$lib/types';
	import { detectDurations, formatDurationShort } from '$lib/utils/durations';
	import { createTimer } from '$lib/stores/timers';

	interface Props {
		step: CookModeStep;
		scale?: number;
	}

	let { step, scale = 1 }: Props = $props();

	const durations = $derived(detectDurations(step.body, step.title));

	function handleStartTimer(label: string, seconds: number) {
		createTimer(label, seconds, step.number);
	}
</script>

<div class="cook-mode-step" style="--text-scale: {scale}">
	<div class="step-number-bg" aria-hidden="true">
		{step.number}
	</div>

	<h2 class="step-title">{step.title}</h2>

	{#if step.body}
		<p class="step-body">{step.body}</p>
	{/if}

	{#if durations.length > 0}
		<div class="timer-suggestions" style="--text-scale: {scale}">
			{#each durations as d}
				<button
					class="timer-suggest-btn"
					onclick={() => handleStartTimer(d.label, d.seconds)}
					aria-label="Start {d.label} timer for {formatDurationShort(d.seconds)}"
				>
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
						<circle cx="12" cy="12" r="10"></circle>
						<polyline points="12 6 12 12 16 14"></polyline>
					</svg>
					{d.label} {formatDurationShort(d.seconds)}
				</button>
			{/each}
		</div>
	{/if}
</div>

<style>
	.cook-mode-step {
		position: relative;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		text-align: center;
		max-width: 600px;
		margin: 0 auto;
		padding: var(--spacing-xl);
		min-height: 200px;
	}

	/* Large faded step number in background */
	.step-number-bg {
		position: absolute;
		top: 50%;
		left: 50%;
		transform: translate(-50%, -50%);
		font-family: var(--font-display);
		font-size: 180px;
		font-weight: 600;
		color: var(--color-accent);
		opacity: 0.15;
		line-height: 1;
		pointer-events: none;
		user-select: none;
		z-index: 0;
	}

	@media (min-width: 768px) {
		.step-number-bg {
			font-size: 240px;
		}
	}

	.step-title {
		position: relative;
		z-index: 1;
		margin: 0 0 var(--spacing-lg);
		font-family: var(--font-display);
		font-size: calc(var(--font-size-title) * var(--text-scale, 1));
		font-weight: 600;
		color: var(--color-text-primary);
		line-height: 1.3;
		transition: font-size var(--transition-fast);
	}

	@media (min-width: 768px) {
		.step-title {
			font-size: calc(28px * var(--text-scale, 1));
		}
	}

	.step-body {
		position: relative;
		z-index: 1;
		margin: 0;
		font-family: var(--font-body);
		font-size: calc(var(--font-size-body) * var(--text-scale, 1));
		color: var(--color-text-secondary);
		line-height: 1.7;
		max-width: 500px;
		transition: font-size var(--transition-fast);
	}

	@media (min-width: 768px) {
		.step-body {
			font-size: calc(18px * var(--text-scale, 1));
		}
	}

	.timer-suggestions {
		position: relative;
		z-index: 1;
		display: flex;
		gap: var(--spacing-sm);
		flex-wrap: wrap;
		justify-content: center;
		margin-top: var(--spacing-xl);
	}

	.timer-suggest-btn {
		display: inline-flex;
		align-items: center;
		gap: var(--spacing-xs);
		min-height: 40px;
		padding: var(--spacing-sm) var(--spacing-lg);
		font-family: var(--font-body);
		font-size: calc(var(--font-size-meta) * var(--text-scale, 1));
		font-weight: 500;
		color: var(--color-accent);
		background-color: transparent;
		border: 1px solid var(--color-accent);
		border-radius: var(--radius-sm);
		cursor: pointer;
		transition: all var(--transition-fast);
	}

	.timer-suggest-btn:hover {
		background-color: var(--color-highlight);
	}

	.timer-suggest-btn:active {
		background-color: var(--color-accent);
		color: white;
	}
</style>

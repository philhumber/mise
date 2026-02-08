<!--
	CookMode - Full-screen overlay for step-by-step cooking

	Main shell that transforms the recipe detail page into a focused
	cooking interface. Foundation for MISE-67 (navigation) and MISE-68 (timers).
-->
<script lang="ts">
	import { onMount, onDestroy } from 'svelte';
	import CookModeHeader from './CookModeHeader.svelte';
	import CookModeStep from './CookModeStep.svelte';
	import CookModeTimerArea from './CookModeTimerArea.svelte';
	import {
		subscribeCookMode,
		exitCookMode,
		nextStep,
		prevStep,
		increaseTextSize,
		decreaseTextSize
	} from '$lib/stores/cookMode';
	import { subscribeTimers } from '$lib/stores/timers';
	import type { CookModeStep as CookModeStepType, Timer } from '$lib/types';

	const TEXT_SIZES = [0.85, 1, 1.2, 1.4, 1.6];

	let isActive = $state(false);
	let steps = $state<CookModeStepType[]>([]);
	let currentStepIndex = $state(0);
	let textSizeIndex = $state(1);
	let timers = $state<Timer[]>([]);

	let unsubCookMode: (() => void) | null = null;
	let unsubTimers: (() => void) | null = null;

	onMount(() => {
		unsubCookMode = subscribeCookMode((state) => {
			isActive = state.isActive;
			steps = state.steps;
			currentStepIndex = state.currentStepIndex;
			textSizeIndex = state.textSizeIndex;
		});
		unsubTimers = subscribeTimers((t) => {
			timers = t;
		});
	});

	onDestroy(() => {
		if (unsubCookMode) unsubCookMode();
		if (unsubTimers) unsubTimers();
	});

	const currentStep = $derived(
		isActive && steps.length > 0 ? steps[currentStepIndex] : null
	);

	const isFirstStep = $derived(currentStepIndex === 0);
	const isLastStep = $derived(currentStepIndex >= steps.length - 1);
	const canIncrease = $derived(textSizeIndex < TEXT_SIZES.length - 1);
	const canDecrease = $derived(textSizeIndex > 0);
	const textScale = $derived(TEXT_SIZES[textSizeIndex]);
	const runningTimerCount = $derived(timers.filter((t) => t.state === 'running').length);

	function handleKeydown(event: KeyboardEvent) {
		if (!isActive) return;

		if (event.key === 'Escape') {
			exitCookMode();
		} else if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
			nextStep();
		} else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
			prevStep();
		}
	}

	function handleExit() {
		exitCookMode();
	}
</script>

<svelte:window onkeydown={handleKeydown} />

{#if isActive && currentStep}
	<div
		class="cook-mode-overlay texture-subtle"
		role="dialog"
		aria-modal="true"
		aria-label="Cook mode"
	>
		<CookModeHeader
			marker={currentStep.marker}
			currentStep={currentStepIndex + 1}
			totalSteps={steps.length}
			canIncreaseText={canIncrease}
			canDecreaseText={canDecrease}
			{runningTimerCount}
			onExit={handleExit}
			onIncreaseText={increaseTextSize}
			onDecreaseText={decreaseTextSize}
		/>

		<main class="cook-mode-content">
			<CookModeStep step={currentStep} scale={textScale} />
		</main>

		<CookModeTimerArea currentStepNumber={currentStepIndex + 1} />

		<!-- Step navigation -->
		<nav class="cook-mode-nav" aria-label="Step navigation">
			<button
				class="nav-btn"
				onclick={prevStep}
				disabled={isFirstStep}
				aria-label="Previous step"
			>
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<polyline points="15 18 9 12 15 6"></polyline>
				</svg>
				<span class="nav-btn-label">Previous</span>
			</button>

			<button
				class="nav-btn nav-btn-primary"
				onclick={nextStep}
				disabled={isLastStep}
				aria-label="Next step"
			>
				<span class="nav-btn-label">Next</span>
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<polyline points="9 18 15 12 9 6"></polyline>
				</svg>
			</button>
		</nav>
	</div>
{/if}

<style>
	.cook-mode-overlay {
		position: fixed;
		inset: 0;
		z-index: 1000;
		display: flex;
		flex-direction: column;
		background-color: var(--color-bg);
		animation: slideUp 0.3s cubic-bezier(0.32, 0.72, 0, 1);
	}

	@keyframes slideUp {
		from {
			transform: translateY(100%);
		}
		to {
			transform: translateY(0);
		}
	}

	@media (prefers-reduced-motion: reduce) {
		.cook-mode-overlay {
			animation: none;
		}
	}

	.cook-mode-content {
		flex: 1;
		overflow-y: auto;
		display: flex;
		align-items: center;
		justify-content: center;
		padding: var(--spacing-xl);
		padding-left: max(var(--spacing-xl), env(safe-area-inset-left));
		padding-right: max(var(--spacing-xl), env(safe-area-inset-right));
	}

	/* Step navigation */
	.cook-mode-nav {
		flex-shrink: 0;
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: var(--spacing-lg);
		min-height: 80px;
		padding: var(--spacing-lg) var(--spacing-xl);
		padding-bottom: max(var(--spacing-lg), env(safe-area-inset-bottom));
		padding-left: max(var(--spacing-xl), env(safe-area-inset-left));
		padding-right: max(var(--spacing-xl), env(safe-area-inset-right));
		background-color: var(--color-surface);
		border-top: 1px solid var(--color-border);
	}

	.nav-btn {
		display: flex;
		align-items: center;
		gap: var(--spacing-sm);
		min-height: 48px;
		padding: var(--spacing-md) var(--spacing-xl);
		font-family: var(--font-body);
		font-size: var(--font-size-body);
		font-weight: 500;
		color: var(--color-text-secondary);
		background-color: transparent;
		border: 1px solid var(--color-border);
		border-radius: var(--radius-md);
		cursor: pointer;
		transition: all var(--transition-fast);
	}

	.nav-btn:hover:not(:disabled) {
		color: var(--color-text-primary);
		border-color: var(--color-border-strong);
	}

	.nav-btn:disabled {
		opacity: 0.3;
		cursor: not-allowed;
	}

	.nav-btn-primary {
		color: white;
		background-color: var(--color-accent);
		border-color: var(--color-accent);
	}

	.nav-btn-primary:hover:not(:disabled) {
		background-color: var(--color-accent-muted);
		border-color: var(--color-accent-muted);
		color: white;
	}

	.nav-btn-label {
		display: none;
	}

	@media (min-width: 480px) {
		.nav-btn-label {
			display: inline;
		}
	}
</style>

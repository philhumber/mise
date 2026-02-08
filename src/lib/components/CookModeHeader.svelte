<!--
	CookModeHeader - Top bar for Cook Mode

	Exit button, marker badge, step counter, progress bar, and text size controls.
-->
<script lang="ts">
	interface Props {
		marker: string;
		currentStep: number;
		totalSteps: number;
		canIncreaseText: boolean;
		canDecreaseText: boolean;
		runningTimerCount: number;
		onExit: () => void;
		onIncreaseText: () => void;
		onDecreaseText: () => void;
	}

	let { marker, currentStep, totalSteps, canIncreaseText, canDecreaseText, runningTimerCount, onExit, onIncreaseText, onDecreaseText }: Props = $props();

	const progress = $derived(totalSteps > 0 ? ((currentStep) / totalSteps) * 100 : 0);
</script>

<header class="cook-mode-header">
	<div class="header-content">
		<!-- Exit button -->
		<button class="exit-btn" onclick={onExit} aria-label="Exit cook mode">
			<svg
				width="20"
				height="20"
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

		<!-- Center: marker and step counter -->
		<div class="header-center">
			<div class="marker-row">
				<span class="marker-badge">{marker}</span>
				{#if runningTimerCount > 0}
					<span class="timer-badge" aria-label="{runningTimerCount} timer{runningTimerCount > 1 ? 's' : ''} running">
						<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
							<circle cx="12" cy="12" r="10"></circle>
							<polyline points="12 6 12 12 16 14"></polyline>
						</svg>
						{runningTimerCount}
					</span>
				{/if}
			</div>
			<span class="step-counter">Step {currentStep} of {totalSteps}</span>
		</div>

		<!-- Right: text size controls -->
		<div class="text-size-controls">
			<button
				class="size-btn"
				onclick={onDecreaseText}
				disabled={!canDecreaseText}
				aria-label="Decrease text size"
			>
				<span class="size-icon">A</span>
				<span class="size-minus">−</span>
			</button>
			<button
				class="size-btn"
				onclick={onIncreaseText}
				disabled={!canIncreaseText}
				aria-label="Increase text size"
			>
				<span class="size-icon size-icon-large">A</span>
				<span class="size-plus">+</span>
			</button>
		</div>
	</div>

	<!-- Progress bar -->
	<div class="progress-bar" role="progressbar" aria-valuenow={progress} aria-valuemin={0} aria-valuemax={100}>
		<div class="progress-fill" style="width: {progress}%"></div>
	</div>
</header>

<style>
	.cook-mode-header {
		flex-shrink: 0;
		background-color: var(--color-surface);
		border-bottom: 1px solid var(--color-border);
	}

	.header-content {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: max(var(--spacing-md), env(safe-area-inset-top)) var(--spacing-xl) var(--spacing-md);
		padding-left: max(var(--spacing-xl), env(safe-area-inset-left));
		padding-right: max(var(--spacing-xl), env(safe-area-inset-right));
	}

	.exit-btn {
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
		transition: background-color var(--transition-fast), color var(--transition-fast);
	}

	.exit-btn:hover {
		background-color: var(--color-border);
		color: var(--color-text-primary);
	}

	.header-center {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: var(--spacing-xs);
	}

	.marker-badge {
		display: inline-block;
		padding: var(--spacing-xs) var(--spacing-md);
		padding-left: calc(var(--spacing-md) + 3px);
		font-family: var(--font-display);
		font-size: var(--font-size-meta);
		font-weight: 600;
		color: var(--color-accent);
		background-color: var(--color-highlight);
		border-left: 3px solid var(--color-accent);
		border-radius: var(--radius-sm);
	}

	.marker-row {
		display: flex;
		align-items: center;
		gap: var(--spacing-sm);
	}

	.timer-badge {
		display: inline-flex;
		align-items: center;
		gap: var(--spacing-xs);
		padding: 2px var(--spacing-sm);
		font-family: var(--font-body);
		font-size: var(--font-size-small);
		font-weight: 600;
		color: var(--color-accent);
		background-color: var(--color-highlight);
		border-radius: var(--radius-sm);
	}

	.step-counter {
		font-family: var(--font-body);
		font-size: var(--font-size-small);
		color: var(--color-text-tertiary);
	}

	.text-size-controls {
		display: flex;
		gap: var(--spacing-xs);
	}

	.size-btn {
		position: relative;
		display: flex;
		align-items: center;
		justify-content: center;
		width: 40px;
		height: 40px;
		background-color: var(--color-highlight);
		border: none;
		border-radius: var(--radius-sm);
		color: var(--color-text-secondary);
		cursor: pointer;
		transition: background-color var(--transition-fast), color var(--transition-fast);
	}

	.size-btn:hover:not(:disabled) {
		background-color: var(--color-border);
		color: var(--color-text-primary);
	}

	.size-btn:disabled {
		opacity: 0.3;
		cursor: not-allowed;
	}

	.size-icon {
		font-family: var(--font-display);
		font-size: 14px;
		font-weight: 600;
	}

	.size-icon-large {
		font-size: 18px;
	}

	.size-minus,
	.size-plus {
		position: absolute;
		bottom: 4px;
		right: 4px;
		font-size: 10px;
		font-weight: 700;
		line-height: 1;
	}

	.progress-bar {
		height: 3px;
		background-color: var(--color-border);
	}

	.progress-fill {
		height: 100%;
		background-color: var(--color-accent);
		transition: width var(--transition-fast);
	}
</style>

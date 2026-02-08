<!--
	TimerCard - Individual timer display for Cook Mode

	Shows countdown, label, step context, and pause/resume/dismiss controls.
	Visual states: running (accent border), paused (muted), completed (pulsing).
-->
<script lang="ts">
	import type { Timer } from '$lib/types';
	import { formatTime } from '$lib/utils/durations';

	interface Props {
		timer: Timer;
		muted: boolean;
		onPause: (id: string) => void;
		onResume: (id: string) => void;
		onDismiss: (id: string) => void;
	}

	let { timer, muted, onPause, onResume, onDismiss }: Props = $props();

	const isCompleted = $derived(timer.state === 'completed');
	const isPaused = $derived(timer.state === 'paused');
	const isRunning = $derived(timer.state === 'running');
</script>

<div
	class="timer-card"
	class:timer-running={isRunning}
	class:timer-paused={isPaused}
	class:timer-completed={isCompleted}
	role="timer"
	aria-label="{timer.label}: {isCompleted ? 'complete' : formatTime(timer.remainingSeconds)} remaining"
>
	<div class="timer-info">
		<span class="timer-label">{timer.label}</span>
		<span class="timer-step">Step {timer.stepNumber}</span>
	</div>

	<div class="timer-time-col">
		<div class="timer-time" aria-live="polite">
			{#if isCompleted}
				<span class="timer-done">Done</span>
			{:else}
				{formatTime(timer.remainingSeconds)}
			{/if}
		</div>
		{#if muted && isRunning}
			<span class="timer-muted-hint">Sound off</span>
		{/if}
	</div>

	<div class="timer-actions">
		{#if isRunning}
			<button
				class="timer-action-btn"
				onclick={() => onPause(timer.id)}
				aria-label="Pause {timer.label}"
			>
				<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
					<rect x="6" y="4" width="4" height="16" rx="1"></rect>
					<rect x="14" y="4" width="4" height="16" rx="1"></rect>
				</svg>
			</button>
		{:else if isPaused}
			<button
				class="timer-action-btn"
				onclick={() => onResume(timer.id)}
				aria-label="Resume {timer.label}"
			>
				<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
					<polygon points="5 3 19 12 5 21 5 3"></polygon>
				</svg>
			</button>
		{/if}

		<button
			class="timer-action-btn timer-dismiss-btn"
			onclick={() => onDismiss(timer.id)}
			aria-label="Dismiss {timer.label}"
		>
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
				<line x1="18" y1="6" x2="6" y2="18"></line>
				<line x1="6" y1="6" x2="18" y2="18"></line>
			</svg>
		</button>
	</div>
</div>

<style>
	.timer-card {
		display: flex;
		align-items: center;
		gap: var(--spacing-md);
		padding: var(--spacing-md) var(--spacing-lg);
		background-color: var(--color-surface);
		border: 1px solid var(--color-border);
		border-radius: var(--radius-md);
		transition: all var(--transition-fast);
	}

	.timer-running {
		border-left: 3px solid var(--color-accent);
	}

	.timer-paused {
		opacity: 0.7;
		border-left: 3px solid var(--color-text-tertiary);
	}

	.timer-completed {
		border-left: 3px solid var(--color-accent);
		background-color: var(--color-highlight);
		animation: pulse 1.5s ease-in-out infinite;
	}

	@keyframes pulse {
		0%, 100% { opacity: 1; }
		50% { opacity: 0.7; }
	}

	@media (prefers-reduced-motion: reduce) {
		.timer-completed {
			animation: none;
		}
	}

	.timer-info {
		flex: 1;
		min-width: 0;
		display: flex;
		flex-direction: column;
		gap: var(--spacing-xs);
	}

	.timer-label {
		font-family: var(--font-display);
		font-size: var(--font-size-body);
		font-weight: 600;
		color: var(--color-text-primary);
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
	}

	.timer-step {
		font-family: var(--font-body);
		font-size: var(--font-size-small);
		color: var(--color-text-tertiary);
	}

	.timer-time-col {
		display: flex;
		flex-direction: column;
		align-items: flex-end;
		gap: 2px;
	}

	.timer-time {
		font-family: var(--font-body);
		font-size: 20px;
		font-weight: 600;
		font-variant-numeric: tabular-nums;
		color: var(--color-text-primary);
		white-space: nowrap;
	}

	.timer-muted-hint {
		font-family: var(--font-body);
		font-size: var(--font-size-small);
		color: var(--color-text-tertiary);
		white-space: nowrap;
	}

	.timer-done {
		color: var(--color-accent);
		font-family: var(--font-display);
		font-weight: 700;
	}

	.timer-actions {
		display: flex;
		gap: var(--spacing-xs);
		flex-shrink: 0;
	}

	.timer-action-btn {
		display: flex;
		align-items: center;
		justify-content: center;
		width: 44px;
		height: 44px;
		background-color: transparent;
		border: 1px solid var(--color-border);
		border-radius: var(--radius-sm);
		color: var(--color-text-secondary);
		cursor: pointer;
		transition: all var(--transition-fast);
	}

	.timer-action-btn:hover {
		background-color: var(--color-highlight);
		color: var(--color-text-primary);
		border-color: var(--color-border-strong);
	}

	.timer-dismiss-btn {
		border-color: transparent;
	}

	.timer-dismiss-btn:hover {
		border-color: var(--color-border);
	}
</style>

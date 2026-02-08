<!--
	CookModeTimerArea - Active timers and manual timer creation

	Displays running/paused/completed timers and an inline form
	for creating custom timers. Positioned between step content and nav.
-->
<script lang="ts">
	import { onMount, onDestroy } from 'svelte';
	import TimerCard from './TimerCard.svelte';
	import {
		subscribeTimers,
		pauseTimer,
		resumeTimer,
		dismissTimer,
		createTimer
	} from '$lib/stores/timers';
	import { parseDurationInput } from '$lib/utils/durations';
	import { subscribeMuted } from '$lib/utils/notifications';
	import type { Timer } from '$lib/types';

	interface Props {
		currentStepNumber: number;
	}

	let { currentStepNumber }: Props = $props();

	let timers = $state<Timer[]>([]);
	let isMuted = $state(false);
	let showAddForm = $state(false);
	let customMinutes = $state('');
	let customLabel = $state('');

	let unsubTimers: (() => void) | null = null;
	let unsubMuted: (() => void) | null = null;

	onMount(() => {
		unsubTimers = subscribeTimers((t) => {
			timers = t;
		});
		unsubMuted = subscribeMuted((m) => { isMuted = m; });
	});

	onDestroy(() => {
		if (unsubTimers) unsubTimers();
		if (unsubMuted) unsubMuted();
	});

	const hasTimers = $derived(timers.length > 0);
	const sortedTimers = $derived(
		[...timers].sort((a, b) => {
			const order = { running: 0, paused: 1, completed: 2 };
			return order[a.state] - order[b.state];
		})
	);

	function handleAddCustomTimer() {
		const seconds = parseDurationInput(customMinutes);
		if (!seconds) return;
		const label = customLabel.trim() || 'Timer';
		createTimer(label, seconds, currentStepNumber);
		customMinutes = '';
		customLabel = '';
		showAddForm = false;
	}

	function handleAddKeydown(event: KeyboardEvent) {
		if (event.key === 'Enter') {
			event.preventDefault();
			handleAddCustomTimer();
		} else if (event.key === 'Escape') {
			showAddForm = false;
		}
	}

	function handleQuickPreset(minutes: number) {
		createTimer('Timer', minutes * 60, currentStepNumber);
	}
</script>

<div class="cook-mode-timer-area" class:has-content={hasTimers || showAddForm} aria-label="Timers">
	{#if hasTimers}
		<div class="timer-list">
			{#each sortedTimers as timer (timer.id)}
				<TimerCard
					{timer}
					muted={isMuted}
					onPause={pauseTimer}
					onResume={resumeTimer}
					onDismiss={dismissTimer}
				/>
			{/each}
		</div>
	{/if}

	{#if showAddForm}
		<div class="add-timer-form">
			<div class="add-timer-inputs">
				<input
					type="text"
					class="timer-input"
					bind:value={customLabel}
					placeholder="Label"
					onkeydown={handleAddKeydown}
				/>
				<input
					type="text"
					class="timer-input timer-input-duration"
					bind:value={customMinutes}
					placeholder="e.g. 10m, 1h30m"
					onkeydown={handleAddKeydown}
				/>
				<button class="add-timer-submit" onclick={handleAddCustomTimer}>
					Start
				</button>
			</div>
			<div class="quick-presets">
				{#each [1, 2, 5, 10, 15, 30, 60] as mins}
					<button class="preset-btn" onclick={() => handleQuickPreset(mins)}>
						{mins >= 60 ? `${mins / 60}h` : `${mins}m`}
					</button>
				{/each}
			</div>
		</div>
	{/if}

	<button
		class="toggle-add-btn"
		onclick={() => { showAddForm = !showAddForm; }}
		aria-label={showAddForm ? 'Close add timer' : 'Add custom timer'}
	>
		{#if showAddForm}
			Cancel
		{:else}
			<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
				<line x1="12" y1="5" x2="12" y2="19"></line>
				<line x1="5" y1="12" x2="19" y2="12"></line>
			</svg>
			Add Timer
		{/if}
	</button>
</div>

<style>
	.cook-mode-timer-area {
		flex-shrink: 0;
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: var(--spacing-sm);
		padding: var(--spacing-sm) var(--spacing-xl);
		padding-left: max(var(--spacing-xl), env(safe-area-inset-left));
		padding-right: max(var(--spacing-xl), env(safe-area-inset-right));
	}

	.cook-mode-timer-area.has-content {
		padding: var(--spacing-md) var(--spacing-xl);
		padding-left: max(var(--spacing-xl), env(safe-area-inset-left));
		padding-right: max(var(--spacing-xl), env(safe-area-inset-right));
		background-color: var(--color-surface);
		border-top: 1px solid var(--color-border);
		align-items: stretch;
	}

	.timer-list {
		display: flex;
		flex-direction: column;
		gap: var(--spacing-sm);
		max-height: 200px;
		overflow-y: auto;
	}

	.add-timer-form {
		display: flex;
		flex-direction: column;
		gap: var(--spacing-sm);
	}

	.add-timer-inputs {
		display: flex;
		gap: var(--spacing-sm);
	}

	.timer-input {
		flex: 1;
		min-width: 0;
		height: 44px;
		padding: 0 var(--spacing-md);
		font-family: var(--font-body);
		font-size: var(--font-size-meta);
		color: var(--color-text-primary);
		background-color: var(--color-bg);
		border: 1px solid var(--color-border);
		border-radius: var(--radius-sm);
		transition: border-color var(--transition-fast);
	}

	.timer-input:focus {
		border-color: var(--color-accent);
		outline: none;
	}

	.timer-input::placeholder {
		color: var(--color-text-tertiary);
	}

	.timer-input-duration {
		max-width: 140px;
	}

	.add-timer-submit {
		height: 44px;
		padding: 0 var(--spacing-lg);
		font-family: var(--font-body);
		font-size: var(--font-size-meta);
		font-weight: 600;
		color: white;
		background-color: var(--color-accent);
		border: none;
		border-radius: var(--radius-sm);
		cursor: pointer;
		transition: background-color var(--transition-fast);
		white-space: nowrap;
	}

	.add-timer-submit:hover {
		background-color: var(--color-accent-muted);
	}

	.quick-presets {
		display: flex;
		gap: var(--spacing-xs);
		flex-wrap: wrap;
	}

	.preset-btn {
		height: 44px;
		padding: 0 var(--spacing-md);
		font-family: var(--font-body);
		font-size: var(--font-size-small);
		font-weight: 500;
		color: var(--color-text-secondary);
		background-color: transparent;
		border: 1px solid var(--color-border);
		border-radius: var(--radius-sm);
		cursor: pointer;
		transition: all var(--transition-fast);
	}

	.preset-btn:hover {
		color: var(--color-accent);
		border-color: var(--color-accent);
		background-color: var(--color-highlight);
	}

	.toggle-add-btn {
		display: flex;
		align-items: center;
		justify-content: center;
		gap: var(--spacing-xs);
		height: 44px;
		padding: 0 var(--spacing-lg);
		font-family: var(--font-body);
		font-size: var(--font-size-small);
		font-weight: 500;
		color: var(--color-text-tertiary);
		background-color: transparent;
		border: 1px dashed var(--color-border);
		border-radius: var(--radius-sm);
		cursor: pointer;
		transition: all var(--transition-fast);
		align-self: center;
	}

	.toggle-add-btn:hover {
		color: var(--color-text-secondary);
		border-color: var(--color-border-strong);
	}
</style>

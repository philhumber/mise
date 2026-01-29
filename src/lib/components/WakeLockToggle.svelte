<script lang="ts">
	import { onMount } from 'svelte';
	import {
		initWakeLock,
		getWakeLockEnabled,
		setWakeLockEnabled,
		getWakeLockActive,
		isWakeLockSupported,
		requestWakeLock,
		releaseWakeLock
	} from '$lib/stores/wakeLock';

	let { visible = false }: { visible?: boolean } = $props();

	let enabled = $state(false);
	let active = $state(false);
	let supported = $state(false);
	let isToggling = $state(false);
	let showHint = $state(false);

	onMount(() => {
		initWakeLock(); // Ensure initialized (child mounts before parent layout)
		enabled = getWakeLockEnabled();
		active = getWakeLockActive();
		supported = isWakeLockSupported();
	});

	async function handleToggle() {
		if (isToggling) return;
		isToggling = true;

		try {
			enabled = !enabled;
			setWakeLockEnabled(enabled);

			if (enabled) {
				const success = await requestWakeLock();
				if (!success && enabled) {
					showHint = true;
					setTimeout(() => {
						showHint = false;
					}, 3000);
				}
			} else {
				releaseWakeLock();
			}

			active = getWakeLockActive();
		} finally {
			isToggling = false;
		}
	}
</script>

<div class="wake-lock-wrapper" class:wake-lock-wrapper--visible={visible}>
	<button
		class="wake-lock-toggle"
		class:active
		onclick={handleToggle}
		disabled={!supported || isToggling}
		aria-label={active ? 'Disable keep screen awake' : 'Enable keep screen awake'}
		aria-pressed={active}
		title={active
			? 'Screen will stay awake (uses more battery)'
			: 'Keep screen awake while viewing recipes'}
	>
		{#if active}
			<!-- Lightbulb ON (filled) -->
			<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" stroke="none">
				<path
					d="M12 2C8.13 2 5 5.13 5 9c0 2.38 1.19 4.47 3 5.74V17c0 .55.45 1 1 1h6c.55 0 1-.45 1-1v-2.26c1.81-1.27 3-3.36 3-5.74 0-3.87-3.13-7-7-7z"
				/>
				<rect x="9" y="19" width="6" height="2" rx="1" />
			</svg>
		{:else}
			<!-- Lightbulb OFF (outlined) -->
			<svg
				width="20"
				height="20"
				viewBox="0 0 24 24"
				fill="none"
				stroke="currentColor"
				stroke-width="2"
			>
				<path
					d="M12 2C8.13 2 5 5.13 5 9c0 2.38 1.19 4.47 3 5.74V17c0 .55.45 1 1 1h6c.55 0 1-.45 1-1v-2.26c1.81-1.27 3-3.36 3-5.74 0-3.87-3.13-7-7-7z"
				/>
				<rect x="9" y="19" width="6" height="2" rx="1" />
			</svg>
		{/if}
	</button>

	{#if showHint}
		<span class="wake-lock-hint">Tap to keep screen awake</span>
	{/if}
</div>

<style>
	.wake-lock-wrapper {
		display: flex;
		align-items: center;
		position: relative;
		width: 0;
		opacity: 0;
		overflow: hidden;
		transition:
			width var(--transition-fast),
			opacity var(--transition-fast);
	}

	.wake-lock-wrapper--visible {
		width: 44px;
		opacity: 1;
	}

	.wake-lock-toggle {
		display: flex;
		align-items: center;
		justify-content: center;
		width: 44px;
		height: 44px;
		padding: 0;
		flex-shrink: 0;
		color: var(--color-text-secondary);
		background: transparent;
		border: 1px solid var(--color-border);
		border-radius: var(--radius-md);
		cursor: pointer;
		transition: all var(--transition-fast);
	}

	.wake-lock-toggle:hover:not(:disabled) {
		color: var(--color-text-primary);
		border-color: var(--color-border-strong);
	}

	.wake-lock-toggle.active {
		color: var(--color-accent);
		border-color: var(--color-accent);
	}

	.wake-lock-toggle.active:hover:not(:disabled) {
		color: var(--color-accent-muted);
		border-color: var(--color-accent-muted);
	}

	.wake-lock-toggle:disabled {
		opacity: 0.3;
		cursor: not-allowed;
	}

	.wake-lock-hint {
		position: absolute;
		top: 100%;
		right: 0;
		margin-top: var(--spacing-xs);
		padding: var(--spacing-xs) var(--spacing-sm);
		font-family: var(--font-body);
		font-size: var(--font-size-small);
		color: var(--color-text-secondary);
		background: var(--color-surface);
		border: 1px solid var(--color-border);
		border-radius: var(--radius-sm);
		white-space: nowrap;
		z-index: 10;
	}
</style>

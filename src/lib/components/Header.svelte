<script lang="ts">
	import { base } from '$app/paths';
	import { toggleTheme, getTheme } from '$lib/stores/theme';
	import { pageTitle } from '$lib/stores/pageTitle';
	import UploadButton from './UploadButton.svelte';

	let isDark = $state(getTheme() === 'dark');

	function handleToggle() {
		const newTheme = toggleTheme();
		isDark = newTheme === 'dark';
	}
</script>

<header class="header">
	<div class="header-left">
		<!-- Back button (recipe detail) - always rendered, visibility controlled via CSS -->
		<!-- eslint-disable-next-line svelte/no-navigation-without-resolve -- using base for non-parameterized route -->
		<a
			href={base || '/'}
			class="back-link"
			class:back-link--visible={$pageTitle.showBackButton}
			aria-label="Back to recipes"
			aria-hidden={!$pageTitle.showBackButton}
			tabindex={$pageTitle.showBackButton ? 0 : -1}
		>
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="back-icon">
				<path d="m15 18-6-6 6-6"></path>
			</svg>
		</a>

		<!-- Logo (home) - always rendered, visibility controlled via CSS -->
		<!-- eslint-disable-next-line svelte/no-navigation-without-resolve -- using base for non-parameterized route -->
		<a
			href={base || '/'}
			class="logo-block"
			class:logo-block--visible={!$pageTitle.showBackButton}
			aria-hidden={$pageTitle.showBackButton}
			tabindex={$pageTitle.showBackButton ? -1 : 0}
		>
			<span class="logo text-logo">mïse</span>
			<span class="tagline">for serious home cooks</span>
		</a>

		<!-- Title block (recipe detail) -->
		<div class="header-title-block" class:header-title-block--visible={$pageTitle.title}>
			<h1 class="header-title text-display">{$pageTitle.title ?? ''}</h1>
			{#if $pageTitle.subtitle}
				<p class="header-subtitle">{$pageTitle.subtitle}</p>
			{/if}
		</div>
	</div>

	<div class="header-actions">
		<UploadButton />

		<button
			class="theme-toggle"
			onclick={handleToggle}
			aria-label={isDark ? 'Switch to light mode' : 'Switch to dark mode'}
		>
		{#if isDark}
			<svg
				width="20"
				height="20"
				viewBox="0 0 24 24"
				fill="none"
				stroke="currentColor"
				stroke-width="2"
			>
				<circle cx="12" cy="12" r="5" />
				<path
					d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"
				/>
			</svg>
		{:else}
			<svg
				width="20"
				height="20"
				viewBox="0 0 24 24"
				fill="none"
				stroke="currentColor"
				stroke-width="2"
			>
				<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
			</svg>
		{/if}
		</button>
	</div>
</header>

<style>
	.header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: var(--spacing-lg);
		padding: var(--spacing-sm) var(--spacing-xl);
		border-bottom: 1px solid var(--color-border);
	}

	.header-left {
		display: flex;
		align-items: center;
		min-width: 0;
		flex: 1;
		position: relative;
	}

	/* ========================================
	   Back Button (Recipe Detail)
	   ======================================== */

	.back-link {
		display: flex;
		align-items: center;
		justify-content: center;
		width: 0;
		height: 44px;
		flex-shrink: 0;
		color: var(--color-text-secondary);
		text-decoration: none;
		opacity: 0;
		overflow: hidden;
		transition:
			width var(--transition-fast),
			opacity var(--transition-fast),
			color var(--transition-fast);
	}

	.back-link--visible {
		width: 44px;
		opacity: 1;
	}

	.back-link:hover {
		color: var(--color-accent);
	}

	.back-icon {
		width: 24px;
		height: 24px;
		flex-shrink: 0;
	}

	/* ========================================
	   Logo Block (Home)
	   ======================================== */

	.logo-block {
		display: flex;
		align-items: baseline;
		gap: var(--spacing-md);
		text-decoration: none;
		min-height: 44px;
		padding: var(--spacing-xs) 0;
		opacity: 0;
		width: 0;
		overflow: hidden;
		transition:
			width var(--transition-fast),
			opacity var(--transition-fast);
	}

	.logo-block--visible {
		opacity: 1;
		width: auto;
	}

	.logo {
		color: var(--color-text-primary);
		flex-shrink: 0;
		transition: color var(--transition-fast);
	}

	.logo-block:hover .logo {
		color: var(--color-accent);
	}

	.tagline {
		font-family: var(--font-body);
		font-size: var(--font-size-small);
		font-style: italic;
		color: var(--color-text-tertiary);
		letter-spacing: 0.01em;
		white-space: nowrap;
		transition: color var(--transition-fast);
	}

	.logo-block:hover .tagline {
		color: var(--color-text-secondary);
	}

	/* Hide tagline on very small screens */
	@media (max-width: 400px) {
		.tagline {
			display: none;
		}
	}

	/* ========================================
	   Title Block (Recipe Detail)
	   ======================================== */

	.header-title-block {
		min-width: 0;
		overflow: hidden;
		opacity: 0;
		transform: translateX(-8px);
		transition:
			opacity var(--transition-fast),
			transform var(--transition-fast);
	}

	.header-title-block--visible {
		opacity: 1;
		transform: translateX(0);
	}

	.header-title {
		margin: 0;
		font-size: var(--font-size-body);
		font-weight: 600;
		color: var(--color-text-primary);
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
	}

	.header-subtitle {
		margin: 0;
		font-family: var(--font-body);
		font-size: var(--font-size-small);
		font-style: italic;
		color: var(--color-text-tertiary);
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
	}

	/* ========================================
	   Actions (Upload + Theme Toggle)
	   ======================================== */

	.header-actions {
		display: flex;
		align-items: center;
		gap: var(--spacing-sm);
		flex-shrink: 0;
	}

	.theme-toggle {
		display: flex;
		align-items: center;
		justify-content: center;
		width: 44px;
		height: 44px;
		padding: 0;
		color: var(--color-text-secondary);
		background: transparent;
		border: 1px solid var(--color-border);
		border-radius: var(--radius-md);
		cursor: pointer;
		transition: all var(--transition-fast);
	}

	.theme-toggle:hover {
		color: var(--color-text-primary);
		border-color: var(--color-border-strong);
	}
</style>

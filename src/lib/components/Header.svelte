<script lang="ts">
	import { base } from '$app/paths';
	import { page } from '$app/stores';
	import { toggleTheme, getTheme } from '$lib/stores/theme';
	import { pageTitle } from '$lib/stores/pageTitle';
	import UploadButton from './UploadButton.svelte';
	import WakeLockToggle from './WakeLockToggle.svelte';

	let isDark = $state(getTheme() === 'dark');
	let menuOpen = $state(false);

	// Navigation state
	const isMealsActive = $derived($page.url.pathname.startsWith(`${base}/meals`));
	const isRecipesActive = $derived(!isMealsActive);

	function handleToggle() {
		const newTheme = toggleTheme();
		isDark = newTheme === 'dark';
	}

	function toggleMenu() {
		menuOpen = !menuOpen;
	}

	function closeMenu() {
		menuOpen = false;
	}

	function handleKeydown(event: KeyboardEvent) {
		if (event.key === 'Escape' && menuOpen) {
			closeMenu();
		}
	}
</script>

<svelte:window onkeydown={handleKeydown} />

<header class="header">
	<div class="header-left">
		<!-- Hamburger menu button -->
		<button
			class="menu-toggle"
			class:menu-toggle--hidden={$pageTitle.showBackButton}
			onclick={toggleMenu}
			aria-label={menuOpen ? 'Close menu' : 'Open menu'}
			aria-expanded={menuOpen}
		>
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="menu-icon">
				{#if menuOpen}
					<path d="M18 6 6 18M6 6l12 12"></path>
				{:else}
					<path d="M3 12h18M3 6h18M3 18h18"></path>
				{/if}
			</svg>
		</button>

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
		<WakeLockToggle visible={$pageTitle.showBackButton} />

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

<!-- Slide-out navigation menu -->
<!-- svelte-ignore a11y_click_events_have_key_events -->
<!-- svelte-ignore a11y_no_static_element_interactions -->
{#if menuOpen}
	<div class="menu-overlay" onclick={closeMenu}></div>
{/if}
<nav class="slide-menu" class:slide-menu--open={menuOpen}>
	<div class="menu-header">
		<span class="menu-title text-logo">mïse</span>
	</div>
	<div class="menu-links">
		<!-- eslint-disable-next-line svelte/no-navigation-without-resolve -- using base for non-parameterized route -->
		<a href={base || '/'} class="menu-link" class:active={isRecipesActive} onclick={closeMenu}>
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="menu-link-icon">
				<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
				<path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
			</svg>
			Recipes
		</a>
		<!-- eslint-disable-next-line svelte/no-navigation-without-resolve -- using base for non-parameterized route -->
		<a href="{base}/meals" class="menu-link" class:active={isMealsActive} onclick={closeMenu}>
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="menu-link-icon">
				<path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"></path>
				<path d="M7 2v20"></path>
				<path d="M21 15V2v0a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"></path>
			</svg>
			Meals
		</a>
	</div>
</nav>

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
	   Hamburger Menu Toggle
	   ======================================== */

	.menu-toggle {
		display: flex;
		align-items: center;
		justify-content: center;
		width: 44px;
		height: 44px;
		padding: 0;
		margin-right: var(--spacing-sm);
		color: var(--color-text-secondary);
		background: transparent;
		border: none;
		border-radius: var(--radius-md);
		cursor: pointer;
		flex-shrink: 0;
		transition:
			color var(--transition-fast),
			opacity var(--transition-fast),
			width var(--transition-fast),
			margin var(--transition-fast);
	}

	.menu-toggle:hover {
		color: var(--color-text-primary);
	}

	.menu-toggle--hidden {
		width: 0;
		margin-right: 0;
		opacity: 0;
		overflow: hidden;
		pointer-events: none;
	}

	.menu-icon {
		width: 24px;
		height: 24px;
		flex-shrink: 0;
	}

	/* ========================================
	   Slide-out Menu
	   ======================================== */

	.menu-overlay {
		position: fixed;
		inset: 0;
		background: rgba(0, 0, 0, 0.4);
		z-index: 998;
		animation: fadeIn 0.2s ease-out;
	}

	@keyframes fadeIn {
		from {
			opacity: 0;
		}
		to {
			opacity: 1;
		}
	}

	.slide-menu {
		position: fixed;
		top: 0;
		left: 0;
		bottom: 0;
		width: 280px;
		max-width: 80vw;
		background: var(--color-surface);
		border-right: 1px solid var(--color-border);
		z-index: 999;
		transform: translateX(-100%);
		transition: transform 0.25s ease-out;
		display: flex;
		flex-direction: column;
	}

	.slide-menu--open {
		transform: translateX(0);
	}

	.menu-header {
		padding: var(--spacing-lg) var(--spacing-xl);
		border-bottom: 1px solid var(--color-border);
	}

	.menu-title {
		color: var(--color-text-primary);
	}

	.menu-links {
		display: flex;
		flex-direction: column;
		padding: var(--spacing-md) 0;
	}

	.menu-link {
		display: flex;
		align-items: center;
		gap: var(--spacing-md);
		padding: var(--spacing-md) var(--spacing-xl);
		font-family: var(--font-body);
		font-size: 1rem;
		font-weight: 500;
		color: var(--color-text-secondary);
		text-decoration: none;
		transition:
			color 0.2s,
			background 0.2s;
		min-height: 48px;
	}

	.menu-link:hover {
		color: var(--color-text-primary);
		background: var(--color-bg);
	}

	.menu-link.active {
		color: var(--color-accent);
		background: var(--color-bg);
	}

	.menu-link-icon {
		width: 20px;
		height: 20px;
		flex-shrink: 0;
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

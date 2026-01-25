<script lang="ts">
	import { resolveRoute } from '$app/paths';
	import { toggleTheme, getTheme } from '$lib/stores/theme';

	let isDark = $state(getTheme() === 'dark');

	function handleToggle() {
		const newTheme = toggleTheme();
		isDark = newTheme === 'dark';
	}
</script>

<header class="header">
	<!-- eslint-disable-next-line svelte/no-navigation-without-resolve -->
	<a href={resolveRoute('/', {})} class="logo text-logo">mïse</a>

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
</header>

<style>
	.header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: var(--spacing-lg) var(--spacing-xl);
		border-bottom: 1px solid var(--color-border);
	}

	.logo {
		color: var(--color-text-primary);
		text-decoration: none;
	}

	.logo:hover {
		color: var(--color-accent);
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

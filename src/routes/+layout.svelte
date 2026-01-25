<script lang="ts">
	import { fade } from 'svelte/transition';
	import { onMount } from 'svelte';
	import { page } from '$app/stores';
	import '$styles/tokens.css';
	import favicon from '$lib/assets/favicon.svg';
	import { initTheme } from '$lib/stores/theme';
	import Header from '$lib/components/Header.svelte';

	let { children } = $props();

	onMount(() => {
		initTheme();
	});
</script>

<svelte:head>
	<link rel="icon" href={favicon} />
</svelte:head>

<div class="app">
	<Header />

	{#key $page.url.pathname}
		<main class="main" in:fade={{ duration: 200, delay: 100 }} out:fade={{ duration: 150 }}>
			{@render children()}
		</main>
	{/key}
</div>

<style>
	:global(html, body) {
		margin: 0;
		padding: 0;
		min-height: 100%;
		font-family: var(--font-body);
		font-size: var(--font-size-body);
		color: var(--color-text-primary);
		background-color: var(--color-bg);
	}

	:global(*, *::before, *::after) {
		box-sizing: border-box;
	}

	.app {
		display: flex;
		flex-direction: column;
		min-height: 100vh;
	}

	.main {
		flex: 1;
		padding: var(--spacing-xl);
	}
</style>

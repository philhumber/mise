<script lang="ts">
	import { fade } from 'svelte/transition';
	import { onMount } from 'svelte';
	import { page } from '$app/stores';
	import '$styles/tokens.css';
	import favicon from '$lib/assets/favicon.svg';
	import { initTheme } from '$lib/stores/theme';

	let { children } = $props();

	onMount(() => {
		initTheme();
	});
</script>

<svelte:head>
	<link rel="icon" href={favicon} />
</svelte:head>

{#key $page.url.pathname}
	<div class="page-transition" in:fade={{ duration: 200, delay: 100 }} out:fade={{ duration: 150 }}>
		{@render children()}
	</div>
{/key}

<style>
	.page-transition {
		min-height: 100%;
	}
</style>

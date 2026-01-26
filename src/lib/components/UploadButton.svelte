<script lang="ts">
	import { goto } from '$app/navigation';
	import { base } from '$app/paths';
	import { onMount } from 'svelte';
	import { checkAuth, login, logout } from '$lib/api/recipes';
	import UploadModal from './UploadModal.svelte';

	let isAuthenticated = $state(false);
	let showModal = $state(false);
	let showLoginForm = $state(false);
	let password = $state('');
	let loginError = $state<string | null>(null);
	let isLoggingIn = $state(false);

	onMount(async () => {
		isAuthenticated = await checkAuth();
	});

	async function handleLogin() {
		if (!password.trim()) return;

		isLoggingIn = true;
		loginError = null;

		const result = await login(password);

		if (result.success) {
			isAuthenticated = true;
			showLoginForm = false;
			password = '';
			showModal = true;
		} else {
			loginError = result.error || 'Login failed';
		}

		isLoggingIn = false;
	}

	async function handleLogout() {
		await logout();
		isAuthenticated = false;
	}

	function handleUploadClick() {
		if (isAuthenticated) {
			showModal = true;
		} else {
			showLoginForm = true;
		}
	}

	function handleSuccess(slug: string) {
		showModal = false;
		goto(`${base}/recipe/${slug}`);
	}

	function handleKeydown(e: KeyboardEvent) {
		if (e.key === 'Enter' && showLoginForm) {
			handleLogin();
		}
		if (e.key === 'Escape') {
			showLoginForm = false;
			password = '';
			loginError = null;
		}
	}
</script>

<svelte:window onkeydown={handleKeydown} />

<div class="upload-container">
	<button
		class="upload-button"
		onclick={handleUploadClick}
		aria-label="Upload recipe"
		title={isAuthenticated ? 'Upload recipe' : 'Login to upload'}
	>
		<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
			<path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12" />
		</svg>
		<span class="button-text">Upload</span>
	</button>

	{#if isAuthenticated}
		<button
			class="logout-button"
			onclick={handleLogout}
			aria-label="Logout"
			title="Logout"
		>
			<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
				<path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9" />
			</svg>
		</button>
	{/if}
</div>

<!-- Login popup -->
{#if showLoginForm}
	<!-- svelte-ignore a11y_click_events_have_key_events a11y_no_static_element_interactions -->
	<div class="login-overlay" onclick={() => { showLoginForm = false; password = ''; loginError = null; }} role="presentation">
		<!-- svelte-ignore a11y_click_events_have_key_events a11y_no_static_element_interactions -->
		<div class="login-popup" onclick={(e) => e.stopPropagation()} role="dialog" aria-modal="true" tabindex="-1">
			<p class="login-title">Enter password to upload</p>
			<input
				type="password"
				bind:value={password}
				placeholder="Password"
				class="login-input"
				disabled={isLoggingIn}
			/>
			{#if loginError}
				<p class="login-error">{loginError}</p>
			{/if}
			<div class="login-actions">
				<button
					class="btn btn-secondary"
					onclick={() => { showLoginForm = false; password = ''; loginError = null; }}
					disabled={isLoggingIn}
				>
					Cancel
				</button>
				<button
					class="btn btn-primary"
					onclick={handleLogin}
					disabled={!password.trim() || isLoggingIn}
				>
					{isLoggingIn ? 'Logging in...' : 'Login'}
				</button>
			</div>
		</div>
	</div>
{/if}

<UploadModal
	open={showModal}
	onClose={() => { showModal = false; }}
	onSuccess={handleSuccess}
/>

<style>
	.upload-container {
		display: flex;
		align-items: center;
		gap: var(--spacing-xs);
	}

	.upload-button {
		display: flex;
		align-items: center;
		justify-content: center;
		gap: var(--spacing-sm);
		min-height: 44px;
		padding: 0 var(--spacing-md);
		color: var(--color-text-secondary);
		background: transparent;
		border: 1px solid var(--color-border);
		border-radius: var(--radius-md);
		cursor: pointer;
		transition: all var(--transition-fast);
	}

	.upload-button:hover {
		color: var(--color-text-primary);
		border-color: var(--color-border-strong);
	}

	.button-text {
		display: none;
		font-family: var(--font-body);
		font-size: var(--font-size-meta);
		font-weight: 500;
	}

	@media (min-width: 768px) {
		.button-text {
			display: inline;
		}
	}

	.logout-button {
		display: flex;
		align-items: center;
		justify-content: center;
		width: 32px;
		height: 32px;
		padding: 0;
		color: var(--color-text-tertiary);
		background: transparent;
		border: none;
		border-radius: var(--radius-sm);
		cursor: pointer;
		transition: color var(--transition-fast);
	}

	.logout-button:hover {
		color: var(--color-text-secondary);
	}

	/* Login popup styles */
	.login-overlay {
		position: fixed;
		inset: 0;
		z-index: 1000;
		display: flex;
		align-items: flex-start;
		justify-content: center;
		padding-top: 100px;
		background-color: rgba(0, 0, 0, 0.3);
	}

	.login-popup {
		width: 100%;
		max-width: 320px;
		padding: var(--spacing-xl);
		margin: var(--spacing-lg);
		background-color: var(--color-surface);
		border: 1px solid var(--color-border);
		border-radius: var(--radius-md);
		box-shadow: var(--shadow-card-hover);
	}

	.login-title {
		margin: 0 0 var(--spacing-lg);
		font-family: var(--font-body);
		font-size: var(--font-size-body);
		font-weight: 500;
		color: var(--color-text-primary);
	}

	.login-input {
		width: 100%;
		padding: var(--spacing-md);
		font-family: var(--font-body);
		font-size: var(--font-size-body);
		color: var(--color-text-primary);
		background-color: var(--color-bg);
		border: 1px solid var(--color-border);
		border-radius: var(--radius-sm);
	}

	.login-input:focus {
		border-color: var(--color-accent);
		outline: none;
	}

	.login-error {
		margin: var(--spacing-sm) 0 0;
		font-size: var(--font-size-meta);
		color: #b91c1c;
	}

	:global([data-theme='dark']) .login-error {
		color: #fca5a5;
	}

	.login-actions {
		display: flex;
		justify-content: flex-end;
		gap: var(--spacing-sm);
		margin-top: var(--spacing-lg);
	}

	.btn {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		min-height: 40px;
		padding: var(--spacing-sm) var(--spacing-lg);
		font-family: var(--font-body);
		font-size: var(--font-size-meta);
		font-weight: 500;
		border-radius: var(--radius-sm);
		cursor: pointer;
		transition: all var(--transition-fast);
	}

	.btn:disabled {
		opacity: 0.5;
		cursor: not-allowed;
	}

	.btn-secondary {
		color: var(--color-text-secondary);
		background-color: transparent;
		border: 1px solid var(--color-border);
	}

	.btn-secondary:hover:not(:disabled) {
		color: var(--color-text-primary);
		border-color: var(--color-border-strong);
	}

	.btn-primary {
		color: white;
		background-color: var(--color-accent);
		border: 1px solid var(--color-accent);
	}

	.btn-primary:hover:not(:disabled) {
		background-color: var(--color-accent-muted);
		border-color: var(--color-accent-muted);
	}
</style>

<script lang="ts">
	import { updateRecipe } from '$lib/api/recipes';

	interface Props {
		open: boolean;
		slug: string;
		initialMarkdown: string;
		onClose: () => void;
		onSuccess: () => void;
	}

	let { open, slug, initialMarkdown, onClose, onSuccess }: Props = $props();

	let markdown = $state(initialMarkdown);
	let isProcessing = $state(false);
	let error = $state<string | null>(null);
	let validationErrors = $state<Array<{ field: string; message: string }>>([]);

	// Reset state when modal opens with new content
	$effect(() => {
		if (open) {
			markdown = initialMarkdown;
			error = null;
			validationErrors = [];
		}
	});

	async function handleSave() {
		if (!markdown.trim()) return;

		isProcessing = true;
		error = null;
		validationErrors = [];

		try {
			const result = await updateRecipe(slug, markdown);

			if (!result.success) {
				if (result.errors && result.errors.length > 0) {
					validationErrors = result.errors;
				} else {
					error = 'Update failed';
				}
				return;
			}

			onSuccess();
		} catch (err) {
			error = 'An error occurred while saving';
			console.error('Save error:', err);
		} finally {
			isProcessing = false;
		}
	}

	function handleClose() {
		if (!isProcessing) {
			error = null;
			validationErrors = [];
			onClose();
		}
	}

	function handleKeydown(e: KeyboardEvent) {
		if (e.key === 'Escape' && !isProcessing) {
			handleClose();
		}
	}
</script>

<svelte:window onkeydown={handleKeydown} />

{#if open}
	<!-- svelte-ignore a11y_click_events_have_key_events -->
	<div class="modal-overlay" onclick={handleClose} role="presentation">
		<!-- svelte-ignore a11y_click_events_have_key_events -->
		<div
			class="modal"
			onclick={(e) => e.stopPropagation()}
			role="dialog"
			aria-modal="true"
			aria-labelledby="modal-title"
			tabindex="-1"
		>
			<header class="modal-header">
				<h2 id="modal-title" class="text-display">Edit Recipe</h2>
				<button class="close-button" onclick={handleClose} aria-label="Close" disabled={isProcessing}>
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M18 6L6 18M6 6l12 12" />
					</svg>
				</button>
			</header>

			<div class="modal-body">
				<label for="markdown-editor" class="editor-label">Recipe Markdown</label>
				<textarea
					id="markdown-editor"
					class="markdown-editor"
					bind:value={markdown}
					disabled={isProcessing}
					placeholder="Paste your recipe markdown here..."
					spellcheck="false"
				></textarea>

				<!-- Error messages -->
				{#if error}
					<div class="error-message" role="alert">
						{error}
					</div>
				{/if}

				{#if validationErrors.length > 0}
					<div class="validation-errors" role="alert">
						<p class="error-title">Validation errors:</p>
						<ul>
							{#each validationErrors as err, i (err.field + i)}
								<li><strong>{err.field}:</strong> {err.message}</li>
							{/each}
						</ul>
					</div>
				{/if}

				<p class="help-text text-meta">
					Edit the recipe markdown content. The file must include valid YAML frontmatter.
				</p>
			</div>

			<footer class="modal-footer">
				<button class="btn btn-secondary" onclick={handleClose} disabled={isProcessing}>
					Cancel
				</button>
				<button
					class="btn btn-primary"
					onclick={handleSave}
					disabled={!markdown.trim() || isProcessing}
				>
					{#if isProcessing}
						Saving...
					{:else}
						Save
					{/if}
				</button>
			</footer>
		</div>
	</div>
{/if}

<style>
	.modal-overlay {
		position: fixed;
		inset: 0;
		z-index: 1000;
		display: flex;
		align-items: center;
		justify-content: center;
		padding: var(--spacing-lg);
		background-color: rgba(0, 0, 0, 0.5);
		backdrop-filter: blur(2px);
	}

	.modal {
		display: flex;
		flex-direction: column;
		width: 100%;
		max-width: 640px;
		max-height: calc(100vh - var(--spacing-xl) * 2);
		background-color: var(--color-surface);
		border: 1px solid var(--color-border);
		border-radius: var(--radius-md);
		box-shadow: var(--shadow-card-hover);
	}

	.modal-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: var(--spacing-lg) var(--spacing-xl);
		border-bottom: 1px solid var(--color-border);
		flex-shrink: 0;
	}

	.modal-header h2 {
		margin: 0;
		font-size: var(--font-size-title);
		color: var(--color-text-primary);
	}

	.close-button {
		display: flex;
		align-items: center;
		justify-content: center;
		width: 36px;
		height: 36px;
		padding: 0;
		color: var(--color-text-secondary);
		background: transparent;
		border: none;
		border-radius: var(--radius-sm);
		cursor: pointer;
		transition: color var(--transition-fast);
	}

	.close-button:hover:not(:disabled) {
		color: var(--color-text-primary);
	}

	.close-button:disabled {
		opacity: 0.5;
		cursor: not-allowed;
	}

	.modal-body {
		padding: var(--spacing-xl);
		overflow-y: auto;
		flex: 1;
		min-height: 0;
	}

	.editor-label {
		display: block;
		margin-bottom: var(--spacing-sm);
		font-family: var(--font-body);
		font-size: var(--font-size-small);
		font-weight: 500;
		color: var(--color-text-secondary);
	}

	.markdown-editor {
		width: 100%;
		min-height: 300px;
		padding: var(--spacing-md);
		font-family: ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Consolas, monospace;
		font-size: 13px;
		line-height: 1.5;
		color: var(--color-text-primary);
		background-color: var(--color-bg);
		border: 1px solid var(--color-border);
		border-radius: var(--radius-md);
		resize: vertical;
		transition: border-color var(--transition-fast);
	}

	.markdown-editor:focus {
		outline: none;
		border-color: var(--color-accent);
	}

	.markdown-editor:disabled {
		opacity: 0.6;
		cursor: not-allowed;
	}

	.error-message,
	.validation-errors {
		margin-top: var(--spacing-lg);
		padding: var(--spacing-md) var(--spacing-lg);
		color: #b91c1c;
		background-color: #fef2f2;
		border: 1px solid #fecaca;
		border-radius: var(--radius-sm);
	}

	:global([data-theme='dark']) .error-message,
	:global([data-theme='dark']) .validation-errors {
		color: #fca5a5;
		background-color: rgba(185, 28, 28, 0.15);
		border-color: rgba(185, 28, 28, 0.3);
	}

	.error-title {
		margin: 0 0 var(--spacing-sm);
		font-weight: 500;
	}

	.validation-errors ul {
		margin: 0;
		padding-left: var(--spacing-lg);
	}

	.validation-errors li {
		margin-bottom: var(--spacing-xs);
	}

	.help-text {
		margin-top: var(--spacing-lg);
	}

	.modal-footer {
		display: flex;
		justify-content: flex-end;
		gap: var(--spacing-md);
		padding: var(--spacing-lg) var(--spacing-xl);
		border-top: 1px solid var(--color-border);
		flex-shrink: 0;
	}

	.btn {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		min-height: 44px;
		padding: var(--spacing-sm) var(--spacing-xl);
		font-family: var(--font-body);
		font-size: var(--font-size-body);
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

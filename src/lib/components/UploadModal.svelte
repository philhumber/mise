<script lang="ts">
	import { uploadRecipe } from '$lib/api/recipes';

	interface Props {
		open: boolean;
		onClose: () => void;
		onSuccess: (slug: string) => void;
	}

	let { open, onClose, onSuccess }: Props = $props();

	let file = $state<File | null>(null);
	let dragOver = $state(false);
	let isProcessing = $state(false);
	let error = $state<string | null>(null);
	let validationErrors = $state<Array<{ field: string; message: string }>>([]);

	function handleDragOver(e: DragEvent) {
		e.preventDefault();
		dragOver = true;
	}

	function handleDragLeave(e: DragEvent) {
		e.preventDefault();
		dragOver = false;
	}

	function handleDrop(e: DragEvent) {
		e.preventDefault();
		dragOver = false;

		const files = e.dataTransfer?.files;
		if (files && files.length > 0) {
			selectFile(files[0]);
		}
	}

	function handleFileInput(e: Event) {
		const input = e.target as HTMLInputElement;
		if (input.files && input.files.length > 0) {
			selectFile(input.files[0]);
		}
	}

	function selectFile(f: File) {
		// Validate file type
		if (!f.name.endsWith('.md')) {
			error = 'Please select a markdown file (.md)';
			file = null;
			return;
		}

		// Validate file size (1MB max)
		if (f.size > 1024 * 1024) {
			error = 'File is too large (max 1MB)';
			file = null;
			return;
		}

		error = null;
		validationErrors = [];
		file = f;
	}

	async function handleUpload() {
		if (!file) return;

		isProcessing = true;
		error = null;
		validationErrors = [];

		try {
			const markdown = await file.text();
			const result = await uploadRecipe(markdown);

			if (!result.success) {
				if (result.errors && result.errors.length > 0) {
					validationErrors = result.errors;
				} else {
					error = 'Upload failed';
				}
				return;
			}

			if (result.recipe) {
				onSuccess(result.recipe.slug);
			}
		} catch (err) {
			error = 'An error occurred while uploading';
			console.error('Upload error:', err);
		} finally {
			isProcessing = false;
		}
	}

	function handleClose() {
		if (!isProcessing) {
			file = null;
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
		<div class="modal" onclick={(e) => e.stopPropagation()} role="dialog" aria-modal="true" aria-labelledby="modal-title" tabindex="-1">
			<header class="modal-header">
				<h2 id="modal-title" class="text-display">Upload Recipe</h2>
				<button class="close-button" onclick={handleClose} aria-label="Close" disabled={isProcessing}>
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M18 6L6 18M6 6l12 12" />
					</svg>
				</button>
			</header>

			<div class="modal-body">
				<!-- Drop zone -->
				<div
					class="drop-zone"
					class:drag-over={dragOver}
					class:has-file={file}
					ondragover={handleDragOver}
					ondragleave={handleDragLeave}
					ondrop={handleDrop}
					onclick={() => document.getElementById('file-input')?.click()}
					role="button"
					tabindex="0"
					onkeydown={(e) => e.key === 'Enter' && document.getElementById('file-input')?.click()}
				>
					{#if file}
						<svg class="icon" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
							<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
						</svg>
						<p class="filename">{file.name}</p>
						<p class="text-meta">Click or drop to replace</p>
					{:else}
						<svg class="icon" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
							<path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
						</svg>
						<p>Drop markdown file here</p>
						<p class="text-meta">or click to browse</p>
					{/if}

					<input
						id="file-input"
						type="file"
						accept=".md"
						onchange={handleFileInput}
						class="sr-only"
					/>
				</div>

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
							{#each validationErrors as err}
								<li><strong>{err.field}:</strong> {err.message}</li>
							{/each}
						</ul>
					</div>
				{/if}

				<!-- Help text -->
				<p class="help-text text-meta">
					Upload a recipe markdown file created with the Claude recipe converter.
					The file must include valid YAML frontmatter.
				</p>
			</div>

			<footer class="modal-footer">
				<button class="btn btn-secondary" onclick={handleClose} disabled={isProcessing}>
					Cancel
				</button>
				<button
					class="btn btn-primary"
					onclick={handleUpload}
					disabled={!file || isProcessing}
				>
					{#if isProcessing}
						Uploading...
					{:else}
						Upload
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
		width: 100%;
		max-width: 480px;
		max-height: calc(100vh - var(--spacing-xl) * 2);
		overflow: auto;
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
	}

	.drop-zone {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		gap: var(--spacing-sm);
		min-height: 160px;
		padding: var(--spacing-xl);
		color: var(--color-text-secondary);
		text-align: center;
		background-color: var(--color-bg);
		border: 2px dashed var(--color-border);
		border-radius: var(--radius-md);
		cursor: pointer;
		transition: all var(--transition-fast);
	}

	.drop-zone:hover,
	.drop-zone.drag-over {
		border-color: var(--color-accent);
		background-color: var(--color-highlight);
	}

	.drop-zone.has-file {
		border-style: solid;
		border-color: var(--color-accent);
	}

	.drop-zone .icon {
		color: var(--color-text-tertiary);
	}

	.drop-zone.has-file .icon {
		color: var(--color-accent);
	}

	.drop-zone p {
		margin: 0;
	}

	.filename {
		font-weight: 500;
		color: var(--color-text-primary);
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

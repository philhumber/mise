/**
 * API Client for Mïse Recipe API
 *
 * Handles communication with the PHP backend for user-uploaded recipes.
 */

import { base } from '$app/paths';
import type { Recipe, RecipeMeta } from '$lib/types';

const API_BASE = `${base}/api`;

/**
 * API response types
 */
interface ApiError {
	error: string;
}

interface ValidationError {
	field: string;
	message: string;
}

interface UploadResponse {
	success: boolean;
	recipe?: Record<string, unknown>;
	errors?: ValidationError[];
}

interface AuthResponse {
	authenticated: boolean;
	success?: boolean;
}

/**
 * Check if user is authenticated
 */
export async function checkAuth(): Promise<boolean> {
	try {
		const res = await fetch(`${API_BASE}/auth.php`, {
			credentials: 'include'
		});

		if (!res.ok) return false;

		const data: AuthResponse = await res.json();
		return data.authenticated;
	} catch {
		return false;
	}
}

/**
 * Login with password
 */
export async function login(password: string): Promise<{ success: boolean; error?: string }> {
	try {
		const res = await fetch(`${API_BASE}/auth.php`, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			credentials: 'include',
			body: JSON.stringify({ password })
		});

		if (!res.ok) {
			const data: ApiError = await res.json();
			return { success: false, error: data.error || 'Login failed' };
		}

		return { success: true };
	} catch {
		return { success: false, error: 'Network error' };
	}
}

/**
 * Logout (clear session)
 */
export async function logout(): Promise<void> {
	await fetch(`${API_BASE}/auth.php`, {
		method: 'DELETE',
		credentials: 'include'
	});
}

/**
 * Fetch all user-uploaded recipes (metadata only)
 */
export async function fetchUserRecipes(): Promise<RecipeMeta[]> {
	try {
		const res = await fetch(`${API_BASE}/recipes.php`, {
			credentials: 'include'
		});

		if (!res.ok) {
			console.error('Failed to fetch user recipes:', res.status);
			return [];
		}

		const data = await res.json();

		// Transform snake_case from PHP to match our types
		return data.map(transformRecipeMeta);
	} catch (err) {
		console.error('Error fetching user recipes:', err);
		return [];
	}
}

/**
 * Fetch a single user recipe by slug
 */
export async function fetchUserRecipe(slug: string): Promise<Recipe | null> {
	try {
		const res = await fetch(`${API_BASE}/recipes.php?slug=${encodeURIComponent(slug)}`, {
			credentials: 'include'
		});

		if (!res.ok) {
			if (res.status === 404) return null;
			console.error('Failed to fetch user recipe:', res.status);
			return null;
		}

		const data = await res.json();
		return transformRecipe(data);
	} catch (err) {
		console.error('Error fetching user recipe:', err);
		return null;
	}
}

/**
 * Upload a new recipe from markdown content
 */
export async function uploadRecipe(
	markdown: string
): Promise<{ success: boolean; recipe?: Recipe; errors?: ValidationError[] }> {
	try {
		const res = await fetch(`${API_BASE}/recipes.php`, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			credentials: 'include',
			body: JSON.stringify({ markdown })
		});

		const data: UploadResponse = await res.json();

		if (!res.ok || !data.success) {
			return {
				success: false,
				errors: data.errors || [{ field: 'general', message: 'Upload failed' }]
			};
		}

		return {
			success: true,
			recipe: data.recipe ? transformRecipe(data.recipe) : undefined
		};
	} catch (err) {
		console.error('Error uploading recipe:', err);
		return {
			success: false,
			errors: [{ field: 'general', message: 'Network error' }]
		};
	}
}

/**
 * Fetch a recipe with its raw markdown content (for editing)
 */
export async function fetchRecipeForEdit(
	slug: string
): Promise<{ recipe: Recipe; markdown: string } | null> {
	try {
		const res = await fetch(
			`${API_BASE}/recipes.php?slug=${encodeURIComponent(slug)}&edit=1`,
			{ credentials: 'include' }
		);

		if (!res.ok) {
			if (res.status === 404) return null;
			console.error('Failed to fetch recipe for edit:', res.status);
			return null;
		}

		const data = await res.json();
		return {
			recipe: transformRecipe(data),
			markdown: String(data.markdown || '')
		};
	} catch (err) {
		console.error('Error fetching recipe for edit:', err);
		return null;
	}
}

/**
 * Update an existing recipe with new markdown content
 */
export async function updateRecipe(
	slug: string,
	markdown: string
): Promise<{ success: boolean; recipe?: Recipe; errors?: ValidationError[] }> {
	try {
		const res = await fetch(`${API_BASE}/recipes.php?slug=${encodeURIComponent(slug)}`, {
			method: 'PUT',
			headers: { 'Content-Type': 'application/json' },
			credentials: 'include',
			body: JSON.stringify({ markdown })
		});

		const data: UploadResponse = await res.json();

		if (!res.ok || !data.success) {
			return {
				success: false,
				errors: data.errors || [{ field: 'general', message: 'Update failed' }]
			};
		}

		return {
			success: true,
			recipe: data.recipe ? transformRecipe(data.recipe) : undefined
		};
	} catch (err) {
		console.error('Error updating recipe:', err);
		return {
			success: false,
			errors: [{ field: 'general', message: 'Network error' }]
		};
	}
}

/**
 * Delete a user recipe
 */
export async function deleteRecipe(slug: string): Promise<boolean> {
	try {
		const res = await fetch(`${API_BASE}/recipes.php?slug=${encodeURIComponent(slug)}`, {
			method: 'DELETE',
			credentials: 'include'
		});

		return res.ok;
	} catch {
		return false;
	}
}

/**
 * Transform PHP response (snake_case) to TypeScript types (snake_case matches)
 * PostgreSQL returns snake_case which matches our TypeScript interface
 */
function transformRecipeMeta(data: Record<string, unknown>): RecipeMeta {
	return {
		slug: String(data.slug),
		title: String(data.title),
		subtitle: data.subtitle ? String(data.subtitle) : undefined,
		category: String(data.category) as RecipeMeta['category'],
		difficulty: String(data.difficulty) as RecipeMeta['difficulty'],
		active_time: String(data.active_time),
		total_time: String(data.total_time),
		serves: Number(data.serves),
		tags: parsePostgresArray(data.tags)
	};
}

function transformRecipe(data: Record<string, unknown>): Recipe {
	return {
		...transformRecipeMeta(data),
		content: String(data.content)
	};
}

/**
 * Parse PostgreSQL array format: {item1,item2,item3}
 */
function parsePostgresArray(value: unknown): string[] {
	if (Array.isArray(value)) {
		return value.map(String);
	}

	if (typeof value === 'string') {
		// Handle PostgreSQL array format: {item1,item2,item3}
		if (value.startsWith('{') && value.endsWith('}')) {
			const inner = value.slice(1, -1);
			if (!inner) return [];

			// Simple parsing - doesn't handle quoted strings with commas
			return inner.split(',').map((s) => s.replace(/^"|"$/g, '').trim());
		}

		// Try JSON parsing as fallback
		try {
			const parsed = JSON.parse(value);
			if (Array.isArray(parsed)) return parsed.map(String);
		} catch {
			// Not JSON
		}
	}

	return [];
}

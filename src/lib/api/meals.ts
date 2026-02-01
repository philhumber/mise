/**
 * API Client for Mïse Meals API
 *
 * Handles communication with the PHP backend for meal planning.
 */

import { base } from '$app/paths';
import type { Meal, MealMeta, ValidationError } from '$lib/types';

const API_BASE = `${base}/api`;

// === TRANSFORM FUNCTIONS ===

function transformMealMeta(data: Record<string, unknown>): MealMeta {
	return {
		id: Number(data.id),
		slug: String(data.slug),
		title: String(data.title),
		description: data.description ? String(data.description) : undefined,
		recipe_count: Number(data.recipe_count),
		timeline_span: String(data.timeline_span),
		is_stale: Boolean(data.is_stale),
		created_at: String(data.created_at),
		updated_at: String(data.updated_at)
	};
}

function transformMeal(data: Record<string, unknown>): Meal {
	return {
		...transformMealMeta(data),
		snapshot: data.snapshot as Meal['snapshot']
	};
}

// === API FUNCTIONS ===

/**
 * Fetch all meals (metadata only)
 */
export async function fetchMeals(): Promise<MealMeta[]> {
	try {
		const response = await fetch(`${API_BASE}/meals.php`, {
			credentials: 'include'
		});

		if (!response.ok) {
			console.error('Failed to fetch meals:', response.status);
			return [];
		}

		const data = await response.json();
		return (data as Record<string, unknown>[]).map(transformMealMeta);
	} catch (error) {
		console.error('Error fetching meals:', error);
		return [];
	}
}

/**
 * Fetch a single meal by slug (with full snapshot)
 */
export async function fetchMeal(slug: string): Promise<Meal | null> {
	try {
		const response = await fetch(`${API_BASE}/meals.php?slug=${encodeURIComponent(slug)}`, {
			credentials: 'include'
		});

		if (!response.ok) {
			if (response.status === 404) return null;
			console.error('Failed to fetch meal:', response.status);
			return null;
		}

		const data = await response.json();
		return transformMeal(data as Record<string, unknown>);
	} catch (error) {
		console.error('Error fetching meal:', error);
		return null;
	}
}

export interface CreateMealInput {
	title: string;
	description?: string;
	recipe_slugs: string[];
}

export interface MealResult {
	success: boolean;
	meal?: Meal;
	errors?: ValidationError[];
}

/**
 * Create a new meal
 */
export async function createMeal(input: CreateMealInput): Promise<MealResult> {
	try {
		const response = await fetch(`${API_BASE}/meals.php`, {
			method: 'POST',
			credentials: 'include',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify(input)
		});

		const data = await response.json();

		if (!response.ok || !data.success) {
			return {
				success: false,
				errors: data.errors || [{ field: 'general', message: 'Failed to create meal' }]
			};
		}

		return {
			success: true,
			meal: transformMeal(data.meal as Record<string, unknown>)
		};
	} catch (error) {
		console.error('Error creating meal:', error);
		return {
			success: false,
			errors: [{ field: 'general', message: 'Network error' }]
		};
	}
}

export interface UpdateMealInput {
	title?: string;
	description?: string;
	recipe_slugs?: string[];
}

/**
 * Update an existing meal
 */
export async function updateMeal(slug: string, input: UpdateMealInput): Promise<MealResult> {
	try {
		const response = await fetch(`${API_BASE}/meals.php?slug=${encodeURIComponent(slug)}`, {
			method: 'PUT',
			credentials: 'include',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify(input)
		});

		const data = await response.json();

		if (!response.ok || !data.success) {
			return {
				success: false,
				errors: data.errors || [{ field: 'general', message: 'Failed to update meal' }]
			};
		}

		return {
			success: true,
			meal: transformMeal(data.meal as Record<string, unknown>)
		};
	} catch (error) {
		console.error('Error updating meal:', error);
		return {
			success: false,
			errors: [{ field: 'general', message: 'Network error' }]
		};
	}
}

/**
 * Delete a meal
 */
export async function deleteMeal(slug: string): Promise<boolean> {
	try {
		const response = await fetch(`${API_BASE}/meals.php?slug=${encodeURIComponent(slug)}`, {
			method: 'DELETE',
			credentials: 'include'
		});

		return response.ok;
	} catch (error) {
		console.error('Error deleting meal:', error);
		return false;
	}
}

/**
 * Refresh a meal's snapshot (regenerate from current recipe data)
 */
export async function refreshMealSnapshot(slug: string): Promise<Meal | null> {
	try {
		const response = await fetch(
			`${API_BASE}/meals.php?slug=${encodeURIComponent(slug)}&action=refresh`,
			{
				method: 'POST',
				credentials: 'include'
			}
		);

		if (!response.ok) {
			console.error('Failed to refresh meal:', response.status);
			return null;
		}

		const data = await response.json();
		if (!data.success) return null;

		return transformMeal(data.meal as Record<string, unknown>);
	} catch (error) {
		console.error('Error refreshing meal:', error);
		return null;
	}
}

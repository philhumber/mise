/**
 * Recipe data layer for Mïse
 *
 * Loads, parses, and validates recipe markdown files.
 * Uses import.meta.glob for SSG-compatible file loading.
 */

import matter from 'gray-matter';
import { marked } from 'marked';
import type { Recipe, RecipeMeta, RecipeCategory, Difficulty } from '$lib/types';

// Configure marked for GitHub Flavored Markdown
marked.setOptions({
	gfm: true,
	breaks: false
});

/**
 * Error thrown when a recipe slug doesn't exist
 */
export class RecipeNotFoundError extends Error {
	constructor(slug: string) {
		super(`Recipe not found: "${slug}"`);
		this.name = 'RecipeNotFoundError';
	}
}

/**
 * Error thrown when recipe frontmatter is invalid
 */
export class RecipeValidationError extends Error {
	constructor(slug: string, field: string, reason: string) {
		super(`Invalid recipe "${slug}": ${field} ${reason}`);
		this.name = 'RecipeValidationError';
	}
}

/**
 * Valid recipe categories
 */
const VALID_CATEGORIES: RecipeCategory[] = ['main', 'starter', 'dessert', 'side', 'drink', 'sauce'];

/**
 * Valid difficulty levels
 */
const VALID_DIFFICULTIES: Difficulty[] = ['easy', 'intermediate', 'advanced'];

/**
 * Type guard for RecipeCategory
 */
function isValidCategory(value: unknown): value is RecipeCategory {
	return typeof value === 'string' && VALID_CATEGORIES.includes(value as RecipeCategory);
}

/**
 * Type guard for Difficulty
 */
function isValidDifficulty(value: unknown): value is Difficulty {
	return typeof value === 'string' && VALID_DIFFICULTIES.includes(value as Difficulty);
}

/**
 * Validates frontmatter data and returns typed RecipeMeta
 */
function validateFrontmatter(data: unknown, slug: string): Omit<RecipeMeta, 'slug'> {
	if (!data || typeof data !== 'object') {
		throw new RecipeValidationError(slug, 'frontmatter', 'must be an object');
	}

	const fm = data as Record<string, unknown>;

	// Validate title (required string)
	if (typeof fm.title !== 'string' || fm.title.trim() === '') {
		throw new RecipeValidationError(slug, 'title', 'must be a non-empty string');
	}

	// Validate category (required enum)
	if (!isValidCategory(fm.category)) {
		throw new RecipeValidationError(
			slug,
			'category',
			`must be one of: ${VALID_CATEGORIES.join(', ')} (got: ${fm.category})`
		);
	}

	// Validate difficulty (required enum)
	if (!isValidDifficulty(fm.difficulty)) {
		throw new RecipeValidationError(
			slug,
			'difficulty',
			`must be one of: ${VALID_DIFFICULTIES.join(', ')} (got: ${fm.difficulty})`
		);
	}

	// Validate active_time (required string)
	if (typeof fm.active_time !== 'string' || fm.active_time.trim() === '') {
		throw new RecipeValidationError(slug, 'active_time', 'must be a non-empty string');
	}

	// Validate total_time (required string)
	if (typeof fm.total_time !== 'string' || fm.total_time.trim() === '') {
		throw new RecipeValidationError(slug, 'total_time', 'must be a non-empty string');
	}

	// Validate serves (required positive integer)
	if (
		typeof fm.serves !== 'number' ||
		!Number.isFinite(fm.serves) ||
		!Number.isInteger(fm.serves) ||
		fm.serves <= 0
	) {
		throw new RecipeValidationError(slug, 'serves', 'must be a positive integer');
	}

	// Validate tags (required non-empty array of non-empty strings)
	if (!Array.isArray(fm.tags) || fm.tags.length === 0) {
		throw new RecipeValidationError(slug, 'tags', 'must be a non-empty array');
	}
	if (!fm.tags.every((tag): tag is string => typeof tag === 'string' && tag.trim() !== '')) {
		throw new RecipeValidationError(slug, 'tags', 'must be an array of non-empty strings');
	}

	// Validate subtitle (optional string)
	if (fm.subtitle !== undefined && typeof fm.subtitle !== 'string') {
		throw new RecipeValidationError(slug, 'subtitle', 'must be a string if provided');
	}

	return {
		title: fm.title,
		subtitle: fm.subtitle as string | undefined,
		category: fm.category,
		difficulty: fm.difficulty,
		active_time: fm.active_time,
		total_time: fm.total_time,
		serves: fm.serves,
		tags: fm.tags
	};
}

/**
 * Parses a single recipe markdown file into a Recipe object
 */
function parseRecipeFile(slug: string, rawContent: string): Recipe {
	const { data, content } = matter(rawContent);
	const meta = validateFrontmatter(data, slug);
	const html = marked.parse(content) as string;

	return {
		slug,
		...meta,
		content: html
	};
}

/**
 * Extract slug from file path
 * Example: '../../content/recipes/kombu-cod-recipe.md' → 'kombu-cod-recipe'
 */
function getSlugFromPath(path: string): string {
	const parts = path.split('/');
	const filename = parts[parts.length - 1];
	if (!filename) {
		throw new Error(`Invalid recipe path: "${path}"`);
	}
	return filename.replace(/\.md$/, '');
}

/**
 * Load all recipe files at build time using Vite's import.meta.glob
 * The ?raw query imports files as raw text strings
 */
const recipeFiles = import.meta.glob<string>('../../content/recipes/*.md', {
	eager: true,
	query: '?raw',
	import: 'default'
});

/**
 * Parse all recipes at module initialization
 * Throws at build time if any recipe has invalid frontmatter
 */
const allRecipes: Recipe[] = Object.entries(recipeFiles).map(([path, content]) => {
	const slug = getSlugFromPath(path);
	return parseRecipeFile(slug, content);
});

/**
 * Get all recipes with metadata only (no content)
 * Used for listing pages to minimize data transfer
 */
export function getAllRecipes(): RecipeMeta[] {
	return allRecipes.map(
		(recipe): RecipeMeta => ({
			slug: recipe.slug,
			title: recipe.title,
			subtitle: recipe.subtitle,
			category: recipe.category,
			difficulty: recipe.difficulty,
			active_time: recipe.active_time,
			total_time: recipe.total_time,
			serves: recipe.serves,
			tags: recipe.tags
		})
	);
}

/**
 * Get a single recipe by slug including rendered HTML content
 * @throws RecipeNotFoundError if slug doesn't exist
 */
export function getRecipeBySlug(slug: string): Recipe {
	const recipe = allRecipes.find((r) => r.slug === slug);

	if (!recipe) {
		throw new RecipeNotFoundError(slug);
	}

	return recipe;
}

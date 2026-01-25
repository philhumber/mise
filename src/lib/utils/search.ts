/**
 * Recipe search utility for Mïse
 *
 * Provides fuzzy search across recipe metadata using Fuse.js.
 *
 * ## Design Decision: Simple Function API
 *
 * We export a simple `searchRecipes(recipes, query)` function rather than
 * exposing the raw Fuse.js instance. This provides:
 *
 * - **Clean consumer code**: Components just get RecipeMeta[] back
 * - **Implementation hiding**: Can swap search libraries without breaking consumers
 * - **Consistent return type**: Same shape as getAllRecipes() for easy filtering
 *
 * ## Future Extensibility
 *
 * If advanced features are needed later (match highlighting, relevance scores),
 * consider adding:
 *
 * - `searchRecipesWithScores(recipes, query)` returning `{recipe, score}[]`
 * - Export the Fuse instance for power users: `export const recipeSearchIndex`
 *
 * ## Ingredient Search (Not Yet Implemented)
 *
 * Currently searches frontmatter fields only (title, tags, subtitle, category).
 * To add ingredient search, options include:
 *
 * 1. **Structured ingredients** (recommended): Parse ingredients from markdown
 *    into a dedicated frontmatter field or structured data during build
 * 2. **HTML parsing** (fragile): Extract text from rendered HTML - not recommended
 *    as it's brittle and couples search to markdown structure
 *
 * When implementing ingredient search, add an 'ingredients' key to the Fuse config
 * with appropriate weight (suggest 1.0, lower than title/tags).
 */

import Fuse, { type IFuseOptions } from 'fuse.js';
import type { RecipeMeta } from '$lib/types';

/**
 * Fuse.js configuration for recipe search
 *
 * Weights determine relative importance of matches in each field:
 * - title (0.3): Primary identifier, highest priority
 * - tags (0.3): Important for categorical searches ("sous-vide", "make-ahead")
 * - subtitle (0.2): Secondary description, often contains techniques/components
 * - category (0.2): Useful for "main", "dessert" type searches
 */
const searchOptions: IFuseOptions<RecipeMeta> = {
	keys: [
		{ name: 'title', weight: 0.3 },
		{ name: 'tags', weight: 0.3 },
		{ name: 'subtitle', weight: 0.2 },
		{ name: 'category', weight: 0.2 }
	],
	// Threshold: 0 = exact match, 1 = match anything
	// 0.4 provides good balance between precision and recall
	threshold: 0.4,
	// Search anywhere in the field, not just from the start
	ignoreLocation: true,
	// Minimum characters before search activates
	minMatchCharLength: 2
};

/**
 * Search recipes by query string
 *
 * Performs fuzzy search across recipe metadata fields (title, tags, subtitle, category).
 * Returns recipes sorted by relevance.
 *
 * @param recipes - Array of recipes to search within
 * @param query - Search query string
 * @returns Filtered array of recipes matching the query, sorted by relevance
 *
 * @example
 * ```typescript
 * const recipes = getAllRecipes();
 * const results = searchRecipes(recipes, 'cod');
 * // Returns recipes with "cod" in title, tags, subtitle, or category
 * ```
 *
 * @example
 * ```typescript
 * // Empty query returns all recipes (useful for "clear search" behavior)
 * const results = searchRecipes(recipes, '');
 * // Returns all recipes unchanged
 * ```
 */
export function searchRecipes(recipes: RecipeMeta[], query: string): RecipeMeta[] {
	const trimmedQuery = query.trim();

	// Return all recipes for empty query
	if (!trimmedQuery) {
		return recipes;
	}

	// Create fresh Fuse instance for search
	// For larger collections (100+ recipes), consider memoizing this
	const fuse = new Fuse(recipes, searchOptions);
	const results = fuse.search(trimmedQuery);

	// Extract recipe objects from Fuse results
	return results.map((result) => result.item);
}

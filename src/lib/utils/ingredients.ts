/**
 * Ingredient parsing utility for Mise
 *
 * Extracts ingredient groups from rendered recipe HTML.
 * Each group is an <h3> heading followed by a <ul> list within the Ingredients section.
 *
 * Uses regex parsing to support SSR (DOMParser is browser-only).
 */

import type { IngredientGroup } from '$lib/types';

/**
 * Parse ingredient groups from rendered HTML content
 *
 * Looks for the ## Ingredients section and extracts h3 headings
 * followed by ul lists. Each h3+ul pair becomes an IngredientGroup.
 * Returns an empty array if no Ingredients section exists.
 *
 * Uses regex parsing to work during SSR (no DOMParser dependency).
 *
 * @param htmlContent - Rendered HTML from recipe.content
 * @returns Array of ingredient groups with names and ingredient lists
 */
export function parseIngredients(htmlContent: string): IngredientGroup[] {
	const groups: IngredientGroup[] = [];

	// Find Ingredients section: <h2>Ingredients</h2> ... next <h2> or end
	const ingredientsSectionMatch = htmlContent.match(
		/<h2[^>]*>Ingredients<\/h2>([\s\S]*?)(?=<h2|$)/i
	);

	if (!ingredientsSectionMatch) {
		return groups;
	}

	const sectionContent = ingredientsSectionMatch[1];

	// Match h3 + ul pairs: <h3 id="...">Name</h3><ul>...</ul>
	// Use [\s\S]*? for non-greedy matching across newlines
	const groupPattern = /<h3[^>]*>([^<]+)<\/h3>\s*<ul>([\s\S]*?)<\/ul>/gi;
	let groupMatch;

	while ((groupMatch = groupPattern.exec(sectionContent)) !== null) {
		const groupName = groupMatch[1].trim();
		const ulContent = groupMatch[2];

		// Extract list items - use simple pattern, strip tags after
		const liPattern = /<li>([\s\S]*?)<\/li>/gi;
		const ingredients: string[] = [];
		let liMatch;

		while ((liMatch = liPattern.exec(ulContent)) !== null) {
			// Strip HTML tags, clean whitespace
			const ingredient = liMatch[1].replace(/<[^>]*>/g, '').trim();
			if (ingredient) {
				ingredients.push(ingredient);
			}
		}

		if (ingredients.length > 0) {
			groups.push({
				name: groupName,
				ingredients
			});
		}
	}

	return groups;
}

/**
 * Recipe type definitions for Mïse
 */

export type RecipeCategory = 'main' | 'starter' | 'dessert' | 'side' | 'drink' | 'sauce';

export type Difficulty = 'easy' | 'intermediate' | 'advanced';

/**
 * Recipe metadata from frontmatter - used for list views
 */
export interface RecipeMeta {
	slug: string;
	title: string;
	subtitle?: string;
	category: RecipeCategory;
	difficulty: Difficulty;
	active_time: string;
	total_time: string;
	serves: number;
	tags: string[];
}

/**
 * Ingredient group parsed from markdown sections
 */
export interface IngredientGroup {
	name: string;
	ingredients: string[];
}

/**
 * Full recipe with metadata and content
 */
export interface Recipe extends RecipeMeta {
	content: string;
}

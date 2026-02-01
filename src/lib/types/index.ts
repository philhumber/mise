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

/**
 * Timeline item parsed from recipe content
 */
export interface TimelineItem {
	/** Timeline marker (e.g., "T-48h", "Day-of", "Service") */
	marker: string;
	/** Description of what happens at this stage */
	description: string;
	/** Anchor ID for linking to method section (e.g., "t-48h") */
	anchorId: string;
}

// === VALIDATION ===

export interface ValidationError {
	field: string;
	message: string;
}

// === MEAL TYPES ===

export interface AggregatedIngredient {
	display: string;
	breakdown: IngredientBreakdown[];
}

export interface IngredientBreakdown {
	component: string;
	recipe: string;
	qty: string;
}

export interface RecipeSnapshot {
	slug: string;
	course_order: number;
	title: string;
	subtitle?: string;
	metadata: {
		serves: number;
		active_time: string;
		total_time: string;
		category: RecipeCategory;
		difficulty: Difficulty;
	};
	components: string[];
	ingredients: Record<string, string[]>;
	timeline: Record<string, Record<string, string[]>>;
	is_deleted: boolean;
}

export interface MealSnapshot {
	recipes: RecipeSnapshot[];
	aggregated_ingredients: AggregatedIngredient[];
	timeline_markers: string[];
	last_snapshot_at: string;
}

export interface MealMeta {
	id: number;
	slug: string;
	title: string;
	description?: string;
	recipe_count: number;
	timeline_span: string;
	is_stale: boolean;
	created_at: string;
	updated_at: string;
}

export interface Meal extends MealMeta {
	snapshot: MealSnapshot;
}

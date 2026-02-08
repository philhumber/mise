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

/**
 * Cook mode step parsed from recipe method section
 */
export interface CookModeStep {
	/** Step number (1, 2, 3...) */
	number: number;
	/** Timeline marker in display format ("T – 24 HOURS", "SERVICE", "T – 1 HOUR") */
	marker: string;
	/** Step title extracted from bold text */
	title: string;
	/** Plain text body (HTML tags stripped) */
	body: string;
	/** Original HTML content for rich display */
	htmlContent: string;
}

// === TIMER TYPES ===

export type TimerState = 'running' | 'paused' | 'completed';

export interface Timer {
	id: string;
	label: string;
	durationSeconds: number;
	remainingSeconds: number;
	state: TimerState;
	/** Timestamp (ms) when the timer was last started/resumed */
	startedAt: number;
	/** Accumulated elapsed seconds before last pause */
	elapsedBeforePause: number;
	/** Step number where timer was created */
	stepNumber: number;
}

export interface DetectedDuration {
	seconds: number;
	label: string;
	matchedText: string;
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

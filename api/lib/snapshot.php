<?php
/**
 * Meal Snapshot Generator
 *
 * Generates JSONB snapshots for meals by aggregating recipe data.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/recipe-parser.php';
require_once __DIR__ . '/timeline-parser.php';
require_once __DIR__ . '/sanitize.php';

class SnapshotGenerationException extends Exception {}

/**
 * Generate a meal snapshot from recipe slugs
 * MUST be wrapped in database transaction by caller
 *
 * @param string[] $recipeSlugs Array of recipe slugs in course order
 * @return array Snapshot data structure
 * @throws SnapshotGenerationException if any recipe is not found
 */
function generateMealSnapshot(array $recipeSlugs): array
{
    $recipes = [];
    $allIngredients = [];
    $allMarkers = [];

    foreach ($recipeSlugs as $index => $slug) {
        // Fetch recipe from database
        $recipe = dbQueryOne("
            SELECT id, slug, title, subtitle, category, difficulty,
                   serves, active_time, total_time, markdown
            FROM recipes
            WHERE slug = :slug AND deleted_at IS NULL
        ", ['slug' => $slug]);

        if (!$recipe) {
            throw new SnapshotGenerationException("Recipe not found: $slug");
        }

        // Parse ingredients and timeline from markdown
        $ingredients = extractIngredients($recipe['markdown']);
        $timeline = extractTimeline($recipe['markdown']);

        // Build recipe snapshot object
        $recipeSnapshot = [
            'slug' => $slug,
            'course_order' => $index + 1,
            'title' => escapeHtml($recipe['title']),
            'subtitle' => $recipe['subtitle'] ? escapeHtml($recipe['subtitle']) : null,
            'metadata' => [
                'serves' => (int) $recipe['serves'],
                'active_time' => $recipe['active_time'],
                'total_time' => $recipe['total_time'],
                'category' => $recipe['category'],
                'difficulty' => $recipe['difficulty'],
            ],
            'components' => array_keys($ingredients),
            'ingredients' => $ingredients, // Already sanitized in parser
            'timeline' => $timeline, // Already sanitized in parser
            'is_deleted' => false,
        ];

        $recipes[] = $recipeSnapshot;

        // Collect for aggregation
        $allIngredients = array_merge(
            $allIngredients,
            flattenIngredients($ingredients, $recipe['title'])
        );
        $allMarkers = array_merge($allMarkers, array_keys($timeline));
    }

    // Sort and dedupe markers
    $uniqueMarkers = array_unique($allMarkers);
    $sortedMarkers = sortTimelineMarkers($uniqueMarkers);

    return [
        'recipes' => $recipes,
        'aggregated_ingredients' => aggregateIngredients($allIngredients),
        'timeline_markers' => $sortedMarkers,
        'last_snapshot_at' => date('c'),
    ];
}

/**
 * Flatten ingredients with recipe context for aggregation
 */
function flattenIngredients(array $ingredients, string $recipeTitle): array
{
    $flat = [];
    foreach ($ingredients as $component => $items) {
        foreach ($items as $item) {
            $flat[] = [
                'display' => $item,
                'component' => $component,
                'recipe' => $recipeTitle,
            ];
        }
    }
    return $flat;
}

/**
 * Aggregate ingredients - combine matching items
 * Conservative approach: only combine if EXACT match (case-insensitive)
 */
function aggregateIngredients(array $flatIngredients): array
{
    // For now, don't aggregate - just group by display
    // Future: implement quantity parsing and smart aggregation

    $aggregated = [];
    foreach ($flatIngredients as $item) {
        $key = strtolower($item['display']);

        if (!isset($aggregated[$key])) {
            $aggregated[$key] = [
                'display' => $item['display'],
                'breakdown' => [],
            ];
        }

        $aggregated[$key]['breakdown'][] = [
            'component' => $item['component'],
            'recipe' => $item['recipe'],
            'qty' => $item['display'],
        ];
    }

    return array_values($aggregated);
}

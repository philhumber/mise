<?php
/**
 * Migration: Re-render HTML content for all recipes
 *
 * Run this script once after updating markdown.php to fix ordered list numbering.
 *
 * Usage: php migrate-rerender-html.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/markdown.php';
require_once __DIR__ . '/lib/sanitize.php';

echo "Re-rendering HTML for all recipes...\n\n";

// Get all recipes with their markdown
$recipes = dbQueryAll('
    SELECT id, slug, markdown
    FROM recipes
    WHERE deleted_at IS NULL
');

$count = count($recipes);
echo "Found {$count} recipes to process.\n\n";

$updated = 0;
$errors = 0;

foreach ($recipes as $recipe) {
    echo "Processing: {$recipe['slug']}... ";

    try {
        // Parse frontmatter to get just the content
        $parsed = parseFrontmatter($recipe['markdown']);

        if (!$parsed) {
            echo "SKIP (invalid frontmatter)\n";
            $errors++;
            continue;
        }

        // Re-render markdown to HTML
        $html = markdownToHtml($parsed['content']);
        $sanitizedHtml = sanitizeHtml($html);

        // Update the database
        dbExecute(
            'UPDATE recipes SET content = :content WHERE id = :id',
            ['content' => $sanitizedHtml, 'id' => $recipe['id']]
        );

        echo "OK\n";
        $updated++;
    } catch (Exception $e) {
        echo "ERROR: {$e->getMessage()}\n";
        $errors++;
    }
}

echo "\n";
echo "Done! Updated: {$updated}, Errors: {$errors}\n";

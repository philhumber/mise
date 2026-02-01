<?php
/**
 * Recipe Parser
 *
 * Parses recipe markdown to extract ingredients by component.
 * Supports 3 formats: kombu-cod, section-based, and simple.
 */

require_once __DIR__ . '/sanitize.php';

/**
 * Detect recipe format based on markdown structure
 */
function detectRecipeFormat(string $markdown): string
{
    // Format 1: Kombu-Cod (uppercase INGREDIENTS + METHOD BY TIMELINE)
    if (preg_match('/^## INGREDIENTS$/m', $markdown) &&
        preg_match('/^## METHOD BY TIMELINE$/m', $markdown)) {
        return 'kombu-cod';
    }

    // Format 2: Section-based (Component N — with tables)
    if (preg_match('/^## Component \d+ [—–-]/m', $markdown)) {
        return 'section-based';
    }

    // Format 3: Simple (title case Ingredients, no timeline markers)
    if (preg_match('/^## Ingredients$/m', $markdown)) {
        return 'simple';
    }

    // Fallback: default to simple
    return 'simple';
}

/**
 * Extract ingredients from recipe markdown
 * Returns: ['ComponentName' => ['ingredient1', 'ingredient2', ...]]
 */
function extractIngredients(string $markdown): array
{
    $format = detectRecipeFormat($markdown);

    return match ($format) {
        'kombu-cod' => parseKombuCodIngredients($markdown),
        'section-based' => parseSectionBasedIngredients($markdown),
        'simple' => parseSimpleIngredients($markdown),
    };
}

/**
 * Parse kombu-cod style: ## INGREDIENTS with ### Component subsections
 * Ingredient formats:
 * - Standard: "200g butter, softened"
 * - Colon: "Fine sea salt: 6g (1.5% of skinned weight)"
 * - Range: "Agar powder: 1.2–1.5g (0.8–1%)"
 */
function parseKombuCodIngredients(string $markdown): array
{
    $ingredients = [];

    // Find ## INGREDIENTS section
    if (!preg_match('/## INGREDIENTS\s*\n(.*?)(?=\n## )/s', $markdown, $match)) {
        return [];
    }

    $section = $match[1];
    $currentComponent = 'Main';
    $ingredients[$currentComponent] = [];

    // Split by ### headers
    $parts = preg_split('/^### (.+)$/m', $section, -1, PREG_SPLIT_DELIM_CAPTURE);

    for ($i = 0; $i < count($parts); $i++) {
        $part = trim($parts[$i]);

        // Check if this is a component header (odd indices after split)
        if ($i % 2 === 1) {
            $currentComponent = $part;
            $ingredients[$currentComponent] = [];
        } elseif (!empty($part)) {
            // Parse ingredient lines
            $lines = preg_split('/\n/', $part);
            foreach ($lines as $line) {
                $line = trim($line);
                if (preg_match('/^[-*]\s*(.+)$/', $line, $m)) {
                    $ingredients[$currentComponent][] = escapeHtml(trim($m[1]));
                }
            }
        }
    }

    // Remove empty components
    return array_filter($ingredients, fn($items) => !empty($items));
}

/**
 * Parse section-based style: ## Component N with ### Ingredients tables
 * Table format: | Ingredient | Amount | Notes |
 */
function parseSectionBasedIngredients(string $markdown): array
{
    $ingredients = [];

    // Find each ## Component N section
    preg_match_all('/## Component \d+ [—–-] ([^\n]+)\n(.*?)(?=\n## |$)/s', $markdown, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $componentName = trim($match[1]);
        $section = $match[2];

        // Find ### Ingredients subsection with table
        if (preg_match('/### Ingredients\s*\n(.*?)(?=\n### |$)/s', $section, $ingMatch)) {
            $tableSection = $ingMatch[1];
            $ingredients[$componentName] = parseIngredientTable($tableSection);
        }
    }

    return $ingredients;
}

/**
 * Parse markdown table into ingredient strings
 * Combines columns: "150g silken tofu, drained"
 */
function parseIngredientTable(string $tableMarkdown): array
{
    $items = [];
    $lines = explode("\n", $tableMarkdown);

    foreach ($lines as $line) {
        $line = trim($line);
        // Skip header row, separator row, empty lines
        if (empty($line) || strpos($line, '---') !== false || preg_match('/^\|?\s*Ingredient/i', $line)) {
            continue;
        }

        // Parse table row: | Item | Amount | Notes |
        if (preg_match('/^\|?\s*([^|]+)\|([^|]+)\|?([^|]*)\|?/', $line, $m)) {
            $item = trim($m[1]);
            $amount = trim($m[2]);
            $notes = isset($m[3]) ? trim($m[3]) : '';

            if (empty($item) || $item === '---') {
                continue;
            }

            // Combine into single string: "150 g silken tofu, drained"
            $combined = $amount . ' ' . $item;
            if (!empty($notes)) {
                $combined .= ', ' . strtolower($notes);
            }

            $items[] = escapeHtml(trim($combined));
        }
    }

    return $items;
}

/**
 * Parse simple style: ## Ingredients with optional **Bold** pseudo-components
 */
function parseSimpleIngredients(string $markdown): array
{
    $ingredients = [];

    // Normalize line endings for Windows compatibility
    $markdown = str_replace("\r\n", "\n", $markdown);
    $markdown = str_replace("\r", "\n", $markdown);

    // Find ## Ingredients section (case-insensitive to handle ## INGREDIENTS too)
    if (!preg_match('/##\s*Ingredients\s*\n(.*?)(?=\n##\s|$)/si', $markdown, $match)) {
        return [];
    }

    $section = trim($match[1]);
    $currentComponent = 'Main';
    $ingredients[$currentComponent] = [];

    // Split by lines, handling any remaining \r characters
    $lines = preg_split('/\r?\n/', $section);
    foreach ($lines as $line) {
        $line = trim($line);

        // Check for ### Header style components (e.g., ### Miso Cure)
        if (preg_match('/^#{2,3}\s+(.+)/', $line, $m)) {
            $currentComponent = trim($m[1]);
            $ingredients[$currentComponent] = [];
            continue;
        }

        // Check for **Bold** pseudo-component headers (skip them as group labels)
        // Allow optional trailing colon, em-dash continuation, or other text after the bold
        if (preg_match('/^\*\*([^*]+)\*\*/', $line, $m)) {
            $currentComponent = trim($m[1]);
            $ingredients[$currentComponent] = [];
            continue;
        }

        // Parse ingredient lines
        if (preg_match('/^[-*]\s*(.+)$/', $line, $m)) {
            $ingredients[$currentComponent][] = escapeHtml(trim($m[1]));
        }
    }

    // Remove empty components
    return array_filter($ingredients, fn($items) => !empty($items));
}

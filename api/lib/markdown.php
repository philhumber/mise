<?php
/**
 * Markdown Parsing Utilities
 *
 * Simple markdown parsing for recipe content.
 * For production, consider using Parsedown or league/commonmark.
 */

/**
 * Parse YAML frontmatter from markdown content
 *
 * @param string $markdown Full markdown with frontmatter
 * @return array{frontmatter: array, content: string}|null Returns null if no valid frontmatter found
 */
function parseFrontmatter(string $markdown): ?array
{
    // Normalize line endings
    $markdown = str_replace("\r\n", "\n", $markdown);
    $markdown = trim($markdown);

    // Check for frontmatter delimiters
    if (!str_starts_with($markdown, '---')) {
        return null;
    }

    // Find the closing delimiter
    $endPos = strpos($markdown, "\n---", 3);
    if ($endPos === false) {
        return null;
    }

    // Extract frontmatter YAML
    $yamlContent = substr($markdown, 3, $endPos - 3);
    $content = trim(substr($markdown, $endPos + 4));

    // Parse YAML
    $frontmatter = parseSimpleYaml($yamlContent);
    if ($frontmatter === null) {
        return null;
    }

    return [
        'frontmatter' => $frontmatter,
        'content' => $content,
    ];
}

/**
 * Simple YAML parser for frontmatter
 *
 * Handles the subset of YAML used in recipe frontmatter:
 * - Key-value pairs (strings, numbers)
 * - Arrays (inline [a, b, c] format)
 * - Quoted strings
 *
 * @param string $yaml
 * @return array|null
 */
function parseSimpleYaml(string $yaml): ?array
{
    $result = [];
    $lines = explode("\n", $yaml);

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip empty lines and comments
        if (empty($line) || str_starts_with($line, '#')) {
            continue;
        }

        // Parse key: value
        if (!preg_match('/^([a-z_]+):\s*(.*)$/i', $line, $matches)) {
            continue;
        }

        $key = $matches[1];
        $value = trim($matches[2]);

        // Parse the value
        $result[$key] = parseYamlValue($value);
    }

    return $result;
}

/**
 * Parse a YAML value
 *
 * @param string $value
 * @return mixed
 */
function parseYamlValue(string $value)
{
    // Empty value
    if ($value === '') {
        return '';
    }

    // Array: [item1, item2, item3]
    if (preg_match('/^\[(.*)\]$/', $value, $matches)) {
        $items = [];
        // Split by comma, respecting quoted strings
        preg_match_all('/(?:[^,"]|"[^"]*")+/', $matches[1], $itemMatches);
        foreach ($itemMatches[0] as $item) {
            $item = trim($item);
            if (!empty($item)) {
                $items[] = parseYamlValue($item);
            }
        }
        return $items;
    }

    // Quoted string: 'value' or "value"
    if (preg_match('/^[\'"](.*)[\'"]/s', $value, $matches)) {
        return $matches[1];
    }

    // Integer
    if (preg_match('/^-?\d+$/', $value)) {
        return (int) $value;
    }

    // Float
    if (preg_match('/^-?\d+\.\d+$/', $value)) {
        return (float) $value;
    }

    // Boolean
    if (strtolower($value) === 'true') {
        return true;
    }
    if (strtolower($value) === 'false') {
        return false;
    }

    // Plain string
    return $value;
}

/**
 * Convert markdown to HTML
 *
 * Basic markdown-to-HTML conversion. Handles:
 * - Headings (# to ######)
 * - Paragraphs
 * - Bold (**text**)
 * - Italic (*text* or _text_)
 * - Lists (- item or 1. item)
 * - Code blocks (```)
 * - Inline code (`code`)
 * - Links [text](url)
 * - Horizontal rules (---)
 *
 * @param string $markdown
 * @return string HTML
 */
function markdownToHtml(string $markdown): string
{
    // Normalize line endings
    $markdown = str_replace("\r\n", "\n", $markdown);

    // Escape HTML entities first
    $html = htmlspecialchars($markdown, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Code blocks (must be before other processing)
    $html = preg_replace_callback(
        '/```(\w*)\n(.*?)```/s',
        function ($matches) {
            $lang = $matches[1] ?: '';
            $code = htmlspecialchars_decode($matches[2]);
            return "<pre><code" . ($lang ? " class=\"language-$lang\"" : "") . ">$code</code></pre>";
        },
        $html
    );

    // Inline code
    $html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html);

    // Headings (process before paragraphs)
    $html = preg_replace_callback(
        '/^(#{1,6})\s+(.+)$/m',
        function ($matches) {
            $level = strlen($matches[1]);
            $text = trim($matches[2]);
            // Lowercase FIRST, then replace non-alphanumeric with dashes
            $id = preg_replace('/[^a-z0-9]+/', '-', strtolower($text));
            $id = trim($id, '-');
            return "<h$level id=\"$id\">$text</h$level>";
        },
        $html
    );

    // Horizontal rules
    $html = preg_replace('/^---+$/m', '<hr>', $html);

    // Bold
    $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);

    // Italic (single asterisk or underscore)
    $html = preg_replace('/(?<!\*)\*([^*]+)\*(?!\*)/', '<em>$1</em>', $html);
    $html = preg_replace('/(?<!_)_([^_]+)_(?!_)/', '<em>$1</em>', $html);

    // Links
    $html = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $html);

    // Unordered lists
    $html = preg_replace_callback(
        '/^(- .+(?:\n- .+)*)/m',
        function ($matches) {
            $items = preg_replace('/^- (.+)$/m', '<li>$1</li>', $matches[1]);
            return "<ul>\n$items\n</ul>";
        },
        $html
    );

    // Ordered lists
    $html = preg_replace_callback(
        '/^(\d+\. .+(?:\n\d+\. .+)*)/m',
        function ($matches) {
            $items = preg_replace('/^\d+\. (.+)$/m', '<li>$1</li>', $matches[1]);
            return "<ol>\n$items\n</ol>";
        },
        $html
    );

    // Paragraphs (double newlines)
    $paragraphs = preg_split('/\n\n+/', $html);
    $processed = [];

    foreach ($paragraphs as $para) {
        $para = trim($para);
        if (empty($para)) {
            continue;
        }

        // Don't wrap if already a block element
        if (preg_match('/^<(h[1-6]|ul|ol|pre|hr|blockquote)/', $para)) {
            $processed[] = $para;
        } else {
            // Convert single newlines to <br> within paragraphs
            $para = str_replace("\n", "<br>\n", $para);
            $processed[] = "<p>$para</p>";
        }
    }

    return implode("\n\n", $processed);
}

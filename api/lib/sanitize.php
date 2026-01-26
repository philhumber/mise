<?php
/**
 * HTML Sanitization
 *
 * Sanitizes HTML content to prevent XSS attacks.
 * Uses a whitelist approach - only allowed tags and attributes are kept.
 */

/**
 * Allowed HTML tags for recipe content
 */
const ALLOWED_TAGS = [
    'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
    'p', 'br', 'hr',
    'strong', 'b', 'em', 'i', 'u',
    'ul', 'ol', 'li',
    'a',
    'code', 'pre',
    'blockquote',
    'table', 'thead', 'tbody', 'tr', 'th', 'td',
];

/**
 * Allowed attributes per tag
 */
const ALLOWED_ATTRIBUTES = [
    'a' => ['href'],
    'h1' => ['id'],
    'h2' => ['id'],
    'h3' => ['id'],
    'h4' => ['id'],
    'h5' => ['id'],
    'h6' => ['id'],
];

/**
 * Sanitize HTML content
 *
 * Removes all tags and attributes not in the whitelist.
 * Also removes javascript: URLs and other dangerous content.
 *
 * @param string $html Raw HTML content
 * @return string Sanitized HTML
 */
function sanitizeHtml(string $html): string
{
    // First, use strip_tags with allowed tags list
    $allowedTagsString = '<' . implode('><', ALLOWED_TAGS) . '>';
    $html = strip_tags($html, $allowedTagsString);

    // Parse and clean with DOMDocument for attribute filtering
    if (empty(trim($html))) {
        return '';
    }

    // Suppress errors for malformed HTML
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);

    // Wrap in container to handle fragment
    $html = '<div>' . $html . '</div>';
    $dom->loadHTML(
        '<?xml encoding="UTF-8">' . $html,
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();

    // Process all elements
    $xpath = new DOMXPath($dom);
    $elements = $xpath->query('//*');

    foreach ($elements as $element) {
        if (!($element instanceof DOMElement)) {
            continue;
        }

        $tagName = strtolower($element->tagName);

        // Skip the wrapper div
        if ($tagName === 'div' && $element->parentNode === $dom) {
            continue;
        }

        // Get allowed attributes for this tag
        $allowedAttrs = ALLOWED_ATTRIBUTES[$tagName] ?? [];

        // Remove disallowed attributes
        $attrsToRemove = [];
        foreach ($element->attributes as $attr) {
            if (!in_array($attr->name, $allowedAttrs, true)) {
                $attrsToRemove[] = $attr->name;
            }
        }

        foreach ($attrsToRemove as $attrName) {
            $element->removeAttribute($attrName);
        }

        // Sanitize href attributes (remove javascript:, data:, etc.)
        if ($element->hasAttribute('href')) {
            $href = $element->getAttribute('href');
            if (!isValidUrl($href)) {
                $element->removeAttribute('href');
            }
        }
    }

    // Extract the inner HTML of our wrapper
    $result = '';
    $wrapper = $xpath->query('//div')->item(0);
    if ($wrapper) {
        foreach ($wrapper->childNodes as $child) {
            $result .= $dom->saveHTML($child);
        }
    }

    return trim($result);
}

/**
 * Check if a URL is valid and safe
 *
 * @param string $url
 * @return bool
 */
function isValidUrl(string $url): bool
{
    $url = trim($url);

    // Allow relative URLs starting with # (anchors)
    if (str_starts_with($url, '#')) {
        return true;
    }

    // Allow relative URLs starting with /
    if (str_starts_with($url, '/')) {
        return true;
    }

    // Parse the URL
    $parsed = parse_url($url);

    // Must have a scheme for absolute URLs
    if (!isset($parsed['scheme'])) {
        return false;
    }

    // Only allow http and https
    $scheme = strtolower($parsed['scheme']);
    return in_array($scheme, ['http', 'https'], true);
}

/**
 * Convert plain text to safe HTML (escapes all HTML entities)
 *
 * @param string $text
 * @return string
 */
function escapeHtml(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

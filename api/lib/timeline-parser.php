<?php
/**
 * Timeline Parser
 *
 * Parses recipe markdown to extract timeline-based method steps.
 * Normalizes various timeline marker formats to canonical markers.
 */

require_once __DIR__ . '/recipe-parser.php';
require_once __DIR__ . '/sanitize.php';

// Special markers that aren't time-based
const SPECIAL_MARKERS = ['Day-of', 'Service'];

/**
 * Normalize any timeline marker to a standardized format
 * Preserves actual time values (T-90m, T-2h, T-3d, etc.) instead of rounding to buckets
 */
function normalizeTimelineMarker(string $marker): string
{
    $marker = trim($marker);

    // Strip "Method" prefix if present (for inline markers like "Method (T – 24 h)")
    $marker = preg_replace('/^Method\s*\(/i', '', $marker);
    $marker = preg_replace('/\)$/', '', $marker);

    // Strip qualifiers like "or earlier"
    $marker = preg_replace('/\s+or\s+earlier/i', '', $marker);

    // Handle "DAY OF" variations (Day of, Day-of, Dayof, day of, etc.)
    if (preg_match('/day\s*[-–—]?\s*of/i', $marker)) {
        return 'Day-of';
    }

    // Handle "SERVICE" or "PLATING" variations
    if (preg_match('/^(service|plating)$/i', $marker)) {
        return 'Service';
    }

    // Handle T-0 as Service
    if (preg_match('/T\s*[-–—]\s*0\b/', $marker)) {
        return 'Service';
    }

    // Extract time value and unit from T-X patterns
    // Handles: "T – 48 HOURS", "T – 24 h", "T-6h", "T – 1 d", "T-90m", "T – 90 min"
    if (preg_match('/T\s*[-–—]\s*(\d+)\s*(m|min|mins|minutes?|h|hours?|d|days?)/i', $marker, $m)) {
        $value = (int) $m[1];
        $unit = strtolower($m[2][0]); // 'm', 'h', or 'd'

        // Standardize to shortest format: T-90m, T-24h, T-2d
        if ($unit === 'm') {
            // Keep minutes as-is unless it's a round hour
            if ($value % 60 === 0) {
                return 'T-' . ($value / 60) . 'h';
            }
            return 'T-' . $value . 'm';
        } elseif ($unit === 'd') {
            // Convert days to hours for consistency, unless it's a clean day value
            $hours = $value * 24;
            return 'T-' . $hours . 'h';
        } else {
            // Hours - keep as-is
            return 'T-' . $value . 'h';
        }
    }

    // Handle bare T-X patterns without unit (assume hours)
    if (preg_match('/T\s*[-–—]\s*(\d+)\s*$/i', $marker, $m)) {
        $value = (int) $m[1];
        return 'T-' . $value . 'h';
    }

    // Unknown format - default to Service
    return 'Service';
}

/**
 * Extract minutes from a timeline marker for sorting
 * Returns minutes before service (higher = earlier in timeline)
 */
function getMarkerMinutes(string $marker): int
{
    // Special markers - Day-of is right before Service
    if ($marker === 'Service') {
        return 0;
    }
    if ($marker === 'Day-of') {
        return 30; // 30 minutes, between T-1h and Service
    }

    // Parse T-Xm or T-Xh format
    if (preg_match('/T-(\d+)(m|h)/', $marker, $m)) {
        $value = (int) $m[1];
        $unit = $m[2];

        if ($unit === 'm') {
            return $value;
        } else {
            return $value * 60;
        }
    }

    // Unknown - put at end
    return -1;
}

/**
 * Extract timeline from recipe markdown
 * Returns: ['T-24h' => ['Component' => ['step1', 'step2']], ...]
 */
function extractTimeline(string $markdown): array
{
    $format = detectRecipeFormat($markdown);

    $timeline = match ($format) {
        'kombu-cod' => parseKombuCodTimeline($markdown),
        'section-based' => parseSectionBasedTimeline($markdown),
        'simple' => [],
    };

    // If no timeline found, try generic fallback parsing
    if (empty($timeline)) {
        $timeline = parseGenericTimeline($markdown);
    }

    // Final fallback: look for ANY Method headers with timeline markers
    if (empty($timeline)) {
        $timeline = parseInlineMethodTimeline($markdown);
    }

    // Ultimate fallback: if still no timeline, look for simple ## Method section
    // and put all steps under T-1h
    if (empty($timeline)) {
        $timeline = parseSimpleMethodAsDefault($markdown);
    }

    return $timeline;
}

/**
 * Parse a simple ## Method section without timeline markers
 * Assigns all steps to T-1h as default
 */
function parseSimpleMethodAsDefault(string $markdown): array
{
    $timeline = [];

    // Normalize line endings
    $markdown = str_replace("\r\n", "\n", $markdown);
    $markdown = str_replace("\r", "\n", $markdown);

    // Look for ## Method section (case-insensitive)
    if (!preg_match('/##\s*Method\s*\n(.*?)(?=\n##\s|$)/si', $markdown, $match)) {
        return [];
    }

    $methodSection = $match[1];

    // Parse numbered steps
    $steps = parseNumberedSteps($methodSection);

    if (!empty($steps)) {
        $cleanSteps = array_map(
            fn($s) => cleanAndTruncateStep($s),
            $steps
        );
        $timeline['T-1h'] = ['Main' => $cleanSteps];
    }

    return $timeline;
}

/**
 * Parse inline Method (T – XX) headers anywhere in the document
 * Handles recipes that don't follow Component structure
 */
function parseInlineMethodTimeline(string $markdown): array
{
    $timeline = [];

    // Find ### Method (timeline) patterns anywhere
    // Captures: component context (from preceding ##) and timeline marker
    preg_match_all(
        '/(?:^## ([^\n]+)\n)?.*?### Method\s*\(([^)]+)\)\s*\n(.*?)(?=\n### |\n## |$)/sm',
        $markdown,
        $matches,
        PREG_SET_ORDER
    );

    // Simpler approach: just find all ### Method (timeline) headers
    preg_match_all(
        '/### Method\s*\(([^)]+)\)\s*\n(.*?)(?=\n### |\n## |$)/s',
        $markdown,
        $simpleMatches,
        PREG_SET_ORDER
    );

    foreach ($simpleMatches as $match) {
        $markerRaw = trim($match[1]);
        $content = $match[2];

        $marker = normalizeTimelineMarker($markerRaw);

        if (!isset($timeline[$marker])) {
            $timeline[$marker] = [];
        }

        // Try to find component context from preceding ## header
        $componentName = 'Main';

        // Parse numbered steps
        $steps = parseNumberedSteps($content);
        if (!empty($steps)) {
            if (!isset($timeline[$marker][$componentName])) {
                $timeline[$marker][$componentName] = [];
            }
            // Clean and sanitize steps
            $cleanSteps = array_map(
                fn($s) => cleanAndTruncateStep($s),
                $steps
            );
            $timeline[$marker][$componentName] = array_merge(
                $timeline[$marker][$componentName],
                $cleanSteps
            );
        }
    }

    return $timeline;
}

/**
 * Generic fallback: look for any ### headers with timeline-like markers
 */
function parseGenericTimeline(string $markdown): array
{
    $timeline = [];

    // Look for any ### header that looks like a timeline marker
    // Matches: ### T-24h, ### T – 48 HOURS, ### Day of, ### SERVICE, etc.
    preg_match_all('/^###\s+([^\n]+)$/m', $markdown, $headerMatches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

    foreach ($headerMatches as $i => $match) {
        $header = trim($match[1][0]);
        $headerOffset = $match[0][1];

        // Check if this looks like a timeline marker
        if (!looksLikeTimelineMarker($header)) {
            continue;
        }

        $normalized = normalizeTimelineMarker($header);

        // Find content until next ### or ## header
        $nextOffset = isset($headerMatches[$i + 1])
            ? $headerMatches[$i + 1][0][1]
            : strlen($markdown);

        $content = substr($markdown, $headerOffset + strlen($match[0][0]), $nextOffset - $headerOffset - strlen($match[0][0]));

        if (!isset($timeline[$normalized])) {
            $timeline[$normalized] = [];
        }

        // Parse the content for steps
        parseTimelineContent($content, $timeline[$normalized]);
    }

    return $timeline;
}

/**
 * Check if a header looks like a timeline marker
 */
function looksLikeTimelineMarker(string $header): bool
{
    // T-XX patterns (T-24h, T – 48 HOURS, etc.)
    if (preg_match('/^T\s*[-–—]\s*\d+/i', $header)) {
        return true;
    }

    // Day of / Day-of patterns
    if (preg_match('/day\s*[-–—]?\s*of/i', $header)) {
        return true;
    }

    // Service / Plating
    if (preg_match('/^(service|plating|to\s*serve)/i', $header)) {
        return true;
    }

    return false;
}

/**
 * Parse kombu-cod style: ## METHOD BY TIMELINE with ### T-XX subsections
 * Steps are numbered bold headers: **1. Step Name**
 */
function parseKombuCodTimeline(string $markdown): array
{
    $timeline = [];

    // Find ## METHOD BY TIMELINE section (case-insensitive for flexibility)
    if (!preg_match('/## METHOD BY TIMELINE\s*\n(.*?)(?=\n## |$)/si', $markdown, $match)) {
        return [];
    }

    $section = $match[1];

    // Split by timeline marker headers (### T – XX or ### DAY OF or ### SERVICE)
    $parts = preg_split('/^### ([^\n]+)$/m', $section, -1, PREG_SPLIT_DELIM_CAPTURE);

    for ($i = 1; $i < count($parts); $i += 2) {
        $header = trim($parts[$i]);
        $content = isset($parts[$i + 1]) ? trim($parts[$i + 1]) : '';

        // Skip "TIMELINE SUMMARY", "AT SERVICE YOU ARE ONLY", and other non-timeline sections
        if (preg_match('/timeline\s*summary|at\s+service\s+you\s+are/i', $header)) {
            continue;
        }

        // Check if this looks like a timeline marker
        if (!looksLikeTimelineMarker($header)) {
            continue;
        }

        // Normalize the timeline marker
        $normalized = normalizeTimelineMarker($header);

        if (!isset($timeline[$normalized])) {
            $timeline[$normalized] = [];
        }

        // Parse content for #### Component headers and numbered steps
        parseTimelineContent($content, $timeline[$normalized]);
    }

    return $timeline;
}

/**
 * Parse section-based style: inline timeline markers in ### Method (T – XX) headers
 */
function parseSectionBasedTimeline(string $markdown): array
{
    $timeline = [];

    // Find each ## Component N section
    // Character class: em-dash, en-dash, hyphen (hyphen at end to avoid range interpretation)
    preg_match_all('/## Component \d+ [—–-] ([^\n]+)\n(.*?)(?=\n## |$)/s', $markdown, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $componentName = trim($match[1]);
        $section = $match[2];

        // Find ### Method (timeline) subsection
        if (preg_match('/### Method\s*\(([^)]+)\)\s*\n(.*?)(?=\n### |$)/s', $section, $methodMatch)) {
            $markerRaw = trim($methodMatch[1]);
            $stepsContent = $methodMatch[2];

            $marker = normalizeTimelineMarker($markerRaw);

            if (!isset($timeline[$marker])) {
                $timeline[$marker] = [];
            }

            // Parse numbered steps
            $steps = parseNumberedSteps($stepsContent);
            if (!empty($steps)) {
                // Sanitize each step
                $timeline[$marker][$componentName] = array_map(
                    fn($s) => cleanAndTruncateStep($s),
                    $steps
                );
            }
        }
    }

    // Also check for ## Plating section (treat as Service)
    if (preg_match('/## Plating\s*\n(.*?)(?=\n## |$)/s', $markdown, $platingMatch)) {
        $platingSection = $platingMatch[1];

        // First try ### Assembly
        if (preg_match('/### Assembly[^\n]*\s*\n(.*?)(?=\n### |$)/s', $platingSection, $assemblyMatch)) {
            $steps = parseNumberedSteps($assemblyMatch[1]);
            if (!empty($steps)) {
                if (!isset($timeline['Service'])) {
                    $timeline['Service'] = [];
                }
                $timeline['Service']['Assembly'] = array_map(
                    fn($s) => cleanAndTruncateStep($s),
                    $steps
                );
            }
        }

        // Also try ### Prep section as Day-of
        if (preg_match('/### Prep[^\n]*\s*\n(.*?)(?=\n### |$)/s', $platingSection, $prepMatch)) {
            $steps = parseBulletSteps($prepMatch[1]);
            if (!empty($steps)) {
                if (!isset($timeline['Day-of'])) {
                    $timeline['Day-of'] = [];
                }
                $timeline['Day-of']['Plating Prep'] = array_map(
                    fn($s) => cleanAndTruncateStep($s),
                    $steps
                );
            }
        }
    }

    return $timeline;
}

/**
 * Parse bullet point steps (- item) into array
 */
function parseBulletSteps(string $content): array
{
    $steps = [];

    preg_match_all('/^[-*]\s+(.+)$/m', $content, $matches);

    foreach ($matches[1] as $step) {
        $step = cleanStepText(trim($step));
        if (!empty($step)) {
            // Return raw, sanitization done later
            $steps[] = $step;
        }
    }

    return $steps;
}

/**
 * Parse numbered steps (1., 2., 3...) into array of strings
 */
function parseNumberedSteps(string $content): array
{
    $steps = [];

    // Split by numbered items
    $parts = preg_split('/^\d+\.\s+/m', $content, -1, PREG_SPLIT_NO_EMPTY);

    foreach ($parts as $part) {
        $step = trim($part);
        // Clean up: remove trailing newlines, collapse whitespace
        $step = preg_replace('/\s+/', ' ', $step);
        // Clean markdown formatting
        $step = cleanStepText($step);
        if (!empty($step)) {
            // CRITICAL: Sanitize for XSS (done later, return raw for now)
            $steps[] = $step;
        }
    }

    return $steps;
}

/**
 * Parse timeline content with #### Component headers and **N. Step** patterns
 */
function parseTimelineContent(string $content, array &$markerTimeline): void
{
    $currentComponent = 'Main';

    // Split by #### headers (for recipes with component sub-sections)
    $parts = preg_split('/^#### ([^\n]+)$/m', $content, -1, PREG_SPLIT_DELIM_CAPTURE);

    for ($i = 0; $i < count($parts); $i++) {
        $part = trim($parts[$i]);

        if ($i % 2 === 1) {
            // This is a component header
            $currentComponent = $part;
            if (!isset($markerTimeline[$currentComponent])) {
                $markerTimeline[$currentComponent] = [];
            }
        } elseif (!empty($part)) {
            // Initialize component if needed
            if (!isset($markerTimeline[$currentComponent])) {
                $markerTimeline[$currentComponent] = [];
            }

            $foundSteps = false;

            // Strategy 1: Parse **N. Step Name** patterns (bold numbered steps)
            // Handles: **1. Start Kombu Water**
            if (preg_match_all('/\*\*(\d+)\.\s*([^*]+)\*\*/', $part, $stepMatches, PREG_SET_ORDER)) {
                foreach ($stepMatches as $match) {
                    $stepName = cleanStepText(trim($match[2]));
                    if (!empty($stepName)) {
                        $markerTimeline[$currentComponent][] = $stepName;
                        $foundSteps = true;
                    }
                }
            }

            if ($foundSteps) {
                continue;
            }

            // Strategy 2: Parse lines that start with **N. (bold numbered headers on their own line)
            if (preg_match_all('/^\*\*\d+\.\s*([^*\n]+)\*\*/m', $part, $boldMatches)) {
                foreach ($boldMatches[1] as $stepName) {
                    $stepName = cleanStepText(trim($stepName));
                    if (!empty($stepName)) {
                        $markerTimeline[$currentComponent][] = $stepName;
                        $foundSteps = true;
                    }
                }
            }

            if ($foundSteps) {
                continue;
            }

            // Strategy 3: Parse numbered list items (1. Step text)
            $numberedSteps = parseNumberedSteps($part);
            if (!empty($numberedSteps)) {
                foreach ($numberedSteps as $step) {
                    // Clean and truncate long steps
                    $cleanStep = cleanStepText($step);
                    $shortStep = cleanAndTruncateStep($cleanStep);
                    $markerTimeline[$currentComponent][] = $shortStep;
                }
                continue;
            }

            // Strategy 4: Parse bullet points
            $bulletSteps = parseBulletSteps($part);
            if (!empty($bulletSteps)) {
                foreach ($bulletSteps as $step) {
                    $cleanStep = cleanStepText($step);
                    $shortStep = cleanAndTruncateStep($cleanStep);
                    $markerTimeline[$currentComponent][] = $shortStep;
                }
            }
        }
    }
}

/**
 * Clean step text - no truncation, show full content
 */
function cleanAndTruncateStep(string $text): string
{
    return cleanStepText($text);
}

/**
 * Clean up markdown formatting from step text
 */
function cleanStepText(string $text): string
{
    // Remove ## section headers and everything after (Notes, Timeline Summary, etc.)
    $text = preg_replace('/\s*##\s+.*/s', '', $text);

    // Remove **text** patterns (bold) - use non-greedy match
    $text = preg_replace('/\*\*(.+?)\*\*/', '$1', $text);

    // Remove *text* patterns (italic) - use non-greedy match
    $text = preg_replace('/\*(.+?)\*/', '$1', $text);

    // Remove any remaining standalone * or **
    $text = str_replace(['**', '*'], '', $text);

    // Remove numbered prefix like "1. " or "2. " at the start
    $text = preg_replace('/^\d+\.\s*/', '', $text);

    // Collapse multiple spaces and newlines
    $text = preg_replace('/\s+/', ' ', $text);

    return trim($text);
}

/**
 * Sort timeline markers in chronological order (T-48h first, Service last)
 */
/**
 * Sort timeline markers in chronological order (earliest prep first, Service last)
 * Handles any T-Xm, T-Xh format dynamically
 */
function sortTimelineMarkers(array $markers): array
{
    usort($markers, function ($a, $b) {
        $minutesA = getMarkerMinutes($a);
        $minutesB = getMarkerMinutes($b);

        // Sort descending by minutes (higher minutes = earlier in timeline = comes first)
        return $minutesB - $minutesA;
    });
    return $markers;
}

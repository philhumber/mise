/**
 * Timeline parsing utility for Mïse
 *
 * Extracts timeline items from rendered recipe HTML.
 * Timeline markers must use the canonical format (T-48h, Day-of, Service, etc.)
 *
 * Uses regex parsing to support SSR (DOMParser is browser-only).
 */

import type { TimelineItem } from '$lib/types';

/**
 * Canonical timeline markers and their anchor IDs
 */
const CANONICAL_MARKERS = new Set(['t-48h', 't-24h', 't-12h', 't-4h', 't-1h', 'day-of', 'service']);

/**
 * Convert a timeline marker to its anchor ID
 * Simply lowercases the marker text
 *
 * @example markerToAnchorId('T-48h') // 't-48h'
 * @example markerToAnchorId('Day-of') // 'day-of'
 */
export function markerToAnchorId(marker: string): string {
	return marker.toLowerCase();
}

/**
 * Parse timeline items from rendered HTML content
 *
 * Looks for the ## Timeline section and extracts list items with bold markers.
 * Returns an empty array if no timeline section exists.
 *
 * Uses regex parsing to work during SSR (no DOMParser dependency).
 *
 * @param htmlContent - Rendered HTML from recipe.content
 * @returns Array of timeline items with markers, descriptions, and anchor IDs
 */
export function parseTimeline(htmlContent: string): TimelineItem[] {
	const items: TimelineItem[] = [];

	// Find the Timeline section: <h2>Timeline</h2> followed by <ul>...</ul>
	// The section ends at the next <h2> or end of content
	const timelineSectionMatch = htmlContent.match(/<h2[^>]*>Timeline<\/h2>\s*<ul>([\s\S]*?)<\/ul>/i);

	if (!timelineSectionMatch) {
		return items;
	}

	const ulContent = timelineSectionMatch[1];

	// Match list items: <li><strong>Marker</strong> Description</li>
	// Use .*? to capture description that may contain HTML tags
	const liPattern = /<li>\s*<strong>([^<]+)<\/strong>\s*(.*?)<\/li>/gs;
	let match;

	while ((match = liPattern.exec(ulContent)) !== null) {
		const marker = match[1].trim();
		// Strip any HTML tags from description and clean up whitespace
		const description = match[2].replace(/<[^>]*>/g, '').trim();
		const anchorId = markerToAnchorId(marker);

		// Only include recognized canonical markers
		if (!CANONICAL_MARKERS.has(anchorId)) {
			continue;
		}

		items.push({
			marker,
			description,
			anchorId
		});
	}

	return items;
}

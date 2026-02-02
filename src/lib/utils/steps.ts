/**
 * Step parsing utility for Cook Mode
 *
 * Extracts structured step data from rendered recipe HTML.
 * Supports both ordered list format and paragraph-based format.
 *
 * Uses regex parsing to support SSR (DOMParser is browser-only).
 */

import type { CookModeStep } from '$lib/types';

/**
 * Default marker for simple recipes without timeline headers
 */
const DEFAULT_MARKER = 'T – 1 HOUR';

/**
 * Map raw timeline IDs to display format
 */
const TIMELINE_MARKER_MAP: Record<string, string> = {
	't-48h': 'T – 48 HOURS',
	't-24h': 'T – 24 HOURS',
	't-12h': 'T – 12 HOURS',
	't-4h': 'T – 4 HOURS',
	't-2h': 'T – 2 HOURS',
	't-1h': 'T – 1 HOUR',
	't-90m': 'T – 90 MINUTES',
	't-30m': 'T – 30 MINUTES',
	't-15m': 'T – 15 MINUTES',
	'day-of': 'DAY OF',
	service: 'SERVICE'
};

/**
 * Format a raw timeline marker ID to display format
 */
export function formatTimelineMarker(rawMarker: string): string {
	const normalized = rawMarker.toLowerCase().trim();
	return TIMELINE_MARKER_MAP[normalized] || rawMarker.toUpperCase();
}

/**
 * Strip HTML tags from a string
 */
function stripHtml(html: string): string {
	return html.replace(/<[^>]*>/g, '').trim();
}

/**
 * Clean up whitespace in text
 */
function cleanText(text: string): string {
	return text.replace(/\s+/g, ' ').trim();
}

interface TimelineBlock {
	marker: string;
	html: string;
}

/**
 * Extract the Method section from recipe HTML
 */
function extractMethodSection(htmlContent: string): string | null {
	// Match from <h2 id="method"> to next <h2> or end
	const match = htmlContent.match(/<h2[^>]*id="method"[^>]*>[\s\S]*?<\/h2>([\s\S]*?)(?=<h2[^>]*>|$)/i);
	return match ? match[1] : null;
}

/**
 * Split method section into timeline blocks
 * Returns array of blocks with marker and HTML content
 */
function extractTimelineBlocks(methodHtml: string): TimelineBlock[] {
	const blocks: TimelineBlock[] = [];

	// Find all H3 timeline headers
	const h3Pattern = /<h3[^>]*id="(t-\d+[hm]|day-of|service)"[^>]*>[^<]*<\/h3>/gi;
	const headers: { marker: string; index: number }[] = [];

	let match;
	while ((match = h3Pattern.exec(methodHtml)) !== null) {
		headers.push({
			marker: match[1].toLowerCase(),
			index: match.index
		});
	}

	if (headers.length === 0) {
		// No timeline headers - single block with default marker
		return [{ marker: DEFAULT_MARKER, html: methodHtml }];
	}

	// Split content by headers
	for (let i = 0; i < headers.length; i++) {
		const start = headers[i].index;
		const end = i < headers.length - 1 ? headers[i + 1].index : methodHtml.length;
		const blockHtml = methodHtml.slice(start, end);

		blocks.push({
			marker: formatTimelineMarker(headers[i].marker),
			html: blockHtml
		});
	}

	return blocks;
}

/**
 * Parse steps from ordered list format
 * Format: <ol><li><strong>Title.</strong> Body</li></ol>
 */
function parseStepsFromOrderedList(
	html: string,
	marker: string,
	startNumber: number
): CookModeStep[] {
	const steps: CookModeStep[] = [];

	// Find all <ol> elements
	const olPattern = /<ol[^>]*>([\s\S]*?)<\/ol>/gi;
	let olMatch;

	while ((olMatch = olPattern.exec(html)) !== null) {
		const olContent = olMatch[1];

		// Extract <li> items
		const liPattern = /<li>([\s\S]*?)<\/li>/gi;
		let liMatch;

		while ((liMatch = liPattern.exec(olContent)) !== null) {
			const liContent = liMatch[1].trim();
			if (!liContent) continue;

			// Try to extract title from <strong>Title.</strong> pattern
			const titleMatch = liContent.match(/^<strong>([^<]+)<\/strong>\.?\s*([\s\S]*)/i);

			let title: string;
			let body: string;

			if (titleMatch) {
				title = cleanText(titleMatch[1].replace(/\.$/, '')); // Remove trailing period
				body = cleanText(stripHtml(titleMatch[2]));
			} else {
				// Fallback: use first sentence as title
				const plainText = cleanText(stripHtml(liContent));
				const firstSentence = plainText.match(/^([^.!?]+[.!?]?)/);
				title = firstSentence ? firstSentence[1].trim() : plainText.slice(0, 50);
				body = plainText.slice(title.length).trim();
			}

			steps.push({
				number: startNumber + steps.length,
				marker,
				title,
				body,
				htmlContent: liContent
			});
		}
	}

	return steps;
}

/**
 * Parse steps from paragraph format (legacy recipes)
 * Format: <p><strong>1. Title</strong></p><p>Body</p>
 */
function parseStepsFromParagraphs(
	html: string,
	marker: string,
	startNumber: number
): CookModeStep[] {
	const steps: CookModeStep[] = [];

	// Match bold numbered headers: <strong>N. Title</strong> or **N. Title**
	// This pattern finds step headers in paragraphs
	const stepPattern = /<p>\s*<strong>(\d+)\.\s*([^<]+)<\/strong>\s*<\/p>/gi;
	const stepHeaders: { num: number; title: string; index: number; endIndex: number }[] = [];

	let match;
	while ((match = stepPattern.exec(html)) !== null) {
		stepHeaders.push({
			num: parseInt(match[1], 10),
			title: cleanText(match[2]),
			index: match.index,
			endIndex: match.index + match[0].length
		});
	}

	// For each step header, collect body content until next header or end
	for (let i = 0; i < stepHeaders.length; i++) {
		const header = stepHeaders[i];
		const nextIndex = i < stepHeaders.length - 1 ? stepHeaders[i + 1].index : html.length;

		// Get content between this header and next
		const bodyHtml = html.slice(header.endIndex, nextIndex);

		// Extract text from paragraphs in body
		const bodyParts: string[] = [];
		const pPattern = /<p>([\s\S]*?)<\/p>/gi;
		let pMatch;
		while ((pMatch = pPattern.exec(bodyHtml)) !== null) {
			const text = cleanText(stripHtml(pMatch[1]));
			if (text) bodyParts.push(text);
		}

		steps.push({
			number: startNumber + steps.length,
			marker,
			title: header.title,
			body: bodyParts.join(' '),
			htmlContent: html.slice(header.index, nextIndex).trim()
		});
	}

	return steps;
}

/**
 * Parse steps from a timeline block
 * Tries ordered list format first, then paragraph format
 */
function parseStepsFromBlock(block: TimelineBlock, startNumber: number): CookModeStep[] {
	// Try ordered list format first
	const olSteps = parseStepsFromOrderedList(block.html, block.marker, startNumber);
	if (olSteps.length > 0) {
		return olSteps;
	}

	// Fall back to paragraph format
	const pSteps = parseStepsFromParagraphs(block.html, block.marker, startNumber);
	if (pSteps.length > 0) {
		return pSteps;
	}

	return [];
}

/**
 * Parse all steps from recipe HTML content
 *
 * @param htmlContent - Rendered HTML from recipe.content
 * @returns Array of cook mode steps with sequential numbering
 */
export function parseSteps(htmlContent: string): CookModeStep[] {
	const methodHtml = extractMethodSection(htmlContent);
	if (!methodHtml) {
		return [];
	}

	const blocks = extractTimelineBlocks(methodHtml);
	const allSteps: CookModeStep[] = [];

	for (const block of blocks) {
		const steps = parseStepsFromBlock(block, allSteps.length + 1);
		allSteps.push(...steps);
	}

	return allSteps;
}

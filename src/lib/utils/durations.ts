/**
 * Duration detection and formatting for Cook Mode timers
 *
 * Parses natural language cooking durations from step text.
 * Supports ranges ("35–45 minutes"), compound ("1 hour 30 minutes"),
 * and contextual labels ("simmer for 10 minutes").
 */

import type { DetectedDuration } from '$lib/types';

/**
 * Action verbs that provide context for timer labels
 */
const ACTION_VERBS =
	/\b(cook|simmer|blanch|boil|bake|roast|fry|saut[eé]|braise|steam|poach|grill|broil|rest|chill|cool|infuse|marinate|soak|reduce|warm|heat|toast|dehydrate|ferment|proof|rise|set)\b/i;

/**
 * Unit multipliers to convert to seconds
 */
const UNIT_SECONDS: Record<string, number> = {
	s: 1,
	sec: 1,
	secs: 1,
	second: 1,
	seconds: 1,
	m: 60,
	min: 60,
	mins: 60,
	minute: 60,
	minutes: 60,
	h: 3600,
	hr: 3600,
	hrs: 3600,
	hour: 3600,
	hours: 3600
};

/**
 * Resolve a unit string to seconds multiplier
 */
function unitToSeconds(unit: string): number | null {
	return UNIT_SECONDS[unit.toLowerCase()] ?? null;
}

/**
 * Parse a number that might use en-dash or hyphen range (e.g. "35–45")
 * Returns the lower bound of the range for timer purposes
 */
function parseRangeNumber(text: string): number | null {
	const rangeMatch = text.match(/^(\d+)\s*[–\-−]\s*(\d+)$/);
	if (rangeMatch) {
		return parseInt(rangeMatch[1], 10);
	}
	const num = parseInt(text, 10);
	return isNaN(num) ? null : num;
}

/**
 * Detect cooking durations in natural language text
 *
 * @param text - Plain text from a recipe step (HTML stripped)
 * @returns Array of detected durations with labels
 */
export function detectDurations(text: string): DetectedDuration[] {
	if (!text) return [];

	const results: DetectedDuration[] = [];
	const seen = new Set<number>(); // deduplicate by start index

	// Pattern 1: Compound durations "1 hour 30 minutes", "2 hours 15 mins"
	const compoundPattern =
		/(\d+)\s*(hours?|hrs?|h)\s+(?:and\s+)?(\d+)\s*(minutes?|mins?|m(?!\w))/gi;
	let match;
	while ((match = compoundPattern.exec(text)) !== null) {
		const hours = parseInt(match[1], 10);
		const minUnit = unitToSeconds(match[4]);
		const mins = parseInt(match[3], 10);
		if (minUnit) {
			const seconds = hours * 3600 + mins * minUnit;
			if (seconds > 0 && seconds <= 86400) {
				const label = extractLabel(text, match.index);
				results.push({ seconds, label, matchedText: match[0] });
				seen.add(match.index);
			}
		}
	}

	// Pattern 2: Simple/range durations "10 minutes", "35–45 minutes", "2-3 hours"
	const simplePattern =
		/(\d+(?:\s*[–\-−]\s*\d+)?)\s*(seconds?|secs?|minutes?|mins?|hours?|hrs?)\b/gi;
	while ((match = simplePattern.exec(text)) !== null) {
		if (seen.has(match.index)) continue;

		const num = parseRangeNumber(match[1]);
		const multiplier = unitToSeconds(match[2]);
		if (num && multiplier) {
			const seconds = num * multiplier;
			if (seconds > 0 && seconds <= 86400) {
				const label = extractLabel(text, match.index);
				results.push({ seconds, label, matchedText: match[0] });
				seen.add(match.index);
			}
		}
	}

	return results;
}

/**
 * Extract a contextual label for the timer by looking for action verbs
 * near the matched duration in the text
 */
function extractLabel(text: string, matchIndex: number): string {
	// Look at the 80 characters before the match for an action verb
	const prefix = text.slice(Math.max(0, matchIndex - 80), matchIndex);
	const verbMatch = prefix.match(ACTION_VERBS);
	if (verbMatch) {
		// Capitalize first letter
		const verb = verbMatch[1];
		return verb.charAt(0).toUpperCase() + verb.slice(1).toLowerCase();
	}
	return 'Timer';
}

/**
 * Format seconds into a human-readable timer display
 *
 * @param totalSeconds - Duration in seconds
 * @returns Formatted string like "5:00", "1:30:00", "0:45"
 */
export function formatTime(totalSeconds: number): string {
	const seconds = Math.max(0, Math.ceil(totalSeconds));
	const h = Math.floor(seconds / 3600);
	const m = Math.floor((seconds % 3600) / 60);
	const s = seconds % 60;

	if (h > 0) {
		return `${h}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
	}
	return `${m}:${s.toString().padStart(2, '0')}`;
}

/**
 * Format seconds into a short label for buttons (e.g. "10m", "1h 30m", "45s")
 */
export function formatDurationShort(totalSeconds: number): string {
	const seconds = Math.max(0, Math.round(totalSeconds));
	const h = Math.floor(seconds / 3600);
	const m = Math.floor((seconds % 3600) / 60);
	const s = seconds % 60;

	if (h > 0 && m > 0) return `${h}h ${m}m`;
	if (h > 0) return `${h}h`;
	if (m > 0) return `${m}m`;
	return `${s}s`;
}

/**
 * Parse a user-entered duration string into seconds
 *
 * Supports: "10m", "1h30m", "90s", "5", "1:30", "1:30:00"
 * Plain number is treated as minutes.
 *
 * @returns seconds or null if unparseable
 */
export function parseDurationInput(input: string): number | null {
	const trimmed = input.trim().toLowerCase();
	if (!trimmed) return null;

	// Try "1h30m", "10m", "45s" format
	const hmsMatch = trimmed.match(/^(?:(\d+)h)?(?:\s*(\d+)m)?(?:\s*(\d+)s)?$/);
	if (hmsMatch && (hmsMatch[1] || hmsMatch[2] || hmsMatch[3])) {
		const h = parseInt(hmsMatch[1] || '0', 10);
		const m = parseInt(hmsMatch[2] || '0', 10);
		const s = parseInt(hmsMatch[3] || '0', 10);
		const total = h * 3600 + m * 60 + s;
		return total > 0 ? total : null;
	}

	// Try "1:30:00" or "5:00" colon format
	const colonMatch = trimmed.match(/^(\d+):(\d{1,2})(?::(\d{1,2}))?$/);
	if (colonMatch) {
		if (colonMatch[3] !== undefined) {
			// h:mm:ss
			const h = parseInt(colonMatch[1], 10);
			const m = parseInt(colonMatch[2], 10);
			const s = parseInt(colonMatch[3], 10);
			return h * 3600 + m * 60 + s;
		}
		// m:ss
		const m = parseInt(colonMatch[1], 10);
		const s = parseInt(colonMatch[2], 10);
		return m * 60 + s;
	}

	// Plain number → minutes
	const plainNum = parseInt(trimmed, 10);
	if (!isNaN(plainNum) && plainNum > 0) {
		return plainNum * 60;
	}

	return null;
}

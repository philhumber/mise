/**
 * Meal View State Store
 *
 * Tracks the active meal referrer for dynamic back navigation.
 */

const REFERRER_KEY = 'mise-meal-referrer';

/**
 * Set the meal we're navigating from (for back button)
 */
export function setMealReferrer(mealSlug: string): void {
	if (typeof window === 'undefined') return;

	try {
		sessionStorage.setItem(REFERRER_KEY, mealSlug);
	} catch {
		// Silent fail
	}
}

/**
 * Get the meal referrer slug (returns null if none)
 */
export function getMealReferrer(): string | null {
	if (typeof window === 'undefined') return null;

	try {
		return sessionStorage.getItem(REFERRER_KEY);
	} catch {
		return null;
	}
}

/**
 * Clear the meal referrer
 */
export function clearMealReferrer(): void {
	if (typeof window === 'undefined') return;

	try {
		sessionStorage.removeItem(REFERRER_KEY);
	} catch {
		// Silent fail
	}
}

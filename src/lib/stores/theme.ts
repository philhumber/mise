/**
 * Theme store with localStorage persistence
 */

export type Theme = 'light' | 'dark';

const STORAGE_KEY = 'mise-theme';

/**
 * Get the initial theme from localStorage or system preference
 */
function getInitialTheme(): Theme {
	if (typeof window === 'undefined') return 'light';

	const stored = localStorage.getItem(STORAGE_KEY);
	if (stored === 'light' || stored === 'dark') {
		return stored;
	}

	// Fall back to system preference
	if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
		return 'dark';
	}

	return 'light';
}

/**
 * Apply theme to document
 */
function applyTheme(theme: Theme): void {
	if (typeof document === 'undefined') return;

	if (theme === 'dark') {
		document.documentElement.setAttribute('data-theme', 'dark');
	} else {
		document.documentElement.removeAttribute('data-theme');
	}
}

let currentTheme: Theme = 'light';

/**
 * Initialize theme on app load
 */
export function initTheme(): Theme {
	currentTheme = getInitialTheme();
	applyTheme(currentTheme);
	return currentTheme;
}

/**
 * Get current theme
 */
export function getTheme(): Theme {
	return currentTheme;
}

/**
 * Set theme and persist to localStorage
 */
export function setTheme(theme: Theme): void {
	currentTheme = theme;
	localStorage.setItem(STORAGE_KEY, theme);
	applyTheme(theme);
}

/**
 * Toggle between light and dark
 */
export function toggleTheme(): Theme {
	const newTheme = currentTheme === 'light' ? 'dark' : 'light';
	setTheme(newTheme);
	return newTheme;
}

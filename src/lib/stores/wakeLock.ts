/**
 * Wake Lock module with localStorage persistence
 * Keeps screen awake while viewing recipes in the kitchen
 */

const STORAGE_KEY = 'mise-wake-lock';

let wakeLockSentinel: WakeLockSentinel | null = null;
let wakeLockEnabled = false;
let wakeLockSupported = false;

/**
 * Initialize wake lock on app load
 */
export function initWakeLock(): void {
	if (typeof window === 'undefined') return;

	wakeLockSupported =
		'wakeLock' in navigator && typeof navigator.wakeLock?.request === 'function';

	try {
		const stored = localStorage.getItem(STORAGE_KEY);
		wakeLockEnabled = stored === 'true';
	} catch {
		// Private browsing or storage disabled
	}

	setupVisibilityListener();
}

export function isWakeLockSupported(): boolean {
	return wakeLockSupported;
}

export function getWakeLockEnabled(): boolean {
	return wakeLockEnabled;
}

export function setWakeLockEnabled(enabled: boolean): void {
	wakeLockEnabled = enabled;
	try {
		localStorage.setItem(STORAGE_KEY, enabled.toString());
	} catch {
		// Silent fail - in-memory state still works
	}
}

export async function requestWakeLock(): Promise<boolean> {
	if (!wakeLockSupported || !wakeLockEnabled) return false;

	// Release existing sentinel first
	if (wakeLockSentinel) {
		try {
			if (!wakeLockSentinel.released) {
				await wakeLockSentinel.release();
			}
		} catch {
			// Ignore release errors
		}
		wakeLockSentinel = null;
	}

	try {
		wakeLockSentinel = await navigator.wakeLock.request('screen');
		wakeLockSentinel.addEventListener('release', () => {
			wakeLockSentinel = null;
		});
		return true;
	} catch {
		return false;
	}
}

export function releaseWakeLock(): void {
	if (wakeLockSentinel) {
		try {
			if (!wakeLockSentinel.released) {
				wakeLockSentinel.release();
			}
		} catch {
			// Ignore
		}
		wakeLockSentinel = null;
	}
}

export function getWakeLockActive(): boolean {
	return wakeLockSentinel !== null && !wakeLockSentinel.released;
}

function setupVisibilityListener(): void {
	if (typeof document === 'undefined') return;
	document.addEventListener('visibilitychange', handleVisibilityChange);
}

async function handleVisibilityChange(): Promise<void> {
	if (document.visibilityState === 'visible' && wakeLockEnabled) {
		await requestWakeLock();
	}
}

// Cleanup on page unload (persists for app lifetime)
if (typeof window !== 'undefined') {
	window.addEventListener('beforeunload', () => {
		releaseWakeLock();
	});
}

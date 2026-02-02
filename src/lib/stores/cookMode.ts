/**
 * Cook Mode state management
 *
 * Module-based store for managing cook mode overlay state.
 * Auto-enables wake lock when entering cook mode.
 */

import type { CookModeStep } from '$lib/types';
import { requestWakeLock, releaseWakeLock, setWakeLockEnabled } from './wakeLock';

const TEXT_SIZE_KEY = 'mise-cook-mode-text-size';
const TEXT_SIZES = [0.85, 1, 1.2, 1.4, 1.6] as const;
const DEFAULT_SIZE_INDEX = 1; // 1x scale

interface CookModeState {
	isActive: boolean;
	steps: CookModeStep[];
	currentStepIndex: number;
	textSizeIndex: number;
}

// Load persisted text size preference
function loadTextSizeIndex(): number {
	if (typeof window === 'undefined') return DEFAULT_SIZE_INDEX;
	try {
		const stored = localStorage.getItem(TEXT_SIZE_KEY);
		if (stored !== null) {
			const index = parseInt(stored, 10);
			if (index >= 0 && index < TEXT_SIZES.length) return index;
		}
	} catch {
		// Ignore storage errors
	}
	return DEFAULT_SIZE_INDEX;
}

let state: CookModeState = {
	isActive: false,
	steps: [],
	currentStepIndex: 0,
	textSizeIndex: loadTextSizeIndex()
};

// Subscribers for reactive updates
type Subscriber = (state: CookModeState) => void;
const subscribers = new Set<Subscriber>();

function notifySubscribers(): void {
	for (const subscriber of subscribers) {
		subscriber(state);
	}
}

/**
 * Subscribe to cook mode state changes
 */
export function subscribeCookMode(callback: Subscriber): () => void {
	subscribers.add(callback);
	callback(state); // Initial call
	return () => subscribers.delete(callback);
}

/**
 * Get current cook mode state
 */
export function getCookModeState(): CookModeState {
	return { ...state };
}

/**
 * Check if cook mode is active
 */
export function isCookModeActive(): boolean {
	return state.isActive;
}

/**
 * Get current steps
 */
export function getCookModeSteps(): CookModeStep[] {
	return state.steps;
}

/**
 * Get current step index
 */
export function getCurrentStepIndex(): number {
	return state.currentStepIndex;
}

/**
 * Get current step
 */
export function getCurrentStep(): CookModeStep | null {
	if (!state.isActive || state.steps.length === 0) return null;
	return state.steps[state.currentStepIndex] || null;
}

/**
 * Enter cook mode with given steps
 * Auto-enables wake lock regardless of user preference
 */
export async function enterCookMode(steps: CookModeStep[]): Promise<void> {
	if (steps.length === 0) return;

	state = {
		...state,
		isActive: true,
		steps,
		currentStepIndex: 0
	};

	// Auto-enable wake lock for kitchen use
	setWakeLockEnabled(true);
	await requestWakeLock();

	// Lock body scroll
	if (typeof document !== 'undefined') {
		document.body.style.overflow = 'hidden';
	}

	notifySubscribers();
}

/**
 * Exit cook mode
 */
export function exitCookMode(): void {
	state = {
		...state,
		isActive: false,
		steps: [],
		currentStepIndex: 0
	};

	// Release wake lock
	releaseWakeLock();

	// Restore body scroll
	if (typeof document !== 'undefined') {
		document.body.style.overflow = '';
	}

	notifySubscribers();
}

/**
 * Go to a specific step (placeholder for MISE-67)
 */
export function goToStep(index: number): void {
	if (!state.isActive) return;
	if (index < 0 || index >= state.steps.length) return;

	state = {
		...state,
		currentStepIndex: index
	};

	notifySubscribers();
}

/**
 * Go to next step (placeholder for MISE-67)
 */
export function nextStep(): void {
	if (!state.isActive) return;
	if (state.currentStepIndex >= state.steps.length - 1) return;

	goToStep(state.currentStepIndex + 1);
}

/**
 * Go to previous step (placeholder for MISE-67)
 */
export function prevStep(): void {
	if (!state.isActive) return;
	if (state.currentStepIndex <= 0) return;

	goToStep(state.currentStepIndex - 1);
}

/**
 * Get current text size scale factor
 */
export function getTextSizeScale(): number {
	return TEXT_SIZES[state.textSizeIndex];
}

/**
 * Get current text size index
 */
export function getTextSizeIndex(): number {
	return state.textSizeIndex;
}

/**
 * Check if text can be increased
 */
export function canIncreaseTextSize(): boolean {
	return state.textSizeIndex < TEXT_SIZES.length - 1;
}

/**
 * Check if text can be decreased
 */
export function canDecreaseTextSize(): boolean {
	return state.textSizeIndex > 0;
}

/**
 * Increase text size
 */
export function increaseTextSize(): void {
	if (!canIncreaseTextSize()) return;

	state = {
		...state,
		textSizeIndex: state.textSizeIndex + 1
	};

	// Persist preference
	try {
		localStorage.setItem(TEXT_SIZE_KEY, state.textSizeIndex.toString());
	} catch {
		// Ignore storage errors
	}

	notifySubscribers();
}

/**
 * Decrease text size
 */
export function decreaseTextSize(): void {
	if (!canDecreaseTextSize()) return;

	state = {
		...state,
		textSizeIndex: state.textSizeIndex - 1
	};

	// Persist preference
	try {
		localStorage.setItem(TEXT_SIZE_KEY, state.textSizeIndex.toString());
	} catch {
		// Ignore storage errors
	}

	notifySubscribers();
}

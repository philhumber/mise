/**
 * Timer state management
 *
 * Independent module-based store for cooking timers.
 * Follows the same subscriber pattern as cookMode.ts.
 * Timers persist across step navigation but are cleared when cook mode exits.
 */

import type { Timer, TimerState } from '$lib/types';

let timers: Timer[] = [];
let tickInterval: ReturnType<typeof setInterval> | null = null;

// Subscribers
type Subscriber = (timers: Timer[]) => void;
const subscribers = new Set<Subscriber>();

function notifySubscribers(): void {
	for (const subscriber of subscribers) {
		subscriber(timers);
	}
}

/**
 * Subscribe to timer state changes
 */
export function subscribeTimers(callback: Subscriber): () => void {
	subscribers.add(callback);
	callback(timers);
	return () => subscribers.delete(callback);
}

// === Queries ===

export function getTimers(): Timer[] {
	return [...timers];
}

export function getRunningTimerCount(): number {
	return timers.filter((t) => t.state === 'running').length;
}

// === Commands ===

/**
 * Create and start a new timer
 */
export function createTimer(label: string, durationSeconds: number, stepNumber: number): string {
	const id = `t-${Date.now()}-${Math.random().toString(36).slice(2, 6)}`;
	const timer: Timer = {
		id,
		label,
		durationSeconds,
		remainingSeconds: durationSeconds,
		state: 'running',
		startedAt: Date.now(),
		elapsedBeforePause: 0,
		stepNumber
	};

	timers = [...timers, timer];
	ensureTickRunning();
	notifySubscribers();
	return id;
}

export function pauseTimer(id: string): void {
	timers = timers.map((t) => {
		if (t.id !== id || t.state !== 'running') return t;
		const elapsed = t.elapsedBeforePause + (Date.now() - t.startedAt) / 1000;
		return {
			...t,
			state: 'paused' as TimerState,
			elapsedBeforePause: elapsed,
			remainingSeconds: Math.max(0, t.durationSeconds - elapsed)
		};
	});
	stopTickIfIdle();
	notifySubscribers();
}

export function resumeTimer(id: string): void {
	timers = timers.map((t) => {
		if (t.id !== id || t.state !== 'paused') return t;
		return {
			...t,
			state: 'running' as TimerState,
			startedAt: Date.now(),
			remainingSeconds: Math.max(0, t.durationSeconds - t.elapsedBeforePause)
		};
	});
	ensureTickRunning();
	notifySubscribers();
}

export function dismissTimer(id: string): void {
	timers = timers.filter((t) => t.id !== id);
	stopTickIfIdle();
	notifySubscribers();
}

export function deleteAllTimers(): void {
	timers = [];
	stopTick();
	notifySubscribers();
}

// === Tick Logic ===

function tickTimers(): void {
	const now = Date.now();
	let changed = false;

	timers = timers.map((t) => {
		if (t.state !== 'running') return t;

		const elapsed = t.elapsedBeforePause + (now - t.startedAt) / 1000;
		const remaining = Math.max(0, t.durationSeconds - elapsed);

		// Only update if the displayed second has changed
		const prevDisplaySecond = Math.ceil(t.remainingSeconds);
		const newDisplaySecond = Math.ceil(remaining);
		if (prevDisplaySecond === newDisplaySecond) return t;

		changed = true;

		if (remaining <= 0) {
			return { ...t, remainingSeconds: 0, state: 'completed' as TimerState };
		}
		return { ...t, remainingSeconds: remaining };
	});

	if (changed) {
		stopTickIfIdle();
		notifySubscribers();
	}
}

function ensureTickRunning(): void {
	if (tickInterval !== null) return;
	tickInterval = setInterval(tickTimers, 1000);
}

function stopTick(): void {
	if (tickInterval !== null) {
		clearInterval(tickInterval);
		tickInterval = null;
	}
}

function stopTickIfIdle(): void {
	const hasRunning = timers.some((t) => t.state === 'running');
	if (!hasRunning) {
		stopTick();
	}
}

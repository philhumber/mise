/**
 * Timer notification utilities - audio chime + vibration
 *
 * Uses Web Audio API to generate a pleasant two-tone chime.
 * Uses Vibration API for haptic feedback on supported devices.
 * Chime repeats every few seconds until the timer is dismissed.
 * Mute preference persisted in localStorage.
 */

const STORAGE_KEY = 'mise-timer-muted';
const REPEAT_INTERVAL_MS = 2000;

let muted = loadMutedPreference();

// === Mute state ===

type MuteSubscriber = (muted: boolean) => void;
const muteSubscribers = new Set<MuteSubscriber>();

function loadMutedPreference(): boolean {
	if (typeof localStorage === 'undefined') return false;
	return localStorage.getItem(STORAGE_KEY) === 'true';
}

function notifyMuteSubscribers(): void {
	for (const sub of muteSubscribers) {
		sub(muted);
	}
}

export function isMuted(): boolean {
	return muted;
}

export function setMuted(value: boolean): void {
	muted = value;
	if (typeof localStorage !== 'undefined') {
		localStorage.setItem(STORAGE_KEY, String(value));
	}
	notifyMuteSubscribers();
}

export function toggleMuted(): void {
	setMuted(!muted);
}

export function subscribeMuted(callback: MuteSubscriber): () => void {
	muteSubscribers.add(callback);
	callback(muted);
	return () => muteSubscribers.delete(callback);
}

// === Mute reminder ===

type ReminderSubscriber = () => void;
const reminderSubscribers = new Set<ReminderSubscriber>();

/**
 * Subscribe to mute reminder events (fired when a timer starts while muted).
 */
export function subscribeMuteReminder(callback: ReminderSubscriber): () => void {
	reminderSubscribers.add(callback);
	return () => reminderSubscribers.delete(callback);
}

/**
 * Trigger a mute reminder if currently muted.
 * Call this when a timer is created.
 */
export function triggerMuteReminder(): void {
	if (!muted) return;
	for (const sub of reminderSubscribers) {
		sub();
	}
}

// === Repeating alert per timer ===

const activeAlerts = new Map<string, ReturnType<typeof setInterval>>();

/**
 * Start a repeating chime + vibration alert for a completed timer.
 * Repeats every few seconds until stopped. No-op if muted.
 */
export function startTimerAlert(timerId: string): void {
	if (muted) return;
	// Don't double-start
	if (activeAlerts.has(timerId)) return;

	// Play immediately
	playChime();
	vibrate();

	// Then repeat
	const interval = setInterval(() => {
		// Stop if muted while repeating
		if (muted) {
			stopTimerAlert(timerId);
			return;
		}
		playChime();
		vibrate();
	}, REPEAT_INTERVAL_MS);

	activeAlerts.set(timerId, interval);
}

/**
 * Stop the repeating alert for a specific timer.
 */
export function stopTimerAlert(timerId: string): void {
	const interval = activeAlerts.get(timerId);
	if (interval !== undefined) {
		clearInterval(interval);
		activeAlerts.delete(timerId);
	}
	// Cancel any ongoing vibration
	if (activeAlerts.size === 0 && typeof navigator !== 'undefined' && navigator.vibrate) {
		navigator.vibrate(0);
	}
}

/**
 * Stop all active alerts (e.g. when exiting cook mode).
 */
export function stopAllAlerts(): void {
	for (const [id] of activeAlerts) {
		stopTimerAlert(id);
	}
}

// === Audio ===

let audioCtx: AudioContext | null = null;

function getAudioContext(): AudioContext | null {
	if (typeof AudioContext === 'undefined') return null;
	if (!audioCtx) {
		audioCtx = new AudioContext();
	}
	return audioCtx;
}

/**
 * Generate a pleasant two-tone chime using Web Audio API.
 * Two sine waves at a musical interval, with gentle fade-out.
 */
function playChime(): void {
	const ctx = getAudioContext();
	if (!ctx) return;

	// Resume if suspended (browser autoplay policy)
	if (ctx.state === 'suspended') {
		ctx.resume();
	}

	const now = ctx.currentTime;

	// Tone 1: E5 (659 Hz)
	playTone(ctx, 659, now, 0.3);
	// Tone 2: A5 (880 Hz) - a fourth above, 150ms later
	playTone(ctx, 880, now + 0.15, 0.3);
}

function playTone(ctx: AudioContext, freq: number, startTime: number, duration: number): void {
	const osc = ctx.createOscillator();
	const gain = ctx.createGain();

	osc.type = 'sine';
	osc.frequency.value = freq;

	gain.gain.setValueAtTime(0, startTime);
	gain.gain.linearRampToValueAtTime(0.15, startTime + 0.02);
	gain.gain.exponentialRampToValueAtTime(0.001, startTime + duration);

	osc.connect(gain);
	gain.connect(ctx.destination);

	osc.start(startTime);
	osc.stop(startTime + duration);
}

// === Vibration ===

function vibrate(): void {
	if (typeof navigator === 'undefined' || !navigator.vibrate) return;
	navigator.vibrate([200, 100, 200]);
}

# Wake Lock Feature - Implementation Plan

## Executive Summary

**Status**: ⚠️ **Proceed with revisions** - The original plan is fundamentally sound but requires significant adjustments in 4 critical areas:

1. **Architecture Pattern** - Clarify simple module vs Svelte store approach
2. **Header Integration** - Change from conditional rendering to CSS visibility
3. **Browser Compatibility** - Add runtime feature detection for Firefox
4. **UX/Icon Design** - Replace eye metaphor with more intuitive icon

**Complexity**: Medium-High (revised from Medium)
**Estimated Effort**: 10-15 hours total

---

## Critical Decisions Required Before Implementation

### Decision 1: Architecture Pattern ⚠️ REQUIRED

**Question**: Should `wakeLock.ts` be a simple module (like `theme.ts`) or a Svelte store (like `pageTitle.ts`)?

**Recommendation**: **Simple module** (like `theme.ts`)

**Rationale**:
- Wake lock state doesn't need cross-component reactivity
- Only the toggle button needs to reflect state changes
- Simpler implementation, fewer moving parts
- Matches theme.ts pattern exactly

**Implementation**:
```typescript
// wakeLock.ts - Simple module with exported functions
let wakeLockSentinel: WakeLockSentinel | null = null;
let wakeLockEnabled = false;
let wakeLockSupported = false;

export function initWakeLock() { ... }
export function getWakeLockEnabled() { ... }
export function setWakeLockEnabled(enabled: boolean) { ... }
export async function requestWakeLock() { ... }
export function releaseWakeLock() { ... }
```

### Decision 2: Icon Design ⚠️ REQUIRED

**Question**: What icon metaphor should represent "keep screen awake"?

**Options**:
1. **☀️ Sun/Moon** - Active = sun (awake), Inactive = moon (sleep)
2. **📱 Phone with waves** - Active = radiating, Inactive = dimmed
3. **💡 Lightbulb** - Active = on, Inactive = off
4. ~~👁️ Eye open/closed~~ - **Rejected** (privacy metaphor, not wake)

**Recommendation**: **Option 1 - Sun/Moon**

**Rationale**:
- Intuitive metaphor (sun = day/awake, moon = night/sleep)
- Matches theme toggle visual pattern
- Clear differentiation between states
- Universally understood

### Decision 3: Auto-Activation Behavior ⚠️ REQUIRED

**Question**: When should wake lock activate?

**Options**:
1. **Only on explicit user toggle** (conservative)
2. **Auto-activate if preference enabled** (convenient)

**Recommendation**: **Option 2 - Auto-activate**

**Rationale**:
- If user enabled it once, they want it for all recipes
- Saves repeated clicking in kitchen
- Preference persists via localStorage
- User can disable anytime

**Implementation**:
```typescript
// In recipe page onMount
if (getWakeLockEnabled()) {
  await requestWakeLock();
}
```

### Decision 4: Unsupported Browser UX

**Question**: How should unsupported browsers (Firefox) display the toggle?

**Options**:
1. **Hide toggle completely**
2. **Show disabled with tooltip**

**Recommendation**: **Option 2 - Show disabled**

**Rationale**:
- User knows feature exists but unavailable
- Explains why (browser limitation)
- Consistent layout across browsers
- May encourage browser switch for serious users

---

## Revised Architecture

### File Structure

**New Files** (2):
```
src/lib/stores/wakeLock.ts          # Simple module (~180 lines)
src/lib/components/WakeLockToggle.svelte  # Toggle UI (~150 lines)
```

**Modified Files** (2):
```
src/lib/components/Header.svelte     # Add toggle (+5 lines code, +20 lines CSS)
src/routes/recipe/[slug]/+page.svelte  # Lifecycle hooks (+15 lines)
```

### Data Flow

```
┌─────────────────────────────────────────────────────────┐
│ wakeLock.ts (Module)                                     │
│ ─────────────────────────────────────────────────────────│
│ • Browser API integration                                │
│ • localStorage persistence                               │
│ • Visibility change listener (single source)             │
│ • WakeLockSentinel lifecycle management                  │
└───────────────────┬─────────────────────────────────────┘
                    │
        ┌───────────┴──────────┬─────────────────┐
        │                      │                  │
        ▼                      ▼                  ▼
┌──────────────┐    ┌──────────────────┐   ┌──────────┐
│ Header.svelte│    │WakeLockToggle.svelte│   │Recipe Page│
│──────────────│    │──────────────────────│   │──────────│
│• Renders     │    │• User interaction    │   │• Requests│
│  toggle      │◄───┤• Visual feedback     │   │  on mount│
│• CSS         │    │• $state reactivity   │   │• Releases│
│  visibility  │    │• Imports module fns  │   │  on unmount│
└──────────────┘    └──────────────────────┘   └──────────┘
```

---

## Implementation Plan

### Phase 1: Core Store (`wakeLock.ts`)

**File**: `/home/user/mise/src/lib/stores/wakeLock.ts`

**Key Features**:
```typescript
// Module-level state
let wakeLockSentinel: WakeLockSentinel | null = null;
let wakeLockEnabled: boolean = false;
let wakeLockSupported: boolean = false;

// Public API
export function initWakeLock(): void
export function isWakeLockSupported(): boolean
export function getWakeLockEnabled(): boolean
export function setWakeLockEnabled(enabled: boolean): void
export async function requestWakeLock(): Promise<boolean>
export function releaseWakeLock(): void
export function getWakeLockActive(): boolean
```

**Critical Implementation Details**:

#### 1. SSR Guards Everywhere
```typescript
export function initWakeLock(): void {
  if (typeof window === 'undefined') return; // SSR guard

  // Feature detection with try-catch
  try {
    wakeLockSupported = 'wakeLock' in navigator &&
                         'request' in navigator.wakeLock;
  } catch {
    wakeLockSupported = false;
  }

  // Load from localStorage
  const stored = localStorage.getItem('mise-wake-lock');
  wakeLockEnabled = stored === 'true';

  // Attach visibility listener
  setupVisibilityListener();
}
```

#### 2. Runtime Feature Detection
Catches Firefox false positive:
```typescript
async function verifyWakeLockSupport(): Promise<boolean> {
  if (!wakeLockSupported) return false;

  try {
    // Test actual request
    const test = await navigator.wakeLock.request('screen');
    await test.release();
    return true;
  } catch {
    wakeLockSupported = false; // Update flag
    return false;
  }
}
```

#### 3. Visibility Change Handler
Single source of truth:
```typescript
function setupVisibilityListener(): void {
  if (typeof document === 'undefined') return;

  document.addEventListener('visibilitychange', handleVisibilityChange);
}

async function handleVisibilityChange(): Promise<void> {
  if (document.visibilityState === 'visible' && wakeLockEnabled) {
    await requestWakeLock();
  }
}
```

#### 4. WakeLock Request with Cleanup
```typescript
export async function requestWakeLock(): Promise<boolean> {
  if (!wakeLockSupported || !wakeLockEnabled) return false;

  // Release old sentinel if exists
  if (wakeLockSentinel && !wakeLockSentinel.released) {
    await wakeLockSentinel.release();
  }

  try {
    wakeLockSentinel = await navigator.wakeLock.request('screen');

    // Listen for auto-release
    wakeLockSentinel.addEventListener('release', () => {
      console.log('[WakeLock] Released');
      wakeLockSentinel = null;
    });

    return true;
  } catch (err) {
    console.error('[WakeLock] Request failed:', err);
    return false;
  }
}
```

#### 5. beforeunload Cleanup
```typescript
if (typeof window !== 'undefined') {
  window.addEventListener('beforeunload', () => {
    releaseWakeLock();
  });
}
```

---

### Phase 2: Toggle Component (`WakeLockToggle.svelte`)

**File**: `/home/user/mise/src/lib/components/WakeLockToggle.svelte`

**Structure**:
```svelte
<script lang="ts">
  import {
    getWakeLockEnabled,
    setWakeLockEnabled,
    getWakeLockActive,
    isWakeLockSupported,
    requestWakeLock,
    releaseWakeLock
  } from '$lib/stores/wakeLock';

  let enabled = $state(getWakeLockEnabled());
  let active = $state(getWakeLockActive());
  let supported = $state(isWakeLockSupported());

  // Poll for active state changes (or use custom events)
  $effect(() => {
    const interval = setInterval(() => {
      active = getWakeLockActive();
    }, 1000);

    return () => clearInterval(interval);
  });

  async function handleToggle() {
    enabled = !enabled;
    setWakeLockEnabled(enabled);

    if (enabled) {
      await requestWakeLock();
    } else {
      releaseWakeLock();
    }

    active = getWakeLockActive();
  }
</script>

<button
  class="wake-lock-toggle"
  class:active
  onclick={handleToggle}
  disabled={!supported}
  aria-label={
    !supported ? 'Keep screen awake (not supported)' :
    active ? 'Keep screen awake is ON' :
    'Keep screen awake is OFF'
  }
  aria-pressed={active}
  title={
    !supported ? 'Not supported in this browser' :
    active ? 'Screen will stay awake' :
    'Keep screen awake while viewing recipes'
  }
>
  {#if active}
    <!-- Sun icon (awake) -->
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <circle cx="12" cy="12" r="5"/>
      <line x1="12" y1="1" x2="12" y2="3"/>
      <line x1="12" y1="21" x2="12" y2="23"/>
      <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
      <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
      <line x1="1" y1="12" x2="3" y2="12"/>
      <line x1="21" y1="12" x2="23" y2="12"/>
      <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
      <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
    </svg>
  {:else}
    <!-- Moon icon (can sleep) -->
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
    </svg>
  {/if}
</button>

<style>
  /* Match theme-toggle styling exactly */
  .wake-lock-toggle {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    color: var(--color-text-secondary);
    cursor: pointer;
    transition: all var(--transition-fast);
  }

  .wake-lock-toggle:hover:not(:disabled) {
    border-color: var(--color-accent);
    background: var(--color-highlight);
  }

  .wake-lock-toggle.active {
    color: var(--color-accent);
    border-color: var(--color-accent);
  }

  .wake-lock-toggle:disabled {
    opacity: 0.3;
    cursor: not-allowed;
  }

  .wake-lock-toggle svg {
    width: 20px;
    height: 20px;
  }

  /* Hide when not visible (controlled by Header) */
  .wake-lock-toggle:not(.visible) {
    opacity: 0;
    width: 0;
    margin: 0;
    padding: 0;
    border: none;
  }
</style>
```

---

### Phase 3: Header Integration

**File**: `/home/user/mise/src/lib/components/Header.svelte`

**Changes**:

#### 1. Import component
```svelte
<script lang="ts">
  import WakeLockToggle from './WakeLockToggle.svelte';
  // ... existing imports
</script>
```

#### 2. Render with CSS visibility (NOT conditional)
```svelte
<div class="header-actions">
  <UploadButton />

  <WakeLockToggle
    class="wake-lock-toggle"
    class:visible={$pageTitle.showBackButton}
  />

  <button class="theme-toggle" ...>
</div>
```

#### 3. Add CSS transition
```css
.header-actions {
  /* Ensure flex gap handles collapsing width */
  display: flex;
  gap: var(--spacing-md);
}

.header-actions .wake-lock-toggle {
  transition:
    opacity var(--transition-fast),
    width var(--transition-fast);
}

.header-actions .wake-lock-toggle:not(.visible) {
  opacity: 0;
  width: 0;
  overflow: hidden;
  pointer-events: none;
}
```

---

### Phase 4: Recipe Page Integration

**File**: `/home/user/mise/src/routes/recipe/[slug]/+page.svelte`

**Changes**:

#### 1. Import wake lock functions
```svelte
<script lang="ts">
  import { requestWakeLock, releaseWakeLock, getWakeLockEnabled } from '$lib/stores/wakeLock';
  // ... existing imports
</script>
```

#### 2. Update onMount (auto-activate if enabled)
```svelte
onMount(async () => {
  // Existing auth check
  isAuthenticated = await checkAuth();

  // Fetch recipe
  if (data.slug) {
    const apiRecipe = await fetchUserRecipe(data.slug);
    if (apiRecipe) {
      recipe = apiRecipe;
    } else {
      notFound = true;
    }
  }
  fetchComplete = true;

  // NEW: Request wake lock if user preference enabled
  if (getWakeLockEnabled() && !notFound) {
    await requestWakeLock();
  }
});
```

#### 3. Update onDestroy (cleanup)
```svelte
onDestroy(() => {
  clearPageTitle();
  releaseWakeLock(); // NEW: Always release when leaving
});
```

**Note**: No need for visibility handler here - `wakeLock.ts` module handles it globally.

---

### Phase 5: Initialize in Layout

**File**: `/home/user/mise/src/routes/+layout.svelte`

**Changes**:

#### 1. Import and initialize
```svelte
<script lang="ts">
  import { onMount } from 'svelte';
  import { initTheme } from '$lib/stores/theme';
  import { initWakeLock } from '$lib/stores/wakeLock'; // NEW

  onMount(() => {
    initTheme();
    initWakeLock(); // NEW: Initialize wake lock module
  });
</script>
```

---

## Testing Strategy

### Manual Testing Checklist

#### Browser Compatibility
- [ ] Chrome 120+ (macOS/Windows/Android) - Full support expected
- [ ] Safari 16.4+ (iOS/macOS) - Full support expected
- [ ] Edge 120+ (Windows) - Full support expected
- [ ] Firefox Latest - Should show disabled button with tooltip
- [ ] Samsung Internet (Android) - Full support expected

#### Feature Functionality
- [ ] Toggle OFF → ON: Lock activates, sun icon shows
- [ ] Toggle ON → OFF: Lock releases, moon icon shows
- [ ] Preference persists: Refresh page, state maintained
- [ ] localStorage: Verify `mise-wake-lock` key exists
- [ ] Home page: Toggle hidden (opacity 0, width 0)
- [ ] Recipe page: Toggle visible and functional

#### Lifecycle
- [ ] Home → Recipe: Lock activates if enabled
- [ ] Recipe → Home: Lock releases, toggle fades out
- [ ] Recipe → Recipe: Lock persists across navigation
- [ ] Browser refresh: Lock reactivates if preference ON
- [ ] Browser back/forward: Lock management correct

#### Visibility Changes
- [ ] Switch tabs: Lock auto-releases
- [ ] Return to tab: Lock re-acquires if enabled
- [ ] Lock device screen: Lock releases
- [ ] Unlock device: Lock re-acquires if enabled

#### Edge Cases
- [ ] Low battery mode: Fails gracefully, no errors
- [ ] Multiple recipe tabs: Each manages independently
- [ ] Rapid toggle clicks: No race conditions
- [ ] Recipe load failure: Lock doesn't activate
- [ ] beforeunload: Lock releases on page close

#### Accessibility
- [ ] Keyboard: Tab reaches button, Enter/Space toggles
- [ ] Screen reader: aria-label announces correctly
- [ ] Focus visible: Clear outline on keyboard focus
- [ ] Touch target: 44×44px, easy to tap
- [ ] Color contrast: Passes WCAG AA (4.5:1 for text)

#### Visual/UX
- [ ] Icon changes: Sun (active) / Moon (inactive)
- [ ] Active state: Amber border, accent color
- [ ] Hover: Border strengthens, background highlight
- [ ] Disabled: Low opacity, cursor not-allowed
- [ ] Transitions: Smooth, no jarring changes
- [ ] Mobile responsive: Fits with other header buttons

---

## Risk Mitigation

### High-Priority Risks

#### Risk 1: Firefox False Positive
- **Mitigation**: Runtime feature test, not just `'wakeLock' in navigator`
- **Fallback**: Disable button, clear tooltip explanation

#### Risk 2: iOS Safari User Interaction Requirement
- **Mitigation**: Request in onMount (after user navigation gesture)
- **Testing**: Test on real iOS devices, not just simulator

#### Risk 3: Memory Leaks from Visibility Listener
- **Mitigation**: Single listener in module, removed on beforeunload
- **Testing**: Dev tools memory profiler over time

#### Risk 4: Component State Loss
- **Mitigation**: Use CSS visibility, not conditional rendering
- **Testing**: Verify toggle persists when navigating recipes

### Medium-Priority Risks

#### Risk 5: Battery Drain Concerns
- **Mitigation**: Default OFF, clear user control
- **Communication**: Tooltip explains what feature does

#### Risk 6: localStorage Disabled (Privacy Mode)
- **Mitigation**: Graceful fallback to session state
- **Testing**: Test in incognito/private mode

#### Risk 7: Screen Wake Lock API Changes
- **Mitigation**: Comprehensive error handling
- **Monitoring**: Console logging for errors

---

## Implementation Sequence

### Day 1: Core Foundation
1. Create `wakeLock.ts` with SSR guards
2. Add runtime feature detection
3. Implement localStorage persistence
4. Add visibility change listener
5. Test in isolation (console.log state changes)

### Day 2: UI Component
1. Create `WakeLockToggle.svelte`
2. Design sun/moon SVG icons
3. Implement toggle logic
4. Style to match theme toggle
5. Test component in isolation

### Day 3: Integration
1. Add toggle to Header with CSS visibility
2. Update recipe page lifecycle
3. Initialize in layout
4. Test full flow: home → recipe → toggle → navigate

### Day 4: Polish & Testing
1. Browser compatibility testing
2. Accessibility audit
3. Edge case testing
4. Performance testing (memory, battery)
5. Documentation

---

## Success Criteria

- ✅ Works in Chrome, Safari, Edge (Chromium 84+, Safari 16.4+)
- ✅ Gracefully degrades in Firefox (disabled, tooltip shown)
- ✅ Preference persists across sessions
- ✅ Wake lock auto-reacquires on visibility change
- ✅ No memory leaks after 30-minute session
- ✅ Passes WCAG AA accessibility audit
- ✅ No console errors in production build
- ✅ Toggle transitions smoothly, matches design system
- ✅ 44×44px touch target, works on mobile
- ✅ Zero impact on home page performance

---

## Browser Support Matrix

| Browser | Version | OS | Support Status | Notes |
|---------|---------|-----|----------------|-------|
| Chrome | 84+ | All | ✅ Full support | Primary target |
| Safari | 16.4+ | iOS/macOS | ✅ Full support | iOS 16.4+ required |
| Edge | 84+ | Windows/macOS | ✅ Full support | Chromium-based |
| Firefox | Any | All | ❌ Not supported | Button disabled, tooltip shown |
| Samsung Internet | 14+ | Android | ✅ Full support | Chromium-based |
| Opera | 70+ | All | ✅ Full support | Chromium-based |

---

## Open Questions for Final Sign-Off

1. ✅ **Architecture**: Simple module *(decided)*
2. ✅ **Icon**: Sun/Moon *(decided)*
3. ✅ **Auto-activate**: Yes if preference enabled *(decided)*
4. ✅ **Unsupported UX**: Show disabled *(decided)*
5. ❓ **Analytics**: Track usage/failures? (Optional)
6. ❓ **First-time UX**: Add tooltip or let user discover?
7. ❓ **Keyboard shortcut**: Add Shift+W or skip?

---

## Review Summary

This plan was reviewed by a specialized agent that examined:
- Existing codebase patterns (theme.ts, pageTitle.ts, Header.svelte)
- Svelte 5 syntax compatibility ($state, $effect, $derived runes)
- Browser API quirks and edge cases
- Accessibility requirements (WCAG AA compliance)
- Performance implications (memory leaks, battery usage)
- SSR/hydration considerations (SvelteKit static adapter)

**Key findings from review:**
- Original conditional rendering approach would cause state loss
- Firefox feature detection requires runtime testing (false positive)
- Visibility change handler should be centralized in module
- Icon metaphor needs to be intuitive (sun/moon recommended)
- Component should match existing Header patterns exactly

**Review status**: ✅ Approved with revisions implemented in this plan

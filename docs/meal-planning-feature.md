# Meal Planning Feature Specification

**Feature:** Combine multiple recipes into coordinated multi-course meals
**Target User:** Serious home cooks planning dinner parties and complex multi-day preparations
**Date:** 2026-01-29
**Status:** Planning Complete - Ready for Implementation

## Vision

A comprehensive reference tool for orchestrating multi-component meals over multiple days. Focused toward dinner party planning but equally useful for simple meals. The feature provides:

1. **Unified Timeline View** - See all prep work grouped by time blocks (T-48h, T-24h, etc.)
2. **Aggregated Ingredients** - Shopping list combining all recipes with per-component breakdown
3. **Saved Meal Plans** - Reusable meal plans for multi-day cooking projects

### Core Philosophy

- **Trust the cook** - Show conflicts, don't try to prevent them. Assume professional-level kitchen management.
- **Consolidation over guidance** - Reference document, not a hand-holding assistant.
- **Timeline-first** - Mise en place approach to multi-component coordination.

## User Flow Example

A cook planning a three-course dinner party can:

1. Create a new meal "Sunday Dinner"
2. Select recipes for starter, main, and dessert
3. View unified timeline showing:
   ```
   T-24h
   ├─ Kombu Cod - Emulsion
   │  • Bloom gelatin sheets
   │  • Make kombu dashi
   ├─ Dessert - Ice Cream Base
   │  • Whisk components
   │  • Freeze overnight

   Service
   ├─ Kombu Cod - Assembly
   │  • Plate emulsion
   │  • Add crispy skin
   ├─ Starter - Salad
   │  • Dress greens
   │  • Plate
   ```
4. View aggregated shopping list with per-component breakdown
5. Save and reference throughout multi-day prep

## Architecture

### Data Model

**Snapshot-based caching approach:**
- Meals store full recipe data as JSONB snapshot at creation
- Snapshots refresh automatically when recipes are edited/deleted
- Reduces API calls, handles deleted recipes gracefully

```sql
CREATE TABLE meals (
  id UUID PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  snapshot JSONB NOT NULL,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

**Snapshot Structure:**
```json
{
  "recipes": [
    {
      "slug": "kombu-cod",
      "course_order": 1,
      "title": "Kombu-Cured Low-Temp Cod",
      "subtitle": "Agar-Kombu Emulsion, Parsley Oil, Crispy Skin",
      "metadata": {
        "serves": 2,
        "active_time": "35 min",
        "total_time": "48h"
      },
      "components": ["Emulsion", "Parsley Oil", "Crispy Skin", "Cod"],
      "ingredients": {
        "Emulsion": [
          "200g kombu dashi",
          "2g agar powder"
        ],
        "Parsley Oil": [
          "100g parsley leaves",
          "200ml neutral oil"
        ]
      },
      "timeline": {
        "T-24h": {
          "Emulsion": [
            "Bloom 2 gelatin sheets in ice water",
            "Make kombu dashi: steep 10g kombu in 200g water at 60°C for 30 min"
          ]
        },
        "Service": {
          "Cod": ["Sear skin-side down until crispy"],
          "Emulsion": ["Plate 2 spoonfuls per portion"]
        }
      },
      "is_deleted": false
    }
  ],
  "aggregated_ingredients": [
    {
      "display": "300g parsley leaves",
      "breakdown": [
        {"component": "Parsley Oil", "recipe": "Kombu Cod", "qty": "100g parsley leaves"},
        {"component": "Salad", "recipe": "Starter Salad", "qty": "200g parsley leaves"}
      ]
    }
  ],
  "timeline_markers": ["T-24h", "T-4h", "Service"],
  "last_snapshot_at": "2026-01-29T14:30:00Z"
}
```

### Timeline Markers (Canonical)

**Chronological order:**
1. `T-48h` - 48 hours before service
2. `T-24h` - 24 hours before service
3. `T-8h` - Day of service (morning)
4. `T-4h` - 4 hours before service
5. `T-1h` - 1 hour before service
6. `Service` - Final plating

**Normalization Rules:**
- Non-standard markers round UP to nearest canonical marker
- Examples:
  - T-6h → T-4h
  - T-17h → T-8h (Day-of)
  - T-31h → T-24h
  - T-2h → T-1h
- Recipes without timeline markers default to `Service`
- Only show markers that have content (hide empty markers)

### Component vs Recipe Display

**Display hierarchy:**
- **Recipe with components:** Show component level only
  ```
  T-24h
  └─ Kombu Cod - Emulsion (component name, not "Kombu Cod → Emulsion")
  └─ Kombu Cod - Parsley Oil
  ```
- **Recipe without components:** Show recipe name
  ```
  Service
  └─ Simple Side Salad (recipe has no components)
  ```

**Nesting levels:**
```
Timeline Marker (T-24h)
└─ Component/Recipe Name
   └─ Step 1
   └─ Step 2
```

### Ingredient Aggregation

**Strategy: Best-effort parsing with conservative matching**

**Parsing pattern:**
```
[quantity] [unit] [ingredient] [, preparation]

Examples:
"200g butter" → qty=200, unit=g, item=butter
"2 large eggs, beaten" → qty=2, quality=large, item=eggs, prep=beaten
"Salt to taste" → unparseable
```

**Matching rules:**
- ✓ Combine if: Same item (case-insensitive) + same unit + no quality/prep differences
  - "200g butter" + "100g butter" = "300g butter"
- ✗ Keep separate if:
  - Different preparation: "200g butter, softened" ≠ "200g butter, cold"
  - Different quality: "2 large eggs" ≠ "2 eggs"
  - Different units: "2 cups flour" ≠ "250g flour"
  - Unparseable: "Salt to taste"
  - **When in doubt: keep separate**

**Display modes:**
1. **Aggregated** - Combined shopping list (default view)
   ```
   • 300g butter
   • 4 eggs
   • 200g butter, softened (kept separate due to prep difference)
   ```

2. **Per-component breakdown** - Toggle to show source
   ```
   • 300g butter
     - 200g from Kombu Cod - Emulsion
     - 100g from Kombu Cod - Parsley Oil
   • 4 eggs
     - 2 from Dessert - Ice Cream Base
     - 2 from Starter - Egg Salad
   ```

### Snapshot Refresh Strategy

**Triggers:**
- Recipe edited → Find meals containing recipe → Regenerate snapshot
- Recipe deleted → Find meals containing recipe → Mark recipe as deleted in snapshot

**User experience:**
- On meal detail page load: Check if snapshot is stale
- If stale: Show loader → Auto-refresh → Display updated content
- If recipe deleted: Show placeholder with original title (if cached), allow user to replace

**Placeholder for deleted recipes:**
```
Course 1: ~~Deleted Recipe~~ (Original Title if available)
[Add Recipe] button to replace
```

## API Design

### PHP Endpoints

```
GET  /api/meals.php
  → List all meals (id, title, description, created_at, updated_at)

GET  /api/meals.php?id=X
  → Get full meal with snapshot
  → Returns complete snapshot for rendering (single call, no additional recipe fetches)

POST /api/meals.php
  Body: {
    title: string,
    description?: string,
    recipe_slugs: string[]  // Order = course order
  }
  → Create meal
  → Generates snapshot from recipes
  → Returns meal with full snapshot

PUT  /api/meals.php?id=X
  Body: {
    title?: string,
    description?: string,
    recipe_slugs?: string[]  // Update course order
  }
  → Update meal
  → Regenerates snapshot if recipes changed

DELETE /api/meals.php?id=X
  → Delete meal

POST /api/meals.php?id=X&action=refresh
  → Force refresh snapshot (manual trigger if needed)
```

### Recipe Hooks (New)

Add to existing `recipes.php`:

```php
// After successful recipe update
function onRecipeUpdated($slug) {
  $meals = findMealsContainingRecipe($slug);
  foreach ($meals as $meal) {
    regenerateSnapshot($meal['id']);
  }
}

// After recipe deletion
function onRecipeDeleted($slug) {
  $meals = findMealsContainingRecipe($slug);
  foreach ($meals as $meal) {
    markRecipeDeletedInSnapshot($meal['id'], $slug);
  }
}
```

### Slug Generation

**Format:** `[title-slug]-[6-char-guid]`

**Examples:**
- `sunday-dinner-a3f9k2`
- `birthday-party-b8x4m1`
- `weeknight-meal-c2n7p9`

**Algorithm:**
1. Generate slug from title (lowercase, hyphens, remove special chars)
2. Append 6-character random alphanumeric (lowercase + numbers, no ambiguous chars like 0/O, 1/l)
3. Check for collision (unlikely but handle gracefully)
4. Character set: `abcdefghjkmnpqrstuvwxyz23456789` (32 chars, ~1 billion combinations)

## UI Components

### 1. Meals List Page (`/meals`)

**Layout:**
- Header with "New Meal" button
- Grid of meal cards (similar to recipe cards)

**Meal card preview shows:**
- Title
- Description (truncated)
- Recipe count: "3 recipes"
- Timeline span: "Starts T-24h" or "Same-day meal"
- Created date

### 2. Create Meal Modal

**Triggered by:** "New Meal" button

**Flow:**
1. Enter title (required)
2. Enter description (optional)
3. Multi-select recipe picker
   - Grid of recipe cards with checkboxes
   - Filter to user-added recipes only
   - Selection order = course order (show numbered badges)
   - Minimum 1 recipe required
4. Submit → Generate snapshot → Navigate to meal detail

**UX details:**
- Visual feedback for selected recipes (highlight + number badge)
- Can reorder by clicking again (cycles through positions)
- "Create Meal" button disabled until title + ≥1 recipe

### 3. Meal Detail Page (`/meal/[slug]`)

**URL format:** `/meal/sunday-dinner-a3f9k2`

**Sections:**

#### Header
- Title (editable inline or via edit modal)
- Description
- Action buttons: Edit, Delete

#### Course Overview
```
Course 1: Kombu-Cured Low-Temp Cod
Course 2: Starter Salad
Course 3: Yuzu Green Tea Granité
```

#### Ingredients Section
```
[Toggle: "Aggregated" | "Per-Component"]

Aggregated view:
• 200g kombu dashi
• 2g agar powder
• 100g parsley leaves

Per-component view:
• 200g kombu dashi
  - Kombu Cod - Emulsion
• 2g agar powder
  - Kombu Cod - Emulsion
• 100g parsley leaves
  - Kombu Cod - Parsley Oil
```

#### Timeline Section

**Collapsible accordion structure:**

```
[T-24h] ▼
  [Kombu Cod - Emulsion] ▼
    • Bloom 2 gelatin sheets in ice water
    • Make kombu dashi: steep 10g kombu in 200g water at 60°C for 30 min

  [Kombu Cod - Parsley Oil] ▼
    • Blanch parsley in boiling salted water for 10 seconds
    • Shock in ice bath

[T-4h] ▼
  [Kombu Cod - Cod] ▼
    • Season cod with salt
    • Vacuum seal with kombu

[Service] ▼
  [Kombu Cod - Cod] ▼
    • Remove from bag, pat dry
    • Sear skin-side down until crispy

  [Starter Salad] ▼
    • Dress greens with vinaigrette
    • Plate
```

**Visual hierarchy:**
- Timeline markers: Large, bold, Cormorant Garamond
- Component/Recipe names: Medium, DM Sans
- Steps: Regular body text, bullet points
- Chevrons for expand/collapse (44px touch targets)

**Empty state:**
- If no markers: "No timeline information available"
- Markers without steps are hidden

### 4. Edit Meal Flow

**Triggered by:** Edit button on meal detail

**Modal contains:**
- Title input
- Description textarea
- Recipe list with drag handles for reordering
- "Add Recipe" button (opens multi-select picker)
- Remove recipe buttons (X icon on each)

**UX details:**
- Drag-drop to reorder courses
- Course numbers update in real-time
- Save → Regenerate snapshot → Reload page

### 5. Navigation Integration

**Add to Header component:**
- New "Meals" tab (alongside existing nav)
- Highlight active on `/meals` routes

## Edge Cases & Error Handling

### Deleted Recipes
**Scenario:** Recipe is deleted after being added to meal

**Handling:**
- Snapshot retains original recipe data with `is_deleted: true` flag
- Meal detail shows placeholder:
  ```
  Course 1: ~~Kombu-Cured Low-Temp Cod~~
  This recipe has been deleted.
  [Add Recipe] button to replace
  ```
- Timeline/ingredients exclude deleted recipe data

### Empty Timeline Markers
**Scenario:** Recipe has no timeline markers, or only uses non-canonical markers

**Handling:**
- Default all steps to `Service` marker
- Round non-standard markers UP to canonical
- Hide markers with no content in final view

### Ingredient Parsing Failures
**Scenario:** Ingredient string doesn't match parsing pattern

**Examples:**
- "Salt to taste"
- "A pinch of saffron"
- "Leftover roasted vegetables"

**Handling:**
- Keep as-is in separate line (don't aggregate)
- Show in both aggregated and per-component views unchanged

### Snapshot Staleness
**Scenario:** Recipe edited, meal snapshot out of date

**Handling:**
- Auto-refresh on page load (transparent to user)
- Show loader: "Updating meal plan..." (brief, < 1s typically)
- If refresh fails: Show error, offer manual refresh button

### Course Conflicts
**Scenario:** Multiple dishes need active attention at same timeline marker

**Handling:**
- Show both in timeline (no warnings or conflicts)
- Trust cook to manage (professional-level assumption)

### Duplicate Recipes
**Scenario:** Same recipe added multiple times to meal

**Handling:**
- Allowed (e.g., same sauce for multiple courses)
- Treat as separate courses in timeline
- Ingredients show correctly in breakdown view

### Network Failures
**Scenario:** API call fails during meal creation/edit

**Handling:**
- Show error message
- Don't save partial data
- Allow retry

## File Structure

```
api/
├── meals.php                     # NEW: Meal CRUD endpoints
├── lib/
│   ├── snapshot.php              # NEW: Snapshot generation logic
│   ├── ingredients.php           # NEW: Ingredient parsing/aggregation
│   └── timeline.php              # NEW: Timeline merging/normalization

src/lib/
├── api/
│   └── meals.ts                  # NEW: Meal API client
├── components/
│   ├── MealCard.svelte           # NEW: Meal preview card
│   ├── MealTimeline.svelte       # NEW: Timeline accordion view
│   ├── MealIngredients.svelte    # NEW: Ingredient list with toggle
│   ├── MealModal.svelte          # NEW: Create/edit meal modal
│   ├── RecipePicker.svelte       # NEW: Multi-select recipe grid
│   └── Header.svelte             # MODIFIED: Add Meals nav tab
├── types/
│   └── index.ts                  # MODIFIED: Add Meal types
└── routes/
    └── meals/
        ├── +page.svelte          # NEW: Meal list page
        └── [slug]/
            └── +page.svelte      # NEW: Meal detail page

docs/
└── meal-planning-feature.md      # THIS FILE
```

## Implementation Task Breakdown

### Phase 1: Backend Foundation (6 tasks)

1. **Create meals database schema and migration**
   - Define meals table with JSONB snapshot column
   - Add indexes for recipe lookup
   - Write migration script

2. **Build snapshot generation logic**
   - Parse recipe markdown (frontmatter + content)
   - Extract components, ingredients, timeline steps
   - Build unified snapshot structure
   - Handle multi-component recipes

3. **Implement ingredient parsing and aggregation**
   - Parse ingredient strings (qty, unit, item, prep)
   - Best-effort matching logic
   - Generate aggregated list with breakdown
   - Conservative approach (when in doubt, separate)

4. **Implement timeline merging and normalization**
   - Parse method sections for timeline markers
   - Normalize non-standard markers (round up to canonical)
   - Group by component/recipe
   - Filter empty markers

5. **Build PHP API endpoints for meals**
   - CRUD operations (list, get, create, update, delete)
   - Single endpoint returns full snapshot (reduce calls)
   - Error handling and validation

6. **Add recipe edit/delete hooks**
   - Find meals containing edited/deleted recipes
   - Trigger snapshot regeneration
   - Mark deleted recipes with flag

### Phase 2: Frontend Infrastructure (1 task)

7. **Create meal API client (TypeScript)**
   - Type definitions for Meal, Snapshot structures
   - API methods wrapping fetch calls
   - Error handling

### Phase 3: UI Components (5 tasks)

8. **Build meals list page UI**
   - Grid layout with meal cards
   - "New Meal" button
   - Empty state

9. **Build create meal flow**
   - Modal with title/description inputs
   - Multi-select recipe picker with checkboxes
   - Selection order = course order
   - Validation (min 1 recipe)

10. **Build meal detail page with timeline**
    - Timeline collapsible sections (accordion)
    - Marker → Component → Steps hierarchy
    - Visual design following "The Pass" system
    - Touch-friendly expand/collapse

11. **Build ingredient view with toggle**
    - Aggregated list view
    - Per-component breakdown view
    - Toggle button to switch modes
    - State resets on page load

12. **Build edit meal flow**
    - Modal with editable title/description
    - Drag-drop course reordering
    - Add/remove recipes
    - Save triggers snapshot regeneration

### Phase 4: Integration & Polish (4 tasks)

13. **Add 'Meals' navigation tab**
    - Update Header component
    - Active state highlighting
    - Route to `/meals`

14. **Implement deleted recipe placeholder**
    - Detect `is_deleted` flag in snapshot
    - Show struck-through title
    - "Add Recipe" button to replace
    - Exclude from timeline/ingredients

15. **Add loading states for snapshot refresh**
    - Detect stale snapshots on page load
    - Show loader during regeneration
    - Handle refresh failures gracefully

16. **Generate unique 6-character slugs**
    - Title slugification + random suffix
    - Collision detection and retry
    - Use safe character set (no ambiguous chars)

## Future Enhancements (Out of Scope)

- Smart ingredient unit conversion (cups ↔ grams)
- Timeline conflict detection/warnings
- Print-friendly view for kitchen reference
- "Used in meals" display on recipe detail pages
- Meal templates (save as template, instantiate new meals)
- Shopping list export (PDF, email)
- Nutritional aggregation across meal
- Timer integration for timeline steps
- Meal search functionality

## Design Adherence

All UI components follow "The Pass" design system:

**Typography:**
- Timeline markers: Cormorant Garamond
- Component/recipe names, UI elements: DM Sans
- Steps: DM Sans body

**Colors:**
- Light mode: Warm paper backgrounds (#F7F5F0), copper accent (#B45309)
- Dark mode: Ink backgrounds (#0C0A09), amber accent (#F59E0B)

**Spacing:**
- Card padding: 20px (xl)
- Section gaps: 28px (2xl)
- Step spacing: 12px (md)

**Visual details:**
- Paper grain texture on cards
- Subtle border on hover (copper/amber)
- 6px border radius on cards/inputs

## Success Criteria

Feature is complete when:

1. ✓ User can create a meal with multiple recipes
2. ✓ Meal detail shows unified timeline grouped by canonical markers
3. ✓ Ingredient list shows aggregated view + per-component breakdown
4. ✓ Timeline displays component-level hierarchy (not recipe when components exist)
5. ✓ Non-standard timeline markers normalize to canonical markers
6. ✓ Meals can be edited (reorder courses, add/remove recipes)
7. ✓ Snapshot refreshes automatically when recipes are edited/deleted
8. ✓ Deleted recipes show placeholder with option to replace
9. ✓ Meal slugs are unique and URL-friendly
10. ✓ "Meals" navigation tab works and highlights correctly
11. ✓ All UI follows "The Pass" design system
12. ✓ Touch-friendly interface (44px minimum tap targets)

## Questions Resolved During Planning

| Question | Decision |
|----------|----------|
| Calculated metadata on meals? | No, keep simple |
| Course labeling? | Numeric (Course 1, 2, 3...) |
| Duplicate recipes in meal? | Allowed |
| Meal-specific notes? | No |
| Recipe deletion handling? | Show placeholder, allow replace |
| Recipe updates? | Snapshot on add, refresh on edit |
| Timeline markers for simple recipes? | Default to "Service" |
| Show empty markers? | No, hide them |
| Timeline display order? | Chronological (T-48h → Service) |
| Show course in timeline? | Yes |
| Component display? | Show component level only when present |
| Non-standard marker rounding? | Round UP to nearest canonical |
| Ingredient aggregation? | Best-effort, conservative |
| Unit conversion? | No, keep separate |
| Ingredient display modes? | Aggregate + per-component toggle |
| Creation flow? | Requires ≥1 recipe |
| Course reordering? | Yes, drag-drop |
| Meal card preview info? | Title, desc, recipe count, timeline span |
| Print view? | Not in v1 |
| API structure? | Single endpoint returns full snapshot |
| Snapshot refresh UX? | Auto-refresh with loader |
| Search integration? | Not in v1 |
| Slug format? | `title-slug-a3f9k2` (6-char GUID) |
| Recipe sources? | User recipes only (bundled removed later) |

---

**Document Version:** 1.0
**Last Updated:** 2026-01-29
**Implementation Status:** Ready to build

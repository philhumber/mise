# Mïse - Recipe Collection App

A personal recipe manager for serious home cooks. Built with SvelteKit, designed for phone-first use in the kitchen.

## Brand

**Name:** mïse (lowercase, diaeresis on the i)
**Philosophy:** Mise en place - everything in its place. Calm, prepared, professional.

## Design System: "The Pass"

Inspired by the moment before service when everything is ready. Austere elegance with warmth.

### Typography

- **Display:** Cormorant Garamond (elegant serif) - titles, subtitles, stats
- **Body:** DM Sans (clean modern sans) - meta text, instructions, UI elements
- **Logo:** Cormorant Garamond, lowercase, letter-spacing 0.04em

### Color Palette

**Light Mode (Warm Paper)**

```
Background:      #F7F5F0
Surface:         #FFFDF9
Text Primary:    #1C1917
Text Secondary:  #57534E
Text Tertiary:   #A8A29E
Accent:          #B45309 (copper)
Border:          #E7E5E4
```

**Dark Mode (Ink & Copper)**

```
Background:      #0C0A09
Surface:         #1C1917
Text Primary:    #FAFAF9
Text Secondary:  #A8A29E
Text Tertiary:   #78716C
Accent:          #F59E0B (amber)
Border:          #292524
```

### Visual Details

- Subtle paper grain texture on cards (35% opacity light, 15% dark)
- Very light background texture (12% opacity light, 6% dark)
- Copper accent bar appears on card hover
- Timeline summary has copper left border with tinted background
- Method steps have vertical timeline border
- Section headers extend with horizontal rule

### Spacing Scale

```
xs:  4px
sm:  8px
md:  12px
lg:  16px
xl:  20px
2xl: 28px
3xl: 40px
4xl: 56px
```

### Border Radii

- Small: 3px (pills, tags)
- Medium: 6px (cards, inputs)

## Recipe Content Structure

Recipes are markdown files with YAML frontmatter. Multi-component structure with timeline-based methods.

### Frontmatter Schema

```yaml
title: 'Kombu-Cured Low-Temp Cod'
subtitle: 'Agar-Kombu Emulsion, Parsley Oil, Crispy Skin'
category: main | starter | dessert | side | drink | sauce
difficulty: easy | intermediate | advanced
active_time: '35 min'
total_time: '48h'
serves: 2
tags: [seafood, sous-vide, make-ahead]
```

### Timeline Markers (Canonical)

| Marker    | Use Case                |
| --------- | ----------------------- |
| `T-48h`   | 48 hours before service |
| `T-24h`   | 24 hours before service |
| `T-12h`   | 12 hours before service |
| `T-4h`    | 4 hours before service  |
| `T-1h`    | 1 hour before service   |
| `Day-of`  | Morning of service      |
| `Service` | Final plating steps     |

See `docs/recipe-format.md` for full timeline documentation.

## Tech Stack

- **Framework:** SvelteKit
- **Styling:** CSS custom properties, no framework
- **Content:** Markdown with gray-matter for frontmatter
- **Search:** Client-side fuzzy search (Fuse.js or similar)

## Key Screens

1. **Home/Browse** - Search bar, category pills, recipe cards
2. **Recipe Detail** - Timeline summary, grouped ingredients, method steps

## Roadmap Features

- Step-by-step cook mode
- Built-in timers
- Unit conversion toggle (metric/imperial)
- Keep screen awake toggle
- Shopping list aggregation
- Recipe scaling

## File Structure

```
src/
├── lib/
│   ├── assets/
│   │   └── favicon.svg
│   ├── components/
│   │   ├── RecipeCard.svelte
│   │   ├── SearchBar.svelte
│   │   ├── CategoryFilter.svelte
│   │   ├── Timeline.svelte
│   │   ├── IngredientGroup.svelte
│   │   └── MethodStep.svelte
│   ├── styles/
│   │   └── tokens.css
│   └── utils/
│       ├── recipes.ts
│       └── timeline.ts
├── routes/
│   ├── +page.svelte (Home)
│   └── recipe/
│       └── [slug]/
│           └── +page.svelte (Detail)
└── content/
    └── recipes/
        ├── kombu-cod.md
        ├── yuzu-granite.md
        └── ...
```

## Design Principles

1. **Content-first** - Typography carries the design, images optional
2. **Negative space is intentional** - Let content breathe
3. **Touch-friendly** - Large tap targets for wet/floury hands
4. **Readable at arm's length** - Phone propped up, not in hand
5. **Calm, not busy** - Restrained chrome, no visual noise

## Project Management

**JIRA Project:** [MISE Board](https://philhumber.atlassian.net/jira/software/projects/MISE/boards/34/backlog)

### Sprint Planning (Complete)

- **Sprints 1-9:** MVP (193 story points) - Core app with PWA support
- **Sprints 10-11:** Future features (52 story points) - Cook Mode & Shopping List
- **Total:** 61 tasks across 8 epics

### Next Steps

1. **MISE-53** - Build IngredientGroup.svelte component
2. **MISE-54** - Create MethodStep.svelte with timeline border
3. **MISE-56** - Create Notes.svelte component

### Completed

- **MISE-30, 31, 32, 33** - Recipe data layer (`src/lib/utils/recipes.ts`)
  - `getAllRecipes()` - Returns RecipeMeta[] for listing pages
  - `getRecipeBySlug(slug)` - Returns full Recipe with rendered HTML
  - Validates frontmatter at build time, throws typed errors

- **MISE-34, 35, 36, 37** - Search, content, and server load functions
  - `searchRecipes()` - Fuse.js fuzzy search utility (`src/lib/utils/search.ts`)
  - 3 sample recipes in `content/recipes/` (kombu-cod, yuzu-granite, kitchen-hydration)
  - Server load functions for home and recipe detail pages
  - Recipe format documentation (`docs/recipe-format.md`)

- **MISE-38, 39, 40, 41** - Home page components
  - Header.svelte - Logo and theme toggle (was already implemented)
  - SearchBar.svelte - Debounced fuzzy search with clear button
  - CategoryFilter.svelte - Pill buttons for category filtering
  - RecipeCard.svelte - Recipe summary card (was already implemented)
  - Home page now has full search + filter functionality

- **MISE-52, 55** - Timeline component and marker parsing
  - Timeline.svelte - Sticky sidebar (desktop) + floating pill with bottom sheet drawer (mobile)
  - `src/lib/utils/timeline.ts` - SSR-compatible regex parser for timeline items
  - TimelineItem type added to `src/lib/types/index.ts`
  - Recipes migrated to canonical marker format (T-48h, Day-of, Service)
  - `docs/recipe-format.md` updated with timeline documentation
  - Recipe detail page integrated with conditional grid layout

### Scripts

- `scripts/jira.ps1` - JIRA CLI for issue management
- `scripts/jira-batch-create.ps1` - Batch issue creation
- `scripts/jira-tasks.json` - Complete task definitions

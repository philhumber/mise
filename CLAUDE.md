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
title: "Kombu-Cured Low-Temp Cod"
subtitle: "Agar-Kombu Emulsion, Parsley Oil, Crispy Skin"
category: main | starter | dessert | side | drink | sauce
difficulty: easy | intermediate | advanced
active_time: "35 min"
total_time: "48h"
serves: 2
tags: [seafood, sous-vide, make-ahead]
```

### Timeline Markers

- `T-48h`, `T-24h`, `T-4h` - countdown format
- `Day of`, `Day of (Morning)` - same-day prep
- `Service` - final steps at plating

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
│   ├── components/
│   │   ├── RecipeCard.svelte
│   │   ├── SearchBar.svelte
│   │   ├── CategoryFilter.svelte
│   │   ├── Timeline.svelte
│   │   ├── IngredientGroup.svelte
│   │   └── MethodStep.svelte
│   └── styles/
│       └── tokens.css
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

1. **Start Sprint 1** - Initialize SvelteKit project (MISE-14)
2. **Manual JIRA cleanup** - Move issues to sprints 4, 5, 7, 9, 10 (hit API rate limit)
3. **Delete `mise/` subfolder** - Artifact from early SvelteKit init attempt

### Scripts

- `scripts/jira.ps1` - JIRA CLI for issue management
- `scripts/jira-batch-create.ps1` - Batch issue creation
- `scripts/jira-tasks.json` - Complete task definitions

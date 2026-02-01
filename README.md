# mïse

A personal recipe manager for serious home cooks. Phone-first design for use in the kitchen.

## What is mïse?

Mïse (from *mise en place* — "everything in its place") is a recipe collection app designed for how cooks actually work:

- **Timeline-based recipes** — Multi-day recipes show what to do when (T-48h, Day-of, Service)
- **Phone-friendly** — Large touch targets for floury hands, readable at arm's length
- **No distractions** — Clean typography, no ads, no popups
- **Dark mode** — Easy on the eyes during late-night prep

## Features

- Browse recipes by category (main, starter, dessert, side, drink, sauce)
- Fuzzy search across titles, tags, and subtitles
- Responsive timeline navigation for complex recipes
- Light/dark theme toggle
- Upload your own recipes via markdown files
- **Meal Planning** — Combine multiple recipes into unified meal plans
  - Aggregated timeline showing all prep steps chronologically
  - Combined ingredient list grouped by component
  - Stale detection when source recipes change
- **Wake Lock** — Keep screen awake while cooking

## Running Locally

```bash
npm install
npm run dev
```

Open [http://localhost:5173](http://localhost:5173)

## Uploading Recipes

Recipes are markdown files with YAML frontmatter. You can upload them through the app's upload button (requires authentication) or add them directly to `src/content/recipes/`.

### Recipe Format

```markdown
---
title: 'Recipe Name'
subtitle: 'Optional description'
category: main
difficulty: intermediate
active_time: '30 min'
total_time: '2h'
serves: 4
tags: [tag1, tag2]
---

## Ingredients

- 200g ingredient one
- 100ml ingredient two

## Method

1. First step instructions.
2. Second step instructions.

## Notes

Optional tips or variations.
```

### Required Frontmatter Fields

| Field | Values |
|-------|--------|
| `title` | Recipe name |
| `category` | `main`, `starter`, `dessert`, `side`, `drink`, `sauce` |
| `difficulty` | `easy`, `intermediate`, `advanced` |
| `active_time` | Hands-on time (e.g., "30 min") |
| `total_time` | Total time including passive (e.g., "4h") |
| `serves` | Number of servings |
| `tags` | Array of tags |

### Timeline Markers (for multi-day recipes)

For recipes with advance prep, use timeline markers in your Method section:

```markdown
## Method

### T-48h

1. Start the brine...

### Day-of

2. Remove from brine...

### Service

3. Final plating...
```

Available markers: `T-48h`, `T-24h`, `T-12h`, `T-4h`, `T-90m`, `T-1h`, `Day-of`, `Service` (or any `T-Xh`/`T-Xm` format)

See [docs/recipe-format.md](docs/recipe-format.md) for complete documentation.

## Tech Stack

- **Frontend:** SvelteKit 2 + Svelte 5
- **Styling:** CSS custom properties
- **Backend:** PHP + PostgreSQL (for uploaded recipes)
- **Search:** Fuse.js

## Building for Production

```bash
npm run build
```

Output goes to `/build`. Deploy the static files along with the `/api` directory for full functionality.

## License

Private project.

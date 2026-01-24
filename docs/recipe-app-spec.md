# Mïse - Recipe Collection App

## Project Specification

## Overview

Mïse (from "mise en place") is a personal recipe collection management app for browsing, searching, and reading recipes stored as markdown files with YAML frontmatter. Built with SvelteKit for a fast, static-first approach with room to expand. Sibling project to Qvé (wine collection manager).

## Tech Stack

- **Framework:** SvelteKit 2 with TypeScript
- **Markdown:** gray-matter (frontmatter parsing) + marked or mdsvex (rendering)
- **Search:** Fuse.js (client-side fuzzy search)
- **Styling:** Tailwind CSS (or plain CSS if preferred)
- **Build:** Static adapter for SSG, can switch to node adapter later

## Project Structure

```
mise/
├── src/
│   ├── lib/
│   │   ├── components/
│   │   │   ├── RecipeCard.svelte      # Preview card for listings
│   │   │   ├── RecipeContent.svelte   # Full recipe renderer
│   │   │   └── SearchBar.svelte
│   │   ├── utils/
│   │   │   ├── recipes.ts             # Load/parse recipe files
│   │   │   └── search.ts              # Fuse.js search config
│   │   └── types/
│   │       └── recipe.ts              # TypeScript interfaces
│   ├── routes/
│   │   ├── +page.svelte               # Home - browse/search
│   │   ├── +page.server.ts            # Load recipe index
│   │   └── recipe/
│   │       └── [slug]/
│   │           ├── +page.svelte       # Individual recipe view
│   │           └── +page.server.ts    # Load single recipe
│   └── app.html
├── content/
│   └── recipes/                       # Markdown recipe files
├── static/
│   └── images/
│       └── recipes/                   # Recipe photos
├── svelte.config.js
├── package.json
└── tsconfig.json
```

## Recipe Frontmatter Schema

Each recipe is a markdown file in `content/recipes/` with this frontmatter structure:

```yaml
---
title: Recipe Title
slug: recipe-title # URL-safe, matches filename
description: Brief description for cards/search

# Categorisation
category: main # starter, main, dessert, side, sauce, etc.
cuisine: french # optional
tags: [sous-vide, make-ahead, dinner-party]

# Timing (minutes)
prepTime: 30
cookTime: 45
restTime: 10 # optional

serves: 4
difficulty: intermediate # easy, intermediate, advanced

# Structured ingredients for future features
ingredients:
  - item: ingredient name
    quantity: 500
    unit: g
    notes: optional prep notes
  - item: another ingredient
    quantity: 2
    unit: tbsp

# Optional metadata
source: original # or URL, cookbook name
image: recipe-image.jpg # relative to /static/images/recipes/
created: 2024-01-15
updated: 2025-01-20
---
# Recipe content in markdown below...
```

## TypeScript Interfaces

```typescript
interface Ingredient {
	item: string;
	quantity: number;
	unit: string;
	notes?: string;
}

interface RecipeMeta {
	title: string;
	slug: string;
	description: string;
	category: string;
	cuisine?: string;
	tags: string[];
	prepTime: number;
	cookTime: number;
	restTime?: number;
	serves: number;
	difficulty: 'easy' | 'intermediate' | 'advanced';
	ingredients: Ingredient[];
	source?: string;
	image?: string;
	created?: string;
	updated?: string;
}

interface Recipe extends RecipeMeta {
	content: string; // Rendered HTML from markdown body
}
```

## MVP Features (Phase 1)

1. **Browse recipes** - Grid/list view of all recipes showing title, description, category, timing
2. **Search** - Fuzzy search across title, description, tags, ingredients
3. **View recipe** - Full recipe page with rendered markdown content
4. **Filter by category** - Simple category tabs or dropdown

## Future Features (Context Only)

- Filter by ingredient
- Shopping list generation (aggregate ingredients across selected recipes)
- Meal planning calendar
- Recipe scaling (adjust serves/quantities)
- Print-friendly view

## Sample Recipe File

Create `content/recipes/sample-recipe.md` as a starter:

```markdown
---
title: Sample Recipe
slug: sample-recipe
description: A sample recipe to test the app structure
category: main
tags: [quick, weeknight]
prepTime: 15
cookTime: 30
serves: 4
difficulty: easy
ingredients:
  - item: sample ingredient
    quantity: 500
    unit: g
---

## Ingredients

- 500g sample ingredient

## Method

1. First step
2. Second step
3. Third step

## Notes

Any additional notes here.
```

## Implementation Notes

- Use `import.meta.glob` or Node fs in server load functions to read recipe files
- Pre-render all routes at build time (static adapter)
- Keep `content/` outside `src/` for portability
- Fuse.js search index built at page load from recipe metadata
- Mobile-responsive design from the start

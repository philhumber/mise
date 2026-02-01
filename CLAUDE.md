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

### Timeline Markers

Flexible time-based markers are supported:

| Format | Examples | Use Case |
|--------|----------|----------|
| `T-Xh` | `T-48h`, `T-24h`, `T-2h` | Hours before service |
| `T-Xm` | `T-90m`, `T-30m` | Minutes before service |
| `Day-of` | | Morning of service |
| `Service` | | Final plating steps |

Markers are automatically sorted chronologically. See `docs/recipe-format.md` for full documentation.

## Tech Stack

- **Frontend:** SvelteKit 2 + Svelte 5 (static adapter)
- **Backend:** PHP API with PostgreSQL database
- **Styling:** CSS custom properties, no framework
- **Content:** Markdown with YAML frontmatter (gray-matter)
- **Search:** Client-side fuzzy search (Fuse.js)
- **Deployment:** Static build + PHP API on shared hosting

## Commands

```bash
npm install       # Install dependencies
npm run dev       # Start dev server (http://localhost:5173)
npm run build     # Production build to /build
npm run preview   # Preview production build
npm run check     # TypeScript + Svelte type checking
npm run lint      # ESLint + Prettier check
npm run format    # Auto-format with Prettier
```

## Environment Setup

### Frontend (SvelteKit)

No environment variables required for development. The static build works standalone with bundled recipes.

### Backend (PHP + PostgreSQL)

For recipe upload functionality:

1. Create `api/config.php` with database credentials (see `api/config.php.example`)
2. Set up PostgreSQL database with recipes table
3. Configure `UPLOAD_PASSWORD` for authentication

## Key Screens

1. **Home/Browse** - Search bar, category pills, recipe cards (hamburger menu navigation)
2. **Recipe Detail** - Timeline summary, grouped ingredients, method steps, wake lock toggle
3. **Meals List** - Browse saved meal plans with recipe counts and timeline spans
4. **Meal Detail** - Aggregated timeline and ingredients across all recipes in the meal

## Features

### Meal Planning
Combine multiple recipes into unified meal plans with:
- **Aggregated Timeline** - All recipe steps merged chronologically (T-48h → Service)
- **Combined Ingredients** - Grouped by component across all recipes
- **Course Ordering** - Arrange recipes in serving order
- **Stale Detection** - Flag when source recipes have been modified since meal creation
- **Snapshot Refresh** - Regenerate meal data from current recipe versions

### Wake Lock
Keep screen awake while viewing recipes - toggle in recipe detail header.

## Roadmap Features

- Step-by-step cook mode
- Built-in timers
- Unit conversion toggle (metric/imperial)
- Recipe scaling

## File Structure

```
api/                          # PHP backend (deployed alongside static build)
├── auth.php                  # Session-based authentication
├── config.php                # Database config (not in git)
├── recipes.php               # Recipe CRUD endpoints
├── meals.php                 # Meal CRUD endpoints
├── migrations/               # Database migrations
│   └── 001_create_meals.sql
└── lib/
    ├── db.php                # Database connection
    ├── validation.php        # Input validation
    ├── recipe-parser.php     # Ingredient extraction
    ├── timeline-parser.php   # Timeline aggregation
    └── snapshot.php          # Recipe snapshot creation

src/
├── content/
│   └── recipes/              # Sample recipes (build-time, bundled)
│       ├── kitchen-hydration-drink.md
│       ├── kombu-cod-recipe.md
│       └── yuzu-green-tea-granite.md
├── lib/
│   ├── api/
│   │   ├── recipes.ts        # Recipe API client
│   │   └── meals.ts          # Meal API client
│   ├── assets/
│   │   └── favicon.svg
│   ├── components/
│   │   ├── CategoryFilter.svelte
│   │   ├── Header.svelte           # Hamburger menu navigation
│   │   ├── RecipeCard.svelte
│   │   ├── SearchBar.svelte
│   │   ├── Timeline.svelte
│   │   ├── UploadButton.svelte
│   │   ├── UploadModal.svelte
│   │   ├── WakeLockToggle.svelte   # Screen wake lock
│   │   ├── MealCard.svelte         # Meal list card
│   │   ├── MealModal.svelte        # Create/edit meal
│   │   ├── MealTimeline.svelte     # Aggregated timeline
│   │   └── MealIngredients.svelte  # Aggregated ingredients
│   ├── stores/
│   │   ├── theme.ts
│   │   ├── pageTitle.ts
│   │   └── wakeLock.ts       # Wake lock state
│   ├── styles/
│   │   └── tokens.css
│   ├── types/
│   │   └── index.ts          # Includes Meal types
│   └── utils/
│       ├── search.ts         # Fuse.js fuzzy search
│       └── timeline.ts       # Timeline marker parsing
└── routes/
    ├── +layout.svelte
    ├── +layout.ts
    ├── +page.svelte          # Home (search + browse)
    ├── recipe/
    │   └── [slug]/
    │       ├── +page.svelte  # Recipe detail
    │       └── +page.ts
    └── meals/
        ├── +page.svelte      # Meals list
        ├── +page.ts
        └── [slug]/
            ├── +page.svelte  # Meal detail
            └── +page.ts
```

## Architecture

### Data Flow

1. **Bundled recipes** (`src/content/recipes/`) - Sample recipes loaded at build time via Vite's `import.meta.glob`. These are static and bundled into the SvelteKit build.

2. **User recipes** (PostgreSQL) - Uploaded recipes stored in database, fetched via PHP API at runtime. The API client (`src/lib/api/recipes.ts`) handles authentication and CRUD operations.

3. **Merged view** - Home page combines bundled + user recipes for unified search and browse.

### PHP API Endpoints

**Authentication**
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/auth.php` | GET | Check auth status |
| `/api/auth.php` | POST | Login with password |
| `/api/auth.php` | DELETE | Logout |

**Recipes**
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/recipes.php` | GET | List all user recipes |
| `/api/recipes.php?slug=X` | GET | Get single recipe |
| `/api/recipes.php` | POST | Upload new recipe (markdown) |
| `/api/recipes.php?slug=X` | PUT | Update recipe |
| `/api/recipes.php?slug=X` | DELETE | Delete recipe |

**Meals**
| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/meals.php` | GET | List all meals (metadata only) |
| `/api/meals.php?slug=X` | GET | Get meal with full snapshot |
| `/api/meals.php` | POST | Create meal from recipe slugs |
| `/api/meals.php?slug=X` | PUT | Update meal |
| `/api/meals.php?slug=X` | DELETE | Delete meal |
| `/api/meals.php?slug=X&action=refresh` | POST | Refresh meal snapshot |

### Authentication

Simple session-based auth with a shared password. Upload functionality requires authentication.

## Design Principles

1. **Content-first** - Typography carries the design, images optional
2. **Negative space is intentional** - Let content breathe
3. **Touch-friendly** - Large tap targets for wet/floury hands
4. **Readable at arm's length** - Phone propped up, not in hand
5. **Calm, not busy** - Restrained chrome, no visual noise

## Project Management

**JIRA Project:** [MISE Board](https://philhumber.atlassian.net/jira/software/projects/MISE/boards/34/backlog)

### Scripts

- `scripts/jira.ps1` - JIRA CLI for issue management
- `scripts/jira-batch-create.ps1` - Batch issue creation

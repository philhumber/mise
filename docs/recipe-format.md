# Recipe Format Guide

This document describes the markdown format for recipe files in Mïse. All recipes are stored as markdown files with YAML frontmatter in the `content/recipes/` directory.

## File Naming

Recipe files should be named using kebab-case with a `.md` extension:

```
content/recipes/
├── kombu-cod.md
├── yuzu-granite.md
└── kitchen-hydration.md
```

The filename (without `.md`) becomes the recipe's **slug** and is used in the URL:

- `kombu-cod.md` → `/recipe/kombu-cod`

## Frontmatter Schema

Every recipe requires YAML frontmatter at the top of the file:

```yaml
---
title: 'Kombu-Cured Low-Temp Cod'
subtitle: 'Agar-Kombu Emulsion, Parsley Oil, Crispy Skin'
category: main
difficulty: advanced
active_time: '35 min'
total_time: '48h'
serves: 2
tags: [seafood, sous-vide, make-ahead]
---
```

### Required Fields

| Field         | Type    | Description                                                    |
| ------------- | ------- | -------------------------------------------------------------- |
| `title`       | string  | Recipe name (non-empty)                                        |
| `category`    | enum    | One of: `main`, `starter`, `dessert`, `side`, `drink`, `sauce` |
| `difficulty`  | enum    | One of: `easy`, `intermediate`, `advanced`                     |
| `active_time` | string  | Hands-on cooking time (e.g., "35 min", "1h 30m")               |
| `total_time`  | string  | Total time including passive time (e.g., "48h", "4h")          |
| `serves`      | integer | Number of servings (positive integer)                          |
| `tags`        | array   | Non-empty array of tag strings                                 |

### Optional Fields

| Field      | Type   | Description                                    |
| ---------- | ------ | ---------------------------------------------- |
| `subtitle` | string | Secondary description (techniques, components) |

## Content Structure

After the frontmatter, write the recipe content in standard markdown.

### Recommended Sections

```markdown
## Timeline

Optional overview of prep stages for multi-day recipes.

## Ingredients

Ingredient lists, optionally grouped by component.

## Method

Step-by-step instructions.

## Notes

Tips, variations, or storage instructions.
```

### Timeline Markers

For multi-component recipes with advance prep, use timeline markers:

```markdown
## Timeline

- **T-48h** Start kombu water infusion
- **T-24h** Cure fish, make parsley oil
- **Day of** Cook and assemble
- **Service** Final plating
```

Timeline format conventions:

- `T-Xh` or `T-Xd` - Countdown format (hours or days before service)
- `Day of` or `Day of (Morning)` - Same-day prep
- `Service` - Final steps at plating time

### Ingredient Groups

Group ingredients by component for complex recipes:

```markdown
## Ingredients

### For the Cod

- 2 fresh cod loin portions (230g each)
- 6g fine sea salt

### For the Emulsion

- 150ml kombu water
- 1.5g agar powder
```

### Method Steps

Number steps and use bold for step titles:

```markdown
## Method

### T-24 Hours

1. **Cure the Cod.** Mix salt and sugar. Distribute over fish...

2. **Prepare Kombu Sheets.** Lay sheets between damp towels...

### Day Of

3. **Cook Cod.** Water bath at 48-49C...
```

## Validation

Recipes are validated at build time. Invalid frontmatter will cause the build to fail with a descriptive error:

```
RecipeValidationError: Invalid recipe "my-recipe": category must be one of: main, starter, dessert, side, drink, sauce (got: entree)
```

### Common Validation Errors

| Error                               | Cause                     | Fix                     |
| ----------------------------------- | ------------------------- | ----------------------- |
| `title must be a non-empty string`  | Missing or empty title    | Add a title             |
| `category must be one of...`        | Invalid category value    | Use valid category enum |
| `serves must be a positive integer` | Decimal or negative value | Use whole number > 0    |
| `tags must be a non-empty array`    | Empty or missing tags     | Add at least one tag    |

## Search Functionality

Recipes are searchable by:

- **Title** (high priority)
- **Tags** (high priority)
- **Subtitle** (medium priority)
- **Category** (medium priority)

### Design Note: Ingredient Search

Currently, search only indexes frontmatter fields. Ingredient search is planned for a future update. When implemented, there are two potential approaches:

1. **Structured ingredients** (recommended): Parse ingredients from markdown into structured data during build. This provides accurate, reliable search.

2. **HTML parsing** (not recommended): Extract text from rendered HTML. This is fragile and couples search to markdown structure changes.

The simple function-based search API (`searchRecipes()`) was chosen over exposing the raw Fuse.js instance to allow implementation changes without breaking consumers.

## Future: Recipe Upload

When implementing recipe upload through the frontend:

### File Processing Pipeline

1. **Filename generation**: Convert title to kebab-case slug
2. **Frontmatter validation**: Validate before saving
3. **Duplicate check**: Ensure slug doesn't already exist
4. **Content sanitization**: Strip unsafe HTML if accepting rich text

### Security: HTML Sanitization

**Current state:** Recipe content is rendered using Svelte's `{@html}` directive. The `marked` library converts markdown to HTML but does **not** sanitize it. This is safe for trusted content (markdown files in the repository).

**When upload is implemented:** Add HTML sanitization using DOMPurify before storing user-submitted content:

```typescript
import DOMPurify from 'isomorphic-dompurify';

// In the upload handler, sanitize before saving
const sanitizedHtml = DOMPurify.sanitize(marked.parse(userMarkdown));
```

Alternatively, sanitize at render time in `recipes.ts`. The upload-time approach is preferred as it stores clean data.

### Considerations

- **Image handling**: Store images in `/static/images/recipes/[slug]/`
- **Draft mode**: Consider a `published: boolean` frontmatter field
- **Versioning**: Consider storing recipe history for undo/edit tracking

### API Design

```typescript
// Potential upload endpoint shape
POST /api/recipes
Content-Type: multipart/form-data

{
  content: string,      // Full markdown with frontmatter
  images?: File[]       // Optional image uploads
}
```

## Examples

### Simple Recipe (Kitchen Hydration Drink)

```markdown
---
title: 'Kitchen Hydration Drink'
subtitle: 'Electrolyte Replacement for Long Service'
category: drink
difficulty: easy
active_time: '5 min'
total_time: '5 min'
serves: 1
tags: [quick, essential, hydration]
---

## Ingredients

- 500ml cold water
- 1/4 tsp fine sea salt
- 2 tbsp fresh lemon juice
- 1-2 tbsp honey

## Method

1. **Combine.** Add salt, citrus juice, and sweetener to water...
```

### Complex Multi-Day Recipe (Kombu Cod)

See `content/recipes/kombu-cod.md` for a full example with:

- Timeline overview section
- Multiple ingredient groups (5 components)
- Timeline-based method organization
- Notes section

## Related Documentation

- [CLAUDE.md](../CLAUDE.md) - Project overview and design system
- [recipes.ts](../src/lib/utils/recipes.ts) - Data layer implementation
- [search.ts](../src/lib/utils/search.ts) - Search utility with design notes

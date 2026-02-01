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

For multi-component recipes with advance prep, use timeline markers. **Important:** Markers must match exactly between the Timeline section and Method headings for navigation to work.

#### Timeline Markers

Any time-based marker is supported. Use the format that best fits your recipe:

| Format | Examples | Use Case |
|--------|----------|----------|
| `T-Xh` | `T-48h`, `T-24h`, `T-2h` | Hours before service |
| `T-Xm` | `T-90m`, `T-30m` | Minutes before service |
| `Day-of` | `Day-of` | Morning of service day |
| `Service` | `Service` | Final plating/assembly |

**Common markers:**
- `T-48h` - 2 days ahead
- `T-24h` - 1 day ahead
- `T-12h` - Morning prep (day before dinner service)
- `T-4h` - Afternoon prep
- `T-90m` - 90 minutes before
- `T-1h` - 1 hour before
- `Day-of` - Morning of service
- `Service` - Final plating

Markers are automatically sorted chronologically (earliest first, Service last).

#### Timeline Section Format

```markdown
## Timeline

- **T-48h** Start kombu water infusion
- **T-24h** Cure fish, make parsley oil
- **Day-of** Cook and assemble
- **Service** Final plating
```

**Rules:**

- Use exact markers from the canonical list above
- Bold the marker: `**T-48h**`
- Follow with a brief description
- Markers become clickable links to Method sections

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

Organize method steps under timeline headings. **Headings must use exact canonical markers** to enable timeline navigation.

```markdown
## Method

### T-24h

1. **Cure the Cod.** Mix salt and sugar. Distribute over fish...

2. **Prepare Kombu Sheets.** Lay sheets between damp towels...

### Day-of

3. **Cook Cod.** Water bath at 48-49C...

### Service

4. **Plate.** Spoon emulsion onto warm plates...
```

**Rules:**

- Use H3 (`###`) for timeline headings
- Heading text must exactly match a canonical marker (e.g., `### T-24h`, not `### T-24 Hours`)
- Number steps sequentially across all timeline sections
- Bold the step title: `1. **Step Title.** Instructions...`

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

## Meal Planning Compatible Format

For recipes to work correctly in meal planning (aggregated timelines and ingredients), follow this **exact** structure.

### The Golden Rules

1. **Use `## Ingredients`** (title case) - not `## INGREDIENTS` or `## ingredients`
2. **Use `### Component Name`** for ingredient groups - not `**Bold**` headers
3. **Use `## Method`** (title case) for the method section
4. **Use `### T-XXh` or `### Day-of` or `### Service`** for timeline sections within Method
5. **Number all steps** with `1.`, `2.`, etc.
6. **Bold the step title** followed by a period: `1. **Step Title.** Instructions...`

### Fool-Proof Template

```markdown
---
title: 'Recipe Title'
subtitle: 'Optional subtitle'
category: main
difficulty: intermediate
active_time: '45 min'
total_time: '24h'
serves: 4
tags: [tag1, tag2]
---

## Timeline

- **T-24h** Brief description of advance prep
- **Day-of** Brief description of day-of prep
- **Service** Brief description of final steps

## Ingredients

### Component One

- 200g ingredient one
- 100ml ingredient two

### Component Two

- 1 item, prepared
- 50g another ingredient

### To Finish

- Garnish items
- Finishing elements

## Method

### T-24h

1. **First Step Title.** Detailed instructions for the first step. Include temperatures, times, and visual cues.

2. **Second Step Title.** More instructions here.

### Day-of

3. **Third Step Title.** Continue numbering sequentially across all timeline sections.

4. **Fourth Step Title.** Instructions continue.

### Service

5. **Plating Step.** Final assembly and plating instructions.

## Notes

- Storage tips
- Variations
- Wine pairing suggestions
```

### What NOT to Do

| ❌ Don't | ✅ Do Instead |
|----------|---------------|
| `## INGREDIENTS` | `## Ingredients` |
| `**Miso Cure**` as component header | `### Miso Cure` |
| `## ingredients` | `## Ingredients` |
| `### Method (T – 24 h)` | `### T-24h` |
| `## The Method` | `## Method` |
| Unnumbered steps | `1.`, `2.`, `3.` etc. |
| `1. Step title: instructions` | `1. **Step Title.** Instructions` |

### Simple Recipe (No Timeline)

For simple recipes without multi-day prep, omit the Timeline section and timeline headers in Method:

```markdown
---
title: 'Quick Recipe'
category: drink
difficulty: easy
active_time: '5 min'
total_time: '5 min'
serves: 1
tags: [quick]
---

## Ingredients

### Base

- 240ml water
- 15ml citrus juice

### Optional Additions

- Mint leaves
- Ginger

## Method

1. **Combine Ingredients.** Mix all base ingredients in a container.

2. **Add Ice.** Add ice and shake well.

3. **Serve.** Pour and serve immediately.

## Notes

- Adjust sweetness to taste.
```

When included in a meal plan, recipes without timeline markers will have all their steps grouped under **T-1h**.

### Component Naming Best Practices

- Use descriptive names: `### Miso Cure` not `### Part 1`
- Keep names concise: `### Pickled Cucumber` not `### For the Quick-Pickled Cucumber Component`
- Use title case: `### Charred Bok Choy` not `### charred bok choy`
- Common useful names: `Main`, `Sauce`, `Garnish`, `To Finish`, `Assembly`

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

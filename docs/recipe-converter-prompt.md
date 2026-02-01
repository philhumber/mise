# Recipe Converter Prompt for Claude

Use this prompt to convert any recipe into a valid Mïse app format.

---

## PROMPT START

You are a recipe converter for the Mïse app - a personal recipe manager for serious home cooks. Your task is to convert the provided recipe into a valid markdown file following the exact specifications below.

### OUTPUT FORMAT

Provide the complete recipe as a markdown file with YAML frontmatter. The filename should be kebab-case based on the recipe title (e.g., `kombu-cured-cod.md`).

### FRONTMATTER SPECIFICATION

**Required Fields** (all must be present and valid):

```yaml
---
title: 'Recipe Name'           # Non-empty string, quoted
subtitle: 'Optional subtitle'  # Optional: describe techniques/components
category: main                 # MUST be one of: main, starter, dessert, side, drink, sauce
difficulty: intermediate       # MUST be one of: easy, intermediate, advanced
active_time: '35 min'         # Active cooking time (e.g., "5 min", "1h 30m", "2h")
total_time: '48h'             # Total time including passive (e.g., "15 min", "4h", "48h")
serves: 2                      # Positive integer (1, 2, 4, etc.)
tags: [tag1, tag2, tag3]      # Non-empty array, at least one tag
---
```

**Validation Rules:**
- `title`: Must be a non-empty string
- `category`: Exactly one of: main, starter, dessert, side, drink, sauce (no variations)
- `difficulty`: Exactly one of: easy, intermediate, advanced (no variations)
- `active_time`: Non-empty string representing hands-on time
- `total_time`: Non-empty string representing total time (must be >= active_time)
- `serves`: Positive integer only (not decimal, not zero, not negative)
- `tags`: Array with at least one non-empty string (suggest 2-4 relevant tags)
- `subtitle`: Optional string for techniques or component description

**Common Tag Examples:**
- Technique: sous-vide, grilled, braised, baked, no-cook
- Diet: vegetarian, vegan, gluten-free, dairy-free
- Speed: quick, make-ahead, weekend-project
- Cuisine: italian, french, japanese, mexican
- Protein: seafood, beef, chicken, pork, vegetarian
- Season: summer, fall, winter, spring

### MEAL PLANNING COMPATIBILITY (CRITICAL)

For recipes to work correctly in the meal planning feature, you MUST follow these rules exactly:

| ❌ DON'T | ✅ DO |
|----------|-------|
| `## INGREDIENTS` | `## Ingredients` |
| `## ingredients` | `## Ingredients` |
| `**Miso Cure**` as component header | `### Miso Cure` |
| `## METHOD` | `## Method` |
| `## Instructions` | `## Method` |
| `### Method (T – 24 h)` | `### T-24h` |
| Unnumbered steps | `1.`, `2.`, `3.` etc. |
| `1. Step title: instructions` | `1. **Step Title.** Instructions` |

### CONTENT STRUCTURE

After frontmatter, organize content with these sections (use exactly these H2 headings):

#### 1. Timeline Section (optional - use for multi-day/multi-component recipes)

```markdown
## Timeline

- **T-48h** Start kombu water infusion
- **T-24h** Cure fish, make parsley oil
- **Day-of** Cook and assemble
- **Service** Final plating
```

**Timeline Markers (flexible format):**

Use any time-based marker that fits your recipe:
- `T-Xh` format: `T-48h`, `T-24h`, `T-12h`, `T-4h`, `T-2h`, `T-1h` (hours)
- `T-Xm` format: `T-90m`, `T-30m`, `T-15m` (minutes)
- `Day-of` - Morning of service day
- `Service` - Final plating/assembly

Common markers: `T-48h`, `T-24h`, `T-12h`, `T-4h`, `T-90m`, `T-1h`, `Day-of`, `Service`

Markers are automatically sorted chronologically in meal plans.

**Rules:**
- Bold the marker: `**T-24h**` not `T-24h`
- Follow with brief description
- Only include relevant markers (skip irrelevant ones)
- Timeline markers must match Method section headings EXACTLY

#### 2. Ingredients Section (required)

```markdown
## Ingredients

### Component Name

- 2 fresh cod loin portions (230g each)
- 6g fine sea salt
- 1/4 tsp sugar

### Another Component

- 100ml olive oil
- 30g parsley
```

**CRITICAL Rules for Meal Planning Compatibility:**
- **ALWAYS use `## Ingredients`** (title case) - not `## INGREDIENTS` or `## ingredients`
- **ALWAYS use `### Component Name`** headers for grouping - **NOT `**Bold**` headers**
- Use H3 (`###`) for ALL component groups, even for simple recipes with one group (use `### Main` or a descriptive name)
- Be specific with quantities and units
- Include brand names or quality notes when relevant (e.g., "fresh cod", "fine sea salt")
- Common component names: `Main`, `Sauce`, `Garnish`, `To Finish`, `Assembly`, or descriptive names like `Miso Cure`, `Pickled Cucumber`

#### 3. Method Section (required)

For simple recipes (no timeline):

```markdown
## Method

1. **Step Title.** Detailed instructions for this step...

2. **Next Step.** More instructions...
```

**Note:** Simple recipes without timeline markers will have all their steps grouped under **T-1h** in meal plans.

For multi-day recipes (with timeline):

```markdown
## Method

### T-24h

1. **Cure the Cod.** Mix salt and sugar. Distribute over fish...

2. **Prepare Kombu.** Lay sheets between damp towels...

### Day-of

3. **Cook Cod.** Water bath at 48-49C...

### Service

4. **Plate.** Spoon emulsion onto warm plates...
```

**CRITICAL Method Rules for Meal Planning Compatibility:**
- **ALWAYS use `## Method`** (title case) - not `## METHOD` or `## Instructions`
- **ALWAYS number steps** with `1.`, `2.`, `3.` etc.
- **ALWAYS bold the step title** followed by a period: `1. **Step Title.** Instructions...`
- For timeline recipes, use **exactly** `### T-24h`, `### Day-of`, `### Service` etc. (not `### T-24 Hours` or `### Method (T – 24 h)`)
- Number steps sequentially across ALL timeline sections
- Be specific with temperatures, times, and techniques
- Include target doneness, visual cues, or other indicators of completion

#### 4. Notes Section (optional)

```markdown
## Notes

Tips, variations, storage instructions, or make-ahead guidance.
```

### DIFFICULTY CLASSIFICATION GUIDE

**Easy:**
- Minimal technique required
- Few ingredients (< 10)
- Short active time (< 20 min)
- No special equipment
- Examples: salad, simple pasta, smoothie, basic cocktail

**Intermediate:**
- Some technique required (sautéing, roasting, basic knife skills)
- Moderate ingredient list (10-20)
- Moderate active time (20-60 min)
- Common kitchen equipment
- Examples: roasted chicken, risotto, cake, compound sauce

**Advanced:**
- Precise technique required (sous vide, tempering, emulsions)
- Complex ingredient list or multiple components
- Extended active time (60+ min) or multi-day prep
- Specialized equipment may be needed
- Examples: multi-component plated dishes, advanced pastry, curing/fermenting

### RECIPE CONVERSION GUIDELINES

When converting from source material:

1. **Standardize measurements**: Convert unusual units to standard (cups, tbsp, tsp, g, ml, C, F)
2. **Add specificity**: "Salt" → "Fine sea salt"; "Oil" → "Neutral oil" or "Olive oil"
3. **Improve clarity**: Vague instructions → Specific techniques with target temps/times/visuals
4. **Organize complexity**: Single-day recipes → direct method; Multi-day → timeline-based
5. **Extract technique**: If recipe uses special techniques, mention in subtitle
6. **Generate relevant tags**: Based on cuisine, protein, technique, dietary needs, speed
7. **Estimate times accurately**:
   - Active time = hands-on work
   - Total time = active + passive (marinating, chilling, baking, etc.)

### VERIFICATION CHECKLIST

Before providing the final output, verify:

**Frontmatter:**
- [ ] Title is quoted and non-empty
- [ ] Category is exactly one of: main, starter, dessert, side, drink, sauce
- [ ] Difficulty is exactly one of: easy, intermediate, advanced
- [ ] active_time is quoted, non-empty string
- [ ] total_time is quoted, non-empty string (>= active_time logically)
- [ ] serves is a positive integer (no quotes, no decimal)
- [ ] tags is an array with at least one tag

**Content (Meal Planning Compatibility):**
- [ ] Ingredients section uses exactly `## Ingredients` (title case)
- [ ] ALL ingredient groups use `### Component Name` headers (NOT `**Bold**`)
- [ ] Method section uses exactly `## Method` (title case)
- [ ] Method steps are numbered sequentially (1, 2, 3...)
- [ ] Each method step has bold title: `1. **Title.** Instructions...`
- [ ] If Timeline section exists, all markers are bolded: `**T-24h**`
- [ ] If using timeline Method sections, H3 headings are exactly: `### T-24h`, `### Day-of`, `### Service` etc.
- [ ] Timeline markers (if used) are from canonical list only

**Quality:**
- [ ] Instructions are clear and specific (temps, times, visual cues)
- [ ] Measurements are standardized
- [ ] Ingredient names include relevant qualifiers (fresh, dried, fine, etc.)
- [ ] Difficulty matches recipe complexity
- [ ] Tags are relevant and useful for search

### EXAMPLE OUTPUT

**Simple Recipe:**

```markdown
---
title: 'Classic Margherita Pizza'
category: main
difficulty: intermediate
active_time: '30 min'
total_time: '3h'
serves: 4
tags: [italian, vegetarian, pizza, weekend]
---

## Ingredients

### Dough

- 500g bread flour (tipo 00 if available)
- 325ml warm water (35C)
- 7g active dry yeast
- 10g fine sea salt
- 15ml olive oil

### Topping

- 400g crushed San Marzano tomatoes
- 300g fresh mozzarella, torn
- Fresh basil leaves
- Extra virgin olive oil
- Fine sea salt

## Method

1. **Make Dough.** Combine warm water and yeast, let bloom 5 minutes. Mix flour and salt in large bowl. Add yeast mixture and olive oil. Mix until shaggy dough forms.

2. **Knead.** Turn onto floured surface. Knead 8-10 minutes until smooth and elastic. Dough should spring back when poked.

3. **First Rise.** Place in oiled bowl, cover with damp towel. Rise at room temperature until doubled, about 2 hours.

4. **Portion.** Divide into 4 equal pieces. Shape each into tight ball. Cover and rest 30 minutes.

5. **Shape.** Working with one ball at a time, stretch into 10-inch round, leaving thicker rim. Transfer to floured peel or parchment.

6. **Top.** Spread thin layer of crushed tomatoes, leaving 1-inch border. Scatter torn mozzarella. Drizzle with olive oil, season with salt.

7. **Bake.** Transfer to preheated pizza stone or steel at 500F (260C). Bake 8-10 minutes until crust is golden and cheese bubbles.

8. **Finish.** Top with fresh basil leaves and drizzle of olive oil. Slice and serve immediately.

## Notes

Dough can be made ahead and refrigerated up to 24 hours after first rise. Bring to room temperature 1 hour before shaping. For crispier crust, use a pizza stone preheated for 45 minutes.
```

**Complex Multi-Day Recipe:**

```markdown
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

## Timeline

- **T-48h** Start kombu water infusion
- **T-24h** Cure fish, make parsley oil, make kombu powder
- **Day-of** Cook cod, fry skin, set agar gel
- **Service** Blend emulsion, reheat, plate

## Ingredients

### Cod

- 2 fresh cod loin portions, skin on (230g each)
- 6g fine sea salt
- 0.8g sugar
- 2 dried kombu sheets

### Kombu Water

- 300ml cold water
- 10 x 10cm piece ma-kombu

### Agar-Kombu Emulsion

- 150ml kombu water
- 1.2g agar powder
- 80ml mild olive oil
- Fine sea salt
- Lemon juice

### Parsley Oil

- 30g flat-leaf parsley leaves
- 80g neutral oil

## Method

### T-48h

1. **Start Kombu Water.** Submerge kombu in 300ml cold water. Cover and refrigerate 48 hours.

### T-24h

2. **Skin the Cod.** Remove skin carefully, keep intact. Reserve for crisping.

3. **Cure the Cod.** Mix salt and sugar. Distribute over fish. Wrap in softened kombu sheets. Refrigerate 40 minutes.

4. **Make Parsley Oil.** Blanch parsley 10 seconds. Ice bath. Squeeze dry. Blend with oil 2 minutes. Strain through coffee filter. Refrigerate.

### Day-of

5. **Cook Cod.** Vacuum seal with neutral oil. Water bath at 48C for 40 minutes to 47C core. Ice bath 10 minutes.

6. **Fry Skin.** Fry dried skin at 185C until glassy, 45 seconds. Season with salt.

7. **Set Agar Gel.** Whisk agar into 150ml kombu water. Simmer 2 minutes. Pour into container. Refrigerate 1 hour.

### Service

8. **Blend Emulsion.** Blend agar gel until smooth. Drizzle in olive oil while blending. Season with salt and lemon. Keep warm.

9. **Reheat Cod.** Water bath at 45C for 15 minutes.

10. **Plate.** Pool emulsion on warm plates. Place cod on sauce. Dot parsley oil around edge. Arrange crispy skin shards. Serve immediately.

## Notes

All prep is done in advance. Service is just blending emulsion (2 min), reheating cod (hands-off), and plating (2 min per plate).
```

---

## HOW TO USE THIS PROMPT

1. Copy everything from "PROMPT START" to this line
2. Paste into a new conversation with Claude
3. Add: "Here's the recipe to convert: [paste recipe]"
4. Claude will output the complete markdown file
5. Save the output to `content/recipes/[slug].md`
6. Run `npm run build` to validate

The build will fail with specific error messages if validation fails, making it easy to fix issues.

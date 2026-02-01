<?php
/**
 * Recipe Validation
 *
 * Validates recipe frontmatter data. Mirrors the TypeScript validation
 * in src/lib/utils/recipes.ts for consistency.
 */

require_once __DIR__ . '/../config.php';

/**
 * Validation error structure
 */
class ValidationError
{
    public string $field;
    public string $message;

    public function __construct(string $field, string $message)
    {
        $this->field = $field;
        $this->message = $message;
    }

    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'message' => $this->message,
        ];
    }
}

/**
 * Validation result structure
 */
class ValidationResult
{
    public bool $valid;
    public ?array $data;
    /** @var ValidationError[] */
    public array $errors;

    public function __construct(bool $valid, ?array $data = null, array $errors = [])
    {
        $this->valid = $valid;
        $this->data = $data;
        $this->errors = $errors;
    }

    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'data' => $this->data,
            'errors' => array_map(fn($e) => $e->toArray(), $this->errors),
        ];
    }
}

/**
 * Validate recipe frontmatter data
 *
 * @param array $data Parsed frontmatter data
 * @return ValidationResult
 */
function validateRecipe(array $data): ValidationResult
{
    $errors = [];

    // Title (required, non-empty string)
    if (!isset($data['title']) || !is_string($data['title']) || trim($data['title']) === '') {
        $errors[] = new ValidationError('title', 'Title must be a non-empty string');
    }

    // Category (required, must be valid enum)
    if (!isset($data['category']) || !in_array($data['category'], VALID_CATEGORIES, true)) {
        $errors[] = new ValidationError(
            'category',
            'Category must be one of: ' . implode(', ', VALID_CATEGORIES)
        );
    }

    // Difficulty (required, must be valid enum)
    if (!isset($data['difficulty']) || !in_array($data['difficulty'], VALID_DIFFICULTIES, true)) {
        $errors[] = new ValidationError(
            'difficulty',
            'Difficulty must be one of: ' . implode(', ', VALID_DIFFICULTIES)
        );
    }

    // Active time (required, non-empty string)
    if (!isset($data['active_time']) || !is_string($data['active_time']) || trim($data['active_time']) === '') {
        $errors[] = new ValidationError('active_time', 'Active time must be a non-empty string');
    }

    // Total time (required, non-empty string)
    if (!isset($data['total_time']) || !is_string($data['total_time']) || trim($data['total_time']) === '') {
        $errors[] = new ValidationError('total_time', 'Total time must be a non-empty string');
    }

    // Serves (required, positive integer)
    if (!isset($data['serves']) || !is_int($data['serves']) || $data['serves'] <= 0) {
        $errors[] = new ValidationError('serves', 'Serves must be a positive integer');
    }

    // Tags (required, non-empty array of non-empty strings)
    if (!isset($data['tags']) || !is_array($data['tags']) || empty($data['tags'])) {
        $errors[] = new ValidationError('tags', 'Tags must be a non-empty array');
    } elseif (!allStrings($data['tags'])) {
        $errors[] = new ValidationError('tags', 'Tags must be an array of non-empty strings');
    }

    // Subtitle (optional, but must be string if provided)
    if (isset($data['subtitle']) && !is_string($data['subtitle'])) {
        $errors[] = new ValidationError('subtitle', 'Subtitle must be a string if provided');
    }

    if (!empty($errors)) {
        return new ValidationResult(false, null, $errors);
    }

    // Return validated data
    return new ValidationResult(true, [
        'title' => trim($data['title']),
        'subtitle' => isset($data['subtitle']) ? trim($data['subtitle']) : null,
        'category' => $data['category'],
        'difficulty' => $data['difficulty'],
        'active_time' => trim($data['active_time']),
        'total_time' => trim($data['total_time']),
        'serves' => (int) $data['serves'],
        'tags' => array_values(array_filter(array_map('trim', $data['tags']))),
    ]);
}

/**
 * Check if all array elements are non-empty strings
 *
 * @param array $arr
 * @return bool
 */
function allStrings(array $arr): bool
{
    foreach ($arr as $item) {
        if (!is_string($item) || trim($item) === '') {
            return false;
        }
    }
    return true;
}

/**
 * Generate a URL-safe slug from a title
 *
 * @param string $title
 * @return string
 */
function generateSlug(string $title): string
{
    // Convert to lowercase
    $slug = strtolower($title);

    // Replace accented characters with ASCII equivalents
    $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug);

    // Replace non-alphanumeric characters with hyphens
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

    // Remove leading/trailing hyphens
    $slug = trim($slug, '-');

    // Prefix with 'user-' to distinguish from build-time recipes
    return 'user-' . $slug;
}

/**
 * Ensure slug is unique by appending a number if needed
 *
 * @param string $baseSlug
 * @param callable $existsCheck Function that returns true if slug exists
 * @return string
 */
function ensureUniqueSlug(string $baseSlug, callable $existsCheck): string
{
    $slug = $baseSlug;
    $counter = 1;

    while ($existsCheck($slug)) {
        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }

    return $slug;
}

// Meal slug character set (no vowels to avoid bad words, no ambiguous chars)
const MEAL_SLUG_CHARS = 'bcdfghjkmnpqrstvwxyz23456789';

/**
 * Generate a unique meal slug: title-slug-xxxxxx
 * Keeps the 'user-' prefix from generateSlug() for consistency
 *
 * @param string $title
 * @return string
 */
function generateMealSlug(string $title): string
{
    $base = generateSlug($title); // Returns 'user-title-here' format

    $guid = '';
    $charsetLength = strlen(MEAL_SLUG_CHARS);
    for ($i = 0; $i < 6; $i++) {
        $guid .= MEAL_SLUG_CHARS[random_int(0, $charsetLength - 1)];
    }

    return $base . '-' . $guid;
}

/**
 * Generate slug with collision retry
 *
 * @param string $title
 * @param int $maxRetries
 * @return string
 */
function ensureUniqueMealSlug(string $title, int $maxRetries = 5): string
{
    require_once __DIR__ . '/db.php';

    for ($i = 0; $i < $maxRetries; $i++) {
        $slug = generateMealSlug($title);
        $exists = dbQueryOne(
            "SELECT 1 FROM meals WHERE slug = :slug AND deleted_at IS NULL",
            ['slug' => $slug]
        );
        if (!$exists) {
            return $slug;
        }
    }

    // Fallback: use timestamp-based suffix
    $base = generateSlug($title);
    return $base . '-' . substr(md5(microtime()), 0, 6);
}

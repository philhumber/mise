<?php
/**
 * Recipes API Endpoint
 *
 * GET /api/recipes.php              - List all user recipes
 * GET /api/recipes.php?slug=x       - Get single recipe by slug
 * GET /api/recipes.php?slug=x&edit=1 - Get recipe with markdown (requires auth)
 * POST /api/recipes.php             - Create new recipe (requires auth)
 * PUT /api/recipes.php?slug=x       - Update recipe (requires auth)
 * DELETE /api/recipes.php?slug=x    - Delete recipe (requires auth)
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/validation.php';
require_once __DIR__ . '/lib/sanitize.php';
require_once __DIR__ . '/lib/markdown.php';

// Set JSON response headers
header('Content-Type: application/json; charset=utf-8');

// Handle CORS
handleCors();

// Route the request
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGet();
            break;
        case 'POST':
            handlePost();
            break;
        case 'DELETE':
            handleDelete();
            break;
        case 'PUT':
            handlePut();
            break;
        case 'OPTIONS':
            // CORS preflight - already handled by handleCors()
            http_response_code(204);
            break;
        default:
            sendError(405, 'Method not allowed');
    }
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    sendError(500, 'Database error');
} catch (Exception $e) {
    error_log('Server error: ' . $e->getMessage());
    sendError(500, 'Server error');
}

/**
 * Handle CORS headers
 */
function handleCors(): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if (in_array($origin, CORS_ALLOWED_ORIGINS, true)) {
        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Credentials: true');
    }

    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 86400');
}

/**
 * Handle GET requests
 */
function handleGet(): void
{
    $slug = $_GET['slug'] ?? null;

    if ($slug) {
        // Include markdown if authenticated and edit mode requested
        $editMode = isset($_GET['edit']) && $_GET['edit'] === '1';
        $includeMarkdown = $editMode && isAuthenticated();

        $recipe = getRecipeBySlug($slug, $includeMarkdown);
        if (!$recipe) {
            sendError(404, 'Recipe not found');
        }
        sendJson($recipe);
    } else {
        // List all recipes
        $recipes = getAllRecipes();
        sendJson($recipes);
    }
}

/**
 * Handle POST requests (create recipe)
 */
function handlePost(): void
{
    // Check authentication
    if (!isAuthenticated()) {
        sendError(401, 'Authentication required');
    }

    // Get request body
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);

    if (!$data || !isset($data['markdown'])) {
        sendError(400, 'Missing markdown content');
    }

    // Parse markdown
    $parsed = parseFrontmatter($data['markdown']);
    if (!$parsed) {
        sendError(400, 'Invalid markdown format - missing or malformed frontmatter');
    }

    // Validate frontmatter
    $validation = validateRecipe($parsed['frontmatter']);
    if (!$validation->valid) {
        sendJson([
            'success' => false,
            'errors' => array_map(fn($e) => $e->toArray(), $validation->errors),
        ], 400);
        return;
    }

    // Generate slug
    $baseSlug = generateSlug($validation->data['title']);
    $slug = ensureUniqueSlug($baseSlug, fn($s) => recipeSlugExists($s));

    // Convert markdown to HTML and sanitize
    $html = markdownToHtml($parsed['content']);
    $sanitizedHtml = sanitizeHtml($html);

    // Save to database
    $recipe = createRecipe([
        'slug' => $slug,
        'title' => $validation->data['title'],
        'subtitle' => $validation->data['subtitle'],
        'category' => $validation->data['category'],
        'difficulty' => $validation->data['difficulty'],
        'active_time' => $validation->data['active_time'],
        'total_time' => $validation->data['total_time'],
        'serves' => $validation->data['serves'],
        'tags' => $validation->data['tags'],
        'markdown' => $data['markdown'],
        'content' => $sanitizedHtml,
    ]);

    sendJson([
        'success' => true,
        'recipe' => $recipe,
    ], 201);
}

/**
 * Handle DELETE requests
 */
function handleDelete(): void
{
    // Check authentication
    if (!isAuthenticated()) {
        sendError(401, 'Authentication required');
    }

    $slug = $_GET['slug'] ?? null;
    if (!$slug) {
        sendError(400, 'Missing slug parameter');
    }

    // Check if recipe exists
    if (!recipeSlugExists($slug)) {
        sendError(404, 'Recipe not found');
    }

    // Delete the recipe
    deleteRecipeBySlug($slug);

    // Mark meals containing this recipe as stale and mark recipe deleted
    markMealsContainingRecipeStale($slug);
    markRecipeDeletedInMeals($slug);

    sendJson(['success' => true, 'message' => 'Recipe deleted']);
}

/**
 * Handle PUT requests (update recipe)
 */
function handlePut(): void
{
    // Check authentication
    if (!isAuthenticated()) {
        sendError(401, 'Authentication required');
    }

    $slug = $_GET['slug'] ?? null;
    if (!$slug) {
        sendError(400, 'Missing slug parameter');
    }

    // Check if recipe exists
    if (!recipeSlugExists($slug)) {
        sendError(404, 'Recipe not found');
    }

    // Get request body
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);

    if (!$data || !isset($data['markdown'])) {
        sendError(400, 'Missing markdown content');
    }

    // Parse markdown
    $parsed = parseFrontmatter($data['markdown']);
    if (!$parsed) {
        sendError(400, 'Invalid markdown format - missing or malformed frontmatter');
    }

    // Validate frontmatter
    $validation = validateRecipe($parsed['frontmatter']);
    if (!$validation->valid) {
        sendJson([
            'success' => false,
            'errors' => array_map(fn($e) => $e->toArray(), $validation->errors),
        ], 400);
        return;
    }

    // Convert markdown to HTML and sanitize
    $html = markdownToHtml($parsed['content']);
    $sanitizedHtml = sanitizeHtml($html);

    // Update in database (slug stays the same)
    $recipe = updateRecipe($slug, [
        'title' => $validation->data['title'],
        'subtitle' => $validation->data['subtitle'],
        'category' => $validation->data['category'],
        'difficulty' => $validation->data['difficulty'],
        'active_time' => $validation->data['active_time'],
        'total_time' => $validation->data['total_time'],
        'serves' => $validation->data['serves'],
        'tags' => $validation->data['tags'],
        'markdown' => $data['markdown'],
        'content' => $sanitizedHtml,
    ]);

    // Mark meals containing this recipe as stale
    markMealsContainingRecipeStale($slug);

    sendJson([
        'success' => true,
        'recipe' => $recipe,
    ]);
}

/**
 * Check if the request is authenticated
 */
function isAuthenticated(): bool
{
    // Check for session cookie
    $token = $_COOKIE[SESSION_COOKIE_NAME] ?? null;
    if (!$token) {
        return false;
    }

    // Validate session in database
    $session = dbQueryOne(
        'SELECT * FROM sessions WHERE token = :token AND expires_at > NOW()',
        ['token' => $token]
    );

    return $session !== null;
}

/**
 * Get all user recipes (metadata only)
 */
function getAllRecipes(): array
{
    return dbQueryAll('
        SELECT slug, title, subtitle, category, difficulty,
               active_time, total_time, serves, tags
        FROM recipes
        WHERE deleted_at IS NULL
        ORDER BY created_at DESC
    ');
}

/**
 * Get a single recipe by slug
 */
function getRecipeBySlug(string $slug, bool $includeMarkdown = false): ?array
{
    $columns = 'slug, title, subtitle, category, difficulty,
               active_time, total_time, serves, tags, content';

    if ($includeMarkdown) {
        $columns .= ', markdown';
    }

    return dbQueryOne("
        SELECT $columns
        FROM recipes
        WHERE slug = :slug AND deleted_at IS NULL
    ", ['slug' => $slug]);
}

/**
 * Check if a recipe slug exists (for new recipe slug uniqueness)
 * Only checks active records - partial unique index enforces this at DB level
 */
function recipeSlugExists(string $slug): bool
{
    $result = dbQueryOne(
        'SELECT 1 FROM recipes WHERE slug = :slug AND deleted_at IS NULL',
        ['slug' => $slug]
    );
    return $result !== null;
}

/**
 * Create a new recipe
 */
function createRecipe(array $data): array
{
    $pdo = getDB();

    // Convert tags array to PostgreSQL array format
    $tagsArray = '{' . implode(',', array_map(
        fn($t) => '"' . str_replace('"', '\\"', $t) . '"',
        $data['tags']
    )) . '}';

    $stmt = $pdo->prepare('
        INSERT INTO recipes (slug, title, subtitle, category, difficulty,
                            active_time, total_time, serves, tags, markdown, content)
        VALUES (:slug, :title, :subtitle, :category, :difficulty,
                :active_time, :total_time, :serves, :tags, :markdown, :content)
        RETURNING id, slug, title, subtitle, category, difficulty,
                  active_time, total_time, serves, tags, created_at
    ');

    $stmt->execute([
        'slug' => $data['slug'],
        'title' => $data['title'],
        'subtitle' => $data['subtitle'],
        'category' => $data['category'],
        'difficulty' => $data['difficulty'],
        'active_time' => $data['active_time'],
        'total_time' => $data['total_time'],
        'serves' => $data['serves'],
        'tags' => $tagsArray,
        'markdown' => $data['markdown'],
        'content' => $data['content'],
    ]);

    return $stmt->fetch();
}

/**
 * Update an existing recipe
 */
function updateRecipe(string $slug, array $data): array
{
    $pdo = getDB();

    // Convert tags array to PostgreSQL array format
    $tagsArray = '{' . implode(',', array_map(
        fn($t) => '"' . str_replace('"', '\\"', $t) . '"',
        $data['tags']
    )) . '}';

    $stmt = $pdo->prepare('
        UPDATE recipes SET
            title = :title,
            subtitle = :subtitle,
            category = :category,
            difficulty = :difficulty,
            active_time = :active_time,
            total_time = :total_time,
            serves = :serves,
            tags = :tags,
            markdown = :markdown,
            content = :content
        WHERE slug = :slug AND deleted_at IS NULL
        RETURNING id, slug, title, subtitle, category, difficulty,
                  active_time, total_time, serves, tags
    ');

    $stmt->execute([
        'slug' => $slug,
        'title' => $data['title'],
        'subtitle' => $data['subtitle'],
        'category' => $data['category'],
        'difficulty' => $data['difficulty'],
        'active_time' => $data['active_time'],
        'total_time' => $data['total_time'],
        'serves' => $data['serves'],
        'tags' => $tagsArray,
        'markdown' => $data['markdown'],
        'content' => $data['content'],
    ]);

    return $stmt->fetch();
}

/**
 * Soft delete a recipe by slug (sets deleted_at timestamp)
 */
function deleteRecipeBySlug(string $slug): void
{
    dbExecute(
        'UPDATE recipes SET deleted_at = NOW() WHERE slug = :slug AND deleted_at IS NULL',
        ['slug' => $slug]
    );
}

// === MEAL STALENESS HOOKS ===

/**
 * Mark all meals containing a recipe as stale
 * Called after recipe update or delete
 */
function markMealsContainingRecipeStale(string $recipeSlug): void
{
    // Validate slug format to prevent JSON injection
    if (!preg_match('/^[a-z0-9-]+$/', $recipeSlug)) {
        error_log("Invalid slug format for marking meals stale: $recipeSlug");
        return;
    }

    try {
        dbExecute("
            UPDATE meals
            SET is_stale = TRUE, updated_at = NOW()
            WHERE snapshot->'recipes' @> :search::jsonb
            AND deleted_at IS NULL
        ", ['search' => json_encode([['slug' => $recipeSlug]])]);
    } catch (Exception $e) {
        // Log but don't fail the recipe operation
        error_log("Failed to mark meals stale for recipe $recipeSlug: " . $e->getMessage());
    }
}

/**
 * Mark a recipe as deleted in all meal snapshots
 * Called after recipe soft delete
 */
function markRecipeDeletedInMeals(string $recipeSlug): void
{
    // Validate slug format to prevent JSON injection
    if (!preg_match('/^[a-z0-9-]+$/', $recipeSlug)) {
        error_log("Invalid slug format for marking recipe deleted in meals: $recipeSlug");
        return;
    }

    try {
        // Find all meals containing this recipe
        $meals = dbQueryAll("
            SELECT id, slug, snapshot FROM meals
            WHERE snapshot->'recipes' @> :search::jsonb
            AND deleted_at IS NULL
        ", ['search' => json_encode([['slug' => $recipeSlug]])]);

        foreach ($meals as $meal) {
            $snapshot = json_decode($meal['snapshot'], true);

            // Mark the recipe as deleted in snapshot
            foreach ($snapshot['recipes'] as &$recipe) {
                if ($recipe['slug'] === $recipeSlug) {
                    $recipe['is_deleted'] = true;
                }
            }

            // Update meal
            dbExecute("
                UPDATE meals
                SET snapshot = :snapshot, is_stale = TRUE, updated_at = NOW()
                WHERE id = :id
            ", [
                'id' => $meal['id'],
                'snapshot' => json_encode($snapshot),
            ]);
        }
    } catch (Exception $e) {
        error_log("Failed to mark recipe deleted in meals for $recipeSlug: " . $e->getMessage());
    }
}

/**
 * Send JSON response
 */
function sendJson(mixed $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Send error response
 */
function sendError(int $status, string $message): void
{
    http_response_code($status);
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

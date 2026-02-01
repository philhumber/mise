<?php
/**
 * Meals API Endpoint
 *
 * GET /api/meals.php                          - List all meals (metadata only)
 * GET /api/meals.php?slug=x                   - Get single meal with full snapshot
 * POST /api/meals.php                         - Create new meal (requires auth)
 * POST /api/meals.php?slug=x&action=refresh   - Refresh stale snapshot (requires auth)
 * PUT /api/meals.php?slug=x                   - Update meal (requires auth)
 * DELETE /api/meals.php?slug=x                - Delete meal (requires auth)
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/validation.php';
require_once __DIR__ . '/lib/sanitize.php';
require_once __DIR__ . '/lib/snapshot.php';

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
            requireAuth();
            handlePost();
            break;
        case 'PUT':
            requireAuth();
            handlePut();
            break;
        case 'DELETE':
            requireAuth();
            handleDelete();
            break;
        case 'OPTIONS':
            // CORS preflight
            http_response_code(204);
            break;
        default:
            sendError(405, 'Method not allowed');
    }
} catch (SnapshotGenerationException $e) {
    sendError(422, $e->getMessage());
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
 * Require authentication or send 401
 */
function requireAuth(): void
{
    if (!isAuthenticated()) {
        sendError(401, 'Authentication required');
    }
}

/**
 * Check if the request is authenticated
 */
function isAuthenticated(): bool
{
    $token = $_COOKIE[SESSION_COOKIE_NAME] ?? null;
    if (!$token) {
        return false;
    }

    $session = dbQueryOne(
        'SELECT * FROM sessions WHERE token = :token AND expires_at > NOW()',
        ['token' => $token]
    );

    return $session !== null;
}

/**
 * Handle GET requests
 */
function handleGet(): void
{
    $slug = $_GET['slug'] ?? null;

    if ($slug) {
        // Get single meal with full snapshot
        $meal = dbQueryOne("
            SELECT id, slug, title, description, snapshot, is_stale,
                   created_at, updated_at
            FROM meals
            WHERE slug = :slug AND deleted_at IS NULL
        ", ['slug' => $slug]);

        if (!$meal) {
            sendError(404, 'Meal not found');
        }

        sendJson(transformMeal($meal));
    } else {
        // List all meals (without full snapshot)
        $meals = dbQueryAll("
            SELECT id, slug, title, description,
                   jsonb_array_length(snapshot->'recipes') as recipe_count,
                   CASE
                       WHEN snapshot->'timeline_markers'->>0 IS NULL THEN 'Same-day meal'
                       ELSE 'Starts ' || (snapshot->'timeline_markers'->>0)
                   END as timeline_span,
                   is_stale, created_at, updated_at
            FROM meals
            WHERE deleted_at IS NULL
            ORDER BY created_at DESC
        ");

        sendJson(array_map('transformMealMeta', $meals));
    }
}

/**
 * Handle POST requests (create meal or refresh snapshot)
 */
function handlePost(): void
{
    // Check for refresh action
    $action = $_GET['action'] ?? null;
    $slug = $_GET['slug'] ?? null;

    if ($action === 'refresh' && $slug) {
        handleRefresh($slug);
        return;
    }

    $input = getJsonInput();

    // Validate input
    $errors = validateMealInput($input, 'create');
    if (!empty($errors)) {
        sendJson(['success' => false, 'errors' => $errors], 422);
        return;
    }

    try {
        // Begin transaction for atomic operation
        dbBeginTransaction();

        // Generate unique slug
        $slug = ensureUniqueMealSlug($input['title']);

        // Generate snapshot
        $snapshot = generateMealSnapshot($input['recipe_slugs']);

        // Insert meal
        $meal = dbQueryOne("
            INSERT INTO meals (slug, title, description, snapshot)
            VALUES (:slug, :title, :description, :snapshot)
            RETURNING id, slug, title, description, snapshot, is_stale, created_at, updated_at
        ", [
            'slug' => $slug,
            'title' => trim($input['title']),
            'description' => isset($input['description']) ? trim($input['description']) : null,
            'snapshot' => json_encode($snapshot),
        ]);

        dbCommit();

        sendJson([
            'success' => true,
            'meal' => transformMeal($meal),
        ]);
    } catch (SnapshotGenerationException $e) {
        dbRollback();
        sendError(422, $e->getMessage());
    } catch (Exception $e) {
        dbRollback();
        throw $e;
    }
}

/**
 * Handle PUT requests (update meal)
 */
function handlePut(): void
{
    $slug = $_GET['slug'] ?? null;
    if (!$slug) {
        sendError(400, 'Missing slug parameter');
    }

    $input = getJsonInput();
    $errors = validateMealInput($input, 'update');
    if (!empty($errors)) {
        sendJson(['success' => false, 'errors' => $errors], 422);
        return;
    }

    try {
        dbBeginTransaction();

        // Fetch existing meal
        $existing = dbQueryOne(
            "SELECT id FROM meals WHERE slug = :slug AND deleted_at IS NULL",
            ['slug' => $slug]
        );

        if (!$existing) {
            dbRollback();
            sendError(404, 'Meal not found');
        }

        // Build update query
        $updates = [];
        $params = ['slug' => $slug];

        if (isset($input['title'])) {
            $updates[] = 'title = :title';
            $params['title'] = trim($input['title']);
        }

        if (array_key_exists('description', $input)) {
            $updates[] = 'description = :description';
            $params['description'] = $input['description'] ? trim($input['description']) : null;
        }

        if (isset($input['recipe_slugs'])) {
            $snapshot = generateMealSnapshot($input['recipe_slugs']);
            $updates[] = 'snapshot = :snapshot';
            $updates[] = 'is_stale = FALSE';
            $params['snapshot'] = json_encode($snapshot);
        }

        $updates[] = 'updated_at = NOW()';

        $meal = dbQueryOne("
            UPDATE meals
            SET " . implode(', ', $updates) . "
            WHERE slug = :slug AND deleted_at IS NULL
            RETURNING id, slug, title, description, snapshot, is_stale, created_at, updated_at
        ", $params);

        dbCommit();

        sendJson([
            'success' => true,
            'meal' => transformMeal($meal),
        ]);
    } catch (SnapshotGenerationException $e) {
        dbRollback();
        sendError(422, $e->getMessage());
    } catch (Exception $e) {
        dbRollback();
        throw $e;
    }
}

/**
 * Handle DELETE requests (soft delete)
 */
function handleDelete(): void
{
    $slug = $_GET['slug'] ?? null;
    if (!$slug) {
        sendError(400, 'Missing slug parameter');
    }

    // Soft delete
    $result = dbExecute("
        UPDATE meals SET deleted_at = NOW(), updated_at = NOW()
        WHERE slug = :slug AND deleted_at IS NULL
    ", ['slug' => $slug]);

    if ($result === 0) {
        sendError(404, 'Meal not found');
    }

    sendJson(['success' => true, 'message' => 'Meal deleted']);
}

/**
 * Handle refresh action (regenerate snapshot)
 */
function handleRefresh(string $slug): void
{
    try {
        dbBeginTransaction();

        $meal = dbQueryOne(
            "SELECT id, snapshot FROM meals WHERE slug = :slug AND deleted_at IS NULL",
            ['slug' => $slug]
        );

        if (!$meal) {
            dbRollback();
            sendError(404, 'Meal not found');
        }

        // Get recipe slugs from current snapshot
        $snapshot = json_decode($meal['snapshot'], true);
        $recipeSlugs = array_column($snapshot['recipes'], 'slug');

        // Filter out deleted recipes
        $validSlugs = [];
        foreach ($recipeSlugs as $recipeSlug) {
            $exists = dbQueryOne(
                "SELECT 1 FROM recipes WHERE slug = :slug AND deleted_at IS NULL",
                ['slug' => $recipeSlug]
            );
            if ($exists) {
                $validSlugs[] = $recipeSlug;
            }
        }

        // Regenerate snapshot with valid recipes only
        $newSnapshot = generateMealSnapshot($validSlugs);

        $updated = dbQueryOne("
            UPDATE meals
            SET snapshot = :snapshot, is_stale = FALSE, updated_at = NOW()
            WHERE slug = :slug AND deleted_at IS NULL
            RETURNING id, slug, title, description, snapshot, is_stale, created_at, updated_at
        ", [
            'slug' => $slug,
            'snapshot' => json_encode($newSnapshot),
        ]);

        dbCommit();

        sendJson([
            'success' => true,
            'meal' => transformMeal($updated),
        ]);
    } catch (SnapshotGenerationException $e) {
        dbRollback();
        sendError(422, $e->getMessage());
    } catch (Exception $e) {
        dbRollback();
        throw $e;
    }
}

/**
 * Get JSON input from request body
 */
function getJsonInput(): array
{
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);
    return $data ?: [];
}

/**
 * Validate meal input
 */
function validateMealInput(array $input, string $mode): array
{
    $errors = [];

    if ($mode === 'create') {
        if (empty($input['title'])) {
            $errors[] = ['field' => 'title', 'message' => 'Title is required'];
        }
        if (empty($input['recipe_slugs']) || !is_array($input['recipe_slugs'])) {
            $errors[] = ['field' => 'recipe_slugs', 'message' => 'At least one recipe is required'];
        }
    }

    // Validate title length
    if (!empty($input['title']) && strlen($input['title']) > 200) {
        $errors[] = ['field' => 'title', 'message' => 'Title too long (max 200 characters)'];
    }

    // Validate description length
    if (!empty($input['description']) && strlen($input['description']) > 1000) {
        $errors[] = ['field' => 'description', 'message' => 'Description too long (max 1000 characters)'];
    }

    if (!empty($input['recipe_slugs'])) {
        // Validate recipe count limit
        if (count($input['recipe_slugs']) > 20) {
            $errors[] = ['field' => 'recipe_slugs', 'message' => 'Maximum 20 recipes per meal'];
        }
        foreach ($input['recipe_slugs'] as $slug) {
            if (!is_string($slug) || !preg_match('/^[a-z0-9-]+$/', $slug)) {
                $errors[] = ['field' => 'recipe_slugs', 'message' => "Invalid slug format: $slug"];
            }
        }

        // Verify all recipes exist
        $foundSlugs = [];
        foreach ($input['recipe_slugs'] as $slug) {
            $found = dbQueryOne(
                "SELECT slug FROM recipes WHERE slug = :slug AND deleted_at IS NULL",
                ['slug' => $slug]
            );
            if ($found) {
                $foundSlugs[] = $found['slug'];
            }
        }

        $missing = array_diff($input['recipe_slugs'], $foundSlugs);
        if (!empty($missing)) {
            $errors[] = [
                'field' => 'recipe_slugs',
                'message' => 'Recipes not found: ' . implode(', ', $missing)
            ];
        }
    }

    return $errors;
}

/**
 * Transform meal row for meta response (list view)
 */
function transformMealMeta(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'slug' => $row['slug'],
        'title' => $row['title'],
        'description' => $row['description'],
        'recipe_count' => (int) $row['recipe_count'],
        'timeline_span' => $row['timeline_span'],
        'is_stale' => (bool) $row['is_stale'],
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at'],
    ];
}

/**
 * Transform meal row for full response (detail view)
 */
function transformMeal(array $row): array
{
    $snapshot = json_decode($row['snapshot'], true);
    return [
        'id' => (int) $row['id'],
        'slug' => $row['slug'],
        'title' => $row['title'],
        'description' => $row['description'],
        'snapshot' => $snapshot,
        'is_stale' => (bool) $row['is_stale'],
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at'],
        'recipe_count' => count($snapshot['recipes']),
        'timeline_span' => computeTimelineSpan($snapshot),
    ];
}

/**
 * Compute timeline span from snapshot
 */
function computeTimelineSpan(array $snapshot): string
{
    $markers = $snapshot['timeline_markers'] ?? [];
    if (empty($markers)) {
        return 'Same-day meal';
    }
    return 'Starts ' . $markers[0];
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

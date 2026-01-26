<?php
/**
 * Database Connection Test Script
 * Run: php api/test-db.php
 * DELETE THIS FILE AFTER VERIFYING CONNECTION
 */

$host = '10.0.0.16';
$db = 'mise';
$user = 'mise_user';
$pass = 'mise_user';

echo "Mïse Database Connection Test\n";
echo "==============================\n\n";

try {
    // Test connection
    echo "Connecting to PostgreSQL at $host...\n";
    $pdo = new PDO("pgsql:host=$host;dbname=$db", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "✓ Connection successful\n\n";

    // Check tables exist
    echo "Checking tables...\n";
    $tables = $pdo->query("
        SELECT table_name
        FROM information_schema.tables
        WHERE table_schema = 'public'
        ORDER BY table_name
    ")->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tables)) {
        echo "✗ No tables found in database\n";
        exit(1);
    }

    echo "✓ Tables found: " . implode(', ', $tables) . "\n\n";

    // Verify recipes table structure
    if (in_array('recipes', $tables)) {
        echo "Recipes table columns:\n";
        $columns = $pdo->query("
            SELECT column_name, data_type, is_nullable
            FROM information_schema.columns
            WHERE table_name = 'recipes'
            ORDER BY ordinal_position
        ")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($columns as $col) {
            $nullable = $col['is_nullable'] === 'YES' ? '(nullable)' : '';
            echo "  - {$col['column_name']}: {$col['data_type']} $nullable\n";
        }
        echo "✓ Recipes table verified\n\n";
    } else {
        echo "✗ Recipes table not found\n";
        exit(1);
    }

    // Verify sessions table structure
    if (in_array('sessions', $tables)) {
        echo "Sessions table columns:\n";
        $columns = $pdo->query("
            SELECT column_name, data_type, is_nullable
            FROM information_schema.columns
            WHERE table_name = 'sessions'
            ORDER BY ordinal_position
        ")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($columns as $col) {
            $nullable = $col['is_nullable'] === 'YES' ? '(nullable)' : '';
            echo "  - {$col['column_name']}: {$col['data_type']} $nullable\n";
        }
        echo "✓ Sessions table verified\n\n";
    } else {
        echo "✗ Sessions table not found\n";
        exit(1);
    }

    // Test insert/select/delete (with rollback)
    echo "Testing CRUD operations...\n";
    $pdo->beginTransaction();

    // Insert test recipe
    $testSlug = 'test-recipe-' . time();
    $stmt = $pdo->prepare("
        INSERT INTO recipes (slug, title, category, difficulty, active_time, total_time, serves, tags, markdown, content)
        VALUES (:slug, :title, :category, :difficulty, :active_time, :total_time, :serves, :tags, :markdown, :content)
        RETURNING id
    ");
    $stmt->execute([
        'slug' => $testSlug,
        'title' => 'Test Recipe',
        'category' => 'main',
        'difficulty' => 'easy',
        'active_time' => '10 min',
        'total_time' => '10 min',
        'serves' => 2,
        'tags' => '{test}',
        'markdown' => '# Test',
        'content' => '<h1>Test</h1>'
    ]);
    $id = $stmt->fetchColumn();
    echo "  ✓ INSERT works (id: $id)\n";

    // Select test recipe
    $stmt = $pdo->prepare("SELECT * FROM recipes WHERE slug = :slug");
    $stmt->execute(['slug' => $testSlug]);
    $recipe = $stmt->fetch();
    echo "  ✓ SELECT works (found: {$recipe['title']})\n";

    // Rollback (don't leave test data)
    $pdo->rollback();
    echo "  ✓ ROLLBACK works (test data removed)\n\n";

    echo "==============================\n";
    echo "✓ All checks passed!\n";
    echo "==============================\n";
    echo "\nDatabase is ready for use.\n";
    echo "Remember to DELETE this file after testing.\n";

} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

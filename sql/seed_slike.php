<?php
// sql/seed_slike.php — one-shot CLI seeder for the slike table.
// Scans public/images/ for jpg/jpeg/png files and inserts one row per file.
// Usage:  /Applications/XAMPP/xamppfiles/bin/php sql/seed_slike.php
//
// Idempotent: skips files whose `putanja` is already present.

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script must be run from the command line.\n");
}

require __DIR__ . '/../includes/db.php';

$projectRoot = realpath(__DIR__ . '/..');
$imagesDir   = $projectRoot . '/public/images';
$webPrefix   = 'public/images';

if (!is_dir($imagesDir)) {
    fwrite(STDERR, "Images directory not found: {$imagesDir}\n");
    exit(1);
}

$allowed = ['jpg', 'jpeg', 'png'];

$files = [];
foreach (scandir($imagesDir) as $name) {
    if ($name === '.' || $name === '..') {
        continue;
    }
    $full = $imagesDir . '/' . $name;
    if (!is_file($full)) {
        continue;
    }
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        continue;
    }
    $files[] = $name;
}
sort($files);

if (!$files) {
    echo "No image files found in {$imagesDir}\n";
    exit(0);
}

function caption_from_filename(string $name): string {
    $base = pathinfo($name, PATHINFO_FILENAME);
    $base = str_replace(['_', '-'], ' ', $base);
    $base = trim(preg_replace('/\s+/', ' ', $base));
    return $base !== '' ? mb_convert_case($base, MB_CASE_TITLE, 'UTF-8') : $name;
}

$inserted = 0;
$skipped  = 0;

try {
    $pdo->beginTransaction();

    $check = $pdo->prepare('SELECT id FROM slike WHERE putanja = ? LIMIT 1');
    $ins   = $pdo->prepare(
        'INSERT INTO slike (naziv_datoteke, opis, putanja, izvor)
         VALUES (?, ?, ?, ?)'
    );

    foreach ($files as $name) {
        $putanja = $webPrefix . '/' . $name;
        $check->execute([$putanja]);
        if ($check->fetchColumn()) {
            $skipped++;
            continue;
        }

        $opis = caption_from_filename($name);
        $ins->execute([$name, $opis, $putanja, 'local']);
        $inserted++;
    }

    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Seed failed (rolled back): " . $e->getMessage() . "\n");
    exit(1);
}

echo "Files found: " . count($files) . "\n";
echo "Inserted:    {$inserted}\n";
echo "Skipped:     {$skipped} (already in slike)\n";

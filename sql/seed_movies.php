<?php
// sql/seed_movies.php — one-shot CLI seeder for the films table.
// Usage:  /Applications/XAMPP/xamppfiles/bin/php sql/seed_movies.php
//
// Reads materials/movies.csv and inserts each row into films, wrapped in a
// transaction. Idempotent: rows whose (title, release_year) already exist are
// skipped, so re-running the script is safe.

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script must be run from the command line.\n");
}

require __DIR__ . '/../includes/db.php';

$csvPath = __DIR__ . '/../materials/movies.csv';
if (!is_readable($csvPath)) {
    fwrite(STDERR, "CSV not found or unreadable: {$csvPath}\n");
    exit(1);
}

$fh = fopen($csvPath, 'r');
if (!$fh) {
    fwrite(STDERR, "Could not open {$csvPath}\n");
    exit(1);
}

$header = fgetcsv($fh);
if (!$header) {
    fwrite(STDERR, "Empty CSV.\n");
    exit(1);
}

// Strip UTF-8 BOM from the first header if present.
$header[0] = preg_replace('/^\x{FEFF}/u', '', (string) $header[0]);

$colMap = [
    'Naslov'           => 'title',
    'Zanr'             => 'genre',
    'Godina'           => 'release_year',
    'Trajanje_min'     => 'duration',
    'Redatelj'         => 'director',
    'Zemlja_porijekla' => 'country',
];

$idx = [];
foreach ($header as $i => $name) {
    $name = trim((string) $name);
    if (isset($colMap[$name])) {
        $idx[$colMap[$name]] = $i;
    }
}

$required = ['title', 'director', 'genre', 'release_year', 'duration', 'country'];
$missing  = array_diff($required, array_keys($idx));
if ($missing) {
    fwrite(STDERR, "CSV missing required columns: " . implode(', ', $missing) . "\n");
    exit(1);
}

$inserted = 0;
$skipped  = 0;
$rows     = 0;

try {
    $pdo->beginTransaction();

    $check = $pdo->prepare(
        'SELECT id FROM films WHERE title = ? AND release_year = ? LIMIT 1'
    );
    $insert = $pdo->prepare(
        'INSERT INTO films
            (title, director, genre, country, release_year, duration, description, available_copies)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );

    while (($row = fgetcsv($fh)) !== false) {
        if (count($row) === 1 && trim((string) $row[0]) === '') {
            continue;
        }
        $rows++;

        $title    = trim((string) ($row[$idx['title']]        ?? ''));
        $director = trim((string) ($row[$idx['director']]     ?? ''));
        $genre    = trim((string) ($row[$idx['genre']]        ?? ''));
        $country  = trim((string) ($row[$idx['country']]      ?? ''));
        $year     = (int) trim((string) ($row[$idx['release_year']] ?? '0'));
        $duration = (int) trim((string) ($row[$idx['duration']]     ?? '0'));

        if ($title === '' || $director === '' || $genre === '' || $country === ''
            || $year < 1888 || $duration < 1 || $duration > 600) {
            fwrite(STDERR, "Skipping malformed row {$rows}: " . implode(' | ', $row) . "\n");
            $skipped++;
            continue;
        }

        $check->execute([$title, $year]);
        if ($check->fetchColumn()) {
            $skipped++;
            continue;
        }

        $insert->execute([
            $title, $director, $genre, $country, $year, $duration, '', 1,
        ]);
        $inserted++;
    }

    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Import failed (transaction rolled back): " . $e->getMessage() . "\n");
    exit(1);
}

fclose($fh);

echo "Processed: {$rows} row(s)\n";
echo "Inserted:  {$inserted}\n";
echo "Skipped:   {$skipped} (already existed or malformed)\n";

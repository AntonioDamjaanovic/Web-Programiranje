<?php
// films.php — public listing with server-side filters and sorting.
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$genre   = trim((string) ($_GET['genre']   ?? ''));
$year    = trim((string) ($_GET['year']    ?? ''));
$country = trim((string) ($_GET['country'] ?? ''));

$yearFilter = null;
if ($year !== '' && preg_match('/^\d{4}$/', $year)) {
    $yearFilter = (int) $year;
}

$sortMap = [
    'title' => 'title',
    'year'  => 'release_year',
    'genre' => 'genre',
];
$sortKey = (string) ($_GET['sort'] ?? 'title');
if (!array_key_exists($sortKey, $sortMap)) {
    $sortKey = 'title';
}
$sortCol = $sortMap[$sortKey];

$dirIn = strtolower((string) ($_GET['dir'] ?? 'asc'));
$dir   = ($dirIn === 'desc') ? 'DESC' : 'ASC';

$where  = [];
$params = [];
if ($genre !== '') {
    $where[]  = 'genre = ?';
    $params[] = $genre;
}
if ($yearFilter !== null) {
    $where[]  = 'release_year = ?';
    $params[] = $yearFilter;
}
if ($country !== '') {
    $where[]  = 'country = ?';
    $params[] = $country;
}

$sql = 'SELECT id, title, director, genre, country, release_year, duration, available_copies
          FROM films';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY ' . $sortCol . ' ' . $dir . ', id ASC';

$films = [];
$loadError = null;
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $films = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Film list failed: ' . $e->getMessage());
    $loadError = 'Could not load films right now.';
}

$wishlistIds = [];
if (is_logged_in() && $films) {
    try {
        $stmt = $pdo->prepare('SELECT film_id FROM zeljeni_filmovi WHERE user_id = ?');
        $stmt->execute([(int) current_user()['id']]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $fid) {
            $wishlistIds[(int) $fid] = true;
        }
    } catch (PDOException $e) {
        error_log('Wishlist lookup failed: ' . $e->getMessage());
    }
}

$genreOptions = [];
$yearOptions = [];
$countryOptions = [];
try {
    $genreOptions = $pdo->query(
        "SELECT DISTINCT genre FROM films WHERE genre <> '' ORDER BY genre"
    )->fetchAll(PDO::FETCH_COLUMN);
    $yearOptions = $pdo->query(
        'SELECT DISTINCT release_year FROM films ORDER BY release_year DESC'
    )->fetchAll(PDO::FETCH_COLUMN);
    $countryOptions = $pdo->query(
        "SELECT DISTINCT country FROM films WHERE country <> '' ORDER BY country"
    )->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log('Filter options failed: ' . $e->getMessage());
}

function sort_url(string $key, string $currentKey, string $currentDir): string {
    $nextDir = ($currentKey === $key && $currentDir === 'ASC') ? 'desc' : 'asc';
    $params = $_GET;
    $params['sort'] = $key;
    $params['dir']  = $nextDir;
    return 'films.php?' . http_build_query($params);
}

$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Films — Movie Database</title>
    <link rel="stylesheet" href="public/styles/style.css">
    <link rel="stylesheet" href="public/styles/auth.css">
    <link rel="stylesheet" href="public/styles/dashboard.css">
    <link rel="stylesheet" href="public/styles/films.css">
</head>
<body>
    <header>
        <h1 class="header-title">Movie Database</h1>
    </header>

    <main>
        <section class="content-section dashboard-header">
            <div>
                <p class="dashboard-user">
                    <?php if ($user): ?>
                        Signed in as <strong><?= h($user['username']) ?></strong>
                    <?php else: ?>
                        Browsing as guest
                    <?php endif; ?>
                </p>
            </div>
            <div class="dashboard-actions">
                <a class="cta-button" href="gallery.php">Gallery</a>
                <?php if ($user): ?>
                    <a class="cta-button" href="wishlist.php">My wishlist</a>
                <?php endif; ?>
                <?php if (is_admin()): ?>
                    <a class="cta-button" href="dashboard.php">Dashboard</a>
                <?php endif; ?>
                <?php if ($user): ?>
                    <a class="cta-button" href="logout.php">Sign out</a>
                <?php else: ?>
                    <a class="cta-button" href="login.php">Sign in</a>
                    <a class="cta-button" href="register.php">Register</a>
                <?php endif; ?>
            </div>
        </section>

        <section class="content-section">
            <h2>Films</h2>

            <form method="get" action="films.php" class="films-filter">
                <div class="form-row">
                    <label for="filter-genre">Genre</label>
                    <select id="filter-genre" name="genre">
                        <option value="">All genres</option>
                        <?php foreach ($genreOptions as $g): ?>
                            <option value="<?= h($g) ?>" <?= $g === $genre ? 'selected' : '' ?>>
                                <?= h($g) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <label for="filter-year">Year</label>
                    <select id="filter-year" name="year">
                        <option value="">All years</option>
                        <?php foreach ($yearOptions as $y): ?>
                            <option value="<?= (int) $y ?>" <?= ((int) $y === $yearFilter) ? 'selected' : '' ?>>
                                <?= (int) $y ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <label for="filter-country">Country</label>
                    <select id="filter-country" name="country">
                        <option value="">All countries</option>
                        <?php foreach ($countryOptions as $c): ?>
                            <option value="<?= h($c) ?>" <?= $c === $country ? 'selected' : '' ?>>
                                <?= h($c) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <input type="hidden" name="sort" value="<?= h($sortKey) ?>">
                <input type="hidden" name="dir" value="<?= h(strtolower($dir)) ?>">

                <div class="filter-buttons">
                    <button type="submit" class="btn-primary">Apply filters</button>
                    <a href="films.php" class="cta-button cta-cancel">Reset</a>
                </div>
            </form>

            <?php if ($loadError): ?>
                <ul class="auth-errors" role="alert">
                    <li><?= h($loadError) ?></li>
                </ul>
            <?php elseif (!$films): ?>
                <p class="empty-state">No films match the current filters.</p>
            <?php else: ?>
                <p class="results-count"><?= count($films) ?> film(s) found.</p>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>
                                    <a class="sort-link" href="<?= h(sort_url('title', $sortKey, $dir)) ?>">
                                        Title<?php if ($sortKey === 'title'): ?>
                                            <span class="sort-arrow"><?= $dir === 'ASC' ? '&#9650;' : '&#9660;' ?></span>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>Director</th>
                                <th>
                                    <a class="sort-link" href="<?= h(sort_url('genre', $sortKey, $dir)) ?>">
                                        Genre<?php if ($sortKey === 'genre'): ?>
                                            <span class="sort-arrow"><?= $dir === 'ASC' ? '&#9650;' : '&#9660;' ?></span>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>Country</th>
                                <th>
                                    <a class="sort-link" href="<?= h(sort_url('year', $sortKey, $dir)) ?>">
                                        Year<?php if ($sortKey === 'year'): ?>
                                            <span class="sort-arrow"><?= $dir === 'ASC' ? '&#9650;' : '&#9660;' ?></span>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>Duration</th>
                                <th>Copies</th>
                                <?php if ($user): ?>
                                    <th>Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $returnUrl = $_GET ? 'films.php?' . http_build_query($_GET) : 'films.php';
                            ?>
                            <?php foreach ($films as $film): ?>
                                <tr>
                                    <td><?= h($film['title']) ?></td>
                                    <td><?= h($film['director']) ?></td>
                                    <td><?= h($film['genre']) ?></td>
                                    <td><?= h((string) $film['country']) ?></td>
                                    <td><?= (int) $film['release_year'] ?></td>
                                    <td><?= (int) $film['duration'] ?> min</td>
                                    <td><?= (int) $film['available_copies'] ?></td>
                                    <?php if ($user): ?>
                                        <td class="row-actions">
                                            <form method="post" action="wishlist.php">
                                                <?php $inList = isset($wishlistIds[(int) $film['id']]); ?>
                                                <input type="hidden" name="op" value="<?= $inList ? 'remove' : 'add' ?>">
                                                <input type="hidden" name="film_id" value="<?= (int) $film['id'] ?>">
                                                <input type="hidden" name="return" value="<?= h($returnUrl) ?>">
                                                <button type="submit" class="btn-link <?= $inList ? 'btn-link-danger' : '' ?>">
                                                    <?= $inList ? 'Remove from wishlist' : 'Add to wishlist' ?>
                                                </button>
                                            </form>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>

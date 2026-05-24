<?php
// wishlist.php — personal wishlist. Handles POST add/remove via PRG, GET shows list.
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';

require_login();

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function safe_return_path(string $candidate): string {
    if ($candidate !== '' && preg_match('/^[a-z_]+\.php(\?[A-Za-z0-9=&_\-%.+]*)?$/', $candidate)) {
        return $candidate;
    }
    return 'wishlist.php';
}

$user   = current_user();
$userId = (int) $user['id'];

$lowRatingThreshold = 5.0;
$warning = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $op        = $_POST['op'] ?? '';
    $filmId    = (int) ($_POST['film_id'] ?? 0);
    $return    = safe_return_path((string) ($_POST['return'] ?? ''));
    $confirmed = !empty($_POST['confirmed']);

    if ($op === 'add' && $filmId > 0) {
        $proceed = $confirmed;

        if (!$confirmed) {
            try {
                $stmt = $pdo->prepare('SELECT AVG(rating) FROM ratings WHERE film_id = ?');
                $stmt->execute([$filmId]);
                $avgRaw = $stmt->fetchColumn();
                $avg    = ($avgRaw === false || $avgRaw === null) ? null : (float) $avgRaw;
            } catch (PDOException $e) {
                error_log('Avg rating check failed: ' . $e->getMessage());
                $avg = null;
            }

            if ($avg !== null && $avg < $lowRatingThreshold) {
                try {
                    $titleStmt = $pdo->prepare('SELECT title FROM films WHERE id = ?');
                    $titleStmt->execute([$filmId]);
                    $filmTitle = (string) $titleStmt->fetchColumn();
                } catch (PDOException $e) {
                    error_log('Film title fetch failed: ' . $e->getMessage());
                    $filmTitle = '';
                }

                $warning = [
                    'film_id' => $filmId,
                    'title'   => $filmTitle,
                    'return'  => $return,
                    'avg'     => round($avg, 2),
                ];
            } else {
                $proceed = true;
            }
        }

        if ($proceed) {
            try {
                $stmt = $pdo->prepare(
                    'INSERT IGNORE INTO zeljeni_filmovi (user_id, film_id) VALUES (?, ?)'
                );
                $stmt->execute([$userId, $filmId]);
            } catch (PDOException $e) {
                error_log('Wishlist add failed: ' . $e->getMessage());
            }
            header('Location: ' . $return);
            exit;
        }
    } elseif ($op === 'remove' && $filmId > 0) {
        try {
            $stmt = $pdo->prepare(
                'DELETE FROM zeljeni_filmovi WHERE user_id = ? AND film_id = ?'
            );
            $stmt->execute([$userId, $filmId]);
        } catch (PDOException $e) {
            error_log('Wishlist remove failed: ' . $e->getMessage());
        }
        header('Location: ' . $return);
        exit;
    } else {
        header('Location: ' . $return);
        exit;
    }
}

$films = [];
$loadError = null;
if ($warning === null) {
    try {
        $stmt = $pdo->prepare(
            'SELECT f.id, f.title, f.director, f.genre, f.country, f.release_year,
                    f.duration, f.available_copies, w.created_at AS added_at
               FROM zeljeni_filmovi w
               JOIN films f ON f.id = w.film_id
              WHERE w.user_id = ?
              ORDER BY w.created_at DESC, f.id DESC'
        );
        $stmt->execute([$userId]);
        $films = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Wishlist load failed: ' . $e->getMessage());
        $loadError = 'Could not load your wishlist right now.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My wishlist — Movie Database</title>
    <link rel="stylesheet" href="public/styles/style.css">
    <link rel="stylesheet" href="public/styles/auth.css">
    <link rel="stylesheet" href="public/styles/dashboard.css">
    <link rel="stylesheet" href="public/styles/films.css">
</head>
<body>
    <header>
        <h1 class="header-title">My wishlist</h1>
    </header>

    <main>
        <section class="content-section dashboard-header">
            <div>
                <p class="dashboard-user">
                    Signed in as <strong><?= h($user['username']) ?></strong>
                </p>
            </div>
            <div class="dashboard-actions">
                <a class="cta-button" href="films.php">Browse films</a>
                <a class="cta-button" href="gallery.php">Gallery</a>
                <?php if (is_admin()): ?>
                    <a class="cta-button" href="dashboard.php">Dashboard</a>
                <?php endif; ?>
                <a class="cta-button" href="logout.php">Sign out</a>
            </div>
        </section>

        <?php if ($warning !== null): ?>
            <section class="content-section">
                <h2>Confirm add to wishlist</h2>
                <div class="warning-box" role="alert">
                    <p class="warning-message">
                        Ovaj film ima nisku ocjenu &ndash; jeste li sigurni da ga &#382;elite dodati?
                    </p>
                    <?php if ($warning['title'] !== ''): ?>
                        <p class="warning-meta">
                            <strong><?= h($warning['title']) ?></strong>
                            &mdash; average rating: <?= h(number_format($warning['avg'], 2)) ?> / 5
                        </p>
                    <?php endif; ?>
                </div>

                <form method="post" action="wishlist.php" class="warning-actions">
                    <input type="hidden" name="op" value="add">
                    <input type="hidden" name="film_id" value="<?= (int) $warning['film_id'] ?>">
                    <input type="hidden" name="return" value="<?= h($warning['return']) ?>">
                    <input type="hidden" name="confirmed" value="1">
                    <button type="submit" class="btn-primary">Yes, add anyway</button>
                    <a class="cta-button cta-cancel" href="<?= h($warning['return']) ?>">Cancel</a>
                </form>
            </section>
        <?php else: ?>
        <section class="content-section">
            <h2>Saved films</h2>

            <?php if ($loadError): ?>
                <ul class="auth-errors" role="alert">
                    <li><?= h($loadError) ?></li>
                </ul>
            <?php elseif (!$films): ?>
                <p class="empty-state">
                    Your wishlist is empty. <a href="films.php">Browse films</a> to add some.
                </p>
            <?php else: ?>
                <p class="results-count"><?= count($films) ?> film(s) on your wishlist.</p>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Director</th>
                                <th>Genre</th>
                                <th>Country</th>
                                <th>Year</th>
                                <th>Duration</th>
                                <th>Copies</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($films as $film): ?>
                                <tr>
                                    <td><?= h($film['title']) ?></td>
                                    <td><?= h($film['director']) ?></td>
                                    <td><?= h($film['genre']) ?></td>
                                    <td><?= h((string) $film['country']) ?></td>
                                    <td><?= (int) $film['release_year'] ?></td>
                                    <td><?= (int) $film['duration'] ?> min</td>
                                    <td><?= (int) $film['available_copies'] ?></td>
                                    <td class="row-actions">
                                        <form method="post" action="wishlist.php">
                                            <input type="hidden" name="op" value="remove">
                                            <input type="hidden" name="film_id" value="<?= (int) $film['id'] ?>">
                                            <input type="hidden" name="return" value="wishlist.php">
                                            <button type="submit" class="btn-link btn-link-danger">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>
    </main>
</body>
</html>

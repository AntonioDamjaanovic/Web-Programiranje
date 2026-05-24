<?php
// photo.php — single image view + rating form.
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    exit('Invalid image id.');
}

$errors = [];
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login(); // server-side guard for rating actions

    $postId  = (int) ($_POST['id'] ?? 0);
    $ratingV = filter_var($_POST['ocjena'] ?? '', FILTER_VALIDATE_INT);

    if ($postId !== $id) {
        $errors[] = 'Invalid request.';
    } elseif ($ratingV === false || $ratingV < 1 || $ratingV > 5) {
        $errors[] = 'Please choose a rating between 1 and 5.';
    } else {
        $userId = (int) current_user()['id'];
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO ocjene (id_korisnik, id_slika, ocjena)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    ocjena = VALUES(ocjena),
                    vrijeme_ocjene = CURRENT_TIMESTAMP'
            );
            $stmt->execute([$userId, $id, $ratingV]);

            // PRG — bounce so a refresh doesn't re-submit.
            header('Location: photo.php?id=' . $id);
            exit;
        } catch (PDOException $e) {
            error_log('Rating save failed: ' . $e->getMessage());
            $errors[] = 'Could not save your rating. Please try again.';
        }
    }
}

// Load the image record.
try {
    $stmt = $pdo->prepare(
        'SELECT id, naziv_datoteke, opis, putanja, izvor, created_at
           FROM slike
          WHERE id = ?
          LIMIT 1'
    );
    $stmt->execute([$id]);
    $image = $stmt->fetch();
} catch (PDOException $e) {
    error_log('Photo load failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Server error.');
}

if (!$image) {
    http_response_code(404);
    exit('Image not found.');
}

// Aggregate rating.
$avg   = null;
$votes = 0;
try {
    $stmt = $pdo->prepare(
        'SELECT AVG(ocjena) AS avg_rating, COUNT(*) AS votes
           FROM ocjene
          WHERE id_slika = ?'
    );
    $stmt->execute([$id]);
    $agg = $stmt->fetch();
    if ($agg) {
        $avg   = $agg['avg_rating'] !== null ? (float) $agg['avg_rating'] : null;
        $votes = (int) $agg['votes'];
    }
} catch (PDOException $e) {
    error_log('Rating aggregate failed: ' . $e->getMessage());
}

// Logged-in user's existing rating, if any.
$user        = current_user();
$myRating    = null;
if ($user) {
    try {
        $stmt = $pdo->prepare(
            'SELECT ocjena FROM ocjene
              WHERE id_korisnik = ? AND id_slika = ?
              LIMIT 1'
        );
        $stmt->execute([(int) $user['id'], $id]);
        $v = $stmt->fetchColumn();
        if ($v !== false && $v !== null) {
            $myRating = (int) $v;
        }
    } catch (PDOException $e) {
        error_log('My rating fetch failed: ' . $e->getMessage());
    }
}

$caption = $image['opis'] !== null && $image['opis'] !== ''
    ? (string) $image['opis']
    : (string) $image['naziv_datoteke'];

$pageTitle  = $caption;
$pageStyles = ['public/styles/dashboard.css', 'public/styles/gallery.css', 'public/styles/photo.css'];
require __DIR__ . '/includes/header.php';
?>
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
                <a class="cta-button" href="gallery.php">&larr; Gallery</a>
                <a class="cta-button" href="films.php">Films</a>
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
                <?php endif; ?>
            </div>
        </section>

        <section class="content-section photo-section">
            <h2><?= h($caption) ?></h2>

            <?php if ($errors): ?>
                <ul class="auth-errors" role="alert">
                    <?php foreach ($errors as $err): ?>
                        <li><?= h($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <div class="photo-image-wrap">
                <img class="photo-image"
                     src="<?= h((string) $image['putanja']) ?>"
                     alt="<?= h($caption) ?>">
            </div>

            <dl class="photo-meta">
                <dt>File</dt>
                <dd><?= h((string) $image['naziv_datoteke']) ?></dd>

                <?php if ($image['opis'] !== null && $image['opis'] !== ''): ?>
                    <dt>Description</dt>
                    <dd><?= h((string) $image['opis']) ?></dd>
                <?php endif; ?>

                <dt>Source</dt>
                <dd><?= h((string) $image['izvor']) ?></dd>

                <dt>Average rating</dt>
                <dd>
                    <?php if ($avg === null): ?>
                        <span class="gallery-rating-empty">No ratings yet</span>
                    <?php else: ?>
                        <span class="gallery-star" aria-hidden="true">&#9733;</span>
                        <strong class="gallery-rating-value"><?= h(number_format($avg, 2)) ?></strong><span class="gallery-rating-max">/5</span>
                        <span class="gallery-rating-votes">(<?= $votes ?> vote<?= $votes === 1 ? '' : 's' ?>)</span>
                    <?php endif; ?>
                </dd>
            </dl>

            <?php if ($user): ?>
                <form method="post" action="photo.php?id=<?= (int) $id ?>" class="rating-form">
                    <input type="hidden" name="id" value="<?= (int) $id ?>">
                    <fieldset class="rating-fieldset">
                        <legend>
                            <?= $myRating !== null
                                ? 'Update your rating (current: ' . (int) $myRating . ' / 5)'
                                : 'Rate this image' ?>
                        </legend>
                        <div class="rating-choices">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <label class="rating-choice">
                                    <input type="radio" name="ocjena" value="<?= $i ?>"
                                        <?= $myRating === $i ? 'checked' : '' ?> required>
                                    <span><?= $i ?></span>
                                </label>
                            <?php endfor; ?>
                        </div>
                        <button type="submit" class="btn-primary">
                            <?= $myRating !== null ? 'Update rating' : 'Submit rating' ?>
                        </button>
                    </fieldset>
                </form>
            <?php else: ?>
                <p class="photo-signin-hint">
                    <a href="login.php">Sign in</a> to rate this image.
                </p>
            <?php endif; ?>
        </section>
<?php require __DIR__ . '/includes/footer.php'; ?>

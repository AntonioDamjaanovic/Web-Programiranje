<?php
// gallery.php — public image gallery. Lists everything in `slike` with avg ocjena.
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$images    = [];
$loadError = null;

try {
    $stmt = $pdo->prepare(
        'SELECT s.id, s.naziv_datoteke, s.opis, s.putanja, s.izvor,
                AVG(o.ocjena) AS avg_rating,
                COUNT(o.id)   AS votes
           FROM slike s
           LEFT JOIN ocjene o ON o.id_slika = s.id
          GROUP BY s.id
          ORDER BY s.created_at DESC, s.id DESC'
    );
    $stmt->execute();
    $images = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Gallery load failed: ' . $e->getMessage());
    $loadError = 'Could not load the gallery right now.';
}

$user = current_user();

$pageTitle  = 'Gallery';
$pageStyles = ['public/styles/dashboard.css', 'public/styles/gallery.css'];
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
                    <a class="cta-button" href="register.php">Register</a>
                <?php endif; ?>
            </div>
        </section>

        <section class="content-section">
            <h2>Gallery</h2>

            <?php if ($loadError): ?>
                <ul class="auth-errors" role="alert">
                    <li><?= h($loadError) ?></li>
                </ul>
            <?php elseif (!$images): ?>
                <p class="empty-state">No images yet.</p>
            <?php else: ?>
                <p class="results-count"><?= count($images) ?> image(s).</p>
                <ul class="gallery-grid">
                    <?php foreach ($images as $img):
                        $caption = $img['opis'] !== null && $img['opis'] !== ''
                            ? (string) $img['opis']
                            : (string) $img['naziv_datoteke'];
                        $votes = (int) $img['votes'];
                        $avg   = $img['avg_rating'] !== null ? (float) $img['avg_rating'] : null;
                    ?>
                        <li class="gallery-tile">
                            <a class="gallery-link" href="photo.php?id=<?= (int) $img['id'] ?>">
                                <div class="gallery-image-wrap">
                                    <img class="gallery-image"
                                         src="<?= h((string) $img['putanja']) ?>"
                                         alt="<?= h($caption) ?>"
                                         loading="lazy">
                                </div>
                                <div class="gallery-meta">
                                    <span class="gallery-caption"><?= h($caption) ?></span>
                                    <span class="gallery-rating">
                                        <?php if ($avg === null): ?>
                                            <span class="gallery-rating-empty">No ratings yet</span>
                                        <?php else: ?>
                                            <span class="gallery-star" aria-hidden="true">&#9733;</span>
                                            <strong class="gallery-rating-value"><?= h(number_format($avg, 1)) ?></strong><span class="gallery-rating-max">/5</span>
                                            <span class="gallery-rating-votes">(<?= $votes ?>)</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
<?php require __DIR__ . '/includes/footer.php'; ?>

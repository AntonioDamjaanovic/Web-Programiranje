<?php
// dashboard.php — admin interface for adding, editing, and deleting movies.
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';

require_admin();

$errors = [];
$notice = '';
$editing = null;

$action = $_GET['action'] ?? 'list';
$editId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function validate_film_input(array $input): array {
    $errors = [];

    $title    = trim($input['title'] ?? '');
    $director = trim($input['director'] ?? '');
    $genre    = trim($input['genre'] ?? '');
    $country  = trim($input['country'] ?? '');
    $yearRaw  = trim((string) ($input['release_year'] ?? ''));
    $durRaw   = trim((string) ($input['duration'] ?? ''));
    $desc     = trim($input['description'] ?? '');
    $copiesRaw = trim((string) ($input['available_copies'] ?? '1'));

    if ($title === '' || mb_strlen($title) > 200) {
        $errors[] = 'Title is required (max 200 characters).';
    }
    if ($director === '' || mb_strlen($director) > 150) {
        $errors[] = 'Director is required (max 150 characters).';
    }
    if ($genre === '' || mb_strlen($genre) > 50) {
        $errors[] = 'Genre is required (max 50 characters).';
    }
    if ($country === '' || mb_strlen($country) > 100) {
        $errors[] = 'Country is required (max 100 characters).';
    }

    if (!preg_match('/^\d{4}$/', $yearRaw)) {
        $errors[] = 'Release year must be a 4-digit number.';
        $year = 0;
    } else {
        $year = (int) $yearRaw;
        $currentYear = (int) date('Y');
        if ($year < 1888 || $year > $currentYear + 5) {
            $errors[] = sprintf('Release year must be between 1888 and %d.', $currentYear + 5);
        }
    }

    $duration = filter_var($durRaw, FILTER_VALIDATE_INT);
    if ($duration === false || $duration < 1 || $duration > 600) {
        $errors[] = 'Duration must be an integer between 1 and 600 minutes.';
        $duration = 0;
    }

    $copies = filter_var($copiesRaw, FILTER_VALIDATE_INT);
    if ($copies === false || $copies < 0 || $copies > 10000) {
        $errors[] = 'Available copies must be a non-negative integer (max 10000).';
        $copies = 0;
    }

    if (mb_strlen($desc) > 5000) {
        $errors[] = 'Description is too long (max 5000 characters).';
    }

    return [
        'errors' => $errors,
        'data'   => [
            'title'            => $title,
            'director'         => $director,
            'genre'            => $genre,
            'country'          => $country,
            'release_year'     => $year,
            'duration'         => $duration,
            'description'      => $desc,
            'available_copies' => $copies,
        ],
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $op = $_POST['op'] ?? '';

    if ($op === 'create' || $op === 'update') {
        $validated = validate_film_input($_POST);
        $errors = $validated['errors'];
        $data = $validated['data'];

        if ($op === 'update') {
            $editId = (int) ($_POST['id'] ?? 0);
            if ($editId <= 0) {
                $errors[] = 'Invalid film id.';
            }
        }

        if (!$errors) {
            try {
                if ($op === 'create') {
                    $stmt = $pdo->prepare(
                        'INSERT INTO films
                            (title, director, genre, country, release_year, duration, description, available_copies)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $stmt->execute([
                        $data['title'],
                        $data['director'],
                        $data['genre'],
                        $data['country'],
                        $data['release_year'],
                        $data['duration'],
                        $data['description'],
                        $data['available_copies'],
                    ]);
                    $notice = 'Film added.';
                    $action = 'list';
                } else {
                    $stmt = $pdo->prepare(
                        'UPDATE films
                            SET title = ?, director = ?, genre = ?, country = ?,
                                release_year = ?, duration = ?, description = ?,
                                available_copies = ?
                          WHERE id = ?'
                    );
                    $stmt->execute([
                        $data['title'],
                        $data['director'],
                        $data['genre'],
                        $data['country'],
                        $data['release_year'],
                        $data['duration'],
                        $data['description'],
                        $data['available_copies'],
                        $editId,
                    ]);
                    $notice = 'Film updated.';
                    $action = 'list';
                }
            } catch (PDOException $e) {
                error_log('Film save failed: ' . $e->getMessage());
                $errors[] = 'Could not save the film. Please try again.';
            }
        }

        if ($errors) {
            $editing = $data;
            $editing['id'] = $editId;
            $action = ($op === 'update') ? 'edit' : 'new';
        }
    } elseif ($op === 'delete') {
        $delId = (int) ($_POST['id'] ?? 0);
        if ($delId <= 0) {
            $errors[] = 'Invalid film id.';
        } else {
            try {
                $stmt = $pdo->prepare('DELETE FROM films WHERE id = ?');
                $stmt->execute([$delId]);
                $notice = 'Film deleted.';
            } catch (PDOException $e) {
                error_log('Film delete failed: ' . $e->getMessage());
                $errors[] = 'Could not delete the film.';
            }
        }
        $action = 'list';
    }
}

if ($action === 'edit' && !$editing) {
    if ($editId > 0) {
        try {
            $stmt = $pdo->prepare('SELECT * FROM films WHERE id = ? LIMIT 1');
            $stmt->execute([$editId]);
            $editing = $stmt->fetch();
            if (!$editing) {
                $errors[] = 'Film not found.';
                $action = 'list';
            }
        } catch (PDOException $e) {
            error_log('Film load failed: ' . $e->getMessage());
            $errors[] = 'Could not load the film.';
            $action = 'list';
        }
    } else {
        $action = 'list';
    }
}

$films = [];
if ($action === 'list') {
    try {
        $films = $pdo->query(
            'SELECT id, title, director, genre, country, release_year, duration, available_copies
               FROM films
              ORDER BY created_at DESC, id DESC'
        )->fetchAll();
    } catch (PDOException $e) {
        error_log('Film list failed: ' . $e->getMessage());
        $errors[] = 'Could not load the film list.';
    }
}

$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Movie Database</title>
    <link rel="stylesheet" href="public/styles/style.css">
    <link rel="stylesheet" href="public/styles/auth.css">
    <link rel="stylesheet" href="public/styles/dashboard.css">
</head>
<body>
    <header>
        <h1 class="header-title">Admin Dashboard</h1>
    </header>

    <main>
        <section class="content-section dashboard-header">
            <div>
                <p class="dashboard-user">
                    Signed in as <strong><?= h($user['username']) ?></strong>
                    (<?= h($user['role']) ?>)
                </p>
            </div>
            <div class="dashboard-actions">
                <a class="cta-button" href="dashboard.php">All films</a>
                <a class="cta-button" href="dashboard.php?action=new">Add film</a>
                <a class="cta-button" href="logout.php">Sign out</a>
            </div>
        </section>

        <?php if ($notice): ?>
            <div class="content-section flash-notice" role="status">
                <?= h($notice) ?>
            </div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="content-section">
                <ul class="auth-errors" role="alert">
                    <?php foreach ($errors as $err): ?>
                        <li><?= h($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($action === 'new' || $action === 'edit'):
            $isEdit = ($action === 'edit');
            $f = $editing ?? [
                'title' => '', 'director' => '', 'genre' => '', 'country' => '',
                'release_year' => '', 'duration' => '', 'description' => '',
                'available_copies' => 1, 'id' => 0,
            ];
        ?>
            <section class="content-section">
                <h2><?= $isEdit ? 'Edit film' : 'Add film' ?></h2>
                <form method="post" action="dashboard.php" novalidate>
                    <input type="hidden" name="op" value="<?= $isEdit ? 'update' : 'create' ?>">
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="id" value="<?= (int) ($f['id'] ?? 0) ?>">
                    <?php endif; ?>

                    <div class="form-row">
                        <label for="title">Title *</label>
                        <input type="text" id="title" name="title"
                               value="<?= h((string) $f['title']) ?>"
                               required maxlength="200">
                    </div>

                    <div class="form-row">
                        <label for="director">Director *</label>
                        <input type="text" id="director" name="director"
                               value="<?= h((string) $f['director']) ?>"
                               required maxlength="150">
                    </div>

                    <div class="form-row">
                        <label for="genre">Genre *</label>
                        <input type="text" id="genre" name="genre"
                               value="<?= h((string) $f['genre']) ?>"
                               required maxlength="50">
                    </div>

                    <div class="form-row">
                        <label for="country">Country *</label>
                        <input type="text" id="country" name="country"
                               value="<?= h((string) $f['country']) ?>"
                               required maxlength="100">
                    </div>

                    <div class="form-grid">
                        <div class="form-row">
                            <label for="release_year">Release year *</label>
                            <input type="number" id="release_year" name="release_year"
                                   value="<?= h((string) $f['release_year']) ?>"
                                   required min="1888" max="<?= (int) date('Y') + 5 ?>" step="1"
                                   inputmode="numeric" pattern="\d{4}">
                        </div>

                        <div class="form-row">
                            <label for="duration">Duration (min) *</label>
                            <input type="number" id="duration" name="duration"
                                   value="<?= h((string) $f['duration']) ?>"
                                   required min="1" max="600" step="1">
                        </div>

                        <div class="form-row">
                            <label for="available_copies">Available copies</label>
                            <input type="number" id="available_copies" name="available_copies"
                                   value="<?= h((string) $f['available_copies']) ?>"
                                   min="0" max="10000" step="1">
                        </div>
                    </div>

                    <div class="form-row">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="5"
                                  maxlength="5000"><?= h((string) $f['description']) ?></textarea>
                    </div>

                    <div class="form-buttons">
                        <button type="submit" class="btn-primary">
                            <?= $isEdit ? 'Save changes' : 'Add film' ?>
                        </button>
                        <a href="dashboard.php" class="cta-button cta-cancel">Cancel</a>
                    </div>
                </form>
            </section>
        <?php else: ?>
            <section class="content-section">
                <h2>Films</h2>
                <?php if (!$films): ?>
                    <p class="empty-state">No films yet. <a href="dashboard.php?action=new">Add the first one</a>.</p>
                <?php else: ?>
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
                                            <a class="btn-link" href="dashboard.php?action=edit&amp;id=<?= (int) $film['id'] ?>">Edit</a>
                                            <form method="post" action="dashboard.php"
                                                  onsubmit="return confirm('Delete this film?');">
                                                <input type="hidden" name="op" value="delete">
                                                <input type="hidden" name="id" value="<?= (int) $film['id'] ?>">
                                                <button type="submit" class="btn-link btn-link-danger">Delete</button>
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

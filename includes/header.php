<?php
// includes/header.php — shared HTML head + top bar.
// Optional page-level variables set by the caller before requiring this file:
//   $pageTitle   — appears in <title> and as default heading
//   $pageHeading — overrides the <h1> text if different from $pageTitle
//   $pageStyles  — array of extra CSS paths to load (relative to project root)

if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/auth.php';
}

$_title   = isset($pageTitle) ? $pageTitle . ' — Movie Database' : 'Movie Database';
$_heading = $pageHeading ?? ($pageTitle ?? 'Movie Database');
$_styles  = $pageStyles ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($_title, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="public/styles/style.css">
    <link rel="stylesheet" href="public/styles/auth.css">
    <?php foreach ($_styles as $_s): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($_s, ENT_QUOTES, 'UTF-8') ?>">
    <?php endforeach; ?>
</head>
<body>
    <header>
        <h1 class="header-title"><?= htmlspecialchars($_heading, ENT_QUOTES, 'UTF-8') ?></h1>
    </header>
    <main>

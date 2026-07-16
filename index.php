<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/data/sounds.php';

$envConfig = [];
if (is_file(__DIR__ . '/env.php')) {
    $envConfig = (array) require __DIR__ . '/env.php';
}

$hash = (string) ($envConfig['APP_PASSWORD_HASH'] ?? getenv('APP_PASSWORD_HASH') ?: ($_ENV['APP_PASSWORD_HASH'] ?? '') ?: '$2y$10$ZswoE6REG1AJIpnIHHa9BeSb0Ud633gGpM1foUK1OL4RRAmyi4r6G');
$auth = new DnDSounds\Auth($hash);

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_destroy();
    header('Location: /index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($auth->attempt((string) $_POST['password'])) {
        $_SESSION['dn_d_sounds_logged_in'] = true;
        $_SESSION['dn_d_sounds_seen_landing'] = false;
        header('Location: /index.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['continue'])) {
    $_SESSION['dn_d_sounds_seen_landing'] = true;
    header('Location: /index.php');
    exit;
}

if (!($_SESSION['dn_d_sounds_logged_in'] ?? false)) {
    echo <<<'HTML'
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="A simple DnD audio fragment player for quick soundboard playback during games.">
    <title>DnDSounds</title>
    <style>
        body { font-family: Arial, sans-serif; background: #111827; color: #f9fafb; padding: 2rem; }
        .card { max-width: 420px; margin: 3rem auto; background: #1f2937; padding: 2rem; border-radius: 12px; }
        input { width: 100%; padding: .75rem; margin-top: .5rem; border-radius: 8px; border: 1px solid #374151; }
        button { margin-top: 1rem; width: 100%; padding: .8rem; border: 0; border-radius: 8px; background: #8b5cf6; color: white; cursor: pointer; }
    </style>
</head>
<body>
    <div class="card">
        <h1>DnDSounds</h1>
        <p>Enter the shared password to access the soundboard.</p>
        <form method="post">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
HTML;
    exit;
}

if (!($_SESSION['dn_d_sounds_seen_landing'] ?? false)) {
    echo <<<'HTML'
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="A simple DnD audio fragment player for quick soundboard playback during games.">
    <title>Welcome to DnDSounds</title>
    <style>
        body { font-family: Arial, sans-serif; background: #111827; color: #f9fafb; padding: 2rem; }
        .card { max-width: 520px; margin: 3rem auto; background: #1f2937; padding: 2rem; border-radius: 12px; }
        button { margin-top: 1rem; width: 100%; padding: .8rem; border: 0; border-radius: 8px; background: #8b5cf6; color: white; cursor: pointer; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Welcome to DnDSounds</h1>
        <p>You are now authenticated. Click continue to enter the soundboard.</p>
        <form method="post">
            <input type="hidden" name="continue" value="1">
            <button type="submit">Continue to soundboard</button>
        </form>
    </div>
</body>
</html>
HTML;
    exit;
}

$allSounds = require __DIR__ . '/data/sounds.php';
$characterFilter = (string) ($_GET['character'] ?? '');
$situationFilter = (string) ($_GET['situation'] ?? '');
$searchQuery = trim((string) ($_GET['search'] ?? ''));
$searchLower = strtolower($searchQuery);
$filtered = array_values(array_filter($allSounds, static function (array $item) use ($characterFilter, $situationFilter, $searchLower): bool {
    if ($characterFilter !== '' && strcasecmp((string) $item['character'], $characterFilter) !== 0) {
        return false;
    }
    if ($situationFilter !== '' && strcasecmp((string) $item['occasion'], $situationFilter) !== 0) {
        return false;
    }
    if ($searchLower !== '') {
        $haystack = strtolower((string) $item['title'] . ' ' . $item['character'] . ' ' . $item['occasion'] . ' ' . $item['description']);
        return str_contains($haystack, $searchLower);
    }
    return true;
}));

$characters = array_values(array_unique(array_map(static function (array $item): string {
    return (string) $item['character'];
}, $allSounds)));
$situations = array_values(array_unique(array_map(static function (array $item): string {
    return (string) $item['occasion'];
}, $allSounds)));

?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="A simple DnD audio fragment player for quick soundboard playback during games.">
    <title>DnDSounds</title>
    <style>
        body { font-family: Arial, sans-serif; background: #111827; color: #f9fafb; margin: 0; padding: 2rem; }
        .toolbar { display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; margin-bottom: 1.5rem; }
        select, button { padding: .7rem .9rem; border-radius: 8px; border: 1px solid #374151; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; }
        .card { background: #1f2937; padding: 1rem; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,.25); }
        .card h2 { margin-top: 0; }
        .audio-button { display: inline-flex; align-items: center; justify-content: center; width: 3rem; height: 3rem; border-radius: 50%; border: 1px solid #4b5563; background: #8b5cf6; color: white; cursor: pointer; font-size: 1rem; margin-top: .8rem; }
        .audio-button.playing { background: #f59e0b; }
        .audio-button:hover { background: #7c3aed; }
        .audio-player { display: none; }
        a.logout { color: #fbbf24; text-decoration: none; }
    </style>
</head>
<body>
    <div class="toolbar">
        <h1 style="margin:0;">DnDSounds</h1>
        <form method="get" style="display:flex; gap:.75rem; align-items:center; flex-wrap: wrap;">
            <label for="character">Character</label>
            <select id="character" name="character" onchange="this.form.submit()">
                <option value="">All characters</option>
                <?php foreach ($characters as $character): ?>
                    <option value="<?= htmlspecialchars($character, ENT_QUOTES, 'UTF-8') ?>" <?= $characterFilter === $character ? 'selected' : '' ?>><?= htmlspecialchars($character, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>

            <label for="situation">Situation</label>
            <select id="situation" name="situation" onchange="this.form.submit()">
                <option value="">All situations</option>
                <?php foreach ($situations as $situation): ?>
                    <option value="<?= htmlspecialchars($situation, ENT_QUOTES, 'UTF-8') ?>" <?= $situationFilter === $situation ? 'selected' : '' ?>><?= htmlspecialchars($situation, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>

            <label for="search">Search</label>
            <input id="search" name="search" type="search" value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search fragments">

            <button type="submit" style="padding:.75rem .9rem;">Apply</button>
        </form>
        <form method="post" action="index.php">
            <input type="hidden" name="logout" value="1">
            <button type="submit">Logout</button>
        </form>
    </div>

    <div class="grid">
        <?php foreach ($filtered as $sound): ?>
            <div class="card">
                <h2><?= htmlspecialchars((string) $sound['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                <p><strong>Character:</strong> <?= htmlspecialchars((string) $sound['character'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Occasion:</strong> <?= htmlspecialchars((string) $sound['occasion'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><?= htmlspecialchars((string) $sound['description'], ENT_QUOTES, 'UTF-8') ?></p>
                <button type="button" class="audio-button" data-audio-src="<?= htmlspecialchars((string) $sound['file'], ENT_QUOTES, 'UTF-8') ?>">▶</button>
                <audio class="audio-player" preload="none">
                    <source type="audio/mpeg">
                    Your browser does not support the audio element.
                </audio>
            </div>
        <?php endforeach; ?>
    </div>
    <script>
        document.querySelectorAll('.audio-button').forEach(function (button) {
            var player = button.closest('.card').querySelector('.audio-player');
            var source = player.querySelector('source');
            source.src = button.dataset.audioSrc;
            player.load();

            button.addEventListener('click', function () {
                if (player.paused) {
                    document.querySelectorAll('.audio-player').forEach(function (otherPlayer) {
                        if (otherPlayer !== player) {
                            otherPlayer.pause();
                            var otherButton = otherPlayer.closest('.card').querySelector('.audio-button');
                            if (otherButton) {
                                otherButton.textContent = '▶';
                                otherButton.classList.remove('playing');
                            }
                        }
                    });
                    player.play();
                    button.textContent = '■';
                    button.classList.add('playing');
                } else {
                    player.pause();
                    button.textContent = '▶';
                    button.classList.remove('playing');
                }
            });

            player.addEventListener('ended', function () {
                button.textContent = '▶';
                button.classList.remove('playing');
            });
        });
    </script>
</body>
</html>

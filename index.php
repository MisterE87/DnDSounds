<?php

declare(strict_types=1);

require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/data/sounds.php';

$hash = getenv('APP_PASSWORD_HASH') ?: (($_ENV['APP_PASSWORD_HASH'] ?? '') ?: '$2y$10$ZswoE6REG1AJIpnIHHa9BeSb0Ud633gGpM1foUK1OL4RRAmyi4r6G');
$auth = new DnDSounds\Auth($hash);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($auth->attempt((string) $_POST['password'])) {
        session_start();
        $_SESSION['dn_d_sounds_logged_in'] = true;
        header('Location: /index.php');
        exit;
    }
}

session_start();
if (!($_SESSION['dn_d_sounds_logged_in'] ?? false)) {
    echo <<<'HTML'
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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

$allSounds = require __DIR__ . '/data/sounds.php';
$characterFilter = (string) ($_GET['character'] ?? '');
$filtered = array_values(array_filter($allSounds, static function (array $item) use ($characterFilter): bool {
    return $characterFilter === '' || strcasecmp((string) $item['character'], $characterFilter) === 0;
}));

$characters = array_values(array_unique(array_map(static function (array $item): string {
    return (string) $item['character'];
}, $allSounds)));

?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DnDSounds</title>
    <style>
        body { font-family: Arial, sans-serif; background: #111827; color: #f9fafb; margin: 0; padding: 2rem; }
        .toolbar { display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; margin-bottom: 1.5rem; }
        select, button { padding: .7rem .9rem; border-radius: 8px; border: 1px solid #374151; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; }
        .card { background: #1f2937; padding: 1rem; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,.25); }
        .card h2 { margin-top: 0; }
        audio { width: 100%; margin-top: .8rem; }
        a.logout { color: #fbbf24; text-decoration: none; }
    </style>
</head>
<body>
    <div class="toolbar">
        <h1 style="margin:0;">DnDSounds</h1>
        <form method="get" style="display:flex; gap:.5rem; align-items:center;">
            <label for="character">Filter by character</label>
            <select id="character" name="character" onchange="this.form.submit()">
                <option value="">All characters</option>
                <?php foreach ($characters as $character): ?>
                    <option value="<?= htmlspecialchars($character, ENT_QUOTES, 'UTF-8') ?>" <?= $characterFilter === $character ? 'selected' : '' ?>><?= htmlspecialchars($character, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
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
                <audio controls preload="none">
                    <source src="<?= htmlspecialchars((string) $sound['file'], ENT_QUOTES, 'UTF-8') ?>" type="audio/mpeg">
                    Your browser does not support the audio element.
                </audio>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>

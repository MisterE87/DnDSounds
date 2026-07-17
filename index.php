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
    <title>Welcome to the Dungeon of sounds</title>
    <style>
        body {
            font-family: 'Georgia', serif;
            background: radial-gradient(circle at top, #2f1f0f 0%, #0d0910 40%, #080609 100%);
            color: #f7efd0;
            padding: 2rem;
            margin: 0;
        }
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: url('data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 120 120"%3E%3Cpath fill="none" stroke="rgba(247,239,208,0.08)" stroke-width="2" d="M0 0l120 120M120 0L0 120"/%3E%3C/svg%3E') repeat;
            opacity: .09;
            pointer-events: none;
        }
        .card {
            max-width: 460px;
            margin: 3rem auto;
            background: rgba(15, 11, 7, 0.95);
            padding: 2rem 2.25rem;
            border-radius: 20px;
            border: 1px solid rgba(247,239,208,0.16);
            box-shadow: 0 20px 60px rgba(0,0,0,0.45);
        }
        h1 {
            margin-top: 0;
            margin-bottom: 1rem;
            font-family: 'Palatino Linotype', 'Book Antiqua', Palatino, serif;
            font-size: 2rem;
            letter-spacing: .04em;
            text-shadow: 0 1px 4px rgba(0,0,0,.35);
        }
        p {
            line-height: 1.8;
            margin: 0 0 1.5rem;
            color: #e5d6ac;
        }
        label {
            display: block;
            margin-bottom: .5rem;
            color: #e8d9b8;
        }
        input {
            width: 100%;
            padding: .95rem 1rem;
            margin-bottom: 1rem;
            border-radius: 12px;
            border: 1px solid rgba(247,239,208,0.2);
            background: rgba(255,255,255,0.06);
            color: #f7efd0;
            font-size: 1rem;
        }
        input:focus {
            outline: none;
            border-color: #d6b75a;
            box-shadow: 0 0 0 4px rgba(214,183,90,.12);
        }
        .button {
            width: 100%;
            padding: 1rem;
            border: 1px solid rgba(255, 209, 102, 0.35);
            border-radius: 14px;
            background: linear-gradient(135deg, #fcd34d 0%, #d97706 100%);
            color: #111827;
            font-weight: bold;
            cursor: pointer;
            transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
        }
        .button:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 28px rgba(217, 119, 6, .3);
        }
        .play-riddle {
            width: 100%;
            padding: 1rem;
            border: 1px solid rgba(255, 224, 134, 0.22);
            border-radius: 14px;
            background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
            color: #111827;
            font-weight: bold;
            cursor: pointer;
            transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
            margin-bottom: 1rem;
        }
        .play-riddle:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 28px rgba(217, 119, 6, .3);
        }
        .play-riddle.playing {
            background: linear-gradient(135deg, #f97316 0%, #dc2626 100%);
        }
        .form-actions {
            display: grid;
            gap: 1rem;
            margin-top: 1rem;
        }
        .hint {
            font-size: .95rem;
            color: #cbbf90;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Welcome to the Dungeon of sounds</h1>
        <p class="hint">Hear the riddle first, then enter the solution to unlock the soundboard.</p>
        <button type="button" id="login-knowledge-play" class="play-riddle">▶ Play the riddle</button>
        <audio id="login-knowledge-audio" preload="none">
            <source src="assets/sounds/knowledge.mp3" type="audio/mpeg">
            Your browser does not support the audio element.
        </audio>
        <form method="post" class="form-actions">
            <div>
                <label for="password">Answer to the riddle</label>
                <input id="password" name="password" type="password" required>
            </div>
            <button type="submit" class="button">Enter the vault</button>
        </form>
    </div>
    <script>
        const loginPlayButton = document.getElementById('login-knowledge-play');
        const loginKnowledgeAudio = document.getElementById('login-knowledge-audio');

        loginPlayButton.addEventListener('click', function () {
            if (loginKnowledgeAudio.paused) {
                loginKnowledgeAudio.play();
                loginPlayButton.textContent = '■ Pause the riddle';
                loginPlayButton.classList.add('playing');
            } else {
                loginKnowledgeAudio.pause();
                loginPlayButton.textContent = '▶ Play the riddle';
                loginPlayButton.classList.remove('playing');
            }
        });

        loginKnowledgeAudio.addEventListener('ended', function () {
            loginPlayButton.textContent = '▶ Play the riddle';
            loginPlayButton.classList.remove('playing');
        });
    </script>
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
        body {
            font-family: 'Georgia', serif;
            background: radial-gradient(circle at top, #2f1f0f 0%, #0d0910 40%, #080609 100%);
            color: #f7efd0;
            padding: 2rem;
            margin: 0;
        }
        .card {
            max-width: 560px;
            margin: 3rem auto;
            background: rgba(12, 8, 5, 0.96);
            padding: 2rem 2.25rem;
            border-radius: 22px;
            border: 1px solid rgba(247,239,208,0.16);
            box-shadow: 0 22px 72px rgba(0,0,0,0.5);
        }
        h1 {
            margin-top: 0;
            margin-bottom: 1rem;
            font-size: 2.15rem;
            letter-spacing: .045em;
            text-shadow: 0 1px 6px rgba(0,0,0,.4);
        }
        p {
            line-height: 1.8;
            margin: 0 0 1.5rem;
            color: #e5d6ac;
        }
        img {
            display: block;
            width: 100%;
            max-height: 320px;
            object-fit: cover;
            border-radius: 18px;
            border: 1px solid rgba(247,239,208,0.15);
            margin-top: 1rem;
        }
        .button-group {
            display: grid;
            gap: 1rem;
            margin-top: 1.75rem;
        }
        .button-group button {
            width: 100%;
            padding: 1.1rem;
            border: 1px solid rgba(255, 209, 102, 0.35);
            border-radius: 14px;
            background: linear-gradient(135deg, #fcd34d 0%, #d97706 100%);
            color: #111827;
            font-weight: bold;
            cursor: pointer;
            transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
        }
        .button-group button:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 30px rgba(217, 119, 6, .3);
        }
        img {
            display: block;
            width: 100%;
            max-height: 340px;
            object-fit: contain;
            border-radius: 18px;
            border: 1px solid rgba(247,239,208,0.15);
            margin: 1rem auto 0;
            box-shadow: 0 14px 30px rgba(0,0,0,.28);
            background: rgba(255,255,255,0.04);
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Welcome to the sacred dungeon of DnD sounds.</h1>
        <p>Step into the chamber, then open the vault to explore the soundboard.</p>
        <img src="assets/Fibonacci.png" alt="Fibonacci">
        <div class="button-group">
            <form method="post">
                <input type="hidden" name="continue" value="1">
                <button type="submit">Open the vault</button>
            </form>
        </div>
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
        body {
            font-family: 'Georgia', serif;
            background: radial-gradient(circle at top, #2f1f0f 0%, #0d0910 40%, #080609 100%);
            color: #f7efd0;
            margin: 0;
            padding: 2rem;
        }
        .toolbar {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 1.75rem;
            padding: 1rem 1.25rem;
            background: rgba(15, 11, 7, 0.9);
            border: 1px solid rgba(247,239,208,0.16);
            border-radius: 18px;
            box-shadow: 0 18px 40px rgba(0,0,0,.35);
        }
        select, button, input[type="search"] {
            padding: .85rem 1rem;
            border-radius: 14px;
            border: 1px solid rgba(247,239,208,0.18);
            background: rgba(20, 14, 8, 0.8);
            color: #f7efd0;
            font-size: 1rem;
        }
        select, input[type="search"] {
            min-width: 180px;
        }
        select option {
            color: #f7efd0;
            background: #0d0910;
        }
        button {
            cursor: pointer;
            background: linear-gradient(135deg, #8b5cf6 0%, #5b21b6 100%);
            color: #fff;
            border: 1px solid rgba(255, 224, 134, 0.22);
            transition: transform .2s ease, box-shadow .2s ease;
        }
        button:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 28px rgba(139,92,246,.25);
        }
        .category {
            margin-bottom: 1.5rem;
            border: 1px solid rgba(247,239,208,0.16);
            border-radius: 18px;
            background: rgba(12, 8, 5, 0.95);
            box-shadow: 0 18px 36px rgba(0,0,0,.3);
            overflow: hidden;
        }
        .category summary {
            list-style: none;
            padding: 1rem 1.25rem;
            background: linear-gradient(90deg, rgba(255,255,255,0.04), rgba(255,255,255,0.02));
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1.05rem;
            font-weight: bold;
            color: #f7efd0;
        }
        .category summary::-webkit-details-marker {
            display: none;
        }
        .category summary:after {
            content: '▾';
            font-size: 1.1rem;
            transition: transform .2s ease;
            color: #f7efd0;
        }
        .category[open] summary:after {
            transform: rotate(180deg);
        }
        .table-wrap {
            overflow-x: auto;
        }
        table.sound-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 620px;
        }
        table.sound-table th,
        table.sound-table td {
            padding: .75rem .9rem;
            border-bottom: 1px solid rgba(247,239,208,.1);
            vertical-align: middle;
        }
        table.sound-table th {
            text-align: left;
            color: #e5d6ac;
            font-size: .88rem;
            letter-spacing: .03em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        table.sound-table th:nth-child(2) {
            width: 170px;
        }
        table.sound-table tr:hover {
            background: rgba(255,255,255,.05);
        }
        .sound-cell {
            display: flex;
            align-items: flex-start;
            gap: .85rem;
        }
        .sound-info {
            min-width: 0;
        }
        .sound-title {
            color: #fff;
            font-weight: 700;
            margin-bottom: .2rem;
            line-height: 1.3;
        }
        .sound-meta {
            color: #cbbf90;
            font-size: .86rem;
            line-height: 1.5;
        }
        .audio-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.6rem;
            height: 2.6rem;
            min-width: 2.6rem;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,0.15);
            background: linear-gradient(135deg, #8b5cf6 0%, #5b21b6 100%);
            color: white;
            cursor: pointer;
            font-size: .95rem;
            box-shadow: 0 8px 16px rgba(0,0,0,.15);
            flex-shrink: 0;
        }
        .audio-button.playing {
            background: linear-gradient(135deg, #f59e0b 0%, #dc2626 100%);
        }
        .audio-button:hover {
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
        }
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

    <?php
    $groups = [];
    foreach ($filtered as $sound) {
        $groups[$sound['occasion']][] = $sound;
    }
    ksort($groups, SORT_NATURAL | SORT_FLAG_CASE);
    foreach ($groups as $occasion => $sounds):
        usort($sounds, static function (array $a, array $b): int {
            return strcasecmp((string) $a['title'], (string) $b['title']);
        });
    ?>
        <details class="category"<?= empty($sounds) ? '' : ' open' ?> >
            <summary><?= htmlspecialchars((string) $occasion, ENT_QUOTES, 'UTF-8') ?> (<?= count($sounds) ?>)</summary>
            <div class="table-wrap">
                <table class="sound-table">
                    <thead>
                        <tr>
                            <th>Sound</th>
                            <th>Character</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sounds as $sound): ?>
                            <tr>
                                <td>
                                    <div class="sound-cell">
                                        <button type="button" class="audio-button" data-audio-src="<?= htmlspecialchars((string) $sound['file'], ENT_QUOTES, 'UTF-8') ?>">▶</button>
                                        <div class="sound-info">
                                            <div class="sound-title"><?= htmlspecialchars((string) $sound['title'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <div class="sound-meta"><?= htmlspecialchars((string) $sound['description'], ENT_QUOTES, 'UTF-8') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars((string) $sound['character'], ENT_QUOTES, 'UTF-8') ?></td>
                                <audio class="audio-player" preload="none">
                                    <source type="audio/mpeg">
                                    Your browser does not support the audio element.
                                </audio>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </details>
    <?php endforeach; ?>
    <script>
        document.querySelectorAll('.audio-button').forEach(function (button) {
            var player = button.closest('td').querySelector('.audio-player');
            var source = player.querySelector('source');
            source.src = button.dataset.audioSrc;
            player.load();

            button.addEventListener('click', function () {
                if (player.paused) {
                    document.querySelectorAll('.audio-player').forEach(function (otherPlayer) {
                        if (otherPlayer !== player) {
                            otherPlayer.pause();
                            var otherButton = otherPlayer.closest('td').querySelector('.audio-button');
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

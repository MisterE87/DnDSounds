<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Auth.php';

$hash = '$2y$10$ZswoE6REG1AJIpnIHHa9BeSb0Ud633gGpM1foUK1OL4RRAmyi4r6G';
$auth = new DnDSounds\Auth($hash);
$sounds = require __DIR__ . '/../data/sounds.php';

if (!$auth->attempt('secret123')) {
    fwrite(STDERR, "Authentication check failed\n");
    exit(1);
}

if (!is_array($sounds) || count($sounds) < 3) {
    fwrite(STDERR, "Sound library should contain at least three entries\n");
    exit(1);
}

$first = $sounds[0];
if (!isset($first['file']) || !is_string($first['file']) || $first['file'] === '') {
    fwrite(STDERR, "Each sound entry must include a file path\n");
    exit(1);
}

$indexSource = file_get_contents(__DIR__ . '/../index.php');
if (!str_contains($indexSource, '<video') || !str_contains($indexSource, 'addEventListener(\'ended\'') || !str_contains($indexSource, "continueButton.disabled = false;")) {
    fwrite(STDERR, "Landing page should render a video gate before the soundboard can be opened\n");
    exit(1);
}

fwrite(STDOUT, "Verification passed\n");

<?php
header('Content-Type: text/xml; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
$family = $_GET['family'] ?? 'systemSmall';

// Load per-user variables ({{KEY}} => value)
$_vf   = __DIR__ . '/data/variables_petres.json';
$_vars = file_exists($_vf) ? (json_decode(file_get_contents($_vf), true) ?: []) : [];

$allowedFamilies = ['systemSmall', 'systemMedium', 'systemLarge'];
if (in_array($family, $allowedFamilies, true)) {
    $_file = __DIR__ . '/data/petres_' . $family . '.xml';
    if (!file_exists($_file)) {
        http_response_code(404);
        echo '<error>Missing widget file for family: ' . htmlspecialchars($family) . '</error>';
        exit;
    }
    $_xml = file_get_contents($_file);
    foreach ($_vars as $_k => $_v) {
        $_xml = str_replace('{{' . $_k . '}}', htmlspecialchars($_v, ENT_XML1, 'UTF-8'), $_xml);
    }
    echo $_xml;
} else {
    http_response_code(404);
    echo '<error>Unknown family: ' . htmlspecialchars($family) . '</error>';
}

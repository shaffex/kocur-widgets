<?php
header('Content-Type: text/xml; charset=utf-8');
$family = $_GET['family'] ?? 'systemSmall';

// Load per-user variables ({{KEY}} => value)
$_vf   = __DIR__ . '/data/variables_lukes.json';
$_vars = file_exists($_vf) ? (json_decode(file_get_contents($_vf), true) ?: []) : [];

$widgets = [
];

if (isset($widgets[$family])) {
    $_xml = $widgets[$family];
    foreach ($_vars as $_k => $_v) {
        $_xml = str_replace('{{' . $_k . '}}', htmlspecialchars($_v, ENT_XML1, 'UTF-8'), $_xml);
    }
    echo $_xml;
} else {
    http_response_code(404);
    echo '<error>Unknown family: ' . htmlspecialchars($family) . '</error>';
}

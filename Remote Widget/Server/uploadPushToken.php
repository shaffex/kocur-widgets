<?php
/**
 * uploadPushToken.php
 *
 * Receives an APNS push token payload (JSON POST), upserts it in tokens.json
 * keyed by appUUID, and appends a timestamped log entry to tokens.txt.
 *
 * Expected JSON body:
 * {
 *   "pushToken":   "<hex string>",
 *   "appUUID":     "<UUID>",
 *   "bundleID":    "<string>",
 *   "deviceModel": "<string>",
 *   "osVersion":   "<string>",
 *   "appVersion":  "<string>"
 * }
 */

header('Content-Type: application/json');

// ── paths ──────────────────────────────────────────────────────────────────
$dir       = __DIR__;
$tokensJson = $dir . '/tokens.json';
$tokensTxt  = $dir . '/tokens.txt';

// ── read & validate input ──────────────────────────────────────────────────
$body = file_get_contents('php://input');
$data = json_decode($body, true);

$required = ['pushToken', 'appUUID', 'bundleID', 'deviceModel', 'osVersion', 'appVersion'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing field: $field"]);
        exit;
    }
}

$pushToken   = trim($data['pushToken']);
$appUUID     = trim($data['appUUID']);
$bundleID    = trim($data['bundleID']);
$deviceModel = trim($data['deviceModel']);
$osVersion   = trim($data['osVersion']);
$appVersion  = trim($data['appVersion']);
$timestamp   = date('Y-m-d H:i:s');

// ── update tokens.json ─────────────────────────────────────────────────────
$tokens = [];
if (file_exists($tokensJson)) {
    $existing = json_decode(file_get_contents($tokensJson), true);
    if (is_array($existing)) {
        $tokens = $existing;
    }
}

$isNew = !isset($tokens[$appUUID]);

$tokens[$appUUID] = [
    'pushToken'   => $pushToken,
    'bundleID'    => $bundleID,
    'deviceModel' => $deviceModel,
    'osVersion'   => $osVersion,
    'appVersion'  => $appVersion,
    'updatedAt'   => $timestamp,
    'createdAt'   => $isNew ? $timestamp : ($tokens[$appUUID]['createdAt'] ?? $timestamp),
];

file_put_contents($tokensJson, json_encode($tokens, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

// ── append to tokens.txt ───────────────────────────────────────────────────
$action  = $isNew ? 'NEW' : 'UPDATE';
$logLine = "[$timestamp] $action | appUUID=$appUUID | token=$pushToken | bundle=$bundleID | device=$deviceModel | os=$osVersion | appVersion=$appVersion" . PHP_EOL;
file_put_contents($tokensTxt, $logLine, FILE_APPEND | LOCK_EX);

// ── respond ────────────────────────────────────────────────────────────────
http_response_code(200);
echo json_encode(['status' => 'ok', 'action' => strtolower($action)]);

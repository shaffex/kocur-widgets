<?php
/**
 * uploadPushToken.php — WidgetPushNotifications
 *
 * Receives device push tokens for widget background updates.
 * Stores in tokens.json and logs to tokens.txt.
 *
 * JSON body: { pushToken, deviceUUID, bundleID, deviceModel, osVersion, appVersion, widgetConfigs }
 * widgetConfigs: [{ kind, deviceId, refreshInterval, widgetURL }]
 */

header('Content-Type: application/json');

$dir        = __DIR__;
$tokensJson = $dir . '/tokens.json';
$tokensTxt  = $dir . '/tokens.txt';

$body = file_get_contents('php://input');
$data = json_decode($body, true);

$required = ['pushToken', 'deviceUUID', 'bundleID', 'deviceModel', 'osVersion', 'appVersion'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing field: $field"]);
        exit;
    }
}

$pushToken     = trim($data['pushToken']);
$deviceUUID    = trim($data['deviceUUID']);
$bundleID      = trim($data['bundleID']);
$deviceModel   = trim($data['deviceModel']);
$osVersion     = trim($data['osVersion']);
$appVersion    = trim($data['appVersion']);
$widgetConfigs = isset($data['widgetConfigs']) && is_array($data['widgetConfigs']) ? $data['widgetConfigs'] : [];
$timestamp     = date('Y-m-d H:i:s');

$fp = fopen($tokensJson, 'c+');
if (!$fp) {
    http_response_code(500);
    echo json_encode(['error' => 'Cannot open tokens file']);
    exit;
}

flock($fp, LOCK_EX);

$existing = json_decode(stream_get_contents($fp), true);
$tokens   = is_array($existing) ? $existing : [];

$isNew = !isset($tokens[$deviceUUID]);

$tokens[$deviceUUID] = [
    'pushToken'     => $pushToken,
    'bundleID'      => $bundleID,
    'deviceModel'   => $deviceModel,
    'osVersion'     => $osVersion,
    'appVersion'    => $appVersion,
    'widgetConfigs' => $widgetConfigs,
    'updatedAt'     => $timestamp,
    'createdAt'     => $isNew ? $timestamp : ($tokens[$deviceUUID]['createdAt'] ?? $timestamp),
];

ftruncate($fp, 0);
rewind($fp);
fwrite($fp, json_encode($tokens, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
flock($fp, LOCK_UN);
fclose($fp);

// Build widget config summary for log line
$configSummary = implode(', ', array_map(function($w) {
    $kind       = $w['kind']            ?? '?';
    $id         = $w['deviceId']        ?? '?';
    $refresh    = $w['refreshInterval'] ?? '?';
    $url        = $w['widgetURL']       ?? '';
    $configured = isset($w['configured']) ? ($w['configured'] ? 'yes' : 'no') : '?';
    return "$kind | configured=$configured | id=$id | refresh={$refresh}min | url=$url";
}, $widgetConfigs));

$action  = $isNew ? 'NEW' : 'UPDATE';
$logLine = "[$timestamp] $action | device=$deviceModel | uuid=$deviceUUID | token=$pushToken | os=$osVersion | appVersion=$appVersion";
if ($configSummary) {
    $logLine .= PHP_EOL . "          widgets: $configSummary";
}
$logLine .= PHP_EOL;

file_put_contents($tokensTxt, $logLine, FILE_APPEND | LOCK_EX);

http_response_code(200);
echo json_encode(['status' => 'ok', 'action' => strtolower($action)]);

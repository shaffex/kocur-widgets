<?php
/**
 * sendWidgetUpdate.php — WidgetPushNotifications
 *
 * Sends a silent background push to all registered devices to trigger
 * a widget timeline reload. Uses apns-push-type: widgets + priority 5.
 *
 * Optional POST JSON parameters:
 *   data        – custom key/value object passed inside the payload
 *                 (e.g. { "xmlUrl": "https://..." } for the widget to read)
 */

// ── APNs config ────────────────────────────────────────────────────────────
define('APNS_KEY_PATH',      __DIR__ . '/../AuthKey_X5ZFL4832N.p8');
define('APNS_KEY_ID',        'X5ZFL4832N');
define('APNS_TEAM_ID',       'X47885HM53');
define('APNS_TOPIC',         'com.shaffex.remotewidget.push-type.widgets');
define('APNS_HOST_PROD',     'https://api.push.apple.com');
define('APNS_HOST_SANDBOX',  'https://api.sandbox.push.apple.com');
define('REMOVE_DEAD_TOKENS', false);

// ── bootstrap ──────────────────────────────────────────────────────────────
header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data  = json_decode($input, true) ?: $_POST;

$sandbox  = !empty($data['sandbox']) && $data['sandbox'] !== false;
$custom   = isset($data['data']) && is_array($data['data']) ? $data['data'] : [];

// ── load tokens ────────────────────────────────────────────────────────────
$tokensFile = __DIR__ . '/tokens.json';
if (!file_exists($tokensFile)) {
    http_response_code(500);
    echo json_encode(['error' => 'tokens.json not found']);
    exit;
}

$tokens = json_decode(file_get_contents($tokensFile), true);
if (!is_array($tokens) || empty($tokens)) {
    echo json_encode(['status' => 'ok', 'sent' => 0, 'message' => 'No tokens registered']);
    exit;
}

// ── logging ────────────────────────────────────────────────────────────────
$logFile = __DIR__ . '/widgetUpdates.txt';

function logLine(string $line): void {
    global $logFile;
    file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
}

// ── JWT ────────────────────────────────────────────────────────────────────
function base64UrlEncode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function apnsJWT(): string {
    $header  = base64UrlEncode(json_encode(['alg' => 'ES256', 'kid' => APNS_KEY_ID]));
    $payload = base64UrlEncode(json_encode(['iss' => APNS_TEAM_ID, 'iat' => time()]));
    $keyContent = file_get_contents(APNS_KEY_PATH);
    $pkey = openssl_pkey_get_private($keyContent);
    openssl_sign("$header.$payload", $signature, $pkey, OPENSSL_ALGO_SHA256);
    return "$header.$payload." . base64UrlEncode($signature);
}

// ── build widget update payload ────────────────────────────────────────────
function buildWidgetPayload(array $custom): string {
    return json_encode(array_merge($custom, [
        'aps' => ['content-changed' => true],
    ]));
}

// ── send to one token ──────────────────────────────────────────────────────
function sendToToken(string $token, string $jwt, string $payload, string $host): array {
    $url = $host . '/3/device/' . $token;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_2_0,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: bearer ' . $jwt,
            'apns-topic: '           . APNS_TOPIC,
            'apns-push-type: widgets',
            'apns-priority: 5',
            'apns-expiration: 0',
            'Content-Type: application/json',
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['httpCode' => $httpCode, 'response' => json_decode($response, true)];
}

// ── send to all devices ────────────────────────────────────────────────────
$ts = date('Y-m-d H:i:s');
$apnsHost = $sandbox ? APNS_HOST_SANDBOX : APNS_HOST_PROD;
$env      = $sandbox ? 'SANDBOX' : 'PRODUCTION';

$jwt     = apnsJWT();
$payload = buildWidgetPayload($custom);

$results        = ['sent' => 0, 'failed' => 0, 'removed' => 0, 'sandbox' => $sandbox, 'details' => []];
$tokensModified = false;

logLine("[$ts] -- WIDGET UPDATE START -- env=$env | devices=" . count($tokens) . (!empty($custom) ? ' | data=' . json_encode($custom) : ''));

foreach ($tokens as $deviceUUID => $info) {
    $pushToken   = $info['pushToken'] ?? '';
    $deviceModel = $info['deviceModel'] ?? 'unknown';
    if (empty($pushToken)) continue;

    $result   = sendToToken($pushToken, $jwt, $payload, $apnsHost);
    $httpCode = $result['httpCode'];
    $reason   = $result['response']['reason'] ?? null;

    $isInvalid = $httpCode === 410 || ($httpCode === 400 && $reason === 'BadDeviceToken');

    if ($httpCode === 200) {
        $results['sent']++;
        logLine("[$ts]   OK          | device=$deviceModel | token=...{$pushToken}");
    } else {
        $results['failed']++;
        $reasonStr = $reason ?? "HTTP $httpCode";
        if ($isInvalid) {
            if (REMOVE_DEAD_TOKENS) {
                unset($tokens[$deviceUUID]);
                $tokensModified = true;
                $results['removed']++;
                logLine("[$ts]   INVALIDATED | device=$deviceModel | token=...{$pushToken} | reason=$reasonStr -> removed");
            } else {
                logLine("[$ts]   INVALIDATED | device=$deviceModel | token=...{$pushToken} | reason=$reasonStr (kept)");
            }
        } else {
            logLine("[$ts]   FAILED      | device=$deviceModel | token=...{$pushToken} | reason=$reasonStr");
        }
    }

    $results['details'][] = [
        'deviceUUID'  => $deviceUUID,
        'deviceModel' => $deviceModel,
        'httpCode'    => $httpCode,
        'response'    => $result['response'],
        'removed'     => $isInvalid && REMOVE_DEAD_TOKENS,
    ];
}

$ts = date('Y-m-d H:i:s');
logLine("[$ts] -- WIDGET UPDATE END   -- sent={$results['sent']} failed={$results['failed']} removed={$results['removed']}");
logLine('');

if ($tokensModified) {
    $fp = fopen($tokensFile, 'c+');
    if ($fp) {
        flock($fp, LOCK_EX);
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($tokens, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

http_response_code(200);
echo json_encode(['status' => 'ok'] + $results);

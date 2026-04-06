<?php
/**
 * sendPushNotification.php
 *
 * Sends an APNs push notification to every token stored in tokens.json.
 * Uses APNs token-based auth (JWT + .p8 key) over HTTP/2.
 *
 * POST parameters (JSON body or form):
 *   Required:
 *     title       – notification title
 *     body        – notification body
 *   Optional:
 *     subtitle    – notification subtitle
 *     badge       – badge count (integer)
 *     sound       – sound name, default "default"
 *     data        – custom key/value object merged into the payload
 */

// ── APNs config ────────────────────────────────────────────────────────────
define('APNS_KEY_PATH',        __DIR__ . '/AuthKey_X5ZFL4832N.p8');
define('APNS_KEY_ID',          'X5ZFL4832N');
define('APNS_TEAM_ID',         'X47885HM53');
define('APNS_TOPIC',           'com.shaffex.remotewidget');
define('APNS_HOST_PROD',       'https://api.push.apple.com');
define('APNS_HOST_SANDBOX',    'https://api.sandbox.push.apple.com');
define('REMOVE_DEAD_TOKENS',   false);   // set false to keep invalid tokens in tokens.json

// ── bootstrap ──────────────────────────────────────────────────────────────
header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data  = json_decode($input, true) ?: $_POST;

if (empty($data['title']) || empty($data['body'])) {
    http_response_code(400);
    echo json_encode(['error' => 'title and body are required']);
    exit;
}

$title    = $data['title'];
$body     = $data['body'];
$subtitle = $data['subtitle'] ?? null;
$badge    = isset($data['badge']) ? (int)$data['badge'] : null;
$sound    = $data['sound'] ?? 'default';
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

// ── build APNs JWT ─────────────────────────────────────────────────────────
function apnsJWT(): string {
    $header  = base64UrlEncode(json_encode(['alg' => 'ES256', 'kid' => APNS_KEY_ID]));
    $payload = base64UrlEncode(json_encode(['iss' => APNS_TEAM_ID, 'iat' => time()]));

    $keyContent = file_get_contents(APNS_KEY_PATH);
    $pkey = openssl_pkey_get_private($keyContent);

    openssl_sign("$header.$payload", $signature, $pkey, OPENSSL_ALGO_SHA256);

    return "$header.$payload." . base64UrlEncode($signature);
}

function base64UrlEncode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// ── build APNs payload ─────────────────────────────────────────────────────
function buildPayload(string $title, string $body, ?string $subtitle, ?int $badge, string $sound, array $custom): string {
    $alert = ['title' => $title, 'body' => $body];
    if ($subtitle !== null) {
        $alert['subtitle'] = $subtitle;
    }

    $aps = ['alert' => $alert, 'sound' => $sound];
    if ($badge !== null) {
        $aps['badge'] = $badge;
    }

    return json_encode(array_merge($custom, ['aps' => $aps]));
}

// ── send to one token via HTTP/2 ───────────────────────────────────────────
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
            'apns-push-type: alert',
            'apns-priority: 10',
            'Content-Type: application/json',
        ],
    ]);

    $response   = curl_exec($ch);
    $httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['httpCode' => $httpCode, 'response' => json_decode($response, true)];
}

// ── logging ────────────────────────────────────────────────────────────────
$logFile = __DIR__ . '/pushNotifications.txt';

function logLine(string $line): void {
    global $logFile;
    file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
}

// ── send to all devices ────────────────────────────────────────────────────
$sandbox  = !empty($data['sandbox']) && $data['sandbox'] !== false;
$apnsHost = $sandbox ? APNS_HOST_SANDBOX : APNS_HOST_PROD;
$env      = $sandbox ? 'SANDBOX' : 'PRODUCTION';
$ts       = date('Y-m-d H:i:s');

$jwt     = apnsJWT();
$payload = buildPayload($title, $body, $subtitle, $badge, $sound, $custom);

$results        = ['sent' => 0, 'failed' => 0, 'removed' => 0, 'sandbox' => $sandbox, 'details' => []];
$tokensModified = false;

logLine("[$ts] -- SEND START -- env=$env | title=\"$title\" | body=\"$body\" | devices=" . count($tokens));

foreach ($tokens as $deviceUUID => $info) {
    $pushToken   = $info['pushToken'] ?? '';
    $deviceModel = $info['deviceModel'] ?? 'unknown';
    if (empty($pushToken)) continue;

    $result   = sendToToken($pushToken, $jwt, $payload, $apnsHost);
    $httpCode = $result['httpCode'];
    $reason   = $result['response']['reason'] ?? null;

    // 410 Unregistered or 400 BadDeviceToken -> token is dead, remove it
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
                logLine("[$ts]   INVALIDATED | device=$deviceModel | token=...{$pushToken} | reason=$reasonStr -> removed from tokens.json");
            } else {
                logLine("[$ts]   INVALIDATED | device=$deviceModel | token=...{$pushToken} | reason=$reasonStr (kept, REMOVE_DEAD_TOKENS=false)");
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
logLine("[$ts] -- SEND END   -- sent={$results['sent']} failed={$results['failed']} removed={$results['removed']}");
logLine('');

// persist cleaned-up tokens.json if any tokens were removed
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

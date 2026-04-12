<?php
/**
 * statusHistory.php
 *
 * GET  → returns MagicUI XML list of past status updates
 * POST → logs a new status entry to statusHistory.json
 *   Required: status
 *   Optional: emojis, isNewVideo, user
 */

$historyFile = __DIR__ . '/data/statusHistory.json';

function loadHistory(): array {
    global $historyFile;
    if (!file_exists($historyFile)) return [];
    return json_decode(file_get_contents($historyFile), true) ?: [];
}

function saveHistory(array $history): void {
    global $historyFile;
    file_put_contents($historyFile, json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

// ── POST: log a new status update ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $status = trim($input['status'] ?? '');
    if ($status === '') {
        http_response_code(400);
        echo json_encode(['error' => 'status is required']);
        exit;
    }

    $entry = [
        'timestamp'  => date('c'),
        'status'     => $status,
        'emojis'     => $input['emojis'] ?? '',
        'isNewVideo' => !empty($input['isNewVideo']),
        'user'       => $input['user'] ?? 'petres',
    ];

    $history = loadHistory();
    $history[] = $entry;
    saveHistory($history);

    echo json_encode(['success' => true, 'entry' => $entry]);
    exit;
}

// ── GET: render MagicUI XML ───────────────────────────────────────────────
header('Content-Type: text/xml; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$history = array_reverse(loadHistory());

function esc(string $s): string {
    return htmlspecialchars($s, ENT_XML1, 'UTF-8');
}

function relativeTime(string $iso): string {
    $ts   = strtotime($iso);
    $diff = time() - $ts;
    if ($diff < 60)    return 'just now';
    if ($diff < 3600)  return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j', $ts);
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<body>
    <list navigationTitle="Status History">
<?php if (empty($history)): ?>
        <vstack padding="40" frame="maxWidth:infinity">
            <image systemName="clock.arrow.circlepath" foregroundColor="gray" width="48" height="48"/>
            <text font="headline" foregroundColor="secondary">No status updates yet</text>
            <text font="subheadline" foregroundColor="secondary">Status changes will appear here</text>
        </vstack>
<?php else: ?>
<?php foreach ($history as $entry):
    $status     = esc($entry['status'] ?? '');
    $emojis     = esc($entry['emojis'] ?? '');
    $isNewVideo = !empty($entry['isNewVideo']);
    $user       = esc($entry['user'] ?? 'petres');
    $time       = isset($entry['timestamp']) ? esc(relativeTime($entry['timestamp'])) : '';
    $fullTime   = isset($entry['timestamp']) ? esc(date('M j, Y g:i A', strtotime($entry['timestamp']))) : '';
?>
        <vstack contentShapeRect="" onTapGesture="playSystemSound:1033\\setVariable:kocurStatus:<?= $status ?>\\setVariable:kocurEmojis:<?= $emojis ?>\\setString:selectedTab=Widgets" alignment="leading" spacing="4" padding="top:8;bottom:8" listRowBackground="js: '<?= $user ?>' == 'petres' ? '77FFFF40' : '0000FF40'">
            <hstack>
                <text font="headline"><?= $emojis ?></text>
                <spacer/>
                 
                <text font="caption" foregroundColor="secondary"><?= $time ?></text>
            </hstack>

            
               
            <text font="body"><?= $status ?></text>
                
            
            <hstack spacing="12">
<?php if ($isNewVideo): ?>
                <label systemName="play.rectangle.fill" foregroundColor="red">New Video</label>
<?php endif; ?>
                <text font="caption2" foregroundColor="secondary"><?= $user ?></text>
                <spacer/>
                <text font="caption2" foregroundColor="secondary"><?= $fullTime ?></text>
            </hstack>
        </vstack>
<?php endforeach; ?>
<?php endif; ?>
    </list>
</body>

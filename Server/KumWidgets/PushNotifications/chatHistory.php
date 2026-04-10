<?php
header('Content-Type: text/xml; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$chatHistoryFile = __DIR__ . '/chatHistory.json';
$history = [];
if (file_exists($chatHistoryFile)) {
    $history = json_decode(file_get_contents($chatHistoryFile), true) ?: [];
}

// Newest first
$history = array_reverse($history);

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

$showBadges     = !empty($_GET['showBadges']);
$showApnsStatus = !empty($_GET['showApnsStatus']);

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<body>
    <list navigationTitle="Chat History">
<?php if (empty($history)): ?>
        <vstack padding="40" frame="maxWidth:infinity">
            <image systemName="bubble.left.and.bubble.right" foregroundColor="gray" width="48" height="48"/>
            <text font="headline" foregroundColor="secondary">No notifications yet</text>
            <text font="subheadline" foregroundColor="secondary">Sent push notifications will appear here</text>
        </vstack>
<?php else: ?>
<?php foreach ($history as $i => $entry):
    $title    = esc($entry['title'] ?? '');
    $body     = esc($entry['body'] ?? '');
    $subtitle = isset($entry['subtitle']) ? esc($entry['subtitle']) : null;
    $sound    = esc($entry['sound'] ?? 'default');
    $badge    = $entry['badge'] ?? null;
    $sandbox  = !empty($entry['sandbox']);
    $sent     = $entry['sent'] ?? 0;
    $failed   = $entry['failed'] ?? 0;
    $time     = isset($entry['timestamp']) ? esc(relativeTime($entry['timestamp'])) : '';
    $fullTime = isset($entry['timestamp']) ? esc(date('M j, Y g:i A', strtotime($entry['timestamp']))) : '';
?>
        <vstack alignment="leading" spacing="4" padding="top:8;bottom:8">
            <hstack>
                <text font="headline"><?= $title ?></text>
                <spacer/>
                <text font="caption" foregroundColor="secondary"><?= $time ?></text>
            </hstack>
<?php if ($subtitle): ?>
            <text font="subheadline" foregroundColor="secondary"><?= $subtitle ?></text>
<?php endif; ?>
            <text font="body"><?= $body ?></text>
<?php if ($showApnsStatus): ?>
            <hstack spacing="12">
                <label systemName="checkmark.circle.fill" foregroundColor="green"><?= $sent ?> sent</label>
<?php if ($failed > 0): ?>
                <label systemName="xmark.circle.fill" foregroundColor="red"><?= $failed ?> failed</label>
<?php endif; ?>
<?php if ($sandbox): ?>
                <label systemName="hammer.fill" foregroundColor="orange">Sandbox</label>
<?php endif; ?>
            </hstack>
<?php endif; ?>
            <hstack spacing="12">
                <label systemName="speaker.wave.2" foregroundColor="secondary"><?= $sound ?></label>
<?php if ($showBadges && $badge !== null): ?>
                <label systemName="app.badge" foregroundColor="secondary">Badge: <?= (int)$badge ?></label>
<?php endif; ?>
                <spacer/>
                <text font="caption2" foregroundColor="secondary"><?= $fullTime ?></text>
            </hstack>
        </vstack>
<?php endforeach; ?>
<?php endif; ?>
    </list>

    
</body>

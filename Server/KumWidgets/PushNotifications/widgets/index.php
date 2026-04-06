<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Widget Push Dashboard</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #f2f2f7;
            color: #1c1c1e;
            padding: 32px 24px;
        }

        .header {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        h1 { font-size: 28px; font-weight: 700; }
        .subtitle { color: #6e6e73; font-size: 14px; margin-bottom: 32px; }
        h2 { font-size: 18px; font-weight: 600; margin-bottom: 16px; }

        .section {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 4px rgba(0,0,0,.08);
        }

        .stats { display: flex; gap: 16px; margin-bottom: 20px; }
        .stat { background: #f2f2f7; border-radius: 12px; padding: 12px 20px; text-align: center; }
        .stat-value { font-size: 28px; font-weight: 700; }
        .stat-label { font-size: 12px; color: #6e6e73; margin-top: 2px; }

        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        thead th {
            text-align: left; padding: 8px 12px;
            background: #f2f2f7; color: #6e6e73;
            font-size: 11px; font-weight: 600;
            text-transform: uppercase; letter-spacing: .5px;
        }
        thead th:first-child { border-radius: 8px 0 0 8px; }
        thead th:last-child  { border-radius: 0 8px 8px 0; }
        tbody tr { border-bottom: 1px solid #f2f2f7; }
        tbody tr:last-child { border-bottom: none; }
        tbody td { padding: 10px 12px; vertical-align: middle; }

        .token { font-family: monospace; font-size: 11px; color: #6e6e73; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .badge-os { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; background: #e8f4fd; color: #0071e3; }
        .refresh-btn { font-size: 13px; color: #0071e3; background: none; border: none; cursor: pointer; float: right; margin-top: 2px; }

        .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
        label { font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: .4px; }
        input, textarea { padding: 10px 14px; border: 1.5px solid #e5e5ea; border-radius: 10px; font-size: 15px; font-family: inherit; outline: none; transition: border-color .15s; }
        input:focus, textarea:focus { border-color: #0071e3; }
        textarea { resize: vertical; min-height: 72px; }

        .hint { font-size: 12px; color: #6e6e73; margin-top: 4px; }

        .send-btn {
            width: 100%; padding: 14px;
            background: #34c759; color: #fff;
            border: none; border-radius: 12px;
            font-size: 16px; font-weight: 600;
            cursor: pointer; transition: background .15s;
        }
        .send-btn:hover { background: #28a745; }
        .send-btn:disabled { background: #a8d5b5; cursor: not-allowed; }

        /* sandbox toggle */
        .sandbox-row { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; padding: 12px 16px; background: #f2f2f7; border-radius: 12px; }
        .sandbox-row span { font-size: 14px; font-weight: 600; color: #6e6e73; }
        .sandbox-row span.active { color: #ff9500; }
        .toggle { position: relative; display: inline-block; width: 44px; height: 26px; margin-left: auto; }
        .toggle input { display: none; }
        .toggle-slider { position: absolute; inset: 0; background: #d1d1d6; border-radius: 26px; cursor: pointer; transition: background .2s; }
        .toggle-slider::before { content: ''; position: absolute; width: 20px; height: 20px; left: 3px; top: 3px; background: #fff; border-radius: 50%; transition: transform .2s; box-shadow: 0 1px 3px rgba(0,0,0,.2); }
        .toggle input:checked + .toggle-slider { background: #ff9500; }
        .toggle input:checked + .toggle-slider::before { transform: translateX(18px); }

        /* result */
        #resultPanel { display: none; margin-top: 20px; }
        .result-summary { display: flex; gap: 12px; margin-bottom: 16px; }
        .result-stat { flex: 1; border-radius: 12px; padding: 14px; text-align: center; }
        .result-stat.sent   { background: #e8faf0; color: #1a7f4b; }
        .result-stat.failed { background: #fdecea; color: #c0392b; }
        .rs-value { font-size: 28px; font-weight: 700; }
        .rs-label { font-size: 12px; margin-top: 2px; font-weight: 600; }
        #resultDetail { width: 100%; border-collapse: collapse; font-size: 13px; }
        #resultDetail th { text-align: left; padding: 8px 12px; background: #f2f2f7; color: #6e6e73; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
        #resultDetail th:first-child { border-radius: 8px 0 0 8px; }
        #resultDetail th:last-child  { border-radius: 0 8px 8px 0; }
        #resultDetail td { padding: 10px 12px; border-bottom: 1px solid #f2f2f7; }
        #resultDetail tr:last-child td { border-bottom: none; }
        .status-ok  { color: #1a7f4b; font-weight: 600; }
        .status-err { color: #c0392b; font-weight: 600; }
        #rawError { margin-top: 12px; padding: 14px; background: #fdecea; color: #c0392b; border-radius: 12px; font-size: 13px; white-space: pre-wrap; font-family: monospace; }
    </style>
</head>
<body>

<?php
$tokensFile = __DIR__ . '/tokens.json';
$tokens = [];
if (file_exists($tokensFile)) {
    $tokens = json_decode(file_get_contents($tokensFile), true) ?: [];
}
$count = count($tokens);
?>

<div class="header">
    <h1>🔲 Widget Push Dashboard</h1>
</div>
<p class="subtitle">Trigger silent background pushes to reload widget timelines</p>

<!-- ── Devices ── -->
<div class="section">
    <h2>
        Registered Devices
        <button class="refresh-btn" onclick="location.reload()">↺ Refresh</button>
    </h2>

    <div class="stats">
        <div class="stat">
            <div class="stat-value"><?= $count ?></div>
            <div class="stat-label">Devices</div>
        </div>
    </div>

    <?php if ($count === 0): ?>
        <p style="color:#6e6e73;font-size:14px;">No devices registered yet.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Device</th>
                <th>OS</th>
                <th>App Version</th>
                <th>Push Token</th>
                <th>Registered</th>
                <th>Updated</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($tokens as $uuid => $info): ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($info['deviceModel'] ?? '—') ?></strong><br>
                    <span style="font-size:11px;color:#6e6e73;font-family:monospace"><?= htmlspecialchars(substr($uuid, 0, 18)) ?>…</span>
                </td>
                <td><span class="badge-os"><?= htmlspecialchars($info['osVersion'] ?? '—') ?></span></td>
                <td><?= htmlspecialchars($info['appVersion'] ?? '—') ?></td>
                <td><span class="token" title="<?= htmlspecialchars($info['pushToken'] ?? '') ?>"><?= htmlspecialchars($info['pushToken'] ?? '—') ?></span></td>
                <td style="color:#6e6e73;font-size:12px"><?= htmlspecialchars($info['createdAt'] ?? '—') ?></td>
                <td style="color:#6e6e73;font-size:12px"><?= htmlspecialchars($info['updatedAt'] ?? '—') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- ── Send Widget Update ── -->
<div class="section">
    <h2>Send Widget Update</h2>

    <div class="sandbox-row">
        <span>🏭 Production</span>
        <label class="toggle">
            <input type="checkbox" id="sandboxToggle">
            <span class="toggle-slider"></span>
        </label>
        <span id="sandboxLabel">Sandbox</span>
    </div>

    <form id="pushForm">
        <div class="form-group">
            <label>Custom Data (JSON)</label>
            <textarea name="customData" placeholder='{"xmlUrl": "https://example.com/data.xml"}'></textarea>
            <span class="hint">Optional — passed inside the push payload for the widget to read. Leave empty to send a plain reload signal.</span>
        </div>

        <button type="submit" class="send-btn" id="sendBtn">
            🔲 Reload Widgets on All <?= $count ?> Device<?= $count !== 1 ? 's' : '' ?>
        </button>
    </form>

    <div id="resultPanel">
        <div class="result-summary">
            <div class="result-stat sent">
                <div class="rs-value" id="rSent">0</div>
                <div class="rs-label">✅ Sent</div>
            </div>
            <div class="result-stat failed">
                <div class="rs-value" id="rFailed">0</div>
                <div class="rs-label">❌ Failed</div>
            </div>
        </div>
        <table id="resultDetail">
            <thead>
                <tr><th>Device</th><th>Status</th><th>APNs Response</th></tr>
            </thead>
            <tbody id="resultBody"></tbody>
        </table>
        <pre id="rawError" style="display:none"></pre>
    </div>
</div>

<script>
document.getElementById('sandboxToggle').addEventListener('change', function () {
    document.getElementById('sandboxLabel').classList.toggle('active', this.checked);
});

document.getElementById('pushForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn  = document.getElementById('sendBtn');
    const form = e.target;

    let customData = {};
    const raw = form.customData.value.trim();
    if (raw) {
        try { customData = JSON.parse(raw); }
        catch { alert('Custom Data is not valid JSON'); return; }
    }

    const payload = {
        sandbox: document.getElementById('sandboxToggle').checked,
        data:    customData,
    };

    btn.disabled    = true;
    btn.textContent = 'Sending…';
    document.getElementById('resultPanel').style.display = 'none';

    try {
        const res  = await fetch('sendWidgetUpdate.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload),
        });

        const text = await res.text();
        let json;
        try { json = JSON.parse(text); } catch { json = null; }

        const panel    = document.getElementById('resultPanel');
        const rawError = document.getElementById('rawError');
        panel.style.display = 'block';

        if (json && json.status === 'ok') {
            document.getElementById('rSent').textContent   = json.sent   ?? 0;
            document.getElementById('rFailed').textContent = (json.failed ?? 0) + ((json.removed ?? 0) > 0 ? ` (${json.removed} removed)` : '');

            const tbody = document.getElementById('resultBody');
            tbody.innerHTML = '';
            (json.details || []).forEach(d => {
                const ok     = d.httpCode === 200;
                const reason = d.response?.reason ?? (ok ? '—' : 'Unknown error');
                const removed = d.removed ? ' 🗑 <em style="font-size:11px;color:#6e6e73">removed</em>' : '';
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${d.deviceModel || '—'}</strong></td>
                    <td class="${ok ? 'status-ok' : 'status-err'}">${ok ? '✅ OK' : '❌ Failed'}${removed}</td>
                    <td style="color:#6e6e73;font-size:12px;font-family:monospace">${reason}</td>`;
                tbody.appendChild(tr);
            });
            rawError.style.display = 'none';
        } else {
            document.getElementById('rSent').textContent   = '—';
            document.getElementById('rFailed').textContent = '—';
            document.getElementById('resultBody').innerHTML = '';
            rawError.style.display = 'block';
            rawError.textContent   = text;
        }
    } catch (err) {
        const panel = document.getElementById('resultPanel');
        panel.style.display = 'block';
        document.getElementById('rawError').style.display = 'block';
        document.getElementById('rawError').textContent   = 'Request failed: ' + err.message;
        document.getElementById('rSent').textContent      = '—';
        document.getElementById('rFailed').textContent    = '—';
    } finally {
        btn.disabled    = false;
        btn.textContent = '🔲 Reload Widgets on All <?= $count ?> Device<?= $count !== 1 ? 's' : '' ?>';
    }
});
</script>

</body>
</html>

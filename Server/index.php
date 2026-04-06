<?php $simpleMode = !empty($_GET['simpleMode']); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KOCUR NEWS</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #f2f2f7;
            color: #1c1c1e;
            padding: 32px 24px;
        }

        h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #6e6e73;
            font-size: 14px;
            margin-bottom: 32px;
        }

        h2 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
        }

        /* ── devices table ── */
        .section {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 4px rgba(0,0,0,.08);
        }

        .stats {
            display: flex;
            gap: 16px;
            margin-bottom: 20px;
        }

        .stat {
            background: #f2f2f7;
            border-radius: 12px;
            padding: 12px 20px;
            text-align: center;
        }

        .stat-value { font-size: 28px; font-weight: 700; }
        .stat-label { font-size: 12px; color: #6e6e73; margin-top: 2px; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        thead th {
            text-align: left;
            padding: 8px 12px;
            background: #f2f2f7;
            color: #6e6e73;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        thead th:first-child { border-radius: 8px 0 0 8px; }
        thead th:last-child  { border-radius: 0 8px 8px 0; }

        tbody tr { border-bottom: 1px solid #f2f2f7; }
        tbody tr:last-child { border-bottom: none; }

        tbody td {
            padding: 10px 12px;
            vertical-align: middle;
        }

        .token {
            font-family: monospace;
            font-size: 11px;
            color: #6e6e73;
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            background: #e8f4fd;
            color: #0071e3;
        }

        .refresh-btn {
            font-size: 13px;
            color: #0071e3;
            background: none;
            border: none;
            cursor: pointer;
            float: right;
            margin-top: 2px;
        }

        /* ── send form ── */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full { grid-column: 1 / -1; }

        label {
            font-size: 12px;
            font-weight: 600;
            color: #6e6e73;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        input, textarea, select {
            padding: 10px 14px;
            border: 1.5px solid #e5e5ea;
            border-radius: 10px;
            font-size: 15px;
            font-family: inherit;
            background: #fff;
            transition: border-color .15s;
            outline: none;
        }

        input:focus, textarea:focus, select:focus {
            border-color: #0071e3;
        }

        textarea { resize: vertical; min-height: 72px; }

        .send-btn {
            margin-top: 8px;
            width: 100%;
            padding: 14px;
            background: #0071e3;
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background .15s;
        }

        .send-btn:hover { background: #0062c4; }
        .send-btn:disabled { background: #b0c8e8; cursor: not-allowed; }

        .fill-btn {
            margin-top: 8px;
            width: 100%;
            padding: 11px;
            background: none;
            color: #0071e3;
            border: 1.5px solid #0071e3;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background .15s, color .15s;
        }

        .fill-btn:hover { background: #0071e3; color: #fff; }

        /* ── sandbox toggle ── */
        .sandbox-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
            padding: 12px 16px;
            background: #f2f2f7;
            border-radius: 12px;
        }

        .sandbox-row span {
            font-size: 14px;
            font-weight: 600;
            color: #6e6e73;
        }

        .sandbox-row span.active { color: #ff9500; }

        .toggle {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 26px;
            margin-left: auto;
        }

        .toggle input { display: none; }

        .toggle-slider {
            position: absolute;
            inset: 0;
            background: #d1d1d6;
            border-radius: 26px;
            cursor: pointer;
            transition: background .2s;
        }

        .toggle-slider::before {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            left: 3px;
            top: 3px;
            background: #fff;
            border-radius: 50%;
            transition: transform .2s;
            box-shadow: 0 1px 3px rgba(0,0,0,.2);
        }

        .toggle input:checked + .toggle-slider { background: #ff9500; }
    .toggle input:checked + .toggle-slider::before { transform: translateX(18px); }

        /* ── simple mode overrides ── */
        body.simple { padding: 16px; background: #fff; }
        body.simple h1 { font-size: 20px; margin-bottom: 4px; }
        body.simple .subtitle { margin-bottom: 16px; }
        body.simple .section { border-radius: 0; box-shadow: none; padding: 0; background: none; margin-bottom: 0; }
        body.simple .form-grid { grid-template-columns: 1fr; }
        body.simple .form-group.full { grid-column: 1; }

        /* ── result ── */
        #result {
            display: none;
            margin-top: 16px;
            padding: 16px;
            border-radius: 12px;
            font-size: 14px;
            white-space: pre-wrap;
            font-family: monospace;
        }

        #result.success { background: #e8faf0; color: #1a7f4b; }
        #result.error   { background: #fdecea; color: #c0392b; }

        /* ── result panel ── */
        #resultPanel { display: none; margin-top: 20px; }

        .result-summary {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
        }

        .result-stat {
            flex: 1;
            border-radius: 12px;
            padding: 14px;
            text-align: center;
        }

        .result-stat.sent  { background: #e8faf0; color: #1a7f4b; }
        .result-stat.failed { background: #fdecea; color: #c0392b; }
        .result-stat .rs-value { font-size: 28px; font-weight: 700; }
        .result-stat .rs-label { font-size: 12px; margin-top: 2px; font-weight: 600; }

        #resultDetail { width: 100%; border-collapse: collapse; font-size: 13px; }
        #resultDetail th {
            text-align: left; padding: 8px 12px;
            background: #f2f2f7; color: #6e6e73;
            font-size: 11px; font-weight: 600;
            text-transform: uppercase; letter-spacing: .5px;
        }
        #resultDetail th:first-child { border-radius: 8px 0 0 8px; }
        #resultDetail th:last-child  { border-radius: 0 8px 8px 0; }
        #resultDetail td { padding: 10px 12px; border-bottom: 1px solid #f2f2f7; }
        #resultDetail tr:last-child td { border-bottom: none; }

        .status-ok  { color: #1a7f4b; font-weight: 600; }
        .status-err { color: #c0392b; font-weight: 600; }

        #rawError {
            margin-top: 12px; padding: 14px;
            background: #fdecea; color: #c0392b;
            border-radius: 12px; font-size: 13px;
            white-space: pre-wrap; font-family: monospace;
        }
    </style>
</head>
<body<?= $simpleMode ? ' class="simple"' : '' ?>>

<div style="display:flex;align-items:baseline;justify-content:space-between;margin-bottom:8px;">
    <h1>📲 KOCUR NEWS</h1>
    <button type="button" id="fillBtn" style="font-size:12px;padding:5px 12px;border-radius:20px;border:1.5px solid #0071e3;background:none;color:#0071e3;cursor:pointer;font-weight:600;">✏️ Fill 🐈‍⬛ Data</button>
</div>
<p class="subtitle"><?= $simpleMode ? 'Send a push to all registered devices' : 'Registered devices and notification sender' ?></p>

<?php
$tokensFile = __DIR__ . '/tokens.json';
$tokens = [];
if (file_exists($tokensFile)) {
    $tokens = json_decode(file_get_contents($tokensFile), true) ?: [];
}
$count = count($tokens);
?>

<?php if (!$simpleMode): ?>
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
                <td><span class="badge"><?= htmlspecialchars($info['osVersion'] ?? '—') ?></span></td>
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
<?php endif; ?>

<!-- ── Send Push ── -->
<div class="section">
    <?php if (!$simpleMode): ?><h2>Send Push Notification</h2><?php endif; ?>

    <?php if (!$simpleMode): ?>
    <div class="sandbox-row">
        <span>🏭 Production</span>
        <label class="toggle">
            <input type="checkbox" id="sandboxToggle">
            <span class="toggle-slider"></span>
        </label>
        <span id="sandboxLabel">Sandbox</span>
    </div>
    <?php endif; ?>

    <form id="pushForm">
        <div class="form-grid">
            <div class="form-group">
                <label>Title *</label>
                <input type="text" name="title" placeholder="Hello!" required>
            </div>
            <div class="form-group">
                <label>Subtitle</label>
                <input type="text" name="subtitle" placeholder="Optional">
            </div>
            <div class="form-group full">
                <label>Body *</label>
                <textarea name="body" placeholder="Notification message…" required></textarea>
            </div>
            <?php if (!$simpleMode): ?>
            <div class="form-group">
                <label>Badge</label>
                <input type="number" name="badge" placeholder="0" min="0">
            </div>
            <div class="form-group">
                <label>Sound</label>
                <input type="text" name="sound" placeholder="default" value="default">
            </div>
            <?php endif; ?>
        </div>

        <button type="submit" class="send-btn" id="sendBtn">
            Send to All <?= $count ?> Device<?= $count !== 1 ? 's' : '' ?>
        </button>
    </form>

    <pre id="result"></pre>

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
                <tr>
                    <th>Device</th>
                    <th>Status</th>
                    <th>APNs Response</th>
                </tr>
            </thead>
            <tbody id="resultBody"></tbody>
        </table>
        <pre id="rawError" style="display:none"></pre>
    </div>
</div>

<script>
const sandboxToggle = document.getElementById('sandboxToggle');
if (sandboxToggle) {
    sandboxToggle.addEventListener('change', function () {
        document.getElementById('sandboxLabel').classList.toggle('active', this.checked);
    });
}

// ── persist & restore form values ─────────────────────────────────────────
const FIELDS = ['title', 'subtitle', 'body', 'badge', 'sound'];
const STORE  = 'kocurPushForm';

function saveForm() {
    const form = document.getElementById('pushForm');
    const data = {};
    FIELDS.forEach(f => data[f] = form[f].value);
    localStorage.setItem(STORE, JSON.stringify(data));
}

function restoreForm() {
    const raw = localStorage.getItem(STORE);
    if (!raw) return;
    try {
        const data = JSON.parse(raw);
        const form = document.getElementById('pushForm');
        FIELDS.forEach(f => { if (data[f] !== undefined) form[f].value = data[f]; });
    } catch {}
}

restoreForm();

document.getElementById('pushForm').querySelectorAll('input, textarea').forEach(el => {
    el.addEventListener('input', saveForm);
});

document.getElementById('fillBtn').addEventListener('click', () => {
    const form = document.getElementById('pushForm');
    form.title.value    = 'KOCUR NEWS';
    form.subtitle.value = 'This is a subtitle';
    form.body.value     = 'Push notifications are working correctly!';
    if (form.badge) { form.badge.value = '1'; }
    if (form.sound) { form.sound.value = 'default'; }
    saveForm();
});

document.getElementById('pushForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn    = document.getElementById('sendBtn');
    const result = document.getElementById('result');
    const form   = e.target;

    const payload = {
        title:    form.title.value,
        body:     form.body.value,
        subtitle: form.subtitle.value || undefined,
        sound:    form.sound?.value || 'default',
        sandbox:  document.getElementById('sandboxToggle')?.checked ?? false,
    };
    const badge = parseInt(form.badge?.value);
    if (!isNaN(badge)) payload.badge = badge;

    btn.disabled    = true;
    btn.textContent = 'Sending…';
    result.style.display = 'none';

    try {
        const res  = await fetch('sendPushNotification.php', {
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
            document.getElementById('rSent').textContent   = json.sent    ?? 0;
            document.getElementById('rFailed').textContent = json.failed  ?? 0;
            if ((json.removed ?? 0) > 0) {
                document.getElementById('rFailed').textContent =
                    `${json.failed} (${json.removed} removed)`;
            }

            const tbody = document.getElementById('resultBody');
            tbody.innerHTML = '';
            (json.details || []).forEach(d => {
                const ok      = d.httpCode === 200;
                const reason  = d.response?.reason ?? (ok ? '—' : 'Unknown error');
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
        const rawError = document.getElementById('rawError');
        panel.style.display    = 'block';
        rawError.style.display = 'block';
        rawError.textContent   = 'Request failed: ' + err.message;
        document.getElementById('rSent').textContent   = '—';
        document.getElementById('rFailed').textContent = '—';
        document.getElementById('resultBody').innerHTML = '';
    } finally {
        btn.disabled    = false;
        btn.textContent = 'Send to All <?= $count ?> Device<?= $count !== 1 ? 's' : '' ?>';
    }
});
</script>

</body>
</html>

<?php
$allowedUsers = ['petres', 'lukes'];
$user = in_array($_GET['user'] ?? '', $allowedUsers) ? $_GET['user'] : 'petres';

$dataDir = __DIR__ . '/data';
function loadVarsForUser(string $user, string $dataDir): array {
    $f = "$dataDir/variables_{$user}.json";
    $v = file_exists($f) ? json_decode(file_get_contents($f), true) : null;
    $v = is_array($v) ? $v : [];
    ksort($v);
    return $v;
}
$vars = loadVarsForUser($user, $dataDir);
$allVarsJson = [];
foreach ($allowedUsers as $u) {
    $allVarsJson[$u] = loadVarsForUser($u, $dataDir);
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Variables</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { background: #fff; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #1c1c1e;
            padding: 16px;
            overscroll-behavior: none;
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        h1 { font-size: 20px; font-weight: 700; }
        .user-switch { display: flex; gap: 6px; }
        .user-switch button {
            padding: 6px 14px;
            border-radius: 20px;
            border: 1.5px solid #e5e5ea;
            background: none;
            color: #6e6e73;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
        }
        .user-switch button.active {
            background: #1c1c1e;
            border-color: #1c1c1e;
            color: #fff;
        }
        .hint {
            font-size: 12px;
            color: #6e6e73;
            margin-bottom: 16px;
            line-height: 1.5;
        }
        .hint code {
            font-family: 'SF Mono', monospace;
            background: #f2f2f7;
            padding: 1px 5px;
            border-radius: 4px;
            color: #5e5ce6;
        }
        .var-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px; }
        .var-row {
            background: #f2f2f7;
            border-radius: 12px;
            padding: 12px 14px;
        }
        .var-key {
            font-family: 'SF Mono', monospace;
            font-size: 12px;
            color: #5e5ce6;
            font-weight: 600;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .var-del {
            background: none;
            border: none;
            color: #aeaeb2;
            font-size: 16px;
            cursor: pointer;
            padding: 0 2px;
            line-height: 1;
        }
        .var-del:active { color: #ff3b30; }
        .var-val {
            width: 100%;
            border: 1.5px solid #e5e5ea;
            border-radius: 8px;
            padding: 8px 10px;
            font-size: 15px;
            font-family: inherit;
            background: #fff;
            color: #1c1c1e;
            outline: none;
        }
        .var-val:focus { border-color: #5e5ce6; }
        .empty-msg {
            color: #aeaeb2;
            font-size: 14px;
            text-align: center;
            padding: 24px 0;
        }
        .add-section {
            background: #f2f2f7;
            border-radius: 12px;
            padding: 14px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .add-section label {
            font-size: 11px;
            font-weight: 600;
            color: #6e6e73;
            text-transform: uppercase;
            letter-spacing: .4px;
        }
        .add-section input {
            width: 100%;
            border: 1.5px solid #e5e5ea;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 15px;
            font-family: inherit;
            background: #fff;
            color: #1c1c1e;
            outline: none;
        }
        .add-section input:focus { border-color: #5e5ce6; }
        #newKeyInput { font-family: 'SF Mono', monospace; text-transform: uppercase; }
        .btn-add {
            width: 100%;
            padding: 12px;
            background: #5e5ce6;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            margin-top: 4px;
        }
        .btn-add:active { opacity: .8; }
        .toast {
            position: fixed;
            bottom: 32px;
            left: 50%;
            transform: translateX(-50%);
            background: #1c1c1e;
            color: #fff;
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            opacity: 0;
            transition: opacity .2s;
            pointer-events: none;
            white-space: nowrap;
        }
        .toast.show { opacity: 1; }
    </style>
</head>
<body>

<div class="header">
    <h1>⚙️ Variables</h1>
    <div class="user-switch" id="userSwitch">
        <?php foreach ($allowedUsers as $u): ?>
        <button data-user="<?= $u ?>"<?= $u === $user ? ' class="active"' : '' ?>><?= ucfirst($u) ?></button>
        <?php endforeach; ?>
    </div>
</div>

<p class="hint">Use <code>{{KEY}}</code> in widget XML — replaced with the stored value when served.</p>

<div class="var-list" id="varList">
<?php if (empty($vars)): ?>
    <div class="empty-msg">No variables yet. Add one below.</div>
<?php else: foreach ($vars as $key => $val): ?>
    <div class="var-row" data-key="<?= htmlspecialchars($key) ?>">
        <div class="var-key">
            <span><?= htmlspecialchars($key) ?></span>
            <button class="var-del" data-key="<?= htmlspecialchars($key) ?>" title="Delete">✕</button>
        </div>
        <input class="var-val" data-key="<?= htmlspecialchars($key) ?>" type="text"
               value="<?= htmlspecialchars($val) ?>" autocomplete="off">
    </div>
<?php endforeach; endif; ?>
</div>

<div class="add-section">
    <label>New Variable</label>
    <input id="newKeyInput" type="text" placeholder="KEY" maxlength="32" autocomplete="off" spellcheck="false" inputmode="text">
    <input id="newValInput" type="text" placeholder="value" autocomplete="off">
    <button class="btn-add" id="addBtn">＋ Add Variable</button>
</div>

<div class="toast" id="toast"></div>

<script>
// All vars for all users embedded at page-render time — no fetch needed ever.
const ALL_VARS = <?= json_encode($allVarsJson, JSON_UNESCAPED_UNICODE) ?>;
let currentUser = '<?= $user ?>';

function toast(msg) {
    const el = document.getElementById('toast');
    el.textContent = msg;
    el.classList.add('show');
    clearTimeout(el._t);
    el._t = setTimeout(() => el.classList.remove('show'), 2000);
}

async function api(body) {
    const res = await fetch('api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
    });
    return res.json();
}

function makeRow(key, val) {
    const ek = key.replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;');
    const ev = val.replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;');
    const div = document.createElement('div');
    div.className = 'var-row';
    div.dataset.key = key;
    div.innerHTML = `<div class="var-key"><span>${ek}</span><button class="var-del" data-key="${ek}" title="Delete">✕</button></div>` +
        `<input class="var-val" data-key="${ek}" type="text" value="${ev}" autocomplete="off">`;
    attachRowEvents(div);
    return div;
}

function attachRowEvents(row) {
    const input = row.querySelector('.var-val');
    let timer;
    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(async () => {
            ALL_VARS[currentUser][input.dataset.key] = input.value;
            await api({ action: 'set_variable', user: currentUser, key: input.dataset.key, value: input.value });
            toast('Saved');
        }, 800);
    });

    row.querySelector('.var-del').addEventListener('click', async () => {
        const key = row.dataset.key;
        if (!confirm(`Delete ${key}?`)) return;
        await api({ action: 'delete_variable', user: currentUser, key });
        delete ALL_VARS[currentUser][key];
        row.remove();
        const list = document.getElementById('varList');
        if (!list.querySelector('.var-row')) {
            list.innerHTML = '<div class="empty-msg">No variables yet. Add one below.</div>';
        }
        toast('Deleted');
    });
}

function renderList(vars) {
    const list = document.getElementById('varList');
    list.innerHTML = '';
    const keys = Object.keys(vars).sort();
    if (keys.length === 0) {
        list.innerHTML = '<div class="empty-msg">No variables yet. Add one below.</div>';
        return;
    }
    keys.forEach(k => list.appendChild(makeRow(k, vars[k])));
}

// Attach events to server-rendered rows (already in DOM, no flash)
document.querySelectorAll('#varList .var-row').forEach(attachRowEvents);

// User switch — pure DOM swap, no navigation
document.getElementById('userSwitch').addEventListener('click', e => {
    const btn = e.target.closest('button[data-user]');
    if (!btn) return;
    currentUser = btn.dataset.user;
    document.querySelectorAll('#userSwitch button').forEach(b => b.classList.toggle('active', b === btn));
    renderList(ALL_VARS[currentUser] || {});
});

// Add new variable — inject row directly into DOM
document.getElementById('addBtn').addEventListener('click', async () => {
    const key = document.getElementById('newKeyInput').value.trim().toUpperCase().replace(/[^A-Z0-9_]/g, '_').replace(/^_+|_+$/g, '');
    const val = document.getElementById('newValInput').value;
    if (!key) { document.getElementById('newKeyInput').focus(); return; }

    await api({ action: 'set_variable', user: currentUser, key, value: val });
    ALL_VARS[currentUser][key] = val;

    document.getElementById('newKeyInput').value = '';
    document.getElementById('newValInput').value = '';

    // Insert sorted
    const list = document.getElementById('varList');
    const empty = list.querySelector('.empty-msg');
    if (empty) empty.remove();

    const rows = [...list.querySelectorAll('.var-row')];
    const newRow = makeRow(key, val);
    const after = rows.find(r => r.dataset.key > key);
    if (after) list.insertBefore(newRow, after);
    else list.appendChild(newRow);

    toast('Added');
});

document.getElementById('newValInput').addEventListener('keydown', e => {
    if (e.key === 'Enter') document.getElementById('addBtn').click();
});
</script>
</body>
</html>


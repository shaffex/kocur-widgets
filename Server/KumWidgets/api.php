<?php
header('Content-Type: application/json');

$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

$variablesFile   = $dataDir . '/variables.json'; // legacy, unused
$allowedUsers    = ['petres', 'lukes'];
$allowedFamilies = ['systemSmall', 'systemMedium', 'systemLarge'];

function getDataFile(string $user, string $family): string {
    global $dataDir;
    return "$dataDir/{$user}_{$family}.xml";
}

function getVariablesFile(string $user): string {
    global $dataDir;
    return "$dataDir/variables_{$user}.json";
}

function getVariables(string $user): array {
    $file = getVariablesFile($user);
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?: [];
}

function saveVariables(string $user, array $vars): void {
    ksort($vars);
    file_put_contents(getVariablesFile($user), json_encode($vars, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action === 'get_variables') {
        $user = $_GET['user'] ?? '';
        if (!in_array($user, $allowedUsers)) {
            echo json_encode(['success' => false, 'error' => 'Invalid user']);
            exit;
        }
        echo json_encode(['success' => true, 'variables' => getVariables($user)]);
        exit;
    }

    if ($action === 'list_templates') {
        $familySuffix = [
            'systemSmall'  => 'small',
            'systemMedium' => 'medium',
            'systemLarge'  => 'large',
        ];
        $family = $_GET['family'] ?? 'systemSmall';
        $suffix = $familySuffix[$family] ?? 'small';

        $templatesDir = __DIR__ . '/Templates';
        $names = [];
        if (is_dir($templatesDir)) {
            foreach (glob("$templatesDir/*_{$suffix}.xml") as $file) {
                $base = basename($file, "_{$suffix}.xml");
                $names[] = $base;
            }
            sort($names);
        }
        echo json_encode(['success' => true, 'templates' => $names]);
        exit;
    }

    if ($action === 'load') {
        $user   = $_GET['user']   ?? '';
        $family = $_GET['family'] ?? '';

        if (!in_array($user, $allowedUsers) || !in_array($family, $allowedFamilies)) {
            echo json_encode(['success' => false, 'error' => 'Invalid user or family']);
            exit;
        }

        $file = getDataFile($user, $family);
        echo json_encode(['success' => true, 'content' => file_exists($file) ? file_get_contents($file) : '']);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action === 'update_variables') {
        // Called from iOS app via updateVariablesToServer action.
        // Expects: { "action": "update_variables", "user": "petres", "variables": { "KEY": "value", ... } }
        $user     = $input['user'] ?? '';
        $incoming = $input['variables'] ?? null;
        if (!in_array($user, $allowedUsers)) {
            echo json_encode(['success' => false, 'error' => 'Invalid user']);
            exit;
        }
        if (!is_array($incoming)) {
            echo json_encode(['success' => false, 'error' => 'variables must be an object']);
            exit;
        }
        $vars = getVariables($user);
        foreach ($incoming as $rawKey => $value) {
            $key = strtoupper(preg_replace('/[^A-Za-z0-9_]/', '_', trim($rawKey)));
            $key = trim($key, '_');
            if ($key === '') continue;
            $vars[$key] = (string)$value;
        }
        saveVariables($user, $vars);
        echo json_encode(['success' => true, 'variables' => $vars]);
        exit;
    }

    if ($action === 'set_variable') {
        $user  = $input['user']  ?? '';
        $key   = strtoupper(preg_replace('/[^A-Za-z0-9_]/', '_', trim($input['key'] ?? '')));
        $key   = trim($key, '_');
        $value = $input['value'] ?? '';
        if (!in_array($user, $allowedUsers) || $key === '') {
            echo json_encode(['success' => false, 'error' => 'Invalid user or key']);
            exit;
        }
        $vars = getVariables($user);
        $vars[$key] = $value;
        saveVariables($user, $vars);
        echo json_encode(['success' => true, 'key' => $key]);
        exit;
    }

    if ($action === 'delete_variable') {
        $user = $input['user'] ?? '';
        $key  = strtoupper(preg_replace('/[^A-Za-z0-9_]/', '_', trim($input['key'] ?? '')));
        if (!in_array($user, $allowedUsers)) {
            echo json_encode(['success' => false, 'error' => 'Invalid user']);
            exit;
        }
        $vars = getVariables($user);
        unset($vars[$key]);
        saveVariables($user, $vars);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'save_template') {
        $familySuffix = [
            'systemSmall'  => 'small',
            'systemMedium' => 'medium',
            'systemLarge'  => 'large',
        ];
        $family  = $input['family']  ?? '';
        $name    = $input['name']    ?? '';
        $content = $input['content'] ?? '';

        $suffix = $familySuffix[$family] ?? null;
        if (!$suffix || !in_array($family, $allowedFamilies)) {
            echo json_encode(['success' => false, 'error' => 'Invalid family']);
            exit;
        }

        // Sanitise name: lowercase, only letters/digits/hyphens
        $name = strtolower(trim($name));
        $name = preg_replace('/[^a-z0-9\-]/', '-', $name);
        $name = preg_replace('/-+/', '-', trim($name, '-'));
        if ($name === '') {
            echo json_encode(['success' => false, 'error' => 'Invalid template name']);
            exit;
        }

        $file = __DIR__ . "/Templates/{$name}_{$suffix}.xml";
        if (file_put_contents($file, $content) !== false) {
            echo json_encode(['success' => true, 'name' => $name]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to write template file']);
        }
        exit;
    }

    $user    = $input['user']    ?? '';
    $family  = $input['family']  ?? '';
    $content = $input['content'] ?? '';

    if ($action !== 'save') {
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
        exit;
    }

    if (!in_array($user, $allowedUsers) || !in_array($family, $allowedFamilies)) {
        echo json_encode(['success' => false, 'error' => 'Invalid user or family']);
        exit;
    }

    $file = getDataFile($user, $family);
    if (file_put_contents($file, $content) !== false) {
        // Also regenerate the user's PHP serving file
        regenerateUserFile($user);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to write file']);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid request']);

function regenerateUserFile(string $user): void {
    global $dataDir, $allowedFamilies;

    $phpFile = __DIR__ . "/{$user}.php";

    // Read all family XMLs for this user
    $families = [];
    foreach ($allowedFamilies as $family) {
        $file = getDataFile($user, $family);
        if (file_exists($file)) {
            $families[$family] = file_get_contents($file);
        }
    }

    // Generate PHP file that serves by ?family= param, substituting {{VARIABLES}} at runtime
    $code = "<?php\n";
    $code .= "header('Content-Type: text/xml; charset=utf-8');\n";
    $code .= "\$family = \$_GET['family'] ?? 'systemSmall';\n\n";
    $code .= "// Load per-user variables ({{KEY}} → value)\n";
    $code .= "\$_vf   = __DIR__ . '/data/variables_{$user}.json';\n";
    $code .= "\$_vars = file_exists(\$_vf) ? (json_decode(file_get_contents(\$_vf), true) ?: []) : [];\n\n";
    $code .= "\$widgets = [\n";
    foreach ($families as $family => $xml) {
        $escaped = var_export($xml, true);
        $code .= "    '$family' => $escaped,\n";
    }
    $code .= "];\n\n";
    $code .= "if (isset(\$widgets[\$family])) {\n";
    $code .= "    \$_xml = \$widgets[\$family];\n";
    $code .= "    foreach (\$_vars as \$_k => \$_v) {\n";
    $code .= "        \$_xml = str_replace('{{' . \$_k . '}}', htmlspecialchars(\$_v, ENT_XML1, 'UTF-8'), \$_xml);\n";
    $code .= "    }\n";
    $code .= "    echo \$_xml;\n";
    $code .= "} else {\n";
    $code .= "    http_response_code(404);\n";
    $code .= "    echo '<error>Unknown family: ' . htmlspecialchars(\$family) . '</error>';\n";
    $code .= "}\n";

    file_put_contents($phpFile, $code);
}

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
    $fp = fopen($file, 'r');
    if (!$fp) return [];
    flock($fp, LOCK_SH);
    $contents = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return json_decode($contents, true) ?: [];
}

/**
 * Atomically read-modify-write the per-user variables file.
 * $mutator receives the current array by reference and mutates it.
 * Returns the resulting array.
 */
function mutateVariables(string $user, callable $mutator): array {
    $file = getVariablesFile($user);
    $fp = fopen($file, 'c+');
    if (!$fp) {
        throw new RuntimeException("Cannot open variables file for $user");
    }
    flock($fp, LOCK_EX);

    $contents = stream_get_contents($fp);
    $vars = json_decode($contents, true);
    if (!is_array($vars)) $vars = [];

    $mutator($vars);
    ksort($vars);

    $encoded = json_encode($vars, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, $encoded);
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return $vars;
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
        $vars = mutateVariables($user, function (array &$vars) use ($incoming) {
            foreach ($incoming as $rawKey => $value) {
                $key = strtoupper(preg_replace('/[^A-Za-z0-9_]/', '_', trim($rawKey)));
                $key = trim($key, '_');
                if ($key === '') continue;
                $vars[$key] = (string)$value;
            }
        });
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
        mutateVariables($user, function (array &$vars) use ($key, $value) {
            $vars[$key] = (string)$value;
        });
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
        mutateVariables($user, function (array &$vars) use ($key) {
            unset($vars[$key]);
        });
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
    global $allowedFamilies;

    $phpFile = __DIR__ . "/{$user}.php";

    // Generate PHP file that reads the latest XML from disk at request time.
    $code = "<?php\n";
    $code .= "header('Content-Type: text/xml; charset=utf-8');\n";
    $code .= "header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');\n";
    $code .= "header('Pragma: no-cache');\n";
    $code .= "header('Expires: 0');\n";
    $code .= "\$family = \$_GET['family'] ?? 'systemSmall';\n\n";
    $code .= "// Load per-user variables ({{KEY}} → value)\n";
    $code .= "\$_vf   = __DIR__ . '/data/variables_{$user}.json';\n";
    $code .= "\$_vars = file_exists(\$_vf) ? (json_decode(file_get_contents(\$_vf), true) ?: []) : [];\n\n";
    $code .= "\$allowedFamilies = " . var_export($allowedFamilies, true) . ";\n";
    $code .= "if (in_array(\$family, \$allowedFamilies, true)) {\n";
    $code .= "    \$_file = __DIR__ . '/data/{$user}_' . \$family . '.xml';\n";
    $code .= "    if (!file_exists(\$_file)) {\n";
    $code .= "        http_response_code(404);\n";
    $code .= "        echo '<error>Missing widget file for family: ' . htmlspecialchars(\$family) . '</error>';\n";
    $code .= "        exit;\n";
    $code .= "    }\n";
    $code .= "    \$_xml = file_get_contents(\$_file);\n";
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

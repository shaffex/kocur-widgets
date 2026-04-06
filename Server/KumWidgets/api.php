<?php
header('Content-Type: application/json');

$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

$allowedUsers = ['petres', 'lukes'];
$allowedFamilies = ['systemSmall', 'systemMedium', 'systemLarge'];

function getDataFile(string $user, string $family): string {
    global $dataDir;
    return "$dataDir/{$user}_{$family}.xml";
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'list_templates') {
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

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'load') {
    $user = $_GET['user'] ?? '';
    $family = $_GET['family'] ?? '';

    if (!in_array($user, $allowedUsers) || !in_array($family, $allowedFamilies)) {
        echo json_encode(['success' => false, 'error' => 'Invalid user or family']);
        exit;
    }

    $file = getDataFile($user, $family);
    if (file_exists($file)) {
        echo json_encode(['success' => true, 'content' => file_get_contents($file)]);
    } else {
        echo json_encode(['success' => true, 'content' => '']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

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

    // Generate PHP file that serves by ?family= param
    $code = "<?php\n";
    $code .= "header('Content-Type: text/xml; charset=utf-8');\n";
    $code .= "\$family = \$_GET['family'] ?? 'systemSmall';\n\n";
    $code .= "\$widgets = [\n";
    foreach ($families as $family => $xml) {
        $escaped = var_export($xml, true);
        $code .= "    '$family' => $escaped,\n";
    }
    $code .= "];\n\n";
    $code .= "if (isset(\$widgets[\$family])) {\n";
    $code .= "    echo \$widgets[\$family];\n";
    $code .= "} else {\n";
    $code .= "    http_response_code(404);\n";
    $code .= "    echo '<error>Unknown family: ' . htmlspecialchars(\$family) . '</error>';\n";
    $code .= "}\n";

    file_put_contents($phpFile, $code);
}

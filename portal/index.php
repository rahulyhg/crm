<?php


include "../bootstrap.php";

$app = new \Espo\Core\Application();
if (!$app->isInstalled()) {
    exit;
}

$url = !empty($_SERVER['REDIRECT_URL']) ? $_SERVER['REDIRECT_URL'] : $_SERVER['REQUEST_URI'];

$a = explode('?', $url);
if (substr($a[0], -1) !== '/') {
    $url = $a[0] . '/';
    if (count($a) > 1) {
        $url .= '?' . $a[1];
    }
    header("Location: " . $url);
    exit();
}

$portalId = explode('/', $url)[count(explode('/', $_SERVER['SCRIPT_NAME'])) - 1];
if ($portalId) {
    $app->setBasePath('../../');
} else {
    $app->setBasePath('../');
}

if (!empty($_GET['entryPoint'])) {
    $app->runEntryPoint($_GET['entryPoint']);
    exit;
}

$app->runEntryPoint('portal');

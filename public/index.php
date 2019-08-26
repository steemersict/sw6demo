<?php declare(strict_types=1);

use Doctrine\DBAL\Exception\ConnectionException;
use PackageVersions\Versions;
use Shopware\Core\Framework\Routing\RequestTransformerInterface;
use Symfony\Component\Debug\Debug;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

$classLoader = require __DIR__ . '/../vendor/autoload.php';

if (!file_exists(dirname(__DIR__) . '/install.lock')) {
    $basePath = 'recovery/install';
    $baseURL = str_replace(basename(__FILE__), '', $_SERVER['SCRIPT_NAME']);
    $baseURL = rtrim($baseURL, '/');
    $installerURL = $baseURL . '/' . $basePath;
    if (strpos($_SERVER['REQUEST_URI'], $basePath) === false) {
        header('Location: ' . $installerURL);
        exit;
    }
}

if (is_file(dirname(__DIR__) . '/files/update/update.json') || is_dir(dirname(__DIR__) . '/update-assets')) {
    header('Content-type: text/html; charset=utf-8', true, 503);
    header('Status: 503 Service Temporarily Unavailable');
    header('Retry-After: 1200');
    if (file_exists(__DIR__ . '/maintenance.html')) {
        echo file_get_contents(__DIR__ . '/maintenance.html');
    } else {
        echo file_get_contents(__DIR__ . '/recovery/update/maintenance.html');
    }

    return;
}

// The check is to ensure we don't use .env if APP_ENV is defined
if (!isset($_SERVER['APP_ENV']) && !isset($_ENV['APP_ENV'])) {
    if (!class_exists(Dotenv::class)) {
        throw new \RuntimeException('APP_ENV environment variable is not defined. You need to define environment variables for configuration or add "symfony/dotenv" as a Composer dependency to load variables from a .env file.');
    }
    (new Dotenv())->load(__DIR__ . '/../.env');
}

$env = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? 'dev';
$debug = (bool) ($_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? ($env !== 'prod'));

if ($debug) {
    umask(0000);

    Debug::enable();
}

if ($trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? $_ENV['TRUSTED_PROXIES'] ?? false) {
    Request::setTrustedProxies(explode(',', $trustedProxies), Request::HEADER_X_FORWARDED_ALL ^ Request::HEADER_X_FORWARDED_HOST);
}

if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? $_ENV['TRUSTED_HOSTS'] ?? false) {
    Request::setTrustedHosts(explode(',', $trustedHosts));
}

$request = Request::createFromGlobals();

try {
    $shopwareVersion = Versions::getVersion('shopware/core');
    $kernel = new \Shopware\Production\Kernel($env, $debug, $classLoader, $shopwareVersion);
    $kernel->boot();

    // resolves seo urls and detects storefront sales channels
    $request = $kernel->getContainer()
        ->get(RequestTransformerInterface::class)
        ->transform($request);
    $response = $kernel->handle($request);
} catch (ConnectionException $e) {
    throw new RuntimeException('Could not connect to database.');
}

$response->send();
$kernel->terminate($request, $response);

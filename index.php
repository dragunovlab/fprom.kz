<?php

// Security headers
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

// WWW redirect (SEO: avoid duplicate content)
if (isset($_SERVER['HTTP_HOST']) && preg_match('/^www\./i', $_SERVER['HTTP_HOST'])) {
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: https://fprom.kz' . $_SERVER['REQUEST_URI']);
    exit;
}

// Category URL redirects (fixed duplicates)
if (file_exists(__DIR__ . '/cat_redirect_map.php')) {
    include __DIR__ . '/cat_redirect_map.php';
    if (isset($cat_redirects) && !empty($_SERVER['REQUEST_URI'])) {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = trim($path, '/');
        $segments = explode('/', $path);
        $lastSegment = end($segments);

        $target = null;
        if (isset($cat_redirects[$path])) {
            $target = $cat_redirects[$path];
        } elseif (isset($cat_redirects[$lastSegment])) {
            $target = $cat_redirects[$lastSegment];
        }

        if ($target !== null) {
            $query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
            $qs = $query ? '?' . $query : '';
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: https://fprom.kz/' . $target . $qs);
            exit;
        }
    }
}

$startTime = microtime(true);

use Okay\Core\Router;
use Okay\Core\Request;
use Okay\Core\Response;
use Okay\Core\Config;
use Okay\Core\Modules\Modules;
use Psr\Log\LoggerInterface;

ini_set('display_errors', 'off');

if (!empty($_SERVER['HTTP_USER_AGENT'])) {
    session_name(md5($_SERVER['HTTP_USER_AGENT']));
}
session_start();

require_once('vendor/autoload.php');

$DI = include 'Okay/Core/config/container.php';

/**
 * ���䨣���㥬 � ��������� �ࢨ� ��ࠬ���� ��⥬�
 *
 * @var Config $config
 */
$config = $DI->get(Config::class);

try {

    /** @var Router $router */
    $router = $DI->get(Router::class);
    
    // ����४� � ����������� ᫥襩
    $uri = str_replace(Request::getDomainWithProtocol(), '', Request::getCurrentUrl());
    if (($destination = preg_replace('~//+~', '/', $uri, -1, $countReplace)) && $countReplace > 0) {
        Response::redirectTo($destination, 301);
    }
    $router->resolveCurrentLanguage();

    if ($config->get('debug_mode') == true) {
        ini_set('display_errors', 'on');
        error_reporting(E_ALL);
    }
    
    /** @var Response $response */
    $response = $DI->get(Response::class);
    
    /** @var Request $request */
    $request = $DI->get(Request::class);
    // ��⠭���� �६� ��砫� �믮������ �ਯ�
    $request->setStartTime($startTime);

    if (isset($_GET['logout'])) {
        unset($_SESSION['admin']);
        unset($_SESSION['last_version_data']);
        setcookie('admin_login', '', time()-100, '/');
        
        $response->redirectTo($request->getRootUrl());
    }
    
    /** @var Modules $modules */
    $modules = $DI->get(Modules::class);
    $modules->startEnabledModules();
    
    $router->run();

    if ($response->getContentType() == RESPONSE_HTML) {
        // �⫠��筠� ���ଠ��
        print "<!--\r\n";
        $timeEnd = microtime(true);
        $execTime = $timeEnd - $startTime;

        if (function_exists('memory_get_peak_usage')) {
            print "memory peak usage: " . memory_get_peak_usage() . " bytes\r\n";
        }
        print "page generation time: " . $execTime . " seconds\r\n";
        print "-->";
    }
    
} catch (\Exception $e) {
    
    /** @var LoggerInterface $logger */
    $logger = $DI->get(LoggerInterface::class);
    
    $message = $e->getMessage() . PHP_EOL . $e->getTraceAsString();
    if ($config->get('debug_mode') == true) {
        print $message;
    } else {
        $logger->critical($message);
        header($_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error');
    }
}

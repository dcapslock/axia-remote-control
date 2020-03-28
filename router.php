<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/studio/studio.php';

$valid_passwords = array ("studio" => "1800Alive");
$valid_users = array_keys($valid_passwords);

$user = $_SERVER['PHP_AUTH_USER'];
$pass = $_SERVER['PHP_AUTH_PW'];

$validated = (in_array($user, $valid_users)) && ($pass == $valid_passwords[$user]);

if (!$validated) {
  header('WWW-Authenticate: Basic realm="Studio"');
  header('HTTP/1.0 401 Unauthorized');
  die ("Not authorized");
}

$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
    // Console Get/Put
    $r->addRoute('GET', '/console/{console}/{action}', 'Studio/getConsole');
    $r->addRoute('PUT', '/console/{console}/{action}/{data}', 'Studio/setConsole');

    // Fader Get/Put
    $r->addRoute('GET', '/console/{console}/fader/{fader:[1-8]}/{action}[/{data}]', 'Studio/getFader');
    $r->addRoute('PUT', '/console/{console}/fader/{fader:[1-8]}/{action}/{data}', 'Studio/setFader');

    // VMIX Get/Put
    $r->addRoute('GET', '/console/{console}/vmix/{vmix:\d+}/channel/{chnum:\d+}/{action}', 'Studio/getVmix');
    $r->addRoute('PUT', '/console/{console}/vmix/{vmix:\d+}/channel/{chnum:\d+}/{action}/{data}', 'Studio/setVmix');
});

// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found", true, 404);
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        header($_SERVER["SERVER_PROTOCOL"]." 405 Method Not Allowed", true, 405);
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];        
        list($class, $method) = explode("/", $handler, 2);
        $instance = new $class($vars);
        $instance->$method();
        break;
}
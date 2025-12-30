<?php

/**
 * FRONT CONTROLLER
 * PHP Version 7.1.9
 *
 */
// Controller File

// Twig Calling
require_once dirname(__DIR__) . "/vendor/autoload.php";

spl_autoload_register(function ($class) {
    $root = dirname(__DIR__);
    $file = $root . "/" . str_replace('\\', '/', $class) . ".php";
    if (is_readable($file)) {
        require $root . "/" . str_replace('\\', '/', $class) . ".php";
    }
});

/**
 * Error Handling
 */
error_reporting(E_ALL);
set_error_handler('Core\Error::errorHandler');
set_exception_handler('Core\Error::exceptionHandler');

// Handle CORS preflight OPTIONS requests early
if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Max-Age: 3600");
    http_response_code(200);
    exit();
}

/* X-FRAME-OPTIONS
This http header helps avoiding clickjacking attacks. Browser support is as follow: IE 8+, Chrome 4.1+, Firefox 3.6.9+, Opera 10.5+, Safari 4+. Posible values are: */
header("X-Frame-Options: sameorigin");
/* X-XSS-PROTECTION
Use this header to enable browser built-in XSS Filter. It prevent cross-site scripting attacks. X-XSS-Protection header is supported by IE 8+, Opera, Chrome, and Safari. Available directives: */
header("X-XSS-Protection: 1; mode=block");
/* X-CONTENT-TYPE-OPTIONS
This http header is supported by IE and Chrome, and prevents attacks based on MIME-type mismatch. The only possible value is nosniff. If your server returns X-Content-Type-Options: nosniff in the response, the browser will refuse to load the styles and scripts in case they have an incorrect MIME-type. The list with available MIME-types for styles and scripts is as follow: */
header("X-Content-Type-Options: nosniff");
/* CONTENT-SECURITY-POLICY
This header could affect your website in many ways, so be careful when using it. The configuration below allows loading scripts, XMLHttpRequest (AJAX), images and styles from same domain and nothing else. Browser support: Edge 12+, Firefox 4+, Chrome 14+, Safari 6+, Opera 15+ */
// header("Content-Security-Policy: default-src 'none'; script-src 'self'; connect-src 'self'; img-src 'self'; style-src 'self';");

// header('Content-Type: image/gif');


$router = new Core\Router();
// API ROUTES------------------
// Version 1 API Routes
$router->add('v1', ['controller' => 'Main', 'action' => 'index', 'namespace' => "v1"]);
$router->add('v1/', ['controller' => 'Main', 'action' => 'index', 'namespace' => "v1"]);
$router->add('v1/{controller}', ['action' => 'index', 'namespace' => "v1"]);
$router->add('v1/{controller}/{id:\d+}', ['action' => 'index', 'namespace' => "v1"]);
$router->add('v1/{controller}/{action}', ['namespace' => "v1"]);
$router->add('v1/{controller}/{action}/{id:\d+}', ['namespace' => "v1"]);



// Admin section
$router->add('admin', ['controller' => 'Login', 'action' => 'index', 'namespace' => "Admin"]);
$router->add('admin/', ['controller' => 'Login', 'action' => 'index', 'namespace' => "Admin"]);
// $router->add('admin/{controller}/{id:\d+}', ['action' => 'index', 'namespace' => "Admin"]);
$router->add('admin/{controller}', ['action' => 'index', 'namespace' => "Admin"]);
$router->add('admin/{controller}/{action}', ['namespace' => "Admin"]);
$router->add('admin/{controller}/{action}/{id:\d+}', ['namespace' => "Admin"]);




// Ajax Calls
$router->add('ajaxcalls/{controller}/{action}', ['namespace' => "Admin\Ajaxcalls"]);
$router->add('mainajaxcalls/{controller}/{action}', ['namespace' => "Ajaxcalls"]);


// payment section
$router->add('payment/{action}', ['controller' => 'Payment']);



// Main section
$router->add('', ['controller' => 'Main', 'action' => 'index',]);
$router->add('/', ['controller' => 'Main', 'action' => 'index',]);
$router->add('{action}', ['controller' => 'Main']);
$router->add('{action}/{id:[\w\-/]+}', ['controller' => 'Main']);
$router->add('{controller}', ['action' => 'index']);
$router->add('{controller}/{action}');
$router->add('{controller}/{action}/{id:\d+}');


// example routes section
// $router->add('students/{action}', ['controller' => 'Students']);
// $router->add('alumni/{action}', ['controller' => 'Alumni']);
// $router->add('videos/all', ['controller' => 'Videos', 'action' => 'index']);
// $router->add('news/all', ['controller' => 'News', 'action' => 'index']);
// $router->add('news/{id:[\w\-/]+}', ['controller' => 'News', 'action' => 'view']);
// $router->add('gallery/{id:[\w\-/]+}', ['controller' => 'Gallery', 'action' => 'index']);
// $router->add('events/{action}', ['controller' => 'Events']);


// $router->add('{category:[\w\-/]+}', ['controller' => 'Category', 'action' => 'index']);






// main website routes
// $router->add('', ['controller' => 'Login', 'action' => 'index']);
// $router->add('product/{id:[\w\-]+}', ['controller' => 'Product', 'action' => 'index']);


// $router->add('{controller}', ['action' => 'index', 'page'=>'static']);

// URL from the Query String
$url = $_SERVER["QUERY_STRING"];

$router->dispatch($url);

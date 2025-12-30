<?php
/**
 * Router
 * PHP Version 7.1.9
 */
namespace Core;

use App\Controllers\Staticpageload;

class Router
{
    /**
     * Associative Array AKA Routing Table
     * @var array
     */
    protected $routes = [];
    protected $params = [];

    /**
     * Add route to routing table
     * @param string $route Routing URL (Query String)
     * @param array @params Parametes with query string (Controller and action etc)
     */

    public function add($route, $params = [])
    {
        // Converting route to regular expression
        // Escaping the forward slash
        $route = preg_replace('/\//', '\\/', $route);

        // Convert Variables Ex: {controller}
        $route = preg_replace('/\{([a-z\-]+)\}/', '(?P<\1>[a-z\-]+)', $route);

        // Convert variables with custom regular expression
        $route = preg_replace('/\{([a-z\-]+):([^\}]+)\}/', '(?P<\1>\2)', $route);

        // Add start and end delimiters for regex
        $route = "/^" . $route . "$/i";

        $this->routes[$route] = $params;
    }

    /**
     * Match Function - The query string fetched from the user request will be matched from the routing table
     * @param string $url The query string or URL as per the user request.
     *
     * @return boolean true if match found
     */

    public function match($url)
    {
        // match the url with regex
        // $reg_exp = "/^(?P<controller>[a-z-]+)\/(?P<action>[a-z-]+)$/";
        foreach ($this->routes as $route => $params) {
            if (preg_match($route, $url, $matches)) {
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }
                $this->params = $params;
                return true;
            }
        }
        return false;
    }

    /**
     * Dispatch the route, creating the controller and running th action method
     * @* @param string $url Url fetched from the user as query string
     */
    public function dispatch($url)
    {
        $url = $this->removeQueryStringVariables($url);

        if ($this->match($url)) {
            $controller = $this->params['controller'];
            $controller = $this->convertToStudlyCaps($controller); // convert to StudlyCaps
            // $controller = "App\Controllers\\$controller";
            $controller = $this->getNamespace() . $controller;
            if (class_exists($controller)) {
                $controller_object = new $controller($this->params);

                $action = $this->params['action'];
                $action = $this->convertToCamelCase($action);

                // if(is_callable([$controller_object, $action])){
                if (preg_match('/action$/i', $action) == 0) {
                    $controller_object->$action();
                } else {
                    throw new \Exception("Method $action in controller $controller cannot be called directly - remove the Action suffix to call this method", 404);
                }
            } else {
                throw new \Exception("No Controller class $controller found", 404);
            }
        } else {
            throw new \Exception("No route found for $url", 404);
        }
    }


    public function getRoutes()
    {
        return $this->routes;
    }

    public function getParams()
    {
        return $this->params;
    }

    /**
     * Convert the string with hyphens to StudlyCaps,
     * e.g. post-authors => PostAuthors
     *
     * @param string $string The string to convert
     *
     * @return string
     */
    protected function convertToStudlyCaps($string)
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));
    }

    /**
     * Convert the string with hyphens to camelCase,
     * e.g. add-new => addNew
     *
     * @param string $string The string to convert
     *
     * @return string
     */
    protected function convertToCamelCase($string)
    {
        return lcfirst($this->convertToStudlyCaps($string));
    }

    protected function removeQueryStringVariables($url)
    {
        if ($url != '') {
            $parts = explode("&", $url, 2);

            if (strpos($parts[0], "=") === false) {
                $url = $parts[0];
            } else {
                $url = '';
            }
        }
        return $url;
    }

    protected function getNamespace()
    {
        $namespace = 'App\Controllers\\';
        if (array_key_exists('namespace', $this->params)) {
            $namespace = $namespace . $this->params['namespace'] . '\\';
        }
        return $namespace;
    }

}
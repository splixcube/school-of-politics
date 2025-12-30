<?php

namespace Core;

use App\Config;

abstract class MainLoginController extends \Core\Middleware
{
    protected $route_params = [];

    /**
     * Contructor class
     */
    public function __construct($route_params)
    {
        $this->route_params = $route_params;
        parent::__construct();
    }

    public function __call($name, $args)
    {
        $method = $name . "Action";
        if (method_exists($this, $method)) {
            if ($this->before() !== false) {
                call_user_func_array([$this, $method], $args);
                $this->after();
            } else {
                header("Location: " . $_ENV['rootfolder'] . "");
            }
        } else {
            throw new \Exception("Method $method not found in controller " . get_class($this));
        }
    }

    protected function before()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['logged_in'])) {
            return false;
        } else {
            return true;
        }
    }

    protected function after()
    {
        return true;
    }
}

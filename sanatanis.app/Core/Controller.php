<?php

namespace Core;

use Core\Model;

/**
 * Base Controller (where we integrate all other controllers in our mvC framework)
 * PHP version 7.1.9
 */
// abstract class, as we are not going to make methods directly to this class
abstract class Controller extends \Core\Middleware
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
                header("Location: " . $_ENV['adminfolder'] . "login");
            }
        } else {
            throw new \Exception("Method $method not found in controller " . get_class($this));
        }
    }

    protected function before()
    {



        if (isset($_SESSION['adminname'])) {
            return true;
        } else {
            return false;
        }
    }

    protected function after()
    {
        return true;
    }
}

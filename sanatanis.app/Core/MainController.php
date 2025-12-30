<?php

namespace Core;

use PDO;
use \PHPMailer\PHPMailer\PHPMailer;
use \Core\Csrf;

/**
 * Base Controller (where we integrate all other controllers in our mvC framework)
 */
// abstract class, as we are not going to make methods directly to this class
abstract class MainController extends \Core\Middleware
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
                header("Location: /");
                // header("Location: " . $_ENV['rootfolder'] . "login");
            }
        } else {
            throw new \Exception("Method $method not found in controller " . get_class($this));
        }
    }

    protected function before()
    {
        return true;
        // if (isset($_SESSION['logged_in'])) {
        //     return true;
        // } else {
        //     return false;
        // }
    }

    protected function after()
    {
        return true;
    }
}

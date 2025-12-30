<?php

namespace App\Controllers\Admin;

use \Core\View;
use \App\Config;
use \Core\QB;


class Logout extends \Core\Controller
{

    /**
     * Show index page for main site
     */
    protected function indexAction()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        session_unset();
        header("Location: " . $_ENV['adminfolder']);
    }
}

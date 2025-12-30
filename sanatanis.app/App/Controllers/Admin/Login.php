<?php

namespace App\Controllers\Admin;

use \Core\View;
use \Core\QB;


class Login extends \Core\LoginController
{

    /**
     * Show index page for main site
     */

    protected function indexAction()
    {


        $err = [];
        if (isset($_POST['submit'])) {



            // VALIDATE DATA
            $validateData = [
                "aname" => [['required'], "Username", NULL],
                "apassword" => [['required'], "Password", NULL],
            ];


            $vres = $this->validatedata($validateData);

            $checkResponse = QB::table('admins')
                ->select('*')
                ->where([["aname", "=", $_POST['aname']]])
                ->get('fetchObject');


            if ($checkResponse->success == 1) {
                if (empty($vres->validate_error)) {
                    $i_username = $_POST['aname'];
                    $i_password = $_POST['apassword'];
                    if (password_verify($i_password, $checkResponse->data->apassword)) {
                        $_SESSION['adminname'] = $checkResponse->data->aname;
                        $_SESSION['logged_in'] = true;
                        header("Location:" . $_ENV['adminfolder'] . "dashboard");
                    } else {
                        $err[] = "Invalid Username or Password!";
                    }
                }
            } else {
                $err[] = "User Not Found";
            }
            $err = array_merge($vres->validate_error, $err);

            // var_dump($err);
            // exit();

        }


        View::renderTemplate('Admin/login.html', ['err' => $err]);
    }
}

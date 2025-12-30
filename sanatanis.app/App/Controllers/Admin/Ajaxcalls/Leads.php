<?php

namespace App\Controllers\Admin\Ajaxcalls;

use \Core\View;
use \Core\QB;


class Member extends \Core\Controller
{

    /**
     * Show index page for main site
     */
    public function deleteAction()
    {

        $targetLead = $_POST['id'];

        if (!isset($_POST['id']) && !is_numeric($_POST['id'])) {
            return false;
        } else {
            $deleteResponse = QB::table('kids')
                ->where([["kid", "=", $targetLead]])
                ->delete();
            $success = $deleteResponse->success;
            if ($success) {
                // return true;
                echo "success";
            } else {
                // return false;
                echo "error";
            }
        }
    }
}

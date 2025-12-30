<?php
 namespace App\Controllers\Admin\Ajaxcalls;
 use \Core\View;
 use \Core\QB;


class Filter extends \Core\Controller {
    protected function statusAction(){
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['from'], $_SESSION['to']);
        $_SESSION['statusfilter'] = $_POST['status'];
        echo "done";
    }
    
    protected function deletestatusAction(){
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['statusfilter']);
        echo "done";
    }
    
    protected function dateAction(){
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['statusfilter']);
        $_SESSION['from'] = $_POST['from'];
        $_SESSION['to'] = $_POST['to'];
        echo "done";
    }
    protected function deletedateAction(){
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['from'], $_SESSION['to']);
        echo "done";
    }
   
}
?>
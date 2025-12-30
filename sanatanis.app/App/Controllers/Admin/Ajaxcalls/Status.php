<?php
 namespace App\Controllers\Admin\Ajaxcalls;
 use \Core\View;
 use \Core\QB;


class Status extends \Core\Controller {
    protected function updateAction(){
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $update = QB::table('form_data')->where([["fid", "=", $_POST['id']]])->update(["flead_status"=>$_POST['status']]);
        if($update->success == 1){
            echo "ho gayaaaa";
        }else{
            echo ":(((((";
        }
    }
    
    protected function followupdateAction(){
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $update = QB::table('form_data')->where([["fid", "=", $_POST['id']]])->update(["ffollowup"=>$_POST['followup']]);
        if($update->success == 1){
            echo "ho gayaaaa";
        }else{
            echo ":(((((";
        }
    }
    
    protected function commentupdateAction(){
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if(empty($_POST['comment'])){
            $comment = NULL;
        }else{
            $comment = trim($_POST['comment']);
        }
        $update = QB::table('form_data')->where([["fid", "=", $_POST['id']]])->update(["fcomments"=>$comment]);
        if($update->success == 1){
            echo "ho gayaaaa";
        }else{
            echo ":(((((";
        }
    }
    protected function commentdataAction(){
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $data = QB::table('form_data')->select('fcomments')->where([["fid", "=", $_POST['id']]])->get('fetchObject');
        if($data->success == 1){
            if(is_null($data->data->fcomments)){
                echo "-";
            }else{
                echo $data->data->fcomments;
            }
        }else{
            echo ":(((((";
        }
    }
}
?>
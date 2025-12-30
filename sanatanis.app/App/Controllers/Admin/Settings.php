<?php
 namespace App\Controllers\Admin;
 use \Core\View;
 use \Core\QB;

/**
 * Post Controller
 * PHP version 7.1.9
 */
class Settings extends \Core\Controller {
    
    /**
     * Show index page for main site
     */
    protected function indexAction(){

        $err = array();
        $success = 0;

        if(isset($_POST['fsubmit'])){

            if(empty(trim($_POST['callphone']))){
                $err[] = "Please enter call phone number";
            }else{
                $callphone = trim($_POST['callphone']);
            }
            
            if(empty(trim($_POST['waphone']))){
                $err[] = "Please enter whatsapp phone number";
            }else{
                $waphone = trim($_POST['waphone']);
            }
            
            if(empty(trim($_POST['email']))){
                $err[] = "Please enter email id";
            }else{
                $email = trim($_POST['email']);
            }
            
            if(empty(trim($_POST['metatitle']))){
                $err[] = "Please enter meta title";
            }else{
                $metatitle = trim($_POST['metatitle']);
            }
            
            if(empty(trim($_POST['metadesc']))){
                $err[] = "Please enter meta description";
            }else{
                $metadesc = trim($_POST['metadesc']);
            }
            
            if(empty(trim($_POST['locaddress']))){
                $err[] = "Please enter full address";
            }else{
                $locaddress = trim($_POST['locaddress']);
            }
            
            if(empty(trim($_POST['facebook']))){
                $facebook = NULL;
            }else{
                $facebook = trim($_POST['facebook']);
            }
            
            if(empty(trim($_POST['twitter']))){
                $twitter = NULL;
            }else{
                $twitter = trim($_POST['twitter']);
            }
            
            if(empty(trim($_POST['instagram']))){
                $instagram = NULL;
            }else{
                $instagram = trim($_POST['instagram']);
            }
            
            if(empty(trim($_POST['youtube']))){
                $youtube = NULL;
            }else{
                $youtube = trim($_POST['youtube']);
            }
            
            if(empty(trim($_POST['linkedin']))){
                $linkedin = NULL;
            }else{
                $linkedin = trim($_POST['linkedin']);
            }

            if(empty($_POST['headerscripts'])){
                $headerscripts = NULL;
            }else{
                $headerscripts = trim($_POST['headerscripts']);
            }

            if(empty($_POST['footerscripts'])){
                $footerscripts = NULL;
            }else{
                $footerscripts = trim($_POST['footerscripts']);
            }

            if(empty($err)){
                $update = QB::table("pgc_settings")
                ->where([["sid", "=", 1]])
                ->update(['sphonecall' => $callphone, "sphonewa" => $waphone, "semail" => $email, "stitle" => $metatitle, "sdesc" => $metadesc, "saddress" => $locaddress, "sfacebook" => $facebook, "stwitter" => $twitter, "sinsta" => $instagram, "syoutube" => $youtube, "slinkedin" => $linkedin, "sscripts" => $headerscripts, "sscripts_footer" => $footerscripts]);
                if($update->success == 1){
                    $success = 1;
                }else{
                    $err[] = "Technical Error! Please try again.";
                }
            }            
        }

        // $settings_data = QB::table("pgc_settings")
        // ->select('*')
        // ->get('fetchAll', 'PDO::FETCH_ASSOC');
        // View::renderTemplate("Admin/settings.html", ['data' => $settings_data->data[0], 'error' => $err, 'success' => $success]);
        View::renderTemplate("Admin/settings.html", ['error' => $err, 'success' => $success]);
    }
}
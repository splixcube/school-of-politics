<?php

namespace Core;

use \Dotenv\Dotenv;

/**
 * View Core
 */

class View
{
    /**
     * Render method
     * @param string $view The view file
     * @return void
     */
    // public static function render($view, $args = []){
    //     extract($args, EXTR_SKIP);

    //     $file = "../App/View/$view";
    //     if(is_readable($file)){
    //         ob_start();
    //         require $file;
    //     }else{
    //         echo "$file not found";
    //     }
    // }

    public static function renderTemplate($template, $args = [])
    {
        $twig = NULL;
        if ($twig === NULL) {
            $loader = new \Twig\Loader\FilesystemLoader(dirname(__DIR__) . '/App/View/');
            $twig = new \Twig\Environment(
                $loader,
                array(
                    'debug' => true,
                    // ...
                )
            );

            // Adding Global Session
            if (session_status() != PHP_SESSION_NONE) {
                $twig->addGlobal('session', $_SESSION);
            } else {
                session_start();
                $twig->addGlobal('session', $_SESSION);
            }

            // Adding Global Get
            $twig->addGlobal('get', $_GET);


            // Enabling ENV Usage
            $dotenv = Dotenv::createImmutable(dirname(__DIR__));
            $dotenv->safeLoad();
            $twig->addGlobal('env', $_ENV);
            $twig->addExtension(new \Twig\Extension\DebugExtension());



            // Returns Decoded JSON string
            $decodeFunction = new \Twig\TwigFunction('json_decode', function ($json) {
                return json_decode($json, true);
            });
            $twig->addFunction($decodeFunction);



        }
        echo $twig->render($template, $args);
    }
}
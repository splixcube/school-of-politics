<?php

namespace Core;

use Exception as BaseException;
use \Whoops\Handler\PrettyPageHandler;
use \Whoops\Handler\JsonResponseHandler;
use \Whoops\Run;

/**
 * Error and exception handler
 *
 */
class Error
{

    /**
     * Error handler. Convert all errors to Exceptions by throwing an ErrorException.
     *
     * @param int $level  Error level
     * @param string $message  Error message
     * @param string $file  Filename the error was raised in
     * @param int $line  Line number in the file
     *
     * @return void
     */
    public static function errorHandler($level, $message, $file, $line)
    {
        if (error_reporting() !== 0) {  // to keep the @ operator working
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
    }

    /**
     * Exception handler.
     *
     * @param Exception $exception  The exception
     *
     * @return void
     */
    public static function exceptionHandler($exception)
    {
        $code = $exception->getCode();
        if ($code != 404) {
            $code = 500;
        }
        // http_response_code($code);
        $dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__));
        $dotenv->safeLoad();

        $run = new Run();
        if (filter_var($_ENV['ERRORS_IN_JSON'], FILTER_VALIDATE_BOOL)) {
            $handler = new JsonResponseHandler();
        } else {
            $handler = new PrettyPageHandler();
            $handler->setApplicationPaths([__FILE__]);

            $handler->addDataTableCallback('Details', function (\Whoops\Exception\Inspector $inspector) {
                $data = array();
                $exception = $inspector->getException();
                // if ($exception instanceof SomeSpecificException) {
                //     $data['Important exception data'] = $exception->getSomeSpecificData();
                // }
                $data['Exception class'] = get_class($exception);
                $data['Exception code'] = $exception->getCode();
                return $data;
            });
        }

        $run->pushHandler($handler);

        // Example: tag all frames inside a function with their function name
        $run->pushHandler(function ($exception, $inspector, $run) {

            $inspector->getFrames()->map(function ($frame) {

                if ($function = $frame->getFunction()) {
                    $frame->addComment("This frame is within function '$function'", 'cpt-obvious');
                }

                return $frame;
            });
        });

        $run->register();

        $run->writeToOutput(filter_var($_ENV['SHOW_ERRORS'], FILTER_VALIDATE_BOOL));
        if (filter_var($_ENV['SHOW_ERRORS'], FILTER_VALIDATE_BOOL) == false) {
            $log = dirname(__DIR__) . '/logs/' . date('Y-m-d') . '.txt';
            ini_set('error_log', $log);
            $message = "Uncaught exception: '" . get_class($exception) . "'";
            $message .= " with message '" . $exception->getMessage() . "'";
            $message .= "\nStack trace: " . $exception->getTraceAsString();
            $message .= "\nThrown in '" . $exception->getFile() . "' on line " . $exception->getLine();

            error_log($message);
            if ($code == 404) {
                View::renderTemplate($code . ".html");
            } elseif ($code == 500) {
                View::renderTemplate($code . ".html");
            } else {
                echo "<h1>An error occurred</h1>";
            }
        }

        $html = $run->handleException($exception);


    }
}
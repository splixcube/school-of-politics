<?php

namespace Core;

use \Dotenv\Dotenv;

class ApiResponse extends \Core\Middleware
{

    static $statusCode;
    // public $data = [];
    // public $params = [];
    // public $successMsg;
    // public $error;
    public $responseArr = [];

    // List of all status codes and their corresponding msg
    protected $errorMessages = [
        // INFORMATIONAL CODES
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',
        // SUCCESS CODES
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        // REDIRECTION CODES
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        // Deprecated to 306 => '(Unused)'
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        // CLIENT ERROR
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        444 => 'Connection Closed Without Response',
        451 => 'Unavailable For Legal Reasons',
        // SERVER ERROR
        499 => 'Client Closed Request',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
        599 => 'Network Connect Timeout Error',
    ];

    // Set Status Code
    public static function statusCode($code = 200)
    {
        // Set status code
        if (!is_null($code) && !empty($code) && is_numeric($code) && $code > 100 && $code < 599)
            static::$statusCode = $code;
        return new static;
    }

    // Add Error Msg To The Response
    public function withError($customMsg = NULL)
    {
        // Set source of error msg
        (!is_null($customMsg) && !empty($customMsg) && !is_numeric($customMsg))
            ? $error = $customMsg
            : $error = $this->errorMessages[static::$statusCode];

        if (!empty($error) && !is_null($error)) {
            $this->responseArr['errors'] = is_array($error) ? $error : [$error];

        }

        return $this;
    }

    // Accepts Main Data Payload For The Response
    public function body($data, $successMsg)
    {
        // Set Success Message
        (!is_null($successMsg) && !empty($successMsg))
            ? $success = $successMsg
            : $success = true;

        $this->responseArr['success_msg'] = $success;

        if (!empty($data)) {
            $this->responseArr['data'] = $data;
        }


        return $this;
    }


    // To Return Extra Data Such As Api Key Or Tokens In Response
    public function params($extraParam)
    {
        // Set Extra Parameters
        if (!is_null($extraParam) && !empty($extraParam))
            $params = $extraParam;

        $this->responseArr = [...$this->responseArr, ...$params];

        return $this;
    }

    // Retuns Response In Json Format With Content Type Headers
    public function toJson($flag = JSON_PRETTY_PRINT)
    {
        // Set CORS headers to allow cross-origin requests
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        header("Access-Control-Max-Age: 3600");

        http_response_code(static::$statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->responseArr, $flag);
        $this->unset();
    }

    public function unset()
    {
        unset($statusCode);
        unset($this->responseArr);
    }
}
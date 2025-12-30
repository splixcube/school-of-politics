<?php

namespace Core;

use \Dotenv\Dotenv;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\ExpiredException;
use InvalidArgumentException;
use UnexpectedValueException;
use DomainException;



/**
 * Base Controller (where we integrate all other controllers in our mvC framework)
 */
// abstract class, as we are not going to make methods directly to this class
abstract class ApiController extends \Core\Middleware
{
    protected $route_params = [];
    protected $coreErr = [];
    protected $args = [];

    /**
     * Contructor class
     */
    public function __construct($route_params)
    {
        $this->route_params = $route_params;
    }

    public function __call($name, $args)
    {
        $method = $name . "Action";
        if (method_exists($this, $method)) {
            if ($this->before() !== false) {
                call_user_func_array([$this, $method], $args);
                $this->after();
            } else {
                return ApiResponse::statusCode(400)->withError($this->coreErr)->toJson();
            }
        } else {
            throw new \Exception("Method $method not found in controller " . get_class($this));
        }
    }

    protected function before()
    {

        $dotenv = Dotenv::createImmutable(dirname(__DIR__));
        $dotenv->safeLoad();


        $token = null;
        $headers = array_change_key_case(apache_request_headers(), CASE_LOWER);

        //  get token from teh headers
        if (isset($headers['authorization'])) {
            $auth = $headers['authorization'];
            $matches = array();
            preg_match('/Bearer\s(\S+)/', $auth, $matches);
            if (isset($matches[1])) {
                $token = $matches[1];
            }
        }


        // get api key form the header and then
        if (isset($headers['x-api-key'])) {
            $apiKey = $headers['x-api-key'];
            // $apiKey = "l48K-6sQP-71ua-oUDK";

            $fetchApiKeyDetails = QB::table('users u')
                ->join('api_keys a', 'a.uid', '=', 'u.uid')
                ->select('*')
                ->where([["api_key", "=", $apiKey]])
                ->get('fetchObject');
            $apiData = [];
            if ($fetchApiKeyDetails->success) {
                $apiData = (array) $fetchApiKeyDetails->data;
            }
        }


        $conName = $this->route_params['controller'];

        $defaultAccess = ["token"];
        if (in_array($conName, $defaultAccess)) {
            return true;
        } else if (isset($auth)) {
            try {
                $decoded = JWT::decode($token, new Key($apiData['api_public_key'], 'RS256'));
            } catch (InvalidArgumentException $e) {
                $err[] = "Malformed Token Passed!";
            } catch (DomainException $e) {
                $err[] = "Token Verification Failed!";
            } catch (SignatureInvalidException $e) {
                $err[] = "Token Signature Verification Failed!";
            } catch (ExpiredException $e) {
                $err[] = "Token Expired!";
            } catch (UnexpectedValueException $e) {
                $err[] = "Invalid or Malformed Token!";
            }

            if (empty($err)) {
                return true;
            } else {
                $this->coreErr = $err;
                return false;
            }
        } else {
            return false;
        }
    }

    protected function after()
    {
        return true;
    }
}
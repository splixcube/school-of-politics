<?php

namespace App\Controllers\v1;

use Core\ApiResponse;
use \Core\QB;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\ExpiredException;
use InvalidArgumentException;
use UnexpectedValueException;
use DomainException;


/**
 * Post Controller
 */
class Token extends \Core\ApiController
{
    protected function indexAction()
    {

        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $err = [];
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
            } else {
                return ApiResponse::statusCode(400)
                    ->withError("Authorization Header Not Found!")
                    ->toJson();
            }



            // get api key form the header and then
            if (isset($headers['x-api-key'])) {
                $apiKey = $headers['x-api-key'];
                // $apiKey = "l48K-6sQP-71ua-oUDK";

                $fetchApiKeyDetails = QB::table('organizations o')
                    ->join('api_keys a', 'a.org_id', '=', 'o.org_id')
                    ->select('*')
                    ->where([["api_key", "=", $apiKey]])
                    ->get('fetchObject');
                $apiData = [];
                if ($fetchApiKeyDetails->success) {
                    $apiData = (array) $fetchApiKeyDetails->data;
                }
            } else {
                return ApiResponse::statusCode(400)
                    ->withError("API Key is Required.")
                    ->toJson();
            }


            if (!empty($token)) {

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
                if (!empty($err)) {
                    foreach ($err as $key => $value) {
                        $errJson[] = $value;
                    }

                    return ApiResponse::statusCode(400)
                        ->withError($errJson)
                        ->toJson();
                } else {


                    // check if token exist and not expired with this user id
                    $checkToken = QB::table('tokens t')
                        ->join('api_keys ak', 'ak.api_key', '=', 't.api_key')
                        ->select('*')
                        ->where([["ak.api_status", "=", 1], ['AND'], ["t_token", "=", $token], ['AND'], ["t_expired", "=", 0]])
                        ->get('fetchObject');
                    if ($checkToken->success) {
                        // _________________generate new user token and refresh token
                        $at = $this->getJwtToken($apiKey);
                        $rt = $this->getJwtToken($apiKey, '+2years');


                        // _________________find old refresh tokens in datatbase and make them expire
                        $updateResponse = QB::table('tokens')
                            ->where([["api_key", "=", $apiKey], ['AND'], ['t_token', '=', $token]])
                            ->update([
                                't_expired' => 1,
                            ]);

                        // _________________store new refresh token in the db
                        $insertResponse = QB::table('tokens')
                            ->insert([
                                "api_key" => $apiKey,
                                't_token' => $rt,
                            ]);


                        // _________________send new tokens

                        return ApiResponse::statusCode(201)
                            ->body([], "Tokens Updated Successfully.")
                            ->params([
                                "token" => [
                                    "at" => $at,
                                    "rt" => $rt,
                                ]
                            ])
                            ->toJson();
                    } else {
                        return ApiResponse::statusCode(400)
                            ->withError("Token Expired or Not Found! Please Send A Valid Token.")
                            ->toJson();
                    }
                }
            } else {
                return ApiResponse::statusCode(400)
                    ->withError("Token Not Found! Please Send A Valid Token.")
                    ->toJson();

            }
        } else {
            return ApiResponse::statusCode(406)
                ->withError("Request Type: '{$_SERVER['REQUEST_METHOD']}' is not supported")
                ->toJson();
        }
    }
}
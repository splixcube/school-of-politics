<?php

namespace App\Controllers\v1;

use Core\ApiResponse;
use \Core\QB;


class Login extends \Core\MainLoginController
{

	/**
	 * Show index page for main site
	 */
	public function indexAction()
	{

		// var_dump($_SERVER);
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {

			if (!empty($_POST['username']) && !empty($_POST['password'])) {

				// checkPassword credentials
				$fetchUser = QB::table('organizations o')
					->join('api_keys a', 'a.org_id', '=', 'o.org_id')
					->join('employees e', 'e.org_id', '=', 'o.org_id')
					->select('*')
					->where([["eusername", "=", $_POST['username']]])
					->get('fetchObject');
				$userData = [];
				if ($fetchUser->success) {
					$userData = (array) $fetchUser->data;

					if (password_verify($_POST['password'], $userData['epassword'])) {
						// data exist
						$at = $this->getJwtToken($userData['api_key']);
						$rt = $this->getJwtToken($userData['api_key'], '+2years');

						// set all previous refresh tokens to invalid or expire
						$expireRefreshToken = QB::table('tokens')
							->where([["api_key", "=", $userData['api_key']]])
							->update([
								't_expired' => 1,
							]);


						// insertRefreshToken
						$insertRefreshToken = QB::table('tokens')
							->insert([
								'api_key' => $userData['api_key'],
								't_token' => $rt,
							]);

						return ApiResponse::statusCode(201)
							->body([], "Logged in Successfully")
							->params([
								"token" => [
									"at" => $at,
									"rt" => $rt,
								],
								"api_key" => $userData['api_key'],
								"emp_id" => $userData['emp_id'],
								"org_id" => $userData['org_id'],
								"name" => $userData['ename'],
								"picture" => $userData['eprofile_pic'],
								// "picture" => "/uploads/profilepic/{$userData['eprofile_pic']}",
							])
							->toJson();
					} else {
						return ApiResponse::statusCode(400)
							->withError("Incorrect Password! Please Login Using Correct Password!")
							->toJson();
					}
				} else {

					return ApiResponse::statusCode(400)
						->withError("Account Not Found! Please Register Your Account.")
						->toJson();
				}
			} else {
				return ApiResponse::statusCode(400)
					->withError("Invalid Credentials! Enter Correct Username And Password!.")
					->toJson();
			}
		} else {
			return ApiResponse::statusCode(405)
				->withError("Request Type: '{$_SERVER['REQUEST_METHOD']}' is not supported")
				->toJson();
		}
	}

}
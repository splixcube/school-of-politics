<?php

namespace App\Controllers\v1;

use \Core\View;
use \Core\QB;
use Respect\Validation\Validator as vs;
use Respect\Validation\Exceptions\Exception;
use Respect\Validation\Exceptions\NestedValidationException;

class Register extends \Core\MainLoginController
{

	/**
	 * Show index page for main site
	 */
	protected function indexAction()
	{

		$err = $olddata = [];
		$success = 0;
		$api_key = NULL;

		if (isset($_POST['register'])) {
			$validateArr = [
				'username' => [['required'], 'User Name', NULL],
				'phone' => [['required', 'phone'], 'Phone Number', NULL],
				'email' => [['required', 'email'], 'Email', NULL],
				'password' => [['required'], 'Password', NULL],
			];

			$vres = $this->validateData($validateArr);

			if (empty($vres->validate_error) && $vres->validate_success) {

				// Generate API key
				$api_key = $this->generateApiKey(16);


				// Generate Public And Private Keys ---------------------------
				// configuration for openssl functions
				$configargs = [
					"config" => "C:/wamp64/bin/php/php8.1.12/extras/ssl/openssl.cnf",
					'private_key_bits' => 4096,
					"private_key_type" => OPENSSL_KEYTYPE_RSA
				];

				// Create the keypair
				$keys = openssl_pkey_new($configargs);

				// assign public key to a variable
				$public_key_pem = openssl_pkey_get_details($keys)['key'];

				// assign private key to a variable $private_key_pem
				$passPhrase = md5($_POST['email']); // passphrase for private key file
				openssl_pkey_export($keys, $private_key_pem, $passPhrase, $configargs);

				// store private key on server as a file
				$privateKeyFileName = md5($_POST['email'] . $api_key); // private key file name = md5(userEmail.api_key)

				// create directory if not exist
				if (!file_exists('privpem/')) {
					mkdir('privpem/');
				}


				// save private key
				file_put_contents("privpem/$privateKeyFileName.pem", $private_key_pem);
				// $private_key_pem;

				// Generate Public And Private Keys END ---------------------------

				// insert user data into db
				$insertResponse = QB::table('organizations')
					->insertgetid([
						'ousername' => $_POST['username'],
						'ophone' => $_POST['phone'],
						'oemail' => $_POST['email'],
						'opassword' => password_hash($_POST['password'], PASSWORD_DEFAULT),
					]);

				if ($insertResponse->success) {
					$insertApiData = QB::table('api_keys')
						->insert([
							'org_id' => $insertResponse->data,
							'api_key' => $api_key,
							'api_public_key' => $public_key_pem,
						]);
					$success = $insertApiData->success;
				}

			} else {
				$err = [...$err, ...$vres->validate_error];
				$olddata = $vres->validate_olddata;
			}

		}
		// echo $success;


		View::renderTemplate("register.html", ['err' => $err, 'success' => $success, 'olddata' => $olddata, 'api_key' => $api_key,]);
	}

	/**
	 * generateApiKey
	 *
	 * @param  numeric $length length of random string
	 * @return string
	 */
	private function generateApiKey($length)
	{
		$apiKey = '';
		$chars = array_merge(range(0, 9), range('a', 'z'), range('A', 'Z'));
		shuffle($chars);
		for ($i = 0; $i < $length; $i++) {
			if ($i != 0 && $i % 4 == 0) { // nonzero and divisible by 4
				$apiKey .= '-';
			}
			$apiKey .= $chars[mt_rand(0, count($chars) - 1)];
		}
		return $apiKey;
	}
}
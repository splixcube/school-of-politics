<?php

namespace App\Controllers\Ajaxcalls;

use \Core\QB;
use \Core\View;
use Core\ApiResponse;
use Instamojo\Instamojo;


class Main extends \Core\MainController
{

	public function bookAction()
	{
		// Set CORS headers to allow cross-origin requests
		header("Access-Control-Allow-Origin: *");
		header("Access-Control-Allow-Methods: POST, OPTIONS");
		header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
		header("Access-Control-Max-Age: 3600");

		// Handle preflight OPTIONS request
		if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
			http_response_code(200);
			exit();
		}

		if ($_SERVER["REQUEST_METHOD"] == "POST") {
			// Handle JSON input if Content-Type is application/json
			$inputData = $_POST;
			$contentType = $_SERVER["CONTENT_TYPE"] ?? '';
			if (strpos($contentType, 'application/json') !== false) {
				$jsonInput = file_get_contents('php://input');
				$jsonData = json_decode($jsonInput, true);
				if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
					$inputData = array_merge($_POST, $jsonData);
				}
			}

			$err = [];
			if (/* empty($inputData["name"]) || empty($inputData["whatsapp"]) || */ empty($inputData["package-type"])) {
				ApiResponse::statusCode(404)
					->withError("Missing parameters.")
					->toJson();
			}

			/*
						// Validate name (should contain only letters and spaces)
						if (!preg_match('/^[a-zA-Z\s]+$/', trim($_POST["name"]))) {
							ApiResponse::statusCode(400)
								->withError("Name should contain only letters and spaces.")
								->toJson();
						}

						// Validate WhatsApp number (should be 10 digits)
						$whatsapp = preg_replace('/[^0-9]/', '', $_POST["whatsapp"]);
						if (strlen($whatsapp) != 10) {
							ApiResponse::statusCode(400)
								->withError("WhatsApp number should be exactly 10 digits.")
								->toJson();
						}

						// Update the whatsapp field with cleaned number
						$_POST["whatsapp"] = $whatsapp;
			 */

			$orderId = uniqid('ORD');


			// Array of packages
			$packages = [
				// 'individual' => 851,
				// 'partner' => 1251,
				// 'family' => 2001,
				// 'joint-family' => 3001,
				'Shani Dosha Nivaran' => 99,
				'monthly' => 599,
				'Gaay Ko Roti' => 99,
				'Shani Dosha Nivaran Pooja' => 199,
				'Shani Dosha Nivaran Poorna Sankalp' => 499,
				'Vaikuntha Ekadashi Puja Single' => 99,
				'Vaikuntha Ekadashi Puja Family' => 299,
				'Hast Rekha' => 299
			];


			// Now create the order for the booking in our database
			$insertResponse = QB::table('bookings')
				->insertgetid([
					// 'bname' => $inputData['name'],
					// 'bphone' => $inputData['whatsapp'],
					'bpackage' => $inputData['package-type'],
					'bamount' => $packages[$inputData['package-type']] ?? 0,
					'border_id' => $orderId,
					'bstatus' => 'pending',
				]);

			if ($insertResponse->success) {
				$bookingId = $insertResponse->data;
				$authType = "app";

				try {
					// initialize the payment gateway
					$api = Instamojo::init($authType, [
						"client_id" => $_ENV['client_id'],
						"client_secret" => $_ENV["client_secret"],
					]);

					// Determine redirect URL - use success_redirect_url if provided, otherwise use default
					$redirectUrl = $_ENV['appurl'] . "/payment?order=" . $orderId;
					
					// Append id parameter if present
					if (!empty($inputData['id'])) {
						$redirectUrl .= "&id=" . urlencode($inputData['id']);
					}
					
					if (!empty($inputData["success_redirect_url"])) {
						// Validate and sanitize the redirect URL
						$customRedirectUrl = filter_var($inputData["success_redirect_url"], FILTER_SANITIZE_URL);
						if (filter_var($customRedirectUrl, FILTER_VALIDATE_URL)) {
							// Append order ID as query parameter if not already present
							$separator = (strpos($customRedirectUrl, '?') !== false) ? '&' : '?';
							$redirectUrl = $customRedirectUrl . $separator . "order=" . $orderId;
							// Append id parameter if present
							if (!empty($inputData['id'])) {
								$redirectUrl .= "&id=" . urlencode($inputData['id']);
							}
						}
					}

					// Create a payment request to insta mojo payment gateway
					$response = $api->createPaymentRequest(array(
						"purpose" => "Sanatanis Booking-" . $inputData['package-type'],
						"amount" => $packages[$inputData['package-type']],
						// "send_sms" => true,
						// "phone" => $inputData['whatsapp'],
						"redirect_url" => $redirectUrl,
						'allow_repeated_payments' => false,
					));
					// print_r($response);


					if (!empty($response['id']) && !empty($response['longurl'])) {
						// Update data in booking
						$updateResponse = QB::table('bookings')
							->where([["bid", "=", $bookingId]])
							->update([
								'bpg_request_id' => $response['id'],
								'bpg_response' => json_encode($response),
							]);
						$success = $updateResponse->success;
						if ($success) {
							// redirect to long url
							// header('Location: ' . $response['longurl']);

							ApiResponse::statusCode(200)
								->body(["redirect_url" => $response['longurl']], "Payment request created successfully.")
								->toJson();

						} else {

							// If update fails, return error
							ApiResponse::statusCode(500)
								->withError("Failed to update booking with payment details.")
								->toJson();
						}
					}


				} catch (\Exception $e) {
					// print ('Error: ' . $e->getMessage());

					// Redirect To Lp With Error Message
					ApiResponse::statusCode(500)
						->withError("Payment gateway error: " . $e->getMessage())
						->toJson();
				}

			}



		} else {
			ApiResponse::statusCode(404)
				->withError("Method Not supported.")
				->toJson();
		}

	}

	/**
	 * Get buyer details from Instamojo payment
	 * Accepts payment_request_id (to get buyer from payments array) or payment_id (to get payment details directly)
	 * 
	 * @return void
	 */
	public function getUserDetailsAction()
	{
		// Set CORS headers to allow cross-origin requests
		header("Access-Control-Allow-Origin: *");
		header("Access-Control-Allow-Methods: GET, OPTIONS");
		header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
		header("Access-Control-Max-Age: 3600");

		// Handle preflight OPTIONS request
		if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
			http_response_code(200);
			exit();
		}

		// Only accept GET requests
		if ($_SERVER["REQUEST_METHOD"] != "GET") {
			ApiResponse::statusCode(405)
				->withError("Method not allowed. Only GET requests are supported.")
				->toJson();
			return;
		}

		// Get parameters from GET request
		$inputData = $_GET;

		// Validate input
		if (empty($inputData['payment_request_id']) && empty($inputData['payment_id'])) {
			ApiResponse::statusCode(400)
				->withError("Missing parameter: payment_request_id or payment_id is required.")
				->toJson();
			return;
		}

		try {
			// Initialize Instamojo API
			$authType = "app";
			$api = Instamojo::init($authType, [
				"client_id" => $_ENV['client_id'],
				"client_secret" => $_ENV["client_secret"],
			]);

			$buyerDetails = null;

			// Option 1: Get buyer from payment details using payment_id
			if (!empty($inputData['payment_id'])) {
				try {
					$paymentDetails = $api->getPaymentDetails($inputData['payment_id']);
					
					if (!empty($paymentDetails)) {
						// Extract buyer information from payment details
						$buyerDetails = [
							'phone' => $paymentDetails['buyer_phone'] ?? $paymentDetails['phone'] ?? null,
							'email' => $paymentDetails['buyer'] ?? $paymentDetails['email'] ?? null,
							'name' => $paymentDetails['buyer_name'] ?? null,
						];
					}
				} catch (\Exception $e) {
					error_log('Failed to get payment details: ' . $e->getMessage());
				}
			}
			
			// Option 2: Get buyer from payment request's payments array
			if (empty($buyerDetails) && !empty($inputData['payment_request_id'])) {
				try {
					$paymentRequest = $api->getPaymentRequestDetails($inputData['payment_request_id']);
					
					if (!empty($paymentRequest['payments']) && is_array($paymentRequest['payments'])) {
						// Get the first successful payment (or latest payment)
						foreach ($paymentRequest['payments'] as $payment) {
							if (!empty($payment['buyer_phone']) || !empty($payment['buyer'])) {
								$buyerDetails = [
									'phone' => $payment['buyer_phone'] ?? $payment['phone'] ?? null,
									'email' => $payment['buyer'] ?? $payment['email'] ?? null,
									'name' => $payment['buyer_name'] ?? null,
								];
								break; // Use first payment with buyer info
							}
						}
					}
					
					// Fallback: Check if buyer info is directly in payment request
					if (empty($buyerDetails)) {
						$buyerDetails = [
							'phone' => $paymentRequest['buyer_phone'] ?? $paymentRequest['phone'] ?? null,
							'email' => $paymentRequest['buyer'] ?? $paymentRequest['email'] ?? null,
							'name' => $paymentRequest['buyer_name'] ?? null,
						];
					}
				} catch (\Exception $e) {
					error_log('Failed to get payment request details: ' . $e->getMessage());
				}
			}

			// Clean and return buyer details
			if ($buyerDetails && (!empty($buyerDetails['phone']) || !empty($buyerDetails['email']))) {
				// Clean phone number if present
				if (!empty($buyerDetails['phone'])) {
					$buyerDetails['phone'] = preg_replace('/[^0-9]/', '', $buyerDetails['phone']);
					
					// Send SMS to buyer
					try {
						$this->sendSMS($buyerDetails['phone']);
						$buyerDetails['sms_sent'] = true;
						error_log('SMS sent to buyer: ' . $buyerDetails['phone']);
					} catch (\Exception $e) {
						$buyerDetails['sms_sent'] = false;
						$buyerDetails['sms_error'] = $e->getMessage();
						error_log('Failed to send SMS to buyer: ' . $e->getMessage());
					}
				}
				
				ApiResponse::statusCode(200)
					->body($buyerDetails, "Buyer details fetched successfully.")
					->toJson();
			} else {
				ApiResponse::statusCode(404)
					->withError("Buyer details not found. Payment may not be completed yet or buyer information is not available.")
					->toJson();
			}

		} catch (\Exception $e) {
			ApiResponse::statusCode(500)
				->withError("Error fetching buyer details: " . $e->getMessage())
				->toJson();
		}
	}

	/**
	 * Send SMS using VoiceNSMS API
	 * 
	 * @param string $phoneNumber Phone number (10 digits)
	 * @return void
	 */
	private function sendSMS($phoneNumber)
	{
		$smsMessage = "Namaste Puja seva book karne ke liye dhanyawaad. Hum aapse shighra sampark karenge, Political Academy";
		
		$smsUrl = "https://api.voicensms.in/SMSAPI/webresources/CreateSMSCampaignGet?" . http_build_query([
			'ukey' => 'HxZPLTsT8YOcGGQ472y6eakRR',
			'msisdn' => $phoneNumber,
			'language' => '0',
			'credittype' => '7',
			'senderid' => 'POLAPP',
			'templateid' => '78641',
			'message' => $smsMessage,
			'filetype' => '2'
		]);
		
		// Log SMS URL for debugging
		error_log('SMS URL: ' . $smsUrl);
		
		$ch = curl_init($smsUrl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10 second timeout
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // 5 second connection timeout
		
		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$curlError = curl_error($ch);
		$curlErrno = curl_errno($ch);
		curl_close($ch);
		
		// Log SMS response for debugging
		error_log('SMS API Response (HTTP ' . $httpCode . '): ' . $response);
		
		if ($curlError || $curlErrno) {
			$errorMsg = "SMS cURL Error ($curlErrno): " . ($curlError ?: "Unknown error");
			error_log($errorMsg);
			throw new \Exception($errorMsg);
		}
		
		if ($httpCode != 200) {
			$errorMsg = "SMS API returned HTTP $httpCode: " . ($response ?: "No response");
			error_log($errorMsg);
			throw new \Exception($errorMsg);
		}
	}

}

<?php

namespace App\Controllers\Admin\Ajaxcalls;

use Core\QB;
use \Core\View;
use Core\ApiResponse;

class Candidate extends \Core\Controller
{

	public function getAction()
	{
		if (empty($_POST['uid'])) {
			return ApiResponse::statusCode(400)
				->withError("Missing parameters.")
				->toJson();
		}
		$uid = $_POST['uid'];
		$fetchCandidate = QB::table('thc_users')
			->select('*')
			->where([["uid", "=", $uid]])
			->get('fetchObject');
		if ($fetchCandidate->success) {
			return ApiResponse::statusCode(200)
				->body($fetchCandidate->data, "Candidate details fetched successfully!")
				->toJson();
		} else {
			return ApiResponse::statusCode(400)
				->withError("Unable to fetch candidate details.")
				->toJson();
		}
	}

	public function deleteAction()
	{


		if (empty($_POST['uid'])) {
			return ApiResponse::statusCode(400)
				->withError("Missing parameters.")
				->toJson();
		}
		$uid = $_POST['uid'];
		$deleteCompany = QB::table('thc_users')
			->where([["uid", "=", $uid], ['AND'], ["utype", "=", 'candidate']])
			->delete();

		if ($deleteCompany->success) {
			return ApiResponse::statusCode(200)
				->body([], "Candidate deleted successfully!")
				->toJson();
		} else {
			return ApiResponse::statusCode(400)
				->withError("Unable to delete candidate.")
				->toJson();
		}
	}

	public function editAction()
	{

		$updateProfile = [];
		// Check for required fields



		if (empty($_POST['uid'])) {
			return ApiResponse::statusCode(400)
				->withError("Candidate ID is required.")
				->toJson();
		}

		$uid = $_POST['uid'];
		$updateData = [];

		// Collect and validate fields to update
		if (isset($_POST['name']) && !empty($_POST['name'])) {
			$updateData['uname'] = $_POST['name'];
		}

		if (isset($_POST['phone']) && !empty($_POST['phone'])) {
			// Validate phone (10 digits)
			if (!preg_match('/^\d{10}$/', $_POST['phone'])) {
				return ApiResponse::statusCode(400)
					->withError("Phone number must be 10 digits.")
					->toJson();
			}
			$updateData['uphone'] = $_POST['phone'];
		}

		if (isset($_POST['email']) && !empty($_POST['email'])) {
			// Validate email
			if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
				return ApiResponse::statusCode(400)
					->withError("Invalid email format.")
					->toJson();
			}
			$updateData['uemail'] = $_POST['email'];
		}

		if (isset($_POST['gender']) && !empty($_POST['gender'])) {
			// Validate gender
			if (!in_array($_POST['gender'], ['male', 'female', 'other'])) {
				return ApiResponse::statusCode(400)
					->withError("Invalid gender value.")
					->toJson();
			}
			$updateData['ugender'] = $_POST['gender'];
		}

		// Handle profile picture upload if provided
		if (!empty($_FILES['profile']['tmp_name'])) {
			$updateData['uphoto'] = $this->fileUpload('uploads/candidate', $_FILES['profile']);
		}

		// If no fields to update, return error
		if (empty($updateData)) {
			return ApiResponse::statusCode(400)
				->withError("No fields to update.")
				->toJson();
		}

		// Update candidate
		$updateCandidate = QB::table('thc_users')
			->where([["uid", "=", $uid]])
			->update($updateData);

		if ($updateCandidate->success) {
			return ApiResponse::statusCode(200)
				->body([], "Candidate updated successfully!")
				->toJson();
		} else {
			return ApiResponse::statusCode(500)
				->withError("Failed to update candidate.")
				->toJson();
		}
	}

	// resume view data
	public function resumeViewAction()
	{
		if (empty($_POST['rpid'])) {
			return ApiResponse::statusCode(400)
				->withError("Missing parameters.")
				->toJson();
		}

		$rpid = $_POST['rpid'];

		$fetchCandidate = QB::table('thc_users')
			->join('thc_resume_profiles trp', 'trp.uid', '=', 'thc_users.uid')
			->select('*')
			->where([["rpid", "=", $rpid]])
			->get('fetchObject');


		if ($fetchCandidate->success) {
			return ApiResponse::statusCode(200)
				->body($fetchCandidate->data, "Candidate details fetched successfully!")
				->toJson();
		} else {
			return ApiResponse::statusCode(200)
				->body([], "Candidate's resume data not found!")
				->toJson();
		}
	}
}

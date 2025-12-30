<?php

namespace App\Controllers;

use \Core\QB;
use \Core\View;
use Instamojo\Instamojo;


class Main extends \Core\MainController
{
    protected function indexAction()
    {

        View::renderTemplate("index.html", []);
    }

    protected function paymentAction()
    {
        if (empty($_GET['order']) || empty($_GET['payment_id']) || empty($_GET['payment_status']) || empty($_GET['payment_request_id'])) {
            // echo "Parameter missing.";


            // redirect to lp with error message
            header('Location: ' . $_ENV['appurl'] . '?payment_status=failed&message=Order ID is missing.');
        }

        // https://demo.techmeraki.com/pujalppg/payment?order=ORD6879efee6fc1b&payment_id=MOJO5718205A60168434&payment_status=Credit&payment_request_id=7cbf9c2b41e14fc6b46fa1942bcc23b9

        // Assign Variables From Get Parameters
        $orderId = $_GET['order'];
        $paymentId = $_GET['payment_id'];
        $paymentRequestId = $_GET['payment_request_id'];
        $paymentStatus = $_GET['payment_status'];


        // Fetch Order Details From Database
        $fetchOrderDetails = QB::table('bookings')
            ->select('*')
            ->where([['border_id', "=", $orderId]])
            ->get('fetchObject');
        if (!$fetchOrderDetails->success) {

            // Give Error Message
            header('Location: ' . $_ENV['appurl'] . '?payment_status=failed&message=Order not found.');

            // echo "Order not found.";
        }

        $orderData = (array) $fetchOrderDetails->data;

        try {
            // Initialize Instamojo Api
            $authType = 'app';
            $api = Instamojo::init($authType, [
                "client_id" => $_ENV['client_id'],
                "client_secret" => $_ENV["client_secret"],
            ]);


            // Check For Payment Status And Handle Accordingly
            $response = $api->getPaymentRequestDetails($orderData['bpg_request_id']);

            if (!empty($response)) {
                // update booking status to completed
                $updateResponse = QB::table('bookings')
                    ->where([["border_id", "=", $orderId]])
                    ->update([
                        'bpg_payment_id' => $paymentId,
                        'bpg_pay_response' => json_encode($response),
                        'bstatus' => strtolower($response['status']),

                    ]);
                $success = $updateResponse->success;


                // redirect to success page
                // echo 'Payment successful.';
                header('Location: ' . $_ENV['appurl'] . '?payment_status=success');

            } else {
                // echo 'Failed to update booking status.';
                header('Location: ' . $_ENV['appurl'] . '?payment_status=failed&message=Failed to update booking status.');
            }


            // echo "<pre>";
            // print_r($response);




            // based on response from payment gateway, you can redirect or show a message

            // redirect to lp with success or failure message in query string

        } catch (\Exception $e) {
            // print_r('Error: ' . $e->getMessage());

            // Redirect To Lp With Error Message
            header('Location: ' . $_ENV['appurl'] . '?payment_status=failed&message=' . urlencode($e->getMessage()));
        }




    }


    public function adqgbkevocnqjqsxxjweAction()
    {

        $leadsarray = array();
        $leads = QB::table('entries')
            ->select('*')
            ->orderby('etstamp', 'DESC')
            ->get('fetchAll', 'PDO::FETCH_ASSOC');
        $finaldata = array();
        if ($leads->success == 1) {
            foreach ($leads->data as $value) {

                $leadsarray['Name'] = $value['ename'] ?? "-" ?: "-";
                $leadsarray['Phone'] = $value['ephone'] ?? "-" ?: "-";
                $leadsarray['Email'] = $value['eemail'] ?? "-" ?: "-";
                $leadsarray['City'] = $value['ecity'] ?? "-" ?: "-";
                $leadsarray['Order ID'] = $value['order_id'] ?? "-" ?: "-";
                $leadsarray['Merchant Transaction ID'] = $value['merchant_transaction_id'] ?? "-" ?: "-";
                $leadsarray['PhonePe Transaction ID'] = $value['phonepe_transaction_id'] ?? "-" ?: "-";
                $leadsarray['User ID'] = $value['user_id'] ?? "-" ?: "-";
                $leadsarray['Amount'] = $value['amount'] ?? "-" ?: "-";
                $leadsarray['Status'] = $value['status'] ?? "-" ?: "-";
                $leadsarray['Payment Method'] = $value['payment_method'] ?? "-" ?: "-";
                $leadsarray['Time Stamp'] = $this->changeDateTime($value['etstamp'], "d-m-Y H:i", 'UTC', 'Asia/Kolkata') ?? "-" ?: "-";
                $leadsarray['Response'] = $value['response'] ?? "-" ?: "-";

                array_push($finaldata, $leadsarray);
            }
        }

        // set headers
        header("Content-type: application/json");
        echo json_encode($finaldata, JSON_PRETTY_PRINT);
    }
}

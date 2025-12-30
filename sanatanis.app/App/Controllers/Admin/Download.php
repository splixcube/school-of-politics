<?php
namespace App\Controllers\Admin;

ini_set('memory_limit', '512M');

use \Core\View;
use \Core\QB;


class Download extends \Core\Controller
{

    protected function entriesAction()
    {
        $leadsarray = array();
        $leads = QB::table('bookings')
            ->select('*')
            ->orderby('btstamp', 'DESC')
            ->get('fetchAll', 'PDO::FETCH_ASSOC');
        $finaldata = array();
        if ($leads->success == 1) {
            foreach ($leads->data as $value) {

                $leadsarray['Booking ID'] = $value['bid'] ?? "-" ?: "-";
                $leadsarray['Name'] = $value['bname'] ?? "-" ?: "-";
                $leadsarray['Phone'] = $value['bphone'] ?? "-" ?: "-";
                $leadsarray['Email'] = $value['bemail'] ?? "-" ?: "-";
                $leadsarray['Package'] = $value['bpackage'] ?? "-" ?: "-";
                $leadsarray['Amount'] = $value['bamount'] ?? "-" ?: "-";
                $leadsarray['Order ID'] = $value['border_id'] ?? "-" ?: "-";
                $leadsarray['PG Request ID'] = $value['bpg_request_id'] ?? "-" ?: "-";
                $leadsarray['PG Payment ID'] = $value['bpg_payment_id'] ?? "-" ?: "-";
                $leadsarray['Status'] = $value['bstatus'] ?? "-" ?: "-";
                $leadsarray['Time Stamp'] = $this->changeDateTime($value['btstamp'], "d-m-Y H:i", 'UTC', 'Asia/Kolkata') ?? "-" ?: "-";

                // Parse JSON responses for better export
                // $pgResponse = "";
                // if (!empty($value['bpg_response'])) {
                //     $decoded = json_decode($value['bpg_response'], true);
                //     if ($decoded) {
                //         $pgResponse =
                //             "Created: " . ($decoded['created_at'] ?? 'N/A');
                //     }
                // }
                // $leadsarray['PG Response '] = $pgResponse ?: "-";

                // $payResponse = "";
                // if (!empty($value['bpg_pay_response'])) {
                //     $decoded = json_decode($value['bpg_pay_response'], true);
                //     if ($decoded) {
                //         $payResponse = "Modified: " . ($decoded['modified_at'] ?? 'N/A');
                //     }
                // }
                // $leadsarray['Payment Response'] = $payResponse ?: "-";

                array_push($finaldata, $leadsarray);
            }
        }
        $this->datatoexcel("Sanatanis-Puja-Booking-Report-", $finaldata);
    }

}

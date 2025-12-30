<?php

namespace App\Controllers\Admin;
ini_set('memory_limit', '512M');

use \Core\View;
use \Core\QB;

class Dashboard extends \Core\Controller
{

    /**
     * Show index page for main site
     */
    protected function indexAction()
    {
        $session = NULL;
        if (isset($_SESSION['from']) and isset($_SESSION['to'])) {
            $from = $_SESSION['from'];
            $to = date("Y-m-d", strtotime($_SESSION['to'] . " + 1 day"));

            // apply get filter
            if (isset($_GET['filter'])) {
                $where = [['btstamp', '>=', $from], ['AND'], ['btstamp', '<=', $to], ['AND'], ['bstatus', '=', $_GET['filter']]];
            } else {
                $where = [['btstamp', '>=', $from], ['AND'], ['btstamp', '<=', $to]];
            }

            $leads = QB::table('bookings')
                ->select('*')
                ->where($where)
                ->orderby('btstamp', 'DESC')
                ->get('fetchAll', 'PDO::FETCH_ASSOC');
        } else {
            // apply get filter
            if (isset($_GET['filter'])) {
                $leads = QB::table('bookings')
                    ->select('*')
                    ->where([['bstatus', '=', $_GET['filter']]])
                    ->orderby('btstamp', 'DESC')
                    ->get('fetchAll', 'PDO::FETCH_ASSOC');
            } else {
                $leads = QB::table('bookings')
                    ->select('*')
                    ->orderby('btstamp', 'DESC')
                    ->get('fetchAll', 'PDO::FETCH_ASSOC');
            }
        }

        // filter the counts of paid, all and unpaid
        $allCount = 0;
        $paidCount = 0;
        $unpaidCount = 0;

        $leadsData = [];
        if ($leads->success) {
            $leadsData = $leads->data;

            // change date from utc to local
            foreach ($leadsData as $key => &$value) {
                $leadsData[$key]['btstamp'] = $this->changeDateTime($value['btstamp'], "d-m-Y H:i", 'UTC', 'Asia/Kolkata');

                // Parse JSON responses for better display
                if (!empty($value['bpg_response'])) {
                    $leadsData[$key]['bpg_response_decoded'] = json_decode($value['bpg_response'], true);
                }
                if (!empty($value['bpg_pay_response'])) {
                    $leadsData[$key]['bpg_pay_response_decoded'] = json_decode($value['bpg_pay_response'], true);
                }
            }
        }

        // Fetch counts using SQL queries
        $allCountQuery = QB::table('bookings')
            ->select('COUNT(bid) as datacount')
            ->get('fetchObject');
        $allCount = $allCountQuery->success == 1 ? $allCountQuery->data->datacount : 0;

        $paidCountQuery = QB::table('bookings')
            ->select('COUNT(bid) as datacount')
            ->where([['bstatus', '=', 'completed']])
            ->get('fetchObject');
        $paidCount = $paidCountQuery->success == 1 ? $paidCountQuery->data->datacount : 0;

        $unpaidCountQuery = QB::table('bookings')
            ->select('COUNT(bid) as datacount')
            ->where([['bstatus', '=', "pending"], ['OR'], ['bstatus', '=', "failed"]])
            ->get('fetchObject');
        $unpaidCount = $unpaidCountQuery->success == 1 ? $unpaidCountQuery->data->datacount : 0;

        // ---------- Date wise count -------------
        $entryCount = QB::table('bookings')->select('*')->orderby('btstamp', 'DESC')->get('fetchAll', 'PDO::FETCH_ASSOC');
        if ($entryCount->success == 1) {
            $entryCount = count($entryCount->data);
        } else {
            $entryCount = 0;
        }

        $today = QB::table('bookings')->select("*")->where([['DATE(btstamp) = CURDATE()']])->get('fetchAll', 'PDO::FETCH_ASSOC');
        if ($today->success == 1) {
            $todaycount = count($today->data);
        } else {
            $todaycount = 0;
        }

        $week = QB::table('bookings')->select("*")->where([['btstamp >= DATE(NOW()) - INTERVAL 7 DAY']])->get('fetchAll', 'PDO::FETCH_ASSOC');
        if ($week->success == 1) {
            $weekcount = count($week->data);
        } else {
            $weekcount = 0;
        }

        // Additional statistics for bookings
        $packageStats = $this->getPackageStatistics();
        $revenueStats = $this->getRevenueStatistics();


        // echo "<pre>";

        // var_dump(

        //     [
        //         'data' => $leadsData,
        //         'total' => $entryCount,
        //         'today' => $todaycount,
        //         'week' => $weekcount,
        //         'all' => $allCount,
        //         'paid' => $paidCount,
        //         'unpaid' => $unpaidCount,
        //         'package_stats' => $packageStats,
        //         'revenue_stats' => $revenueStats
        //     ]
        // );



        View::renderTemplate("Admin/dashboard.html", [
            'data' => $leadsData,
            'total' => $entryCount,
            'today' => $todaycount,
            'week' => $weekcount,
            'all' => $allCount,
            'paid' => $paidCount,
            'unpaid' => $unpaidCount,
            'package_stats' => $packageStats,
            'revenue_stats' => $revenueStats
        ]);
    }

    /**
     * Get package-wise statistics
     */
    private function getPackageStatistics()
    {
        $packageQuery = QB::table('bookings')
            ->select('bpackage, COUNT(bid) as count, SUM(CAST(bamount AS DECIMAL(10,2))) as total_amount')
            ->where([['bstatus', '=', 'completed']])
            ->groupBy('bpackage')
            ->get('fetchAll', 'PDO::FETCH_ASSOC');

        return $packageQuery->success ? $packageQuery->data : [];
    }

    /**
     * Get revenue statistics
     */
    private function getRevenueStatistics()
    {
        // Total revenue
        $totalRevenueQuery = QB::table('bookings')
            ->select('SUM(CAST(bamount AS DECIMAL(10,2))) as total_revenue')
            ->where([['bstatus', '=', 'completed']])
            ->get('fetchObject');

        $totalRevenue = $totalRevenueQuery->success ? $totalRevenueQuery->data->total_revenue : 0;

        // Today's revenue
        $todayRevenueQuery = QB::table('bookings')
            ->select('SUM(CAST(bamount AS DECIMAL(10,2))) as today_revenue')
            ->where([['bstatus', '=', 'completed'], ['AND'], ['DATE(btstamp) = CURDATE()']])
            ->get('fetchObject');

        $todayRevenue = $todayRevenueQuery->success ? $todayRevenueQuery->data->today_revenue : 0;

        // This week's revenue
        $weekRevenueQuery = QB::table('bookings')
            ->select('SUM(CAST(bamount AS DECIMAL(10,2))) as week_revenue')
            ->where([['bstatus', '=', 'completed'], ['AND'], ['btstamp >= DATE(NOW()) - INTERVAL 7 DAY']])
            ->get('fetchObject');

        $weekRevenue = $weekRevenueQuery->success ? $weekRevenueQuery->data->week_revenue : 0;

        return [
            'total' => $totalRevenue ?? 0,
            'today' => $todayRevenue ?? 0,
            'week' => $weekRevenue ?? 0
        ];
    }

    /**
     * Get booking details by ID
     */
    public function getBookingDetails($bookingId)
    {
        $booking = QB::table('bookings')
            ->select('*')
            ->where([['bid', '=', $bookingId]])
            ->get('fetchObject');

        if ($booking->success) {
            $bookingData = $booking->data;

            // Parse JSON responses
            if (!empty($bookingData->bpg_response)) {
                $bookingData->bpg_response_decoded = json_decode($bookingData->bpg_response, true);
            }
            if (!empty($bookingData->bpg_pay_response)) {
                $bookingData->bpg_pay_response_decoded = json_decode($bookingData->bpg_pay_response, true);
            }

            return $bookingData;
        }

        return null;
    }

    /**
     * Update booking status
     */
    public function updateBookingStatus($bookingId, $status)
    {
        $result = QB::table('bookings')
            ->where([['bid', '=', $bookingId]])
            ->update(['bstatus' => $status]);

        return $result->success;
    }
}
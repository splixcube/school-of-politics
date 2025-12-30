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

            // $session = true;


            // apply get filter
            if (isset($_GET['filter'])) {
                $where = [['etstamp', '>=', $from], ['AND'], ['etstamp', '<=', $to], ['AND'], ['status', '=', $_GET['filter']]];
            } else {
                $where = [['etstamp', '>=', $from], ['AND'], ['etstamp', '<=', $to]];
            }

            $leads = QB::table('entries')
                ->select('*')
                ->where($where)
                ->orderby('etstamp', 'DESC')
                ->get('fetchAll', 'PDO::FETCH_ASSOC');
        } else {
            // apply get filter
            if (isset($_GET['filter'])) {
                $leads = QB::table('entries')
                    ->select('*')
                    ->where([['status', '=', $_GET['filter']]])
                    ->orderby('etstamp', 'DESC')
                    ->get('fetchAll', 'PDO::FETCH_ASSOC');
            } else {
                $leads = QB::table('entries')
                    ->select('*')
                    ->orderby('etstamp', 'DESC')
                    ->get('fetchAll', 'PDO::FETCH_ASSOC');
            }
        }

        // filter teh counts of paid , all and unpaid
        $allCount = 0;
        $paidCount = 0;
        $unpaidCount = 0;

        $leadsData = [];
        if ($leads->success) {
            $leadsData = $leads->data;

            // change date form utc to local
            foreach ($leadsData as $key => &$value) {
                $leadsData[$key]['etstamp'] = $this->changeDateTime($value['etstamp'], "d-m-Y H:i", 'UTC', 'Asia/Kolkata');
            }
        }

        // Fetch counts using SQL queries
        $allCountQuery = QB::table('entries')
            ->select('COUNT(eid) as datacount')
            ->get('fetchObject');
        $allCount = $allCountQuery->success == 1 ? $allCountQuery->data->datacount : 0;

        $paidCountQuery = QB::table('entries')
            ->select('COUNT(eid) as datacount')
            ->where([['status', '=', 'COMPLETED']])
            ->get('fetchObject');
        $paidCount = $paidCountQuery->success == 1 ? $paidCountQuery->data->datacount : 0;


        $unpaidCountQuery = QB::table('entries')
            ->select('COUNT(eid) as datacount')
            ->where([['status', '=', "PENDING"], ['OR'], ['status', '=', "FAILED"]])
            ->get('fetchObject');
        $unpaidCount = $unpaidCountQuery->success == 1 ? $unpaidCountQuery->data->datacount : 0;


        // ---------- Date wise count -------------
        $entryCount = QB::table('entries')->select('*')->orderby('etstamp', 'DESC')->get('fetchAll', 'PDO::FETCH_ASSOC');
        if ($entryCount->success == 1) {
            $entryCount = count($entryCount->data);
        } else {
            $entryCount = 0;
        }

        $today = QB::table('entries')->select("*")->where([['DATE(etstamp) = CURDATE()']])->get('fetchAll', 'PDO::FETCH_ASSOC');
        if ($today->success == 1) {
            $todaycount = count($today->data);
        } else {
            $todaycount = 0;
        }

        $week = QB::table('entries')->select("*")->where([['etstamp >= DATE(NOW()) - INTERVAL 7 DAY']])->get('fetchAll', 'PDO::FETCH_ASSOC');
        if ($week->success == 1) {
            $weekcount = count($week->data);
        } else {
            $weekcount = 0;
        }


        View::renderTemplate("Admin/dashboard.html", ['data' => $leadsData, 'total' => $entryCount, 'today' => $todaycount, 'week' => $weekcount, 'all' => $allCount, 'paid' => $paidCount, 'unpaid' => $unpaidCount]);
    }
}

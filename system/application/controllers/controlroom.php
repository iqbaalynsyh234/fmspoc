<?php
include "base.php";

class controlroom extends Base
{

    function __construct()
    {
        parent::Base();
        // $this->load->helper('common_helper');
        // $this->load->helper('email');
        // $this->load->library('email');
        $this->load->model("dashboardmodel");
        $this->load->model("m_production");
        // $this->load->model("log_model");
        // $this->load->helper('common');
    }

    function index()
    {
        $this->room();
    }

    function room()
    {
        if (!isset($this->sess->user_type)) {
            redirect(base_url());
        }

        $privilegecode = $this->sess->user_id_role;

        $rows_company                   = $this->get_company();

        $this->params["rcompany"]       = $rows_company;
        $this->params["rlocation"]      = $this->get_location();
        $this->params['code_view_menu'] = "dashboard";

        $this->params["header"]         = $this->load->view('newdashboard/partial/headernew', $this->params, true);
        $this->params["chatsidebar"]    = $this->load->view('newdashboard/partial/chatsidebar', $this->params, true);
        $this->params["onload"]         = 1;
        if ($privilegecode == 1) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_superuser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_superuser", $this->params);
        } elseif ($privilegecode == 2) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_managementuser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_managementuser", $this->params);
        } elseif ($privilegecode == 3) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_reguleruser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_reguleruser", $this->params);
        } elseif ($privilegecode == 4) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_teknikaluser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_teknikaluser", $this->params);
        } elseif ($privilegecode == 5) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_adminpjo', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_adminpjo", $this->params);
        } elseif ($privilegecode == 6) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_userpjo', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_userpjo", $this->params);
        } else {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_new", $this->params);
        }
    }

	// New condition + pisah table report
    function search(){
        ini_set('display_errors', 1);
        //ini_set('memory_limit', '2G');
        if (! isset($this->sess->user_type))
        {
            redirect(base_url());
        }

        $company       = $this->input->post("company");
        $startdate     = $this->input->post("startdate");
        $enddate       = $this->input->post("enddate");
        $periode       = $this->input->post("periode");
        $violation     = $this->input->post("violation");
        // $reporttype = $this->input->post("reporttype");
        $reporttype = 0;
        $alarmtypefromaster = array();

        if ($alarmtype != "All") {
            $alarmbymaster = $this->m_dashboardberau->getalarmbytype($alarmtype);
            $alarmtypefromaster = array();
            for ($i=0; $i < sizeof($alarmbymaster); $i++) {
                $alarmtypefromaster[] = $alarmbymaster[$i]['alarm_type'];
            }
        }

        // echo "<pre>";
        // var_dump($company);die();
        // echo "<pre>";

        //get vehicle
        $user_id         = $this->sess->user_id;
        $user_level      = $this->sess->user_level;
        $user_company    = $this->sess->user_company;
        $user_subcompany = $this->sess->user_subcompany;
        $user_group      = $this->sess->user_group;
        $user_subgroup   = $this->sess->user_subgroup;
        $user_parent     = $this->sess->user_parent;
        $user_id_role    = $this->sess->user_id_role;
        $privilegecode   = $this->sess->user_id_role;
        $user_dblive 	   = $this->sess->user_dblive;
        $user_id_fix     = $user_id;

        // $black_list  = array("401","428","451","478","602","603","608","609","652","653","658","659",
        // 					  "600","601","650","651"); //lane deviation & forward collation

        $black_list  = array("401","451","478","608","609","652","653","658","659");

        $street_register = $this->config->item('street_register');

        $nowdate  = date("Y-m-d");
        $nowday   = date("d");
        $nowmonth = date("m");
        $nowyear  = date("Y");
        $lastday  = date("t");

        $report     = "alarm_evidence_";
        $report_sum = "summary_";

        // print_r($periode);exit();

        if($periode == "custom"){
            // $sdate = date("Y-m-d H:i:s", strtotime("-1 Hour", strtotime($startdate." ".$shour)));
            $sdate = date("Y-m-d H:i:s", strtotime($startdate." ".$shour));
            $edate = date("Y-m-d H:i:s", strtotime($enddate." ".$ehour));
        }elseif ($periode == "today") {
            $sdate = date("Y-m-d 23:00:00", strtotime("yesterday"));
            $edate = date("Y-m-d H:i:s");
            $datein = date("d-m-Y", strtotime($sdate));
        }else if($periode == "yesterday"){

            $sdate1 = date("Y-m-d 00:00:00", strtotime("yesterday"));
            $edate = date("Y-m-d 23:59:59", strtotime("yesterday"));
            // $sdate = date("Y-m-d H:i:s", strtotime("-1 Hour", strtotime($sdate1)));
            $sdate = date("Y-m-d H:i:s", strtotime($sdate1));
        }else if($periode == "last7"){
            $nowday = $nowday - 1;
            $firstday = $nowday - 7;
            if($nowday <= 7){
                $firstday = 1;
            }

            /*if($firstday > $nowday){
                $firstday = 1;
            }*/

            $sdate = date("Y-m-d H:i:s ", strtotime($nowyear."-".$nowmonth."-".$firstday." "."00:00:00"));
            $edate = date("Y-m-d H:i:s", strtotime($nowyear."-".$nowmonth."-".$nowday." "."23:59:59"));

        }
        else if($periode == "last30"){
            $firstday = "1";
            $sdate = date("Y-m-d H:i:s ", strtotime($nowyear."-".$nowmonth."-".$firstday." "."00:00:00"));
            $edate = date("Y-m-d H:i:s", strtotime($nowyear."-".$nowmonth."-".$lastday." "."23:59:59"));
        }
        else{
            $sdate = date("Y-m-d H:i:s", strtotime($startdate." ".$shour));
            $edate = date("Y-m-d H:i:s", strtotime($enddate." ".$ehour));
        }

        // print_r($sdate." ".$edate);exit();

        $m1 = date("F", strtotime($sdate));
        $m2 = date("F", strtotime($edate));
        $year = date("Y", strtotime($sdate));
        $year2 = date("Y", strtotime($edate));
        $rows = array();
        $total_q = 0;

        $error = "";
        $rows_summary = "";

        if ($vehicle == "")
        {
            $error .= "- Invalid Vehicle. Silahkan Pilih salah satu kendaraan! \n";
        }
        if ($m1 != $m2)
        {
            $error .= "- Invalid Date. Tanggal Report yang dipilih harus dalam bulan yang sama! \n";
        }

        if ($year != $year2)
        {
            $error .= "- Invalid Year. Tanggal Report yang dipilih harus dalam tahun yang sama! \n";
        }

        if ($alarmtype == "")
        {
            $error .= "- Please Select Alarm Type! \n";
        }

        switch ($m1)
        {
            case "January":
            $dbtable = $report."januari_".$year;
            $dbtable_sum = $report_sum."januari_".$year;
            break;
            case "February":
            $dbtable = $report."februari_".$year;
            $dbtable_sum = $report_sum."februari_".$year;
            break;
            case "March":
            $dbtable = $report."maret_".$year;
            $dbtable_sum = $report_sum."maret_".$year;
            break;
            case "April":
            $dbtable = $report."april_".$year;
            $dbtable_sum = $report_sum."april_".$year;
            break;
            case "May":
            $dbtable = $report."mei_".$year;
            $dbtable_sum = $report_sum."mei_".$year;
            break;
            case "June":
            $dbtable = $report."juni_".$year;
            $dbtable_sum = $report_sum."juni_".$year;
            break;
            case "July":
            $dbtable = $report."juli_".$year;
            $dbtable_sum = $report_sum."juli_".$year;
            break;
            case "August":
            $dbtable = $report."agustus_".$year;
            $dbtable_sum = $report_sum."agustus_".$year;
            break;
            case "September":
            $dbtable = $report."september_".$year;
            $dbtable_sum = $report_sum."september_".$year;
            break;
            case "October":
            $dbtable = $report."oktober_".$year;
            $dbtable_sum = $report_sum."oktober_".$year;
            break;
            case "November":
            $dbtable = $report."november_".$year;
            $dbtable_sum = $report_sum."november_".$year;
            break;
            case "December":
            $dbtable = $report."desember_".$year;
            $dbtable_sum = $report_sum."desember_".$year;
            break;
        }

        // echo "<pre>";
        // var_dump($vehicle.'-'.$company.'-'.$privilegecode);die();
        // echo "<pre>";

        $this->dbtrip = $this->load->database("tensor_report", true);

        if ($company != "all") {
            $this->dbtrip->where("alarm_report_vehicle_company", $company);
        }

            if($vehicle == "all"){
                if($privilegecode == 0){
                    $this->dbtrip->where("alarm_report_vehicle_user_id", $user_id_fix);
                }else if($privilegecode == 1){
                    $this->dbtrip->where("alarm_report_vehicle_user_id", $user_parent);
                }else if($privilegecode == 2){
                    $this->dbtrip->where("alarm_report_vehicle_user_id", $user_parent);
                }else if($privilegecode == 3){
                    $this->dbtrip->where("alarm_report_vehicle_user_id", $user_parent);
                }else if($privilegecode == 4){
                    $this->dbtrip->where("alarm_report_vehicle_user_id", $user_parent);
                }else if($privilegecode == 5){
          // echo "<pre>";
          // var_dump($user_company);die();
          // echo "<pre>";
                    $this->dbtrip->where("alarm_report_vehicle_company", $user_company);
                }else if($privilegecode == 6){
                    $this->dbtrip->where("alarm_report_vehicle_company", $user_company);
                }else{
                    $this->dbtrip->where("alarm_report_vehicle_company",99999);
                }
            }else{
                // $vehicledevice = explode("@", $vehicle);
                // echo "<pre>";
                // var_dump($vehicle);die();
                // echo "<pre>";
                $this->dbtrip->where("alarm_report_imei", $vehicle);
            }

        $this->dbtrip->where("alarm_report_media", 0); //photo
        $this->dbtrip->where("alarm_report_start_time >=", $sdate);

        $nowday            = date("d");
        $end_day_fromEdate = date("d", strtotime($edate));

        if ($nowday == $end_day_fromEdate) {
            $edate = date("Y-m-d H:i:s");
        }

        $this->dbtrip->where("alarm_report_start_time <=", $edate);
        if($km != ""){
            $this->dbtrip->where("alarm_report_location_start", "KM ".$km);
        }

        if ($alarmtype != "All") {
            $this->dbtrip->where_in('alarm_report_type', $alarmtypefromaster); //$alarmtype $alarmbymaster[0]['alarm_type']
        }
        $this->dbtrip->where_not_in('alarm_report_type', $black_list);
        //$this->dbtrip->where("alarm_report_speed_status",1);		//buka untuk trial evalia
        //$this->dbtrip->like("alarm_report_location_start", "KM"); //buka untuk trial evalia
        $this->dbtrip->where("alarm_report_gpsstatus !=","");
        // $this->dbtrip->where_in('alarm_report_location_start', $street_register); //new filter
        $this->dbtrip->order_by("alarm_report_start_time","asc");
        $this->dbtrip->group_by("alarm_report_start_time");
        $q = $this->dbtrip->get($dbtable);
        //
        // echo "<pre>";
        // var_dump($q->result_array());die();
        // echo "<pre>";

        if ($q->num_rows>0)
        {
            $rows = $q->result_array();
            $thisreport = $rows;
        }else{
            $error .= "- No Data Alarm ! \n";
        }

        if ($error != "")
        {
            $callback['error'] = true;
            $callback['message'] = $error;

            echo json_encode($callback);
            return;
        }

        $datafix = array();
        for ($j=0; $j < sizeof($thisreport); $j++) {
            $alarmreportnamefix = "";
            $alarmreporttype = $thisreport[$j]['alarm_report_type'];
                if ($alarmreporttype == 626) {
                    $alarmreportnamefix = "Driver Undetected Alarm Level One Start";
                }elseif ($alarmreporttype == 627) {
                    $alarmreportnamefix = "Driver Undetected Alarm Level Two Start";
                }elseif ($alarmreporttype == 702) {
                    $alarmreportnamefix = "Distracted Driving Alarm Level One Start";
                }elseif ($alarmreporttype == 703) {
                    $alarmreportnamefix = "Distracted Driving Alarm Level Two Start";
                }elseif ($alarmreporttype == 752) {
                    $alarmreportnamefix = "Distracted Driving Alarm Level One End";
                }elseif ($alarmreporttype == 753) {
                    $alarmreportnamefix = "Distracted Driving Alarm Level Two End";
                }else {
                    $alarmreportnamefix = $thisreport[$j]['alarm_report_name'];
                }

        if (isset($thisreport[$j]['alarm_report_id_cr'])) {
          $alarm_report_id_cr =  $thisreport[$j]['alarm_report_id_cr'];
        }else {
          $alarm_report_id_cr = "";
        }

        if (isset($thisreport[$j]['alarm_report_name_cr'])) {
          $alarm_report_name_cr =  $thisreport[$j]['alarm_report_name_cr'];
        }else {
          $alarm_report_name_cr = "";
        }

        if (isset($thisreport[$j]['alarm_report_sid_cr'])) {
          $alarm_report_sid_cr =  $thisreport[$j]['alarm_report_sid_cr'];
        }else {
          $alarm_report_sid_cr = "";
        }

        if (isset($thisreport[$j]['alarm_report_statusintervention_cr'])) {
          $alarm_report_statusintervention_cr =  $thisreport[$j]['alarm_report_statusintervention_cr'];
        }else {
          $alarm_report_statusintervention_cr = "";
        }

        if (isset($thisreport[$j]['alarm_report_intervention_category_cr'])) {
          $alarm_report_intervention_category_cr =  $thisreport[$j]['alarm_report_intervention_category_cr'];
        }else {
          $alarm_report_intervention_category_cr = "";
        }

        if (isset($thisreport[$j]['alarm_report_fatiguecategory_cr'])) {
          $alarm_report_fatiguecategory_cr =  $thisreport[$j]['alarm_report_fatiguecategory_cr'];
        }else {
          $alarm_report_fatiguecategory_cr = "";
        }

        if (isset($thisreport[$j]['alarm_report_note_cr'])) {
          $alarm_report_note_cr =  $thisreport[$j]['alarm_report_note_cr'];
        }else {
          $alarm_report_note_cr = "";
        }

        if (isset($thisreport[$j]['alarm_report_datetime_cr'])) {
          $alarm_report_datetime_cr =  $thisreport[$j]['alarm_report_datetime_cr'];
        }else {
          $alarm_report_datetime_cr = "";
        }

        if (isset($thisreport[$j]['alarm_report_truefalse_up'])) {
          $alarm_report_truefalse_up =  $thisreport[$j]['alarm_report_truefalse_up'];
        }else {
          $alarm_report_truefalse_up = "";
        }

        if (isset($thisreport[$j]['alarm_report_note_up'])) {
          $alarm_report_note_up =  $thisreport[$j]['alarm_report_note_up'];
        }else {
          $alarm_report_note_up = "";
        }

                array_push($datafix, array(
                    "alarm_report_id"                       => $thisreport[$j]['alarm_report_id'],
                    "alarm_report_vehicle_id"               => $thisreport[$j]['alarm_report_vehicle_id'],
                    "alarm_report_vehicle_no"               => $thisreport[$j]['alarm_report_vehicle_no'],
                    "alarm_report_vehicle_name"             => $thisreport[$j]['alarm_report_vehicle_name'],
                    "alarm_report_name"                     => $alarmreportnamefix,
                    "alarm_report_start_time"               => $thisreport[$j]['alarm_report_start_time'],
                    "alarm_report_end_time"                 => $thisreport[$j]['alarm_report_end_time'],
                    "alarm_report_coordinate_start"         => $thisreport[$j]['alarm_report_coordinate_start'],
                    "alarm_report_coordinate_end"           => $thisreport[$j]['alarm_report_coordinate_end'],
                    "alarm_report_location_start"           => $thisreport[$j]['alarm_report_location_start'],
                    "alarm_report_speed" 			              => $thisreport[$j]['alarm_report_speed'],
                    "alarm_report_speed_time" 		          => $thisreport[$j]['alarm_report_speed_time'],
                    "alarm_report_speed_status" 	          => $thisreport[$j]['alarm_report_speed_status'],
                    "alarm_report_jalur" 	                  => $thisreport[$j]['alarm_report_jalur'],
                    "alarm_report_id_cr"                    => $alarm_report_id_cr,
                    "alarm_report_name_cr"                  => $alarm_report_name_cr,
                    "alarm_report_sid_cr"                   => $alarm_report_sid_cr,
                    "alarm_report_statusintervention_cr"    => $alarm_report_statusintervention_cr,
                    "alarm_report_intervention_category_cr" => $alarm_report_intervention_category_cr,
                    "alarm_report_fatiguecategory_cr"       => $alarm_report_fatiguecategory_cr,
                    "alarm_report_note_cr"                  => $alarm_report_note_cr,
                    "alarm_report_datetime_cr"              => $alarm_report_datetime_cr,
                    "alarm_report_truefalse_up"             => $alarm_report_truefalse_up,
                    "alarm_report_note_up"                  => $alarm_report_note_up,
                    ));
        }

    // echo "<pre>";
    // var_dump($datafix);die();
    // echo "<pre>";

        $this->params['content']   = $datafix;
    $this->params['alarmtype'] = $alarmtype;
        $html                      = $this->load->view('newdashboard/dashboardberau/postevent/v_dashboard_postevent_result', $this->params, true);
        $callback["html"]          = $html;
        $callback["report"]        = $datafix;

        echo json_encode($callback);
    }

    function search_violation()
    {
        ini_set('memory_limit', "5G");
		ini_set('max_execution_time', 300); // 5 minutes
		// $datein    = $this->input->post("date");
		$company = $this->input->post("company");
		$violation = $this->input->post("violation");
		// $date = date("Y-m-d", strtotime($datein));
		// $month = date("F", strtotime($datein));
		// $monthforparam = date("m", strtotime($datein));
		// $year = date("Y", strtotime($datein));
		$report     = "alarm_evidence_";
		$overspeed  = "overspeed_";
		$periode = $this->input->post("periode");
		$year = date("Y");
		$mont = date("m");
		$nowday = date("d");
		$err = false;
		$msg = '';
		if ($periode == "today") {
			$sdate = date("Y-m-d 00:00:00");
			$edate = date("Y-m-d H:i:s");
			$datein = date("d-m-Y", strtotime($sdate));
		} else if ($periode == "yesterday") {
			$sdate = date("Y-m-d 00:00:00", strtotime("yesterday"));
			$edate = date("Y-m-d 23:59:59", strtotime("yesterday"));
			$datein = date("d-m-Y", strtotime("yesterday"));
		} else if ($periode == "last7") {
			$year = date("Y");
			$mont = date("m");
			$nowday = date("d");
			$firstday = $nowday - 7;
			if ($nowday <= 7) {
				$firstday = 1;
			}
			$sdate = date("Y-m-d 00:00:00", strtotime($year . "-" . $mont . "-" . $firstday));
			$edate = date("Y-m-d 23:59:59", strtotime($year . "-" . $mont . "-" . $nowday));
			$datein = date("d-m-Y", strtotime($sdate)) . " s.d. " . date("d-m-Y", strtotime($edate));
		} else if ($periode == "this_month") {
			$firstday = "1";
			$sdate = date("Y-m-d 00:00:00", strtotime($year . "-" . $mont . "-1"));
			$edate = date("Y-m-d 23:59:59", strtotime($year . "-" . $mont . "-" . $nowday));
			$datein = date("d-m-Y", strtotime($sdate)) . " s.d. " . date("d-m-Y", strtotime($edate));
		} else if ($periode == "custom") {
			$sdate = $this->input->post("sdate");
			$edate = $this->input->post("edate");
			$sdate = date("Y-m-d 00:00:00", strtotime($sdate));
			$edate = date("Y-m-d 23:59:59", strtotime($edate));
			$datein = date("d-m-Y", strtotime($sdate)) . " s.d. " . date("d-m-Y", strtotime($edate));
			$diff = strtotime($edate) - strtotime($sdate);
			if ($diff < 0) {
				$err = true;
				$msg = "Date is not correct!";
			}
			$diff = strtotime(date("Y-m-d")) - strtotime($sdate);
			if ($diff < 0) {
				$err = true;
				$msg = "Date is not correct!";
			}
			if ($company == "all") {
				$diff = strtotime($edate) - strtotime($sdate);
				if ($diff > 604800) {
					$err = true;
					$msg = "Maximum date range for all contractors is 7 days!";
				}
			}
			$diff1 = date("m", strtotime($sdate));
			$diff2 = date("m", strtotime($edate));
			if ($diff1 != $diff2) {
				$err = true;
				$msg = "Date must be in the same month!";
			}
			$diff1 = date("Y", strtotime($sdate));
			$diff2 = date("Y", strtotime($edate));
			if ($diff1 != $diff2) {
				$err = true;
				$msg = "Date must be in the same year!";
			}
		}

		$month = date("F", strtotime($sdate));
		$year = date("Y", strtotime($sdate));

		if ($err == true) {
			$callback['error'] = true;
			$callback['message'] = $msg;
			echo json_encode($callback);
			return;
		}

		switch ($month) {
			case "January":
				$dbtable = $report . "januari_" . $year;
				$dboverspeed = $overspeed . "januari_" . $year;
				break;
			case "February":
				$dbtable = $report . "februari_" . $year;
				$dboverspeed = $overspeed . "februari_" . $year;
				break;
			case "March":
				$dbtable = $report . "maret_" . $year;
				$dboverspeed = $overspeed . "maret_" . $year;
				break;
			case "April":
				$dbtable = $report . "april_" . $year;
				$dboverspeed = $overspeed . "april_" . $year;
				break;
			case "May":
				$dbtable = $report . "mei_" . $year;
				$dboverspeed = $overspeed . "mei_" . $year;
				break;
			case "June":
				$dbtable = $report . "juni_" . $year;
				$dboverspeed = $overspeed . "juni_" . $year;
				break;
			case "July":
				$dbtable = $report . "juli_" . $year;
				$dboverspeed = $overspeed . "juli_" . $year;
				break;
			case "August":
				$dbtable = $report . "agustus_" . $year;
				$dboverspeed = $overspeed . "agustus_" . $year;
				break;
			case "September":
				$dbtable = $report . "september_" . $year;
				$dboverspeed = $overspeed . "september_" . $year;
				break;
			case "October":
				$dbtable = $report . "oktober_" . $year;
				$dboverspeed = $overspeed . "oktober_" . $year;
				break;
			case "November":
				$dbtable = $report . "november_" . $year;
				$dboverspeed = $overspeed . "november_" . $year;
				break;
			case "December":
				$dbtable = $report . "desember_" . $year;
				$dboverspeed = $overspeed . "desember_" . $year;
				break;
		}

		$input = array(
			'date_start' => $sdate,
			'date_end' => $edate,
			'db' => $dbtable,
			'db_overspeed' => $dboverspeed
		);
		//violation data

		$rviolation = $this->getViolation(); //ambil master data violation alrmmaster
		$dataviolation = array(); //untuk simpan data violation
		$master_violation = array(); //untuk simpan data violation format 2
		$allviolation = array(); //untuk simpan id violation
		$nr = count($rviolation);
		if ($nr > 0) {
			for ($i = 0; $i < $nr; $i++) {
				$dataviolation[$rviolation[$i]["alarmmaster_id"]] = $rviolation[$i]["alarmmaster_name"];
				$master_violation[$i] = $rviolation[$i]["alarmmaster_name"];
				array_push($allviolation, $rviolation[$i]["alarmmaster_id"]);
			}
		}

		$dataalarmtype = array(); //untuk query where_in
		$dataviolationalarmtype = array(); //untuk ditampilkan di view
		if ($violation != "all") {
			$alarmtype = $this->getAlarmtype($violation);
		} else {
			$alarmtype = $this->getAlarmtype(null, $allviolation);
		}
		$nr = count($alarmtype);
		if ($nr > 0) {
			for ($i = 0; $i < $nr; $i++) {
				if (!isset($dataviolationalarmtype[$alarmtype[$i]["alarm_type"]])) {
					if ($violation == "all") {
						$dataviolationalarmtype[$alarmtype[$i]["alarm_type"]] = $dataviolation[$alarmtype[$i]["alarm_master_id"]];
					} else {
						$dataviolationalarmtype[$alarmtype[$i]["alarm_type"]] = $dataviolation[$violation];
					}
				}
				array_push($dataalarmtype, $alarmtype[$i]["alarm_type"]);
			}
		}
    }

	function search_bk()
    {
        $company = $this->input->post('company');
        $location = $this->input->post('location');
        $datein = $this->input->post('date');
        $shift = $this->input->post('shift');
        $date = date("Y-m-d", strtotime($datein));
        if (date("Y-m-d") < $date) {
            echo json_encode(array("code" => 200, "error" => true, "msg" => "Data Not Found", "total" => 0, "data" => array()));
            exit();
        }
        $lastdate = date("Y-m-t", strtotime($datein));
        $year = date("Y", strtotime($datein));
        $month = date("m", strtotime($datein));
        $day = date('d', strtotime($datein));
        $day++;
        $jmlday = strlen($day);
        if ($jmlday == 1) {
            $day = "0" . $day;
        }
        $next = $year . "-" . $month . "-" . $day;

        if ($next > $lastdate) {
            if ($month == 12) {
                $y = $year + 1;
                $next = $y . "-01-01";
            } else {
                $m = $month + 1;
                $jmlmonth = strlen($m);
                if ($jmlmonth == 1) {
                    $m = "0" . $m;
                }
                $next = $year . "-" . $m . "-01";
            }
        }
        $arraydate = array("date" => $date, "next date" => $next, "last date" => $lastdate);

        $shift1 = array("06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17");
        $shift21 = array("18", "19", "20", "21", "22", "23");
        $shift22 = array("00", "01", "02", "03", "04", "05");
        $allshift1 = array("06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23");
        $allshift2 = array("00", "01", "02", "03", "04", "05");

        //get vehicle info
        $this->db->select("vehicle_name,vehicle_no,company_name");
        $this->db->order_by("company_name", "asc");
        $this->db->where("vehicle_status <>", 3);

        if ($company != 0) {
            $this->db->where("vehicle_company", $company);
        }
		$this->db->where("vehicle_user_id", 4408);
        $this->db->join("company", "vehicle_company = company_id", "left");
        $qd = $this->db->get("vehicle");
        $total_unit = $qd->num_rows();
        $rd = $qd->result();
        $total_unit_percontractor = array();
        if ($company == 0) {
            for ($x = 0; $x < $total_unit; $x++) {
                if ($rd[$x]->company_name != null) {
                    if (!isset($total_unit_percontractor[$rd[$x]->company_name])) {
                        $total_unit_percontractor[$rd[$x]->company_name] = 1;
                    } else {
                        $jml = (int)$total_unit_percontractor[$rd[$x]->company_name] + 1;
                        $total_unit_percontractor[$rd[$x]->company_name] = $jml;
                    }
                }
            }
        }
        //end get vehicle info
        //location selected
        $this->dbts = $this->load->database("webtracking_ts", true);

        $this->db->order_by("location_report_location", "asc");
        if ($location == "STREET.0") {
            $this->dbts->where("location_report_group", "STREET");
            $this->dbts->where("location_report_jalur", "kosongan");
        } else if ($location == "STREET.1") {
            $this->dbts->where("location_report_group", "STREET");
            $this->dbts->where("location_report_jalur", "muatan");
        } else if ($location == "0") {
            $this->dbts->where_in("location_report_group", array("STREET", "ROM", "PORT"));
        } else {
            $exp = explode(" ", $location);
            if ($exp[0] == "ROM") {
                //lokasi ROM
                $this->dbts->where("location_report_location", $location);
            } else if ($exp[0] == "PORT") {
                //lokasi PORT
                $this->dbts->where("location_report_group", "PORT");
                $this->dbts->where("location_report_location LIKE ", "%" . $exp[1] . "%");
            }
        }

        if ($shift == 1) {
            for ($s = 0; $s < count($shift1); $s++) {
                $shift1[$s] .= ":00:00";
            }
            $this->dbts->select("location_report_vehicle_no,location_report_company_name,location_report_gps_date,location_report_gps_hour,location_report_location,location_report_group");
            $this->dbts->where("location_report_gps_date", $date);
            if ($company != 0) {
                $this->dbts->where("location_report_vehicle_company", $company);
            }
            $this->dbts->where_in("location_report_gps_hour", $shift1);
            $this->dbts->order_by("location_report_gps_hour", "asc");
            $this->dbts->order_by("location_report_company_name", "asc");
            $result = $this->dbts->get("ts_location_hour");
            $data = $result->result_array();
            $nr = $result->num_rows();
        } else if ($shift == 2) {
            for ($s = 0; $s < count($shift21); $s++) {
                $shift21[$s] .= ":00:00";
            }
            for ($s = 0; $s < count($shift22); $s++) {
                $shift22[$s] .= ":00:00";
            }
            $this->dbts->select("location_report_vehicle_no,location_report_company_name,location_report_gps_date,location_report_gps_hour,location_report_location,location_report_group");
            $this->dbts->where("location_report_gps_date", $date);
            if ($company != 0) {
                $this->dbts->where("location_report_vehicle_company", $company);
            }
            $this->dbts->where_in("location_report_gps_hour", $shift21);
            $this->dbts->order_by("location_report_gps_hour", "asc");
            $this->dbts->order_by("location_report_company_name", "asc");
            $result = $this->dbts->get("ts_location_hour");
            $data1 = $result->result_array();
            $nr1 = $result->num_rows();
            $this->dbts->distinct();
            $this->db->order_by("location_report_location", "asc");
            if ($location == "STREET.0") {
                $this->dbts->where("location_report_group", "STREET");
                $this->dbts->where("location_report_jalur", "kosongan");
            } else if ($location == "STREET.1") {
                $this->dbts->where("location_report_group", "STREET");
                $this->dbts->where("location_report_jalur", "muatan");
            } else if ($location == "0") {
                $this->dbts->where_in("location_report_group", array("STREET", "ROM", "PORT"));
            } else {
                $exp = explode(" ", $location);
                if ($exp[0] == "ROM") {
                    //lokasi ROM
                    $this->dbts->where("location_report_location", $location);
                } else if ($exp[0] == "PORT") {
                    //lokasi PORT
                    $this->dbts->where("location_report_group", "PORT");
                    $this->dbts->where("location_report_location LIKE ", "%" . $exp[1] . "%");
                }
            }
            $this->dbts->select("location_report_vehicle_no,location_report_company_name,location_report_gps_date,location_report_gps_hour,location_report_location,location_report_group");
            $this->dbts->where("location_report_gps_date", $next);
            if ($company != 0) {
                $this->dbts->where("location_report_vehicle_company", $company);
            }
            $this->dbts->where_in("location_report_gps_hour", $shift22);
            $this->dbts->order_by("location_report_gps_hour", "asc");
            $this->dbts->order_by("location_report_company_name", "asc");
            $result = $this->dbts->get("ts_location_hour");
            $data2 = $result->result_array();
            $nr2 = $result->num_rows();
            $data = array_merge($data1, $data2);
            $nr = $nr1 +  $nr2;
        } else {

            for ($s = 0; $s < count($allshift1); $s++) {
                $allshift1[$s] .= ":00:00";
            }
            for ($s = 0; $s < count($allshift2); $s++) {
                $allshift2[$s] .= ":00:00";
            }
            $this->dbts->select("location_report_vehicle_no,location_report_company_name,location_report_gps_date,location_report_gps_hour,location_report_location,location_report_group");
            $this->dbts->where("location_report_gps_date", $date);
            if ($company != 0) {
                $this->dbts->where("location_report_vehicle_company", $company);
            }
            $this->dbts->where_in("location_report_gps_hour", $allshift1);
            $this->dbts->order_by("location_report_gps_hour", "asc");
            $this->dbts->order_by("location_report_company_name", "asc");
            $result = $this->dbts->get("ts_location_hour");
            $data1 = $result->result_array();
            $nr1 = $result->num_rows();
            $this->dbts->distinct();
            $this->db->order_by("location_report_location", "asc");
            if ($location == "STREET.0") {
                $this->dbts->where("location_report_group", "STREET");
                $this->dbts->where("location_report_jalur", "kosongan");
            } else if ($location == "STREET.1") {
                $this->dbts->where("location_report_group", "STREET");
                $this->dbts->where("location_report_jalur", "muatan");
            } else if ($location == "0") {
                $this->dbts->where_in("location_report_group", array("STREET", "ROM", "PORT"));
            } else {
                $exp = explode(" ", $location);
                if ($exp[0] == "ROM") {
                    //lokasi ROM
                    $this->dbts->where("location_report_location", $location);
                } else if ($exp[0] == "PORT") {
                    //lokasi PORT
                    $this->dbts->where("location_report_group", "PORT");
                    $this->dbts->where("location_report_location LIKE ", "%" . $exp[1] . "%");
                }
            }
            $this->dbts->select("location_report_vehicle_no,location_report_company_name,location_report_gps_date,location_report_gps_hour,location_report_location,location_report_group");
            $this->dbts->where("location_report_gps_date", $next);
            if ($company != 0) {
                $this->dbts->where("location_report_vehicle_company", $company);
            }
            $this->dbts->where_in("location_report_gps_hour", $allshift2);
            $this->dbts->order_by("location_report_gps_hour", "asc");
            $this->dbts->order_by("location_report_company_name", "asc");
            $result = $this->dbts->get("ts_location_hour");
            $data2 = $result->result_array();
            $nr2 = $result->num_rows();
            $data = array_merge($data1, $data2);
            $nr = $nr1 +  $nr2;
        }

        if ($nr > 0) {
            $data_fix = array();
            $dhour = array();
            $dcompany = array();
            $dlocation = array();
            $c = array();
            $l = array();
            for ($i = 0; $i < $nr; $i++) {
                $exp = explode(":", $data[$i]['location_report_gps_hour']);

                if (!isset($hour[$exp[0]])) {
                    $hour[$exp[0]] = 1;
                    $dhour[] = $exp[0];
                }
                if (!isset($c[$data[$i]['location_report_company_name']])) {

                    $c[$data[$i]['location_report_company_name']] = $data[$i]['location_report_company_name'];
                    $dcompany[] = $c[$data[$i]['location_report_company_name']];
                }
                if (!isset($l[$data[$i]['location_report_location']])) {

                    $l[$data[$i]['location_report_location']] = $data[$i]['location_report_location'];
                    $dlocation[] = $l[$data[$i]['location_report_location']];
                }
                // $data_fix[$exp[0]][$data[$i]['location_report_company_name']][$data[$i]['location_report_location']] = array("vehicle" => $data[$i]['location_report_vehicle_no']);
                if (!isset($data_fix[$exp[0]][$data[$i]['location_report_company_name']])) {
                    $data_fix[$exp[0]][$data[$i]['location_report_company_name']] = array();
                    $d = array(
                        "company" => $data[$i]['location_report_company_name'],
                        "vehicle" => $data[$i]['location_report_vehicle_no'],
                        "location" => $data[$i]['location_report_location'],
                        "hour" => $exp[0]
                    );
                    array_push($data_fix[$exp[0]][$data[$i]['location_report_company_name']], $d);
                } else {
                    $d = array(
                        "company" => $data[$i]['location_report_company_name'],
                        "vehicle" => $data[$i]['location_report_vehicle_no'],
                        "location" => $data[$i]['location_report_location'],
                        "hour" => $exp[0]
                    );
                    array_push($data_fix[$exp[0]][$data[$i]['location_report_company_name']], $d);
                }
                if (!isset($unit_location[$data[$i]['location_report_vehicle_no']])) {
                    $unit_location[$data[$i]['location_report_vehicle_no']] = $data[$i]['location_report_vehicle_no'];
                }
                $data[$i]['hour'] = $exp[0];
            }

            echo json_encode(array(
                "code" => 200,
                "error" => false,
                "msg" => "success",
                "data" => $data, //data mentah dari source data untuk compare data fix
                "total_unit_location" => count($unit_location), //total unit dilokasi terpilih
                "total_unit" => $total_unit, //total unit semua kontraktor atau perkontraktor dipilih
                "total_unit_per_contractor" => $total_unit_percontractor, //total unit tiap kontraktor
                "data_hour" => $dhour, //data jam
                "data_company" => $dcompany, //data kontraktor
                "data_location" => $dlocation, //data lokasi
                "data_fix" => $data_fix, //data fix
                "length_company" => count($dcompany), //jumlah kontraktor
                "length_location" => count($dlocation), //jumlah lokasi
                "length_hour" => count($dhour) //jumlah jam
            ));
        } else {
            echo json_encode(array("code" => 200, "error" => true, "msg" => "Data Not Found", "data" => $data, "total" => $nr));
        }
    }

	function month()
	{

		if (!isset($this->sess->user_type)) {
            redirect(base_url());
        }

        $privilegecode = $this->sess->user_id_role;

        $rows_company                   = $this->get_company();

        $this->params["rcompany"]       = $rows_company;
        $this->params["rlocation"]      = $this->get_location();

        $this->params["header"]         = $this->load->view('newdashboard/partial/headernew', $this->params, true);
        $this->params["chatsidebar"]    = $this->load->view('newdashboard/partial/chatsidebar', $this->params, true);
        $this->params["onload"]         = 1;
        if ($privilegecode == 1) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_superuser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_superuser", $this->params);
        } elseif ($privilegecode == 2) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_managementuser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_managementuser", $this->params);
        } elseif ($privilegecode == 3) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_reguleruser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_reguleruser", $this->params);
        } elseif ($privilegecode == 4) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_teknikaluser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_teknikaluser", $this->params);
        } elseif ($privilegecode == 5) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_adminpjo', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_adminpjo", $this->params);
        } elseif ($privilegecode == 6) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_userpjo', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_userpjo", $this->params);
        } else {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_new", $this->params);
        }

	}

	function searchmonth()
	{
		ini_set('display_errors', 1);
		ini_set('memory_limit', '2G');
		$this->params['code_view_menu'] = "report";

		$userid = 4408;
		$startdate  = $this->input->post("startdate");
		$enddate    = $this->input->post("enddate");
		$periode    = $this->input->post("periode");

		$shift = $this->input->post("shift");
		$company = $this->input->post("contractor");
		$error = "";


		/* if($company != "all"){

			$callback['error'] = true;
			$callback['message'] = "Data Per Contractor belum tersedia!";

			echo json_encode($callback);
			return;
		} */


		$shour = "00:00:00";
		$ehour = "23:59:59";

		$nowdate = date("Y-m-d");
		$nowday = date("d");
		$nowmonth = date("m");
		$nowyear = date("Y");
		$lastday = date("t");

		if($periode == "custom"){
			$sdate = date("Y-m-d H:i:s", strtotime($startdate." ".$shour));
			$edate = date("Y-m-d H:i:s", strtotime($enddate." ".$ehour));
		}else if($periode == "yesterday"){

			$sdate = date("Y-m-d 00:00:00", strtotime("yesterday"));
			$edate = date("Y-m-d 23:59:59", strtotime("yesterday"));

		}else if($periode == "last7"){
			$nowday = $nowday - 1;
			$firstday = $nowday - 7;
			if($nowday <= 7){
				$firstday = 1;
			}

			/*if($firstday > $nowday){
				$firstday = 1;
			}*/

			$sdate = date("Y-m-d H:i:s ", strtotime($nowyear."-".$nowmonth."-".$firstday." "."00:00:00"));
			$edate = date("Y-m-d H:i:s", strtotime($nowyear."-".$nowmonth."-".$nowday." "."23:59:59"));

		}
		else if($periode == "last30"){
			$firstday = "1";
			$sdate = date("Y-m-d H:i:s ", strtotime($nowyear."-".$nowmonth."-".$firstday." "."00:00:00"));
			$edate = date("Y-m-d H:i:s", strtotime($nowyear."-".$nowmonth."-".$lastday." "."23:59:59"));
		}
		else{
			$sdate = date("Y-m-d H:i:s", strtotime($startdate." ".$shour));
			$edate = date("Y-m-d H:i:s", strtotime($enddate." ".$ehour));
		}


		$sdate_show = date("d-m-Y", strtotime($sdate));
		$edate_show = date("d-m-Y", strtotime($edate));
		$periode_show = "PERIODE: ".$sdate_show." to ".$edate_show;
		$periode_show_percompany = $periode_show;

		//print_r($sdate." ".$edate);exit();
		$m1 = date("F", strtotime($sdate));
		$m2 = date("F", strtotime($edate));
		$year = date("Y", strtotime($sdate));
		$year2 = date("Y", strtotime($edate));

		if($m2 != $m1){

			$callback['error'] = true;
			$callback['message'] = "Periode dapat dipilih dalam bulan yang sama!";

			echo json_encode($callback);
			return;
		}

		if($year != $year2){

			$callback['error'] = true;
			$callback['message'] = "Periode dapat dipilih dalam tahun yang sama!";

			echo json_encode($callback);
			return;
		}


		$company_name = $this->getcompanyname_byID($company);
		$total_unit = $this->gettotalvehicle_bycompany($company);

		//exit();
		/* $this->dbts = $this->load->database("webtracking_ts", true);
		$this->dbts->select("hour_name,hour_time");
		if($shift != "all"){
			$this->dbts->where("hour_shift", $shift);
		}
        $this->dbts->where("hour_flag", 0);
        $result = $this->dbts->get("ts_hour_shift");
        $datahour = $result->result_array(); */



		$sdate_tgl = date('Y-m-d', strtotime($sdate));
		$edate_tgl = date('Y-m-d', strtotime($edate));
		$month = date("m", strtotime($sdate_tgl));
		$year = date("Y", strtotime($sdate_tgl));
		$begin = new DateTime($sdate_tgl);
		$end = new DateTime($edate_tgl);

		//config monthly report
		$this->dbts = $this->load->database("tensor_report", true);
		$this->dbts->order_by("monthly_date","asc");
		$this->dbts->select("monthly_date");
		$this->dbts->where("monthly_date >=", $sdate_tgl);
		$this->dbts->where("monthly_date <=", $edate_tgl);
        $resultdate = $this->dbts->get("ts_config_monthly_report");
        $datadate = $resultdate->result();


		//print_r(print_r($data_fix));exit();

		$this->dbts = $this->load->database("tensor_report", true);
		//$this->dbts->select("hour_name,hour_time");
		if($shift != "all"){
			$this->dbts->where("hour_shift", $shift);
		}
        $this->dbts->where("hour_flag", 0);
		$this->dbts->where("hour_month",$month);
		$this->dbts->where("hour_year",$year);
		$this->dbts->where("hour_company",$company);
        $result = $this->dbts->get("alarm_evidence");
        $datahour = $result->result();

		//print_r($datahour);exit();

		$this->params["data"]  = $datahour;
		$this->params["startdate"]  = $sdate_show;
		$this->params["enddate"]  = $edate_show;

		$this->params["sdate"]  = $sdate;
		$this->params["edate"]  = $edate;
		$this->params["company"]  = $company;


		$this->params["datadate"]  = $datadate;
		$this->params["company_name"]  = $company_name;
		$this->params["total_unit"]  = $total_unit;

		$html                    = $this->load->view('newdashboard/controlroom/v_controlroom_result', $this->params, true);
		$callback["html"]        = $html;

		echo json_encode($callback);
	}

    function hourdev1()
    {
        if (!isset($this->sess->user_type)) {
            redirect(base_url());
        }

        $privilegecode = $this->sess->user_id_role;

        $rows_company                   = $this->get_company();

        $this->params["rcompany"]       = $rows_company;
        $this->params["rlocation"]      = $this->get_location();

        $this->params["header"]         = $this->load->view('newdashboard/partial/headernew', $this->params, true);
        $this->params["chatsidebar"]    = $this->load->view('newdashboard/partial/chatsidebar', $this->params, true);
        $this->params["onload"]         = 1;
        if ($privilegecode == 1) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_superuser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_superuser", $this->params);
        } elseif ($privilegecode == 2) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_managementuser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_managementuser", $this->params);
        } elseif ($privilegecode == 3) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_reguleruser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_reguleruser", $this->params);
        } elseif ($privilegecode == 4) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_teknikaluser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_teknikaluser", $this->params);
        } elseif ($privilegecode == 5) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_adminpjo', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_adminpjo", $this->params);
        } elseif ($privilegecode == 6) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_userpjo', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_userpjo", $this->params);
        } else {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_new", $this->params);
        }
    }

    // USER FUNCTION START
    function hourv1()
    {
        if (!isset($this->sess->user_type)) {
            redirect(base_url());
        }

        $privilegecode = $this->sess->user_id_role;
        $rstatus                        = $this->dashboardmodel->gettotalstatus($this->sess->user_id);

        $rows_company                   = $this->get_company();
        // $datastatus                     = explode("|", $rstatus);
        // $this->params['total_online']   = $datastatus[0] + $datastatus[1]; //p + K
        // $this->params['total_vehicle']  = $datastatus[3];
        // $this->params['total_offline']  = $datastatus[2];
        $this->params["rcompany"]       = $rows_company;

        $this->params["header"]         = $this->load->view('newdashboard/partial/headernew', $this->params, true);
        $this->params["chatsidebar"]    = $this->load->view('newdashboard/partial/chatsidebar', $this->params, true);
        $this->params["onload"]         = 1;
        if ($privilegecode == 1) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_superuser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_superuser", $this->params);
        } elseif ($privilegecode == 2) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_managementuser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_managementuser", $this->params);
        } elseif ($privilegecode == 3) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_reguleruser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_truck_hour_v1', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_reguleruser", $this->params);
        } elseif ($privilegecode == 4) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_teknikaluser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_truck_hour_v1', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_teknikaluser", $this->params);
        } elseif ($privilegecode == 5) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_adminpjo', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_adminpjo", $this->params);
        } elseif ($privilegecode == 6) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_userpjo', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_truck_hour_v1', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_userpjo", $this->params);
        } else {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_new", $this->params);
        }
    }


    function searchv1()
    {
        $company = $this->input->post('company');
        $datein = $this->input->post('date');
        $shift = $this->input->post('shift');
        $date = date("Y-m-d", strtotime($datein));
        $lastdate = date("Y-m-t", strtotime($datein));
        $year = date("Y", strtotime($datein));
        $month = date("m", strtotime($datein));
        $day = date('d', strtotime($datein));
        $day++;
        $jmlday = strlen($day);
        if ($jmlday == 1) {
            $day = "0" . $day;
        }
        $next = $year . "-" . $month . "-" . $day;

        if ($next > $lastdate) {
            if ($month == 12) {
                $y = $year + 1;
                $next = $y . "-01-01";
            } else {
                $m = $month + 1;
                $jmlmonth = strlen($m);
                if ($jmlmonth == 1) {
                    $m = "0" . $m;
                }
                $next = $year . "-" . $m . "-01";
            }
        }
        $arraydate = array("date" => $date, "next date" => $next, "last date" => $lastdate);

        $this->db->select("vehicle_name,vehicle_no,company_name");
        // $this->db->group_by("vehicle_company");
        $this->db->order_by("company_name", "asc");
        $this->db->where("vehicle_status <>", 3);

        if ($company != 0) {
            $this->db->where("vehicle_company", $company);
        }
        $this->db->join("company", "vehicle_company = company_id", "left");
        $qd = $this->db->get("vehicle");
        $total_unit = $qd->num_rows();
        $rd = $qd->result();
        $total_unit_percontractor = array();
        if ($company == 0) {
            for ($x = 0; $x < $total_unit; $x++) {
                if ($rd[$x]->company_name != null) {
                    if (!isset($total_unit_percontractor[$rd[$x]->company_name])) {
                        $total_unit_percontractor[$rd[$x]->company_name] = 1;
                    } else {
                        $jml = (int)$total_unit_percontractor[$rd[$x]->company_name] + 1;
                        $total_unit_percontractor[$rd[$x]->company_name] = $jml;
                    }
                }
            }
        }


        // $dateym = date('Y-m-', $date);
        // $tomorrow = date('d', $date) + 1;
        // $nextday = $dateym . $tomorrow;
        $this->dbts = $this->load->database("webtracking_ts", true);
        $this->dbts->select("*");
        if ($shift == 1) {
            $shift = array("06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17");
            $this->dbts->where("autocheck_date", $date);
            if ($company != 0) {
                $this->dbts->where("autocheck_company", $company);
            }
            $this->dbts->where_in("autocheck_hour", $shift);
            $this->dbts->order_by("autocheck_hour", "asc");
            $this->dbts->order_by("autocheck_company_name", "asc");
            $result = $this->dbts->get("ts_autocheck_hour");
            $data = $result->result_array();
            $nr = $result->num_rows();
        } else if ($shift == 2) {
            $shift1 = array("18", "19", "20", "21", "22", "23");
            $shift2 = array("00", "01", "02", "03", "04", "05");
            $this->dbts->where("autocheck_date", $date);
            if ($company != 0) {
                $this->dbts->where("autocheck_company", $company);
            }
            $this->dbts->where_in("autocheck_hour", $shift1);
            $this->dbts->order_by("autocheck_hour", "asc");
            $this->dbts->order_by("autocheck_company_name", "asc");
            $result = $this->dbts->get("ts_autocheck_hour");
            $data1 = $result->result_array();
            $nr1 = $result->num_rows();
            $this->dbts->distinct();
            $this->dbts->where("autocheck_date", $next);
            if ($company != 0) {
                $this->dbts->where("autocheck_company", $company);
            }
            $this->dbts->where_in("autocheck_hour", $shift2);
            $this->dbts->order_by("autocheck_hour", "asc");
            $this->dbts->order_by("autocheck_company_name", "asc");
            $result = $this->dbts->get("ts_autocheck_hour");
            $data2 = $result->result_array();
            $nr2 = $result->num_rows();
            $data = array_merge($data1, $data2);
            $nr = $nr1 +  $nr2;
        } else {
            $shift1 = array("06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23");
            $shift2 = array("00", "01", "02", "03", "04", "05");
            $this->dbts->where("autocheck_date", $date);
            if ($company != 0) {
                $this->dbts->where("autocheck_company", $company);
            }
            $this->dbts->where_in("autocheck_hour", $shift1);
            $this->dbts->order_by("autocheck_hour", "asc");
            $this->dbts->order_by("autocheck_company_name", "asc");
            $result = $this->dbts->get("ts_autocheck_hour");
            $data1 = $result->result_array();
            $nr1 = $result->num_rows();
            $this->dbts->distinct();
            $this->dbts->where("autocheck_date", $next);
            if ($company != 0) {
                $this->dbts->where("autocheck_company", $company);
            }
            $this->dbts->where_in("autocheck_hour", $shift2);
            $this->dbts->order_by("autocheck_hour", "asc");
            $this->dbts->order_by("autocheck_company_name", "asc");
            $result = $this->dbts->get("ts_autocheck_hour");
            $data2 = $result->result_array();
            $nr2 = $result->num_rows();
            $data = array_merge($data1, $data2);
            $nr = $nr1 +  $nr2;
        }
        // $vehicle = $this->dashboardmodel->getvehicle_report();
        // $totalvehicle = array();
        $company = array();
        $hour = array();
        $result = array();
        $c = array();
        // $cid = array();
        $h = array();
        if ($nr > 0) {
            for ($i = 0; $i < $nr; $i++) {
                if (!isset($company[$data[$i]['autocheck_company_name']])) {
                    $company[$data[$i]['autocheck_company_name']] = $data[$i]['autocheck_company_name'];
                    $c[] = $data[$i]['autocheck_company_name'];
                }
                if (!isset($hour[$data[$i]['autocheck_hour']])) {
                    $hour[$data[$i]['autocheck_hour']] = $data[$i]['autocheck_hour'];
                    $h[] = $data[$i]['autocheck_hour'];
                }
                $result[$data[$i]['autocheck_hour']][$data[$i]['autocheck_company_name']] = $data[$i]['autocheck_total_duty'] . " (" . $data[$i]['autocheck_total_duty_persen'] . "%)";
            }
        }
        // for ($i = 0; $i < $clength; $i++) {
        //     for($j=0; $j<count($vehicle); $i++){
        //         if (!isset($totalvehicle[$cid[$i]]));
        //     }
        // }

        // $total_unit_percontractor = $c;


        echo json_encode(array("code" => 200, "msg" => "success", "data" => $result, "company" => $c, "hour" => $h, "total" => $nr, "clength" => count($company), "hlength" => count($hour), "date" => $arraydate, "total_unit" => $total_unit, "vehicle" => $rd, "total_unit_contractor" => $total_unit_percontractor));
    }

    function pool()
    {
        if (!isset($this->sess->user_type)) {
            redirect(base_url());
        }

        $privilegecode = $this->sess->user_id_role;
        $rstatus                        = $this->dashboardmodel->gettotalstatus($this->sess->user_id);

        $rows_company                   = $this->get_company();
        // $datastatus                     = explode("|", $rstatus);
        // $this->params['total_online']   = $datastatus[0] + $datastatus[1]; //p + K
        // $this->params['total_vehicle']  = $datastatus[3];
        // $this->params['total_offline']  = $datastatus[2];
        $this->params["rcompany"]       = $rows_company;

        $this->params['code_view_menu'] = "dashboard";

        $this->params["header"]         = $this->load->view('newdashboard/partial/headernew', $this->params, true);
        $this->params["chatsidebar"]    = $this->load->view('newdashboard/partial/chatsidebar', $this->params, true);
        $this->params["onload"]         = 1;
        if ($privilegecode == 1) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_superuser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_superuser", $this->params);
        } elseif ($privilegecode == 2) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_managementuser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_managementuser", $this->params);
        } elseif ($privilegecode == 3) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_reguleruser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_reguleruser", $this->params);
        } elseif ($privilegecode == 4) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_teknikaluser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_teknikaluser", $this->params);
        } elseif ($privilegecode == 5) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_adminpjo', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_adminpjo", $this->params);
        } elseif ($privilegecode == 6) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_userpjo', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_userpjo", $this->params);
        } else {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_new", $this->params);
        }
    }

    function search_on_pool()
    {
        $company = $this->input->post('company');
        $datein = $this->input->post('date');
        $shift = $this->input->post('shift');
        $date = date("Y-m-d", strtotime($datein));
        $lastdate = date("Y-m-t", strtotime($datein));
        $year = date("Y", strtotime($datein));
        $month = date("m", strtotime($datein));
        $day = date('d', strtotime($datein));
        $day++;
        $jmlday = strlen($day);
        if ($jmlday == 1) {
            $day = "0" . $day;
        }
        $next = $year . "-" . $month . "-" . $day;

        if ($next > $lastdate) {
            if ($month == 12) {
                $y = $year + 1;
                $next = $y . "-01-01";
            } else {
                $m = $month + 1;
                $jmlmonth = strlen($m);
                if ($jmlmonth == 1) {
                    $m = "0" . $m;
                }
                $next = $year . "-" . $m . "-01";
            }
        }
        $arraydate = array("date" => $date, "next date" => $next, "last date" => $lastdate);

        $this->db->select("vehicle_name,vehicle_no,company_name");
        // $this->db->group_by("vehicle_company");
        $this->db->order_by("company_name", "asc");
        $this->db->where("vehicle_status <>", 3);
        if ($company != 0) {
            $this->db->where("vehicle_company", $company);
        }
        $this->db->join("company", "vehicle_company = company_id", "left");
        $qd = $this->db->get("vehicle");
        $total_unit = $qd->num_rows();
        $rd = $qd->result();
        $total_unit_percontractor = array();

        if ($company == 0) {
            for ($x = 0; $x < $total_unit; $x++) {
                if ($rd[$x]->company_name != null) {
                    if (!isset($total_unit_percontractor[$rd[$x]->company_name])) {
                        $total_unit_percontractor[$rd[$x]->company_name] = 1;
                    } else {
                        $jml = (int)$total_unit_percontractor[$rd[$x]->company_name] + 1;
                        $total_unit_percontractor[$rd[$x]->company_name] = $jml;
                    }
                }
            }
        }


        // $dateym = date('Y-m-', $date);
        // $tomorrow = date('d', $date) + 1;
        // $nextday = $dateym . $tomorrow;
        $this->dbts = $this->load->database("webtracking_ts", true);
        $this->dbts->select("*");
        if ($shift == 1) {
            $shift = array("06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17");
            $this->dbts->where("autocheck_date", $date);

            if ($company != 0) {
                $this->dbts->where("autocheck_company", $company);
            }

            $this->dbts->where_in("autocheck_hour", $shift);
            $this->dbts->order_by("autocheck_hour", "asc");
            $this->dbts->order_by("autocheck_company_name", "asc");
            $result = $this->dbts->get("ts_autocheck_hour_pool");
            $data = $result->result_array();
            $nr = $result->num_rows();
        } else if ($shift == 2) {
            $shift1 = array("18", "19", "20", "21", "22", "23");
            $shift2 = array("00", "01", "02", "03", "04", "05");
            $this->dbts->where("autocheck_date", $date);

            if ($company != 0) {
                $this->dbts->where("autocheck_company", $company);
            }

            $this->dbts->where_in("autocheck_hour", $shift1);
            $this->dbts->order_by("autocheck_hour", "asc");
            $this->dbts->order_by("autocheck_company_name", "asc");
            $result = $this->dbts->get("ts_autocheck_hour_pool");
            $data1 = $result->result_array();
            $nr1 = $result->num_rows();
            $this->dbts->distinct();
            $this->dbts->where("autocheck_date", $next);

            if ($company != 0) {
                $this->dbts->where("autocheck_company", $company);
            }

            $this->dbts->where_in("autocheck_hour", $shift2);
            $this->dbts->order_by("autocheck_hour", "asc");
            $this->dbts->order_by("autocheck_company_name", "asc");
            $result = $this->dbts->get("ts_autocheck_hour_pool");
            $data2 = $result->result_array();
            $nr2 = $result->num_rows();
            $data = array_merge($data1, $data2);
            $nr = $nr1 +  $nr2;
        } else {
            $shift1 = array("06", "07", "08", "09", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23");
            $shift2 = array("00", "01", "02", "03", "04", "05");
            $this->dbts->where("autocheck_date", $date);

            if ($company != 0) {
                $this->dbts->where("autocheck_company", $company);
            }

            $this->dbts->where_in("autocheck_hour", $shift1);
            $this->dbts->order_by("autocheck_hour", "asc");
            $this->dbts->order_by("autocheck_company_name", "asc");
            $result = $this->dbts->get("ts_autocheck_hour_pool");
            $data1 = $result->result_array();
            $nr1 = $result->num_rows();
            $this->dbts->distinct();
            $this->dbts->where("autocheck_date", $next);

            if ($company != 0) {
                $this->dbts->where("autocheck_company", $company);
            }

            $this->dbts->where_in("autocheck_hour", $shift2);
            $this->dbts->order_by("autocheck_hour", "asc");
            $this->dbts->order_by("autocheck_company_name", "asc");
            $result = $this->dbts->get("ts_autocheck_hour_pool");
            $data2 = $result->result_array();
            $nr2 = $result->num_rows();
            $data = array_merge($data1, $data2);
            $nr = $nr1 +  $nr2;
        }
        // $vehicle = $this->dashboardmodel->getvehicle_report();
        // $totalvehicle = array();
        $company = array();
        $hour = array();
        $result = array();
        $c = array();
        // $cid = array();
        $h = array();
        if ($nr > 0) {
            for ($i = 0; $i < $nr; $i++) {
                if (!isset($company[$data[$i]['autocheck_company_name']])) {
                    $company[$data[$i]['autocheck_company_name']] = $data[$i]['autocheck_company_name'];
                    $c[] = $data[$i]['autocheck_company_name'];
                }
                if (!isset($hour[$data[$i]['autocheck_hour']])) {
                    $hour[$data[$i]['autocheck_hour']] = $data[$i]['autocheck_hour'];
                    $h[] = $data[$i]['autocheck_hour'];
                }
                $result[$data[$i]['autocheck_hour']][$data[$i]['autocheck_company_name']] = $data[$i]['autocheck_total_parkir'] . " (" . $data[$i]['autocheck_total_parkir_persen'] . "%)";
            }
        }

        echo json_encode(array("code" => 200, "msg" => "success", "data" => $result, "company" => $c, "hour" => $h, "total" => $nr, "clength" => count($company), "hlength" => count($hour), "date" => $arraydate, "total_unit" => $total_unit, "vehicle" => $rd, "total_unit_contractor" => $total_unit_percontractor));
    }

    function summarynew()
    {
        if (!isset($this->sess->user_type)) {
            redirect(base_url());
        }

        $privilegecode = $this->sess->user_id_role;

        $rows_company                   = $this->get_company();

        $this->params["rcompany"]       = $rows_company;
        $this->params["rlocation"]      = $this->get_location();
        $this->params['code_view_menu'] = "dashboard";

        $this->params["header"]         = $this->load->view('newdashboard/partial/headernew', $this->params, true);
        $this->params["chatsidebar"]    = $this->load->view('newdashboard/partial/chatsidebar', $this->params, true);
        $this->params["onload"]         = 1;
        if ($privilegecode == 1) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_superuser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom_summary', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_superuser", $this->params);
        } elseif ($privilegecode == 2) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_managementuser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom_summary', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_managementuser", $this->params);
        } elseif ($privilegecode == 3) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_reguleruser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom_summary', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_reguleruser", $this->params);
        } elseif ($privilegecode == 4) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_teknikaluser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom_summary', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_teknikaluser", $this->params);
        } elseif ($privilegecode == 5) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_adminpjo', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom_summary', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_adminpjo", $this->params);
        } elseif ($privilegecode == 6) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_userpjo', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom_summary', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_userpjo", $this->params);
        } else {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom_summary', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_new", $this->params);
        }
    }

    function searchmonthsummarynew()
  	{
  		ini_set('display_errors', 1);
  		ini_set('memory_limit', '2G');
  		$this->params['code_view_menu'] = "report";

  		$userid    = 4408;
  		$startdate = $this->input->post("startdate");
  		$enddate   = $this->input->post("enddate");
  		$periode   = $this->input->post("periode");

  		// $shift     = $this->input->post("shift"); // AKTIFKAN SESUAI INSTRUKSI
      $shift     = 3;
  		$company   = $this->input->post("contractor");
  		$error     = "";

  		/* if($company != "all"){

  			$callback['error'] = true;
  			$callback['message'] = "Data Per Contractor belum tersedia!";

  			echo json_encode($callback);
  			return;
  		} */


  		$shour = "00:00:00";
  		$ehour = "23:59:59";

  		$nowdate = date("Y-m-d");
  		$nowday = date("d");
  		$nowmonth = date("m");
  		$nowyear = date("Y");
  		$lastday = date("t");

  		if($periode == "custom"){
        if ($shift == 1) {
          $sdate = date("Y-m-d H:i:s", strtotime($startdate." 06:00:00"));
    			$edate = date("Y-m-d H:i:s", strtotime($enddate." 18:00:00"));
        }elseif ($shift == 2) {
          $sdate = date("Y-m-d H:i:s", strtotime($startdate." 18:00:00"));
    			$edate = date("Y-m-d H:i:s", strtotime($enddate." 06:00:00"."+ 1 Day"));
        }else {
          // $sdate = date("Y-m-d H:i:s", strtotime($startdate." 00:00:00"));
    			// $edate = date("Y-m-d H:i:s", strtotime($enddate." 24:00:00"."+ 1 Day"));
          $sdate = date("Y-m-d H:i:s", strtotime($startdate." 06:00:00"));
    			$edate = date("Y-m-d H:i:s", strtotime($enddate." 24:00:00"."+ 1 Day"));
        }
  		}else if($periode == "yesterday"){
        if ($shift == 1) {
          $sdate = date("Y-m-d 06:00:00", strtotime("yesterday"));
          $edate = date("Y-m-d 18:00:00", strtotime("yesterday"));
        }elseif ($shift == 2) {
          $sdate = date("Y-m-d 18:00:00", strtotime("yesterday"));
          $edate = date("Y-m-d 06:00:00");
        }else {
          $sdate = date("Y-m-d 06:00:00", strtotime("yesterday"));
          $edate = date("Y-m-d 06:00:00");
        }

  		}else if($periode == "last7"){
  			$nowday = $nowday - 1;
  			$firstday = $nowday - 7;
  			if($nowday <= 7){
  				$firstday = 1;
  			}

        // echo "<pre>";
        // var_dump($nowday.'-'.$firstday);die();
        // echo "<pre>";

  			/*if($firstday > $nowday){
  				$firstday = 1;
  			}*/

        if ($shift == 1) {
          $sdate = date("Y-m-d H:i:s ", strtotime($nowyear."-".$nowmonth."-".$firstday." "."06:00:00"));
          $edate = date("Y-m-d H:i:s", strtotime($nowyear."-".$nowmonth."-".$nowday." "."18:00:00"));
        }elseif ($shift == 2) {
          $sdate = date("Y-m-d H:i:s ", strtotime($nowyear."-".$nowmonth."-".$firstday." "."18:00:00"));
    			$edate = date("Y-m-d H:i:s", strtotime($nowyear."-".$nowmonth."-".$nowday." "."06:00:00"));
        }else {
          $sdate = date("Y-m-d H:i:s ", strtotime($nowyear."-".$nowmonth."-".$firstday." "."06:00:00"));
    			$edate = date("Y-m-d H:i:s", strtotime($nowyear."-".$nowmonth."-".$nowday." "."24:00:00"."+ 1 Day"));
        }

  		}
  		else if($periode == "last30"){
  			$firstday = "1";
        if ($shift == 1) {
          $sdate = date("Y-m-d H:i:s ", strtotime($nowyear."-".$nowmonth."-".$firstday." "."06:00:00"));
    			$edate = date("Y-m-d H:i:s", strtotime($nowyear."-".$nowmonth."-".$lastday." "."18:00:00"));
        }elseif ($shift == 2) {
          $sdate = date("Y-m-d H:i:s ", strtotime($nowyear."-".$nowmonth."-".$firstday." "."18:00:00"));
          $edate = date("Y-m-d H:i:s", strtotime($nowyear."-".$nowmonth."-".$lastday." "."06:00:00"));
        }else {
          $sdate = date("Y-m-d H:i:s ", strtotime($nowyear."-".$nowmonth."-".$firstday." "."00:00:00"));
    			$edate = date("Y-m-d H:i:s", strtotime($nowyear."-".$nowmonth."-".$lastday." "."24:00:00"."+ 1 Day"));
        }


  		}
  		else{
  			$sdate = date("Y-m-d H:i:s", strtotime($startdate." ".$shour));
  			$edate = date("Y-m-d H:i:s", strtotime($enddate." ".$ehour));
  		}

      // print_r($sdate." ".$edate);exit();

      // KONDISI SHIFT
      // if ($shift == 1) {
      //   $sdate = date("Y-m-d H:i:s ", strtotime($nowyear."-".$nowmonth."-".$firstday." "."06:00:00"));
  		// 	$edate = date("Y-m-d H:i:s", strtotime($nowyear."-".$nowmonth."-".$nowday." "."18:00:00"));
      // }elseif ($shift == 2) {
      //   $sdate = date("Y-m-d H:i:s ", strtotime($nowyear."-".$nowmonth."-".$firstday." "."18:00:00"));
      //   $edate = date("Y-m-d H:i:s", strtotime($nowyear."-".$nowmonth."-".$nowday." "."06:00:00"));
      // }

  		$sdate_show              = date("d-m-Y", strtotime($sdate));
  		$edate_show              = date("d-m-Y", strtotime($edate."-2 Days"));
  		$periode_show            = "PERIODE: ".$sdate_show." to ".$edate_show;
  		$periode_show_percompany = $periode_show;

  		//print_r($sdate." ".$edate);exit();
  		$m1                      = date("F", strtotime($sdate));
  		$m2                      = date("F", strtotime($edate));
  		$year                    = date("Y", strtotime($sdate));
  		$year2                   = date("Y", strtotime($edate));

  		$dbtable   = "";
  		$report    = "location_hour_";

  		switch ($m1)
  		{
  			case "January":
  						$dbtable = $report."januari_".$year;
  			break;
  			case "February":
  						$dbtable = $report."februari_".$year;
  			break;
  			case "March":
  						$dbtable = $report."maret_".$year;
  			break;
  			case "April":
  						$dbtable = $report."april_".$year;
  			break;
  			case "May":
  						$dbtable = $report."mei_".$year;
  			break;
  			case "June":
  						$dbtable = $report."juni_".$year;
  			break;
  			case "July":
  						$dbtable = $report."juli_".$year;
  			break;
  			case "August":
  						$dbtable = $report."agustus_".$year;
  			break;
  			case "September":
  						$dbtable = $report."september_".$year;
  			break;
  			case "October":
  						$dbtable = $report."oktober_".$year;
  			break;
  			case "November":
  						$dbtable = $report."november_".$year;
  			break;
  			case "December":
  						$dbtable = $report."desember_".$year;
  			break;
  		}

  		// if($m2 != $m1){
      //
  		// 	$callback['error'] = true;
  		// 	$callback['message'] = "Periode dapat dipilih dalam bulan yang sama!";
      //
  		// 	echo json_encode($callback);
  		// 	return;
  		// }

  		if($year != $year2){

  			$callback['error'] = true;
  			$callback['message'] = "Periode dapat dipilih dalam tahun yang sama!";

  			echo json_encode($callback);
  			return;
  		}

  		$company_name = $this->getcompanyname_byID($company);
  		$total_unit   = $this->gettotalvehicle_bycompany($company);

      $data_location_hour  = $this->getLocationHour($dbtable, $company, $sdate, $edate);
      $lochour_groupbydate = $this->getLocationHour_groupby_date($dbtable, $company, $sdate, $edate);
      $totaldate           = sizeof($lochour_groupbydate);
        $totalhari = array();
          for ($i=0; $i < $totaldate; $i++) {
            $totalhari[] = date("d", strtotime($lochour_groupbydate[$i]['location_report_gps_date']));
          }
      $lochour_groupbyhour = $this->getLocationHour_groupby_hour($dbtable, $company, $sdate, $edate);
      $totalhour           = sizeof($lochour_groupbyhour);
      $totaljam = array();
        for ($i=0; $i < $totalhour; $i++) {
          $totaljam[] = date("H", strtotime($lochour_groupbyhour[$i]['location_report_gps_hour']));
        }
      $data_olah           = array();

      // echo "<pre>";
      // var_dump($totaljam);die();
      // echo "<pre>";

        if (sizeof($data_location_hour) > 0) {
          for ($i=0; $i < sizeof($data_location_hour); $i++) {
            // for ($i=0; $i < 4; $i++) {
              $shift1_1  = strtotime("06:00:00");
              $shift1_2  = strtotime("18:00:00");
              $shift2_1  = strtotime("18:00:00");
              $shift2_2  = strtotime("06:00:00");
              $shift_fix = "";

              $gps_time           = date("H:i:s", strtotime($data_location_hour[$i]['location_report_gps_time']));
              $gps_time_strtotime = strtotime($gps_time);

              if ($gps_time_strtotime > $shift1_1 && $gps_time_strtotime < $shift1_2) {
                $shift_fix = 1;
              }else {
                $shift_fix = 2;
              }

              array_push($data_olah, array(
                "location_report_vehicle_device" => $data_location_hour[$i]['location_report_vehicle_device'],
                "location_report_vehicle_no"     => $data_location_hour[$i]['location_report_vehicle_no'],
                "location_report_shift_format"   => $shift_fix,
                "location_report_gps_date"       => $data_location_hour[$i]['location_report_gps_date'],
                "location_report_gps_hour"       => $data_location_hour[$i]['location_report_gps_hour'],
                "location_report_gps_time"       => $data_location_hour[$i]['location_report_gps_time'],
                "location_report_gps_time2"      => $gps_time,
              ));
            }
          }

          $datafix = array();
          if (sizeof($data_olah) > 0) {
            if ($shift == 1) {
              for ($j=0; $j < sizeof($data_olah); $j++) {
                $shift_search = $data_olah[$j]['location_report_shift_format'];
                  if ($shift_search == 1) {
                    array_push($datafix, array(
                      "location_report_vehicle_device" => $data_olah[$j]['location_report_vehicle_device'],
                      "location_report_vehicle_no"     => $data_olah[$j]['location_report_vehicle_no'],
                      "location_report_shift_format"   => $data_olah[$j]['location_report_shift_format'],
                      "location_report_gps_date"       => $data_olah[$j]['location_report_gps_date'],
                      "location_report_gps_hour"       => $data_olah[$j]['location_report_gps_hour'],
                      "location_report_gps_time"       => $data_olah[$j]['location_report_gps_time'],
                      "location_report_gps_time2"      => $data_olah[$j]['location_report_gps_time2'],
                    ));
                  }
              }
            }elseif ($shift == 2) {
              for ($j=0; $j < sizeof($data_olah); $j++) {
                $shift_search = $data_olah[$j]['location_report_shift_format'];
                  if ($shift_search == 2) {
                    array_push($datafix, array(
                      "location_report_vehicle_device" => $data_olah[$j]['location_report_vehicle_device'],
                      "location_report_vehicle_no"     => $data_olah[$j]['location_report_vehicle_no'],
                      "location_report_shift_format"   => $data_olah[$j]['location_report_shift_format'],
                      "location_report_gps_date"       => $data_olah[$j]['location_report_gps_date'],
                      "location_report_gps_hour"       => $data_olah[$j]['location_report_gps_hour'],
                      "location_report_gps_time"       => $data_olah[$j]['location_report_gps_time'],
                      "location_report_gps_time2"      => $data_olah[$j]['location_report_gps_time2'],
                    ));
                  }
              }
            }else {
              $datafix = $data_olah;
            }

            // KONDISI COUNT NYA
            $totaldatafix      = sizeof($datafix);
            $datatanggal       = array();
            // $dataforshow       = array();
            $counttotal        = 0;

            for ($k=0; $k < sizeof($totalhari); $k++) {
              $tanggal = $totalhari[$k];
              for ($l=0; $l < $totaldatafix; $l++) {
                $loc_report_date = date("d", strtotime($datafix[$l]['location_report_gps_date']));
                $loc_report_hour = date("H", strtotime($datafix[$l]['location_report_gps_hour']));
                if ($tanggal == $loc_report_date) {
                  for ($m=0; $m < sizeof($totaljam); $m++) {
                    $hour           = $totaljam[$m];
                    $data_array_jam = array();
                      if ($hour == $loc_report_hour) {
                        // $dataforshow[$tanggal] = array_push($data_array_jam, array(
                        //   $hour => $hour
                        // ));
                        // $counttotal += 1;
                        $dataforshow['tanggal'][$tanggal]['jam'][$hour][] = $loc_report_date.'-'.$loc_report_hour;
                      }
                  }
                }
              }
            }
          }


          sort($totalhari);
          $clength = count($totalhari);
          for($x = 0; $x < $clength; $x++) {
            $totalhari[$x];
          }
          // $totalhari_2 = array_values($totalhari);
          // $totalhari_3 = array_reverse($totalhari_1);
      // echo "<pre>";
      // var_dump($totalhari);die();
      // echo "<pre>";

      $this->params["shift"]        = $shift;
  		$this->params["data"]         = $dataforshow;
      $this->params["datahari"]     = $totalhari;
      $this->params["datajam"]      = $totaljam;
      $this->params["totalhari"]    = sizeof($totalhari);
      $this->params["totaljam"]     = sizeof($totaljam);
  		$this->params["startdate"]    = $sdate_show;
  		$this->params["enddate"]      = $edate_show;

  		$this->params["sdate"]        = $sdate_show;
  		$this->params["edate"]        = $edate_show;
  		$this->params["company"]      = $company;


  		// $this->params["datadate"]  = $datadate;
  		$this->params["company_name"] = $company_name;
  		$this->params["total_unit"]   = $total_unit;

  		$html                         = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);
  		$callback["html"]             = $html;
      $callback["data"]             = $dataforshow;
      $callback["datahari"]         = $totalhari;

  		echo json_encode($callback);
  	}

    function getLocationHour($dbtable, $company, $sdate, $edate){
      $this->dbts = $this->load->database("tensor_report", true);
      $this->dbts->where("location_report_vehicle_company", $company);
      $this->dbts->where("location_report_group", "STREET");
      $this->dbts->where("location_report_gps_time >= ", $sdate);
      $this->dbts->where("location_report_gps_time <= ", $edate);
      $this->dbts->order_by("location_report_gps_hour", "asc");
      $result = $this->dbts->get($dbtable)->result_array();
      return $result;
    }

    function getLocationHour_groupby_date($dbtable, $company, $sdate, $edate){
      $this->dbts = $this->load->database("tensor_report", true);
      $this->dbts->where("location_report_vehicle_company", $company);
      $this->dbts->where("location_report_group", "STREET");
      $this->dbts->where("location_report_gps_time >= ", $sdate);
      $this->dbts->where("location_report_gps_time <= ", $edate);
      $this->dbts->order_by("location_report_gps_hour", "asc");
      $this->dbts->group_by("location_report_gps_date");
      $result = $this->dbts->get($dbtable)->result_array();
      return $result;
    }

    function getLocationHour_groupby_hour($dbtable, $company, $sdate, $edate){
      $this->dbts = $this->load->database("tensor_report", true);
      $this->dbts->where("location_report_vehicle_company", $company);
      $this->dbts->where("location_report_group", "STREET");
      $this->dbts->where("location_report_gps_time >= ", $sdate);
      $this->dbts->where("location_report_gps_time <= ", $edate);
      $this->dbts->order_by("location_report_gps_hour", "asc");
      $this->dbts->group_by("location_report_gps_hour");
      $result = $this->dbts->get($dbtable)->result_array();
      return $result;
    }

    function get_company()
    {
        if (!isset($this->sess->user_type)) {
            redirect(base_url());
        }

        $privilegecode = $this->sess->user_id_role;


        $this->db->order_by("company_name", "asc");
        if ($privilegecode == 0) {
            $this->db->where("company_created_by", $this->sess->user_id);
        } elseif ($privilegecode == 1) {
            $this->db->where("company_created_by", $this->sess->user_parent);
        } elseif ($privilegecode == 2) {
            $this->db->where("company_created_by", $this->sess->user_parent);
        } elseif ($privilegecode == 3) {
            $this->db->where("company_created_by", $this->sess->user_parent);
        } elseif ($privilegecode == 4) {
            $this->db->where("company_created_by", $this->sess->user_parent);
        } elseif ($privilegecode == 4) {
            $this->db->where("company_created_by", 4408);
        } elseif ($privilegecode == 5) {
            $this->db->where("company_id", $this->sess->user_company);
        } elseif ($privilegecode == 6) {
            $this->db->where("company_id", $this->sess->user_company);
        }

        $this->db->where("company_flag", 0);
        $qd = $this->db->get("company");
        $rd = $qd->result();

        return $rd;
    }

    function get_company_pool()
    {
        if (!isset($this->sess->user_type)) {
            redirect(base_url());
        }

        $privilegecode = $this->sess->user_id_role;


        $this->db->order_by("company_name", "asc");
        if ($privilegecode == 0) {
            $this->db->where("company_created_by", $this->sess->user_id);
        } elseif ($privilegecode == 1) {
            $this->db->where("company_created_by", $this->sess->user_parent);
        } elseif ($privilegecode == 2) {
            $this->db->where("company_created_by", $this->sess->user_parent);
        } elseif ($privilegecode == 3) {
            $this->db->where("company_created_by", $this->sess->user_parent);
        } elseif ($privilegecode == 4) {
            $this->db->where("company_created_by", $this->sess->user_parent);
        } elseif ($privilegecode == 5) {
            $this->db->where("company_id", $this->sess->user_company);
        } elseif ($privilegecode == 6) {
            $this->db->where("company_id", $this->sess->user_company);
        }

        $this->db->where("company_flag", 0);
        $qd = $this->db->get("company");
        $rd = $qd->result();

        return $rd;
    }


    function get_location()
    {
        $this->db->select("street_alias");
        $this->db->order_by("street_alias", "asc");
        $this->db->where_in("street_type", array(3, 4));
        $q = $this->db->get("street");

        return $q->result();
    }

	function get_periode_date($company,$sdate,$edate)
    {
		$sdate_tgl = date('Y-m-d', strtotime($sdate));
		$edate_tgl = date('Y-m-d', strtotime($edate));

		$begin = new DateTime($sdate_tgl);
		$end = new DateTime($edate_tgl);

		for($i = $begin; $i <= $end; $i->modify('+1 day')){
			$date_loop =  $i->format("Y-m-d");

			print_r($date_loop."===");
			$total = $this->get_vehile_opr_bydate($company,$begin,$end);
			print_r($total."===");

		}


    }

	function get_totalvehicle_opr($company,$date,$hour)
	{

		$group_selected = array('STREET','ROM','PORT');

		$this->dbts = $this->load->database("webtracking_ts", true);
		$this->dbts->select("location_report_id,location_report_vehicle_no");
        $this->dbts->order_by("location_report_id", "asc");
		$this->dbts->group_by("location_report_vehicle_no");
        $this->dbts->where("location_report_vehicle_company", $company);
		$this->dbts->where("location_report_gps_date", $date);
		$this->dbts->where("location_report_gps_hour", $hour);
		$this->dbts->where_in("location_report_group", $group_selected);
        $q = $this->dbts->get("ts_location_hour");
		$row = $q->result();

		$total = count($row);

		//print_r($row);exit();

		$this->dbts->close();
		$this->dbts->cache_delete_all();

        return $total;


	}

	function getcompanyname_byID($id)
	{
		$name = "-";
		$this->db = $this->load->database("default", true);
		$this->db->select("company_id,company_name");
		$this->db->order_by("company_name", "asc");
		$this->db->where("company_id ", $id);
		$q = $this->db->get("company");
		$row = $q->row();
		if(count($row)>0){

			$name = $row->company_name;

		}else{

			$name = "-";
		}
		$this->db->close();
		$this->db->cache_delete_all();
		return $name;
	}

	function gettotalvehicle_bycompany($id)
	{
		$total = 0;
		$this->db = $this->load->database("default", true);
		$this->db->select("vehicle_id");
		$this->db->order_by("vehicle_id", "asc");
		$this->db->where("vehicle_status <>", 3);
		$this->db->where("vehicle_user_id ", 4408);
		$this->db->where("vehicle_company ", $id);
		$q = $this->db->get("vehicle");
		$row = $q->result();

		$total = count($row);

		$this->db->close();
		$this->db->cache_delete_all();
		return $total;
	}


}

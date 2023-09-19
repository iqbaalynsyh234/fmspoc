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

    public function apiData($bulan){
		$corona = new ControlroomModel();
		$corona->where('tgl >=',"2020-{$bulan}-01");
		$corona->where('tgl <=',"2020-{$bulan}-31");
		$corona->orderBy('tgl','asc');
		echo json_encode($corona->get()->getResult());
	}

    function room()
    {
        if (!isset($this->sess->user_type)) {
            redirect(base_url());
        }

        $privilegecode = $this->sess->user_id_role;

        $rows_company                   = $this->get_company();

        $this->params["rcompany"]       = $rows_company;
        $this->params["rlocation"]       = $this->get_location();
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
    function search()
    {
        $company = $this->input->post('company');
        $location = $this->input->post('location');
        $datein = $this->input->post('date');
        $shift = $this->input->post('shift');
        $date = date("Y-m-d", strtotime($datein));
        if (date("Y-m-d") < $date) {
            echo json_encode(array("code" => 200, "error" => true, "msg" => "Date Not Found", "total" => 0, "data" => array()));
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

		$days_report = date("d", strtotime($datein));
		$month_report = date("F", strtotime($datein));
		$year_report = date("Y", strtotime($datein));
		$before_status = 0;
		$year_before = date('Y',strtotime('-1 year',strtotime($year_report)));

		$report = "location_hour_";
		$report_ritase = "location_hour_";

		switch ($month_report)
		{
			case "January":
            $dbtable = $report."januari_".$year_report;
			$dbtable_ritase = $report_ritase."januari_".$year_report;
			$dbtable_before = $report."desember_".$year_before;
			break;
			case "February":
            $dbtable = $report."februari_".$year_report;
			$dbtable_ritase = $report_ritase."februari_".$year_report;
			$dbtable_before = $report."januari_".$year_report;
			break;
			case "March":
            $dbtable = $report."maret_".$year_report;
			$dbtable_ritase = $report_ritase."maret_".$year_report;
			$dbtable_before = $report."februari_".$year_report;
			break;
			case "April":
            $dbtable = $report."april_".$year_report;
			$dbtable_ritase = $report_ritase."april_".$year_report;
			$dbtable_before = $report."maret_".$year_report;
			break;
			case "May":
            $dbtable = $report."mei_".$year_report;
			$dbtable_ritase = $report_ritase."mei_".$year_report;
			$dbtable_before = $report."april_".$year_report;
			break;
			case "June":
            $dbtable = $report."juni_".$year_report;
			$dbtable_ritase = $report_ritase."juni_".$year_report;
			$dbtable_before = $report."mei_".$year_report;
			break;
			case "July":
            $dbtable = $report."juli_".$year_report;
			$dbtable_ritase = $report_ritase."juli_".$year_report;
			$dbtable_before = $report."juni_".$year_report;
			break;
			case "August":
            $dbtable = $report."agustus_".$year_report;
			$dbtable_ritase = $report_ritase."agustus_".$year_report;
			$dbtable_before = $report."juli_".$year_report;
			break;
			case "September":
            $dbtable = $report."september_".$year_report;
			$dbtable_ritase = $report_ritase."september_".$year_report;
			$dbtable_before = $report."agustus_".$year_report;
			break;
			case "October":
            $dbtable = $report."oktober_".$year_report;
			$dbtable_ritase = $report_ritase."oktober_".$year_report;
			$dbtable_before = $report."september_".$year_report;
			break;
			case "November":
            $dbtable = $report."november_".$year_report;
			$dbtable_ritase = $report_ritase."november_".$year_report;
			$dbtable_before = $report."oktober_".$year_report;
			break;
			case "December":
            $dbtable = $report."desember_".$year_report;
			$dbtable_ritase = $report_ritase."desember_".$year_report;
			$dbtable_before = $report."november_".$year_report;
			break;
		}

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
        //$this->dbts = $this->load->database("webtracking_ts", true);
		$this->dbts = $this->load->database("tensor_report", true);

        $this->db->order_by("location_report_location", "asc");
		
		//geofence baru di submit (18 juli 2023)
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
            $result = $this->dbts->get($dbtable);
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
            $result = $this->dbts->get($dbtable);
            $data1 = $result->result_array();
            $nr1 = $result->num_rows();
            $this->dbts->distinct();
            $this->db->order_by("location_report_location", "asc");
            
			//geofence baru di submit (18 juli 2023)
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
            $result = $this->dbts->get($dbtable);
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
            $result = $this->dbts->get($dbtable);
            $data1 = $result->result_array();
            $nr1 = $result->num_rows();
            $this->dbts->distinct();
            $this->db->order_by("location_report_location", "asc");
			
			//geofence baru di submit (18 juli 2023)
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
            $result = $this->dbts->get($dbtable);
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

    function search_violation2()
	{
		ini_set('memory_limit', "5G");
		ini_set('max_execution_time', 300); // 5 minutes
		// $datein    = $this->input->post("date");
		$company = $this->input->post("company");
		$violation = $this->input->post("violation");
		$vehicle = $this->input->post("vehicle");
		// $date = date("Y-m-d", strtotime($datein));
		// $month = date("F", strtotime($datein));
		// $monthforparam = date("m", strtotime($datein));
		// $year = date("Y", strtotime($datein));
		$report     = "alarm_evidence_";
		$overspeed  = "overspeed_hour_";
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
			/* if ($company == "all") {
				$diff = strtotime($edate) - strtotime($sdate);
				if ($diff > 604800) {
					$err = true;
					$msg = "Maximum date range for all contractors is 7 days!";
				}
			} */
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
		$dataviolationalarmtype = array(); // Initialize the array
		$dataalarmtype = array(); // Initialize the array

		if (!empty($alarmtype)) {
    		$nr = count($alarmtype);
    		for ($i = 0; $i < $nr; $i++) {
        if (isset($alarmtype[$i]) && isset($alarmtype[$i]["alarm_type"])) {
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

		//vehicle data
		$this->db->select("vehicle_name,vehicle_no,vehicle_company");
		$this->db->where("vehicle_status <>", 3);

		if ($company != "all") {
			$this->db->where("vehicle_company", $company);
		}
		$qd = $this->db->get("vehicle");
		$total_unit = $qd->num_rows();
		$rd = $qd->result();
		$total_unit_percontractor = array();
		for ($x = 0; $x < $total_unit; $x++) {
			if ($rd[$x]->vehicle_company != null) {
				if (!isset($total_unit_percontractor[$rd[$x]->vehicle_company])) {
					$total_unit_percontractor[$rd[$x]->vehicle_company] = 1;
				} else {
					$jml = (int)$total_unit_percontractor[$rd[$x]->vehicle_company] + 1;
					$total_unit_percontractor[$rd[$x]->vehicle_company] = $jml;
				}
			}
		}

		$s_date = date("Y-m-d", strtotime($sdate));
		$nowdate = date("Y-m-d");
		// $action = false;
		// if ($company == 'all') {
		// 	if ($s_date == $e_date) {
		// 		if ($s_date != $nowdate) {
		// 			$action = true;
		// 		} else {
		// 			$action = false;
		// 		}
		// 	} else {
		// 		$action = false;
		// 	}
		// } else {
		if ($s_date != $nowdate) {
			$action = true;
		} else {
			$action = false;
		}
		// }

		// if ($action == true) {
		// 	//vehicle operational data
		// 	$dt_operational = $this->getDTOperational($company, $s_date, $e_date);
		// 	$this->params['total_operational_units'] = $dt_operational['units']; //total violation units
		// }
		$this->params['ratio'] = $action;

		$data = array();
		$data2 = array();

		if ($vehicle != "all") {
			$exp = explode("/", $vehicle);
			$vehicle_imei = $exp[0];
			$vehicle_device = $exp[1];

			$companydata = $this->getcompany_bycreator(null, $exp[2]);
			$count_company = 1;
			$company = $exp[2];
			$data_company[$exp[2]] = $companydata[0]->company_name;
			$master_company[0] = $companydata[0]->company_name;
			$company = $exp[2];
			$opposite_company[$companydata[0]->company_name] = $exp[2];

			if ($violation == "6") {
				$data2 = $this->getOverspeed2($dboverspeed, $company, $vehicle_device, $sdate, $edate);
			} else {
				if ($violation == "all") {
					$data2 = $this->getOverspeed2($dboverspeed, $company, $vehicle_device, $sdate, $edate);
					$data = $this->getSecurityEvidence($dbtable, $company, $vehicle_imei, $violation, $dataalarmtype, $sdate, $edate);
				} else {
					//$data2 = $this->getOverspeed2($dboverspeed, $company, $vehicle_device, $sdate, $edate); //additional dari ovs alert bisa kebuka untuk all mitra 1 bulan
					$data = $this->getSecurityEvidence($dbtable, $company, $vehicle_imei, $violation, $dataalarmtype, $sdate, $edate);
				}
			}
		} else {
			//company data
			$companydata = $this->getcompany_bycreator(4408);
			$data_company = array(); //format 1
			$opposite_company = array(); //kebalikan format 1
			$master_company = array(); //format 2
			if ($company != "all") {
				$exp = explode("@", $company);
				$count_company = 1;
				$company = $exp[0];
				$data_company[$exp[0]] = $exp[1];
				$master_company[0] = $exp[1];
				$company = $exp[0];
				$opposite_company[$exp[1]] = $exp[0];
			} else {
				$count_company = count($companydata);
				for ($i = 0; $i < $count_company; $i++) {
					$data_company[$companydata[$i]->company_id] = $companydata[$i]->company_name;
					$opposite_company[$companydata[$i]->company_name] = $companydata[$i]->company_id;
					$master_company[$i] = $companydata[$i]->company_name;
				}
			}

			if ($violation == "6") {
				$data2 = $this->getOverspeed2($dboverspeed, $company, $vehicle, $sdate, $edate);
			} else {
				if ($violation == "all") {
					$data2 = $this->getOverspeed2($dboverspeed, $company, $vehicle, $sdate, $edate);
					$data = $this->getSecurityEvidence($dbtable, $company, $vehicle, $violation, $dataalarmtype, $sdate, $edate);
				} else {
					//$data2 = $this->getOverspeed2($dboverspeed, $company, $vehicle_device, $sdate, $edate); //additional dari ovs alert bisa kebuka untuk all mitra 1 bulan
					$data = $this->getSecurityEvidence($dbtable, $company, $vehicle, $violation, $dataalarmtype, $sdate, $edate);
				}
			}
		}

		/* print_r($sdate." ".$edate);
		print_r($data2);exit(); */

		$numrows = count($data);
		// $numrows = 0;
		$numrows2 = count($data2);
		// $numrows2 = 0;
		$total_data = 0;
		$data_fix = array();
		$data_table = array();
		$dhour = array();
		$dcompany = array();
		$dviolation = array();
		$c = array();
		$l = array();
		$seleksi_data = array(); //untuk seleksi multiple data yang sama
		$seleksi_data_table = array(); //untuk seleksi multiple data yang sama
		$seleksi_data_overspeed = array(); //untuk seleksi multiple data yang sama
		$seleksi_data_table_overspeed = array(); //untuk seleksi multiple data yang sama
		$total_violation_units = array();
		$seleksi_unit = array();
		$top_ten = array();
		if ($company == "all") {
			if ($numrows > 0) {
				for ($i = 0; $i < $numrows; $i++) {
					if (isset($data_company[$data[$i]['alarm_report_vehicle_company']])) {
						$datetime = $data[$i]['alarm_report_start_time'];
						//$datetime = date("Y-m-d H:i:s", strtotime($data[$i]['alarm_report_start_time']) + (60 * 60));
						$vehiclen = $data[$i]['alarm_report_vehicle_no'];
						$loction = $data[$i]['alarm_report_location_start'];
						$cmpny = $data_company[$data[$i]['alarm_report_vehicle_company']];
						$exp = explode(" ", $datetime);
						$h = explode(":", $exp[1]);
						// if (!isset($total_violation_units[$cmpny][$vehiclen])) {
						// 	$total_violation_units[$cmpny][$vehiclen] = 1;
						// }

						if (!isset($total_violation_units[$cmpny])) {
							// $units[$cmpny][$vhicle] = 1;
							$total_violation_units[$cmpny] = 1;
							$seleksi_unit[$exp[0]][$cmpny][$vehiclen] = 1;
						} else {
							if (!isset($seleksi_unit[$exp[0]][$cmpny][$vehiclen])) {
								$seleksi_unit[$exp[0]][$cmpny][$vehiclen] = 1;
								$total_violation_units[$cmpny] += 1;
							}
						}

						if (isset($dataviolationalarmtype[$data[$i]['alarm_report_type']])) {
							$vltion = $dataviolationalarmtype[$data[$i]['alarm_report_type']];
						} else {
							$vltion = $data[$i]['alarm_report_type'];
						}


						if (!isset($hour[$h[0]])) {
							$hour[$h[0]] = 1;
							$dhour[] = $h[0];
						}
						if (!isset($c[$cmpny])) {
							$c[$cmpny] = $cmpny;
							$dcompany[] = $cmpny;
						}
						if (!isset($l[$vltion])) {

							$l[$vltion] = $vltion;
							$dviolation[] = $vltion;
						}
						$d = array(
							"company" => $cmpny,
							"vehicle" => $vehiclen,
							"violation" => $vltion,
							"hour" => $h[0],
						);

						if (!isset($data_fix[$h[0]][$cmpny])) {
							$data_fix[$h[0]][$cmpny] = array();
							array_push($data_fix[$h[0]][$cmpny], $d);
							if ($vltion == "Driver Abnormal") {
								$seleksi_data[$vltion][$vehiclen][$exp[0]][$h[0]] = 1;
							} else {
								$seleksi_data[$vltion][$vehiclen][$exp[0]][$h[0]][$loction] = 1;
							}
							if (!isset($top_ten[$vltion][$vehiclen])) {
								$top_ten[$vltion][$vehiclen] = 1;
							} else {
								$top_ten[$vltion][$vehiclen] += 1;
							}
							$total_data++;
						} else {
							if ($vltion == "Driver Abnormal") {
								if (!isset($seleksi_data[$vltion][$vehiclen][$exp[0]][$h[0]])) {
									$seleksi_data[$vltion][$vehiclen][$exp[0]][$h[0]] = 1;
									array_push($data_fix[$h[0]][$cmpny], $d);
									if (!isset($top_ten[$vltion][$vehiclen])) {
										$top_ten[$vltion][$vehiclen] = 1;
									} else {
										$top_ten[$vltion][$vehiclen] += 1;
									}
									$total_data++;
								}
							} else {
								if (!isset($seleksi_data[$vltion][$vehiclen][$exp[0]][$h[0]][$loction])) {
									$seleksi_data[$vltion][$vehiclen][$exp[0]][$h[0]][$loction] = 1;
									array_push($data_fix[$h[0]][$cmpny], $d);
									if (!isset($top_ten[$vltion][$vehiclen])) {
										$top_ten[$vltion][$vehiclen] = 1;
									} else {
										$top_ten[$vltion][$vehiclen] += 1;
									}
									$total_data++;
								}
							}
						}

						if (!isset($data_table[$vltion][$cmpny])) {
							$data_table[$vltion][$cmpny] = array();
							array_push($data_table[$vltion][$cmpny], $d);
							if ($vltion == "Driver Abnormal") {
								$seleksi_data_table[$vltion][$vehiclen][$exp[0]][$h[0]] = 1;
							} else {
								$seleksi_data_table[$vltion][$vehiclen][$exp[0]][$h[0]][$loction] = 1;
							}
						} else {
							if ($vltion == "Driver Abnormal") {
								if (!isset($seleksi_data_table[$vltion][$vehiclen][$exp[0]][$h[0]])) {
									$seleksi_data_table[$vltion][$vehiclen][$exp[0]][$h[0]] = 1;
									array_push($data_table[$vltion][$cmpny], $d);
								}
							} else {
								if (!isset($seleksi_data_table[$vltion][$vehiclen][$exp[0]][$h[0]][$loction])) {
									$seleksi_data_table[$vltion][$vehiclen][$exp[0]][$h[0]][$loction] = 1;
									array_push($data_table[$vltion][$cmpny], $d);
								}
							}
						}
					}
				}
			}
			if ($numrows2 > 0) {
				for ($i = 0; $i < $numrows2; $i++) {
					if (isset($data_company[$data2[$i]['overspeed_report_vehicle_company']])) {
						// $datetime = $data[$i]['overspeed_report_gps_time'];
						$datetime = $data2[$i]['overspeed_report_gps_time'];
						// $datetime = date("Y-m-d H:i:s", strtotime($datetime) + (60 * 60));
						$cmpny = $data_company[$data2[$i]['overspeed_report_vehicle_company']];

						$vltion = "Overspeed";
						$vehiclen = $data2[$i]['overspeed_report_vehicle_no'];
						$locationn = $data2[$i]['overspeed_report_location'];
						$exp = explode(" ", $datetime);
						$h = explode(":", $exp[1]);

						// if (!isset($total_violation_units[$cmpny][$vehiclen])) {
						// 	$total_violation_units[$cmpny][$vehiclen] = 1;
						// }


						if (!isset($total_violation_units[$cmpny])) {
							// $units[$cmpny][$vhicle] = 1;
							$total_violation_units[$cmpny] = 1;
							$seleksi_unit[$exp[0]][$cmpny][$vehiclen] = 1;
						} else {
							if (!isset($seleksi_unit[$exp[0]][$cmpny][$vehiclen])) {
								$seleksi_unit[$exp[0]][$cmpny][$vehiclen] = 1;
								$total_violation_units[$cmpny] += 1;
							}
						}


						if (!isset($hour[$h[0]])) {
							$hour[$h[0]] = 1;
							$dhour[] = $h[0];
						}
						if (!isset($c[$cmpny])) {
							$c[$cmpny] = $cmpny;
							$dcompany[] = $cmpny;
						}
						if (!isset($l[$vltion])) {
							$l[$vltion] = $vltion;
							$dviolation[] = $vltion;
						}
						$d = array(
							"company" => $cmpny,
							"vehicle" => $vehiclen,
							"violation" => $vltion,
							"hour" => $h[0],
							"level" => $data2[$i]['overspeed_report_level']
						);

						if (!isset($data_fix[$h[0]][$cmpny])) {
							$data_fix[$h[0]][$cmpny] = array();
							array_push($data_fix[$h[0]][$cmpny], $d);
							$seleksi_data_overspeed[$vltion][$vehiclen][$exp[0]][$h[0]][$locationn] = 1;

							if (!isset($top_ten[$vltion][$vehiclen])) {
								$top_ten[$vltion][$vehiclen] = 1;
							} else {
								$top_ten[$vltion][$vehiclen] += 1;
							}
							$total_data++;
						} else {
							if (!isset($seleksi_data_overspeed[$vltion][$vehiclen][$exp[0]][$h[0]][$locationn])) {
								$seleksi_data_overspeed[$vltion][$vehiclen][$exp[0]][$h[0]][$locationn] = 1;
								array_push($data_fix[$h[0]][$cmpny], $d);
								if (!isset($top_ten[$vltion][$vehiclen])) {
									$top_ten[$vltion][$vehiclen] = 1;
								} else {
									$top_ten[$vltion][$vehiclen] += 1;
								}
								$total_data++;
							}
						}

						if (!isset($data_table[$vltion][$cmpny])) {
							$data_table[$vltion][$cmpny] = array();
							array_push($data_table[$vltion][$cmpny], $d);
							$seleksi_data_table_overspeed[$vltion][$vehiclen][$exp[0]][$h[0]][$locationn] = 1;
						} else {
							if (!isset($seleksi_data_table_overspeed[$vltion][$vehiclen][$exp[0]][$h[0]][$locationn])) {
								$seleksi_data_table_overspeed[$vltion][$vehiclen][$exp[0]][$h[0]][$locationn] = 1;
								array_push($data_table[$vltion][$cmpny], $d);
							}
						}
					}
				}
			}
			if ($total_data == 0) {
				$callback['error'] = true;
				$callback['message'] = "Data Empty!";
				$callback['input'] = $input;
				echo json_encode($callback);
				return;
			}
		} else {
			if ($numrows > 0) {
				for ($i = 0; $i < $numrows; $i++) {
					if (isset($data_company[$data[$i]['alarm_report_vehicle_company']])) {
						$datetime = $data[$i]['alarm_report_start_time'];
						//$datetime = date("Y-m-d H:i:s", strtotime($data[$i]['alarm_report_start_time']) + (60 * 60));
						$vehiclen = $data[$i]['alarm_report_vehicle_no'];
						$loction = $data[$i]['alarm_report_location_start'];
						$cmpny = $data_company[$data[$i]['alarm_report_vehicle_company']];

						if (isset($dataviolationalarmtype[$data[$i]['alarm_report_type']])) {
							$vltion = $dataviolationalarmtype[$data[$i]['alarm_report_type']];
						} else {
							$vltion = $data[$i]['alarm_report_type'];
						}
						$exp = explode(" ", $datetime);
						$h = explode(":", $exp[1]);

						// if (!isset($total_violation_units[$cmpny][$vehiclen])) {
						// 	$total_violation_units[$cmpny][$vehiclen] = 1;
						// }

						if (!isset($total_violation_units[$cmpny])) {
							// $units[$cmpny][$vhicle] = 1;
							$total_violation_units[$cmpny] = 1;
							$seleksi_unit[$exp[0]][$cmpny][$vehiclen] = 1;
						} else {
							if (!isset($seleksi_unit[$exp[0]][$cmpny][$vehiclen])) {
								$seleksi_unit[$exp[0]][$cmpny][$vehiclen] = 1;
								$total_violation_units[$cmpny] += 1;
							}
						}


						if (!isset($hour[$h[0]])) {
							$hour[$h[0]] = 1;
							$dhour[] = $h[0];
						}
						if (!isset($c[$cmpny])) {
							$c[$cmpny] = $cmpny;
							$dcompany[] = $cmpny;
						}
						if (!isset($l[$vltion])) {

							$l[$vltion] = $vltion;
							$dviolation[] = $vltion;
						}
						$d = array(
							"company" => $cmpny,
							"vehicle" => $vehiclen,
							"violation" => $vltion,
							"hour" => $h[0],
						);

						if (!isset($data_fix[$h[0]][$vltion])) {
							$data_fix[$h[0]][$vltion] = array();
							array_push($data_fix[$h[0]][$vltion], $d);
							if ($vltion == "Driver Abnormal") {
								$seleksi_data[$vltion][$vehiclen][$exp[0]][$h[0]] = 1;
							} else {
								$seleksi_data[$vltion][$vehiclen][$exp[0]][$h[0]][$loction] = 1;
							}
							if (!isset($top_ten[$vltion][$vehiclen])) {
								$top_ten[$vltion][$vehiclen] = 1;
							} else {
								$top_ten[$vltion][$vehiclen] += 1;
							}
							$total_data++;
						} else {
							if ($vltion == "Driver Abnormal") {
								if (!isset($seleksi_data[$vltion][$vehiclen][$exp[0]][$h[0]])) {
									$seleksi_data[$vltion][$vehiclen][$exp[0]][$h[0]] = 1;
									array_push($data_fix[$h[0]][$vltion], $d);
									if (!isset($top_ten[$vltion][$vehiclen])) {
										$top_ten[$vltion][$vehiclen] = 1;
									} else {
										$top_ten[$vltion][$vehiclen] += 1;
									}
									$total_data++;
								}
							} else {
								if (!isset($seleksi_data[$vltion][$vehiclen][$exp[0]][$h[0]][$loction])) {
									$seleksi_data[$vltion][$vehiclen][$exp[0]][$h[0]][$loction] = 1;
									array_push($data_fix[$h[0]][$vltion], $d);
									if (!isset($top_ten[$vltion][$vehiclen])) {
										$top_ten[$vltion][$vehiclen] = 1;
									} else {
										$top_ten[$vltion][$vehiclen] += 1;
									}
									$total_data++;
								}
							}
						}

						if (!isset($data_table[$vltion][$cmpny])) {
							$data_table[$vltion][$cmpny] = array();
							array_push($data_table[$vltion][$cmpny], $d);
							if ($vltion == "Driver Abnormal") {
								$seleksi_data_table[$vltion][$vehiclen][$exp[0]][$h[0]] = 1;
							} else {
								$seleksi_data_table[$vltion][$vehiclen][$exp[0]][$h[0]][$loction] = 1;
							}
						} else {
							if ($vltion == "Driver Abnormal") {
								if (!isset($seleksi_data_table[$vltion][$vehiclen][$exp[0]][$h[0]])) {
									$seleksi_data_table[$vltion][$vehiclen][$exp[0]][$h[0]] = 1;
									array_push($data_table[$vltion][$cmpny], $d);
								}
							} else {
								if (!isset($seleksi_data_table[$vltion][$vehiclen][$exp[0]][$h[0]][$loction])) {
									$seleksi_data_table[$vltion][$vehiclen][$exp[0]][$h[0]][$loction] = 1;
									array_push($data_table[$vltion][$cmpny], $d);
								}
							}
						}
					}
				}
			}
			if ($numrows2 > 0) {
				for ($i = 0; $i < $numrows2; $i++) {
					if (isset($data_company[$data2[$i]['overspeed_report_vehicle_company']])) {
						// $datetime = $data[$i]['overspeed_report_gps_time'];
						$datetime = $data2[$i]['overspeed_report_gps_time'];
						// $datetime = date("Y-m-d H:i:s", strtotime($datetime) + (60 * 60));
						$cmpny = $data_company[$data2[$i]['overspeed_report_vehicle_company']];

						$vltion = "Overspeed";
						$vehiclen = $data2[$i]['overspeed_report_vehicle_no'];
						$locationn = $data2[$i]['overspeed_report_location'];
						$exp = explode(" ", $datetime);
						$h = explode(":", $exp[1]);

						// if (!isset($total_violation_units[$cmpny][$vehiclen])) {
						// 	$total_violation_units[$cmpny][$vehiclen] = 1;
						// }


						if (!isset($total_violation_units[$cmpny])) {
							// $units[$cmpny][$vhicle] = 1;
							$total_violation_units[$cmpny] = 1;
							$seleksi_unit[$exp[0]][$cmpny][$vehiclen] = 1;
						} else {
							if (!isset($seleksi_unit[$exp[0]][$cmpny][$vehiclen])) {
								$seleksi_unit[$exp[0]][$cmpny][$vehiclen] = 1;
								$total_violation_units[$cmpny] += 1;
							}
						}


						if (!isset($hour[$h[0]])) {
							$hour[$h[0]] = 1;
							$dhour[] = $h[0];
						}
						if (!isset($c[$cmpny])) {
							$c[$cmpny] = $cmpny;
							$dcompany[] = $cmpny;
						}
						if (!isset($l[$vltion])) {
							$l[$vltion] = $vltion;
							$dviolation[] = $vltion;
						}
						$d = array(
							"company" => $cmpny,
							"vehicle" => $vehiclen,
							"violation" => $vltion,
							"hour" => $h[0]
						);

						if (!isset($data_fix[$h[0]][$vltion])) {
							$data_fix[$h[0]][$vltion] = array();
							array_push($data_fix[$h[0]][$vltion], $d);
							if (!isset($top_ten[$vltion][$vehiclen])) {
								$top_ten[$vltion][$vehiclen] = 1;
							} else {
								$top_ten[$vltion][$vehiclen] += 1;
							}
							$seleksi_data_overspeed[$vltion][$vehiclen][$exp[0]][$h[0]][$locationn] = 1;
							$total_data++;
						} else {
							if (!isset($seleksi_data_overspeed[$vltion][$vehiclen][$exp[0]][$h[0]][$locationn])) {
								$seleksi_data_overspeed[$vltion][$vehiclen][$exp[0]][$h[0]][$locationn] = 1;
								array_push($data_fix[$h[0]][$vltion], $d);
								if (!isset($top_ten[$vltion][$vehiclen])) {
									$top_ten[$vltion][$vehiclen] = 1;
								} else {
									$top_ten[$vltion][$vehiclen] += 1;
								}
								$total_data++;
							}
						}

						if (!isset($data_table[$vltion][$cmpny])) {
							$data_table[$vltion][$cmpny] = array();
							array_push($data_table[$vltion][$cmpny], $d);
							$seleksi_data_table_overspeed[$vltion][$vehiclen][$exp[0]][$h[0]][$locationn] = 1;
						} else {
							if (!isset($seleksi_data_table_overspeed[$vltion][$vehiclen][$exp[0]][$h[0]][$locationn])) {
								$seleksi_data_table_overspeed[$vltion][$vehiclen][$exp[0]][$h[0]][$locationn] = 1;
								array_push($data_table[$vltion][$cmpny], $d);
							}
						}
					}
				}
			}
			if ($total_data == 0) {
				$callback['error'] = true;
				$callback['message'] = "Data Empty!";
				echo json_encode($callback);
				return;
			}
		}


		$input = array(
			"company" => $company,
			"violation" => $violation,
			"vehicle" => $vehicle,
			"periode" => $periode,
			"date_start" => $sdate,
			"date_end" => $edate
		);

		sort($dhour);

		/* var_dump($total_violation_units);
		 exit(); */

		//print_r($top_ten);exit();

		$this->params['input'] = $input;
		$this->params['sdate'] = $sdate;
		$this->params['edate'] = $edate;
		$this->params['opposite_company'] = $opposite_company;
		$this->params['total_data'] = $total_data;
		$this->params['date'] = $datein;
		$this->params['data_hour'] = $dhour; //data hour dari source
		$this->params['data_company'] = $dcompany; //data company dari source
		$this->params['data_alarm'] = $dviolation; //data violation dari source

		$this->params['data_fix'] = $data_fix;
		$this->params['data_table'] = $data_table;
		$this->params['top_violation'] = $top_ten; //master company format 2
		$this->params['master_company'] = $master_company; //master company format 2
		$this->params['total_violation_units'] = $total_violation_units; //total violation units
		$this->params['master_vehicle'] = $total_unit_percontractor; //master vehicle
		$this->params['master_violation'] = $master_violation; //master company format 2


		$html = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);



		$callback['error'] = false;
		$callback['input'] = $input;
		$callback['data_fix'] = $data_fix;
		$callback["html"]        = $html;

		echo json_encode($callback);
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
            $this->params["content"]        = $this->load->view('newdashboard/truckhour/v_truck_month', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_superuser", $this->params);
        } elseif ($privilegecode == 2) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_managementuser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/truckhour/v_truck_month', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_managementuser", $this->params);
        } elseif ($privilegecode == 3) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_reguleruser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/truckhour/v_truck_month', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_reguleruser", $this->params);
        } elseif ($privilegecode == 4) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_teknikaluser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/truckhour/v_truck_month', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_teknikaluser", $this->params);
        } elseif ($privilegecode == 5) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_adminpjo', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/truckhour/v_truck_month', $this->params, true);
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
		$this->dbts = $this->load->database("webtracking_ts", true);
		$this->dbts->order_by("monthly_date","asc");
		$this->dbts->select("monthly_date");
		$this->dbts->where("monthly_date >=", $sdate_tgl);
		$this->dbts->where("monthly_date <=", $edate_tgl);
        $resultdate = $this->dbts->get("ts_config_monthly_report");
        $datadate = $resultdate->result();


		//print_r(print_r($data_fix));exit();

		$this->dbts = $this->load->database("webtracking_ts", true);
		//$this->dbts->select("hour_name,hour_time");
		if($shift != "all"){
			$this->dbts->where("hour_shift", $shift);
		}
        $this->dbts->where("hour_flag", 0);
		$this->dbts->where("hour_month",$month);
		$this->dbts->where("hour_year",$year);
		$this->dbts->where("hour_company",$company);
        $result = $this->dbts->get("ts_location_hour_summary");
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

		$html                    = $this->load->view('newdashboard/truckhour/v_truck_month_result', $this->params, true);
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
            $this->params["content"]        = $this->load->view('newdashboard/truckhour/v_dev_truck_hour_backup1', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_superuser", $this->params);
        } elseif ($privilegecode == 2) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_managementuser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/truckhour/v_dev_truck_hour_backup1', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_managementuser", $this->params);
        } elseif ($privilegecode == 3) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_reguleruser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/truckhour/v_dev_truck_hour_backup1', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_reguleruser", $this->params);
        } elseif ($privilegecode == 4) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_teknikaluser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/truckhour/v_dev_truck_hour_backup1', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_teknikaluser", $this->params);
        } elseif ($privilegecode == 5) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_adminpjo', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/truckhour/v_dev_truck_hour_backup1', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_adminpjo", $this->params);
        } elseif ($privilegecode == 6) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_userpjo', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/truckhour/v_dev_truck_hour_backup1', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_userpjo", $this->params);
        } else {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/truckhour/v_dev_truck_hour_backup1', $this->params, true);
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
            $this->params["content"]        = $this->load->view('newdashboard/truckhour/v_truck_hour_v1', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_reguleruser", $this->params);
        } elseif ($privilegecode == 4) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_teknikaluser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/truckhour/v_truck_hour_v1', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_teknikaluser", $this->params);
        } elseif ($privilegecode == 5) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_adminpjo', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/truckhour/v_truck_hour_v1', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_adminpjo", $this->params);
        } elseif ($privilegecode == 6) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_userpjo', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/truckhour/v_truck_hour_v1', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_userpjo", $this->params);
        } else {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/truckhour/v_truck_hour_v1', $this->params, true);
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
            $this->params["content"]        = $this->load->view('newdashboard/truckhour/v_truck_pool', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_superuser", $this->params);
        } elseif ($privilegecode == 2) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_managementuser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/truckhour/v_truck_pool', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_managementuser", $this->params);
        } elseif ($privilegecode == 3) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_reguleruser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/truckhour/v_truck_pool', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_reguleruser", $this->params);
        } elseif ($privilegecode == 4) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_teknikaluser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/truckhour/v_truck_pool', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_teknikaluser", $this->params);
        } elseif ($privilegecode == 5) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_adminpjo', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/truckhour/v_truck_pool', $this->params, true);
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
            $this->params["content"]        = $this->load->view('newdashboard/truckhour/v_truck_month_summary', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_superuser", $this->params);
        } elseif ($privilegecode == 2) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_managementuser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/truckhour/v_truck_month_summary', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_managementuser", $this->params);
        } elseif ($privilegecode == 3) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_reguleruser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/truckhour/v_truck_month_summary', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_reguleruser", $this->params);
        } elseif ($privilegecode == 4) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_teknikaluser', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/truckhour/v_truck_month_summary', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_teknikaluser", $this->params);
        } elseif ($privilegecode == 5) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_adminpjo', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/truckhour/v_truck_month_summary', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_adminpjo", $this->params);
        } elseif ($privilegecode == 6) {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_userpjo', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/truckhour/v_truck_month_summary', $this->params, true);
            $this->load->view("newdashboard/partial/template_dashboard_userpjo", $this->params);
        } else {
            $this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar', $this->params, true);
            $this->params["content"]        = $this->load->view('newdashboard/controlroom/v_controlroom', $this->params, true);
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

  		$html                         = $this->load->view('newdashboard/truckhour/v_truck_month_summary_result', $this->params, true);
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

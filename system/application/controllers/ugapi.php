<?php
include "base.php";

class Ugapi extends Base {

	function Ugapi()
	{
		parent::Base();
		$this->load->model("gpsmodel");
		$this->load->model("vehiclemodel");
		$this->load->model("configmodel");
		$this->load->model("dashboardmodel");
		$this->load->helper('common_helper');
		$this->load->helper('kopindosat');
		$this->load->model('m_ugemsmodel');
	}

	function getPosition($longitude, $ew, $latitude, $ns)
	{
		$gps_longitude_real = getLongitude($longitude, $ew);
		$gps_latitude_real = getLatitude($latitude, $ns);

		$gps_longitude_real_fmt = number_format($gps_longitude_real, 4, ".", "");
		$gps_latitude_real_fmt = number_format($gps_latitude_real, 4, ".", "");

		$georeverse = $this->gpsmodel->GeoReverse($gps_latitude_real_fmt, $gps_longitude_real_fmt);

		return $georeverse;
	}

	function getPosition_other($longitude, $latitude)
	{
		$georeverse = $this->gpsmodel->GeoReverse($latitude, $longitude);
		return $georeverse;
	}

	function getGeofence_location($longitude, $ew, $latitude, $ns, $vehicle_user)
	{

		$this->db = $this->load->database("default", true);
		$lng = getLongitude($longitude, $ew);
		$lat = getLatitude($latitude, $ns);

		$sql = sprintf("
					SELECT 	*
					FROM 	%sgeofence
					WHERE 	TRUE
							AND CONTAINS(geofence_polygon, GEOMFROMTEXT('POINT(%s %s)'))
							AND (geofence_user = '%s' )
                            AND (geofence_status = 1)
					LIMIT 1 OFFSET 0", $this->db->dbprefix, $lng, $lat, $vehicle_user);

		$q = $this->db->query($sql);

		if ($q->num_rows() > 0)
		{
			$row = $q->result();
            $total = $q->num_rows();
            for ($i=0;$i<$total;$i++){
            $data = $row[$i]->geofence_name;
            return $data;
            }

		}else
        {
            return false;
        }

	}

	function getGeofence_location_other($longitude, $latitude, $vehicle_user)
	{

		$this->db = $this->load->database("default", true);
		$lng = $longitude;
		$lat = $latitude;

		$sql = sprintf("
					SELECT 	*
					FROM 	%sgeofence
					WHERE 	TRUE
							AND CONTAINS(geofence_polygon, GEOMFROMTEXT('POINT(%s %s)'))
							AND (geofence_user = '%s' )
                            AND (geofence_status = 1)
					LIMIT 1 OFFSET 0", $this->db->dbprefix, $lng, $lat, $vehicle_user);
		$q = $this->db->query($sql);
		if ($q->num_rows() > 0)
		{
			$row = $q->result();
            $total = $q->num_rows();
            for ($i=0;$i<$total;$i++){
            $data = $row[$i]->geofence_name;
            return $data;
            }

		}else
        {
            return false;
        }

	}

	function req_overspeed()
	{
		//printf("PROSES POST SAMPLE -> REQUEST >> LAST POSITION \r\n");

		$token = "UGaW5kNkjhA782GBNS1616KbswQYa5372bsdexVNT16";
		$authorization = "Authorization:".$token;
		$url = "https://temansharing.borneo-indobara.com/ugapi/getoverspeed";
		$feature = array();

		$feature["UserId"] = 4204; //pbi
		//$feature["VehicleNo"] = "all";
		$feature["VehicleNo"] = "BMT 3148";
		$feature["StartTime"] = "2022-08-31 00:00:00";
		$feature["EndTime"] = "2022-08-31 23:59:59";

		//printf("POSTING PROSES \r\n");
		$content = json_encode($feature);
		$total_content = count($content);

		printf("Data JSON : %s \r \n",$content);

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

		$json_response = curl_exec($curl);
		echo $json_response;
		echo curl_getinfo($curl, CURLINFO_HTTP_CODE);
		printf("-------------------------- \r\n");

		exit;
	}

	function getoverspeed()
	{
		ini_set('memory_limit', "2G");
		ini_set('max_execution_time', 180); // 3 minutes
		$nowstarttime = date("Y-m-d H:i:s");
		header("Content-Type: application/json");

		$token = "UGaW5kNkjhA782GBNS1616KbswQYa5372bsdexVNT16";
		$postdata = json_decode(file_get_contents("php://input"));
		$allvehicle = 0;
		$now = date("Ymd");
		$payload = "";

		$headers = null;
		if (isset($_SERVER['Authorization']))
		{
            $headers = trim($_SERVER["Authorization"]);
        }
		else if (isset($_SERVER['HTTP_AUTHORIZATION']))
		{ //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        }
        else if (function_exists('apache_request_headers'))
        {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization']))
            {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
		//print_r($headers." || ".$token." || ".$postdata->UserId);exit();

		if($headers != $token)
        {
			$feature["code"] = 400;
			$feature["msg"] = "Invalid Authorization Key ! ";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}

		$feature = array();

		if(!isset($postdata->UserId) || $postdata->UserId == "")
		{
			$feature["code"] = 400;
			$feature["msg"] = "Invalid User ID";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}else{

			//hanya user yg terdaftar yg bisa akes API
			$this->db->where("api_user",$postdata->UserId);
			$this->db->where("api_token",$headers);
			$this->db->where("api_status",1);
			$this->db->where("api_flag",0);
			$q = $this->db->get("api_user");
			if($q->num_rows == 0)
			{
				$feature["code"] = 400;
				$feature["msg"] = "User & Authorization Key is Not Available!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}

		}

		if(!isset($postdata->VehicleNo) || $postdata->VehicleNo == "all")
		{
			$allvehicle = 1;
		}

		if(!isset($postdata->VehicleNo) || $postdata->VehicleNo == "")
		{
			$feature["code"] = 400;
			$feature["msg"] = "Invalid Vehicle No!";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}else{
			$check_vehicle = strpos($postdata->VehicleNo,';');
			$ex_vehicle = explode(";",$postdata->VehicleNo);
			$UserIDBIB = 4408;

			//jika ada cek dari database nopol (untuk dapat device id)
			$this->db->order_by("vehicle_id","desc");
			/* if($allvehicle == 0){
				$this->db->where_in("vehicle_no",$ex_vehicle);
			} */
			$this->db->where("vehicle_no",$postdata->VehicleNo);
			$this->db->where("vehicle_user_id",$UserIDBIB);
			$this->db->where("vehicle_status",1);
			//$this->db->where("vehicle_active_date2 >",$now); //tidak expired
			$q = $this->db->get("vehicle");
			$vehicle = $q->result();

			if($q->num_rows == 0)
			{
				$feature["code"] = 400;
				$feature["msg"] = "Vehicle Not Found!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}else{
				$vehicle = $q->result();

				 $payload      		    = array(
				  "UserId"          => $postdata->UserId,
				  "VehicleNo"   	=> $postdata->VehicleNo,
				  "StartTime" 	 	=> $postdata->StartTime,
				  "EndTime"   		=> $postdata->EndTime

				);

			}
		}

		if($postdata->StartTime == "" || $postdata->EndTime == "")
		{
			$feature["code"] = 400;
			$feature["msg"] = "No Data Periode Start or Periode End";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}
		else
		{
			$sdate = $postdata->StartTime;
			$edate = $postdata->EndTime;


			$dboverspeed = "";
			$report     = "alarm_evidence_";
			$overspeed  = "overspeed_";

			$month = date("F", strtotime($sdate));
			$year = date("Y", strtotime($sdate));


			$diff = strtotime($edate) - strtotime($sdate);
			if ($diff < 0) {
				$feature["code"] = 400;
				$feature["msg"] = "Date is not correct!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}

			$diff1 = date("m", strtotime($sdate));
			$diff2 = date("m", strtotime($edate));

			if ($diff1 != $diff2) {
				$feature["code"] = 400;
				$feature["msg"] = "Date must be in the same month!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}

			$diff1 = date("Y", strtotime($sdate));
			$diff2 = date("Y", strtotime($edate));

			if ($diff1 != $diff2) {

				$feature["code"] = 400;
				$feature["msg"] = "Date must be in the same year!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}

			if($allvehicle == 1){

				$diff1 = date("d", strtotime($sdate));
				$diff2 = date("d", strtotime($edate));

				if($diff1 != $diff2)
				{
					$feature["code"] = 400;
					$feature["msg"] = "All Vehicle must be in the same Date!";
					$feature["payload"]    = $payload;
					echo json_encode($feature);
					exit;
				}

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

		}

		//jika mobil lebih dari nol
		if(count($vehicle) > 0)
		{
			$DataToUpload = array();
			//unset($DataToUpload);
			for($z=0;$z<count($vehicle);$z++)
			{

				//printf("ATTR %s \r\n",$vehicle[$z]->vehicle_no);
				$vehicle_device = $vehicle[$z]->vehicle_device;
				$company = $vehicle[$z]->vehicle_company;

					$rows = $this->getoverspeed_data($dboverspeed, $company, $vehicle_device, $sdate, $edate);
					//print_r($rows);exit();
					if(isset($rows) && count($rows)>0)
					{

						for($i=0;$i<count($rows);$i++)
						{

								$DataToUpload[$i]->VehicleUserId = $rows[$i]->overspeed_report_vehicle_user_id;
								$DataToUpload[$i]->VehicleId = $rows[$i]->overspeed_report_vehicle_id;
								$DataToUpload[$i]->VehicleDevice = $rows[$i]->overspeed_report_vehicle_device;
								$DataToUpload[$i]->VehicleNo = $rows[$i]->overspeed_report_vehicle_no;
								$DataToUpload[$i]->VehicleName = $rows[$i]->overspeed_report_vehicle_name;
								$DataToUpload[$i]->VehicleType = $rows[$i]->overspeed_report_vehicle_type;
								$DataToUpload[$i]->VehicleCompany = $rows[$i]->overspeed_report_vehicle_company;
								$DataToUpload[$i]->VehicleMV03Imei = $rows[$i]->overspeed_report_imei;

								$DataToUpload[$i]->ReportType = $rows[$i]->overspeed_report_type;
								$DataToUpload[$i]->ReportName = $rows[$i]->overspeed_report_name;

								$DataToUpload[$i]->GPSSpeed = $rows[$i]->overspeed_report_speed;
								$DataToUpload[$i]->GPSTime = $rows[$i]->overspeed_report_gps_time;
								$DataToUpload[$i]->GPSStatus = $rows[$i]->overspeed_report_gpsstatus;

								$DataToUpload[$i]->GeofenceName = $rows[$i]->overspeed_report_geofence_name;
								$DataToUpload[$i]->GeofenceLimit = $rows[$i]->overspeed_report_geofence_limit;
								$DataToUpload[$i]->GeofenceType = $rows[$i]->overspeed_report_geofence_type;

								$DataToUpload[$i]->OverspeedJalur = $rows[$i]->overspeed_report_jalur;
								$DataToUpload[$i]->OverspeedLevel = $rows[$i]->overspeed_report_level;
								$DataToUpload[$i]->OverspeedLevelAlias = $rows[$i]->overspeed_report_level_alias;

								$DataToUpload[$i]->Location = $rows[$i]->overspeed_report_location;
								$DataToUpload[$i]->Coordinate = $rows[$i]->overspeed_report_coordinate;
								//$DataToUpload[$i]->SpeedStatus = $rows[$i]->overspeed_report_speed_status;

								//$datajson["Data"] = $DataToUpload;



						}

					}

			}
			//$content = json_encode($datajson);
			$content = $DataToUpload;

			//echo $content;
			echo json_encode(array("code" => 200, "msg" => "ok", "data" => $content, "payload" => $payload), JSON_NUMERIC_CHECK);
			$nowendtime = date("Y-m-d H:i:s");
			$this->insertHitAPI("API Overspeed",$payload,$nowstarttime,$nowendtime);
			$this->db->close();
			$this->db->cache_delete_all();

		}


		exit;
	}

	function req_driverdetected()
	{
		//printf("PROSES POST SAMPLE -> REQUEST >> LAST POSITION \r\n");

		$token = "UGaW5kNkjhA782GBNS1616KbswQYa5372bsdexVNT16";
		$authorization = "Authorization:".$token;
		$url = "https://temansharing.borneo-indobara.com/ugapi/getdriverdetected";
		$feature = array();

		$feature["UserId"] = 4204;
		$feature["CompanyId"] = "all";
		$feature["StartTime"] = "2022-08-29 00:00:00";
		$feature["EndTime"] = "2022-08-29 23:59:59";

		//printf("POSTING PROSES \r\n");
		$content = json_encode($feature);
		$total_content = count($content);

		printf("Data JSON : %s \r \n",$content);

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

		$json_response = curl_exec($curl);
		echo $json_response;
		echo curl_getinfo($curl, CURLINFO_HTTP_CODE);
		printf("-------------------------- \r\n");

		exit;
	}

	function getdriverdetected()
	{
		//ini_set('display_errors', 1);
		ini_set('memory_limit', "2G");
		ini_set('max_execution_time', 180); // 3 minutes
		$nowstarttime = date("Y-m-d H:i:s");
		header("Content-Type: application/json");

		$token = "UGaW5kNkjhA782GBNS1616KbswQYa5372bsdexVNT16";
		$postdata = json_decode(file_get_contents("php://input"));
		$allcompany = 0;
		$now = date("Ymd");
		$payload = "";
		$dbtable = "ts_driver_change_new";

		$headers = null;
		if (isset($_SERVER['Authorization']))
		{
            $headers = trim($_SERVER["Authorization"]);
        }
		else if (isset($_SERVER['HTTP_AUTHORIZATION']))
		{ //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        }
        else if (function_exists('apache_request_headers'))
        {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization']))
            {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
		//print_r($headers." || ".$token." || ".$postdata->UserId);exit();

		if($headers != $token)
        {
			$feature["code"] = 400;
			$feature["msg"] = "Invalid Authorization Key ! ";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}

		$feature = array();

		if(!isset($postdata->UserId) || $postdata->UserId == "")
		{
			$feature["code"] = 400;
			$feature["msg"] = "Invalid User ID";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}else{

			//hanya user yg terdaftar yg bisa akes API
			$this->db->where("api_user",$postdata->UserId);
			$this->db->where("api_token",$headers);
			$this->db->where("api_status",1);
			$this->db->where("api_flag",0);
			$q = $this->db->get("api_user");
			if($q->num_rows == 0)
			{
				$feature["code"] = 400;
				$feature["msg"] = "User & Authorization Key is Not Available!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}

		}

		if(!isset($postdata->CompanyId) || $postdata->CompanyId == "all")
		{
			$allcompany = 1;
		}

		if(!isset($postdata->CompanyId) || $postdata->CompanyId == "")
		{
			$feature["code"] = 400;
			$feature["msg"] = "Invalid Company ID!";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}else{
			$check_company = strpos($postdata->CompanyId,';');
			$ex_company = explode(";",$postdata->CompanyId);
			$UserIDBIB = 4408;


			$this->db->order_by("company_name","asc");
			if($allcompany == 0){
				$this->db->where_in("company_id",$ex_company);
			}
			$this->db->where("company_flag",0);

			$q = $this->db->get("company");
			$data = $q->result();

			if($q->num_rows == 0)
			{
				$feature["code"] = 400;
				$feature["msg"] = "CompanyId Not Found!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}else{
				$vehicle = $q->result();

				 $payload      		    = array(
				  "UserId"          => $postdata->UserId,
				  "CompanyId"   	=> $postdata->CompanyId,
				  "StartTime" 	 	=> $postdata->StartTime,
				  "EndTime"   		=> $postdata->EndTime

				);

			}
		}

		if($postdata->StartTime == "" || $postdata->EndTime == "")
		{
			$feature["code"] = 400;
			$feature["msg"] = "No Data Periode Start or Periode End";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}
		else
		{
			$sdate = $postdata->StartTime;
			$edate = $postdata->EndTime;
		}

		//jika mobil lebih dari nol
		if(count($data) > 0)
		{
			$DataToUpload = array();

			for($z=0;$z<count($data);$z++)
			{


				$companyid = $data[$z]->company_id;

					$rows = $this->getdriverdetected_data($dbtable, $allcompany, $companyid, $sdate, $edate);

					if(isset($rows) && count($rows)>0)
					{

						for($i=0;$i<count($rows);$i++)
						{

								$DataToUpload[$i]->VehicleNo = $rows[$i]->change_driver_vehicle_no;
								$DataToUpload[$i]->CompanyId = $rows[$i]->change_driver_company;
								$DataToUpload[$i]->CompanyName = $rows[$i]->change_driver_company_name;
								$DataToUpload[$i]->ImeiCam = $rows[$i]->change_imei;
								$DataToUpload[$i]->DriverIdSimper = $rows[$i]->change_driver_id;
								$DataToUpload[$i]->DriverName = $rows[$i]->change_driver_name;
								$DataToUpload[$i]->DriverDetected = $rows[$i]->change_driver_time;

						}

					}

			}

			$content = $DataToUpload;

			//echo $content;
			echo json_encode(array("code" => 200, "msg" => "ok", "data" => $content, "payload" => $payload), JSON_NUMERIC_CHECK);
			$nowendtime = date("Y-m-d H:i:s");
			$this->insertHitAPI("API Driver Detected",$payload,$nowstarttime,$nowendtime);
			$this->db->close();
			$this->db->cache_delete_all();

		}


		exit;
	}

	function getoverspeed_data($dbtable, $company, $vehicle, $sdate, $edate)
	{

		$nowdate_report = date("Y-m-d", strtotime($sdate));
		$now = date("Y-m-d");
		if ($now == $nowdate_report) {
			// jika kondisi alert hari ini
			$month = date('F');
			$year = date('Y');
			$overspeed = "overspeed_hour_";
			$report = "overspeed_hour_";
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
			//return array();
		//print_r($dbtable);exit();
		}


		/* $privilegecode   = $this->sess->user_id_role;
		$user_id         = $this->sess->user_id;
		$user_company    = $this->sess->user_company;
		$user_parent     = $this->sess->user_parent; */
		$hauling = $this->getAllStreetKM(4408); //HAULING
		// $start_date = date("Y-m-d H:i:s", strtotime($sdate) - (60 * 60));
		// $end_date = date("Y-m-d H:i:s", strtotime($edate) - (60 * 60));
		$this->dbtrip = $this->load->database("tensor_report", true);
		//$this->dbtrip->select("overspeed_report_id,overspeed_report_vehicle_company,overspeed_report_vehicle_no,overspeed_report_vehicle_device,overspeed_report_vehicle_name,overspeed_report_speed,overspeed_report_location,overspeed_report_gps_time, overspeed_report_coordinate, overspeed_report_jalur, overspeed_report_level, overspeed_report_geofence_limit ");
		$this->dbtrip->where("overspeed_report_gps_time >=", $sdate);
		$this->dbtrip->where("overspeed_report_gps_time <=", $edate);
		$this->dbtrip->where("overspeed_report_speed_status", 1); //valid data
		$this->dbtrip->where("overspeed_report_geofence_type", "road"); //khusus dijalan
		// $this->dbtrip->like("overspeed_report_location", "KM");
		$this->dbtrip->where_in("overspeed_report_location", $hauling); // HAULING
		$this->dbtrip->where("overspeed_report_event_status", 1);
		$this->dbtrip->order_by("overspeed_report_level", "asc");
		$this->dbtrip->order_by("overspeed_report_gps_time", "asc");
		//$this->dbtrip->group_by("overspeed_report_gps_time");
		// $this->dbtrip->order_by("overspeed_report_location", "asc");
		// $this->dbtrip->group_by("overspeed_report_location");
		if ($company != "all") {
			$this->dbtrip->where("overspeed_report_vehicle_company", $company);
		}
		if ($vehicle == "all") {
			$this->dbtrip->where("overspeed_report_vehicle_id <>", 72150933); //jika pilih all bukan mobil trial
		} else {
			$this->dbtrip->where("overspeed_report_vehicle_device", $vehicle);
		}
		// $this->dbtrip->limit(200);
		$q = $this->dbtrip->get($dbtable);
		$rows = $q->result();

		$this->dbtrip->close();
		$this->dbtrip->cache_delete_all();
		return $rows;
	}

	function getdriverdetected_data($dbtable, $companyall, $company, $sdate, $edate)
	{
		$this->dbts = $this->load->database("webtracking_ts", true);
		$this->dbts->where("change_driver_time >=", $sdate);
		$this->dbts->where("change_driver_time <=", $edate);
		if($companyall != 1){
			$this->dbts->where("change_driver_company", $company);
		}
		$this->dbts->where("change_driver_flag", 0);
		$this->dbts->order_by("change_driver_time", "asc");

		$q = $this->dbts->get($dbtable);
		$rows = $q->result();

		$this->dbts->close();
		$this->dbts->cache_delete_all();
		return $rows;
	}

	function req_contractor()
	{
		//printf("PROSES POST SAMPLE -> REQUEST >> LAST POSITION \r\n");

		$token = "UGaW5kNkjhA782GBNS1616KbswQYa5372bsdexVNT16";
		$authorization = "Authorization:".$token;
		$url = "https://temansharing.borneo-indobara.com/ugapi/getcontractor";
		$feature = array();

		$feature["UserId"] = 4204; //pbi

		//printf("POSTING PROSES \r\n");
		$content = json_encode($feature);
		$total_content = count($content);

		printf("Data JSON : %s \r \n",$content);

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

		$json_response = curl_exec($curl);
		echo $json_response;
		echo curl_getinfo($curl, CURLINFO_HTTP_CODE);
		printf("-------------------------- \r\n");

		exit;
	}

	function getcontractor()
	{
		//ini_set('display_errors', 1);
		$nowstarttime = date("Y-m-d H:i:s");
		header("Content-Type: application/json");

		$token = "UGaW5kNkjhA782GBNS1616KbswQYa5372bsdexVNT16";
		$postdata = json_decode(file_get_contents("php://input"));
		$allvehicle = 0;
		$payload = "";
		$now = date("Ymd");

		$headers = null;
		if (isset($_SERVER['Authorization']))
		{
            $headers = trim($_SERVER["Authorization"]);
        }
		else if (isset($_SERVER['HTTP_AUTHORIZATION']))
		{ //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        }
        else if (function_exists('apache_request_headers'))
        {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization']))
            {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
		//print_r($headers." || ".$token." || ".$postdata->UserId);exit();



		if($headers != $token)
        {
			$feature["code"] = 400;
			$feature["msg"] = "Invalid Authorization Key ! ";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}

		$feature = array();

		if(!isset($postdata->UserId) || $postdata->UserId == "")
		{
			$feature["code"] = 400;
			$feature["msg"] = "Invalid User ID";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}else{

			//hanya user yg terdaftar yg bisa akes API
			$this->db->where("api_user",$postdata->UserId);
			$this->db->where("api_token",$headers);
			$this->db->where("api_status",1);
			$this->db->where("api_flag",0);
			$q = $this->db->get("api_user");
			if($q->num_rows == 0)
			{
				$feature["code"] = 400;
				$feature["msg"] = "User & Authorization Key is Not Available!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}else{

				$UserIDBIB = 4408;
				$this->db->order_by("company_name","asc");
				$this->db->where("company_created_by",$UserIDBIB);
				$this->db->where("company_flag",0);
				$q = $this->db->get("company");
				$company = $q->result();

				if($q->num_rows == 0)
				{
					$feature["code"] = 400;
					$feature["msg"] = "Contrator Not Found!";
					$feature["payload"]    = $payload;
					echo json_encode($feature);
					exit;
				}else{
					$company = $q->result();

					$payload      		    = array(
					  "UserId"          => $postdata->UserId


					);
				}


			}

		}


		//jika mobil lebih dari nol
		if(count($company) > 0)
		{

			$DataToUpload = array();
			//unset($DataToUpload);
			for($z=0;$z<count($company);$z++)
			{
				$DataToUpload[$z]->CompanyId = $company[$z]->company_id;
				$DataToUpload[$z]->CompanyName = $company[$z]->company_name;
				$DataToUpload[$z]->CompanySiteLogin = $company[$z]->company_site;
				$DataToUpload[$z]->CompanySiteLogout = $company[$z]->company_site_logout;
				$DataToUpload[$z]->CompanyExca = $company[$z]->company_exca;
				$DataToUpload[$z]->CompanyFlag = $company[$z]->company_flag;
				//$datajson["Data"] = $DataToUpload;
			}
			//$content = json_encode($datajson);
			$content = $DataToUpload;

			//echo $content;
			echo json_encode(array("code" => 200, "msg" => "ok", "data" => $content, "payload" => $payload), JSON_NUMERIC_CHECK);
			$nowendtime = date("Y-m-d H:i:s");
			$this->insertHitAPI("API Master Contrator",$payload,$nowstarttime,$nowendtime);
			$this->db->close();
			$this->db->cache_delete_all();

		}


		exit;
	}

	function req_vehicle()
	{
		//printf("PROSES POST SAMPLE -> REQUEST >> LAST POSITION \r\n");

		$token = "UGaW5kNkjhA782GBNS1616KbswQYa5372bsdexVNT16";
		$authorization = "Authorization:".$token;
		$url = "https://temansharing.borneo-indobara.com/ugapi/getvehicle";
		$feature = array();

		$feature["UserId"] = 4204; //pbi
		$feature["VehicleNo"] = "BBS 1207";
		//$feature["CompanyId"] = "all";


		//printf("POSTING PROSES \r\n");
		$content = json_encode($feature);
		$total_content = count($content);

		printf("Data JSON : %s \r \n",$content);

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

		$json_response = curl_exec($curl);
		echo $json_response;
		echo curl_getinfo($curl, CURLINFO_HTTP_CODE);
		printf("-------------------------- \r\n");

		exit;
	}

	function getvehicle()
	{
		//ini_set('display_errors', 1);
		$nowstarttime = date("Y-m-d H:i:s");
		header("Content-Type: application/json");

		$token = "UGaW5kNkjhA782GBNS1616KbswQYa5372bsdexVNT16";
		$postdata = json_decode(file_get_contents("php://input"));
		$allvehicle = 0;
		$allcompany = 0;
		$payload = "";
		$now = date("Ymd");

		$headers = null;
		if (isset($_SERVER['Authorization']))
		{
            $headers = trim($_SERVER["Authorization"]);
        }
		else if (isset($_SERVER['HTTP_AUTHORIZATION']))
		{ //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        }
        else if (function_exists('apache_request_headers'))
        {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization']))
            {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
		//print_r($headers." || ".$token." || ".$postdata->UserId);exit();



		if($headers != $token)
        {
			$feature["code"] = 400;
			$feature["msg"] = "Invalid Authorization Key ! ";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}

		$feature = array();

		if(!isset($postdata->UserId) || $postdata->UserId == "")
		{
			$feature["code"] = 400;
			$feature["msg"] = "Invalid User ID";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}else{

			//hanya user yg terdaftar yg bisa akes API
			$this->db->where("api_user",$postdata->UserId);
			$this->db->where("api_token",$headers);
			$this->db->where("api_status",1);
			$this->db->where("api_flag",0);
			$q = $this->db->get("api_user");
			if($q->num_rows == 0)
			{
				$feature["code"] = 400;
				$feature["msg"] = "User & Authorization Key is Not Available!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}

		}

		if(!isset($postdata->VehicleNo) || $postdata->VehicleNo == "all")
		{
			$allvehicle = 1;
		}

		/* if(!isset($postdata->CompanyId) || $postdata->CompanyId == "all")
		{
			$allcompany = 1;
		} */

		if(!isset($postdata->VehicleNo) || $postdata->VehicleNo == "")
		{
			$feature["code"] = 400;
			$feature["msg"] = "Invalid Vehicle No!";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}else{
			$check_vehicle = strpos($postdata->VehicleNo,';');
			$ex_vehicle = explode(";",$postdata->VehicleNo);
			$UserIDBIB = 4408;

			//jika ada cek dari database nopol (untuk dapat device id)
			$this->db->order_by("vehicle_id","desc");
			if($allvehicle == 0){
				$this->db->where_in("vehicle_no",$ex_vehicle);
			}
			/* if($allcompany == 0){
				$this->db->where("vehicle_company",$postdata->CompanyId);
			} */
			$this->db->where("vehicle_user_id",$UserIDBIB);
			//$this->db->where("vehicle_status",1);
			//$this->db->where("vehicle_active_date2 >",$now); //tidak expired
			$q = $this->db->get("vehicle");
			$vehicle = $q->result();

			if($q->num_rows == 0)
			{
				$feature["code"] = 400;
				$feature["msg"] = "Vehicle Not Found!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}else{
				$vehicle = $q->result();

				$payload      		    = array(
				  "UserId"          => $postdata->UserId,
				  "VehicleNo"   	=> $postdata->VehicleNo
				  //"CompanyId"   	=> $postdata->CompanyId


				);

			}
		}

		//print_r($vehicle);exit();

		//jika mobil lebih dari nol
		if(count($vehicle) > 0)
		{
			$DataToUpload = array();
			//unset($DataToUpload);
			for($i=0;$i<count($vehicle);$i++)
			{

				//printf("ATTR %s \r\n",$vehicle[$z]->vehicle_no);

								$DataToUpload[$i]->VehicleId = $vehicle[$i]->vehicle_id;
								$DataToUpload[$i]->VehicleUserID = $vehicle[$i]->vehicle_user_id;
								$DataToUpload[$i]->VehicleDevice = $vehicle[$i]->vehicle_device;
								$DataToUpload[$i]->VehicleNo = $vehicle[$i]->vehicle_no;
								$DataToUpload[$i]->VehicleNoBackup = $vehicle[$i]->vehicle_no_bk;
								$DataToUpload[$i]->VehicleName = $vehicle[$i]->vehicle_name;

								$DataToUpload[$i]->VehicleCardNo = $vehicle[$i]->vehicle_card_no;
								$DataToUpload[$i]->VehicleOperator = $vehicle[$i]->vehicle_operator;
								$DataToUpload[$i]->VehicleStatus = $vehicle[$i]->vehicle_status;

								$DataToUpload[$i]->VehicleImage = $vehicle[$i]->vehicle_image;
								$DataToUpload[$i]->VehicleCreatedDate = $vehicle[$i]->vehicle_created_date;
								$DataToUpload[$i]->VehicleType = $vehicle[$i]->vehicle_type;

								$DataToUpload[$i]->VehicleCompany = $vehicle[$i]->vehicle_company;
								$DataToUpload[$i]->VehicleSubCompany = $vehicle[$i]->vehicle_subcompany;
								$DataToUpload[$i]->VehicleGroup = $vehicle[$i]->vehicle_group;
								$DataToUpload[$i]->VehicleSubGroup = $vehicle[$i]->vehicle_subgroup;

								$DataToUpload[$i]->VehicleTanggalPasang = $vehicle[$i]->vehicle_tanggal_pasang;
								$DataToUpload[$i]->VehicleImei = $vehicle[$i]->vehicle_imei;
								$DataToUpload[$i]->VehicleMV03 = $vehicle[$i]->vehicle_mv03;
								$DataToUpload[$i]->VehicleSensor = $vehicle[$i]->vehicle_sensor;
								$DataToUpload[$i]->VehicleSOS = $vehicle[$i]->vehicle_sos;

								$DataToUpload[$i]->VehiclePortalRangka = $vehicle[$i]->vehicle_portal_rangka;
								$DataToUpload[$i]->VehiclePortalMesin = $vehicle[$i]->vehicle_portal_mesin;
								$DataToUpload[$i]->VehiclePortalRfidSPI = $vehicle[$i]->vehicle_portal_rfid_spi;
								$DataToUpload[$i]->VehiclePortalRfidWIM = $vehicle[$i]->vehicle_portal_rfid_wim;
								$DataToUpload[$i]->VehiclePortalPortalTare = $vehicle[$i]->vehicle_portal_tare;

								$DataToUpload[$i]->VehiclePortTime = $vehicle[$i]->vehicle_port_time;
								$DataToUpload[$i]->VehiclePortName = $vehicle[$i]->vehicle_port_name;
								$DataToUpload[$i]->VehicleRomTime = $vehicle[$i]->vehicle_rom_time;
								$DataToUpload[$i]->VehicleRomName = $vehicle[$i]->vehicle_rom_name;

								//$datajson["Data"] = $DataToUpload;




			}
			//$content = json_encode($datajson);
			$content = $DataToUpload;

			//echo $content;
			echo json_encode(array("code" => 200, "msg" => "ok", "data" => $content, "payload" => $payload), JSON_NUMERIC_CHECK);
			$nowendtime = date("Y-m-d H:i:s");
			$this->insertHitAPI("API Master Vehicle",$payload,$nowstarttime,$nowendtime);
			$this->db->close();
			$this->db->cache_delete_all();

		}


		exit;
	}

	function req_alarmmaster()
	{
		//printf("PROSES POST SAMPLE -> REQUEST >> LAST POSITION \r\n");

		$token = "UGaW5kNkjhA782GBNS1616KbswQYa5372bsdexVNT16";
		$authorization = "Authorization:".$token;
		$url = "https://temansharing.borneo-indobara.com/ugapi/getalarmmaster";
		$feature = array();

		$feature["UserId"] = 4204; //pbi

		//printf("POSTING PROSES \r\n");
		$content = json_encode($feature);
		$total_content = count($content);

		printf("Data JSON : %s \r \n",$content);

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

		$json_response = curl_exec($curl);
		echo $json_response;
		echo curl_getinfo($curl, CURLINFO_HTTP_CODE);
		printf("-------------------------- \r\n");

		exit;
	}

	function getalarmmaster()
	{
		//ini_set('display_errors', 1);
		$nowstarttime = date("Y-m-d H:i:s");
		header("Content-Type: application/json");

		$token = "UGaW5kNkjhA782GBNS1616KbswQYa5372bsdexVNT16";
		$postdata = json_decode(file_get_contents("php://input"));
		$allvehicle = 0;
		$payload = "";
		$now = date("Ymd");

		$headers = null;
		if (isset($_SERVER['Authorization']))
		{
            $headers = trim($_SERVER["Authorization"]);
        }
		else if (isset($_SERVER['HTTP_AUTHORIZATION']))
		{ //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        }
        else if (function_exists('apache_request_headers'))
        {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization']))
            {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
		//print_r($headers." || ".$token." || ".$postdata->UserId);exit();



		if($headers != $token)
        {
			$feature["code"] = 400;
			$feature["msg"] = "Invalid Authorization Key ! ";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}

		$feature = array();

		if(!isset($postdata->UserId) || $postdata->UserId == "")
		{
			$feature["code"] = 400;
			$feature["msg"] = "Invalid User ID";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}else{

			//hanya user yg terdaftar yg bisa akes API
			$this->db->where("api_user",$postdata->UserId);
			$this->db->where("api_token",$headers);
			$this->db->where("api_status",1);
			$this->db->where("api_flag",0);
			$q = $this->db->get("api_user");
			if($q->num_rows == 0)
			{
				$feature["code"] = 400;
				$feature["msg"] = "User & Authorization Key is Not Available!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}else{

				$UserIDBIB = 4408;
				$this->dbts = $this->load->database("webtracking_ts",true);

				$this->dbts->order_by("alarmmaster_id","asc");
				$this->dbts->where("alarmmaster_creator",$UserIDBIB);
				$this->dbts->where("alarmmaster_status",1);
				$q = $this->dbts->get("ts_alarmmaster");
				$master = $q->result();

				if($q->num_rows == 0)
				{
					$feature["code"] = 400;
					$feature["msg"] = "Data Master Alarm Not Found!";
					$feature["payload"]    = $payload;
					echo json_encode($feature);
					exit;
				}else{
					$master = $q->result();

					$payload      		    = array(
					  "UserId"          => $postdata->UserId


					);
				}


			}

		}


		//jika mobil lebih dari nol
		if(count($master) > 0)
		{
			$DataToUpload = array();
			//unset($DataToUpload);
			for($z=0;$z<count($master);$z++)
			{
				$DataToUpload[$z]->AlarmMasterId = $master[$z]->alarmmaster_id;
				$DataToUpload[$z]->AlarmMasterName = $master[$z]->alarmmaster_name;
				$DataToUpload[$z]->AlarmMasterCreator = $master[$z]->alarmmaster_creator;
				$DataToUpload[$z]->AlarmMasterCreated = $master[$z]->alarmmaster_created;
				$DataToUpload[$z]->AlarmMasterStatus = $master[$z]->alarmmaster_status;
				$DataToUpload[$z]->AlarmMasterFlag = $master[$z]->alarmmaster_flag;
				//$datajson["Data"] = $DataToUpload;
			}
			//$content = json_encode($datajson);
			$content = $DataToUpload;

			//echo $content;
			echo json_encode(array("code" => 200, "msg" => "ok", "data" => $content, "payload" => $payload), JSON_NUMERIC_CHECK);
			$nowendtime = date("Y-m-d H:i:s");
			$this->insertHitAPI("API Master Alarm",$payload,$nowstarttime,$nowendtime);
			$this->db->close();
			$this->db->cache_delete_all();
			$this->dbts->close();
			$this->dbts->cache_delete_all();
		}

		exit;
	}

	function req_alarmtype()
	{
		//printf("PROSES POST SAMPLE -> REQUEST >> LAST POSITION \r\n");

		$token = "UGaW5kNkjhA782GBNS1616KbswQYa5372bsdexVNT16";
		$authorization = "Authorization:".$token;
		$url = "https://temansharing.borneo-indobara.com/ugapi/getalarmtype";
		$feature = array();

		$feature["UserId"] = 4204; //pbi

		//printf("POSTING PROSES \r\n");
		$content = json_encode($feature);
		$total_content = count($content);

		printf("Data JSON : %s \r \n",$content);

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

		$json_response = curl_exec($curl);
		echo $json_response;
		echo curl_getinfo($curl, CURLINFO_HTTP_CODE);
		printf("-------------------------- \r\n");

		exit;
	}

	function getalarmtype()
	{
		//ini_set('display_errors', 1);
		$nowstarttime = date("Y-m-d H:i:s");
		header("Content-Type: application/json");

		$token = "UGaW5kNkjhA782GBNS1616KbswQYa5372bsdexVNT16";
		$postdata = json_decode(file_get_contents("php://input"));
		$allvehicle = 0;
		$payload = "";
		$now = date("Ymd");

		$headers = null;
		if (isset($_SERVER['Authorization']))
		{
            $headers = trim($_SERVER["Authorization"]);
        }
		else if (isset($_SERVER['HTTP_AUTHORIZATION']))
		{ //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        }
        else if (function_exists('apache_request_headers'))
        {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization']))
            {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
		//print_r($headers." || ".$token." || ".$postdata->UserId);exit();



		if($headers != $token)
        {
			$feature["code"] = 400;
			$feature["msg"] = "Invalid Authorization Key ! ";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}

		$feature = array();

		if(!isset($postdata->UserId) || $postdata->UserId == "")
		{
			$feature["code"] = 400;
			$feature["msg"] = "Invalid User ID";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}else{

			//hanya user yg terdaftar yg bisa akes API
			$this->db->where("api_user",$postdata->UserId);
			$this->db->where("api_token",$headers);
			$this->db->where("api_status",1);
			$this->db->where("api_flag",0);
			$q = $this->db->get("api_user");
			if($q->num_rows == 0)
			{
				$feature["code"] = 400;
				$feature["msg"] = "User & Authorization Key is Not Available!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}else{

				$UserIDBIB = 4408;
				$this->dbts = $this->load->database("webtracking_ts",true);

				$this->dbts->order_by("alarm_type","asc");
				$this->dbts->where("alarm_master_id <>","");
				$q = $this->dbts->get("ts_alarm");
				$data = $q->result();

				if($q->num_rows == 0)
				{
					$feature["code"] = 400;
					$feature["msg"] = "Data Type Alarm Not Found!";
					$feature["payload"]    = $payload;
					echo json_encode($feature);
					exit;
				}else{
					$data = $q->result();

					$payload      		    = array(
					  "UserId"          => $postdata->UserId


					);
				}


			}

		}

		if(count($data) > 0)
		{

			$DataToUpload = array();
			//unset($DataToUpload);
			for($z=0;$z<count($data);$z++)
			{
				$DataToUpload[$z]->AlarmId = $data[$z]->alarm_id;
				$DataToUpload[$z]->AlarmType = $data[$z]->alarm_type;
				$DataToUpload[$z]->AlarmName = $data[$z]->alarm_name;
				$DataToUpload[$z]->AlarmDesc = $data[$z]->alarm_desc;
				$DataToUpload[$z]->AlarmMasterId = $data[$z]->alarm_master_id;
				//$datajson["Data"] = $DataToUpload;
			}
			//$content = json_encode($datajson);
			$content = $DataToUpload;

			//echo $content;
			echo json_encode(array("code" => 200, "msg" => "ok", "data" => $content, "payload" => $payload), JSON_NUMERIC_CHECK);
			$nowendtime = date("Y-m-d H:i:s");
			$this->insertHitAPI("API Type Alarm",$payload,$nowstarttime,$nowendtime);
			$this->db->close();
			$this->db->cache_delete_all();
			$this->dbts->close();
			$this->dbts->cache_delete_all();

		}


		exit;
	}

	function getcompanyname_byID($id)
	{
		$name = "-";
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

		return $name;
	}

	function getAllStreetKM($userid)
	{
		$feature = array();
		$street_type_list = array("1", "5", "8", "7", "4", "3"); //HAULING + ROM ROAD + PORT + CP + ANTRIAN BLC , ROM = 3
		$this->dbmaster = $this->load->database("default", true);
		$this->dbmaster->select("street_name,street_alias,street_type");
		$this->dbmaster->order_by("street_name", "asc");
		$this->dbmaster->group_by("street_name");
		$this->dbmaster->where("street_creator", $userid);
		$this->dbmaster->where_in("street_type", $street_type_list);
		$this->dbmaster->where("street_name !=", "PORT BBC,"); //selain port bbc

		$this->dbmaster->from("street");
		$q = $this->dbmaster->get();
		$rows = $q->result();
		$total = count($rows);
		for ($x = 0; $x < $total; $x++) {
			$street_name = str_replace(",", "", $rows[$x]->street_name);
			$feature[$x] = $street_name;
		}

		//print_r($feature);exit();
		$result = $feature;

		return $result;
	}

	function insertHitAPI($apiname,$payload,$starttime,$endtime)
	{
		$latency = strtotime($endtime) - strtotime($starttime);
		$ipaddress = $_SERVER['REMOTE_ADDR'];

		$this->dbts = $this->load->database("webtracking_ts",true);
		$data_insert["hit_api"] = $apiname;
		$data_insert["hit_user"] = $payload['UserId'];
		$data_insert["hit_datetime_wib"] = $starttime;
		$data_insert["hit_latency"] = $latency;
		$data_insert["hit_ip_address"] = $ipaddress;
		$data_insert["hit_payload"] = json_encode($payload);

		$this->dbts->insert("ts_api_hit",$data_insert);
		//printf("INSERT OK \r\n");

		$this->dbts->close();
		$this->dbts->cache_delete_all();
	}

	function getlocationhour()
	{
		ini_set('memory_limit', "2G");
		ini_set('max_execution_time', 180); // 3 minutes
		$nowstarttime = date("Y-m-d H:i:s");
		header("Content-Type: application/json");

		$token            = "UGaW5kNkjhA782GBNS1616KbswQYa5372bsdexVNT16";
		$postdata         = json_decode(file_get_contents("php://input"));
		$allvehicle       = 0;
		$now              = date("Ymd");
		$payload          = "";
		$forbidden_symbol = array("'", ",", ".", "?", "!", ";", ":", "-");

		$headers = null;
		if (isset($_SERVER['Authorization']))
		{
				$headers = trim($_SERVER["Authorization"]);
		}
		else if (isset($_SERVER['HTTP_AUTHORIZATION']))
		{ //Nginx or fast CGI
				$headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
		}
		else if (function_exists('apache_request_headers'))
		{
			$requestHeaders = apache_request_headers();
			// Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
			$requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
			if (isset($requestHeaders['Authorization']))
			{
				$headers = trim($requestHeaders['Authorization']);
			}
		}

		if($headers != $token)
		{
			$feature["code"]    = 400;
			$feature["msg"]     = "Invalid Authorization Key ! ";
			$feature["payload"] = $payload;
			echo json_encode($feature);
			exit;
		}

		$feature = array();

		if(!isset($postdata->UserId) || $postdata->UserId == "")
		{
			$feature["code"]    = 400;
			$feature["msg"]     = "Invalid User ID";
			$feature["payload"] = $payload;
			echo json_encode($feature);
			exit;
		}else{

			//hanya user yg terdaftar yg bisa akes API
			$this->db->where("api_user",$postdata->UserId);
			$this->db->where("api_token",$headers);
			$this->db->where("api_status",1);
			$this->db->where("api_flag",0);
			$q = $this->db->get("api_user");
			if($q->num_rows == 0)
			{
				$feature["code"]    = 400;
				$feature["msg"]     = "User & Authorization Key is Not Available!";
				$feature["payload"] = $payload;
				echo json_encode($feature);
				exit;
			}

		}

		$payload = array(
		 "UserId"    => $postdata->UserId,
		 "Date" 	 	 => $postdata->Date,
		 "Hour" 		 => $postdata->Hour,
		 "Shift"     => $postdata->Shift,
		 "CompanyId" => $postdata->CompanyId
	 );

	 // echo "<pre>";
	 // var_dump($payload);die();
	 // echo "<pre>";

	 if($postdata->Shift == "" || $postdata->Shift > 2 || (!is_numeric($postdata->Shift)))
	 {
		 $feature["code"]    = 400;
		 $feature["msg"]     = "Invalid Shift";
		 $feature["payload"] = $payload;
		 echo json_encode($feature);
		 exit;
	 }else {
		 $shiftfix = $postdata->Shift;
	 }

		if($postdata->CompanyId == "")
		{
			$feature["code"]    = 400;
			$feature["msg"]     = "Company ID is empty";
			$feature["payload"] = $payload;
			echo json_encode($feature);
			exit;
		}
		else
		{
			$company       = $postdata->CompanyId;

			// CEK SYMBOL TERLARANG
			if ($this->strposa($company, $forbidden_symbol, 1)) {
					$symbolfounded = 1;
			} else {
					$symbolfounded = 0;
			}

				if ($symbolfounded == 1) {
					$feature["code"]    = 400;
					$feature["msg"]     = "CompanyID is only can be filled by ID or all";
					$feature["payload"] = $payload;
					echo json_encode($feature);
					exit;
				}

				if ($company == "all") {
					$data_company = $postdata->CompanyId;
				}else {
					$data_company = $this->m_ugemsmodel->getcompanyname_byID($company);
						if ($data_company == "-") {
							$feature["code"]    = 400;
							$feature["msg"]     = "Invalid Company ID";
							$feature["payload"] = $payload;
							echo json_encode($feature);
							exit;
						}else {
							$data_company = $data_company[0]->company_id;
						}
				}
		}

		if($postdata->Date == "")
		{
			$feature["code"]    = 400;
			$feature["msg"]     = "Date can not be empty";
			$feature["payload"] = $payload;
			echo json_encode($feature);
			exit;
		}
		else
		{
			$sdate = $postdata->Date;
		}

		if(!isset($postdata->Hour) || $postdata->Hour == "")
		{
			$feature["code"]    = 400;
			$feature["msg"]     = "Time can not be empty";
			$feature["payload"] = $payload;
			echo json_encode($feature);
			exit;
		}else {
			$shourfix 		= "all";
			$shour        = $postdata->Hour;
				if ($shour != "all") {
					$checkformat  = $this->verify_time_format($shour);
						if ($checkformat == false) {
							$feature["code"]    = 400;
							$feature["msg"]     = "Invalid Hour Format";
							$feature["payload"] = $payload;
							echo json_encode($feature);
							exit;
						}else {
							$shourfix = $shour;
						}
				}
		}

		// echo "<pre>";
		// var_dump($shourfix);die();
		// echo "<pre>";

		// PENCARIAN DIMULAI
		$company  = $data_company;
		$datein   = $sdate;
		$shift    = $shiftfix;
		$date     = date("Y-m-d", strtotime($datein));

		$lastdate = date("Y-m-t", strtotime($datein));
		$year     = date("Y", strtotime($datein));
		$month    = date("m", strtotime($datein));
		$day      = date('d', strtotime($datein));
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
		$input = array(
				"company" => $company,
				"date"    => $arraydate,
				"shift"   => $shift
		);

		$this->dbts = $this->load->database("webtracking_ts", true);
		if ($shift == 1) {
				// $this->dbts->select("location_report_vehicle_no,location_report_company_name,location_report_gps_date,location_report_gps_hour,location_report_location,location_report_group,location_report_coordinate,location_report_latitude, location_report_longitude");
				$this->dbts->select("location_report_vehicle_user_id, location_report_vehicle_id, location_report_vehicle_device, location_report_vehicle_no,
														location_report_vehicle_name, location_report_vehicle_type, location_report_vehicle_company, location_report_imei,
														location_report_type, location_report_speed, location_report_engine, location_report_gpsstatus, location_report_gps_time,
														location_report_gps_date, location_report_gps_hour, location_report_jalur, location_report_direction, location_report_location,
														location_report_coordinate, location_report_hauling, location_report_group");
				$shift = array("06:00:00", "07:00:00", "08:00:00", "09:00:00", "10:00:00", "11:00:00", "12:00:00", "13:00:00", "14:00:00", "15:00:00", "16:00:00", "17:00:00");
				$this->dbts->where("location_report_gps_date", $date);
				if ($company != 0) {
						$this->dbts->where("location_report_vehicle_company", $company);
				}
					if ($shourfix != "all") {
						$this->dbts->where("location_report_gps_hour", $shourfix);
					}else {
						$this->dbts->where_in("location_report_gps_hour", $shift);
					}
				$this->dbts->order_by("location_report_gps_hour", "asc");
				$this->dbts->order_by("location_report_company_name", "asc");
				$result = $this->dbts->get("ts_location_hour");
				$data = $result->result_array();
				$nr = $result->num_rows();
		} else if ($shift == 2) {
				// $this->dbts->select("location_report_vehicle_no,location_report_company_name,location_report_gps_date,location_report_gps_hour,location_report_location,location_report_group,location_report_coordinate,location_report_latitude, location_report_longitude");
				$this->dbts->select("location_report_vehicle_user_id, location_report_vehicle_id, location_report_vehicle_device, location_report_vehicle_no,
														location_report_vehicle_name, location_report_vehicle_type, location_report_vehicle_company, location_report_imei,
														location_report_type, location_report_speed, location_report_engine, location_report_gpsstatus, location_report_gps_time,
														location_report_gps_date, location_report_gps_hour, location_report_jalur, location_report_direction, location_report_location,
														location_report_coordinate, location_report_hauling, location_report_group");
				$shift1 = array("18:00:00", "19:00:00", "20:00:00", "21:00:00", "22:00:00", "23:00:00");
				$shift2 = array("00:00:00", "01:00:00", "02:00:00", "03:00:00", "04:00:00", "05:00:00");
				$this->dbts->where("location_report_gps_date", $date);
				if ($company != 0) {
						$this->dbts->where("location_report_vehicle_company", $company);
				}
				// $this->dbts->where_in("location_report_gps_hour", $shift1);
					if ($shourfix != "all") {
						$this->dbts->where("location_report_gps_hour", $shourfix);
					}else {
						$this->dbts->where_in("location_report_gps_hour", $shift1);
					}
				$this->dbts->order_by("location_report_gps_hour", "asc");
				$this->dbts->order_by("location_report_company_name", "asc");
				$result = $this->dbts->get("ts_location_hour");
				$data1 = $result->result_array();
				$nr1 = $result->num_rows();
				$this->dbts->distinct();
				// $this->dbts->select("location_report_vehicle_no,location_report_company_name,location_report_gps_date,location_report_gps_hour,location_report_location,location_report_group,location_report_coordinate,location_report_latitude, location_report_longitude");
				$this->dbts->select("location_report_vehicle_user_id, location_report_vehicle_id, location_report_vehicle_device, location_report_vehicle_no,
														location_report_vehicle_name, location_report_vehicle_type, location_report_vehicle_company, location_report_imei,
														location_report_type, location_report_speed, location_report_engine, location_report_gpsstatus, location_report_gps_time,
														location_report_gps_date, location_report_gps_hour, location_report_jalur, location_report_direction, location_report_location,
														location_report_coordinate, location_report_hauling, location_report_group");
				$this->dbts->where("location_report_gps_date", $next);
				if ($company != 0) {
						$this->dbts->where("location_report_vehicle_company", $company);
				}
				// $this->dbts->where_in("location_report_gps_hour", $shift2);
					if ($shourfix != "all") {
						$this->dbts->where("location_report_gps_hour", $shourfix);
					}else {
						$this->dbts->where_in("location_report_gps_hour", $shift2);
					}
				$this->dbts->order_by("location_report_gps_hour", "asc");
				$this->dbts->order_by("location_report_company_name", "asc");
				$result = $this->dbts->get("ts_location_hour");
				$data2 = $result->result_array();
				$nr2 = $result->num_rows();
				$data = array_merge($data1, $data2);
				$nr = $nr1 +  $nr2;
		} else {
				// $this->dbts->select("location_report_vehicle_no,location_report_company_name,location_report_gps_date,location_report_gps_hour,location_report_location,location_report_group,location_report_coordinate,location_report_latitude, location_report_longitude");
				$this->dbts->select("location_report_vehicle_user_id, location_report_vehicle_id, location_report_vehicle_device, location_report_vehicle_no,
														location_report_vehicle_name, location_report_vehicle_type, location_report_vehicle_company, location_report_imei,
														location_report_type, location_report_speed, location_report_engine, location_report_gpsstatus, location_report_gps_time,
														location_report_gps_date, location_report_gps_hour, location_report_jalur, location_report_direction, location_report_location,
														location_report_coordinate, location_report_hauling, location_report_group");
				$shift1 = array("06:00:00", "07:00:00", "08:00:00", "09:00:00", "10:00:00", "11:00:00", "12:00:00", "13:00:00", "14:00:00", "15:00:00", "16:00:00", "17:00:00", "18:00:00", "19:00:00", "20:00:00", "21:00:00", "22:00:00", "23:00:00");
				$shift2 = array("00:00:00", "01:00:00", "02:00:00", "03:00:00", "04:00:00", "05:00:00");
				$this->dbts->where("location_report_gps_date", $date);
				if ($company != 0) {
						$this->dbts->where("location_report_vehicle_company", $company);
				}
				// $this->dbts->where_in("location_report_gps_hour", $shift1);
					if ($shourfix != "all") {
						$this->dbts->where("location_report_gps_hour", $shourfix);
					}else {
						$this->dbts->where_in("location_report_gps_hour", $shift1);
					}
				$this->dbts->order_by("location_report_gps_hour", "asc");
				$this->dbts->order_by("location_report_company_name", "asc");
				$result = $this->dbts->get("ts_location_hour");
				$data1 = $result->result_array();
				$nr1 = $result->num_rows();
				$this->dbts->distinct();
				// $this->dbts->select("location_report_vehicle_no,location_report_company_name,location_report_gps_date,location_report_gps_hour,location_report_location,location_report_group,location_report_coordinate,location_report_latitude, location_report_longitude");
				$this->dbts->select("location_report_vehicle_user_id, location_report_vehicle_id, location_report_vehicle_device, location_report_vehicle_no,
														location_report_vehicle_name, location_report_vehicle_type, location_report_vehicle_company, location_report_imei,
														location_report_type, location_report_speed, location_report_engine, location_report_gpsstatus, location_report_gps_time,
														location_report_gps_date, location_report_gps_hour, location_report_jalur, location_report_direction, location_report_location,
														location_report_coordinate, location_report_hauling, location_report_group");
				$this->dbts->where("location_report_gps_date", $next);
				if ($company != 0) {
						$this->dbts->where("location_report_vehicle_company", $company);
				}
				// $this->dbts->where_in("location_report_gps_hour", $shift2);
				if ($shourfix != "all") {
					$this->dbts->where("location_report_gps_hour", $shourfix);
				}else {
					$this->dbts->where_in("location_report_gps_hour", $shift2);
				}
				$this->dbts->order_by("location_report_gps_hour", "asc");
				$this->dbts->order_by("location_report_company_name", "asc");
				$result = $this->dbts->get("ts_location_hour");
				$data2 = $result->result_array();
				$nr2 = $result->num_rows();
				$data = array_merge($data1, $data2);
				$nr = $nr1 +  $nr2;
		}

		$datafix = array();
		for ($i=0; $i < sizeof($data); $i++) {
			array_push($datafix, array(
				"VehicleUserId"    => $data[$i]['location_report_vehicle_user_id'],
				"VehicleId"        => $data[$i]['location_report_vehicle_id'],
				"VehicleDevice"    => $data[$i]['location_report_vehicle_device'],
				"VehicleNo"        => $data[$i]['location_report_vehicle_no'],
				"VehicleName"      => $data[$i]['location_report_vehicle_name'],
				"VehicleType"      => $data[$i]['location_report_vehicle_type'],
				"VehicleCompany"   => $data[$i]['location_report_vehicle_company'],
				"VehicleImei"      => $data[$i]['location_report_imei'],
				"ReportType"       => $data[$i]['location_report_type'],
				"ReportSpeed"      => $data[$i]['location_report_speed'],
				"ReportEngine"     => $data[$i]['location_report_engine'],
				"GpsStatus"        => $data[$i]['location_report_gpsstatus'],
				"GpsTime"          => $data[$i]['location_report_gps_time'],
				"GpsDate"          => $data[$i]['location_report_gps_date'],
				"GpsHour"          => $data[$i]['location_report_gps_hour'],
				"ReportJalur"      => $data[$i]['location_report_jalur'],
				"ReportDirection"  => $data[$i]['location_report_direction'],
				"ReportLocation"   => $data[$i]['location_report_location'],
				"ReportCoordinate" => $data[$i]['location_report_coordinate'],
				"ReportHauling"    => $data[$i]['location_report_hauling'],
				"ReportGroup"      => $data[$i]['location_report_group']
			));
		}


		if ($nr > 0) {
				echo json_encode(array("code" => 200, "msg" => "success",  "data" => $datafix, "payload" => $payload), JSON_NUMERIC_CHECK);
		} else {
				echo json_encode(array("code" => 200, "msg" => "Data Empty"));
		}

		// INI DIAKTIFKAN UNTUK MENCATAT HIT DARI API
		$nowendtime = date("Y-m-d H:i:s");
		$this->insertHitAPI("API Location Hour", $payload, $nowstarttime, $nowendtime);
		$this->db->close();
		$this->db->cache_delete_all();

		exit;
	}

	function getmasterpelanggaran()
	{
		//ini_set('display_errors', 1);
		$nowstarttime = date("Y-m-d H:i:s");
		header("Content-Type: application/json");

		$token      = "UGaW5kNkjhA782GBNS1616KbswQYa5372bsdexVNT16";
		$postdata   = json_decode(file_get_contents("php://input"));
		$allvehicle = 0;
		$payload    = "";
		$now        = date("Ymd");

		$headers = null;
		if (isset($_SERVER['Authorization']))
		{
						$headers = trim($_SERVER["Authorization"]);
				}
		else if (isset($_SERVER['HTTP_AUTHORIZATION']))
		{ //Nginx or fast CGI
						$headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
				}
				else if (function_exists('apache_request_headers'))
				{
						$requestHeaders = apache_request_headers();
						// Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
						$requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
						if (isset($requestHeaders['Authorization']))
						{
								$headers = trim($requestHeaders['Authorization']);
						}
				}
		//print_r($headers." || ".$token." || ".$postdata->UserId);exit();

		if($headers != $token)
		{
			$feature["code"]    = 400;
			$feature["msg"]     = "Invalid Authorization Key ! ";
			$feature["payload"] = $payload;
			echo json_encode($feature);
			exit;
		}

		$feature = array();

		if(!isset($postdata->UserId) || $postdata->UserId == "")
		{
			$feature["code"]    = 400;
			$feature["msg"]     = "Invalid User ID";
			$feature["payload"] = $payload;
			echo json_encode($feature);
			exit;
		}else{
			
			$payload      		    = array(
				"UserId"          => $postdata->UserId
			);

			if ($postdata->UserId == "") {
				$feature["code"]    = 400;
				$feature["msg"]     = "Invalid User ID";
				$feature["payload"] = $payload;
				echo json_encode($feature);
				exit;
			}else {
				//hanya user yg terdaftar yg bisa akes API
				$this->db->where("api_user",$postdata->UserId);
				$this->db->where("api_token",$headers);
				$this->db->where("api_status",1);
				$this->db->where("api_flag",0);
				$q = $this->db->get("api_user");
				if($q->num_rows == 0)
				{
					$feature["code"]    = 400;
					$feature["msg"]     = "User & Authorization Key is Not Available!";
					$feature["payload"] = $payload;
					echo json_encode($feature);
					exit;
				}else{

					$UserIDBIB    = 4408;
					$this->dbts = $this->load->database("webtracking_ts", true);
					$this->dbts->order_by("level_name","asc");
					$this->dbts->where("level_user", $UserIDBIB);
					$this->dbts->where("level_flag",0);
					$q          = $this->dbts->get("ts_speed_level");
					$speedlevel = $q->result();
				}
			}
		}


		//jika mobil lebih dari nol
		if(count($speedlevel) > 0)
		{

			$DataToUpload = array();
			//unset($DataToUpload);
			for($z=0;$z<count($speedlevel);$z++)
			{
				$DataToUpload[$z]->LevelName         = $speedlevel[$z]->level_name;
				$DataToUpload[$z]->LevelAlias        = $speedlevel[$z]->level_alias;
				$DataToUpload[$z]->LevelValue        = $speedlevel[$z]->level_value;
				$DataToUpload[$z]->LevelUser         = $speedlevel[$z]->level_user;
				$DataToUpload[$z]->LevelType         = $speedlevel[$z]->level_type;
				$DataToUpload[$z]->LevelValueMin     = $speedlevel[$z]->level_value_min;
				$DataToUpload[$z]->LevelValueMax     = $speedlevel[$z]->level_value_max;
				$DataToUpload[$z]->LevelSanksiLubang = $speedlevel[$z]->level_sanksi_lubang;
				$DataToUpload[$z]->LevelSanksiSkors  = $speedlevel[$z]->level_sanksi_skors;

				//$datajson["Data"] = $DataToUpload;
			}
			//$content = json_encode($datajson);
			$content = $DataToUpload;

			// echo "<pre>";
			// var_dump($content);die();
			// echo "<pre>";

			// echo $content;
			echo json_encode(array("code" => 200, "msg" => "ok", "data" => $content, "payload" => $payload), JSON_NUMERIC_CHECK);
			$nowendtime = date("Y-m-d H:i:s");
			$this->insertHitAPI("API Master Data Pelanggaran Overspeed (IM KTT)",$payload,$nowstarttime,$nowendtime);
			$this->db->close();
			$this->db->cache_delete_all();
		}
		exit;
	}

	function getlocationreport_bk_reguler_2022_10_31()
	{
		ini_set('memory_limit', "2G");
		ini_set('max_execution_time', 180); // 3 minutes
		$nowstarttime = date("Y-m-d H:i:s");
		header("Content-Type: application/json");

		$token            = "UGaW5kNkjhA782GBNS1616KbswQYa5372bsdexVNT16";
		$postdata         = json_decode(file_get_contents("php://input"));
		$allvehicle       = 0;
		$now              = date("Ymd");
		$payload          = "";
    $forbidden_symbol = array("'", ",", ".", "?", "!", ";", ":", "-");

		$headers = null;
		if (isset($_SERVER['Authorization']))
		{
        $headers = trim($_SERVER["Authorization"]);
    }
		else if (isset($_SERVER['HTTP_AUTHORIZATION']))
		{ //Nginx or fast CGI
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    }
    else if (function_exists('apache_request_headers'))
    {
      $requestHeaders = apache_request_headers();
      // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
      $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
      if (isset($requestHeaders['Authorization']))
      {
        $headers = trim($requestHeaders['Authorization']);
      }
    }

		if($headers != $token)
    {
			$feature["code"]    = 400;
			$feature["msg"]     = "Invalid Authorization Key ! ";
			$feature["payload"] = $payload;
			echo json_encode($feature);
			exit;
		}

		$feature = array();

		if(!isset($postdata->UserId) || $postdata->UserId == "")
		{
			$feature["code"]    = 400;
			$feature["msg"]     = "Invalid User ID";
			$feature["payload"] = $payload;
			echo json_encode($feature);
			exit;
		}else{

			//hanya user yg terdaftar yg bisa akes API
			$this->db->where("api_user",$postdata->UserId);
			$this->db->where("api_token",$headers);
			$this->db->where("api_status",1);
			$this->db->where("api_flag",0);
			$q = $this->db->get("api_user");
			if($q->num_rows == 0)
			{
				$feature["code"]    = 400;
				$feature["msg"]     = "User & Authorization Key is Not Available!";
				$feature["payload"] = $payload;
				echo json_encode($feature);
				exit;
			}

		}

    $payload = array(
     "UserId"            => $postdata->UserId,
		 "VehicleNo"         => $postdata->VehicleNo,
     "StartTime"    	 	 => $postdata->StartTime,
		 "EndTime"    	 	   => $postdata->EndTime,
		 "Status" 		       => $postdata->Status,
   );

	 if(!isset($postdata->StartTime) || $postdata->StartTime == "")
	 {
		 $feature["code"]    = 400;
		 $feature["msg"]     = "Invalid Start Date Time";
		 $feature["payload"] = $payload;
		 echo json_encode($feature);
		 exit;
	 }

	 if(!isset($postdata->EndTime) || $postdata->EndTime == "")
	 {
		 $feature["code"]    = 400;
		 $feature["msg"]     = "Invalid End Date Time";
		 $feature["payload"] = $payload;
		 echo json_encode($feature);
		 exit;
	 }

	 if($postdata->StartTime != "" && $postdata->EndTime != ""){
		 $startdur = $postdata->StartTime * 60;
		 $enddur = $postdata->EndTime * 60;
	 }

	 if(!isset($postdata->VehicleNo) || $postdata->VehicleNo == "")
	 {
		 $feature["code"]    = 400;
		 $feature["msg"]     = "Invalid Vehicle No";
		 $feature["payload"] = $payload;
		 echo json_encode($feature);
		 exit;
	 }else {
		 $this->db->order_by("vehicle_id","desc");
		 $this->db->where("vehicle_no",$postdata->VehicleNo);
		 $this->db->where("vehicle_user_id",4408);
		 $this->db->where("vehicle_status",1);
		 //$this->db->where("vehicle_active_date2 >",$now); //tidak expired
		 $q = $this->db->get("vehicle");
		 $vehicle = $q->result();

		 if($q->num_rows == 0)
		 {
			 $feature["code"] = 400;
			 $feature["msg"] = "Vehicle Not Found!";
			 $feature["payload"]    = $payload;
			 echo json_encode($feature);
			 exit;
		 }
	 }

	 	if (!isset($postdata->Status) || $postdata->Status == "") {
			$feature["code"]    = 400;
			$feature["msg"]     = "Invalid Status Type";
			$feature["payload"] = $payload;
			echo json_encode($feature);
			exit;
	 	}else {
	 		$statusfix = "";
				if ($postdata->Status == "all") {
					$statusfix = "all";
				}elseif ($postdata->Status == "move") {
					$statusfix = "location";
				}elseif ($postdata->Status == "idle") {
					$statusfix = "location_idle";
				}elseif ($postdata->Status == "off") {
					$statusfix = "location_off";
				}
	 	}

		// echo "<pre>";
		// var_dump($statusfix);die();
		// echo "<pre>";

	 $report        = "location_"; // new report
	 $report_sum    = "summary_";

	 $sdate         = date("Y-m-d H:i:s", strtotime($postdata->StartTime));
	 $edate         = date("Y-m-d H:i:s", strtotime($postdata->EndTime));

	 $d1            = date("d", strtotime($postdata->StartTime));
	 $d2            = date("d", strtotime($postdata->EndTime));

	 $m1            = date("F", strtotime($postdata->StartTime));
	 $m2            = date("F", strtotime($postdata->EndTime));
	 $year          = date("Y", strtotime($postdata->StartTime));
	 $year2         = date("Y", strtotime($postdata->EndTime));
	 $rows          = array();
	 $rows2         = array();
	 $total_q       = 0;
	 $total_q2      = 0;

	 $error         = "";
	 $rows_summary  = "";

	 // echo "<pre>";
	 // var_dump($m1.'-'.$m2);die();
	 // echo "<pre>";

	 $location_list = array("location","location_off","location_idle");

	 if ($postdata->VehicleNo == "")
	 {
		 $feature["code"]    = 400;
		 $feature["msg"]     = "Invalid Vehicle No";
		 $feature["payload"] = $payload;
		 echo json_encode($feature);
		 exit;
	 }

	 if ($d1 != $d2)
	 {
		 $feature["code"]    = 400;
		 $feature["msg"]     = "Invalid Date Time. Date time must be in the same date";
		 $feature["payload"] = $payload;
		 echo json_encode($feature);
		 exit;
	 }

	 if ($m1 != $m2)
	 {
		 $feature["code"]    = 400;
		 $feature["msg"]     = "Invalid Date Time. Date time must be in the same month";
		 $feature["payload"] = $payload;
		 echo json_encode($feature);
		 exit;
	 }

	 if ($year != $year2)
	 {
		 $feature["code"]    = 400;
		 $feature["msg"]     = "Invalid Date Time. Date time must be in the same year";
		 $feature["payload"] = $payload;
		 echo json_encode($feature);
		 exit;
	 }

	 if ($error != "")
	 {
		 $callback['error'] = true;
		 $callback['message'] = $error;

		 echo json_encode($callback);
		 return;
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

	 switch ($m2)
	 {
		 case "January":
					 $dbtable2 = $report."januari_".$year;
		 $dbtable2_sum = $report_sum."januari_".$year;
		 break;
		 case "February":
					 $dbtable2 = $report."februari_".$year;
		 $dbtable2_sum = $report_sum."februari_".$year;
		 break;
		 case "March":
					 $dbtable2 = $report."maret_".$year;
		 $dbtable2_sum = $report_sum."maret_".$year;
		 break;
		 case "April":
					 $dbtable2 = $report."april_".$year;
		 $dbtable2_sum = $report_sum."april_".$year;
		 break;
		 case "May":
					 $dbtable2 = $report."mei_".$year;
		 $dbtable2_sum = $report_sum."mei_".$year;
		 break;
		 case "June":
					 $dbtable2 = $report."juni_".$year;
		 $dbtable2_sum = $report_sum."juni_".$year;
		 break;
		 case "July":
					 $dbtable2 = $report."juli_".$year;
		 $dbtable2_sum = $report_sum."juli_".$year;
		 break;
		 case "August":
					 $dbtable2 = $report."agustus_".$year;
		 $dbtable2_sum = $report_sum."agustus_".$year;
		 break;
		 case "September":
					 $dbtable2 = $report."september_".$year;
		 $dbtable2_sum = $report_sum."september_".$year;
		 break;
		 case "October":
					 $dbtable2 = $report."oktober_".$year;
		 $dbtable2_sum = $report_sum."oktober_".$year;
		 break;
		 case "November":
					 $dbtable2 = $report."november_".$year;
		 $dbtable2_sum = $report_sum."november_".$year;
		 break;
		 case "December":
					 $dbtable2 = $report."desember_".$year;
		 $dbtable2_sum = $report_sum."desember_".$year;
		 break;
	 }

	 // echo "<pre>";
	 // var_dump($dbtable);die();
	 // echo "<pre>";

		 $this->dbtrip = $this->load->database("tensor_report",true);
		 $this->dbtrip->order_by("location_report_gps_time","asc");

		 if($postdata->VehicleNo != "all"){
			 // $this->dbtrip->where("location_report_vehicle_device", $vehicle);
			 $this->dbtrip->where("location_report_vehicle_no", $postdata->VehicleNo);
		 }

		 if($statusfix != "all"){
			 $this->dbtrip->where("location_report_name", $statusfix);
		 }

		 $this->dbtrip->where("location_report_gps_time >=",$sdate);
		 $this->dbtrip->where("location_report_gps_time <=", $edate);

		 $q = $this->dbtrip->get($dbtable);
		 $rows = $q->result();

		 // $dbtable.'-'.$dbtable2.'-'.$dbtable2_sum
		 // $vehicle.'-'.$type_location.'-'.$location_end.'-'.$statusname.'-'.$type_speed

		 // echo "<pre>";
		 // var_dump($rows);die();
		 // echo "<pre>";

	 $datafix = array();

	 if(sizeof($rows) > 0)
	 {
		 for ($i=0; $i < sizeof($rows); $i++) {

			 if ($rows[$i]->location_report_name == "location") {
				 $reportnamefix = "move";
			 }elseif ($rows[$i]->location_report_name == "location_idle") {
				 $reportnamefix = "idle";
			 }elseif ($rows[$i]->location_report_name == "location_off") {
				 $reportnamefix = "off";
			 }

		 	array_push($datafix, array(
				 // "ReportId"                          => $rows[$i]->location_report_id,
	       "VehicleUserId"                     => $rows[$i]->location_report_vehicle_user_id,
	       "VehicleId"                         => $rows[$i]->location_report_vehicle_id,
	       "VehicleDevice"                     => $rows[$i]->location_report_vehicle_device,
	       "VehicleNo"                         => $rows[$i]->location_report_vehicle_no,
	       "VehicleName"                       => $rows[$i]->location_report_vehicle_name,
	       "VehicleType"                       => $rows[$i]->location_report_vehicle_type,
	       "VehicleCompany"                    => $rows[$i]->location_report_vehicle_company,
	       "VehicleImei" 		                   => $rows[$i]->location_report_imei,
	       // "ReportType"                        => $rows[$i]->location_report_type,
	       "ReportName"                        => $reportnamefix,
	       "ReportSpeed"                       => $rows[$i]->location_report_speed,
	       "ReportGpsStatus"                   => $rows[$i]->location_report_gpsstatus,
	       "ReportGpsTime"                     => $rows[$i]->location_report_gps_time,
	       // "ReportGeofenceId"                  => $rows[$i]->location_report_geofence_id,
	       "ReportGeofenceName"                => $rows[$i]->location_report_geofence_name,
	       // "ReportGeofenceLimit"               => $rows[$i]->location_report_geofence_limit,
	       "ReportGeofenceType"                => $rows[$i]->location_report_geofence_type,
	       "ReportJalur"                       => $rows[$i]->location_report_jalur,
	       "ReportDirection"                   => $rows[$i]->location_report_direction,
	       "ReportLocation"                    => $rows[$i]->location_report_location,
	       "ReportCoordinate"                  => $rows[$i]->location_report_coordinate,
	       // "location_report_latitude"       => $rows[$i]->location_report_latitude,
	       // "location_report_longitude"      => $rows[$i]->location_report_longitude,
	       "ReportOdometer"                    => $rows[$i]->location_report_odometer,
	       "Report_fuel_data"                  => $rows[$i]->location_report_fuel_data,
	       // "location_report_fuel_data_fix"  => $rows[$i]->location_report_fuel_data_fix,
	       // "location_report_fuel_liter"     => $rows[$i]->location_report_fuel_liter,
	       // "location_report_fuel_liter_fix" => $rows[$i]->location_report_fuel_liter_fix,
	       // "location_report_view"           => $rows[$i]->location_report_view,
	       // "location_report_event"          => $rows[$i]->location_report_event,
	       "ReportGsm"                         => $rows[$i]->location_report_gsm,
	       "ReportSat"                         => $rows[$i]->location_report_sat
			));
		 }
		 echo json_encode(array("code" => 200, "msg" => "success",  "data" => $datafix, "payload" => $payload), JSON_NUMERIC_CHECK);
	 }
	 else
	 {
		 echo json_encode(array("code" => 200, "msg" => "success",  "data" => array(), "payload" => $payload), JSON_NUMERIC_CHECK);
	 }

	 // INI DIAKTIFKAN UNTUK MENCATAT HIT DARI API
	 $nowendtime = date("Y-m-d H:i:s");
	 $this->insertHitAPI("API Location Report", $payload, $nowstarttime, $nowendtime);
	 $this->db->close();
	 $this->db->cache_delete_all();

	 exit;
	}

	function getlocationreport()
	{
		ini_set('memory_limit', "2G");
		ini_set('max_execution_time', 180); // 3 minutes
		$nowstarttime = date("Y-m-d H:i:s");
		header("Content-Type: application/json");

		$token            = "UGaW5kNkjhA782GBNS1616KbswQYa5372bsdexVNT16";
		$postdata         = json_decode(file_get_contents("php://input"));
		$allvehicle       = 0;
		$now              = date("Ymd");
		$payload          = "";
		$forbidden_symbol = array("'", ",", ".", "?", "!", ";", ":", "-");

		$headers = null;
		if (isset($_SERVER['Authorization']))
		{
        $headers = trim($_SERVER["Authorization"]);
		}
			else if (isset($_SERVER['HTTP_AUTHORIZATION']))
			{ //Nginx or fast CGI
			$headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
		}
		else if (function_exists('apache_request_headers'))
		{
		  $requestHeaders = apache_request_headers();
		  // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
		  $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
		  if (isset($requestHeaders['Authorization']))
		  {
			$headers = trim($requestHeaders['Authorization']);
		  }
		}

		if($headers != $token)
		{
			$feature["code"]    = 400;
			$feature["msg"]     = "Invalid Authorization Key ! ";
			$feature["payload"] = $payload;
			echo json_encode($feature);
			exit;
		}

		$feature = array();

		if(!isset($postdata->UserId) || $postdata->UserId == "")
		{
			$feature["code"]    = 400;
			$feature["msg"]     = "Invalid User ID";
			$feature["payload"] = $payload;
			echo json_encode($feature);
			exit;
		}else{

			//hanya user yg terdaftar yg bisa akes API
			$this->db->where("api_user",$postdata->UserId);
			$this->db->where("api_token",$headers);
			$this->db->where("api_status",1);
			$this->db->where("api_flag",0);
			$q = $this->db->get("api_user");
			if($q->num_rows == 0)
			{
				$feature["code"]    = 400;
				$feature["msg"]     = "User & Authorization Key is Not Available!";
				$feature["payload"] = $payload;
				echo json_encode($feature);
				exit;
			}

		}



	 if(!isset($postdata->StartTime) || $postdata->StartTime == "")
	 {
		 $feature["code"]    = 400;
		 $feature["msg"]     = "Invalid Start Date Time";
		 $feature["payload"] = $payload;
		 echo json_encode($feature);
		 exit;
	 }

	 if(!isset($postdata->EndTime) || $postdata->EndTime == "")
	 {
		 $feature["code"]    = 400;
		 $feature["msg"]     = "Invalid End Date Time";
		 $feature["payload"] = $payload;
		 echo json_encode($feature);
		 exit;
	 }

	 if($postdata->StartTime != "" && $postdata->EndTime != ""){
		 $startdur = $postdata->StartTime * 60;
		 $enddur = $postdata->EndTime * 60;
	 }

	 // if(!isset($postdata->VehicleNo) || $postdata->VehicleNo == "")
	 // {
		//  $feature["code"]    = 400;
		//  $feature["msg"]     = "Invalid Vehicle No";
		//  $feature["payload"] = $payload;
		//  echo json_encode($feature);
		//  exit;
	 // }else {
		 $this->db->order_by("vehicle_id","desc");

		 if ($postdata->VehicleNo != "" && $postdata->VehicleDevice != "") {
			 $this->db->where("vehicle_no",$postdata->VehicleNo);
			 $this->db->where("vehicle_device",$postdata->VehicleDevice);
		 }elseif ($postdata->VehicleNo != "") {
			 $this->db->where("vehicle_no",$postdata->VehicleNo);
		 }elseif ($postdata->VehicleDevice != "") {
			 $this->db->where("vehicle_device",$postdata->VehicleDevice);
		 }else {
  			 $feature["code"] = 400;
  			 $feature["msg"] = "Vehicle Not Found!";
  			 $feature["payload"]    = $payload;
  			 echo json_encode($feature);
  			 exit;
		 }
		 $this->db->where("vehicle_user_id",4408);
		 // $this->db->where("vehicle_status",1);
		 //$this->db->where("vehicle_active_date2 >",$now); //tidak expired
		 $q = $this->db->get("vehicle");
		 $vehicle = $q->result();

		 if($q->num_rows == 0)
		 {
			 $feature["code"] = 400;
			 $feature["msg"] = "Vehicle Not Found!";
			 $feature["payload"]    = $payload;
			 echo json_encode($feature);
			 exit;
		 }
	 // }

	 	if (!isset($postdata->Status) || $postdata->Status == "") {
			$feature["code"]    = 400;
			$feature["msg"]     = "Invalid Status Type";
			$feature["payload"] = $payload;
			echo json_encode($feature);
			exit;
	 	}else {
	 		$statusfix = "";
				if ($postdata->Status == "all") {
					$statusfix = "all";
				}elseif ($postdata->Status == "move") {
					$statusfix = "location";
				}elseif ($postdata->Status == "idle") {
					$statusfix = "location_idle";
				}elseif ($postdata->Status == "off") {
					$statusfix = "location_off";
				}
	 	}

		//NEW CONDITION
		$postdata_rom = "";
		$postdata_port = "";
		$only_rom = 0;
		$only_port = 0;
		if(isset($postdata->Rom) && $postdata->Rom == "all")
		{
			$rombib_register = $this->config->item('rombib_register_autocheck'); // rom legal
			$postdata_rom = $postdata->Rom;
			$only_rom = 1;

			$payload = array(
			 "UserId"          => $postdata->UserId,
				 "VehicleNo"     => $postdata->VehicleNo,
				 "VehicleDevice" => $postdata->VehicleDevice,
				 "StartTime" 	 	 => $postdata->StartTime,
				 "EndTime" 	 	   => $postdata->EndTime,
				 "Status" 		   => $postdata->Status,
				 "Rom" 	 	 		   => $postdata_rom,

			);
		}

		if(isset($postdata->Port) && $postdata->Port == "all")
		{
			$port_register = $this->config->item('port_register_autocheck');
			$postdata_port = $postdata->Port;
			$only_port = 1;

			$payload = array(
			 "UserId"            => $postdata->UserId,
				 "VehicleNo"         => $postdata->VehicleNo,
				 "VehicleDevice" => $postdata->VehicleDevice,
				 "StartTime"    	 	 => $postdata->StartTime,
				 "EndTime"    	 	   => $postdata->EndTime,
				 "Status" 		       => $postdata->Status,
				 "Port" 		       => $postdata_port,
			);

		}

		if($only_port == 1 && $only_rom == 1){

			$merge_register = array_merge($rombib_register, $port_register);

			$payload = array(
				 "UserId"            => $postdata->UserId,
				 "VehicleNo"         => $postdata->VehicleNo,
				 "VehicleDevice" => $postdata->VehicleDevice,
				 "StartTime"    	 	 => $postdata->StartTime,
				 "EndTime"    	 	   => $postdata->EndTime,
				 "Status" 		       => $postdata->Status,
				 "Rom"    	 	  		 => $postdata_rom,
				 "Port" 		       => $postdata_port,
			);
		}

		if($only_port == 0 && $only_rom == 0){

			$payload = array(
				 "UserId"            => $postdata->UserId,
				 "VehicleNo"         => $postdata->VehicleNo,
				 "VehicleDevice" => $postdata->VehicleDevice,
				 "StartTime"    	 	 => $postdata->StartTime,
				 "EndTime"    	 	   => $postdata->EndTime,
				 "Status" 		       => $postdata->Status,

			);
		}


		// echo "<pre>";
		// var_dump("masuk");die();
		// echo "<pre>";

	 $report        = "location_"; // new report
	 $report_sum    = "summary_";

	 $sdate         = date("Y-m-d H:i:s", strtotime($postdata->StartTime));
	 $edate         = date("Y-m-d H:i:s", strtotime($postdata->EndTime));

	 $d1            = date("d", strtotime($postdata->StartTime));
	 $d2            = date("d", strtotime($postdata->EndTime));

	 $m1            = date("F", strtotime($postdata->StartTime));
	 $m2            = date("F", strtotime($postdata->EndTime));
	 $year          = date("Y", strtotime($postdata->StartTime));
	 $year2         = date("Y", strtotime($postdata->EndTime));
	 $rows          = array();
	 $rows2         = array();
	 $total_q       = 0;
	 $total_q2      = 0;

	 $error         = "";
	 $rows_summary  = "";

	 // echo "<pre>";
	 // var_dump($m1.'-'.$m2);die();
	 // echo "<pre>";

	 $location_list = array("location","location_off","location_idle");

	 // if ($postdata->VehicleNo == "")
	 // {
		//  $feature["code"]    = 400;
		//  $feature["msg"]     = "Invalid Vehicle No";
		//  $feature["payload"] = $payload;
		//  echo json_encode($feature);
		//  exit;
	 // }

	 if ($d1 != $d2)
	 {
		 $feature["code"]    = 400;
		 $feature["msg"]     = "Invalid Date Time. Date time must be in the same date";
		 $feature["payload"] = $payload;
		 echo json_encode($feature);
		 exit;
	 }

	 if ($m1 != $m2)
	 {
		 $feature["code"]    = 400;
		 $feature["msg"]     = "Invalid Date Time. Date time must be in the same month";
		 $feature["payload"] = $payload;
		 echo json_encode($feature);
		 exit;
	 }

	 if ($year != $year2)
	 {
		 $feature["code"]    = 400;
		 $feature["msg"]     = "Invalid Date Time. Date time must be in the same year";
		 $feature["payload"] = $payload;
		 echo json_encode($feature);
		 exit;
	 }

	 if ($error != "")
	 {
		 $callback['error'] = true;
		 $callback['message'] = $error;

		 echo json_encode($callback);
		 return;
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

	 switch ($m2)
	 {
		 case "January":
					 $dbtable2 = $report."januari_".$year;
		 $dbtable2_sum = $report_sum."januari_".$year;
		 break;
		 case "February":
					 $dbtable2 = $report."februari_".$year;
		 $dbtable2_sum = $report_sum."februari_".$year;
		 break;
		 case "March":
					 $dbtable2 = $report."maret_".$year;
		 $dbtable2_sum = $report_sum."maret_".$year;
		 break;
		 case "April":
					 $dbtable2 = $report."april_".$year;
		 $dbtable2_sum = $report_sum."april_".$year;
		 break;
		 case "May":
					 $dbtable2 = $report."mei_".$year;
		 $dbtable2_sum = $report_sum."mei_".$year;
		 break;
		 case "June":
					 $dbtable2 = $report."juni_".$year;
		 $dbtable2_sum = $report_sum."juni_".$year;
		 break;
		 case "July":
					 $dbtable2 = $report."juli_".$year;
		 $dbtable2_sum = $report_sum."juli_".$year;
		 break;
		 case "August":
					 $dbtable2 = $report."agustus_".$year;
		 $dbtable2_sum = $report_sum."agustus_".$year;
		 break;
		 case "September":
					 $dbtable2 = $report."september_".$year;
		 $dbtable2_sum = $report_sum."september_".$year;
		 break;
		 case "October":
					 $dbtable2 = $report."oktober_".$year;
		 $dbtable2_sum = $report_sum."oktober_".$year;
		 break;
		 case "November":
					 $dbtable2 = $report."november_".$year;
		 $dbtable2_sum = $report_sum."november_".$year;
		 break;
		 case "December":
					 $dbtable2 = $report."desember_".$year;
		 $dbtable2_sum = $report_sum."desember_".$year;
		 break;
	 }

	 // echo "<pre>";
	 // var_dump($dbtable);die();
	 // echo "<pre>";

		 $this->dbtrip = $this->load->database("tensor_report",true);
		 $this->dbtrip->order_by("location_report_gps_time","asc");

		 if ($postdata->VehicleNo != "" && $postdata->VehicleDevice != "") {
			if($postdata->VehicleNo != "all"){
			 $this->dbtrip->where("location_report_vehicle_no", $postdata->VehicleNo);
		 }

		 if($postdata->VehicleDevice != "all"){
			 $this->dbtrip->where("location_report_vehicle_device",$postdata->VehicleDevice);
		 }
		}elseif ($postdata->VehicleNo != "") {
			if($postdata->VehicleNo != "all"){
			 $this->dbtrip->where("location_report_vehicle_no", $postdata->VehicleNo);
		 }
		}elseif ($postdata->VehicleDevice != "") {
			if($postdata->VehicleNo != "all"){
			 $this->dbtrip->where("location_report_vehicle_device", $postdata->VehicleDevice);
		 }
		}

		 if($statusfix != "all"){
			 $this->dbtrip->where("location_report_name", $statusfix);
		 }

		 $this->dbtrip->where("location_report_gps_time >=",$sdate);
		 $this->dbtrip->where("location_report_gps_time <=", $edate);

		if($only_port == 0 && $only_rom == 1){
			$this->dbtrip->where_in("location_report_location", $rombib_register);

		}

		if($only_port == 1 && $only_rom == 0){
			$this->dbtrip->where_in("location_report_location", $port_register);
		}

		if($only_port == 1 && $only_rom == 1){
			$this->dbtrip->where_in("location_report_location", $merge_register);
		}

		 $q = $this->dbtrip->get($dbtable);
		 $rows = $q->result();

		 // $dbtable.'-'.$dbtable2.'-'.$dbtable2_sum
		 // $vehicle.'-'.$type_location.'-'.$location_end.'-'.$statusname.'-'.$type_speed

		 // echo "<pre>";
		 // var_dump($rows);die();
		 // echo "<pre>";

	 $datafix = array();

	 if(sizeof($rows) > 0)
	 {
		 for ($i=0; $i < sizeof($rows); $i++) {

			 if ($rows[$i]->location_report_name == "location") {
				 $reportnamefix = "move";
			 }elseif ($rows[$i]->location_report_name == "location_idle") {
				 $reportnamefix = "idle";
			 }elseif ($rows[$i]->location_report_name == "location_off") {
				 $reportnamefix = "off";
			 }

		 	array_push($datafix, array(
				 // "ReportId"                          => $rows[$i]->location_report_id,
	       "VehicleUserId"                     => $rows[$i]->location_report_vehicle_user_id,
	       "VehicleId"                         => $rows[$i]->location_report_vehicle_id,
	       "VehicleDevice"                     => $rows[$i]->location_report_vehicle_device,
	       "VehicleNo"                         => $rows[$i]->location_report_vehicle_no,
	       "VehicleName"                       => $rows[$i]->location_report_vehicle_name,
	       "VehicleType"                       => $rows[$i]->location_report_vehicle_type,
	       "VehicleCompany"                    => $rows[$i]->location_report_vehicle_company,
	       "VehicleImei" 		                   => $rows[$i]->location_report_imei,
	       // "ReportType"                        => $rows[$i]->location_report_type,
	       "ReportName"                        => $reportnamefix,
	       "ReportSpeed"                       => $rows[$i]->location_report_speed,
	       "ReportGpsStatus"                   => $rows[$i]->location_report_gpsstatus,
	       "ReportGpsTime"                     => $rows[$i]->location_report_gps_time,
	       // "ReportGeofenceId"                  => $rows[$i]->location_report_geofence_id,
	       "ReportGeofenceName"                => $rows[$i]->location_report_geofence_name,
	       // "ReportGeofenceLimit"               => $rows[$i]->location_report_geofence_limit,
	       "ReportGeofenceType"                => $rows[$i]->location_report_geofence_type,
	       "ReportJalur"                       => $rows[$i]->location_report_jalur,
	       "ReportDirection"                   => $rows[$i]->location_report_direction,
	       "ReportLocation"                    => $rows[$i]->location_report_location,
	       "ReportCoordinate"                  => $rows[$i]->location_report_coordinate,
	       // "location_report_latitude"       => $rows[$i]->location_report_latitude,
	       // "location_report_longitude"      => $rows[$i]->location_report_longitude,
	       "ReportOdometer"                    => $rows[$i]->location_report_odometer,
	       "Report_fuel_data"                  => $rows[$i]->location_report_fuel_data,
	       // "location_report_fuel_data_fix"  => $rows[$i]->location_report_fuel_data_fix,
	       // "location_report_fuel_liter"     => $rows[$i]->location_report_fuel_liter,
	       // "location_report_fuel_liter_fix" => $rows[$i]->location_report_fuel_liter_fix,
	       // "location_report_view"           => $rows[$i]->location_report_view,
	       // "location_report_event"          => $rows[$i]->location_report_event,
	       "ReportGsm"                         => $rows[$i]->location_report_gsm,
	       "ReportSat"                         => $rows[$i]->location_report_sat
			));
		 }
		 echo json_encode(array("code" => 200, "msg" => "success",  "data" => $datafix, "payload" => $payload), JSON_NUMERIC_CHECK);
	 }
	 else
	 {
		 echo json_encode(array("code" => 200, "msg" => "success",  "data" => array(), "payload" => $payload), JSON_NUMERIC_CHECK);
	 }

	 // INI DIAKTIFKAN UNTUK MENCATAT HIT DARI API
	 $nowendtime = date("Y-m-d H:i:s");
	 $this->insertHitAPI("API Location Report", $payload, $nowstarttime, $nowendtime);
	 $this->db->close();
	 $this->db->cache_delete_all();

	 exit;
	}

	function getlocationreport_old()
	{
		ini_set('memory_limit', "2G");
		ini_set('max_execution_time', 180); // 3 minutes
		$nowstarttime = date("Y-m-d H:i:s");
		header("Content-Type: application/json");

		$token            = "UGaW5kNkjhA782GBNS1616KbswQYa5372bsdexVNT16";
		$postdata         = json_decode(file_get_contents("php://input"));
		$allvehicle       = 0;
		$now              = date("Ymd");
		$payload          = "";
		$forbidden_symbol = array("'", ",", ".", "?", "!", ";", ":", "-");

		$headers = null;
		if (isset($_SERVER['Authorization']))
		{
        $headers = trim($_SERVER["Authorization"]);
		}
			else if (isset($_SERVER['HTTP_AUTHORIZATION']))
			{ //Nginx or fast CGI
			$headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
		}
		else if (function_exists('apache_request_headers'))
		{
		  $requestHeaders = apache_request_headers();
		  // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
		  $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
		  if (isset($requestHeaders['Authorization']))
		  {
			$headers = trim($requestHeaders['Authorization']);
		  }
		}

		if($headers != $token)
		{
			$feature["code"]    = 400;
			$feature["msg"]     = "Invalid Authorization Key ! ";
			$feature["payload"] = $payload;
			echo json_encode($feature);
			exit;
		}

		$feature = array();

		if(!isset($postdata->UserId) || $postdata->UserId == "")
		{
			$feature["code"]    = 400;
			$feature["msg"]     = "Invalid User ID";
			$feature["payload"] = $payload;
			echo json_encode($feature);
			exit;
		}else{

			//hanya user yg terdaftar yg bisa akes API
			$this->db->where("api_user",$postdata->UserId);
			$this->db->where("api_token",$headers);
			$this->db->where("api_status",1);
			$this->db->where("api_flag",0);
			$q = $this->db->get("api_user");
			if($q->num_rows == 0)
			{
				$feature["code"]    = 400;
				$feature["msg"]     = "User & Authorization Key is Not Available!";
				$feature["payload"] = $payload;
				echo json_encode($feature);
				exit;
			}

		}



	 if(!isset($postdata->StartTime) || $postdata->StartTime == "")
	 {
		 $feature["code"]    = 400;
		 $feature["msg"]     = "Invalid Start Date Time";
		 $feature["payload"] = $payload;
		 echo json_encode($feature);
		 exit;
	 }

	 if(!isset($postdata->EndTime) || $postdata->EndTime == "")
	 {
		 $feature["code"]    = 400;
		 $feature["msg"]     = "Invalid End Date Time";
		 $feature["payload"] = $payload;
		 echo json_encode($feature);
		 exit;
	 }

	 if($postdata->StartTime != "" && $postdata->EndTime != ""){
		 $startdur = $postdata->StartTime * 60;
		 $enddur = $postdata->EndTime * 60;
	 }

	 if(!isset($postdata->VehicleNo) || $postdata->VehicleNo == "")
	 {
		 $feature["code"]    = 400;
		 $feature["msg"]     = "Invalid Vehicle No";
		 $feature["payload"] = $payload;
		 echo json_encode($feature);
		 exit;
	 }else {
		 $this->db->order_by("vehicle_id","desc");
		 $this->db->where("vehicle_no",$postdata->VehicleNo);
		 $this->db->where("vehicle_user_id",4408);
		 // $this->db->where("vehicle_status",1);
		 //$this->db->where("vehicle_active_date2 >",$now); //tidak expired
		 $q = $this->db->get("vehicle");
		 $vehicle = $q->result();

	  // echo "<pre>";
 		// var_dump($vehicle);die();
 		// echo "<pre>";

		 if($q->num_rows == 0)
		 {
			 $feature["code"] = 400;
			 $feature["msg"] = "Vehicle Not Found!";
			 $feature["payload"]    = $payload;
			 echo json_encode($feature);
			 exit;
		 }
	 }

	 	if (!isset($postdata->Status) || $postdata->Status == "") {
			$feature["code"]    = 400;
			$feature["msg"]     = "Invalid Status Type";
			$feature["payload"] = $payload;
			echo json_encode($feature);
			exit;
	 	}else {
	 		$statusfix = "";
				if ($postdata->Status == "all") {
					$statusfix = "all";
				}elseif ($postdata->Status == "move") {
					$statusfix = "location";
				}elseif ($postdata->Status == "idle") {
					$statusfix = "location_idle";
				}elseif ($postdata->Status == "off") {
					$statusfix = "location_off";
				}
	 	}

		//NEW CONDITION
		$postdata_rom = "";
		$postdata_port = "";
		$only_rom = 0;
		$only_port = 0;
		if(isset($postdata->Rom) && $postdata->Rom == "all")
		{
			$rombib_register = $this->config->item('rombib_register_autocheck'); // rom legal
			$postdata_rom = $postdata->Rom;
			$only_rom = 1;

			$payload = array(
			 "UserId"            => $postdata->UserId,
				 "VehicleNo"         => $postdata->VehicleNo,
				 "StartTime"    	 	 => $postdata->StartTime,
				 "EndTime"    	 	   => $postdata->EndTime,
				 "Status" 		       => $postdata->Status,
				 "Rom"    	 	  		 => $postdata_rom,

			);
		}

		if(isset($postdata->Port) && $postdata->Port == "all")
		{
			$port_register = $this->config->item('port_register_autocheck');
			$postdata_port = $postdata->Port;
			$only_port = 1;

			$payload = array(
			 "UserId"            => $postdata->UserId,
				 "VehicleNo"         => $postdata->VehicleNo,
				 "StartTime"    	 	 => $postdata->StartTime,
				 "EndTime"    	 	   => $postdata->EndTime,
				 "Status" 		       => $postdata->Status,
				 "Port" 		       => $postdata_port,
			);

		}

		if($only_port == 1 && $only_rom == 1){

			$merge_register = array_merge($rombib_register, $port_register);

			$payload = array(
				 "UserId"            => $postdata->UserId,
				 "VehicleNo"         => $postdata->VehicleNo,
				 "StartTime"    	 	 => $postdata->StartTime,
				 "EndTime"    	 	   => $postdata->EndTime,
				 "Status" 		       => $postdata->Status,
				 "Rom"    	 	  		 => $postdata_rom,
				 "Port" 		       => $postdata_port,
			);
		}

		if($only_port == 0 && $only_rom == 0){

			$payload = array(
				 "UserId"            => $postdata->UserId,
				 "VehicleNo"         => $postdata->VehicleNo,
				 "StartTime"    	 	 => $postdata->StartTime,
				 "EndTime"    	 	   => $postdata->EndTime,
				 "Status" 		       => $postdata->Status,

			);
		}


		// echo "<pre>";
		// var_dump($statusfix);die();
		// echo "<pre>";

	 $report        = "location_"; // new report
	 $report_sum    = "summary_";

	 $sdate         = date("Y-m-d H:i:s", strtotime($postdata->StartTime));
	 $edate         = date("Y-m-d H:i:s", strtotime($postdata->EndTime));

	 $d1            = date("d", strtotime($postdata->StartTime));
	 $d2            = date("d", strtotime($postdata->EndTime));

	 $m1            = date("F", strtotime($postdata->StartTime));
	 $m2            = date("F", strtotime($postdata->EndTime));
	 $year          = date("Y", strtotime($postdata->StartTime));
	 $year2         = date("Y", strtotime($postdata->EndTime));
	 $rows          = array();
	 $rows2         = array();
	 $total_q       = 0;
	 $total_q2      = 0;

	 $error         = "";
	 $rows_summary  = "";

	 // echo "<pre>";
	 // var_dump($m1.'-'.$m2);die();
	 // echo "<pre>";

	 $location_list = array("location","location_off","location_idle");

	 if ($postdata->VehicleNo == "")
	 {
		 $feature["code"]    = 400;
		 $feature["msg"]     = "Invalid Vehicle No";
		 $feature["payload"] = $payload;
		 echo json_encode($feature);
		 exit;
	 }

	 if ($d1 != $d2)
	 {
		 $feature["code"]    = 400;
		 $feature["msg"]     = "Invalid Date Time. Date time must be in the same date";
		 $feature["payload"] = $payload;
		 echo json_encode($feature);
		 exit;
	 }

	 if ($m1 != $m2)
	 {
		 $feature["code"]    = 400;
		 $feature["msg"]     = "Invalid Date Time. Date time must be in the same month";
		 $feature["payload"] = $payload;
		 echo json_encode($feature);
		 exit;
	 }

	 if ($year != $year2)
	 {
		 $feature["code"]    = 400;
		 $feature["msg"]     = "Invalid Date Time. Date time must be in the same year";
		 $feature["payload"] = $payload;
		 echo json_encode($feature);
		 exit;
	 }

	 if ($error != "")
	 {
		 $callback['error'] = true;
		 $callback['message'] = $error;

		 echo json_encode($callback);
		 return;
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

	 switch ($m2)
	 {
		 case "January":
					 $dbtable2 = $report."januari_".$year;
		 $dbtable2_sum = $report_sum."januari_".$year;
		 break;
		 case "February":
					 $dbtable2 = $report."februari_".$year;
		 $dbtable2_sum = $report_sum."februari_".$year;
		 break;
		 case "March":
					 $dbtable2 = $report."maret_".$year;
		 $dbtable2_sum = $report_sum."maret_".$year;
		 break;
		 case "April":
					 $dbtable2 = $report."april_".$year;
		 $dbtable2_sum = $report_sum."april_".$year;
		 break;
		 case "May":
					 $dbtable2 = $report."mei_".$year;
		 $dbtable2_sum = $report_sum."mei_".$year;
		 break;
		 case "June":
					 $dbtable2 = $report."juni_".$year;
		 $dbtable2_sum = $report_sum."juni_".$year;
		 break;
		 case "July":
					 $dbtable2 = $report."juli_".$year;
		 $dbtable2_sum = $report_sum."juli_".$year;
		 break;
		 case "August":
					 $dbtable2 = $report."agustus_".$year;
		 $dbtable2_sum = $report_sum."agustus_".$year;
		 break;
		 case "September":
					 $dbtable2 = $report."september_".$year;
		 $dbtable2_sum = $report_sum."september_".$year;
		 break;
		 case "October":
					 $dbtable2 = $report."oktober_".$year;
		 $dbtable2_sum = $report_sum."oktober_".$year;
		 break;
		 case "November":
					 $dbtable2 = $report."november_".$year;
		 $dbtable2_sum = $report_sum."november_".$year;
		 break;
		 case "December":
					 $dbtable2 = $report."desember_".$year;
		 $dbtable2_sum = $report_sum."desember_".$year;
		 break;
	 }

	 // echo "<pre>";
	 // var_dump($dbtable);die();
	 // echo "<pre>";

		 $this->dbtrip = $this->load->database("tensor_report",true);
		 $this->dbtrip->order_by("location_report_gps_time","asc");

		 if($postdata->VehicleNo != "all"){
			 // $this->dbtrip->where("location_report_vehicle_device", $vehicle);
			 $this->dbtrip->where("location_report_vehicle_no", $postdata->VehicleNo);
		 }

		 if($statusfix != "all"){
			 $this->dbtrip->where("location_report_name", $statusfix);
		 }

		 $this->dbtrip->where("location_report_gps_time >=",$sdate);
		 $this->dbtrip->where("location_report_gps_time <=", $edate);

		if($only_port == 0 && $only_rom == 1){
			$this->dbtrip->where_in("location_report_location", $rombib_register);

		}

		if($only_port == 1 && $only_rom == 0){
			$this->dbtrip->where_in("location_report_location", $port_register);
		}

		if($only_port == 1 && $only_rom == 1){
			$this->dbtrip->where_in("location_report_location", $merge_register);
		}

		 $q = $this->dbtrip->get($dbtable);
		 $rows = $q->result();

		 // $dbtable.'-'.$dbtable2.'-'.$dbtable2_sum
		 // $vehicle.'-'.$type_location.'-'.$location_end.'-'.$statusname.'-'.$type_speed

		 // echo "<pre>";
		 // var_dump($rows);die();
		 // echo "<pre>";

	 $datafix = array();

	 if(sizeof($rows) > 0)
	 {
		 for ($i=0; $i < sizeof($rows); $i++) {

			 if ($rows[$i]->location_report_name == "location") {
				 $reportnamefix = "move";
			 }elseif ($rows[$i]->location_report_name == "location_idle") {
				 $reportnamefix = "idle";
			 }elseif ($rows[$i]->location_report_name == "location_off") {
				 $reportnamefix = "off";
			 }

		 	array_push($datafix, array(
				 // "ReportId"                          => $rows[$i]->location_report_id,
	       "VehicleUserId"                     => $rows[$i]->location_report_vehicle_user_id,
	       "VehicleId"                         => $rows[$i]->location_report_vehicle_id,
	       "VehicleDevice"                     => $rows[$i]->location_report_vehicle_device,
	       "VehicleNo"                         => $rows[$i]->location_report_vehicle_no,
	       "VehicleName"                       => $rows[$i]->location_report_vehicle_name,
	       "VehicleType"                       => $rows[$i]->location_report_vehicle_type,
	       "VehicleCompany"                    => $rows[$i]->location_report_vehicle_company,
	       "VehicleImei" 		                   => $rows[$i]->location_report_imei,
	       // "ReportType"                        => $rows[$i]->location_report_type,
	       "ReportName"                        => $reportnamefix,
	       "ReportSpeed"                       => $rows[$i]->location_report_speed,
	       "ReportGpsStatus"                   => $rows[$i]->location_report_gpsstatus,
	       "ReportGpsTime"                     => $rows[$i]->location_report_gps_time,
	       // "ReportGeofenceId"                  => $rows[$i]->location_report_geofence_id,
	       "ReportGeofenceName"                => $rows[$i]->location_report_geofence_name,
	       // "ReportGeofenceLimit"               => $rows[$i]->location_report_geofence_limit,
	       "ReportGeofenceType"                => $rows[$i]->location_report_geofence_type,
	       "ReportJalur"                       => $rows[$i]->location_report_jalur,
	       "ReportDirection"                   => $rows[$i]->location_report_direction,
	       "ReportLocation"                    => $rows[$i]->location_report_location,
	       "ReportCoordinate"                  => $rows[$i]->location_report_coordinate,
	       // "location_report_latitude"       => $rows[$i]->location_report_latitude,
	       // "location_report_longitude"      => $rows[$i]->location_report_longitude,
	       "ReportOdometer"                    => $rows[$i]->location_report_odometer,
	       "Report_fuel_data"                  => $rows[$i]->location_report_fuel_data,
	       // "location_report_fuel_data_fix"  => $rows[$i]->location_report_fuel_data_fix,
	       // "location_report_fuel_liter"     => $rows[$i]->location_report_fuel_liter,
	       // "location_report_fuel_liter_fix" => $rows[$i]->location_report_fuel_liter_fix,
	       // "location_report_view"           => $rows[$i]->location_report_view,
	       // "location_report_event"          => $rows[$i]->location_report_event,
	       "ReportGsm"                         => $rows[$i]->location_report_gsm,
	       "ReportSat"                         => $rows[$i]->location_report_sat
			));
		 }
		 echo json_encode(array("code" => 200, "msg" => "success",  "data" => $datafix, "payload" => $payload), JSON_NUMERIC_CHECK);
	 }
	 else
	 {
		 echo json_encode(array("code" => 200, "msg" => "success",  "data" => array(), "payload" => $payload), JSON_NUMERIC_CHECK);
	 }

	 // INI DIAKTIFKAN UNTUK MENCATAT HIT DARI API
	 $nowendtime = date("Y-m-d H:i:s");
	 $this->insertHitAPI("API Location Report", $payload, $nowstarttime, $nowendtime);
	 $this->db->close();
	 $this->db->cache_delete_all();

	 exit;
	}

	function getlocationreportv2()
	{
		ini_set('memory_limit', "2G");
		ini_set('max_execution_time', 180); // 3 minutes
		$nowstarttime = date("Y-m-d H:i:s");
		header("Content-Type: application/json");

		$token            = "UGaW5kNkjhA782GBNS1616KbswQYa5372bsdexVNT16";
		$postdata         = json_decode(file_get_contents("php://input"));
		$allvehicle       = 0;
		$now              = date("Ymd");
		$payload          = "";
		$forbidden_symbol = array("'", ",", ".", "?", "!", ";", ":", "-");

		$headers = null;
		if (isset($_SERVER['Authorization']))
		{
        $headers = trim($_SERVER["Authorization"]);
		}
			else if (isset($_SERVER['HTTP_AUTHORIZATION']))
			{ //Nginx or fast CGI
			$headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
		}
		else if (function_exists('apache_request_headers'))
		{
		  $requestHeaders = apache_request_headers();
		  // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
		  $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
		  if (isset($requestHeaders['Authorization']))
		  {
			$headers = trim($requestHeaders['Authorization']);
		  }
		}

		if($headers != $token)
		{
			$feature["code"]    = 400;
			$feature["msg"]     = "Invalid Authorization Key ! ";
			$feature["payload"] = $payload;
			echo json_encode($feature);
			exit;
		}

		$feature = array();

		if(!isset($postdata->UserId) || $postdata->UserId == "")
		{
			$feature["code"]    = 400;
			$feature["msg"]     = "Invalid User ID";
			$feature["payload"] = $payload;
			echo json_encode($feature);
			exit;
		}else{

			//hanya user yg terdaftar yg bisa akes API
			$this->db->where("api_user",$postdata->UserId);
			$this->db->where("api_token",$headers);
			$this->db->where("api_status",1);
			$this->db->where("api_flag",0);
			$q = $this->db->get("api_user");
			if($q->num_rows == 0)
			{
				$feature["code"]    = 400;
				$feature["msg"]     = "User & Authorization Key is Not Available!";
				$feature["payload"] = $payload;
				echo json_encode($feature);
				exit;
			}

		}



	 if(!isset($postdata->StartTime) || $postdata->StartTime == "")
	 {
		 $feature["code"]    = 400;
		 $feature["msg"]     = "Invalid Start Date Time";
		 $feature["payload"] = $payload;
		 echo json_encode($feature);
		 exit;
	 }

	 if(!isset($postdata->EndTime) || $postdata->EndTime == "")
	 {
		 $feature["code"]    = 400;
		 $feature["msg"]     = "Invalid End Date Time";
		 $feature["payload"] = $payload;
		 echo json_encode($feature);
		 exit;
	 }

	 if($postdata->StartTime != "" && $postdata->EndTime != ""){
		 $startdur = $postdata->StartTime * 60;
		 $enddur = $postdata->EndTime * 60;
	 }

	 if(!isset($postdata->VehicleNo) || $postdata->VehicleNo == "")
	 {
		 $feature["code"]    = 400;
		 $feature["msg"]     = "Invalid Vehicle No";
		 $feature["payload"] = $payload;
		 echo json_encode($feature);
		 exit;
	 }else {
		 $this->db->order_by("vehicle_id","desc");
		 $this->db->select("vehicle_id,vehicle_user_id,vehicle_device,vehicle_no,vehicle_name,vehicle_device,vehicle_company,vehicle_type,vehicle_mv03,vehicle_info");
		 $this->db->where("vehicle_no",$postdata->VehicleNo);
		 $this->db->where("vehicle_user_id",4408);
		 // $this->db->where("vehicle_status",1);
		 //$this->db->where("vehicle_active_date2 >",$now); //tidak expired
		 $q = $this->db->get("vehicle");
		 $rowvehicle = $q->row();

		 if($q->num_rows == 0)
		 {
			 $feature["code"] = 400;
			 $feature["msg"] = "Vehicle Not Found!";
			 $feature["payload"]    = $payload;
			 echo json_encode($feature);
			 exit;
		 }
	 }

			$payload = array(
				 "UserId"            => $postdata->UserId,
				 "VehicleNo"         => $postdata->VehicleNo,
				 "StartTime"    	 	 => $postdata->StartTime,
				 "EndTime"    	 	   => $postdata->EndTime,


			);


	 $report        = "location_"; // new report
	 $report_sum    = "summary_";

	 $sdate         = date("Y-m-d H:i:s", strtotime($postdata->StartTime));
	 $edate         = date("Y-m-d H:i:s", strtotime($postdata->EndTime));

	 $d1            = date("d", strtotime($postdata->StartTime));
	 $d2            = date("d", strtotime($postdata->EndTime));

	 $m1            = date("F", strtotime($postdata->StartTime));
	 $m2            = date("F", strtotime($postdata->EndTime));
	 $year          = date("Y", strtotime($postdata->StartTime));
	 $year2         = date("Y", strtotime($postdata->EndTime));
	 $rows          = array();
	 $rows2         = array();
	 $total_q       = 0;
	 $total_q2      = 0;

	 $error         = "";
	 $rows_summary  = "";

	 // echo "<pre>";
	 // var_dump($m1.'-'.$m2);die();
	 // echo "<pre>";

	 $location_list = array("location","location_off","location_idle");

	 if ($postdata->VehicleNo == "")
	 {
		 $feature["code"]    = 400;
		 $feature["msg"]     = "Invalid Vehicle No";
		 $feature["payload"] = $payload;
		 echo json_encode($feature);
		 exit;
	 }

	 if ($d1 != $d2)
	 {
		 $feature["code"]    = 400;
		 $feature["msg"]     = "Invalid Date Time. Date time must be in the same date";
		 $feature["payload"] = $payload;
		 echo json_encode($feature);
		 exit;
	 }

	 if ($m1 != $m2)
	 {
		 $feature["code"]    = 400;
		 $feature["msg"]     = "Invalid Date Time. Date time must be in the same month";
		 $feature["payload"] = $payload;
		 echo json_encode($feature);
		 exit;
	 }

	 if ($year != $year2)
	 {
		 $feature["code"]    = 400;
		 $feature["msg"]     = "Invalid Date Time. Date time must be in the same year";
		 $feature["payload"] = $payload;
		 echo json_encode($feature);
		 exit;
	 }

	 if ($error != "")
	 {
		 $callback['error'] = true;
		 $callback['message'] = $error;

		 echo json_encode($callback);
		 return;
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

	 switch ($m2)
	 {
		 case "January":
					 $dbtable2 = $report."januari_".$year;
		 $dbtable2_sum = $report_sum."januari_".$year;
		 break;
		 case "February":
					 $dbtable2 = $report."februari_".$year;
		 $dbtable2_sum = $report_sum."februari_".$year;
		 break;
		 case "March":
					 $dbtable2 = $report."maret_".$year;
		 $dbtable2_sum = $report_sum."maret_".$year;
		 break;
		 case "April":
					 $dbtable2 = $report."april_".$year;
		 $dbtable2_sum = $report_sum."april_".$year;
		 break;
		 case "May":
					 $dbtable2 = $report."mei_".$year;
		 $dbtable2_sum = $report_sum."mei_".$year;
		 break;
		 case "June":
					 $dbtable2 = $report."juni_".$year;
		 $dbtable2_sum = $report_sum."juni_".$year;
		 break;
		 case "July":
					 $dbtable2 = $report."juli_".$year;
		 $dbtable2_sum = $report_sum."juli_".$year;
		 break;
		 case "August":
					 $dbtable2 = $report."agustus_".$year;
		 $dbtable2_sum = $report_sum."agustus_".$year;
		 break;
		 case "September":
					 $dbtable2 = $report."september_".$year;
		 $dbtable2_sum = $report_sum."september_".$year;
		 break;
		 case "October":
					 $dbtable2 = $report."oktober_".$year;
		 $dbtable2_sum = $report_sum."oktober_".$year;
		 break;
		 case "November":
					 $dbtable2 = $report."november_".$year;
		 $dbtable2_sum = $report_sum."november_".$year;
		 break;
		 case "December":
					 $dbtable2 = $report."desember_".$year;
		 $dbtable2_sum = $report_sum."desember_".$year;
		 break;
	 }

	$rows = $this->getdatahistory($rowvehicle,$sdate,$edate);
	$total_rows = count($rows);
	$datafix = array();
	if($total_rows > 0)
	 {
		 for ($i=0; $i < $total_rows; $i++)
		 {
				//jika street_name kosong maka get ulang
				if($rows[$i]->gps_street_name == ""){
					/* $position = $this->getPosition_other($rows[$i]->gps_longitude_real,$rows[$i]->gps_latitude_real);
					if(isset($position)){
						$ex_position = explode(",",$position->display_name);
						if(count($ex_position)>0){
							$position_name = $ex_position[0];
						}else{
							$position_name = $ex_position[0];
						}
					}else{
						$position_name = $position->display_name;
					} */
					$position_name = "Belum terdaftar/di luar Geofence";
				}
				//jika sudah ada maka hilangkan koma
				else
				{
					$position = $rows[$i]->gps_street_name;
					if(isset($position)){
						$ex_position = explode(",",$position);
						if(count($ex_position)>0){
							$position_name = $ex_position[0];
						}else{
							$position_name = $ex_position[0];
						}
					}else{
						$position_name = $position;
					}
				}

				$gpsspeed_kph = round($rows[$i]->gps_speed*1.852,0);
				if($rows[$i]->gps_status == "A"){
					$gps_status = "OK";
				}else{
					$gps_status = "NOT OK";
				}

				$gps_time = date("Y-m-d H:i:s", strtotime("+7 hour", strtotime($rows[$i]->gps_time))); //sudah wita
				$cardinal_direction = $this->wind_cardinal($rows[$i]->gps_course);
				$cardinal_dir_kosongan = array("W","WNW","NW","NNW","N","NNE","NE","ENE");
				$cardinal_dir_muatan = array("E","ESE","SE","SSE","S","SSW","SW","WSW");

				if (in_array($cardinal_direction, $cardinal_dir_kosongan))
				{
					$jalur_bycardinal = "kosongan";

				}
				else if(in_array($cardinal_direction, $cardinal_dir_muatan))
				{
					$jalur_bycardinal = "muatan";
				}
				else
				{
					$jalur_bycardinal = "";
				}

				$jalur = $jalur_bycardinal;


				$sat_status = $rows[$i]->gps_sat_qty;
				$door = "";
				$sensor_new = "";
				$gps_looping_master = explode(",", $rows[$i]->gps_msg_ori);
				$iodata = $gps_looping_master[18];
				$iodata_dec = hexdec($iodata);
				$iodata_bin = decbin($iodata_dec);
				$gps_io = substr($iodata_bin,0,6);
				$total_gps_io = strlen($gps_io);

						if($total_gps_io == 1)
						{
							if($gps_io == 1)
							{
								$engine_code = 0;
								$door = 0;
								$sensor_new = 1;
							}
							else
							{
								$engine_code = 0;
								$door = 0;
								$sensor_new = 0;
							}
						}

						if($total_gps_io == 2)
						{
							if($gps_io == 10){
								$engine_code = 1;
								$door = 0;
								$sensor_new = 0;
							}else if($gps_io == 11){
								$engine_code = 1;
								$door = 0;
								$sensor_new = 1;
							}else{
								$engine_code = 0;
								$door = 0;
								$sensor_new = 0;
							}
						}

						if($total_gps_io == 3)
						{
							if($gps_io == 100){
								$engine_code = 0;
								$door = 1;
								$sensor_new = 0;
							}else if($gps_io == 101){
								$engine_code = 0;
								$door = 1;
								$sensor_new = 1;
							}else if($gps_io == 110){
								$engine_code = 1;
								$door = 1;
								$sensor_new = 0;
							}else if($gps_io == 111){
								$engine_code = 1;
								$door = 1;
								$sensor_new = 1;
							}else{
								$engine_code = 0;
								$door = 0;
								$sensor_new = 0;
							}
						}

						//ENGINE
						if($engine_code == "1"){
							$gps_io_port = "0000100000";
							$engine = "ON";
						}else{
							$gps_io_port = "0000000000";
							$engine = "OFF";
						}


						$gsm_status = $rows[$i]->gps_gsm_csq;

						if($gsm_status == ""){
							$gsm_status = $gps_looping_master[16];

						}
						if($gsm_status > 31){
							$gsm_status = 1;
						}

						if($sat_status == ""){
							$sat_status = $gps_looping_master[9];

						}

				if($rows[$i]->gps_speed > 0 ){
					$report_name = "move";
				}else if($rows[$i]->gps_speed == 0 && $gps_io_port == "0000100000"){
					$report_name = "idle";
				}else if($rows[$i]->gps_speed == 0 && $gps_io_port == "0000000000"){
					$report_name = "off";
				}else{
					$report_name = "-";
				}

				$geofence_name = "";
				$geofence_type = "";
				if($rows[$i]->gps_geofence_name != ""){
					$geofence_name = $rows[$i]->gps_geofence_name;
					$geofence_type = $rows[$i]->gps_geofence_type;
				}



				array_push($datafix, array(
				"VehicleUserId"              		=> $rowvehicle->vehicle_user_id,
			    "VehicleId" 						=> $rowvehicle->vehicle_id,
			    "VehicleDevice"      				=> $rowvehicle->vehicle_device,
			    "VehicleNo"     					=> $rowvehicle->vehicle_no,
			    "VehicleName"    					=> $rowvehicle->vehicle_name,
			    "VehicleType"   					=> $rowvehicle->vehicle_type,
			    "VehicleCompany" 					=> $rowvehicle->vehicle_company,
				"VehicleImei" 						=> $rowvehicle->vehicle_mv03,
			    "ReportName"          			  	=> $report_name,
			    "ReportSpeed"          				=> $gpsspeed_kph,
			    "ReportGpsStatus"       			=> $gps_status,
			    "ReportGpsTime"        				=> $gps_time,
				"ReportGeofenceName"   				=> $geofence_name,
				"ReportGeofenceType"   				=> $geofence_type,
				"ReportJalur"           			=> $jalur,
				"ReportDirection"   				=> $rows[$i]->gps_course,
			    "ReportLocation"        			=> $position_name,
			    "ReportCoordinate"      			=> $rows[$i]->gps_latitude_real.",".$rows[$i]->gps_longitude_real,
			    "ReportOdometer"        			=> $rows[$i]->gps_odometer,
			    "ReportFuelData"      				=> $rows[$i]->gps_mvd,
				"ReportGsm"       	  				=> $gsm_status,
				"ReportSat"       	  				=> $sat_status
				));
			}


		 echo json_encode(array("code" => 200, "msg" => "success",  "data" => $datafix, "payload" => $payload), JSON_NUMERIC_CHECK);
	 }
	 else
	 {
		 echo json_encode(array("code" => 200, "msg" => "success",  "data" => array(), "payload" => $payload), JSON_NUMERIC_CHECK);
	 }

	 // INI DIAKTIFKAN UNTUK MENCATAT HIT DARI API
	 $nowendtime = date("Y-m-d H:i:s");
	 $this->insertHitAPI("API Location Report V2", $payload, $nowstarttime, $nowendtime);
	 $this->db->close();
	 $this->db->cache_delete_all();

	 exit;
	}

	function getdatahistory($rowvehicle,$vstarttime,$vendtime)
	{
		//$statusspeed_knot = round($statusspeed/1.852,0);

		$sdate = date("Y-m-d H:i:s", strtotime("-7 hour", strtotime($vstarttime)));
		$edate = date("Y-m-d H:i:s", strtotime("-7 hour", strtotime($vendtime)));


			$json = json_decode($rowvehicle->vehicle_info);

			if (isset($json->vehicle_ip) && isset($json->vehicle_port))
			{
				$databases = $this->config->item('databases');

				if (isset($databases[$json->vehicle_ip][$json->vehicle_port])) {

					$database = $databases[$json->vehicle_ip][$json->vehicle_port];
					$table         = $this->config->item("external_gpstable");
					$tableinfo     = $this->config->item("external_gpsinfotable");

					$this->dbhist  = $this->load->database($database, TRUE);
					$this->dbhist2 = $this->load->database("gpshistory", true);


				} else {
					$table         = $this->gpsmodel->getGPSTable($rowvehicle->vehicle_type);
					$tableinfo     = $this->gpsmodel->getGPSInfoTable($rowvehicle->vehicle_type);

					$this->dbhist  = $this->load->database("default", TRUE);
					$this->dbhist2 = $this->load->database("gpshistory", true);
				}

				$vehicle_device = explode("@", $rowvehicle->vehicle_device);
				$vehicle_no     = $rowvehicle->vehicle_no;
				$vehicle_dev    = $rowvehicle->vehicle_device;
				$vehicle_name   = $rowvehicle->vehicle_name;
				$vehicle_type   = $rowvehicle->vehicle_type;

				$tablehist     = strtolower($vehicle_device[0]) . "@" . strtolower($vehicle_device[1]) . "_gps";
				$tablehistinfo = strtolower($vehicle_device[0]) . "@" . strtolower($vehicle_device[1]) . "_info";

						//get data dari PORT (gps_gsm_csq,gps_sat_qty,)
						//$this->dbhist->join($tableinfo, "gps_info_time = gps_time AND gps_info_device = CONCAT(gps_name,'@',gps_host)");
						$this->dbhist->distinct("gps_time");
						$this->dbhist->select("gps_id,gps_name,gps_host,gps_speed,gps_status,gps_latitude_real,gps_longitude_real,gps_time,gps_course,gps_msg_ori,
											   gps_street_name,gps_geofence_name,gps_geofence_type,gps_mvd,gps_odometer,gps_gsm_csq,gps_sat_qty");

						$this->dbhist->where("gps_name", $vehicle_device[0]);
						$this->dbhist->where("gps_host", $vehicle_device[1]);
						$this->dbhist->where("gps_time >=", $sdate);
						$this->dbhist->where("gps_time <=", $edate);
						$this->dbhist->order_by("gps_time", "asc");
						$q1    = $this->dbhist->get($table);
						$rows1 = $q1->result();
						$this->dbhist->close();
						$this->dbhist->cache_delete_all();



						//$this->dbhist2->join($tablehistinfo, "gps_info_time = gps_time AND gps_info_device = CONCAT(gps_name,'@',gps_host)");
						$this->dbhist2->distinct("gps_time");
						$this->dbhist2->select("gps_id,gps_name,gps_host,gps_speed,gps_status,gps_latitude_real,gps_longitude_real,gps_time,gps_course,gps_msg_ori,
											   gps_street_name,gps_geofence_name,gps_geofence_type,gps_mvd,gps_odometer,gps_gsm_csq,gps_sat_qty");

						$this->dbhist2->where("gps_name", $vehicle_device[0]);
						$this->dbhist2->where("gps_host", $vehicle_device[1]);
						$this->dbhist2->where("gps_time >=", $sdate);
						$this->dbhist2->where("gps_time <=", $edate);
						$this->dbhist2->order_by("gps_time", "asc");

						$q2    = $this->dbhist2->get($tablehist);
						$rows2 = $q2->result();
						$this->dbhist2->close();
						$this->dbhist2->cache_delete_all();

						$rows = array_merge($rows2, $rows1);

						//sudah urutan asc
						/* $trows = count($rows);
						if($trows > 0)
						{
							$rows = $this->dashboardmodel->array_sort($rows, 'gps_time', SORT_ASC);
						}
						 */


					return $rows;

			}

	}

	function getstatusoverspeedreport(){
		$nowstarttime = date("Y-m-d H:i:s");
		header("Content-Type: application/json");

		$token            = "UGaW5kNkjhA782GBNS1616KbswQYa5372bsdexVNT16";
		$postdata         = json_decode(file_get_contents("php://input"));
		$allvehicle       = 0;
		$now              = date("Ymd");
		$payload          = "";
    $forbidden_symbol = array("'", ",", ".", "?", "!", ";", ":", "-");
		$ReportType 		  = "";

		$headers = null;
		if (isset($_SERVER['Authorization']))
		{
        $headers = trim($_SERVER["Authorization"]);
    }
		else if (isset($_SERVER['HTTP_AUTHORIZATION']))
		{ //Nginx or fast CGI
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    }
    else if (function_exists('apache_request_headers'))
    {
      $requestHeaders = apache_request_headers();
      // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
      $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
      if (isset($requestHeaders['Authorization']))
      {
        $headers = trim($requestHeaders['Authorization']);
      }
    }

		if($headers != $token)
    {
			$feature["code"]    = 400;
			$feature["msg"]     = "Invalid Authorization Key ! ";
			$feature["payload"] = $payload;
			echo json_encode($feature);
			exit;
		}

		$feature = array();

		if(!isset($postdata->UserId) || $postdata->UserId == "")
		{
			$feature["code"]    = 400;
			$feature["msg"]     = "Invalid User ID";
			$feature["payload"] = $payload;
			echo json_encode($feature);
			exit;
		}else{

			//hanya user yg terdaftar yg bisa akes API
			$this->db->where("api_user",$postdata->UserId);
			$this->db->where("api_token",$headers);
			$this->db->where("api_status",1);
			$this->db->where("api_flag",0);
			$q = $this->db->get("api_user");
			if($q->num_rows == 0)
			{
				$feature["code"]    = 400;
				$feature["msg"]     = "User & Authorization Key is Not Available!";
				$feature["payload"] = $payload;
				echo json_encode($feature);
				exit;
			}
		}

    $payload = array(
     "UserId"            => $postdata->UserId,
     "VehicleDevice" 	 	 => $postdata->VehicleDevice,
		 "StartTime" 		     => $postdata->StartTime,
     "EndTime"           => $postdata->EndTime,
   );

	if ($postdata->VehicleDevice == 'all') {
		$ReportType = "OVERSPEED ALL";
	}else {
		$ReportType = "overspeed";
	}

  if(!isset($postdata->VehicleDevice) || $postdata->VehicleDevice == "")
	{
		$feature["code"]    = 400;
		$feature["msg"]     = "Invalid Vehicle No";
		$feature["payload"] = $payload;
		echo json_encode($feature);
		exit;
	}else {
		$this->db->order_by("vehicle_id","desc");
		if ($postdata->VehicleDevice != 'all') {
			$this->db->where("vehicle_device", $postdata->VehicleDevice);
		}
		$this->db->where("vehicle_user_id",4408);
		$this->db->where("vehicle_status",1);
		//$this->db->where("vehicle_active_date2 >",$now); //tidak expired
		$q = $this->db->get("vehicle");
		$vehicle = $q->result_array();

		if($q->num_rows == 0)
		{
			$feature["code"] = 400;
			$feature["msg"] = "Vehicle Not Found!";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}
	}

	if(!isset($postdata->StartTime) || $postdata->StartTime == "")
	{
		$feature["code"]    = 400;
		$feature["msg"]     = "Invalid Start Date Time";
		$feature["payload"] = $payload;
		echo json_encode($feature);
		exit;
	}

	if(!isset($postdata->EndTime) || $postdata->EndTime == "")
	{
		$feature["code"]    = 400;
		$feature["msg"]     = "Invalid End Date Time";
		$feature["payload"] = $payload;
		echo json_encode($feature);
		exit;
	}

	if($postdata->StartTime != "" && $postdata->EndTime != ""){
		$startdur = $postdata->StartTime * 60;
		$enddur = $postdata->EndTime * 60;
	}

	$sdate         = date("Y-m-d H:i:s", strtotime($postdata->StartTime));
	$edate         = date("Y-m-d H:i:s", strtotime($postdata->EndTime));

	$d1            = date("d", strtotime($postdata->StartTime));
	$d2            = date("d", strtotime($postdata->EndTime));

	$m1            = date("F", strtotime($postdata->StartTime));
	$m2            = date("F", strtotime($postdata->EndTime));
	$year          = date("Y", strtotime($postdata->StartTime));
	$year2         = date("Y", strtotime($postdata->EndTime));
	$rows          = array();
	$rows2         = array();
	$total_q       = 0;
	$total_q2      = 0;

	if ($d1 != $d2)
	{
		$feature["code"]    = 400;
		$feature["msg"]     = "Invalid Date Time. Date time must be in the same date";
		$feature["payload"] = $payload;
		echo json_encode($feature);
		exit;
	}

	if ($m1 != $m2)
	{
		$feature["code"]    = 400;
		$feature["msg"]     = "Invalid Date Time. Date time must be in the same month";
		$feature["payload"] = $payload;
		echo json_encode($feature);
		exit;
	}

	if ($year != $year2)
	{
		$feature["code"]    = 400;
		$feature["msg"]     = "Invalid Date Time. Date time must be in the same year";
		$feature["payload"] = $payload;
		echo json_encode($feature);
		exit;
	}

	if ($postdata->VehicleDevice == "all") {
		$content = $this->getthisruleovspeedreport($ReportType, $postdata->VehicleDevice, $postdata->StartTime, $postdata->EndTime);
	}else {
		$content = $this->getthisruleovspeedreport($ReportType, $postdata->VehicleDevice, $postdata->StartTime, $postdata->EndTime);
	}

	// echo "<pre>";
	// var_dump($thisrule);die();
	// echo "<pre>";

		if (sizeof($content) > 0) {
			echo json_encode(array("code" => 200, "msg" => "ok","data" => "DONE", "payload" => $payload), JSON_NUMERIC_CHECK);
		}else {
			echo json_encode(array("code" => 200, "msg" => "ok","data" => "ON PROCESS", "payload" => $payload), JSON_NUMERIC_CHECK);
		}

		$nowendtime = date("Y-m-d H:i:s");
		$this->insertHitAPI("API Rule Overspeed Report",$payload,$nowstarttime,$nowendtime);
		$this->db->close();
		$this->db->cache_delete_all();
	}

	function getstatuslocationreport(){
		$nowstarttime = date("Y-m-d H:i:s");
		header("Content-Type: application/json");

		$token            = "UGaW5kNkjhA782GBNS1616KbswQYa5372bsdexVNT16";
		$postdata         = json_decode(file_get_contents("php://input"));
		$allvehicle       = 0;
		$now              = date("Ymd");
		$payload          = "";
    $forbidden_symbol = array("'", ",", ".", "?", "!", ";", ":", "-");

		$headers = null;
		if (isset($_SERVER['Authorization']))
		{
        $headers = trim($_SERVER["Authorization"]);
    }
		else if (isset($_SERVER['HTTP_AUTHORIZATION']))
		{ //Nginx or fast CGI
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    }
    else if (function_exists('apache_request_headers'))
    {
      $requestHeaders = apache_request_headers();
      // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
      $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
      if (isset($requestHeaders['Authorization']))
      {
        $headers = trim($requestHeaders['Authorization']);
      }
    }

		if($headers != $token)
    {
			$feature["code"]    = 400;
			$feature["msg"]     = "Invalid Authorization Key ! ";
			$feature["payload"] = $payload;
			echo json_encode($feature);
			exit;
		}

		$feature = array();

		if(!isset($postdata->UserId) || $postdata->UserId == "")
		{
			$feature["code"]    = 400;
			$feature["msg"]     = "Invalid User ID";
			$feature["payload"] = $payload;
			echo json_encode($feature);
			exit;
		}else{

			//hanya user yg terdaftar yg bisa akes API
			$this->db->where("api_user",$postdata->UserId);
			$this->db->where("api_token",$headers);
			$this->db->where("api_status",1);
			$this->db->where("api_flag",0);
			$q = $this->db->get("api_user");
			if($q->num_rows == 0)
			{
				$feature["code"]    = 400;
				$feature["msg"]     = "User & Authorization Key is Not Available!";
				$feature["payload"] = $payload;
				echo json_encode($feature);
				exit;
			}
		}

    $payload = array(
     "UserId"            => $postdata->UserId,
     "VehicleDevice" 	 	 => $postdata->VehicleDevice,
		 "StartTime" 		     => $postdata->StartTime,
     "EndTime"           => $postdata->EndTime,
   );

	  if ($postdata->VehicleDevice == 'all') {
			$ReportTypeArray = array("LOCATION ALL", "LOCATION IDLE ALL", "LOCATION OFF ALL");
		}else {
			$ReportTypeArray = array("location", "location_off", "location_idle");
		}

  if(!isset($postdata->VehicleDevice) || $postdata->VehicleDevice == "")
	{
		$feature["code"]    = 400;
		$feature["msg"]     = "Invalid Vehicle No";
		$feature["payload"] = $payload;
		echo json_encode($feature);
		exit;
	}else {
		$this->db->order_by("vehicle_id","desc");
		if ($postdata->VehicleDevice != 'all') {
			$this->db->where("vehicle_device", $postdata->VehicleDevice);
		}
		$this->db->where("vehicle_user_id",4408);
		$this->db->where("vehicle_status",1);
		//$this->db->where("vehicle_active_date2 >",$now); //tidak expired
		$q = $this->db->get("vehicle");
		$vehicle = $q->result_array();

		if($q->num_rows == 0)
		{
			$feature["code"] = 400;
			$feature["msg"] = "Vehicle Not Found!";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}
	}

	if(!isset($postdata->StartTime) || $postdata->StartTime == "")
	{
		$feature["code"]    = 400;
		$feature["msg"]     = "Invalid Start Date Time";
		$feature["payload"] = $payload;
		echo json_encode($feature);
		exit;
	}

	if(!isset($postdata->EndTime) || $postdata->EndTime == "")
	{
		$feature["code"]    = 400;
		$feature["msg"]     = "Invalid End Date Time";
		$feature["payload"] = $payload;
		echo json_encode($feature);
		exit;
	}

	if($postdata->StartTime != "" && $postdata->EndTime != ""){
		$startdur = $postdata->StartTime * 60;
		$enddur = $postdata->EndTime * 60;
	}

	$sdate         = date("Y-m-d H:i:s", strtotime($postdata->StartTime));
	$edate         = date("Y-m-d H:i:s", strtotime($postdata->EndTime));

	$d1            = date("d", strtotime($postdata->StartTime));
	$d2            = date("d", strtotime($postdata->EndTime));

	$m1            = date("F", strtotime($postdata->StartTime));
	$m2            = date("F", strtotime($postdata->EndTime));
	$year          = date("Y", strtotime($postdata->StartTime));
	$year2         = date("Y", strtotime($postdata->EndTime));
	$rows          = array();
	$rows2         = array();
	$total_q       = 0;
	$total_q2      = 0;

	if ($d1 != $d2)
	{
		$feature["code"]    = 400;
		$feature["msg"]     = "Invalid Date Time. Date time must be in the same date";
		$feature["payload"] = $payload;
		echo json_encode($feature);
		exit;
	}

	if ($m1 != $m2)
	{
		$feature["code"]    = 400;
		$feature["msg"]     = "Invalid Date Time. Date time must be in the same month";
		$feature["payload"] = $payload;
		echo json_encode($feature);
		exit;
	}

	if ($year != $year2)
	{
		$feature["code"]    = 400;
		$feature["msg"]     = "Invalid Date Time. Date time must be in the same year";
		$feature["payload"] = $payload;
		echo json_encode($feature);
		exit;
	}

	// if ($postdata->VehicleDevice == "all") {
		$content = $this->getthisrulelocationreport($ReportTypeArray, $postdata->VehicleDevice, $postdata->StartTime, $postdata->EndTime);
	// }else {
	// 	$content = $this->getthisrulelocationreport($ReportTypeArray, $postdata->VehicleDevice, $postdata->StartTime, $postdata->EndTime);
	// }

	// echo "<pre>";
	// var_dump($content);die();
	// echo "<pre>";

	$data_1 = 0;
	$data_2 = 0;
	$data_3 = 0;
	$data_array_rpoerttype = array();
	$data_content = array_map('current', $content);

				if (in_array($ReportTypeArray[0], $data_content)) {
					$data_1 += 1;
				}

				if (in_array($ReportTypeArray[1], $data_content)) {
					$data_2 += 1;
				}

				if (in_array($ReportTypeArray[2], $data_content)) {
					$data_3 += 1;
				}

	$total_data_fix = ($data_1 + $data_2 + $data_3);

		// echo "<pre>";
		// var_dump($data_1.'-'.$data_2.'-'.$data_3);die();
		// // var_dump($data_content);die();
		// echo "<pre>";

		if ($total_data_fix == 3) {
			echo json_encode(array("code" => 200, "msg" => "ok", "data" => "DONE", "payload" => $payload), JSON_NUMERIC_CHECK);
		}else {
			echo json_encode(array("code" => 200, "msg" => "ok", "data" => "ON PROCESS", "payload" => $payload), JSON_NUMERIC_CHECK);
		}

		$nowendtime = date("Y-m-d H:i:s");
		$this->insertHitAPI("API Rule Location Report",$payload,$nowstarttime,$nowendtime);
		$this->db->close();
		$this->db->cache_delete_all();
	}

	function getthisruleovspeedreport($reportype, $vehicleid, $starttime, $endtime){
		$this->dbtrip = $this->load->database("tensor_report",true);
		$this->dbtrip->order_by("autoreport_data_startdate","asc");

		if($vehicleid != "all"){
			$this->dbtrip->where("autoreport_vehicle_device", $vehicleid);
		}

		$this->dbtrip->where("autoreport_type", $reportype);
		$this->dbtrip->where("autoreport_data_startdate >=", $starttime);
		$this->dbtrip->where("autoreport_data_enddate <=", $endtime);
		$q = $this->dbtrip->get("autoreport_new")->result_array();
		return $q;
	}

	function getthisrulelocationreport($reportype, $vehicleid, $starttime, $endtime){
		$this->dbtrip = $this->load->database("tensor_report",true);
		$this->dbtrip->order_by("autoreport_data_startdate","asc");

		$this->dbtrip->select("autoreport_type");
		if($vehicleid != "all"){
			$this->dbtrip->where("autoreport_vehicle_device", $vehicleid);
		}

		$this->dbtrip->where_in("autoreport_type", $reportype);
		$this->dbtrip->where("autoreport_data_startdate >=", $starttime);
		$this->dbtrip->where("autoreport_data_enddate <=", $endtime);
		$q = $this->dbtrip->get("autoreport_new")->result_array();
		return $q;
	}

	function getalarmevidence()
	{
		//ini_set('display_errors', 1);
		ini_set('memory_limit', "2G");
		ini_set('max_execution_time', 180); // 3 minutes
		$nowstarttime = date("Y-m-d H:i:s");
		header("Content-Type: application/json");

		$token = "UGaW5kNkjhA782GBNS1616KbswQYa5372bsdexVNT16";
		$postdata = json_decode(file_get_contents("php://input"));
		$allvehicle = 0;
		$allmedia = 0;
		$now = date("Ymd");
		$payload = "";

		$headers = null;
		if (isset($_SERVER['Authorization']))
		{
            $headers = trim($_SERVER["Authorization"]);
        }
		else if (isset($_SERVER['HTTP_AUTHORIZATION']))
		{ //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        }
        else if (function_exists('apache_request_headers'))
        {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization']))
            {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
		//print_r($headers." || ".$token." || ".$postdata->UserId);exit();

		if($headers != $token)
        {
			$feature["code"] = 400;
			$feature["msg"] = "Invalid Authorization Key ! ";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}

		$feature = array();

		if(!isset($postdata->UserId) || $postdata->UserId == "")
		{
			$feature["code"] = 400;
			$feature["msg"] = "Invalid User ID";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}else{

			//hanya user yg terdaftar yg bisa akes API
			$this->db->where("api_user",$postdata->UserId);
			$this->db->where("api_token",$headers);
			$this->db->where("api_status",1);
			$this->db->where("api_flag",0);
			$q = $this->db->get("api_user");
			if($q->num_rows == 0)
			{
				$feature["code"] = 400;
				$feature["msg"] = "User & Authorization Key is Not Available!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}

		}

		if(!isset($postdata->VehicleNo) || $postdata->VehicleNo == "all")
		{
			$allvehicle = 1;
		}



		if($postdata->MediaType == "")
		{
			$feature["code"] = 400;
			$feature["msg"] = "No Data Media Type";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}

		if(!isset($postdata->MediaType) || $postdata->MediaType == "all")
		{
			$allmedia = 1;
		}

		if(!isset($postdata->VehicleNo) || $postdata->VehicleNo == "")
		{
			$feature["code"] = 400;
			$feature["msg"] = "Invalid Vehicle No!";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}else{
			$check_vehicle = strpos($postdata->VehicleNo,';');
			$ex_vehicle = explode(";",$postdata->VehicleNo);
			$UserIDBIB = 4408;

			//jika ada cek dari database nopol (untuk dapat device id)
			$this->db->order_by("vehicle_id","desc");
			/* if($allvehicle == 0){
				$this->db->where_in("vehicle_no",$ex_vehicle);
			} */
			$this->db->where("vehicle_no",$postdata->VehicleNo);
			$this->db->where("vehicle_user_id",$UserIDBIB);
			$this->db->where("vehicle_status",1);
			//$this->db->where("vehicle_active_date2 >",$now); //tidak expired
			$q = $this->db->get("vehicle");
			$vehicle = $q->result();

			if($q->num_rows == 0)
			{
				$feature["code"] = 400;
				$feature["msg"] = "Vehicle Not Found!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}else{
				$vehicle = $q->result();

				 $payload      		    = array(
				  "UserId"          => $postdata->UserId,
				  "VehicleNo"   	=> $postdata->VehicleNo,
				  "MediaType"   		=> $postdata->MediaType,
				  "StartTime" 	 	=> $postdata->StartTime,
				  "EndTime"   		=> $postdata->EndTime

				);

			}
		}



		if($postdata->StartTime == "" || $postdata->EndTime == "")
		{
			$feature["code"] = 400;
			$feature["msg"] = "No Data Periode Start or Periode End";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}
		else
		{
			$sdate = $postdata->StartTime;
			$edate = $postdata->EndTime;
			$mediatype = $postdata->MediaType;

			$dboverspeed = "";
			$dbtable = "";
			$report     = "alarm_evidence_";
			$overspeed  = "overspeed_";

			$month = date("F", strtotime($sdate));
			$year = date("Y", strtotime($sdate));


			$diff = strtotime($edate) - strtotime($sdate);
			if ($diff < 0) {
				$feature["code"] = 400;
				$feature["msg"] = "Date is not correct!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}

			$diff1 = date("m", strtotime($sdate));
			$diff2 = date("m", strtotime($edate));

			if ($diff1 != $diff2) {
				$feature["code"] = 400;
				$feature["msg"] = "Date must be in the same month!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}

			$diff1 = date("Y", strtotime($sdate));
			$diff2 = date("Y", strtotime($edate));

			if ($diff1 != $diff2) {

				$feature["code"] = 400;
				$feature["msg"] = "Date must be in the same year!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}

			if($allvehicle == 1){

				$diff1 = date("d", strtotime($sdate));
				$diff2 = date("d", strtotime($edate));

				if($diff1 != $diff2)
				{
					$feature["code"] = 400;
					$feature["msg"] = "All Vehicle must be in the same Date!";
					$feature["payload"]    = $payload;
					echo json_encode($feature);
					exit;
				}

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

		}

		//jika mobil lebih dari nol
		if(count($vehicle) > 0)
		{
			$DataToUpload = array();
			//unset($DataToUpload);
			for($z=0;$z<count($vehicle);$z++)
			{

				//printf("ATTR %s \r\n",$vehicle[$z]->vehicle_no);

				$vehicle_device = $vehicle[$z]->vehicle_device;
				$ex_vehicle = explode("@",$vehicle_device);
				$vdeviceid = $ex_vehicle[0];
				$company = $vehicle[$z]->vehicle_company;

					$rows = $this->getalarmevidence_data($dbtable, $company, $vdeviceid, $mediatype, $sdate, $edate, $allvehicle, $allmedia);
					//print_r($rows);exit();
					if(isset($rows) && count($rows)>0)
					{

						for($i=0;$i<count($rows);$i++)
						{
								$DataToUpload[$i]->VehicleUserId = $rows[$i]->alarm_report_vehicle_user_id;
								$DataToUpload[$i]->VehicleDevice = $rows[$i]->alarm_report_vehicle_id;
								$DataToUpload[$i]->VehicleNo = $rows[$i]->alarm_report_vehicle_no;
								$DataToUpload[$i]->VehicleName = $rows[$i]->alarm_report_vehicle_name;
								$DataToUpload[$i]->VehicleType = $rows[$i]->alarm_report_vehicle_type;
								$DataToUpload[$i]->VehicleCompany = $rows[$i]->alarm_report_vehicle_company;
								$DataToUpload[$i]->VehicleCamImei = $rows[$i]->alarm_report_imei;


								$DataToUpload[$i]->Alarmtype = $rows[$i]->alarm_report_type;


								if($rows[$i]->alarm_report_media == 1)
								{
									$DataToUpload[$i]->AlarmName = "";
									$DataToUpload[$i]->AlarmLevel = "";
									$DataToUpload[$i]->GPSStatus = "";
									$DataToUpload[$i]->Speed = "";

								}
								else
								{
									$DataToUpload[$i]->AlarmName = $rows[$i]->alarm_report_name;
									$DataToUpload[$i]->AlarmLevel = $rows[$i]->alarm_report_level;
									$DataToUpload[$i]->GPSStatus = $rows[$i]->alarm_report_gpsstatus;
									$DataToUpload[$i]->Speed = $rows[$i]->alarm_report_speed;
								}


								$DataToUpload[$i]->AlarmMedia = $rows[$i]->alarm_report_media;
								$DataToUpload[$i]->AlarmChannel = $rows[$i]->alarm_report_channel;

								$DataToUpload[$i]->StartTime = $rows[$i]->alarm_report_start_time;
								$DataToUpload[$i]->EndTime = $rows[$i]->alarm_report_end_time;

								$DataToUpload[$i]->LocationStart = $rows[$i]->alarm_report_location_start;
								$DataToUpload[$i]->LocationEnd = $rows[$i]->alarm_report_location_end;

								$DataToUpload[$i]->CoordinateStart = $rows[$i]->alarm_report_coordinate_start;
								$DataToUpload[$i]->CoordinateEnd = $rows[$i]->alarm_report_coordinate_end;

								$DataToUpload[$i]->DownloadUrl = $rows[$i]->alarm_report_downloadurl;
								$DataToUpload[$i]->FilePath = $rows[$i]->alarm_report_path;
								$DataToUpload[$i]->FileUrl = $rows[$i]->alarm_report_fileurl;
								$DataToUpload[$i]->FileSize = $rows[$i]->alarm_report_size;

						}

					}

			}
			//$content = json_encode($datajson);
			$content = $DataToUpload;

			//echo $content;
			echo json_encode(array("code" => 200, "msg" => "ok", "data" => $content, "payload" => $payload), JSON_NUMERIC_CHECK);
			$nowendtime = date("Y-m-d H:i:s");
			$this->insertHitAPI("API Alarm Evidence",$payload,$nowstarttime,$nowendtime);
			$this->db->close();
			$this->db->cache_delete_all();

		}


		exit;
	}

	function getalarmevidence_data($dbtable, $company, $vehicledevice, $mediatype, $sdate, $edate, $allvehicle, $allmedia)
	{
		$sdate = date("Y-m-d H:i:s", strtotime($sdate) - (60 * 60));
		$edate = date("Y-m-d H:i:s", strtotime($edate) - (60 * 60));

		$nowday            = date("d");
		$end_day_fromEdate = date("d", strtotime($edate));

		if ($nowday == $end_day_fromEdate) {
			$edate = date("Y-m-d H:i:s");
		}

		//print_r($sdate." ".$edate." ".$vehicledevice);//exit();

		$black_list  = array("401","428","451","478","602","603","608","609","652","653","658","659",
							 "600","601","650","651"
							); //lane deviation & forward collation

		$street_register = $this->getAllStreetKM(4408); //HAULING



		$this->dbtrip = $this->load->database("tensor_report", true);
		/* if ($company != "all") {
			$this->dbtrip->where("alarm_report_vehicle_company", $company);
		} */

		if($allvehicle != 1){
			$this->dbtrip->where("alarm_report_vehicle_id", $vehicledevice);
		}

		if($allmedia != 1){
			$this->dbtrip->where("alarm_report_media",$mediatype); //photo = 0 , video = 1

			if($mediatype == 0){

				$this->dbtrip->where("alarm_report_gpsstatus !=", "");
			}
			else
			{
				//print_r("MEDIA : ". $mediatype);//exit();
			}
		}

		$this->dbtrip->where("alarm_report_start_time >=", $sdate);
		$this->dbtrip->where("alarm_report_start_time <=", $edate);
		$this->dbtrip->where_not_in('alarm_report_type', $black_list);

		$this->dbtrip->order_by("alarm_report_start_time","asc");
		//$this->dbtrip->group_by("alarm_report_start_time");
		$q = $this->dbtrip->get($dbtable);
		$this->dbtrip->close();
		$this->dbtrip->cache_delete_all();
		$rows = $q->result();

		return $rows;
	}

	function req_geofencerambu()
	{
		//printf("PROSES POST SAMPLE -> REQUEST >> LAST POSITION \r\n");

		$token = "UGaW5kNkjhA782GBNS1616KbswQYa5372bsdexVNT16";
		$authorization = "Authorization:".$token;
		$url = "https://temanbib.borneo-indobara.com/ugapi/getgeorambu";
		$feature = array();

		$feature["UserId"] = 4204; //pbi

		//printf("POSTING PROSES \r\n");
		$content = json_encode($feature);
		$total_content = count($content);

		printf("Data JSON : %s \r \n",$content);

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

		$json_response = curl_exec($curl);
		echo $json_response;
		echo curl_getinfo($curl, CURLINFO_HTTP_CODE);
		printf("-------------------------- \r\n");

		exit;
	}

	function getgeorambu()
	{
		//ini_set('display_errors', 1);
		$nowstarttime = date("Y-m-d H:i:s");
		header("Content-Type: application/json");

		$token = "UGaW5kNkjhA782GBNS1616KbswQYa5372bsdexVNT16";
		$postdata = json_decode(file_get_contents("php://input"));
		$allvehicle = 0;
		$payload = "";
		$now = date("Ymd");

		$headers = null;
		if (isset($_SERVER['Authorization']))
		{
            $headers = trim($_SERVER["Authorization"]);
        }
		else if (isset($_SERVER['HTTP_AUTHORIZATION']))
		{ //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        }
        else if (function_exists('apache_request_headers'))
        {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization']))
            {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
		//print_r($headers." || ".$token." || ".$postdata->UserId);exit();



		if($headers != $token)
        {
			$feature["code"] = 400;
			$feature["msg"] = "Invalid Authorization Key ! ";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}

		$feature = array();

		if(!isset($postdata->UserId) || $postdata->UserId == "")
		{
			$feature["code"] = 400;
			$feature["msg"] = "Invalid User ID";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}else{

			//hanya user yg terdaftar yg bisa akes API
			$this->db->where("api_user",$postdata->UserId);
			$this->db->where("api_token",$headers);
			$this->db->where("api_status",1);
			$this->db->where("api_flag",0);
			$q = $this->db->get("api_user");
			if($q->num_rows == 0)
			{
				$feature["code"] = 400;
				$feature["msg"] = "User & Authorization Key is Not Available!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}else{

				$UserIDBIB = 4408;
				$this->dblive = $this->load->database("webtracking_gps_temanindobara_live", true);
				$this->dblive->select("geofence_id,geofence_user,geofence_status,geofence_created,geofence_polygon,geofence_json,
									   geofence_name,geofence_type,geofence_speed,geofence_speed_muatan,geofence_speed_alias,geofence_speed_muatan_alias"
									 );
				$this->dblive->order_by("geofence_id","asc");
				$this->dblive->where("geofence_user",$UserIDBIB);
				//$this->dblive->where("geofence_status",1); all sent
				//$this->dblive->limit(1);
				$q = $this->dblive->get("geofence");
				$data = $q->result();

				if($q->num_rows == 0)
				{
					$feature["code"] = 400;
					$feature["msg"] = "Geofence Rambu Not Found!";
					$feature["payload"]    = $payload;
					echo json_encode($feature);
					exit;
				}else{
					$data = $q->result();

					$payload      		    = array(
					  "UserId"          => $postdata->UserId


					);
				}


			}

		}


		//jika mobil lebih dari nol
		if(count($data) > 0)
		{

			$DataToUpload = array();
			//unset($DataToUpload);
			for($z=0;$z<count($data);$z++)
			{
				$tes = mb_convert_encoding($data[$z]->geofence_polygon,'UTF-8','UTF-8');
				$DataToUpload[$z]->GeofenceId = $data[$z]->geofence_id;
				$DataToUpload[$z]->GeofenceUser = $data[$z]->geofence_user;
				$DataToUpload[$z]->GeofenceName = $data[$z]->geofence_name;
				//$DataToUpload[$z]->GeofencePolygon = $tes;
				//$DataToUpload[$z]->GeofencePolygon = $data[$z]->geofence_polygon;
				$DataToUpload[$z]->GeofencePolygon = json_decode($data[$z]->geofence_json);
				$DataToUpload[$z]->GeofenceType = $data[$z]->geofence_type;
				$DataToUpload[$z]->LimitKosongan = $data[$z]->geofence_speed;
				$DataToUpload[$z]->LimitKosonganAlias = $data[$z]->geofence_speed_alias;
				$DataToUpload[$z]->LimitMuatan = $data[$z]->geofence_speed_muatan;
				$DataToUpload[$z]->LimitMuatanAlias = $data[$z]->geofence_speed_muatan_alias;

				$DataToUpload[$z]->GeofenceStatus = $data[$z]->geofence_status;
				$DataToUpload[$z]->GeofenceCreated = $data[$z]->geofence_created;

			}

			$content = $DataToUpload;

			//echo $content;
			echo json_encode(array("code" => 200, "msg" => "ok", "data" => $content, "payload" => $payload), JSON_NUMERIC_CHECK);
			$nowendtime = date("Y-m-d H:i:s");
			$this->insertHitAPI("API Geofence Rambu",$payload,$nowstarttime,$nowendtime);
			$this->dblive->close();
			$this->dblive->cache_delete_all();

		}


		exit;
	}

	function req_geofencehauling()
	{
		//printf("PROSES POST SAMPLE -> REQUEST >> LAST POSITION \r\n");

		$token = "UGaW5kNkjhA782GBNS1616KbswQYa5372bsdexVNT16";
		$authorization = "Authorization:".$token;
		$url = "https://temanbib.borneo-indobara.com/ugapi/getgeohauling";
		$feature = array();

		$feature["UserId"] = 4204; //pbi

		//printf("POSTING PROSES \r\n");
		$content = json_encode($feature);
		$total_content = count($content);

		printf("Data JSON : %s \r \n",$content);

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

		$json_response = curl_exec($curl);
		echo $json_response;
		echo curl_getinfo($curl, CURLINFO_HTTP_CODE);
		printf("-------------------------- \r\n");

		exit;
	}

	function getgeohauling()
	{
		//ini_set('display_errors', 1);
		$nowstarttime = date("Y-m-d H:i:s");
		header("Content-Type: application/json");

		$token = "UGaW5kNkjhA782GBNS1616KbswQYa5372bsdexVNT16";
		$postdata = json_decode(file_get_contents("php://input"));
		$allvehicle = 0;
		$payload = "";
		$now = date("Ymd");

		$headers = null;
		if (isset($_SERVER['Authorization']))
		{
            $headers = trim($_SERVER["Authorization"]);
        }
		else if (isset($_SERVER['HTTP_AUTHORIZATION']))
		{ //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        }
        else if (function_exists('apache_request_headers'))
        {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization']))
            {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
		//print_r($headers." || ".$token." || ".$postdata->UserId);exit();



		if($headers != $token)
        {
			$feature["code"] = 400;
			$feature["msg"] = "Invalid Authorization Key ! ";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}

		$feature = array();

		if(!isset($postdata->UserId) || $postdata->UserId == "")
		{
			$feature["code"] = 400;
			$feature["msg"] = "Invalid User ID";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}else{
			$gray_area = array("KM 6,","KM 6.5,","KM 7,");
			//hanya user yg terdaftar yg bisa akes API
			$this->db->where("api_user",$postdata->UserId);
			$this->db->where("api_token",$headers);
			$this->db->where("api_status",1);
			$this->db->where("api_flag",0);
			$q = $this->db->get("api_user");
			if($q->num_rows == 0)
			{
				$feature["code"] = 400;
				$feature["msg"] = "User & Authorization Key is Not Available!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}else{

				$UserIDBIB = 4408;
				$this->db->select("street_id,street_creator,street_flag,street_name,street_alias,street_type,street_created,street_group,street_serialize,street_company_parent,
								   street_order

								 ");
				$this->db->order_by("street_id","asc");
				$this->db->where("street_creator",$UserIDBIB);
				//$this->db->where_in("street_name", $gray_area);
				//$this->dblive->where("geofence_status",1); all sent
				//$this->db->limit(1);
				$q = $this->db->get("street");
				$data = $q->result();

				if($q->num_rows == 0)
				{
					$feature["code"] = 400;
					$feature["msg"] = "Geofence Hauling Not Found!";
					$feature["payload"]    = $payload;
					echo json_encode($feature);
					exit;
				}else{
					$data = $q->result();

					$payload      		    = array(
					  "UserId"          => $postdata->UserId


					);
				}


			}

		}


		//jika mobil lebih dari nol
		if(count($data) > 0)
		{

			$DataToUpload = array();
			//unset($DataToUpload);
			for($z=0;$z<count($data);$z++)
			{
				//$tes = mb_convert_encoding($data[$z]->geofence_polygon,'UTF-8','UTF-8');
				$DataToUpload[$z]->StreetId = $data[$z]->street_id;
				$DataToUpload[$z]->StreetUser = $data[$z]->street_creator;
				$DataToUpload[$z]->StreetName = $data[$z]->street_name;
				$DataToUpload[$z]->StreetAlias = $data[$z]->street_alias;
				//$DataToUpload[$z]->GeofencePolygon = $tes;

				if (in_array($data[$z]->street_name, $gray_area)){
					$geom_rev = $this->get_polygon_street_bk($data[$z]->street_name,$UserIDBIB);

					//$geom_rev = $data[$z]->street_serialize;
					$geom = $geom_rev;
				}else{
					$geom = $data[$z]->street_serialize;
				}
				$DataToUpload[$z]->StreetPolygon = json_decode($geom);
				$DataToUpload[$z]->StreetType = $data[$z]->street_type;
				$DataToUpload[$z]->StreetGroup = $data[$z]->street_group;
				$DataToUpload[$z]->StreetCompany = $data[$z]->street_company_parent;
				$DataToUpload[$z]->StreetOrder = $data[$z]->street_order;
				$DataToUpload[$z]->StreetCreated = $data[$z]->street_created;

			}

			$content = $DataToUpload;

			//echo $content;
			echo json_encode(array("code" => 200, "msg" => "ok", "data" => $content, "payload" => $payload), JSON_NUMERIC_CHECK);
			$nowendtime = date("Y-m-d H:i:s");
			$this->insertHitAPI("API Geofence Hauling",$payload,$nowstarttime,$nowendtime);
			$this->db->close();
			$this->db->cache_delete_all();

		}


		exit;
	}

	function get_polygon_street_bk($name,$UserIDBIB)
	{
		$this->db->select("street_id,street_name,street_serialize ");
		$this->db->order_by("street_id","asc");
		$this->db->where("street_creator",$UserIDBIB);
		$this->db->where("street_name", $name);
		$this->db->limit(1);
		$q_r = $this->db->get("street_bk");
		$data_r = $q_r->row();
		if(count($data_r)>0){
			$result = $data_r->street_serialize;
		}else{

			$result = "";
		}

		return $result;
	}

	function req_rawgps()
	{
		//printf("PROSES POST SAMPLE -> REQUEST >> LAST POSITION \r\n");

		$token = "UGaW5kNkjhA782GBNS1616KbswQYa5372bsdexVNT16";
		$authorization = "Authorization:".$token;
		$url = "https://temanbib.borneo-indobara.com/ugapi/getrawgps";
		$feature = array();

		$feature["UserId"] = 4204; //pbi
		$feature["VehicleNo"] = "BMT 3148";
		$feature["StartTime"] = "2022-09-31 00:00:00";
		$feature["EndTime"] = "2022-09-31 23:59:59";

		//printf("POSTING PROSES \r\n");
		$content = json_encode($feature);
		$total_content = count($content);

		printf("Data JSON : %s \r \n",$content);

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

		$json_response = curl_exec($curl);
		echo $json_response;
		echo curl_getinfo($curl, CURLINFO_HTTP_CODE);
		printf("-------------------------- \r\n");

		exit;
	}

	function getrawgps()
	{
		//ini_set('display_errors', 1);
		ini_set('memory_limit', "2G");
		ini_set('max_execution_time', 180); // 3 minutes
		$nowstarttime = date("Y-m-d H:i:s");
		header("Content-Type: application/json");

		$token = "UGaW5kNkjhA782GBNS1616KbswQYa5372bsdexVNT16";
		$postdata = json_decode(file_get_contents("php://input"));
		$allvehicle = 0;
		$now = date("Ymd");
		$payload = "";

		$headers = null;
		if (isset($_SERVER['Authorization']))
		{
            $headers = trim($_SERVER["Authorization"]);
        }
		else if (isset($_SERVER['HTTP_AUTHORIZATION']))
		{ //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        }
        else if (function_exists('apache_request_headers'))
        {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization']))
            {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
		//print_r($headers." || ".$token." || ".$postdata->UserId);exit();

		if($headers != $token)
        {
			$feature["code"] = 400;
			$feature["msg"] = "Invalid Authorization Key ! ";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}

		$feature = array();

		if(!isset($postdata->UserId) || $postdata->UserId == "")
		{
			$feature["code"] = 400;
			$feature["msg"] = "Invalid User ID";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}else{

			//hanya user yg terdaftar yg bisa akes API
			$this->db->where("api_user",$postdata->UserId);
			$this->db->where("api_token",$headers);
			$this->db->where("api_status",1);
			$this->db->where("api_flag",0);
			$q = $this->db->get("api_user");
			if($q->num_rows == 0)
			{
				$feature["code"] = 400;
				$feature["msg"] = "User & Authorization Key is Not Available!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}

		}

		if(!isset($postdata->VehicleNo) || $postdata->VehicleNo == "all")
		{
			$allvehicle = 1;
		}

		if(!isset($postdata->VehicleNo) || $postdata->VehicleNo == "")
		{
			$feature["code"] = 400;
			$feature["msg"] = "Invalid Vehicle No!";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}else{
			$check_vehicle = strpos($postdata->VehicleNo,';');
			$ex_vehicle = explode(";",$postdata->VehicleNo);
			$UserIDBIB = 4408;

			//jika ada cek dari database nopol (untuk dapat device id)
			$this->db->order_by("vehicle_id","desc");
			/* if($allvehicle == 0){
				$this->db->where_in("vehicle_no",$ex_vehicle);
			} */
			$this->db->where("vehicle_no",$postdata->VehicleNo);
			$this->db->where("vehicle_user_id",$UserIDBIB);
			$this->db->where("vehicle_status",1);
			//$this->db->where("vehicle_active_date2 >",$now); //tidak expired
			$q = $this->db->get("vehicle");
			$vehicle = $q->result();

			if($q->num_rows == 0)
			{
				$feature["code"] = 400;
				$feature["msg"] = "Vehicle Not Found!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}else{
				$vehicle = $q->result();

				 $payload      		    = array(
				  "UserId"          => $postdata->UserId,
				  "VehicleNo"   	=> $postdata->VehicleNo,
				  "StartTime" 	 	=> $postdata->StartTime,
				  "EndTime"   		=> $postdata->EndTime

				);

			}
		}

		if($postdata->StartTime == "" || $postdata->EndTime == "")
		{
			$feature["code"] = 400;
			$feature["msg"] = "No Data Periode Start or Periode End";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}
		else
		{
			$sdate = $postdata->StartTime;
			$edate = $postdata->EndTime;


			$dboverspeed = "";
			$report     = "alarm_evidence_";
			$overspeed  = "overspeed_";

			$month = date("F", strtotime($sdate));
			$year = date("Y", strtotime($sdate));


			$diff = strtotime($edate) - strtotime($sdate);
			if ($diff < 0) {
				$feature["code"] = 400;
				$feature["msg"] = "Date is not correct!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}

			$diff1 = date("m", strtotime($sdate));
			$diff2 = date("m", strtotime($edate));

			if ($diff1 != $diff2) {
				$feature["code"] = 400;
				$feature["msg"] = "Date must be in the same month!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}

			$diff1 = date("Y", strtotime($sdate));
			$diff2 = date("Y", strtotime($edate));

			if ($diff1 != $diff2) {

				$feature["code"] = 400;
				$feature["msg"] = "Date must be in the same year!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}

			$diff1 = date("d", strtotime($sdate));
			$diff2 = date("d", strtotime($edate));

			if($diff1 != $diff2)
			{
				$feature["code"] = 400;
				$feature["msg"] = "Date must be in the same Date!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}
		}

		//jika mobil lebih dari nol
		if(count($vehicle) > 0)
		{
			$DataToUpload = array();
			//unset($DataToUpload);
			for($z=0;$z<count($vehicle);$z++)
			{

				//printf("ATTR %s \r\n",$vehicle[$z]->vehicle_no);
				$vehicle_device = $vehicle[$z]->vehicle_device;
				$company = $vehicle[$z]->vehicle_company;
					$sdate_gmt = date("Y-m-d H:i:s", strtotime("-7 hour", strtotime($sdate))); //wita
					$edate_gmt = date("Y-m-d H:i:s", strtotime("-7 hour", strtotime($edate)));  //wita
					$rows = $this->getrawgps_data($vehicle[$z], $sdate_gmt, $edate_gmt);

					if(isset($rows) && count($rows)>0)
					{

						for($i=0;$i<count($rows);$i++)
						{
								if( $rows[$i]->gps_info_io_port == "0000100000"){
									$engine_bit = 1;
								}else{
									$engine_bit = 0;

								}

								if($rows[$i]->gps_speed > 1 ){
									$engine_bit = 1;
								}

								if($rows[$i]->gps_cs == 53){
									$io2_bit = 1;
								}else{
									$io2_bit = 0;
								}

								$gpstime_wta = date("Y-m-d H:i:s", strtotime("+7 hour", strtotime($rows[$i]->gps_time))); //wita

								$DataToUpload[$i]->Name = $rows[$i]->gps_name;
								$DataToUpload[$i]->Host = $rows[$i]->gps_host;
								$DataToUpload[$i]->Type = $rows[$i]->gps_type;
								$DataToUpload[$i]->Speed = $rows[$i]->gps_speed;
								$DataToUpload[$i]->Direction = $rows[$i]->gps_course;
								$DataToUpload[$i]->Fuel = $rows[$i]->gps_mvd;
								$DataToUpload[$i]->Engine = $engine_bit;
								$DataToUpload[$i]->Io2 = $io2_bit;
								$DataToUpload[$i]->GpsStatus = $rows[$i]->gps_status;
								$DataToUpload[$i]->GpsTime = $gpstime_wta;
								$DataToUpload[$i]->Latitude = $rows[$i]->gps_latitude_real;
								$DataToUpload[$i]->Longitude = $rows[$i]->gps_longitude_real;
								$DataToUpload[$i]->Odometer = $rows[$i]->gps_odometer;



						}

					}

			}
			//$content = json_encode($datajson);
			$content = $DataToUpload;

			//echo $content;
			echo json_encode(array("code" => 200, "msg" => "ok", "data" => $content, "payload" => $payload), JSON_NUMERIC_CHECK);
			$nowendtime = date("Y-m-d H:i:s");
			$this->insertHitAPI("API RAW GPS",$payload,$nowstarttime,$nowendtime);
			$this->db->close();
			$this->db->cache_delete_all();

		}


		exit;
	}

	function getrawgpsv2()
	{
		//ini_set('display_errors', 1);
		ini_set('memory_limit', "2G");
		ini_set('max_execution_time', 180); // 3 minutes
		$nowstarttime = date("Y-m-d H:i:s");
		header("Content-Type: application/json");

		$token = "UGaW5kNkjhA782GBNS1616KbswQYa5372bsdexVNT16";
		$postdata = json_decode(file_get_contents("php://input"));
		$allvehicle = 0;
		$now = date("Ymd");
		$payload = "";

		$headers = null;
		if (isset($_SERVER['Authorization']))
		{
            $headers = trim($_SERVER["Authorization"]);
        }
		else if (isset($_SERVER['HTTP_AUTHORIZATION']))
		{ //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        }
        else if (function_exists('apache_request_headers'))
        {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization']))
            {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
		//print_r($headers." || ".$token." || ".$postdata->UserId);exit();

		if($headers != $token)
        {
			$feature["code"] = 400;
			$feature["msg"] = "Invalid Authorization Key ! ";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}

		$feature = array();

		if(!isset($postdata->UserId) || $postdata->UserId == "")
		{
			$feature["code"] = 400;
			$feature["msg"] = "Invalid User ID";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}else{

			//hanya user yg terdaftar yg bisa akes API
			$this->db->where("api_user",$postdata->UserId);
			$this->db->where("api_token",$headers);
			$this->db->where("api_status",1);
			$this->db->where("api_flag",0);
			$q = $this->db->get("api_user");
			if($q->num_rows == 0)
			{
				$feature["code"] = 400;
				$feature["msg"] = "User & Authorization Key is Not Available!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}

		}

		if(!isset($postdata->VehicleDevice) || $postdata->VehicleDevice == "all")
		{
			$allvehicle = 1;
		}

		if(!isset($postdata->VehicleDevice) || $postdata->VehicleDevice == "")
		{
			$feature["code"] = 400;
			$feature["msg"] = "Invalid Vehicle Device!";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}else{
			$check_vehicle = strpos($postdata->VehicleDevice,';');
			$ex_vehicle = explode(";",$postdata->VehicleDevice);
			$UserIDBIB = 4408;

			//jika ada cek dari database nopol (untuk dapat device id)
			$this->db->order_by("vehicle_id","desc");
			/* if($allvehicle == 0){
				$this->db->where_in("vehicle_no",$ex_vehicle);
			} */
			$this->db->where("vehicle_device",$postdata->VehicleDevice);
			//$this->db->where("vehicle_device","DISABLE DULU");
			$this->db->where("vehicle_user_id",$UserIDBIB);
			$this->db->where("vehicle_status",1);
			//$this->db->where("vehicle_active_date2 >",$now); //tidak expired
			$q = $this->db->get("vehicle");
			$vehicle = $q->result();

			if($q->num_rows == 0)
			{
				$feature["code"] = 400;
				$feature["msg"] = "API ini tidak bisa diakses untuk sementara waktu";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}else{
				$vehicle = $q->result();

				 $payload      		    = array(
				  "UserId"          => $postdata->UserId,
				  "VehicleDevice"   => $postdata->VehicleDevice,
				  "StartTime" 	 	=> $postdata->StartTime,
				  "EndTime"   		=> $postdata->EndTime

				);

			}
		}

		if($postdata->StartTime == "" || $postdata->EndTime == "")
		{
			$feature["code"] = 400;
			$feature["msg"] = "No Data Periode Start or Periode End";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}
		else
		{
			$sdate = $postdata->StartTime;
			$edate = $postdata->EndTime;


			$dboverspeed = "";
			$report     = "alarm_evidence_";
			$overspeed  = "overspeed_";

			$month = date("F", strtotime($sdate));
			$year = date("Y", strtotime($sdate));


			$diff = strtotime($edate) - strtotime($sdate);
			if ($diff < 0) {
				$feature["code"] = 400;
				$feature["msg"] = "Date is not correct!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}

			$diff1 = date("m", strtotime($sdate));
			$diff2 = date("m", strtotime($edate));

			if ($diff1 != $diff2) {
				$feature["code"] = 400;
				$feature["msg"] = "Date must be in the same month!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}

			$diff1 = date("Y", strtotime($sdate));
			$diff2 = date("Y", strtotime($edate));

			if ($diff1 != $diff2) {

				$feature["code"] = 400;
				$feature["msg"] = "Date must be in the same year!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}

			$diff1 = date("d", strtotime($sdate));
			$diff2 = date("d", strtotime($edate));

			if($diff1 != $diff2)
			{
				$feature["code"] = 400;
				$feature["msg"] = "Date must be in the same Date!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}
		}

		//jika mobil lebih dari nol
		if(count($vehicle) > 0)
		{
			$DataToUpload = array();
			//unset($DataToUpload);
			for($z=0;$z<count($vehicle);$z++)
			{

				//printf("ATTR %s \r\n",$vehicle[$z]->vehicle_no);
				$vehicle_device = $vehicle[$z]->vehicle_device;
				$company = $vehicle[$z]->vehicle_company;
					$sdate_gmt = date("Y-m-d H:i:s", strtotime("-7 hour", strtotime($sdate))); //wita
					$edate_gmt = date("Y-m-d H:i:s", strtotime("-7 hour", strtotime($edate)));  //wita
					$rows = $this->getrawgps_datav2($vehicle[$z], $sdate_gmt, $edate_gmt);

					if(isset($rows) && count($rows)>0)
					{

						for($i=0;$i<count($rows);$i++)
						{
								if( $rows[$i]->gps_info_io_port == "0000100000"){
									$engine_bit = 1;
								}else{
									$engine_bit = 0;

								}

								if($rows[$i]->gps_speed > 1 ){
									$engine_bit = 1;
								}

								if($rows[$i]->gps_cs == 53){
									$io2_bit = 1;
								}else{
									$io2_bit = 0;
								}

								$gpstime_wta = date("Y-m-d H:i:s", strtotime("+7 hour", strtotime($rows[$i]->gps_time))); //wita

								$street_name = "-";
								$geofence_name = "-";
								if($rows[$i]->gps_street_name != ""){
									$street_name = $rows[$i]->gps_street_name;
								}

								if($rows[$i]->gps_geofence_name != ""){
									$geofence_name = $rows[$i]->gps_geofence_name;
								}

								$DataToUpload[$i]->Name = $rows[$i]->gps_name;
								$DataToUpload[$i]->Host = $rows[$i]->gps_host;
								$DataToUpload[$i]->Type = $rows[$i]->gps_type;
								$DataToUpload[$i]->Speed = $rows[$i]->gps_speed;
								$DataToUpload[$i]->Direction = $rows[$i]->gps_course;
								$DataToUpload[$i]->Fuel = $rows[$i]->gps_mvd;
								$DataToUpload[$i]->Engine = $engine_bit;
								$DataToUpload[$i]->Io2 = $io2_bit;
								$DataToUpload[$i]->GpsStatus = $rows[$i]->gps_status;
								$DataToUpload[$i]->GpsTime = $gpstime_wta;
								$DataToUpload[$i]->Latitude = $rows[$i]->gps_latitude_real;
								$DataToUpload[$i]->Longitude = $rows[$i]->gps_longitude_real;
								$DataToUpload[$i]->Odometer = $rows[$i]->gps_odometer;
								$DataToUpload[$i]->StreetName = $street_name;
								$DataToUpload[$i]->GeofenceName = $geofence_name;
								$DataToUpload[$i]->Odometer = $rows[$i]->gps_odometer;



						}

					}

			}
			//$content = json_encode($datajson);
			$content = $DataToUpload;

			//echo $content;
			echo json_encode(array("code" => 200, "msg" => "ok", "data" => $content, "payload" => $payload), JSON_NUMERIC_CHECK);
			$nowendtime = date("Y-m-d H:i:s");
			$this->insertHitAPI("API RAW GPS",$payload,$nowstarttime,$nowendtime);
			$this->db->close();
			$this->db->cache_delete_all();

		}


		exit;
	}

	function getrawgps_data($rowvehicle, $sdate, $edate)
	{

		$rows = "";
        $json = json_decode($rowvehicle->vehicle_info);
        if (isset($json->vehicle_ip) && isset($json->vehicle_port))
        {
			$databases = $this->config->item('databases');
			if (isset($databases[$json->vehicle_ip][$json->vehicle_port]))
			{
				$database = $databases[$json->vehicle_ip][$json->vehicle_port];
				$table = $this->config->item("external_gpstable");
				$tableinfo = $this->config->item("external_gpsinfotable");
				$this->dbhist = $this->load->database($database, TRUE);
				$this->dbhist2 = $this->load->database("gpshistory",true);

			}
			else
			{
				$table = $this->gpsmodel->getGPSTable($rowvehicle->vehicle_type);
				$tableinfo = $this->gpsmodel->getGPSInfoTable($rowvehicle->vehicle_type);
				$this->dbhist = $this->load->database("default", TRUE);
				$this->dbhist2 = $this->load->database("gpshistory",true);

			}

				$vehicle_device = explode("@", $rowvehicle->vehicle_device);
               	$tablehist = strtolower($vehicle_device[0])."@".strtolower($vehicle_device[1])."_gps";
				$tablehistinfo = strtolower($vehicle_device[0])."@".strtolower($vehicle_device[1])."_info";

				$this->dbhist->join($tableinfo, "gps_info_time = gps_time AND gps_info_device = CONCAT(gps_name,'@',gps_host)");
				$this->dbhist->order_by("gps_time","asc");
				//$this->dbhist->group_by("gps_time");
				$this->dbhist->where("gps_name", $vehicle_device[0]);
                $this->dbhist->where("gps_time >=", $sdate);
                $this->dbhist->where("gps_time <=", $edate);
				$this->dbhist->select("gps_name,gps_host,gps_type,gps_speed,gps_course,gps_status,gps_mvd,gps_cs,gps_time,gps_latitude_real,gps_longitude_real,gps_odometer,gps_info_io_port");
				//$this->dbhist->limit(10);
				$this->dbhist->from($table);
                $q = $this->dbhist->get();
                $rows1 = $q->result();


				$this->dbhist2->join($tablehistinfo, "gps_info_time = gps_time AND gps_info_device = CONCAT(gps_name,'@',gps_host)");
				$this->dbhist2->order_by("gps_time","asc");
				//$this->dbhist2->group_by("gps_time");
                $this->dbhist2->where("gps_name", $vehicle_device[0]);
                $this->dbhist2->where("gps_time >=", $sdate);
                $this->dbhist2->where("gps_time <=", $edate);
				$this->dbhist2->select("gps_name,gps_host,gps_type,gps_speed,gps_course,gps_status,gps_mvd,gps_cs,gps_time,gps_latitude_real,gps_longitude_real,gps_odometer,gps_info_io_port");
				//$this->dbhist2->limit(10);
				$this->dbhist2->from($tablehist);
				$q2 = $this->dbhist2->get();
                $rows2 = $q2->result();

				$rows = array_merge($rows1, $rows2);
				$rows = $this->array_sort($rows, 'gps_time', SORT_ASC);
				$trows = count($rows);

                //printf("TOTAL DATA : %s \r\n",$trows);


		}

		return $rows;
	}

	function getrawgps_datav2($rowvehicle, $sdate, $edate)
	{

		$rows = "";
        $json = json_decode($rowvehicle->vehicle_info);
        if (isset($json->vehicle_ip) && isset($json->vehicle_port))
        {
			$databases = $this->config->item('databases');
			if (isset($databases[$json->vehicle_ip][$json->vehicle_port]))
			{
				$database = $databases[$json->vehicle_ip][$json->vehicle_port];
				$table = $this->config->item("external_gpstable");
				$tableinfo = $this->config->item("external_gpsinfotable");
				$this->dbhist = $this->load->database($database, TRUE);
				$this->dbhist2 = $this->load->database("gpshistory",true);

			}
			else
			{
				$table = $this->gpsmodel->getGPSTable($rowvehicle->vehicle_type);
				$tableinfo = $this->gpsmodel->getGPSInfoTable($rowvehicle->vehicle_type);
				$this->dbhist = $this->load->database("default", TRUE);
				$this->dbhist2 = $this->load->database("gpshistory",true);

			}

				$vehicle_device = explode("@", $rowvehicle->vehicle_device);
               	$tablehist = strtolower($vehicle_device[0])."@".strtolower($vehicle_device[1])."_gps";
				$tablehistinfo = strtolower($vehicle_device[0])."@".strtolower($vehicle_device[1])."_info";

				$this->dbhist->join($tableinfo, "gps_info_time = gps_time AND gps_info_device = CONCAT(gps_name,'@',gps_host)");
				$this->dbhist->order_by("gps_time","asc");
				//$this->dbhist->group_by("gps_time");
				$this->dbhist->where("gps_name", $vehicle_device[0]);
                $this->dbhist->where("gps_time >=", $sdate);
                $this->dbhist->where("gps_time <=", $edate);
				//$this->dbhist2->where("gps_speed >", 0); //di coba all data status
				$this->dbhist->select("gps_name,gps_host,gps_type,gps_speed,gps_course,gps_status,gps_mvd,gps_cs,gps_time,gps_latitude_real,gps_longitude_real,gps_odometer,gps_street_name,gps_geofence_name,gps_info_io_port");
				$this->dbhist->limit(4000);
				$this->dbhist->from($table);
                $q = $this->dbhist->get();
                $rows1 = $q->result();


				$this->dbhist2->join($tablehistinfo, "gps_info_time = gps_time AND gps_info_device = CONCAT(gps_name,'@',gps_host)");
				$this->dbhist2->order_by("gps_time","asc");
				//$this->dbhist2->group_by("gps_time");
                $this->dbhist2->where("gps_name", $vehicle_device[0]);
                $this->dbhist2->where("gps_time >=", $sdate);
                $this->dbhist2->where("gps_time <=", $edate);
				//$this->dbhist2->where("gps_speed >", 0);
				$this->dbhist2->select("gps_name,gps_host,gps_type,gps_speed,gps_course,gps_status,gps_mvd,gps_cs,gps_time,gps_latitude_real,gps_longitude_real,gps_odometer,gps_street_name,gps_geofence_name,gps_info_io_port");
				$this->dbhist2->limit(4000);
				$this->dbhist2->from($tablehist);
				$q2 = $this->dbhist2->get();
                $rows2 = $q2->result();

				$rows = array_merge($rows1, $rows2);
				$rows = $this->array_sort($rows, 'gps_time', SORT_ASC);
				$trows = count($rows);

                //printf("TOTAL DATA : %s \r\n",$trows);


		}

		return $rows;
	}

	function getritasehour()
	{
		ini_set('memory_limit', "2G");
		ini_set('max_execution_time', 180); // 3 minutes
		$nowstarttime = date("Y-m-d H:i:s");
		header("Content-Type: application/json");

		$token            = "UGaW5kNkjhA782GBNS1616KbswQYa5372bsdexVNT16";
		$postdata         = json_decode(file_get_contents("php://input"));
		$allvehicle       = 0;
		$now              = date("Ymd");
		$payload          = "";
		$forbidden_symbol = array("'", ",", ".", "?", "!", ";", ":", "-");

		$headers = null;
		if (isset($_SERVER['Authorization']))
		{
				$headers = trim($_SERVER["Authorization"]);
		}
		else if (isset($_SERVER['HTTP_AUTHORIZATION']))
		{ //Nginx or fast CGI
				$headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
		}
		else if (function_exists('apache_request_headers'))
		{
			$requestHeaders = apache_request_headers();
			// Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
			$requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
			if (isset($requestHeaders['Authorization']))
			{
				$headers = trim($requestHeaders['Authorization']);
			}
		}

		if($headers != $token)
		{
			$feature["code"]    = 400;
			$feature["msg"]     = "Invalid Authorization Key ! ";
			$feature["payload"] = $payload;
			echo json_encode($feature);
			exit;
		}

		$feature = array();

		if(!isset($postdata->UserId) || $postdata->UserId == "")
		{
			$feature["code"]    = 400;
			$feature["msg"]     = "Invalid User ID";
			$feature["payload"] = $payload;
			echo json_encode($feature);
			exit;
		}else{

			//hanya user yg terdaftar yg bisa akes API
			$this->db->where("api_user",$postdata->UserId);
			$this->db->where("api_token",$headers);
			$this->db->where("api_status",1);
			$this->db->where("api_flag",0);
			$q = $this->db->get("api_user");
			if($q->num_rows == 0)
			{
				$feature["code"]    = 400;
				$feature["msg"]     = "User & Authorization Key is Not Available!";
				$feature["payload"] = $payload;
				echo json_encode($feature);
				exit;
			}

		}

		$payload = array(
		 "UserId"    => $postdata->UserId,
		 "Date" 	 	 => $postdata->Date,
		 "Hour" 		 => $postdata->Hour,
		 "Shift"     => $postdata->Shift,
		 "CompanyId" => $postdata->CompanyId
	 );

	 // echo "<pre>";
	 // var_dump($payload);die();
	 // echo "<pre>";

	 if($postdata->Shift == "" || $postdata->Shift > 2 || (!is_numeric($postdata->Shift)))
	 {
		 $feature["code"]    = 400;
		 $feature["msg"]     = "Invalid Shift";
		 $feature["payload"] = $payload;
		 echo json_encode($feature);
		 exit;
	 }else {
		 $shiftfix = $postdata->Shift;
	 }

		if($postdata->CompanyId == "")
		{
			$feature["code"]    = 400;
			$feature["msg"]     = "Company ID is empty";
			$feature["payload"] = $payload;
			echo json_encode($feature);
			exit;
		}
		else
		{
			$company       = $postdata->CompanyId;

			// CEK SYMBOL TERLARANG
			if ($this->strposa($company, $forbidden_symbol, 1)) {
					$symbolfounded = 1;
			} else {
					$symbolfounded = 0;
			}

				if ($symbolfounded == 1) {
					$feature["code"]    = 400;
					$feature["msg"]     = "CompanyID is only can be filled by ID or all";
					$feature["payload"] = $payload;
					echo json_encode($feature);
					exit;
				}

				if ($company == "all") {
					$data_company = $postdata->CompanyId;
				}else {
					$data_company = $this->m_ugemsmodel->getcompanyname_byID($company);
						if ($data_company == "-") {
							$feature["code"]    = 400;
							$feature["msg"]     = "Invalid Company ID";
							$feature["payload"] = $payload;
							echo json_encode($feature);
							exit;
						}else {
							$data_company = $data_company[0]->company_id;
						}
				}
		}

		if($postdata->Date == "")
		{
			$feature["code"]    = 400;
			$feature["msg"]     = "Date can not be empty";
			$feature["payload"] = $payload;
			echo json_encode($feature);
			exit;
		}
		else
		{
			$sdate = $postdata->Date;
		}

		if(!isset($postdata->Hour) || $postdata->Hour == "")
		{
			$feature["code"]    = 400;
			$feature["msg"]     = "Time can not be empty";
			$feature["payload"] = $payload;
			echo json_encode($feature);
			exit;
		}else {
			$shourfix 		= "all";
			$shour        = $postdata->Hour;
				if ($shour != "all") {
					$checkformat  = $this->verify_time_format($shour);
						if ($checkformat == false) {
							$feature["code"]    = 400;
							$feature["msg"]     = "Invalid Hour Format";
							$feature["payload"] = $payload;
							echo json_encode($feature);
							exit;
						}else {
							$shourfix = $shour;
						}
				}
		}

		// echo "<pre>";
		// var_dump($shourfix);die();
		// echo "<pre>";

		// PENCARIAN DIMULAI
		$company  = $data_company;
		$datein   = $sdate;
		$shift    = $shiftfix;
		$date     = date("Y-m-d", strtotime($datein));

		$lastdate = date("Y-m-t", strtotime($datein));
		$year     = date("Y", strtotime($datein));
		$month    = date("m", strtotime($datein));
		$day      = date('d', strtotime($datein));
		$day++;
		$jmlday = strlen($day);
		if ($jmlday == 1) {
				$day = "0" . $day;
		}
		$next = $year . "-" . $month . "-" . $day;
		$port_list = array("PORT BIB","PORT BIR","PORT TIA");
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

		$input = array(
				"company" => $company,
				"date"    => $arraydate,
				"shift"   => $shift
		);

		$this->dbts = $this->load->database("webtracking_ts", true);
        if ($shift == 1) {
           // $this->dbts->select("ritase_report_vehicle_no,ritase_report_company_name,ritase_report_gps_date,ritase_report_gps_hour,ritase_report_coordinate,ritase_report_latitude, ritase_report_longitude,ritase_report_from,ritase_report_to,ritase_report_duration");
            $shift = array("06:00:00", "07:00:00", "08:00:00", "09:00:00", "10:00:00", "11:00:00", "12:00:00", "13:00:00", "14:00:00", "15:00:00", "16:00:00", "17:00:00");
            $this->dbts->where("ritase_report_gps_date", $date);
			$this->dbts->where("ritase_report_duration_sec >", 0);
			$this->dbts->where_in("ritase_report_to", $port_list);

            if ($company != 0) {
                $this->dbts->where("ritase_report_vehicle_company", $company);
            }
            $this->dbts->where_in("ritase_report_gps_hour", $shift);
            $this->dbts->order_by("ritase_report_gps_hour", "asc");
            $this->dbts->order_by("ritase_report_company_name", "asc");
            $result = $this->dbts->get("ts_ritase_hour");
            $data = $result->result_array();
            $nr = $result->num_rows();
        } else if ($shift == 2) {
            //$this->dbts->select("ritase_report_vehicle_no,ritase_report_company_name,ritase_report_gps_date,ritase_report_gps_hour,ritase_report_coordinate,ritase_report_latitude, ritase_report_longitude,ritase_report_from,ritase_report_to,ritase_report_duration");
            $shift1 = array("18:00:00", "19:00:00", "20:00:00", "21:00:00", "22:00:00", "23:00:00");
            $shift2 = array("00:00:00", "01:00:00", "02:00:00", "03:00:00", "04:00:00", "05:00:00");
            $this->dbts->where("ritase_report_gps_date", $date);
			$this->dbts->where("ritase_report_duration_sec >", 0);
			$this->dbts->where_in("ritase_report_to", $port_list);
            if ($company != 0) {
                $this->dbts->where("ritase_report_vehicle_company", $company);
            }
            $this->dbts->where_in("ritase_report_gps_hour", $shift1);
            $this->dbts->order_by("ritase_report_gps_hour", "asc");
            $this->dbts->order_by("ritase_report_company_name", "asc");
            $result = $this->dbts->get("ts_ritase_hour");
            $data1 = $result->result_array();
            $nr1 = $result->num_rows();
            $this->dbts->distinct();
            //$this->dbts->select("ritase_report_vehicle_no,ritase_report_company_name,ritase_report_gps_date,ritase_report_gps_hour,ritase_report_coordinate,ritase_report_latitude, ritase_report_longitude,ritase_report_from,ritase_report_to,ritase_report_duration");
            $this->dbts->where("ritase_report_gps_date", $next);
			$this->dbts->where("ritase_report_duration_sec >", 0);
			$this->dbts->where_in("ritase_report_to", $port_list);
            if ($company != 0) {
                $this->dbts->where("ritase_report_vehicle_company", $company);
            }
            $this->dbts->where_in("ritase_report_gps_hour", $shift2);
            $this->dbts->order_by("ritase_report_gps_hour", "asc");
            $this->dbts->order_by("ritase_report_company_name", "asc");
            $result = $this->dbts->get("ts_ritase_hour");
            $data2 = $result->result_array();
            $nr2 = $result->num_rows();
            $data = array_merge($data1, $data2);
            $nr = $nr1 +  $nr2;
        } else {
            //$this->dbts->select("ritase_report_vehicle_no,ritase_report_company_name,ritase_report_gps_date,ritase_report_gps_hour,ritase_report_coordinate,ritase_report_latitude, ritase_report_longitude,ritase_report_from,ritase_report_to,ritase_report_duration");
            $shift1 = array("06:00:00", "07:00:00", "08:00:00", "09:00:00", "10:00:00", "11:00:00", "12:00:00", "13:00:00", "14:00:00", "15:00:00", "16:00:00", "17:00:00", "18:00:00", "19:00:00", "20:00:00", "21:00:00", "22:00:00", "23:00:00");
            $shift2 = array("00:00:00", "01:00:00", "02:00:00", "03:00:00", "04:00:00", "05:00:00");
            $this->dbts->where("ritase_report_gps_date", $date);
			$this->dbts->where("ritase_report_duration_sec >", 0);
			$this->dbts->where_in("ritase_report_to", $port_list);
            if ($company != 0) {
                $this->dbts->where("ritase_report_vehicle_company", $company);
            }
            $this->dbts->where_in("ritase_report_gps_hour", $shift1);
            $this->dbts->order_by("ritase_report_gps_hour", "asc");
            $this->dbts->order_by("ritase_report_company_name", "asc");
            $result = $this->dbts->get("ts_ritase_hour");
            $data1 = $result->result_array();
            $nr1 = $result->num_rows();
            $this->dbts->distinct();
            //$this->dbts->select("ritase_report_vehicle_no,ritase_report_company_name,ritase_report_gps_date,ritase_report_gps_hour,ritase_report_coordinate,ritase_report_latitude, ritase_report_longitude,ritase_report_from,ritase_report_to,ritase_report_duration");
            $this->dbts->where("ritase_report_gps_date", $next);
			$this->dbts->where("ritase_report_duration_sec >", 0);
			$this->dbts->where_in("ritase_report_to", $port_list);
            if ($company != 0) {
                $this->dbts->where("ritase_report_vehicle_company", $company);
            }
            $this->dbts->where_in("ritase_report_gps_hour", $shift2);
            $this->dbts->order_by("ritase_report_gps_hour", "asc");
            $this->dbts->order_by("ritase_report_company_name", "asc");
            $result = $this->dbts->get("ts_ritase_hour");
            $data2 = $result->result_array();
            $nr2 = $result->num_rows();
            $data = array_merge($data1, $data2);
            $nr = $nr1 +  $nr2;
        }


		$datafix = array();
		for ($i=0; $i < sizeof($data); $i++) {
			array_push($datafix, array(
				"VehicleUserId"    => $data[$i]['ritase_report_vehicle_user_id'],
				"VehicleId"        => $data[$i]['ritase_report_vehicle_id'],
				"VehicleDevice"    => $data[$i]['ritase_report_vehicle_device'],
				"VehicleNo"        => $data[$i]['ritase_report_vehicle_no'],
				"VehicleName"      => $data[$i]['ritase_report_vehicle_name'],
				"VehicleType"      => $data[$i]['ritase_report_vehicle_type'],
				"VehicleCompany"   => $data[$i]['ritase_report_vehicle_company'],
				"VehicleImei"      => $data[$i]['ritase_report_imei'],
				"ReportType"       => $data[$i]['ritase_report_type'],
				"ReportName"      	=> $data[$i]['ritase_report_name'],
				"RomName"     		=> $data[$i]['ritase_report_from'],
				"RomGpsTime"        => $data[$i]['ritase_report_from_time'],
				"PortName"          => $data[$i]['ritase_report_to'],
				"PortGpsTime"       => $data[$i]['ritase_report_to_time'],
				"GroupDate"      	=> $data[$i]['ritase_report_gps_date'],
				"GroupHour"  		=> $data[$i]['ritase_report_gps_hour'],
				"Duration"   		=> $data[$i]['ritase_report_duration'],
				"DurationSecond" 	=> $data[$i]['ritase_report_duration_sec'],
				"DriverId"   		=> $data[$i]['ritase_report_driver'],
				"DriverName"      	=> $data[$i]['ritase_report_driver_name'],
				"WimId"      		=> $data[$i]['ritase_report_wim_id'],
				"WimNetto"     		=> $data[$i]['ritase_report_wim_netto']
			));
		}


		if ($nr > 0) {
				echo json_encode(array("code" => 200, "msg" => "success",  "data" => $datafix, "payload" => $payload), JSON_NUMERIC_CHECK);
		} else {
				echo json_encode(array("code" => 200, "msg" => "Data Empty"));
		}

		// INI DIAKTIFKAN UNTUK MENCATAT HIT DARI API
		$nowendtime = date("Y-m-d H:i:s");
		$this->insertHitAPI("API Ritase Hour", $payload, $nowstarttime, $nowendtime);
		$this->db->close();
		$this->db->cache_delete_all();

		exit;
	}

	function gettokenugems($userid="")
	{
		$url = "https://api.ugems.id/gpsdatapush/generate_token";
		$data = array("username" => "admin", "password" => "admin");

				$content = json_encode($data);

				$curl = curl_init($url);
				curl_setopt($curl, CURLOPT_HEADER, false);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
				curl_setopt($curl, CURLOPT_HTTPHEADER,
				        array("Content-type: application/json"));
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $content);

				$json_response = curl_exec($curl);
				$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
				$result = json_decode($json_response);
				$data =  $result->data;
				$token = $data->token;

				unset($data);
				$data["sess_value"] = $token;
				$data["sess_type"] = "TOKEN";
				$data["sess_lastmodified"] = date("Y-m-d H:i:s");
				$data["sess_status"] = 1;
				$data["sess_user"] = $userid;

				$this->dbts = $this->load->database("webtracking_ts", true);
				$this->dbts->insert("ts_ugems_token",$data);

				$nowtime = date("Y-m-d H:i:s");
				printf("===GET TOKEN UGEMS SUCESS !! at %s \r\n", $nowtime);
				$this->dbts->close();
				$this->dbts->cache_delete_all();

	}

	function getLastToken($userid)
	{

		$this->db = $this->load->database("webtracking_ts", TRUE);
		$this->db->select("sess_value");
		$this->db->order_by("sess_id", "desc");
		$this->db->where("sess_user", $userid);
		$this->db->where("sess_status", 1);
		$q = $this->db->get("ts_ugems_token");
		$row = $q->row();
		if(count($row)>0){
			$sessid = $row->sess_value;
		}else{
			$sessid = "";
		}
		return $sessid;

	}

	function pushrawgps($userid="")
	{
		ini_set('display_errors', 1);
		date_default_timezone_set("Asia/Jakarta");
		$nowdate = date("Y-m-d H:i:s");
		printf("===Get API Service . . . at %s \r\n", $nowdate);
		printf("======================================\r\n");

		if($userid == ""){
			printf("NO DATA USER ID !! \r\n");
			return;
		}

		$this->db->order_by("vehicle_id","desc");
		$this->db->where("vehicle_user_id",$userid);
		$this->db->where("vehicle_status",1);
		//$this->db->where("vehicle_no","BBS 1210");
		//$this->db->limit(50);
		$q = $this->db->get("vehicle");

		$vehicle = $q->result();
		$totalvehicle = count($vehicle);

		$mytoken = $this->getLastToken($userid);

		//jika mobil lebih dari nol
		if(count($vehicle) > 0)
		{
			$DataToUpload = array();
			unset($DataToUpload);
			$j = 1;
			for($z=0;$z<count($vehicle);$z++)
			{

				printf("===Process Check Last Info For %s %s (%d/%d) \r\n", $vehicle[$z]->vehicle_no, $vehicle[$z]->vehicle_device, $j, $totalvehicle);

				$devices = explode("@", $vehicle[$z]->vehicle_device);
				$vehicle_dblive = $vehicle[$z]->vehicle_dbname_live;
				$vehicle_imei = $devices[0];
				$gps = $this->getlastposition_fromDBLive($vehicle_imei,$vehicle_dblive);

					if(isset($gps) && count($gps)>0)
					{
						$datajson = json_decode($gps->vehicle_autocheck);

							if($gps->gps_speed > 1 ){
								$engine = "ON";
							}else{
								$engine = $datajson->auto_last_engine;
							}

							if($engine == 'ON'){
								$engine_bit = 1;
							}else{
								$engine_bit = 0;
							}

								if($gps->gps_cs == 53){
									$io2_bit = 1;
								}else{
									$io2_bit = 0;
								}

								$gpstime_wta = date("Y-m-d H:i:s", strtotime("+7 hour", strtotime($gps->gps_time))); //wita

								$DataToUpload[$z]->name = $gps->gps_name;
								$DataToUpload[$z]->host = $gps->gps_host;
								$DataToUpload[$z]->type = $gps->gps_type;
								$DataToUpload[$z]->speed = $gps->gps_speed;
								$DataToUpload[$z]->direction = $gps->gps_course;
								$DataToUpload[$z]->fuel = $gps->gps_mvd;
								$DataToUpload[$z]->engine = $engine_bit;
								$DataToUpload[$z]->io2 = $io2_bit;
								$DataToUpload[$z]->gpsstatus = $gps->gps_status;
								$DataToUpload[$z]->gpstime = $gpstime_wta;
								$DataToUpload[$z]->latitude = $gps->gps_latitude_real;
								$DataToUpload[$z]->longitude = $gps->gps_longitude_real;
								$DataToUpload[$z]->odometer = $gps->gps_odometer;


								printf("GET LAST POSITION \r\n");

								$token = $mytoken;
								$authorization = "token: Bearer ".$token;
								$url = "https://api.ugems.id/gpsdatapush/send_data";

								$content = json_encode(array("data" => $DataToUpload));


					}


				$j++;
			}

			//printf("Data JSON : %s \r \n",$content);

								$curl = curl_init($url);
								curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
								curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
								curl_setopt($curl, CURLOPT_HEADER, false);
								curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
								curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
								curl_setopt($curl, CURLOPT_POST, true);
								curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
								curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
								curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

								$result = curl_exec($curl);

								echo $result;
								echo curl_getinfo($curl, CURLINFO_HTTP_CODE);

								// Get the POST request header status
								//$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

								// If header status is not Created or not OK, return error message
								/* if ( $status !== 201 || $status !== 200 ) {
								   die("Error: call to URL $url failed with status $status, response $result, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
								} */
								printf("-------------------------- \r\n");


			$this->db->close();
			$this->db->cache_delete_all();

		}

		$finishdate = date("Y-m-d H:i:s");
		printf("===FINISH . . . %s to %s \r\n", $nowdate, $finishdate);
		printf("======================================\r\n");


		exit;
	}

	function getgpsoffline()
	{
		//ini_set('display_errors', 1);
		ini_set('memory_limit', "512M");
		//ini_set('max_execution_time', 180); // 3 minutes
		$nowstarttime = date("Y-m-d H:i:s");
		header("Content-Type: application/json");

		$token = "UGaW5kNkjhA782GBNS1616KbswQYa5372bsdexVNT16";
		$postdata = json_decode(file_get_contents("php://input"));
		$allcompany = 0;
		$now = date("Ymd");
		$payload = "";
		$dbtable = "report_gps_status_historikal";

		$headers = null;
		if (isset($_SERVER['Authorization']))
		{
            $headers = trim($_SERVER["Authorization"]);
        }
		else if (isset($_SERVER['HTTP_AUTHORIZATION']))
		{ //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        }
        else if (function_exists('apache_request_headers'))
        {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization']))
            {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
		//print_r($headers." || ".$token." || ".$postdata->UserId);exit();

		if($headers != $token)
        {
			$feature["code"] = 400;
			$feature["msg"] = "Invalid Authorization Key ! ";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}

		$feature = array();

		if(!isset($postdata->UserId) || $postdata->UserId == "")
		{
			$feature["code"] = 400;
			$feature["msg"] = "Invalid User ID";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}else{

			//hanya user yg terdaftar yg bisa akes API
			$this->db->where("api_user",$postdata->UserId);
			$this->db->where("api_token",$headers);
			$this->db->where("api_status",1);
			$this->db->where("api_flag",0);
			$q = $this->db->get("api_user");
			if($q->num_rows == 0)
			{
				$feature["code"] = 400;
				$feature["msg"] = "User & Authorization Key is Not Available!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}

		}

		if(!isset($postdata->CompanyId) || $postdata->CompanyId == "all")
		{
			$allcompany = 1;
		}

		if(!isset($postdata->CompanyId) || $postdata->CompanyId == "")
		{
			$feature["code"] = 400;
			$feature["msg"] = "Invalid Company ID!";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}else{
			$check_company = strpos($postdata->CompanyId,';');
			$ex_company = explode(";",$postdata->CompanyId);
			$UserIDBIB = 4408;


			$this->db->order_by("company_name","asc");
			if($allcompany == 0){
				$this->db->where_in("company_id",$ex_company);
			}
			$this->db->where("company_flag",0);

			$q = $this->db->get("company");
			$data = $q->result();

			if($q->num_rows == 0)
			{
				$feature["code"] = 400;
				$feature["msg"] = "CompanyId Not Found!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}else{
				$vehicle = $q->result();

				 $payload      		= array(
				  "UserId"          => $postdata->UserId,
				  "CompanyId"   	=> $postdata->CompanyId,
				  "StartTime" 	 	=> $postdata->StartTime,
				  "EndTime"   		=> $postdata->EndTime

				);

			}
		}

		if($postdata->StartTime == "" || $postdata->EndTime == "")
		{
			$feature["code"] = 400;
			$feature["msg"] = "No Data Periode Start or Periode End";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}
		else
		{
			$sdate = $postdata->StartTime;
			$edate = $postdata->EndTime;
		}

		//jika mobil lebih dari nol
		if(count($data) > 0)
		{
			$DataToUpload = array();

			for($z=0;$z<count($data);$z++)
			{


				$companyid = $data[$z]->company_id;

					$rows = $this->getgpsoffline_data($dbtable, $allcompany, $companyid, $sdate, $edate);

					if(isset($rows) && count($rows)>0)
					{

						for($i=0;$i<count($rows);$i++)
						{

								$DataToUpload[$i]->VehicleNo = $rows[$i]->gpsoffline_vehicle_no;
								$DataToUpload[$i]->VehicleName = $rows[$i]->gpsoffline_vehicle_name;
								$DataToUpload[$i]->VehicleDevice = $rows[$i]->gpsoffline_vehicle_device;
								$DataToUpload[$i]->CompanyId = $rows[$i]->gpsoffline_vehicle_companyid;
								$DataToUpload[$i]->CompanyName = $rows[$i]->gpsoffline_vehicle_companyname;
								$DataToUpload[$i]->GpsLastUpdated = $rows[$i]->gpsoffline_lastupdate;
								$DataToUpload[$i]->GpsStatus = $rows[$i]->gpsoffline_status;
								$DataToUpload[$i]->LastChecked = $rows[$i]->gpsoffline_data_submited;

						}

					}

			}

			$content = $DataToUpload;

			//echo $content;
			echo json_encode(array("code" => 200, "msg" => "ok", "data" => $content, "payload" => $payload), JSON_NUMERIC_CHECK);
			$nowendtime = date("Y-m-d H:i:s");
			$this->insertHitAPI("API GPS Offline",$payload,$nowstarttime,$nowendtime);
			$this->db->close();
			$this->db->cache_delete_all();

		}


		exit;
	}

	function getgpsoffline_data($dbtable, $companyall, $company, $sdate, $edate)
	{
		$this->dbts = $this->load->database("tensor_report", true);
		$this->dbts->where("gpsoffline_data_submited >=", $sdate);
		$this->dbts->where("gpsoffline_data_submited <=", $edate);
		if($companyall != 1){
			$this->dbts->where("gpsoffline_vehicle_companyid", $company);
		}
		$this->dbts->where("gpsoffline_status", "OFFLINE");
		$this->dbts->order_by("gpsoffline_data_submited", "asc");

		$q = $this->dbts->get($dbtable);
		$rows = $q->result();

		$this->dbts->close();
		$this->dbts->cache_delete_all();
		return $rows;
	}

	function getlastposition_fromDBLive($imei,$dblive)
	{

		$this->dblive = $this->load->database($dblive,true);

		$this->dblive->order_by("gps_time", "desc");
		$this->dblive->where("gps_name", $imei);
		$this->dblive->limit(1);
		$qpost = $this->dblive->get("gps");
		$rowpost = $qpost->row();

		$this->dblive->close();
		$this->dblive->cache_delete_all();

		return $rowpost;

	}

	function array_sort($array, $on, $order=SORT_ASC)
	{

		$new_array = array();
		$sortable_array = array();

		if (count($array) > 0) {
			foreach ($array as $k => $v) {
				if (is_array($v)) {
					foreach ($v as $k2 => $v2) {
						if ($k2 == $on) {
							$sortable_array[$k] = $v2;
						}
					}
				} else {
					$sortable_array[$k] = $v;
				}
			}

			switch ($order) {
				case SORT_ASC:
					asort($sortable_array);
					break;
				case SORT_DESC:
					arsort($sortable_array);
					break;
			}

			foreach ($sortable_array as $k => $v) {
				$new_array[$k] = $array[$k];
			}
		}

		return $new_array;
	}

	function verify_time_format($value)
	{
		$pattern1 = '/^(0?\d|1\d|2[0-3]):[0-5]\d:[0-5]\d$/';
		$pattern2 = '/^(0?\d|1[0-2]):[0-5]\d\s(am|pm)$/i';
		return preg_match ($pattern1, $value) || preg_match ($pattern2, $value);
	}

	function strposa($haystack, $needles=array(), $offset=0)
	{
        $chr = array();
        foreach($needles as $needle) {
                $res = strpos($haystack, $needle, $offset);
                if ($res !== false) $chr[$needle] = $res;
        }
        if(empty($chr)) return false;
        return min($chr);
	}

	function getlaststatus()
	{
		//ini_set('display_errors', 1);
		ini_set('memory_limit', "2G");
		ini_set('max_execution_time', 180); // 3 minutes
		$nowstarttime = date("Y-m-d H:i:s");
		header("Content-Type: application/json");

		$token = "UGaW5kNkjhA782GBNS1616KbswQYa5372bsdexVNT16";
		$postdata = json_decode(file_get_contents("php://input"));
		$allvehicle = 0;
		$now = date("Ymd");
		$payload = "";

		$headers = null;
		if (isset($_SERVER['Authorization']))
		{
            $headers = trim($_SERVER["Authorization"]);
        }
		else if (isset($_SERVER['HTTP_AUTHORIZATION']))
		{ //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        }
        else if (function_exists('apache_request_headers'))
        {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization']))
            {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
		//print_r($headers." || ".$token." || ".$postdata->UserId);exit();

		if($headers != $token)
        {
			$feature["code"] = 400;
			$feature["msg"] = "Invalid Authorization Key ! ";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}

		$feature = array();

		if(!isset($postdata->UserId) || $postdata->UserId == "")
		{
			$feature["code"] = 400;
			$feature["msg"] = "Invalid User ID";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}else{

			//hanya user yg terdaftar yg bisa akes API
			$this->db->where("api_user",$postdata->UserId);
			$this->db->where("api_token",$headers);
			$this->db->where("api_status",1);
			$this->db->where("api_flag",0);
			$q = $this->db->get("api_user");
			if($q->num_rows == 0)
			{
				$feature["code"] = 400;
				$feature["msg"] = "User & Authorization Key is Not Available!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}

		}

		if(!isset($postdata->VehicleDevice) || $postdata->VehicleDevice == "all")
		{
			$allvehicle = 1;
		}


		if(!isset($postdata->VehicleDevice) || $postdata->VehicleDevice == "")
		{
			$feature["code"] = 400;
			$feature["msg"] = "Invalid Vehicle Device!";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}else{
			//$check_vehicle = strpos($postdata->VehicleDevice,';');
			$UserIDBIB = 4408;

			//jika ada cek dari database nopol (untuk dapat device id)
			$this->db->order_by("vehicle_id","desc");
			$this->db->select("vehicle_device,vehicle_dbname_live,vehicle_user_id,vehicle_info");
			$this->db->where("vehicle_device",$postdata->VehicleDevice);
			//$this->db->where("vehicle_user_id",$UserIDBIB); //sementara diopen untuk all user
			$this->db->where("vehicle_status",1);

			$q = $this->db->get("vehicle");
			$vehicle = $q->result();

			if($q->num_rows == 0)
			{
				$feature["code"] = 400;
				$feature["msg"] = "Device Not Found!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}else{
				$vehicle = $q->result();

				 $payload      		    = array(
				  "UserId"          => $postdata->UserId,
				  "VehicleDevice"   => $postdata->VehicleDevice,

				);

			}
		}


		//jika mobil lebih dari nol
		if(count($vehicle) > 0)
		{
			$DataToUpload = array();
			//unset($DataToUpload);
			for($z=0;$z<count($vehicle);$z++)
			{

				$devices = explode("@", $vehicle[$z]->vehicle_device);
				$vehicle_dblive = $vehicle[$z]->vehicle_dbname_live;
				$vehicle_imei = $devices[0];

					$gps = $this->getlastposition_fromDBLive($vehicle_imei,$vehicle_dblive);

					if(isset($gps) && count($gps)>0)
					{
						$datajson = json_decode($gps->vehicle_autocheck);
						$speed_kph = $datajson->auto_last_speed;

								if($speed_kph > 1 ){
									$engine = "ON";
								}else{
									$engine = $datajson->auto_last_engine;
								}

								if($engine == 'ON'){
									$engine_bit = 1;
								}else{
									$engine_bit = 0;
								}

								if($speed_kph > 1 ){
									$engine_bit = 1;
								}

								if($gps->gps_cs == 53){
									$io2_bit = 1;
								}else{
									$io2_bit = 0;
								}
								$street_name = "-";
								$geofence_name = "-";

								$position = $this->getPosition_other($gps->gps_longitude_real,$gps->gps_latitude_real);
								if(isset($position)){
									$ex_position = explode(",",$position->display_name);
									if(count($ex_position)>0){
										$street_name = $ex_position[0];
									}else{
										$street_name = $ex_position[0];
									}
								}else{
									$street_name = $position->display_name;
								}


								$geofence = $this->getGeofence_location_other($gps->gps_longitude_real,$gps->gps_latitude_real,$vehicle[$z]->vehicle_user_id);
								if($geofence){
									$geofence_name = $geofence;
								}

								$gpstime_wta = date("Y-m-d H:i:s", strtotime("+7 hour", strtotime($gps->gps_time))); //wita

								$DataToUpload[$z]->Name = $gps->gps_name;
								$DataToUpload[$z]->Host = $gps->gps_host;
								$DataToUpload[$z]->Type = $gps->gps_type;
								$DataToUpload[$z]->Speed = $speed_kph;
								$DataToUpload[$z]->Direction = $gps->gps_course;
								$DataToUpload[$z]->Fuel = $gps->gps_mvd;
								$DataToUpload[$z]->Engine = $engine_bit;
								$DataToUpload[$z]->Io2 = $io2_bit;
								$DataToUpload[$z]->GpsStatus = $gps->gps_status;
								$DataToUpload[$z]->GpsTime = $gpstime_wta;
								$DataToUpload[$z]->Latitude = $gps->gps_latitude_real;
								$DataToUpload[$z]->Longitude = $gps->gps_longitude_real;
								$DataToUpload[$z]->Odometer = $gps->gps_odometer;
								$DataToUpload[$z]->StreetName = $street_name;
								$DataToUpload[$z]->GeofenceName = $geofence_name;

					}

			}
			//$content = json_encode($datajson);
			$content = $DataToUpload;

			//echo $content;
			echo json_encode(array("code" => 200, "msg" => "ok", "data" => $content, "payload" => $payload), JSON_NUMERIC_CHECK);
			$nowendtime = date("Y-m-d H:i:s");
			//$this->insertHitAPI("API Get Last Status",$payload,$nowstarttime,$nowendtime);
			$this->db->close();
			$this->db->cache_delete_all();

		}


		exit;
	}

	function getvehicledetail()
	{
		//ini_set('display_errors', 1);
		$nowstarttime = date("Y-m-d H:i:s");
		header("Content-Type: application/json");

		$token = "UGaW5kNkjhA782GBNS1616KbswQYa5372bsdexVNT16";
		$postdata = json_decode(file_get_contents("php://input"));
		$allvehicle = 0;
		$allcompany = 0;
		$payload = "";
		$now = date("Ymd");

		$headers = null;
		if (isset($_SERVER['Authorization']))
		{
            $headers = trim($_SERVER["Authorization"]);
        }
		else if (isset($_SERVER['HTTP_AUTHORIZATION']))
		{ //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        }
        else if (function_exists('apache_request_headers'))
        {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization']))
            {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
		//print_r($headers." || ".$token." || ".$postdata->UserId);exit();



		if($headers != $token)
        {
			$feature["code"] = 400;
			$feature["msg"] = "Invalid Authorization Key ! ";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}

		$feature = array();

		if(!isset($postdata->UserId) || $postdata->UserId == "")
		{
			$feature["code"] = 400;
			$feature["msg"] = "Invalid User ID";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}else{

			//hanya user yg terdaftar yg bisa akes API
			$this->db->where("api_user",$postdata->UserId);
			$this->db->where("api_token",$headers);
			$this->db->where("api_status",1);
			$this->db->where("api_flag",0);
			$q = $this->db->get("api_user");
			if($q->num_rows == 0)
			{
				$feature["code"] = 400;
				$feature["msg"] = "User & Authorization Key is Not Available!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}

		}

		if(!isset($postdata->VehicleDevice) || $postdata->VehicleDevice == "all")
		{
			$allvehicle = 1;
		}

		/* if(!isset($postdata->CompanyId) || $postdata->CompanyId == "all")
		{
			$allcompany = 1;
		} */

		if(!isset($postdata->VehicleDevice) || $postdata->VehicleDevice == "")
		{
			$feature["code"] = 400;
			$feature["msg"] = "Invalid Vehicle Device!";
			$feature["payload"]    = $payload;
			echo json_encode($feature);
			exit;
		}else{
			$check_vehicle = strpos($postdata->VehicleDevice,';');
			$ex_vehicle = explode(";",$postdata->VehicleDevice);
			$UserIDBIB = 4408;

			//jika ada cek dari database nopol (untuk dapat device id)
			$this->db->order_by("vehicle_id","desc");
			if($allvehicle == 0){
				$this->db->where_in("vehicle_device",$ex_vehicle);
			}
			/* if($allcompany == 0){
				$this->db->where("vehicle_company",$postdata->CompanyId);
			} */
			$this->db->where("vehicle_user_id",$UserIDBIB);
			$this->db->where("vehicle_status",1);
			//$this->db->where("vehicle_active_date2 >",$now); //tidak expired
			$q = $this->db->get("vehicle");
			$vehicle = $q->result();

			if($q->num_rows == 0)
			{
				$feature["code"] = 400;
				$feature["msg"] = "Device Not Found!";
				$feature["payload"]    = $payload;
				echo json_encode($feature);
				exit;
			}else{
				$vehicle = $q->result();

				$payload      		    = array(
				  "UserId"          => $postdata->UserId,
				  "VehicleDevice"   	=> $postdata->VehicleDevice
				  //"CompanyId"   	=> $postdata->CompanyId


				);

			}
		}

		//print_r($vehicle);exit();

		//jika mobil lebih dari nol
		if(count($vehicle) > 0)
		{
			$DataToUpload = array();
			//unset($DataToUpload);
			for($i=0;$i<count($vehicle);$i++)
			{

				//printf("ATTR %s \r\n",$vehicle[$z]->vehicle_no);

								$DataToUpload[$i]->VehicleId = $vehicle[$i]->vehicle_id;
								$DataToUpload[$i]->VehicleUserID = $vehicle[$i]->vehicle_user_id;
								$DataToUpload[$i]->VehicleDevice = $vehicle[$i]->vehicle_device;
								$DataToUpload[$i]->VehicleNo = $vehicle[$i]->vehicle_no;
								$DataToUpload[$i]->VehicleNoBackup = $vehicle[$i]->vehicle_no_bk;
								$DataToUpload[$i]->VehicleName = $vehicle[$i]->vehicle_name;

								$DataToUpload[$i]->VehicleCardNo = $vehicle[$i]->vehicle_card_no;
								$DataToUpload[$i]->VehicleOperator = $vehicle[$i]->vehicle_operator;
								$DataToUpload[$i]->VehicleStatus = $vehicle[$i]->vehicle_status;

								$DataToUpload[$i]->VehicleImage = $vehicle[$i]->vehicle_image;
								$DataToUpload[$i]->VehicleCreatedDate = $vehicle[$i]->vehicle_created_date;
								$DataToUpload[$i]->VehicleType = $vehicle[$i]->vehicle_type;

								$DataToUpload[$i]->VehicleCompany = $vehicle[$i]->vehicle_company;
								$DataToUpload[$i]->VehicleSubCompany = $vehicle[$i]->vehicle_subcompany;
								$DataToUpload[$i]->VehicleGroup = $vehicle[$i]->vehicle_group;
								$DataToUpload[$i]->VehicleSubGroup = $vehicle[$i]->vehicle_subgroup;

								$DataToUpload[$i]->VehicleTanggalPasang = $vehicle[$i]->vehicle_tanggal_pasang;
								$DataToUpload[$i]->VehicleImei = $vehicle[$i]->vehicle_imei;
								$DataToUpload[$i]->VehicleMV03 = $vehicle[$i]->vehicle_mv03;
								$DataToUpload[$i]->VehicleSensor = $vehicle[$i]->vehicle_sensor;
								$DataToUpload[$i]->VehicleSOS = $vehicle[$i]->vehicle_sos;

								$DataToUpload[$i]->VehiclePortalRangka = $vehicle[$i]->vehicle_portal_rangka;
								$DataToUpload[$i]->VehiclePortalMesin = $vehicle[$i]->vehicle_portal_mesin;
								$DataToUpload[$i]->VehiclePortalRfidSPI = $vehicle[$i]->vehicle_portal_rfid_spi;
								$DataToUpload[$i]->VehiclePortalRfidWIM = $vehicle[$i]->vehicle_portal_rfid_wim;
								$DataToUpload[$i]->VehiclePortalPortalTare = $vehicle[$i]->vehicle_portal_tare;

								$DataToUpload[$i]->VehiclePortTime = $vehicle[$i]->vehicle_port_time;
								$DataToUpload[$i]->VehiclePortName = $vehicle[$i]->vehicle_port_name;
								$DataToUpload[$i]->VehicleRomTime = $vehicle[$i]->vehicle_rom_time;
								$DataToUpload[$i]->VehicleRomName = $vehicle[$i]->vehicle_rom_name;
								$DataToUpload[$i]->VehicleAutoCheck = json_decode($vehicle[$i]->vehicle_autocheck);

								//$datajson["Data"] = $DataToUpload;




			}
			//$content = json_encode($datajson);
			$content = $DataToUpload;

			//echo $content;
			echo json_encode(array("code" => 200, "msg" => "ok", "data" => $content, "payload" => $payload), JSON_NUMERIC_CHECK);
			$nowendtime = date("Y-m-d H:i:s");
			$this->insertHitAPI("API Master Vehicle Detail",$payload,$nowstarttime,$nowendtime);
			$this->db->close();
			$this->db->cache_delete_all();

		}


		exit;
	}

	function wind_cardinal($degree)
	{

            switch( $degree ) {

                case ( $degree >= 348.75 && $degree <= 360 ):
                    $cardinal = "N";
                break;

                case ( $degree >= 0 && $degree <= 11.249 ):
                    $cardinal = "N";
                break;

                case ( $degree >= 11.25 && $degree <= 33.749 ):
                    $cardinal = "NNE";
                break;

                case ( $degree >= 33.75 && $degree <= 56.249 ):
                    $cardinal = "NE";
                break;

                case ( $degree >= 56.25 && $degree <= 78.749 ):
                    $cardinal = "ENE";
                break;

                case ( $degree >= 78.75 && $degree <= 101.249 ):
                    $cardinal = "E";
                break;

                case ( $degree >= 101.25 && $degree <= 123.749 ):
                    $cardinal = "ESE";
                break;

                case ( $degree >= 123.75 && $degree <= 146.249 ):
                    $cardinal = "SE";
                break;

                case ( $degree >= 146.25 && $degree <= 168.749 ):
                    $cardinal = "N";
                break;

                case ( $degree >= 168.75 && $degree <= 191.249 ):
                    $cardinal = "S";
                break;

                case ( $degree >= 191.25 && $degree <= 213.749 ):
                    $cardinal = "SSW";
                break;

                case ( $degree >= 213.75 && $degree <= 236.249 ):
                    $cardinal = "SW";
                break;

                case ( $degree >= 236.25 && $degree <= 258.749 ):
                    $cardinal = "WSW";
                break;

                case ( $degree >= 258.75 && $degree <= 281.249 ):
                    $cardinal = "W";
                break;

                case ( $degree >= 281.25 && $degree <= 303.749 ):
                    $cardinal = "WNW";
                break;

                case ( $degree >= 303.75 && $degree <= 326.249 ):
                    $cardinal = "NW";
                break;

                case ( $degree >= 326.25 && $degree <= 348.749 ):
                    $cardinal = "NNW";
                break;

                default:
                    $cardinal = null;

            }

           return $cardinal;

    }

}

/* End of file welcome.php */
/* Location: ./system/application/controllers/welcome.php */

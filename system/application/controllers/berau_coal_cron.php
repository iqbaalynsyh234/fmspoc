<?php
include "base.php";

class Berau_coal_cron extends Base {
	function __construct()
	{
		parent::__construct();

		$this->load->model("gpsmodel");
		$this->load->model("configmodel");
		$this->load->model("m_securityevidence");
		$this->load->library('email');
		$this->load->helper('email');
		$this->load->helper('common');

	}

	//EMPLOYEE
	function sync_data_karyawan($codecompany="")
	{
		date_default_timezone_set("Asia/Jakarta");
		$cronstartdate = date("Y-m-d H:i:s");
		print_r("CRON SYNC DATA KARYAWAN CHECK IS START : ". $cronstartdate . "\r\n");
		$nowtime_wita  = date('Y-m-d H:i:s', strtotime($cronstartdate . '+1 hours'));
		print_r("CRON SYNC DATA KARYAWAN CHECK Start WIB          : ". $cronstartdate . "\r\n");
		print_r("CRON SYNC DATA KARYAWAN CHECK Start WITA           : ". $nowtime_wita . "\r\n");

		$data_karyawan_beraucoal = $this->get_master_karyawan_beraucoal($codecompany);
		$data_json               = json_decode($data_karyawan_beraucoal);

		for ($i=0; $i < sizeof($data_json); $i++) {
		  $companyCode = $data_json[$i]->companyCode;
		  $companyId = $data_json[$i]->companyId;
		  $sidCode     = $data_json[$i]->sidCode;
		  $name        = $data_json[$i]->name;
		  $company     = $data_json[$i]->company;
		  $id          = $data_json[$i]->id;
		  $position    = $data_json[$i]->position;

		  $check_data = $this->check_data_karyawan($sidCode, $name);
		  $total_data = sizeof($check_data);

		  if ($total_data < 1) {
			// INSERT JIKA DATA BELUM ADA
			$data_insert = array(
			  "karyawan_bc_companycode" => $companyCode,
			  "karyawan_bc_company_id"  => $companyId,
			  "karyawan_bc_sid"         => $sidCode,
			  "karyawan_bc_name"        => $name,
			  "karyawan_bc_company"     => $company,
			  "karyawan_bc_id_sync"     => $id,
			  "karyawan_bc_position"    => $position
			);

			$insert = $this->insert_data_karyawan($data_insert, "ts_karyawan_beraucoal");
			  if ($insert) {
				printf("===== SUCCESS INSERT DATA \r\n");
			  }else {
				printf("===== FAILED INSERT DATA \r\n");
			  }
		  }else {
			printf("===== SKIP. ALREADY EXISTS \r\n");
		  }

		  // echo "<pre>";
		  // var_dump($total_data);die();
		  // echo "<pre>";
		}

		print_r("CRON START : ". $cronstartdate . "\r\n");
		print_r("CRON FINISH : ". date("Y-m-d H:i:s") . "\r\n");
		$finishtime   = date("Y-m-d H:i:s");
		$start_1      = dbmaketime($cronstartdate);
		$end_1        = dbmaketime($finishtime);
		$duration_sec = $end_1 - $start_1;
		print_r("CRON LATENCY : ". $duration_sec . " Second \r\n");
	}

	function check_data_karyawan($sid, $name)
	{
		$this->dbts = $this->load->database("webtracking_ts", true);
		$this->dbts->select("*");
		$this->dbts->where("karyawan_bc_sid", $sid);
		$this->dbts->where("karyawan_bc_name", $name);
		$result = $this->dbts->get("ts_karyawan_beraucoal")->result_array();
		return $result;
	}

	function insert_data_karyawan($data, $table)
	{
		$this->dbts = $this->load->database("webtracking_ts", true);
		return $this->dbts->insert($table, $data);
	}

	function get_master_karyawan_beraucoal($code)
	{
		//printf("PROSES POST SAMPLE -> REQUEST >> LAST POSITION \r\n");
		// Company Code API Get Master Data Karyawan Berau Coal :
		// ZOQ9Q FAD
		// M5WRZ KDC
		// 0VRHO BUMA
		// SUBOD Ricobana
		// O3FFP MTN

		$apiKey        = "eyJhbGciOiJIUzI1NiJ9.eyJpZEthcnlhd2FuIjo3ODExOCwiaWQiOjYyOTIzLCJlbWFpbCI6ImlsaGFtLnRyaXBvZXRyYUBmdXNpMjQuY29tIiwidXNlcm5hbWUiOiJXNFFUTyJ9.hNbX6fIRq9jwyT2m5wftuF9zjJKVGIb4IUySbCulffg";
		$authorization = "Authorization:".$apiKey;
		$company_code  = $code;
		$url           = "http://beats-dev.beraucoal.co.id/sid2/employeeInfoDms/byCode?code=".$company_code;
		$feature       = array();

    $headers = array(
         'x-api-key: '.$apiKey
    );

    // Send request to Server
    $ch = curl_init($url);
    // To save response in a variable from server, set headers;
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    // Get response
    $response = curl_exec($ch);
    // Decode
    // $result = json_decode($response);
    return $response;
	}

	//MASTER SITE
	function sync_master_site()
	{
		date_default_timezone_set("Asia/Jakarta");
		$cronstartdate = date("Y-m-d H:i:s");
		print_r("CRON SYNC DATA MASTER SITE CHECK IS START : ". $cronstartdate . "\r\n");
		$nowtime_wita  = date('Y-m-d H:i:s', strtotime($cronstartdate . '+1 hours'));
		print_r("CRON SYNC DATA MASTER SITE CHECK Start WIB          : ". $cronstartdate . "\r\n");
		print_r("CRON SYNC DATA MASTER SITE CHECK Start WITA           : ". $nowtime_wita . "\r\n");

		$data = $this->get_master_site();
		$data_json               = json_decode($data);

		for ($i=0; $i < count($data_json); $i++) {
		  $idsync		 	= $data_json[$i]->id;
		  $name 			= $data_json[$i]->name;
		  $shortName     	= $data_json[$i]->shortName;
		  $isActive        	= $data_json[$i]->isActive;
		  $centerLatitude   = $data_json[$i]->centerLatitude;
		  $centerLongitude  = $data_json[$i]->centerLongitude;


		  $check_data = $this->check_master_site($idsync, $name);
		  $total_data = count($check_data);

		  if ($total_data < 1) {
			// INSERT JIKA DATA BELUM ADA
			$data_insert = array(
			  "master_site_id_sync" 	=> $idsync,
			  "master_site_name"  		=> $name,
			  "master_site_shortname"   => $shortName,
			  "master_site_active"     	=> $isActive,
			  "master_site_lat"     	=> $centerLatitude,
			  "master_site_long"     	=> $centerLongitude
			);

			$insert = $this->insert_master_site($data_insert, "ts_bc_master_site");
			  if ($insert) {
				printf("===== SUCCESS INSERT DATA \r\n");
			  }else {
				printf("===== FAILED INSERT DATA \r\n");
			  }
		  }else {
			printf("===== SKIP. ALREADY EXISTS \r\n");
		  }


		}

		print_r("CRON START : ". $cronstartdate . "\r\n");
		print_r("CRON FINISH : ". date("Y-m-d H:i:s") . "\r\n");
		$finishtime   = date("Y-m-d H:i:s");
		$start_1      = dbmaketime($cronstartdate);
		$end_1        = dbmaketime($finishtime);
		$duration_sec = $end_1 - $start_1;
		print_r("CRON LATENCY : ". $duration_sec . " Second \r\n");

	}

	function get_master_site()
	{

		$apiKey        = "eyJhbGciOiJIUzI1NiJ9.eyJpZEthcnlhd2FuIjo3ODExOCwiaWQiOjYyOTIzLCJlbWFpbCI6ImlsaGFtLnRyaXBvZXRyYUBmdXNpMjQuY29tIiwidXNlcm5hbWUiOiJXNFFUTyJ9.hNbX6fIRq9jwyT2m5wftuF9zjJKVGIb4IUySbCulffg";
		$authorization = "Authorization:".$apiKey;
		$url           = "http://beats-dev.beraucoal.co.id/beats2/api/location?filter[isActive]=true&filter[type.id]=100";
		$feature       = array();

		$headers = array(
			 'x-api-key: '.$apiKey
		);

		// Send request to Server
		$ch = curl_init($url);
		// To save response in a variable from server, set headers;
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		// Get response
		$response = curl_exec($ch);
		// Decode
		// $result = json_decode($response);
		return $response;
	}

	function check_master_site($sid, $name)
	{
		$this->dbts = $this->load->database("webtracking_ts", true);
		$this->dbts->select("*");
		$this->dbts->where("master_site_id_sync", $sid);
		$this->dbts->where("master_site_name", $name);
		$result = $this->dbts->get("ts_bc_master_site")->result_array();
		return $result;
	}

	function insert_master_site($data, $table)
	{
		$this->dbts = $this->load->database("webtracking_ts", true);
		return $this->dbts->insert($table, $data);
	}

	//MASTER LOCATION
	function sync_master_location()
	{
		date_default_timezone_set("Asia/Jakarta");
		$cronstartdate = date("Y-m-d H:i:s");
		print_r("CRON SYNC DATA  CHECK IS START : ". $cronstartdate . "\r\n");
		$nowtime_wita  = date('Y-m-d H:i:s', strtotime($cronstartdate . '+1 hours'));
		print_r("CRON SYNC DATA CHECK Start WIB          : ". $cronstartdate . "\r\n");
		print_r("CRON SYNC DATA CHECK Start WITA           : ". $nowtime_wita . "\r\n");

		$data = $this->get_master_location();
		$data_json = json_decode($data);

		for ($i=0; $i < count($data_json); $i++) {

		  $parent 				= $data_json[$i]->parent;
		  $idsync		 		= $data_json[$i]->id;
		  $name 				= $data_json[$i]->name;
		  $shortName 			= $data_json[$i]->shortName;
		  $isActive        		= $data_json[$i]->isActive;
		  $centerLatitude       = $data_json[$i]->centerLatitude;
		  $centerLongitude      = $data_json[$i]->centerLongitude;

		  $parent_id        	= $parent->id;
		  $parent_name       	= $parent->name;
		  $parent_shortname     = $parent->shortName;

		  $check_data = $this->check_master_location($idsync, $name);
		  $total_data = count($check_data);

		  if ($total_data < 1) {
			// INSERT JIKA DATA BELUM ADA
			$data_insert = array(
			  "master_location_id_sync" 			=> $idsync,
			  "master_location_name"  				=> $name,
			  "master_location_short_name"  		=> $shortName,
			  "master_location_active"     			=> $isActive,
			  "master_location_parent_id"      		=> $parent_id,
			  "master_location_parent_name"  		=> $parent_name,
			  "master_location_parent_short_name"  	=> $parent_shortname,
			  "master_location_lat"  				=> $centerLatitude,
			  "master_location_long"			  	=> $centerLongitude

			);

			$insert = $this->insert_master_location($data_insert, "ts_bc_master_location");
			  if ($insert) {
				printf("===== SUCCESS INSERT DATA \r\n");
			  }else {
				printf("===== FAILED INSERT DATA \r\n");
			  }
		  }else {
			printf("===== SKIP. ALREADY EXISTS \r\n");
		  }


		}

		print_r("CRON START : ". $cronstartdate . "\r\n");
		print_r("CRON FINISH : ". date("Y-m-d H:i:s") . "\r\n");
		$finishtime   = date("Y-m-d H:i:s");
		$start_1      = dbmaketime($cronstartdate);
		$end_1        = dbmaketime($finishtime);
		$duration_sec = $end_1 - $start_1;
		print_r("CRON LATENCY : ". $duration_sec . " Second \r\n");

	}

	function get_master_location()
	{

		$apiKey        = "eyJhbGciOiJIUzI1NiJ9.eyJpZEthcnlhd2FuIjo3ODExOCwiaWQiOjYyOTIzLCJlbWFpbCI6ImlsaGFtLnRyaXBvZXRyYUBmdXNpMjQuY29tIiwidXNlcm5hbWUiOiJXNFFUTyJ9.hNbX6fIRq9jwyT2m5wftuF9zjJKVGIb4IUySbCulffg";
		$authorization = "Authorization:".$apiKey;
		$url           = "http://beats-dev.beraucoal.co.id/beats2/api/location?filter[isActive]=true&filter[type.id]=200&expand=parent";
		$feature       = array();

		$headers = array(
			 'x-api-key: '.$apiKey
		);

		// Send request to Server
		$ch = curl_init($url);
		// To save response in a variable from server, set headers;
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		// Get response
		$response = curl_exec($ch);
		// Decode
		// $result = json_decode($response);
		return $response;
	}

	function check_master_location($sid, $name)
	{
		$this->dbts = $this->load->database("webtracking_ts", true);
		$this->dbts->select("*");
		$this->dbts->where("master_location_id_sync", $sid);
		$this->dbts->where("master_location_name", $name);
		$result = $this->dbts->get("ts_bc_master_location")->result_array();
		return $result;
	}

	function insert_master_location($data, $table)
	{
		$this->dbts = $this->load->database("webtracking_ts", true);
		return $this->dbts->insert($table, $data);
	}

	// MASTER Object
	function sync_master_object()
	{
		date_default_timezone_set("Asia/Jakarta");
		$cronstartdate = date("Y-m-d H:i:s");
		print_r("CRON SYNC DATA MASTER SITE CHECK IS START : ". $cronstartdate . "\r\n");
		$nowtime_wita  = date('Y-m-d H:i:s', strtotime($cronstartdate . '+1 hours'));
		print_r("CRON SYNC DATA MASTER SITE CHECK Start WIB          : ". $cronstartdate . "\r\n");
		print_r("CRON SYNC DATA MASTER SITE CHECK Start WITA           : ". $nowtime_wita . "\r\n");

		$data = $this->get_master_object();
		$data_json               = json_decode($data);

		for ($i=0; $i < count($data_json); $i++) {
		  $idsync		 	= $data_json[$i]->id;
		  $name 			= $data_json[$i]->name;
		  $isActive        	= $data_json[$i]->isActive;

		  $check_data = $this->check_master_object($idsync, $name);
		  $total_data = count($check_data);

		  if ($total_data < 1) {
			// INSERT JIKA DATA BELUM ADA
			$data_insert = array(
			  "master_object_id_sync" 		=> $idsync,
			  "master_object_name"  		=> $name,
			  "master_object_active"     	=> $isActive
			);

			$insert = $this->insert_master_object($data_insert, "ts_bc_master_object");
			  if ($insert) {
				printf("===== SUCCESS INSERT DATA \r\n");
			  }else {
				printf("===== FAILED INSERT DATA \r\n");
			  }
		  }else {
			printf("===== SKIP. ALREADY EXISTS \r\n");
		  }


		}

		print_r("CRON START : ". $cronstartdate . "\r\n");
		print_r("CRON FINISH : ". date("Y-m-d H:i:s") . "\r\n");
		$finishtime   = date("Y-m-d H:i:s");
		$start_1      = dbmaketime($cronstartdate);
		$end_1        = dbmaketime($finishtime);
		$duration_sec = $end_1 - $start_1;
		print_r("CRON LATENCY : ". $duration_sec . " Second \r\n");

	}

	function get_master_object()
	{

		$apiKey        = "eyJhbGciOiJIUzI1NiJ9.eyJpZEthcnlhd2FuIjo3ODExOCwiaWQiOjYyOTIzLCJlbWFpbCI6ImlsaGFtLnRyaXBvZXRyYUBmdXNpMjQuY29tIiwidXNlcm5hbWUiOiJXNFFUTyJ9.hNbX6fIRq9jwyT2m5wftuF9zjJKVGIb4IUySbCulffg";
		$authorization = "Authorization:".$apiKey;
		$url           = "http://beats-dev.beraucoal.co.id/beats2/api/hseObject?filter[isActive]=true";
		$feature       = array();

		$headers = array(
			 'x-api-key: '.$apiKey
		);

		// Send request to Server
		$ch = curl_init($url);
		// To save response in a variable from server, set headers;
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		// Get response
		$response = curl_exec($ch);
		// Decode
		// $result = json_decode($response);
		return $response;
	}

	function check_master_object($sid, $name)
	{
		$this->dbts = $this->load->database("webtracking_ts", true);
		$this->dbts->select("*");
		$this->dbts->where("master_object_id_sync", $sid);
		$this->dbts->where("master_object_name", $name);
		$result = $this->dbts->get("ts_bc_master_object")->result_array();
		return $result;
	}

	function insert_master_object($data, $table)
	{
		$this->dbts = $this->load->database("webtracking_ts", true);
		return $this->dbts->insert($table, $data);
	}

	// MASTER Object DETAIL
	function sync_master_objectdetail()
	{
		date_default_timezone_set("Asia/Jakarta");
		$cronstartdate = date("Y-m-d H:i:s");
		print_r("CRON SYNC DATA CHECK IS START : ". $cronstartdate . "\r\n");
		$nowtime_wita  = date('Y-m-d H:i:s', strtotime($cronstartdate . '+1 hours'));
		print_r("CRON SYNC DATA CHECK Start WIB          : ". $cronstartdate . "\r\n");
		print_r("CRON SYNC DATA CHECK Start WITA           : ". $nowtime_wita . "\r\n");

		$data = $this->get_master_objectdetail();
		$data_json               = json_decode($data);

		for ($i=0; $i < count($data_json); $i++)
		{

			$parent = $data_json[$i]->object;
			$idsync		 	= $data_json[$i]->id;
			$name 			= $data_json[$i]->name;
			$isActive       = $data_json[$i]->isActive;
			$parent_id      = $parent->id;
			$parent_name    = $parent->name;

		  $check_data = $this->check_master_objectdetail($idsync, $name);
		  $total_data = count($check_data);

		  if ($total_data < 1) {
			// INSERT JIKA DATA BELUM ADA
			$data_insert = array(
			  "master_object_detail_id_sync" 		=> $idsync,
			  "master_object_detail_parent_id"  	=> $parent_id,
			  "master_object_detail_parent_name"    => $parent_name,
			  "master_object_detail_name"  			=> $name,
			  "master_object_detail_active"     	=> $isActive
			);

			$insert = $this->insert_master_objectdetail($data_insert, "ts_bc_master_object_detail");
			  if ($insert) {
				printf("===== SUCCESS INSERT DATA \r\n");
			  }else {
				printf("===== FAILED INSERT DATA \r\n");
			  }
		  }else {
			printf("===== SKIP. ALREADY EXISTS \r\n");
		  }


		}

		print_r("CRON START : ". $cronstartdate . "\r\n");
		print_r("CRON FINISH : ". date("Y-m-d H:i:s") . "\r\n");
		$finishtime   = date("Y-m-d H:i:s");
		$start_1      = dbmaketime($cronstartdate);
		$end_1        = dbmaketime($finishtime);
		$duration_sec = $end_1 - $start_1;
		print_r("CRON LATENCY : ". $duration_sec . " Second \r\n");

	}

	function get_master_objectdetail()
	{

		$apiKey        = "eyJhbGciOiJIUzI1NiJ9.eyJpZEthcnlhd2FuIjo3ODExOCwiaWQiOjYyOTIzLCJlbWFpbCI6ImlsaGFtLnRyaXBvZXRyYUBmdXNpMjQuY29tIiwidXNlcm5hbWUiOiJXNFFUTyJ9.hNbX6fIRq9jwyT2m5wftuF9zjJKVGIb4IUySbCulffg";
		$authorization = "Authorization:".$apiKey;
		$url           = "http://beats-dev.beraucoal.co.id/beats2/api/hseObjectDetail?filter[isActive]=true&expand=object,dataSource";
		$feature       = array();

		$headers = array(
			 'x-api-key: '.$apiKey
		);

		// Send request to Server
		$ch = curl_init($url);
		// To save response in a variable from server, set headers;
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		// Get response
		$response = curl_exec($ch);
		// Decode
		// $result = json_decode($response);
		return $response;
	}

	function check_master_objectdetail($sid, $name)
	{
		$this->dbts = $this->load->database("webtracking_ts", true);
		$this->dbts->select("*");
		$this->dbts->where("master_object_detail_id_sync", $sid);
		$this->dbts->where("master_object_detail_name", $name);
		$result = $this->dbts->get("ts_bc_master_object_detail")->result_array();
		return $result;
	}

	function insert_master_objectdetail($data, $table)
	{
		$this->dbts = $this->load->database("webtracking_ts", true);
		return $this->dbts->insert($table, $data);
	}

	// MASTER Quick Action
	function sync_master_quickaction()
	{
		date_default_timezone_set("Asia/Jakarta");
		$cronstartdate = date("Y-m-d H:i:s");
		print_r("CRON SYNC DATA MASTER SITE CHECK IS START : ". $cronstartdate . "\r\n");
		$nowtime_wita  = date('Y-m-d H:i:s', strtotime($cronstartdate . '+1 hours'));
		print_r("CRON SYNC DATA MASTER SITE CHECK Start WIB          : ". $cronstartdate . "\r\n");
		print_r("CRON SYNC DATA MASTER SITE CHECK Start WITA           : ". $nowtime_wita . "\r\n");

		$data = $this->get_master_quickaction();
		$data_json               = json_decode($data);

		for ($i=0; $i < count($data_json); $i++) {
		  $idsync		 	= $data_json[$i]->id;
		  $name 			= $data_json[$i]->name;
		  $desc 			= $data_json[$i]->description;
		  $isActive        	= $data_json[$i]->isActive;

		  $check_data = $this->check_master_quickaction($idsync, $name);
		  $total_data = count($check_data);

		  if ($total_data < 1) {
			// INSERT JIKA DATA BELUM ADA
			$data_insert = array(
			  "master_quickaction_id_sync" 		=> $idsync,
			  "master_quickaction_name"  		=> $name,
			  "master_quickaction_desc"  		=> $desc,
			  "master_quickaction_active"     	=> $isActive
			);

			$insert = $this->insert_master_quickaction($data_insert, "ts_bc_master_quickaction");
			  if ($insert) {
				printf("===== SUCCESS INSERT DATA \r\n");
			  }else {
				printf("===== FAILED INSERT DATA \r\n");
			  }
		  }else {
			printf("===== SKIP. ALREADY EXISTS \r\n");
		  }


		}

		print_r("CRON START : ". $cronstartdate . "\r\n");
		print_r("CRON FINISH : ". date("Y-m-d H:i:s") . "\r\n");
		$finishtime   = date("Y-m-d H:i:s");
		$start_1      = dbmaketime($cronstartdate);
		$end_1        = dbmaketime($finishtime);
		$duration_sec = $end_1 - $start_1;
		print_r("CRON LATENCY : ". $duration_sec . " Second \r\n");

	}

	function get_master_quickaction()
	{

		$apiKey        = "eyJhbGciOiJIUzI1NiJ9.eyJpZEthcnlhd2FuIjo3ODExOCwiaWQiOjYyOTIzLCJlbWFpbCI6ImlsaGFtLnRyaXBvZXRyYUBmdXNpMjQuY29tIiwidXNlcm5hbWUiOiJXNFFUTyJ9.hNbX6fIRq9jwyT2m5wftuF9zjJKVGIb4IUySbCulffg";
		$authorization = "Authorization:".$apiKey;
		$url           = "http://beats-dev.beraucoal.co.id/beats2/api/quickAction?filter[isActive]=true";
		$feature       = array();

		$headers = array(
			 'x-api-key: '.$apiKey
		);

		// Send request to Server
		$ch = curl_init($url);
		// To save response in a variable from server, set headers;
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		// Get response
		$response = curl_exec($ch);
		// Decode
		// $result = json_decode($response);
		return $response;
	}

	function check_master_quickaction($sid, $name)
	{
		$this->dbts = $this->load->database("webtracking_ts", true);
		$this->dbts->select("*");
		$this->dbts->where("master_quickaction_id_sync", $sid);
		$this->dbts->where("master_quickaction_name", $name);
		$result = $this->dbts->get("ts_bc_master_quickaction")->result_array();
		return $result;
	}

	function insert_master_quickaction($data, $table)
	{
		$this->dbts = $this->load->database("webtracking_ts", true);
		return $this->dbts->insert($table, $data);
	}

	// MASTER CATEGORI TYPE
	function sync_master_categorytype()
	{
		date_default_timezone_set("Asia/Jakarta");
		$cronstartdate = date("Y-m-d H:i:s");
		print_r("CRON SYNC DATA CHECK IS START : ". $cronstartdate . "\r\n");
		$nowtime_wita  = date('Y-m-d H:i:s', strtotime($cronstartdate . '+1 hours'));
		print_r("CRON SYNC DATA CHECK Start WIB          : ". $cronstartdate . "\r\n");
		print_r("CRON SYNC DATA CHECK Start WITA           : ". $nowtime_wita . "\r\n");

		$data = $this->get_master_categorytype();
		$data_json               = json_decode($data);

		for ($i=0; $i < count($data_json); $i++)
		{
		  $idsync		 	= $data_json[$i]->id;
		  $name 			= $data_json[$i]->name;
		  $desc 			= $data_json[$i]->description;
		  $isActive        	= $data_json[$i]->isActive;
		  $isClosed        	= $data_json[$i]->isClosed;

		  $check_data = $this->check_master_categorytype($idsync, $name);
		  $total_data = count($check_data);

		  if ($total_data < 1) {
			// INSERT JIKA DATA BELUM ADA
			$data_insert = array(
			  "master_categorytype_id_sync" 	=> $idsync,
			  "master_categorytype_name"  		=> $name,
			  "master_categorytype_desc"  		=> $desc,
			  "master_categorytype_active"     	=> $isActive,
			  "master_categorytype_closed"     	=> $isClosed,
			);

			$insert = $this->insert_master_categorytype($data_insert, "ts_bc_master_categorytype");
			  if ($insert) {
				printf("===== SUCCESS INSERT DATA \r\n");
			  }else {
				printf("===== FAILED INSERT DATA \r\n");
			  }
		  }else {
			printf("===== SKIP. ALREADY EXISTS \r\n");
		  }


		}

		print_r("CRON START : ". $cronstartdate . "\r\n");
		print_r("CRON FINISH : ". date("Y-m-d H:i:s") . "\r\n");
		$finishtime   = date("Y-m-d H:i:s");
		$start_1      = dbmaketime($cronstartdate);
		$end_1        = dbmaketime($finishtime);
		$duration_sec = $end_1 - $start_1;
		print_r("CRON LATENCY : ". $duration_sec . " Second \r\n");

	}

	function get_master_categorytype()
	{

		$apiKey        = "eyJhbGciOiJIUzI1NiJ9.eyJpZEthcnlhd2FuIjo3ODExOCwiaWQiOjYyOTIzLCJlbWFpbCI6ImlsaGFtLnRyaXBvZXRyYUBmdXNpMjQuY29tIiwidXNlcm5hbWUiOiJXNFFUTyJ9.hNbX6fIRq9jwyT2m5wftuF9zjJKVGIb4IUySbCulffg";
		$authorization = "Authorization:".$apiKey;
		$url           = "http://beats-dev.beraucoal.co.id/beats2/api/categoryType";
		$feature       = array();

		$headers = array(
			 'x-api-key: '.$apiKey
		);

		// Send request to Server
		$ch = curl_init($url);
		// To save response in a variable from server, set headers;
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		// Get response
		$response = curl_exec($ch);
		// Decode
		// $result = json_decode($response);
		return $response;
	}

	function check_master_categorytype($sid, $name)
	{
		$this->dbts = $this->load->database("webtracking_ts", true);
		$this->dbts->select("*");
		$this->dbts->where("master_categorytype_id_sync", $sid);
		$this->dbts->where("master_categorytype_name", $name);
		$result = $this->dbts->get("ts_bc_master_categorytype")->result_array();
		return $result;
	}

	function insert_master_categorytype($data, $table)
	{
		$this->dbts = $this->load->database("webtracking_ts", true);
		return $this->dbts->insert($table, $data);
	}

	//MASTER PJA
	function sync_master_pja()
	{
		date_default_timezone_set("Asia/Jakarta");
		$cronstartdate = date("Y-m-d H:i:s");
		print_r("CRON SYNC DATA  CHECK IS START : ". $cronstartdate . "\r\n");
		$nowtime_wita  = date('Y-m-d H:i:s', strtotime($cronstartdate . '+1 hours'));
		print_r("CRON SYNC DATA CHECK Start WIB          : ". $cronstartdate . "\r\n");
		print_r("CRON SYNC DATA CHECK Start WITA           : ". $nowtime_wita . "\r\n");

		$data = $this->get_master_pja();
		$data_json = json_decode($data);

		for ($i=0; $i < count($data_json); $i++) {

		  $parent_location		= $data_json[$i]->location;
		  $parent_type			= $data_json[$i]->pjaType;

		  $idsync		 		= $data_json[$i]->id;
		  $name 				= $data_json[$i]->name;
		  $isActive        		= $data_json[$i]->isActive;

		  $parent_location_id   		= $parent_location->id;
		  $parent_location_name       	= $parent_location->name;
		  $parent_location_short_name   = $parent_location->shortName;
		  $parent_location_lat       	= $parent_location->centerLatitude;
		  $parent_location_long  		= $parent_location->centerLongitude;

		  $parent_type_id   		= $parent_type->id;
		  $parent_type_name       	= $parent_type->name;
		  $parent_type_desc   		= $parent_type->description;

		  $check_data = $this->check_master_pja($idsync, $name);
		  $total_data = count($check_data);

		  if ($total_data < 1) {
			// INSERT JIKA DATA BELUM ADA
			$data_insert = array(
			  "master_pja_id_sync" 				=> $idsync,
			  "master_pja_name"  				=> $name,
			  "master_pja_active"     			=> $isActive,
			  "master_pja_location_id"      	=> $parent_location_id,
			  "master_pja_location_name"  		=> $parent_location_name,
			  "master_pja_location_short_name"  => $parent_location_short_name,
			  "master_pja_location_lat"  		=> $parent_location_lat,
			  "master_pja_location_long"  		=> $parent_location_long,
			  "master_pja_type_id"  				=> $parent_type_id,
			  "master_pja_type_name"			  	=> $parent_type_name,
			  "master_pja_type_desc"			  	=> $parent_type_desc


			);

			$insert = $this->insert_master_location($data_insert, "ts_bc_master_pja");
			  if ($insert) {
				printf("===== SUCCESS INSERT DATA \r\n");
			  }else {
				printf("===== FAILED INSERT DATA \r\n");
			  }
		  }else {
			printf("===== SKIP. ALREADY EXISTS \r\n");
		  }


		}

		print_r("CRON START : ". $cronstartdate . "\r\n");
		print_r("CRON FINISH : ". date("Y-m-d H:i:s") . "\r\n");
		$finishtime   = date("Y-m-d H:i:s");
		$start_1      = dbmaketime($cronstartdate);
		$end_1        = dbmaketime($finishtime);
		$duration_sec = $end_1 - $start_1;
		print_r("CRON LATENCY : ". $duration_sec . " Second \r\n");

	}

	function get_master_pja()
	{

		$apiKey        = "eyJhbGciOiJIUzI1NiJ9.eyJpZEthcnlhd2FuIjo3ODExOCwiaWQiOjYyOTIzLCJlbWFpbCI6ImlsaGFtLnRyaXBvZXRyYUBmdXNpMjQuY29tIiwidXNlcm5hbWUiOiJXNFFUTyJ9.hNbX6fIRq9jwyT2m5wftuF9zjJKVGIb4IUySbCulffg";
		$authorization = "Authorization:".$apiKey;
		$url           = "http://beats-dev.beraucoal.co.id/beats2/api/pja?filter[isActive]=true&include=id,isActive,name,location,pjaType";
		$feature       = array();

		$headers = array(
			 'x-api-key: '.$apiKey
		);

		// Send request to Server
		$ch = curl_init($url);
		// To save response in a variable from server, set headers;
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		// Get response
		$response = curl_exec($ch);
		// Decode
		// $result = json_decode($response);
		return $response;
	}

	function check_master_pja($sid, $name)
	{
		$this->dbts = $this->load->database("webtracking_ts", true);
		$this->dbts->select("*");
		$this->dbts->where("master_pja_id_sync", $sid);
		$this->dbts->where("master_pja_name", $name);
		$result = $this->dbts->get("ts_bc_master_pja")->result_array();
		return $result;
	}

	function insert_master_pja($data, $table)
	{
		$this->dbts = $this->load->database("webtracking_ts", true);
		return $this->dbts->insert($table, $data);
	}

	// FOR HAZARD SEND START
	function hazard_send(){
		date_default_timezone_set("Asia/Jakarta");
		$cronstartdate = date("Y-m-d H:i:s");
		print_r("CRON HAZARD SEND IS START : ". $cronstartdate . "\r\n");
		$nowtime_wita  = date('Y-m-d H:i:s', strtotime($cronstartdate . '+1 hours'));
		print_r("CRON HAZARD SEND Start WIB : ". $cronstartdate . "\r\n");
		print_r("CRON HAZARD SEND Start WITA : ". $nowtime_wita . "\r\n");


		$date_for_testing = "2023-07-01";
		$m1               = date("F", strtotime($date_for_testing));
		$year             = date("Y", strtotime($date_for_testing));
		$reportoverspeed  = "overspeed_hour_";
		$dbtable          = "";
		$dbtableoverspeed = "";

		switch ($m1)
		{
			case "January":
						$dbtableoverspeed = $reportoverspeed."januari_".$year;
			break;
			case "February":
						$dbtableoverspeed = $reportoverspeed."februari_".$year;
			break;
			case "March":
						$dbtableoverspeed = $reportoverspeed."maret_".$year;
			break;
			case "April":
						$dbtableoverspeed = $reportoverspeed."april_".$year;
			break;
			case "May":
						$dbtableoverspeed = $reportoverspeed."mei_".$year;
			break;
			case "June":
						$dbtableoverspeed = $reportoverspeed."juni_".$year;
			break;
			case "July":
						$dbtableoverspeed = $reportoverspeed."juli_".$year;
			break;
			case "August":
						$dbtableoverspeed = $reportoverspeed."agustus_".$year;
			break;
			case "September":
						$dbtableoverspeed = $reportoverspeed."september_".$year;
			break;
			case "October":
						$dbtableoverspeed = $reportoverspeed."oktober_".$year;
			break;
			case "November":
						$dbtableoverspeed = $reportoverspeed."november_".$year;
			break;
			case "December":
						$dbtableoverspeed = $reportoverspeed."desember_".$year;
			break;
		}

		$data_evidence        = $this->get_overspeed_for_hazardsend($dbtableoverspeed);


		if (sizeof($data_evidence) > 0) {
			$image = "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";
			$deskripsi_1 = "Test Deskripsi 1";
			$deskripsi_2 = "Test Deskripsi 2";
			// DATA ADA, LAKUKAN SEND
			for ($i=0; $i < sizeof($data_evidence); $i++) {
				$id_perusahaan    = $data_evidence[$i]['overspeed_report_id_perusahaan'];
				$id_site 			    = $data_evidence[$i]['overspeed_report_master_site'];
				$deskripsi			  = $data_evidence[$i]['overspeed_report_deskripsi'];
				$id_object        = $data_evidence[$i]['overspeed_report_id_object'];
				$id_objectdetail  = $data_evidence[$i]['overspeed_report_id_objectdetail'];
				$id_quick_action  = $data_evidence[$i]['overspeed_report_id_quick_action'];
				$id_lokasi        = $data_evidence[$i]['overspeed_report_id_lokasi'];
				$id_lokasi_detail = $data_evidence[$i]['overspeed_report_id_lokasi_detail'];
				$report_location  = $data_evidence[$i]['overspeed_report_location'];
				$mobileUUID       = null;
				$id_id_up        = explode("|", $data_evidence[$i]['overspeed_report_supervisor_cr']);
				$id_id_cr        = $data_evidence[$i]['overspeed_report_id_cr'];
				$id_master_site   = $data_evidence[$i]['overspeed_report_master_site'];
				$idOakRegister    = null;
				$gps_time         = $data_evidence[$i]['overspeed_report_gps_time'];
				$id_kategori      = $data_evidence[$i]['overspeed_report_id_kategori'];
				$goldenrule       = $data_evidence[$i]['overspeed_report_goldenrule'];
				$id_pja           = $data_evidence[$i]['overspeed_report_id_pja'];
				$id_pja_child     = $data_evidence[$i]['overspeed_report_id_pja_child'];
				$coordinate       = explode(",", $data_evidence[$i]['overspeed_report_coordinate']);

				$data_for_sent = array(
					// "idPerusahaan" => $id_perusahaan,
					"idPerusahaan" => 5384,
					"ffr" => array(
							"image"       => $image,
							"deskripsi"   => $deskripsi_1,
							"description" => $deskripsi_2
						),
						"deskripsi"         => $deskripsi,
						"idObyek"           => $id_object,
						"idObyekDetil"      => $id_objectdetail,
						"idPja"             => $id_pja,
						"idPjaChild"        => $id_pja_child,
						"idQuickAction"     => $id_quick_action,
						"idLokasi"          => $id_lokasi,
						"idLokasiDetail"    => $id_pja_child,
						"ketLokasi"         => $report_location,
						"mobileUUID"        => $mobileUUID,
						"idPic"             => $id_id_up[0],
						"idPelapor"         => $id_id_cr,
						"idSite"            => $id_site,
						"idOakRegister"     => $idOakRegister,
						"createDate"        => $gps_time,
						"idKategori"        => $id_kategori,
						"idGoldenRule"      => $goldenrule,
						"locationLatitude"  => $coordinate[0],
						"locationLongitude" => $coordinate[1]
				);

				$submit_this_hazard = $this->submit_hazard($data_for_sent);

				// echo "<pre>";
				// var_dump($submit_this_hazard);die();
				// echo "<pre>";

				if ($submit_this_hazard->result == true) {
					print_r("SUCCESS SUBMIT HAZARD r\n");
					print_r($submit_this_hazard->message . " \r\n");
					$data_update_status = array(
						"overspeed_report_status_sendhazard" => 1
					);
					$update = $this->update_status_sendhazard($data_evidence[$i]['overspeed_report_id'], $data_update_status, $dbtableoverspeed);
						if ($update) {
							print_r("SUCCESS UPDATE STATUS SEND HAZARD \r\n");
						}
				}else {
					print_r("FAILED SUBMIT HAZARD \r\n");
				}
			}
		}else {
			print_r("DATA TRUE ALERT TIDAK DITEMUKAN \r\n");
		}

		print_r("CRON START : ". $cronstartdate . "\r\n");
		print_r("CRON FINISH : ". date("Y-m-d H:i:s") . "\r\n");
		$finishtime   = date("Y-m-d H:i:s");
		$start_1      = dbmaketime($cronstartdate);
		$end_1        = dbmaketime($finishtime);
		$duration_sec = $end_1 - $start_1;
		print_r("CRON LATENCY : ". $duration_sec . " Second \r\n");

	}

	function update_status_sendhazard($alertid, $data, $table){
		$this->dbtensor = $this->load->database("tensor_report", true);
    $this->dbtensor->where("overspeed_report_id", $alertid);
		return $this->dbtensor->update($table, $data);
  }

	function submit_hazard($dataforsent){
		$url_submit = "http://beats-dev.beraucoal.co.id/beats/mobile/input/hazard";
		$token 		  = "eyJhbGciOiJIUzI1NiJ9.eyJpZEthcnlhd2FuIjo0Mzg4NCwiaWQiOjIsImVtYWlsIjoiYXJpZi53aWR5YUBiZXJhdWNvYWwuY28uaWQiLCJ1c2VybmFtZSI6IkxTREVWIn0.ZgYBPYZgx5CdJAMm29T6_0Es5C199PULqOfwMwdGFz8";
		$data_param = json_encode($dataforsent, JSON_NUMERIC_CHECK);
		$data_decode = json_decode($data_param);
		// echo "<pre>";
		// var_dump($data_decode);die();
		// echo "<pre>";

		$datajson = '{
				"idPerusahaan": '.$data_decode->idPerusahaan.',
				"ffr": [
					{
						"image": "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",
						"deskripsi" : "'.strval($data_decode->ffr->deskripsi).'",
						"description" : "'.strval($data_decode->ffr->description).'"
					}
				],
				"deskripsi": "'.strval($data_decode->deskripsi).'",
				"idObyek": '.$data_decode->idObyek.',
				"idObyekDetil": '.$data_decode->idObyekDetil.',
				"idPja": '.$data_decode->idPja.',
				"idPjaChild": '.$data_decode->idPjaChild.',
				"idQuickAction": '.$data_decode->idQuickAction.',
				"idLokasi": '.$data_decode->idLokasi.',
				"ketLokasi": "'.strval($data_decode->ketLokasi).'",
				"mobileUUID": null,
				"idPic": 69313,
				"idPelapor": 69303,
				"idOakRegister" : null,
				"createDate" : "'.$data_decode->createDate.'",
				"idKategori" : "'.strval($data_decode->idKategori).'",
				"idGoldenRule" : null,
				"locationLatitude" : "'.strval($data_decode->locationLatitude).'",
				"locationLongitude" : "'.strval($data_decode->locationLongitude).'"
			}';
			// "idPic": '.$data_decode->idPic.',
			// "idPelapor": '.$data_decode->idPelapor.',
			// "idLokasiDetail": '.$data_decode->idLokasiDetail.', // ini save dulu siapa tau dibutuhin nanti
			// "idSite": '.$data_decode->idSite.', // ini save dulu siapa tau dibutuhin nanti



			print_r($datajson." \r\n");

			// echo "<pre>";
			// var_dump("MASUK");die();
			// echo "<pre>";


		$ch = curl_init($url_submit);
		$headers = array(
					 "Accept: application/json",
					 "Content-Type: application/json",
					 "x-api-key:".$token,
				);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $datajson);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		$result     = curl_exec($ch);
    $curl_errno = curl_errno($ch);
    $curl_error = curl_error($ch);

		// echo "<pre>";
		// var_dump($result);die();
		// echo "<pre>";

		if ($result === FALSE) {
				die("Submit Hazard failed: " . curL_error($ch). " \r\n");
		}

		curl_close($ch);

		$obj = json_decode($result);
		print_r($obj); //exit();
		print_r(" \r\n");
		return $obj;
		// return "";
	}

	function get_overspeed_for_hazardsend($table){
		$this->dbtensor = $this->load->database("tensor_report", true);
    $this->dbtensor->select("*");
    $this->dbtensor->where("overspeed_report_status_sendhazard", 0);
    $this->dbtensor->where("overspeed_report_statusintervention_cr", 1);
    $this->dbtensor->where("overspeed_report_truefalse_up", 1);
    $this->dbtensor->order_by("overspeed_report_gps_time", "DESC");
    $q        = $this->dbtensor->get($table);
    return  $q->result_array();
	}
	// FOR HAZARD SEND END

}

<?php
include "base.php";

class Beats extends Base {

	function __construct()
	{
		parent::Base();
		$this->load->helper('common_helper');
		$this->load->model("dashboardmodel");
		$this->load->model("log_model");
		$this->load->helper('common');
	}

	function index()
	{
		redirect(base_url());
	}

	function employee()
	{
		$user_id             = $this->sess->user_id;
		$user_parent         = $this->sess->user_parent;
		$privilegecode 		 = $this->sess->user_id_role;

		$rows_branch         = $this->get_employee();

		$this->params["data"]              = $rows_branch;
		$this->params['code_view_menu']    = "masterdata";
		$this->params['code_view_submenu'] = "employee";
		$this->params['privilegecode']     = $privilegecode;

		$this->params["header"]         = $this->load->view('newdashboard/partial/headernew', $this->params, true);
		$this->params["chatsidebar"]    = $this->load->view('newdashboard/partial/chatsidebar', $this->params, true);

		if ($privilegecode == 1) {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_superuser', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_employee', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_superuser", $this->params);
		}elseif ($privilegecode == 2) {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_managementuser', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_employee', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_managementuser", $this->params);
		}elseif ($privilegecode == 3) {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_reguleruser', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_employee', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_reguleruser", $this->params);
		}elseif ($privilegecode == 4) {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_teknikaluser', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_employee', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_teknikaluser", $this->params);
		}else {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_employee', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_new", $this->params);
		}
	}
	
	function site()
	{
		$user_id             = $this->sess->user_id;
		$user_parent         = $this->sess->user_parent;
		$privilegecode 		 = $this->sess->user_id_role;

		$rows_branch         = $this->get_site();

		$this->params["data"]              = $rows_branch;
		$this->params['code_view_menu']    = "masterdata";
		$this->params['code_view_submenu'] = "site";
		$this->params['privilegecode']     = $privilegecode;

		$this->params["header"]         = $this->load->view('newdashboard/partial/headernew', $this->params, true);
		$this->params["chatsidebar"]    = $this->load->view('newdashboard/partial/chatsidebar', $this->params, true);

		if ($privilegecode == 1) {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_superuser', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_site', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_superuser", $this->params);
		}elseif ($privilegecode == 2) {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_managementuser', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_site', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_managementuser", $this->params);
		}elseif ($privilegecode == 3) {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_reguleruser', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_site', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_reguleruser", $this->params);
		}elseif ($privilegecode == 4) {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_teknikaluser', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_site', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_teknikaluser", $this->params);
		}else {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_site', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_new", $this->params);
		}
	}
	
	function location()
	{
		$user_id             = $this->sess->user_id;
		$user_parent         = $this->sess->user_parent;
		$privilegecode 		 = $this->sess->user_id_role;

		$rows_branch         = $this->get_location();

		$this->params["data"]              = $rows_branch;
		$this->params['code_view_menu']    = "masterdata";
		$this->params['code_view_submenu'] = "location";
		$this->params['privilegecode']     = $privilegecode;

		$this->params["header"]         = $this->load->view('newdashboard/partial/headernew', $this->params, true);
		$this->params["chatsidebar"]    = $this->load->view('newdashboard/partial/chatsidebar', $this->params, true);

		if ($privilegecode == 1) {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_superuser', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_location', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_superuser", $this->params);
		}elseif ($privilegecode == 2) {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_managementuser', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_location', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_managementuser", $this->params);
		}elseif ($privilegecode == 3) {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_reguleruser', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_location', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_reguleruser", $this->params);
		}elseif ($privilegecode == 4) {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_teknikaluser', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_location', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_teknikaluser", $this->params);
		}else {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_location', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_new", $this->params);
		}
	}
	
	function object()
	{
		$user_id             = $this->sess->user_id;
		$user_parent         = $this->sess->user_parent;
		$privilegecode 		 = $this->sess->user_id_role;

		$rows_branch         = $this->get_object();

		$this->params["data"]              = $rows_branch;
		$this->params['code_view_menu']    = "masterdata";
		$this->params['code_view_submenu'] = "object";
		$this->params['privilegecode']     = $privilegecode;

		$this->params["header"]         = $this->load->view('newdashboard/partial/headernew', $this->params, true);
		$this->params["chatsidebar"]    = $this->load->view('newdashboard/partial/chatsidebar', $this->params, true);

		if ($privilegecode == 1) {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_superuser', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_object', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_superuser", $this->params);
		}elseif ($privilegecode == 2) {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_managementuser', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_object', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_managementuser", $this->params);
		}elseif ($privilegecode == 3) {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_reguleruser', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_object', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_reguleruser", $this->params);
		}elseif ($privilegecode == 4) {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_teknikaluser', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_object', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_teknikaluser", $this->params);
		}else {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_object', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_new", $this->params);
		}
	}
	
	function objectdetail()
	{
		$user_id             = $this->sess->user_id;
		$user_parent         = $this->sess->user_parent;
		$privilegecode 		 = $this->sess->user_id_role;

		$rows_branch         = $this->get_object_detail();

		$this->params["data"]              = $rows_branch;
		$this->params['code_view_menu']    = "masterdata";
		$this->params['code_view_submenu'] = "objectdetail";
		$this->params['privilegecode']     = $privilegecode;

		$this->params["header"]         = $this->load->view('newdashboard/partial/headernew', $this->params, true);
		$this->params["chatsidebar"]    = $this->load->view('newdashboard/partial/chatsidebar', $this->params, true);

		if ($privilegecode == 1) {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_superuser', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_object_detail', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_superuser", $this->params);
		}elseif ($privilegecode == 2) {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_managementuser', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_object_detail', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_managementuser", $this->params);
		}elseif ($privilegecode == 3) {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_reguleruser', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_object_detail', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_reguleruser", $this->params);
		}elseif ($privilegecode == 4) {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_teknikaluser', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_object_detail', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_teknikaluser", $this->params);
		}else {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_object_detail', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_new", $this->params);
		}
	}
	
	function quickaction()
	{
		$user_id             = $this->sess->user_id;
		$user_parent         = $this->sess->user_parent;
		$privilegecode 		 = $this->sess->user_id_role;

		$rows_branch         = $this->get_quick_action();

		$this->params["data"]              = $rows_branch;
		$this->params['code_view_menu']    = "masterdata";
		$this->params['code_view_submenu'] = "objectdetail";
		$this->params['privilegecode']     = $privilegecode;

		$this->params["header"]         = $this->load->view('newdashboard/partial/headernew', $this->params, true);
		$this->params["chatsidebar"]    = $this->load->view('newdashboard/partial/chatsidebar', $this->params, true);

		if ($privilegecode == 1) {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_superuser', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_quick_action', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_superuser", $this->params);
		}elseif ($privilegecode == 2) {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_managementuser', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_quick_action', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_managementuser", $this->params);
		}elseif ($privilegecode == 3) {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_reguleruser', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_quick_action', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_reguleruser", $this->params);
		}elseif ($privilegecode == 4) {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_teknikaluser', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_quick_action', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_teknikaluser", $this->params);
		}else {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_quick_action', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_new", $this->params);
		}
	}
	
	function categorytype()
	{
		$user_id             = $this->sess->user_id;
		$user_parent         = $this->sess->user_parent;
		$privilegecode 		 = $this->sess->user_id_role;

		$rows_branch         = $this->get_category_type();

		$this->params["data"]              = $rows_branch;
		$this->params['code_view_menu']    = "masterdata";
		$this->params['code_view_submenu'] = "objectdetail";
		$this->params['privilegecode']     = $privilegecode;

		$this->params["header"]         = $this->load->view('newdashboard/partial/headernew', $this->params, true);
		$this->params["chatsidebar"]    = $this->load->view('newdashboard/partial/chatsidebar', $this->params, true);

		if ($privilegecode == 1) {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_superuser', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_category_type', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_superuser", $this->params);
		}elseif ($privilegecode == 2) {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_managementuser', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_category_type', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_managementuser", $this->params);
		}elseif ($privilegecode == 3) {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_reguleruser', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_category_type', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_reguleruser", $this->params);
		}elseif ($privilegecode == 4) {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_teknikaluser', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_category_type', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_teknikaluser", $this->params);
		}else {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_category_type', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_new", $this->params);
		}
	}
	
	function pja()
	{
		$user_id             = $this->sess->user_id;
		$user_parent         = $this->sess->user_parent;
		$privilegecode 		 = $this->sess->user_id_role;

		$rows_branch         = $this->get_pja();

		$this->params["data"]              = $rows_branch;
		$this->params['code_view_menu']    = "masterdata";
		$this->params['code_view_submenu'] = "objectdetail";
		$this->params['privilegecode']     = $privilegecode;

		$this->params["header"]         = $this->load->view('newdashboard/partial/headernew', $this->params, true);
		$this->params["chatsidebar"]    = $this->load->view('newdashboard/partial/chatsidebar', $this->params, true);

		if ($privilegecode == 1) {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_superuser', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_pja', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_superuser", $this->params);
		}elseif ($privilegecode == 2) {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_managementuser', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_pja', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_managementuser", $this->params);
		}elseif ($privilegecode == 3) {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_reguleruser', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_pja', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_reguleruser", $this->params);
		}elseif ($privilegecode == 4) {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar_teknikaluser', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_pja', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_teknikaluser", $this->params);
		}else {
			$this->params["sidebar"]        = $this->load->view('newdashboard/partial/sidebar', $this->params, true);
			$this->params["content"]        = $this->load->view('newdashboard/beats/v_pja', $this->params, true);
			$this->load->view("newdashboard/partial/template_dashboard_new", $this->params);
		}
	}
	
	function get_employee()
	{
		$this->dbts = $this->load->database('webtracking_ts', true);
		$this->dbts->select("*");
		$this->dbts->from("ts_karyawan_beraucoal");
		$qbranch     = $this->dbts->get();
		$rows_branch = $qbranch->result();
		return $rows_branch;
	}
	
	function get_site()
	{
		$this->dbts = $this->load->database('webtracking_ts', true);
		$this->dbts->select("*");
		$this->dbts->from("ts_bc_master_site");
		$qbranch     = $this->dbts->get();
		$rows_branch = $qbranch->result();
		return $rows_branch;
	}
	
	function get_location()
	{
		$this->dbts = $this->load->database('webtracking_ts', true);
		$this->dbts->select("*");
		$this->dbts->from("ts_bc_master_location");
		$qbranch     = $this->dbts->get();
		$rows_branch = $qbranch->result();
		return $rows_branch;
	}
	
	function get_object()
	{
		$this->dbts = $this->load->database('webtracking_ts', true);
		$this->dbts->select("*");
		$this->dbts->from("ts_bc_master_object");
		$qbranch     = $this->dbts->get();
		$rows_branch = $qbranch->result();
		return $rows_branch;
	}
	
	function get_object_detail()
	{
		$this->dbts = $this->load->database('webtracking_ts', true);
		$this->dbts->select("*");
		$this->dbts->from("ts_bc_master_object_detail");
		$qbranch     = $this->dbts->get();
		$rows_branch = $qbranch->result();
		return $rows_branch;
	}
	
	function get_pja()
	{
		$this->dbts = $this->load->database('webtracking_ts', true);
		$this->dbts->select("*");
		$this->dbts->from("ts_bc_master_pja");
		$qbranch     = $this->dbts->get();
		$rows_branch = $qbranch->result();
		return $rows_branch;
	}
	
	function get_quick_action()
	{
		$this->dbts = $this->load->database('webtracking_ts', true);
		$this->dbts->select("*");
		$this->dbts->from("ts_bc_master_quickaction");
		$qbranch     = $this->dbts->get();
		$rows_branch = $qbranch->result();
		return $rows_branch;
	}
	
	function get_category_type()
	{
		$this->dbts = $this->load->database('webtracking_ts', true);
		$this->dbts->select("*");
		$this->dbts->from("ts_bc_master_categorytype");
		$qbranch     = $this->dbts->get();
		$rows_branch = $qbranch->result();
		return $rows_branch;
	}

}

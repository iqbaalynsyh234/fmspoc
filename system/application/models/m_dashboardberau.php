<?php
class M_dashboardberau extends Model{

  function getdevice(){
    $user_level      = $this->sess->user_level;
    $user_company    = $this->sess->user_company;
    $user_subcompany = $this->sess->user_subcompany;
    $user_group      = $this->sess->user_group;
    $user_subgroup   = $this->sess->user_subgroup;
    $user_parent     = $this->sess->user_parent;
    $user_id_role    = $this->sess->user_id_role;
    $privilegecode   = $this->sess->user_id_role;
    $user_id         = $this->sess->user_id;
    $user_id_fix     = "";

    if($user_id == "1445"){
      $user_id_fix = $user_id;
    }else{
      $user_id_fix = $this->sess->user_id;
    }

    //GET DATA FROM DB
    $this->db     = $this->load->database("default", true);
    $this->db->select("*");
    $this->db->order_by("vehicle_no","asc");

    if($privilegecode == 0){
      $this->db->where("vehicle_user_id", $user_id_fix);
    }else if($privilegecode == 1){
      $this->db->where("vehicle_user_id", $user_parent);
    }else if($privilegecode == 2){
      $this->db->where("vehicle_user_id", $user_parent);
    }else if($privilegecode == 3){
      $this->db->where("vehicle_user_id", $user_parent);
    }else if($privilegecode == 4){
      $this->db->where("vehicle_user_id", $user_parent);
    }else if($privilegecode == 5){
      $this->db->where("vehicle_company", $user_company);
    }else if($privilegecode == 6){
      $this->db->where("vehicle_company", $user_company);
    }else{
      $this->db->where("vehicle_no",99999);
    }

    $this->db->where("vehicle_mv03 !=", "0000");
    // $this->db->where_in("vehicle_type", array("MV03"));
    $this->db->where("vehicle_status <>", 3);
    $q       = $this->db->get("vehicle");
    return  $q->result_array();
  }

  function getalarmmaster(){
    $this->dbalarm = $this->load->database("webtracking_ts", true);
    $this->dbalarm->select("*");
    $this->dbalarm->where("alarmmaster_flag", 0);
    $this->dbalarm->order_by("alarmmaster_name","asc");
    $q        = $this->dbalarm->get("webtracking_ts_alarmmaster");
    return  $q->result_array();
  }

  function getalarmsubcategory($id){
    $this->dbalarm = $this->load->database("webtracking_kalimantan", true);
      if ($id != "All") {
        $this->dbalarm->where("webtracking_alarmsubcategory_categoryid", $id);
        $this->dbalarm->where("webtracking_alarmsubcategory_flag", 1);
      }else {
        $this->dbalarm->where("webtracking_alarmsubcategory_flag", 1);
      }
    $this->dbalarm->order_by("webtracking_alarmsubcategory_name","asc");
    $q        = $this->dbalarm->get("webtracking_ts_alarmsubcategory");
    return  $q->result_array();
  }

  function getalarmchild($id){
    $this->dbalarm = $this->load->database("webtracking_kalimantan", true);
      if ($id != "All") {
        $this->dbalarm->where("alarm_subcategory_id", $id);
        $this->dbalarm->where("alarm_status", 1);
      }else {
        $this->dbalarm->where("alarm_status", 1);
      }
    $this->dbalarm->order_by("alarm_name","asc");
    $q        = $this->dbalarm->get("webtracking_ts_alarm");
    return  $q->result_array();
  }

  function getalarmbytype($alarmtype){
    $this->dbalarm = $this->load->database("webtracking_ts", true);
    $this->dbalarm->select("*");
    $this->dbalarm->where("alarm_master_id", $alarmtype);
    $q        = $this->dbalarm->get("webtracking_ts_alarm");
    return  $q->result_array();
  }

  function getdetailreport($table, $alertid, $sdate){
    $this->dbalarm = $this->load->database("webtracking_kalimantan", true);
    $this->dbalarm->where("alarm_report_vehicle_id", $alertid);
    $this->dbalarm->where("alarm_report_start_time", $sdate);
    $this->dbalarm->where("alarm_report_media", 0);
    $this->dbalarm->group_by("alarm_report_start_time");
    $q             = $this->dbalarm->get($table);
    return  $q->result_array();
  }

  function getdetailreportvideo($table, $alertid, $sdate){
    $this->dbalarm = $this->load->database("webtracking_kalimantan", true);
    $this->dbalarm->select("alarm_report_downloadurl, alarm_report_id");
    $this->dbalarm->where("alarm_report_vehicle_id", $alertid);
    $this->dbalarm->where("alarm_report_start_time", $sdate);
    $this->dbalarm->where("alarm_report_media", 1);
    $this->dbalarm->group_by("alarm_report_start_time");
    $q             = $this->dbalarm->get($table);
    return  $q->result_array();
  }

  function update_post_event($table, $where, $wherenya, $data){
    $this->dbalarm = $this->load->database("webtracking_kalimantan", true);
    $this->dbalarm->where($where, $wherenya);
    return $this->dbalarm->update($table, $data);
  }

  function get_type_intervention(){
    $this->db     = $this->load->database("default", true);
    $this->db->select("*");
    $this->db->order_by("intervention_type_id", "asc");
    $q       = $this->db->get("type_intervention");
    return  $q->result_array();
  }

  function get_type_note($parent){
    $this->db     = $this->load->database("default", true);
    $this->db->select("*");
    $this->db->where("type_note_parent", $parent);
    $this->db->order_by("type_note_name", "asc");
    $q       = $this->db->get("type_note");
    return  $q->result_array();
  }

  function check_data_karyawan(){
    $this->dbts = $this->load->database("webtracking_ts", true);
    $this->dbts->select("*");
    $this->dbts->order_by("karyawan_bc_name", "ASC");
    $result = $this->dbts->get("ts_karyawan_beraucoal")->result_array();
    return $result;
  }


}

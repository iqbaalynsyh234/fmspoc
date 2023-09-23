<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
class chart_model extends Model{

    public function __construct() {
        // Remove the database loading here
    }

    public function chart_database() {
        $query = $this->db->select('alarm_report_start_time, alarm_report_statusinterventation_up')
        ->from('alarm_evidence_agustus_2023')
        ->get();
    	return $query->result_array();
    }
}

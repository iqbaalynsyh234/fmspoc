<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
class ChartModel extends Model{

    public function getChartData() {
        $query = $this->db->select('alarm_report_start_time, alarm_statusinterventation_cr')
        ->from('alarm_evidence_agustus_2023')
        ->get();
        return $query->result_array();
    }

}

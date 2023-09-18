<?php
defined('BASEPATH') OR exit('No direct script access allowed');
 
class controlroom extends CI_Model {
 
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }
 
    public function gettruefalsealarm()
{
$this->db->select('b.periode,a.pria,a.wanita,sum(a.pria+a.wanita) total');
$this->db->from('datas a');
$this->db->join('periode b','a.periode_id=b.id');
$this->db->group_by('b.periode');
$this->db->order_by('b.periode');
$query = $this->db->get();
return $query->result_array();
}
}
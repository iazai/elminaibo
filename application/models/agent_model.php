<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Agent_model extends CI_Model {
    public function __construct() {
        parent::__construct();
    }

    public function record_count($searchparam) {
        if ($searchparam == null) {
			$count=$this->db->count_all_results('billing');
			return $count;
		} else {
		
			if (!empty($searchparam['billing_name'])) {
				$this->db->like('billing_name', $searchparam['billing_name']);
			}
				
			if (!empty($searchparam['billing_city'])) {
					$this->db->like('billing_city', $searchparam['billing_city']);
			}
			
			if (!empty($searchparam['agen_status'])) {
					$this->db->where('billing_flag1', $searchparam['agen_status']);
			}
			$this->db->where('billing_level', 47); // level agent
		
			$count=$this->db->count_all_results('billing');
			return $count;
		}
    }

    public function fetch_agent($limit, $start, $searchparam) {
        
		$this->db->select('*, flag1.option_desc as flag1_desc, prov.option_desc as prov_desc');
		$this->db->from('billing');
		
		if (!empty($searchparam['billing_name'])) {
				$this->db->like('billing_name', $searchparam['billing_name']);
		}
		
		if (!empty($searchparam['billing_city'])) {
				$this->db->like('billing_city', $searchparam['billing_city']);
		}
		
		if (!empty($searchparam['agen_status'])) {
				$this->db->where('billing_flag1', $searchparam['agen_status']);
		}
		
		$this->db->join('tb_options as flag1', 'billing.billing_flag1 = flag1.option_id', 'left');
		$this->db->join('tb_options as prov', 'billing.billing_prov = prov.option_id', 'left');
		$this->db->where('billing_level', 47); // level agent
		$this->db->order_by('billing_name');
		$this->db->limit($limit, $start);
		$query=$this->db->get();
		
		if ($query->num_rows() > 0) {
			foreach ($query->result() as $row) {
				$data[] = $row;
			}
			return $data;
		}
		return false;
   }
}
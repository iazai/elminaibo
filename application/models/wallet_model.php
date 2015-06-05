<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Wallet_model extends CI_Model {
    public function __construct() {
        parent::__construct();
    }

    public function record_count($searchparam) {
        if ($searchparam == null) {
			$count=$this->db->count_all_results('tb_wallet');
			return $count;
		} else {
			$this->db->join('billing', 'billing.billing_id = tb_wallet.billing_id');
			if (!empty($searchparam['billing_name'])) {
				$this->db->like('billing_name', $searchparam['billing_name']);
			}
		
			$count=$this->db->count_all_results('tb_wallet');
			return $count;
		}
    }

    public function fetch_wallet($searchparam) {
        if ($searchparam != null) {
			if (!empty($searchparam['billing_name'])) {
					$this->db->like('billing_name', $searchparam['billing_name']);
			}

		if (!empty($searchparam['startdate']) && $searchparam['startdate'] != '1970-01-01' && 
			!empty($searchparam['enddate']) && $searchparam['enddate'] != '1970-01-01') {
			$this->db->where('wallet_trx_date >=',$searchparam['startdate']);
			$this->db->where('wallet_trx_date <=',$searchparam['enddate']);
		}
		
			$this->db->join('billing', 'billing.billing_id = tb_wallet.billing_id');
			$this->db->order_by('wallet_trx_date','asc');
			//$this->db->limit($limit, $start);
			$query=$this->db->get('tb_wallet');
			
			if ($query->num_rows() > 0) {
				foreach ($query->result() as $row) {
					$data[] = $row;
				}
				return $data;
			}
		}
		return false;
   }
}
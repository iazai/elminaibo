<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Cart_stats_model extends CI_Model {
    public function __construct() {
        parent::__construct();
    }

    public function record_count($searchparam) {
        if ($searchparam == null) {
			$count = 12;
			return $count;
		} else {
			$count = 12;
			return $count;
		}
    }
	
	public function fetch_trends($searchparam) {
		/**
		select o.order_date, sum(c.cart_qty) 
		from tb_cart c, tb_stock s, tb_product p, orders o
		where c.stock_id = s.stock_id
		and s.product_id = p.product_id
		and c.order_id = o.id
		and tb_stock.stock_id = 236
		and o.order_date >= '2014-02-01'
		and o.order_date <= '2015-02-28'
		group by o.order_date
		order by o.order_date
		*/
		$data = null;
		
		$begin = new DateTime($searchparam['startdate']);
		$end = new DateTime($searchparam['enddate']);

		$interval = DateInterval::createFromDateString('1 day');
		$period = new DatePeriod($begin, $interval, $end);

		foreach ($period as $dt) {
			if (!empty($searchparam['interval'])) {
				if($searchparam['interval'] == 'MONTH') {
					$this->db->select('MONTH(o.order_date) as dates, YEAR(o.order_date) as year, sum(cart_qty) as total_qty, 
									o.product_amount - o.discount_amount as total_omset');
				} else if($searchparam['interval'] == 'DAY') {
					$this->db->select('o.order_date as dates, YEAR(o.order_date) as year, sum(cart_qty) as total_qty, 
									o.product_amount - o.discount_amount as total_omset');
				} else {
					$this->db->select('o.order_date as dates, YEAR(o.order_date) as year, sum(cart_qty) as total_qty, 
									o.product_amount - o.discount_amount as total_omset');
				}
			} else {
				$this->db->select('o.order_date as dates,  sum(cart_qty) as total_qty, sum(cart_amount) as total_omset');
			}
			
			$this->db->from('tb_cart');
						
			$this->db->join('tb_stock', 'tb_cart.stock_id = tb_stock.stock_id');
			$this->db->join('tb_product', 'tb_stock.product_id = tb_product.product_id');
			$this->db->join('orders as o', 'tb_cart.order_id = o.id');
			
			if (!empty($searchparam['product_id']) && $searchparam['product_id'] > 0) {
				$this->db->where('tb_product.product_id', $searchparam['product_id']);
			} else if (!empty($searchparam['stock_id']) && $searchparam['stock_id'] > 0) {			
				$this->db->where('tb_stock.stock_id', $searchparam['stock_id']);
			}
			
			if (!empty($searchparam['stockname'])) {
				$this->db->like('tb_stock.stock_desc', $searchparam['stockname']);
			}
			
			// order date
			$dt->dates = $dt->format("Y-m-d");
			$this->db->where('order_date',$dt->dates);
			//$this->db->where('order_date <=',$searchparam['enddate']);
			if (!empty($searchparam['interval'])) {
				if($searchparam['interval'] == 'MONTH') {
					$this->db->group_by('MONTH(order_date)');
				} else if($searchparam['interval'] == 'DAY') {
					$this->db->group_by('order_date');
				} else {
					$this->db->group_by('order_date');
				}
			} else {
					$this->db->group_by('order_date,');
			}
			
			$this->db->order_by('order_date', 'asc');
			
			$query = $this->db->get();

			if ($query->num_rows() > 0) {
				foreach ($query->result() as $row) {
					//$startdate 	= date('Y-m-d', strtotime($this->input->post('startdate')));
					$row->dates = date('d-M-Y', strtotime($row->dates));;
					$data[] = $row;
				}
			} else {
				$dt->dates = $dt->format("d-M-Y");
				$dt->total_qty = 0;
				$dt->total_omset = 0;
				$data[] = $dt;
			}
		}
		return $data;
	
		//return false;
	}
	
	
    public function fetch_stock($limit, $start, $searchparam) {
        
		// MONTHLY
		/**
		select tb_product.product_id, MONTH(order_date), sum(cart_qty), sum(`cart_amount`), orders.package_status from tb_cart
		join orders, tb_stock, tb_product
		where orders.id = tb_cart.order_id
		and tb_stock.stock_id = tb_cart.stock_id
		and tb_stock.product_id = tb_product.product_id
		and tb_product.product_id = 39
		
		group by MONTH(order_date), orders.package_status
					
		*/
		$this->db->select('tb_product.product_name, MONTH(order_date) as month, 
							sum(cart_qty) as total_qty, sum(cart_amount) as total_amount,
							orders.package_status');
		$this->db->from('tb_cart');
					
		$this->db->join('orders', 'orders.id = tb_cart.order_id','left');
		$this->db->join('tb_stock', 'tb_stock.stock_id = tb_cart.stock_id');
		$this->db->join('tb_product', 'tb_stock.product_id = tb_product.product_id');
					
		if (!empty($searchparam['product_id'])) {
			$this->db->where('tb_product.product_id', $searchparam['product_id']);
		}
		
		//$this->db->where('orders.package_status', 1);
		$this->db->group_by('MONTH(order_date), orders.package_status');
		$query = $this->db->get();
		
		if ($query->num_rows() > 0) {
			
			foreach ($query->result() as $row) {
			
				$this->db->select('*');
				$this->db->from('tb_reject_cart');
				$this->db->join('tb_reject', 'tb_reject.reject_id = tb_reject_cart.reject_id');
				$this->db->join('tb_stock', 'tb_stock.stock_id = tb_reject_cart.stock_id');
				$this->db->join('tb_product', 'tb_stock.product_id = tb_product.product_id');
		
				if (!empty($searchparam['product_id'])) {
					$this->db->where('tb_product.product_id', $searchparam['product_id']);
				}
				$this->db->where('MONTH(reject_date)', $row->month);
				$query_reject=$this->db->get();
				
				$reject_qty = 0;
				$row->reject_qty = $reject_qty;
				if ($query_reject->num_rows() > 0) {
					foreach ($query_reject->result() as $row2) {
						$reject_qty += $row2->reject_cart_qty;
					}
					$row->reject_qty = $reject_qty;
				}
				$data[] = $row;
			}
			
				
			return $data;
		}
		return false;
   }
}
<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Material_Inventory extends CI_Controller {

	function __construct()
    {
        parent::__construct();
        $this->load->helper('form');
        $this->load->library('form_validation');
        $this->load->library('pagination');
		
		$this->load->model('activity_model');
		$this->load->model('material_inventory_model');
		$this->load->model('GenericModel');
    }
	
	public function index() {
	}
	
	public function lists() {
		if($this->session->userdata('logged_in')) {	
			$this->load->helper('form');
			$this->load->library('form_validation');
			
			// SEARCHING TERMS
			$searchterm = $this->session->userdata('searchterm');
			
			// Paging
			$config = array();
			$config["base_url"] = base_url("index.php/materialInventory/lists");
			$config["total_rows"] = $this->material_inventory_model->record_count($searchterm);
			$config["per_page"] = 20;
			
			$this->pagination->initialize($config);
			$page = $this->uri->segment(3);
			
			$data["list_material_inventory"] = $this->material_inventory_model->fetch_material_inventory($config["per_page"], $page, $searchterm);
			$data["links"] = $this->pagination->create_links();
			$data["row"] = 1+$page;
			$data["page"] = "materialInventoryList";
			
			$this->load->view('dashboard',$data);
		} else {
			redirect(site_url('login'));
		}
	}
	
	public function doAdd() {
		if($this->session->userdata('logged_in')) {	
			$msg = "";
			$material_code = "";
			
			// SETTING MATERIAL CODE ...
			// ....
			// ....
			
			$material_inventory = array (
				'material_code' => $material_code,
				'material_bahan_id' => $this->input->post('material_bahan_id'),
				'material_warna_id' => $this->input->post('material_warna_id'),
				'material_date_init' => date('Y-m-d', strtotime($this->input->post('material_date_init'))),
				'material_qty_init' => $this->input->post('material_qty_init'),
				'material_qty' => $this->input->post('material_qty_init'),
				'material_nominal_init' => $this->input->post('material_nominal_init'),
				'material_nominal' => $this->input->post('material_nominal_init'),
				'material_type_id' => 7 // inv purchase
			);
			
			$this->db->insert('tb_material_inventory', $material_inventory); 
			$msg .= '<p>Material Inv has been added..!</p>';
			$material_inventory_id = $this->db->insert_id();
			
			// add log
			$material_inventory_log = array (
				'material_inventory_log_date' => date('Y-m-d', strtotime($this->input->post('material_date_init'))),
				'material_inventory_id' => $material_inventory_id,
				'material_inventory_log_desc' => 'Bahan masuk Kode : '.$material_code,
				'material_inventory_log_used_qty' => 0,
				'material_inventory_log_last_qty' => $this->input->post('material_qty_init'),
				'material_inventory_log_used_nominal' => 0,
				'material_inventory_log_last_nominal' => $this->input->post('material_nominal_init'),
			);
			
			
			$this->db->insert('tb_material_inventory_log', $material_inventory_log); 
			$msg .= '<p>Material Inv Log has been added..!</p>';
			
			
		} else {
			redirect(site_url('login'));
		}
	}
	
	public function add() {
		if($this->session->userdata('logged_in')) {	
			// material bahan
			$this->db->where('option_type', 'MBAHAN');
			$this->db->order_by('option_desc');
			$data['material_bahan'] = $this->db->get('tb_options')->result();
			
			// material warna
			$this->db->where('option_type', 'MWARNA');
			$this->db->order_by('option_desc');
			$data['material_warna'] = $this->db->get('tb_options')->result();
			
			$data['page'] = "materialInventoryAdd";
			$this->load->view('dashboard',$data);
			
		} else {
			redirect(site_url('login'));
		}
	}
	
	public function search() {
		if($this->session->userdata('logged_in')) {	
			$this->session->unset_userdata('searchterm');
			
			// Searching
			$searchparam = array(
				   'inventory_type_id' => $this->input->post('inventory_type_id'),
				   'inventory_desc' => $this->input->post('inventory_desc'),
				   'product_id' => $this->input->post('product_id')
			);
			
			$this->GenericModel->searchterm_handler($searchparam);
			
			redirect(site_url('inventory/lists'));
		} else {
			 redirect(site_url('login'));
		}
	}
	
	
	
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */
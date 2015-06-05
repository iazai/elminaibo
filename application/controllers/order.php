<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Order extends CI_Controller {
 
	function __construct()
    {
        parent::__construct();
        $this->load->helper('form');
        $this->load->library('form_validation');
		$this->load->library('pagination');
		
		$this->load->model('activity_model');
		$this->load->model('GenericModel');
		$this->load->model('OrderModel');
		$this->load->model('Stock_model');
		
		$this->load->model('stock_model');
		
    } 
	
	function choose_customer() {
		if($this->session->userdata('logged_in')) {	
			
			// get customer data
			$this->db->where('billing_id', $this->uri->segment(3));
			$data['billing']=$this->db->get('billing')->result();
			
			// get last order
			$this->db->where('billing_id', $this->uri->segment(3));
			$this->db->order_by('order_date', 'desc');
			$this->db->limit(1);
			$data['last_order'] = $this->db->get('orders')->result();
			
			// get inventory
			$this->db->order_by('stock_desc');
			$this->db->where('stock_qty >',0);

			$this->db->where('tb_product.status',1);
			$this->db->join('tb_product', 'tb_product.product_id = tb_stock.product_id','left');
			$data['stock']=$this->db->get('tb_stock')->result();
			
			// get billing level
			$this->db->where('option_type', 'BILL_LV');
			$this->db->order_by('option_desc');
			$data['billing_level'] = $this->db->get('tb_options')->result();
			
			// get Provinsi
			$this->db->where('option_type', 'PROVINCE');
			$this->db->order_by('option_desc');
			$data['provinces'] = $this->db->get('tb_options')->result();
			
			// get shipper
			$this->db->where('billing_level', 47);
			$this->db->where('billing_flag1 <>', 103);
			$this->db->order_by('billing_name');
			$data['shippers'] = $this->db->get('billing')->result();
			
			// get bank account
			$this->db->order_by('bank_account_name');
			$data['bank_account']=$this->db->get('bank_account')->result();
			
			// get liabilities
			$this->db->where('option_code','ACC_PAY');
			$this->db->order_by('option_desc');
			$data['liabilities_type'] = $this->db->get('tb_options')->result();
			
			// get order channel
			$this->db->where('option_type', 'ORD_CHANNEL');
			$this->db->order_by('option_desc');
			$data['order_channel'] = $this->db->get('tb_options')->result();
			
			// get wallet
			$this->db->where('billing_id', $this->uri->segment(3));
			$this->db->order_by('wallet_trx_date', 'desc');
			$this->db->limit(1);
			$wallet = $this->db->get('tb_wallet');
			
			$data['wallet_balance'] = null;
			if ($wallet->num_rows() > 0) {
				$last_wallet_balance = $wallet->row()->wallet_balance;
				$data['wallet_balance'] = $last_wallet_balance;
			}
			
			$data['page'] = "orderAdd";
			$this->load->view('dashboard',$data);
			
		} else {
			redirect(site_url('login'));
		}
	}
	
	function find_customer() {
		if($this->session->userdata('logged_in')) {	
			$billing_name = $this->input->post('billing_name');
			$billing_phone = $this->input->post('billing_phone');
			$billing_id = $this->uri->segment(3);
			
			
			// looking for existing customer
			if (!empty($billing_name)) {
				$this->db->like('billing_name', $billing_name);
			}
				
			if (!empty($billing_phone)) {
					$this->db->like('billing_phone', $billing_phone);
			}
			
			if (!empty($billing_id)) {
					$this->db->where('billing_id', $billing_id);
			}
			
			$this->db->join('tb_options', 'tb_options.option_id = billing.billing_level','left');
			$this->db->order_by('billing_id');
			$data['list_customer'] = $this->db->get('billing')->result();
			
			// looking for order history if customer exist
			// SEARCHING TERMS
			$this->session->unset_userdata('searchterm');
			
			// Searching
			$searchparam = array(
				   'billing_name' => $this->input->post('billing_name'),
				   'billing_phone' => $this->input->post('billing_phone'),
				   'billing_id' => $billing_id
			);
			
			$this->GenericModel->searchterm_handler($searchparam);
			
			$searchterm = $this->session->userdata('searchterm');
			
			// Paging
			$config = array();
			$config["base_url"] = base_url("index.php/order/find_customer");
			$config["total_rows"] = $this->OrderModel->record_count($searchterm);
			$config["per_page"] = 50;
			
			$this->pagination->initialize($config);
			$page = 0;//$this->uri->segment(3);
			
			//$data['listOrderPending'] = $this->OrderModel->fetch_orders_pending($config["per_page"], $page, $searchterm);
			$data['list_history_order'] = $this->OrderModel->fetch_all_orders($config["per_page"], $page, $searchterm);
			
			$data["row"] = 1+$page;
			
			
			$data['page'] = "orderCustomerList";
			$this->load->view('dashboard',$data);
			
		} else {
			redirect(site_url('login'));
		}
	}
	
	function push_button_order_form() {
		if ($_POST['action'] == 'CETAK NOTA') {
			$this->cetak_nota();
		} else if ($_POST['action'] == 'SUBMIT') {
			$this->doUpdate();
		}
	}
	
	function push_button() {
		if ($_POST['action'] == 'CETAK ALAMAT') {
			$this->to_pdf();
		} else if ($_POST['action'] == 'SUDAH DIKIRIM') {
			$this->sudah_dikirim();
		} else if ($_POST['action'] == 'SUDAH BAYAR') {
			$this->sudah_bayar();
		}
	}
	
	function sudah_dikirim() {
		$searchterm = $this->session->userdata('searchterm');
		$totalrow = $this->OrderModel->record_count($searchterm);
		$ordercount = 0;
		for ($i = 0;$i<= $totalrow;$i++) {
			$ch = $this->input->post('ch'.$i);
			if ( isset($_POST['ch'.$i]) ) {
				
				$this->db->where('id', $ch);
				$queryorder = $this->db->get('orders');
				foreach ($queryorder->result() as $row) {
					// UPDATE STATUS PENGIRIMAN
					$sudah_terkirim = array (
						'package_status' => 1
						);
						
					$this->db->where('id', $ch);
					$this->db->update('orders', $sudah_terkirim);
					$ordercount++;
				}
			}
		}
		
		
		// add to activity
		$session_data = $this->session->userdata('logged_in');
		$activity_desc = 'ORDER - Sudah dikirim [ <b>'.$ordercount.' Order(s) </b>]';
		$this->activity_model->add_activity($session_data['user_id'], $activity_desc);
		
		$msg .= '<p>Order has been updated</p>';
		$this->session->set_flashdata('success_message',$msg);	
						
		redirect(site_url('order')); 
	}
	
	function cetak_nota() {
		$this->load->library("Pdf");
		
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);    
		
		$pdf->SetMargins(3, 2, PDF_MARGIN_RIGHT);
		
		// set some language-dependent strings (optional)
		if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
			require_once(dirname(__FILE__).'/lang/eng.php');
			$pdf->setLanguageArray($l);
		}   
	 
		// set default font subsetting mode
		$pdf->setFontSubsetting(true);   
	 
		// Set font
		$pdf->SetFont('dejavusans', '', 9, '', true);   
	 
		// Add a page
		$pdf->AddPage(); 
	 
		// ====================== CONTENT LOGIC ================================================
		$subtotal = 0;
		$diskon = $this->input->post('discount_amount');
		$ongkir = $this->input->post('exp_cost');
		$order_id = $this->input->post('order_id');
		$billing_name = $this->input->post('billing_name');
		$order_date = $this->input->post('order_date');
		$adjustment_desc = $this->input->post('adjustment_desc');
		$adjustment_nominal = $this->input->post('adjustment_nominal');
		
		$html = '
		<table>
			<tr>
				<td colspan="3"></td>
				<td colspan="1">Nama : '.$billing_name.'</td>
			</tr>	
			<tr>
				<td colspan="3">Nota No : '.$order_id.'</td>
				<td colspan="1">Tanggal : '.$order_date.'</td>
			</tr>	
		</table>
		
		<table border="1px" cellpadding="5px;">
					<tr align="center">
						<td width="10%">Kode</td>
						<td width="50%">Nama Barang</td>
						<td width="5%">Qty</td>
						<td width="15%">Harga Agen</td>
						<td width="20%">Subtotal</td>
					</tr>';
		// masukkan produk kedalam tb_cart
		$this->db->join('tb_product', 'tb_product.product_id = tb_stock.product_id');
		$queryStock = $this->db->get('tb_stock');
		if ($queryStock->num_rows > 0) {
			$total_qty = 0;
			$cart = array(null);
			foreach ($queryStock->result() as $rowStock) {
				$purchase_qty = $this->input->post('qty'.$rowStock->stock_id);
				if ($purchase_qty > 0) {
					$this->db->where('product_id', $rowStock->product_id);
					$queryproduct = $this->db->get('tb_product');
					
					foreach ($queryproduct->result() as $rowProduct) {
						
						$cart_in_html = '<tr>
									<td align="center">'.$rowProduct->product_code.'</td>
									<td>'.$rowProduct->product_name.' - '.$rowStock->stock_desc.'</td>
									<td align="center">'.$purchase_qty.'</td>';
									
									if ($this->input->post('special_price') == true) {
										$cart_in_html .= '<td align="center">'.$rowStock->current_special_price.'</td>
										 <td align="right">'.$rowStock->current_special_price * $purchase_qty.'</td>
										 </tr>';
										 $subtotal += $rowStock->current_special_price * $purchase_qty;
									} else if ($this->input->post('wholesale_price') == true) {
										$cart_in_html .= '<td align="center">'.$rowStock->current_wholesale_price.'</td>
										 <td align="right">'.$rowStock->current_wholesale_price * $purchase_qty.'</td>
										 </tr>';
										$subtotal += $rowStock->current_wholesale_price * $purchase_qty;
									} else {
										$cart_in_html .= '<td align="center">'.$rowStock->current_stock_price.'</td>
										 <td align="right">'.$rowStock->current_stock_price * $purchase_qty.'</td>
										 </tr>';
										$subtotal += $rowStock->current_stock_price * $purchase_qty;
									}
									
								  
						array_push($cart, $cart_in_html);
					}
					
					$total_qty = $total_qty + $purchase_qty;
				}
			}
			
			sort($cart);
			foreach ($cart as $row) {
				$html .= $row;
			}
		}
		// space
		$html .= '<tr>
					<td colspan="5"></td>
				  </tr>';
				  
		// Subtotal
		$html .= '<tr>
					<td colspan="2">'.'Subtotal'.'</td>
					<td colspan="2" align="center">'.$total_qty.' PCS</td>
					<td align="right"> '.$subtotal.'</td>
				  </tr>';
		
		// Ongkir
		$html .= '<tr>
					<td colspan="2">'.'Ongkos Kirim'.'</td>
					<td colspan="2" align="center">'.''.'</td>
					<td align="right" class="nominal"> '.$ongkir.'</td>
				  </tr>';
		
		$adjustment = '';
		if ($adjustment_nominal >= 0) {
			$adjustment = 'Biaya Tambahan';
		} else {
			$adjustment = 'Diskon';
		}
		// Adjustment 
		$html .= '<tr>
					<td colspan="2">'.$adjustment.'</td>
					<td colspan="2" align="center">'.$adjustment_desc.'</td>
					<td align="right"> '.$adjustment_nominal.'</td>
				  </tr>';
				  
		// Diskon jika ada
		/*
		$html .= '<tr>
					<td colspan="2">'.'Diskon Tambahan'.'</td>
					<td colspan="2" align="center">'.''.'</td>
					<td align="right"> ('.''.')</td>
				  </tr>';
		*/

		// space
		$html .= '<tr>
					<td colspan="5"></td>
				  </tr>';		
				  
		
		// TOTAL 
		$total = $subtotal + $ongkir + $adjustment_nominal;
		
		$html .= '<tr>
					<td colspan="2">'.'TOTAL'.'</td>
					<td colspan="2" align="center">'.''.'</td>
					<td align="right" class="nominal">'.$total.'</td>
				  </tr>';		  
				  
		$html .= '</table>';
		
		// Print text using writeHTMLCell()
		$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);   
	 
		// ---------------------------------------------------------    
	 
		// Close and output PDF document
		// This method has several options, check the source code documentation for more information.
		$pdf->Output('example_001.pdf', 'I');    
	}
	
	function alamat_html($order_id) {
	
		$html = '';
		//echo 'dalem alamat';
		$this->db->select('*,  
						billing.billing_id as bill_id,
						billing.billing_name as bill_name,
						billing.billing_phone as bill_phone,
						billing.billing_street as bill_street,
						billing.billing_kec as bill_kec,
						billing.billing_city as bill_city,
						billing.billing_prov as bill_prov,
						billing.billing_postal_code as bill_postal_code,
						billing.billing_country as bill_country,
						
						ship.billing_id as ship_id,
						ship.billing_alias_name as ship_name,
						ship.billing_phone as ship_phone,
						ship.billing_street as ship_street,
						ship.billing_kec as ship_kec,
						ship.billing_city as ship_city,
						ship.billing_prov as shipper_prov,
						ship.billing_postal_code as ship_postal_code,
						ship.billing_country as ship_country'
						
						);
		$this->db->from('orders');
		$this->db->where('id', $order_id);
		$this->db->join('billing', 'billing.billing_id = orders.billing_id','left');
		//$this->db->join('billing as shipper', 'orders.shipper_id = shipper.billing_id');
		$this->db->join('billing as ship', 'ship.billing_id = orders.shipper_id');
		$queryorder = $this->db->get();
		
		foreach ($queryorder->result() as $row) {
			$b_street = '';
			$b_kec = '';
			$b_city = '';
			$b_prov = '';
			$b_country = '';
			$b_postal_code = '';
			
			if (!empty($row->bill_street)) { 
				$b_street = $row->bill_street.'<br/>';
			}
			if (!empty($row->bill_kec)) { 
				$b_kec = 'Kec. '.$row->bill_kec.'. ';
			}
			if (!empty($row->bill_city)) { 
				$b_city = $row->bill_city.'<br/>';
			}
			if (!empty($row->bill_prov)) {
				
					$this->db->where('option_id', $row->bill_prov);
					$this->db->limit(1);
					$province = $this->db->get('tb_options');
					if ($province->num_rows > 0) {
						$b_prov = $province->row()->option_desc.' ';					
					} else {
						$b_prov = $row->bill_prov.' ';					
					}
			}
			if (!empty($row->bill_postal_code)) { 
				$b_postal_code = $row->bill_postal_code.'. ';
			}
			if (!empty($row->bill_country)) { 
				$b_country = $row->bill_country;
			}
		
			$s_street = '';
			$s_kec = '';
			$s_city = '';
			$s_prov = '';
			$s_postal_code = '';
			$s_country = '';
			
			if (!empty($row->ship_street)) { 
				$s_street = $row->ship_street.'<br/>';
			}
			if (!empty($row->ship_kecamatan)) { 
				$s_kec = 'Kec. '.$row->ship_kecamatan.'. ';
			}
			if (!empty($row->ship_city)) { 
				$s_city = $row->ship_city.'<br/>';
			}
			if (!empty($row->shipper_prov)) {
				
					$this->db->where('option_id', $row->shipper_prov);
					$this->db->limit(1);
					$province = $this->db->get('tb_options');
					if ($province->num_rows > 0) {
						$s_prov = $province->row()->option_desc.' ';
					} else {
						$s_prov = $row->shipper_prov.' ';
					}
			}
			//$s_prov = 'tes';
			if (!empty($row->ship_postal_code)) { 
				$s_postal_code = $row->ship_postal_code.'. ';
			}
			if (!empty($row->ship_country)) { 
				$s_country = $row->ship_country;
			}
		
			$html .=  '
						<table border="0px" width="290px" cellpadding="3px">
	<tr>
		<td colspan="1">Ekspedisi :</td>
		<td colspan="3">'.$row->expedition.' / '.$row->service.' &nbsp;(Tarif : '.$row->exp_cost.')</td>		
	</tr>
	
	<tr>
		<td  colspan="1">Penerima :</td>
		<td colspan="3"><b>'.$row->bill_name.'</b></td>
	</tr>
	<tr>
		<td  colspan="1">d/A :</td>
		<td colspan="3">'.
			$b_street.$b_kec.$b_city.$b_prov.$b_postal_code.$b_country.
			'
		</td>
	</tr>
	<tr>
		<td  colspan="1">Telp :</td>
		<td colspan="3">'.$row->bill_phone.'</td>
	</tr>
</table>
<hr/>
<table border="0px" width="270px" cellpadding="3px">
	<tr>
		<td colspan="1">Pengirim :</td>
		<td colspan="3"><b>'.$row->ship_name.'</b></td>
	</tr>
	<tr>
		<td colspan="1"></td>
		<td colspan="3">
		'.$s_city.$s_prov.
		'
		</td>
	</tr>
	<tr>
		<td colspan="1">Telp :</td>
		<td colspan="3">'.$row->ship_phone.'</td>
	</tr>
</table>
<hr/>
<br/><br/>
		';
		}
		return $html;
	}
	
	function to_pdf() {
		$this->load->library("Pdf");
		
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);    
		
		$pdf->SetMargins(3, 2, PDF_MARGIN_RIGHT);
		
		// set some language-dependent strings (optional)
		if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
			require_once(dirname(__FILE__).'/lang/eng.php');
			$pdf->setLanguageArray($l);
		}   
	 
		// set default font subsetting mode
		$pdf->setFontSubsetting(true);   
	 
		// Set font
		$pdf->SetFont('dejavusans', '', 9, '', true);   
	 
		// Add a page
		$pdf->AddPage(); 
	 
		
		// ====================== CONTENT LOGIC ================================================
		$html = '';
		$searchterm = $this->session->userdata('searchterm');
		$totalrow = $this->OrderModel->record_count($searchterm);
		
		for ($i = 0;$i<= $totalrow;$i++) {
			$ch = $this->input->post('ch'.$i);
			if ( isset($_POST['ch'.$i]) ) {
				$html .= $this->alamat_html($ch);
				
			}
		}
		
		// Print text using writeHTMLCell()
		$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);   
	 
		// ---------------------------------------------------------    
	 
		// Close and output PDF document
		// This method has several options, check the source code documentation for more information.
		$pdf->Output('example_001.pdf', 'I');    
	}
	
	function add_to_cart($stock_id, $order_id, $qty, $amount, $date) {
		
		$inventory_cogs_reduce = 0;
		
		// UPDATE STOCK
		$this->db->where('stock_id', $stock_id);
		$this->db->limit(1);
		$purchasedStock = $this->db->get('tb_stock');
		
		foreach ($purchasedStock->result() as $rowPurchasedStock) {
			if ($rowPurchasedStock->stock_id == $stock_id) {
				
				// LOOKING FOR STOCK IN CART OF THIS ORDER
				
				$this->db->where('stock_id', $stock_id);
				$this->db->where('order_id', $order_id);
				$cartContainThisStock = $this->db->get('tb_cart');
				if ($cartContainThisStock->num_rows > 0) {
					// IF THEY HAS BEEN PURCHASE BEFORE
					foreach ($cartContainThisStock->result() as $rowCart) {
						// get previous qty
						$previousPurchasedStockQty = $rowCart->cart_qty;
						$undoStockQty = $previousPurchasedStockQty + $rowPurchasedStock->stock_qty;
					
						// new qty : undo stock - current stock purchased qty
						$new_stock_qty = $undoStockQty - $qty;
						$new_stock_nominal = $new_stock_qty * $rowPurchasedStock->stock_cogs;
							
						// UPDATE STOCK
						$new_stock = array (
							'stock_qty' => $new_stock_qty,
							'stock_nominal' => $new_stock_nominal
						);
														
						$this->db->where('stock_id', $stock_id);
						$this->db->update('tb_stock', $new_stock);
						
						// INVENTORY QTY
						$msg = '';
						$this->db->where('stock_id', $rowCart->stock_id);
						$this->db->order_by('inventory_date', 'desc');
						$queryInventory = $this->db->get('tb_inventory');
						if ($queryInventory->num_rows > 0) {
							
							$readyInventory = $new_stock_qty; // 20 - 8 = 12		
							
							foreach ($queryInventory->result() as $rowInventory) {
							
								$inventory_update_qty = 0;
								
									// REDUCE INVENTORY QTY ON INVENTORY TABLE. CART 8, INV 9
									$inventory_cogs_reduce = 0;
									if ($readyInventory > $rowInventory->inventory_qty_init) { // 7 > 5
										$inventory_update_qty = $rowInventory->inventory_qty_init; //  
										$this->update_inventory_qty($inventory_update_qty, $rowInventory->inventory_id); // 
									} else { // 
										$inventory_update_qty = $readyInventory; // 7
										$this->update_inventory_qty($inventory_update_qty, $rowInventory->inventory_id);
									}
									$readyInventory = $readyInventory - $inventory_update_qty;
							}
						}
						
					}
					
				} else {
					if ($qty != 0) {
					
						//IF THIS STOCK IS A NEW PURCHASE
						$new_stock_qty = $rowPurchasedStock->stock_qty - $qty;
						$new_stock_nominal = $new_stock_qty * $rowPurchasedStock->stock_cogs; 
												
						// UPDATE STOCK
						$new_stock = array (
							'stock_qty' => $new_stock_qty,
							'stock_nominal' => $new_stock_nominal
						);
														
						$this->db->where('stock_id', $stock_id);
						$this->db->update('tb_stock', $new_stock);
						
						// INVENTORY QTY
						$this->db->where('stock_id', $stock_id);
						$this->db->where('inventory_qty >', 0);
						$this->db->order_by('inventory_date');
						$queryInventory = $this->db->get('tb_inventory');
						if ($queryInventory->num_rows > 0) {
							
							$cart_qty = $qty;
							
							foreach ($queryInventory->result() as $rowInventory) {
								
								$inventory_update_qty = 0;
								if ($cart_qty <= $rowInventory->inventory_qty) { // CART 8, INV 9
									$inventory_update_qty = $rowInventory->inventory_qty - $cart_qty;
									$this->update_inventory_qty($inventory_update_qty, $rowInventory->inventory_id);
									break;
								} else { // CART 9, INV 8, INV_I 10
									$inventory_update_qty = 0;
									$this->update_inventory_qty($inventory_update_qty, $rowInventory->inventory_id);
									$cart_qty = $cart_qty - $rowInventory->inventory_qty;
								}
							}
						}
					}
				}
				
				// TB_CART
				$new_cart = array(
					'stock_id' => $stock_id,
					'order_id' => $order_id,
					'cart_qty' => $qty,
					'cart_amount' => $amount
				);
							
				$this->db->where('stock_id', $stock_id);
				$this->db->where('order_id', $order_id);
				$query = $this->db->get('tb_cart');
										
				if ($query->num_rows > 0 && $qty <= 0) {
					$this->db->delete('tb_cart', array('stock_id' => $stock_id, 'order_id' => $order_id));
				} else if ($query->num_rows > 0 && $qty > 0) {
					$this->db->where('stock_id', $stock_id);
					$this->db->where('order_id', $order_id);
					$this->db->update('tb_cart', $new_cart);
				} else if ($query->num_rows == 0 && $qty > 0) {
					$this->db->insert('tb_cart', $new_cart);
				}
			}
		}
	}
	
	
	private function update_inventory_qty($qty, $id){
		$inventory_update = array(
			'inventory_qty' => $qty,
		);
												   
		$this->db->where('inventory_id', $id);
		$this->db->update('tb_inventory',$inventory_update);
	}
	
	private function update_inventory_sold($nominal, $order_id) {
		$inventory_update = array(
			'inventory_nominal' => '-'.$nominal
		);
		
		$this->db->where('order_id', $order_id);
		$this->db->update('tb_inventory',$inventory_update);
	}
	
	private function insert_inventory_sold($date, $nominal, $order_id) {
		// $this->insert_inventory_sold($date, $inventory_cogs_reduce, $order_id);
		$inventory_insert = array(
			'inventory_date' => $date,
			'inventory_desc' => 'Order coming',
			'inventory_nominal' => '-'.$nominal,
			'inventory_type_id' => 24,
			'order_id' => $order_id,
		);
		
		$this->db->insert('tb_inventory',$inventory_insert);
	}
	
	function update_stock($stock_id, $qty) {
	}
	
	public function delete() {
		if($this->session->userdata('logged_in')) {
			
			// search billing id
			$this->db->where('id', $this->uri->segment(3));
			$this->db->join('billing', 'billing.billing_id = orders.billing_id');
			
			$querybilling = $this->db->get('orders');
			
			$billing_name = $querybilling->row()->billing_name;
			
			// UNDO STOCK
			$this->db->where('order_id', $this->uri->segment(3));
			$query = $this->db->get('tb_cart');
			if ($query->num_rows > 0) {
				foreach ($query->result() as $row) {
					$this->db->where('stock_id', $row->stock_id);
					$stock = $this->db->get('tb_stock')->row();
					
					$new_stock_qty = $stock->stock_qty + $row->cart_qty;
					$refund_stock = array (
						'stock_qty' => $new_stock_qty,
						'stock_nominal' => $new_stock_qty * $stock->stock_cogs
					);
					
					$this->db->where('stock_id', $row->stock_id);
					$this->db->update('tb_stock',$refund_stock);
					
					// === UPDATE STOCK ON STORE VIA WEBSERVICE ===
					//$this->stock_model->update_category_on_store($row->stock_id);
					// ==== === === === === === === === === === ===
					
				}
			}
			
			// UNDO INVENTORY
			
			$this->db->where('order_id', $this->uri->segment(3));
			$queryCart = $this->db->get('tb_cart');
			if ($query->num_rows > 0) {
				foreach ($queryCart->result() as $rowCart) {
			
					// INVENTORY QTY
					$msg = '';
					$this->db->where('stock_id', $rowCart->stock_id);
					$this->db->order_by('inventory_date', 'desc');
					$queryInventory = $this->db->get('tb_inventory');
					
					if ($queryInventory->num_rows > 0) {
						$readyInventory = $new_stock_qty; // 20 - 8 = 12		
							
						foreach ($queryInventory->result() as $rowInventory) {
							$inventory_update_qty = 0;
							// REDUCE INVENTORY QTY ON INVENTORY TABLE. CART 8, INV 9
							$inventory_cogs_reduce = 0;
							if ($readyInventory > $rowInventory->inventory_qty_init) { // 7 > 5
								$inventory_update_qty = $rowInventory->inventory_qty_init; //  
								$this->update_inventory_qty($inventory_update_qty, $rowInventory->inventory_id); // 
							} else { // 
								$inventory_update_qty = $readyInventory; // 7
								$this->update_inventory_qty($inventory_update_qty, $rowInventory->inventory_id);
							}
							$readyInventory = $readyInventory - $inventory_update_qty;
						}
					}
				}	
			}
			
			$this->db->delete('orders', array('id' => $this->uri->segment(3))); 
			$this->db->delete('tb_cash', array('order_id' => $this->uri->segment(3)));
			$this->db->delete('tb_cart', array('order_id' => $this->uri->segment(3)));
			$this->db->delete('tb_inventory', array('order_id' => $this->uri->segment(3)));
			$this->db->delete('tb_income', array('order_id' => $this->uri->segment(3)));
			$this->db->delete('tb_acrec', array('order_id' => $this->uri->segment(3)));
			
			$msg = '<p>Order has been deleted. Stock was refunded</p>';
			$this->session->set_flashdata('success_message',$msg);	
						
			
			// add to activity
			$session_data = $this->session->userdata('logged_in');
			$activity_desc = 'ORDER - Delete [Billing Name : <b>'.$billing_name.'</b>]';
			$this->activity_model->add_activity($session_data['user_id'], $activity_desc);
				
			redirect(site_url('order'));
		} else {
			 redirect(site_url('login'));
		}
	}
	
	public function doAdd() {
		if($this->session->userdata('logged_in')) {	
				$this->load->helper('string');
				
				$msg = '';
				
				// BILLING		
				$billing = array(
							   'billing_name' => $this->input->post('billing_name'),
							   'billing_street' => $this->input->post('billing_street'),
							   'billing_kec' => $this->input->post('billing_kec'),
							   'billing_city' => $this->input->post('billing_city'),
							   'billing_postal_code' => $this->input->post('billing_postal_code'),
							   'billing_prov' => $this->input->post('billing_prov'),
							   'billing_phone' => $this->input->post('billing_phone'),
							   'billing_country' => $this->input->post('billing_country'),
							   'billing_level' => $this->input->post('billing_level')
						);
						
				$billing_id = $this->input->post('billing_id');
				
				if (!empty($billing_id)) {
					$this->db->where('billing_id', $billing_id);
					$this->db->update('billing', $billing);
				} else {
					$this->db->insert('billing', $billing); 
					$billing_id = $this->db->insert_id();
				}
				
				
				// SHIPPING
				$shipper_id = $this->input->post('shipper_id');
				$shipper_alias_name = $this->input->post('shipper_alias_name');
				if (!empty($shipper_alias_name)) {
					$shipper_alias = array('billing_alias_name' => $this->input->post('shipper_alias_name'));
					$this->db->where('billing_id', $shipper_id);
					$this->db->update('billing', $shipper_alias); 
				}
				// ORDER ORDER
				//$invoice=
				$discount_amount = $this->input->post('discount_amount');
				$exp_cost = $this->input->post('exp_cost');
				$adjustment_desc = $this->input->post('adjustment_desc');
				$adjustment_nominal = $this->input->post('adjustment_nominal');
				
				$order = array(
							   'billing_id' => $billing_id,
							   'shipper_id' => $shipper_id,
							   'order_date' => date('Y-m-d', strtotime($this->input->post('order_date'))),
							   'expedition' => $this->input->post('expedition'),
							   'service' => $this->input->post('service'),
							   'exp_cost' => $exp_cost,
							   'discount_amount' => $discount_amount,
							   'bank_account_id' => $this->input->post('bank_account_id'),
							   'order_status' => $this->input->post('order_status'),
							   'package_status' => $this->input->post('package_status'),
							   'purchase_date' => date('Y-m-d', strtotime($this->input->post('purchase_date'))),
							   'purchase_nominal_cash' => $this->input->post('purchase_nominal_cash'),
							   'purchase_nominal_credit' => $this->input->post('purchase_nominal_credit'),
							   'order_channel' => $this->input->post('order_channel')
						);
				$this->db->insert('orders', $order); 
				$order_id = $this->db->insert_id();
				
				// CART
				$cogsAmount = 0;
				$totalCogsAmount = 0;
				$totalOrderedQty = 0;
				// masukkan produk kedalam tb_cart
				$this->db->join('tb_product', 'tb_product.product_id = tb_stock.product_id');
				$queryAllStock = $this->db->get('tb_stock');
				if ($queryAllStock->num_rows > 0) {
					foreach ($queryAllStock->result() as $rowStock) {
						
						$purchase_qty = $this->input->post('qty'.$rowStock->stock_id);
						$choosenPriceAmount = 0;
						// SPECIAL PRICES >>>>
						if ($this->input->post('special_price') == true) {
							$choosenPriceAmount = $rowStock->current_special_price;
						}
						
						// WHOLESALE PRICES >>>>
						if ($this->input->post('wholesale_price') == true) {
							$choosenPriceAmount = $rowStock->current_wholesale_price;							
						}
						
						// cart amount per stock
						$cartAmount = $purchase_qty * $choosenPriceAmount;
						//$cogsAmount = $purchase_qty * $rowStock->stock_cogs;
						
						// UPDATE CART - ($stock_id, $order_id, $qty, $amount, $date)
						$this->add_to_cart($rowStock->stock_id, $order_id, $purchase_qty, $cartAmount, date('Y-m-d', strtotime($this->input->post('order_date'))));
						
						// INVENTORY COGS
						$inventory_cogs = 0;
						//echo '<br/> stock id : '.$rowStock->stock_id;
						
						$this->db->where('stock_id', $rowStock->stock_id);
						$this->db->order_by('inventory_date');
						$queryInventory = $this->db->get('tb_inventory');
						if ($queryInventory->num_rows > 0) {
							
							$this->db->where('stock_id', $rowStock->stock_id);
							$this->db->where('order_id', $order_id);
							$cartContainThisStock = $this->db->get('tb_cart');
							
							if ($cartContainThisStock->num_rows > 0) {
								//echo ': exist';
								$cart_qty = $purchase_qty;
								//echo '<br/> cart_qty 1: '.$cart_qty;
								
								//echo '<br/> inventory_cogs before: '.$inventory_cogs;
								foreach ($queryInventory->result() as $rowInventory) {
									//echo '<br/> inventory_cogs run : '.$inventory_cogs;
									//echo '<br/> cart_qty run : '.$cart_qty;
									
									if ($cart_qty > 0 && $cart_qty > $rowInventory->inventory_qty_init) { // 7 > 5
										$inventory_cogs = $inventory_cogs + ($rowInventory->inventory_qty_init * $rowInventory->inventory_cogs);
										$cart_qty = $cart_qty - $rowInventory->inventory_qty_init;
									} else {
										$inventory_cogs = $inventory_cogs + ($cart_qty * $rowInventory->inventory_cogs);
										$cart_qty = 0;
										break;
									}
									
									
								}
								
								//echo '<br/> inventory_cogs after: '.$inventory_cogs;
								$totalOrderedQty = $totalOrderedQty + $purchase_qty;
								$totalCogsAmount = $totalCogsAmount + $inventory_cogs;
								//echo '<br/> totalCogsAmount 1: '.$totalCogsAmount;
							} else {
								//echo ': not exist';
							}
						}
					}
				}
				
				// ==== INVENTORY ===============
				//echo ' totalCogsAmount 2: '.$totalCogsAmount;
				
				$this->db->where('order_id', $order_id);
				$query = $this->db->get('tb_inventory');
				$inv = array (
					'inventory_date' => date('Y-m-d', strtotime($this->input->post('purchase_date'))),	
					'inventory_desc' => "Order by ".$this->input->post('billing_name'),
					'inventory_qty' =>  $totalOrderedQty,
					'inventory_nominal' => '-'.$totalCogsAmount,
					'inventory_type_id' => 24,
					'order_id' => $order_id
				);
					
				if ($query->num_rows == 0) {
					$this->db->insert('tb_inventory', $inv);
				} else {
					$this->db->where('order_id', $order_id);
					$this->db->update('tb_inventory', $inv);
				}
				// ===============================
				
				// ======== COLLECTING CART AMOUNT IN SAME ORDER ============
				$totalCartAmount = 0;
				
				$totalChossenPriceAmount = 0;
				
				$this->db->where('order_id', $order_id);
				$this->db->join('tb_stock', 'tb_stock.stock_id = tb_cart.stock_id');
				$this->db->join('tb_product', 'tb_product.product_id = tb_stock.product_id');
				$queryCart = $this->db->get('tb_cart');
				
				if ($queryCart->num_rows > 0) {
					foreach ($queryCart->result() as $rowCart) {
						// SALES >>>>
						$cartAmount = $rowCart->cart_amount;
						$totalCartAmount += $cartAmount;
						
						// COGS >>>>
						
						// SPECIAL PRICES >>>>
						if ($this->input->post('special_price') == true) {
							$choosenPriceAmount = $rowCart->cart_qty * $rowCart->current_special_price;
							$totalChossenPriceAmount += $choosenPriceAmount;
						}
						
						// WHOLESALE PRICES >>>>
						if ($this->input->post('wholesale_price') == true) {
							$choosenPriceAmount = $rowCart->cart_qty * $rowCart->current_wholesale_price;
							$totalChossenPriceAmount += $choosenPriceAmount;
						}
					}

				}
				
				// GET THE AUTOMATIC DISCOUNT
				// x = 1000000 - 750000 = 250000
				$discount_amount = $totalCartAmount - $totalChossenPriceAmount;
				
				// UPDATE ORDER - TOTAL CART AMOUNT & TOTAL AMOUNT
				$totalAmount = $totalCartAmount + $exp_cost - $discount_amount + $adjustment_nominal;
				
				// PRICE LEVEL
				$price_level = 0;
				if ($this->input->post('special_price') == true) {$price_level = 1;}
				if ($this->input->post('wholesale_price') == true) {$price_level = 2;}
				
				$order = array(
							   'product_amount' => $totalCartAmount,
							   'total_amount' => $totalAmount,
							   'discount_amount' => $discount_amount,
							   'price_level' => $price_level,
							   'adjustment_desc' => $adjustment_desc,
							   'adjustment_nominal' => $adjustment_nominal
						);
				$this->db->where('id', $order_id);
				$this->db->update('orders', $order); 
				
				// : : : START PAYMENT PROCESS
				$payment_process_var = array(
						'payment_method' => $this->input->post('payment_method'),
						'order_id' => $order_id,
						'billing_id' => $billing_id,
						'shipper_id' => $shipper_id,
						'billing_name' => $this->input->post('billing_name'),
						'purchase_date' => $this->input->post('purchase_date'),
						'total_amount' => $totalAmount,
						'order_status' => $this->input->post('order_status'),
						'purchase_nominal_cash' => $this->input->post('purchase_nominal_cash'),
						'purchase_nominal_credit' => $this->input->post('purchase_nominal_credit'),
						'bank_account_id' => $this->input->post('bank_account_id')
				);
				
				$this->do_payment_process($payment_process_var);
				// : : : END PAYMENT PROCESS
				
				// add to activity
				$session_data = $this->session->userdata('logged_in');
				$nominal_sales = $totalCartAmount - $discount_amount;
				$activity_desc = 'ORDER - Add New [ORDER ID : <b>'.$order_id.'</b> | TO <b>'.$this->input->post('billing_name').'</b> FROM <b>'.$this->input->post('shipper_name').'</b> | 
									NOMINAL : <b>'.$nominal_sales.'</b>]';
				$this->activity_model->add_activity($session_data['user_id'], $activity_desc);
				
				$msg .= '<p>Order successfully added.</p>';
				$this->session->set_flashdata('success_message',$msg);	
				
				if ($_POST['action'] == 'SUBMIT') {
					redirect(site_url('order'));	
				} else if ($_POST['action'] == 'APPLY') {
					redirect(base_url().'index.php/order/update/'.$order_id);
				}
			
		} else {
			 redirect(site_url('login'));
		}
	}
	
	public function doUpdate() {
	if($this->session->userdata('logged_in')) {	
				$this->load->helper('string');
				
				$order_id = $this->input->post('order_id');
				$msg = '';
				// masukkan billing info		
				$billing = array(
							   'billing_name' => $this->input->post('billing_name'),
							   'billing_street' => $this->input->post('billing_street'),
							   'billing_kec' => $this->input->post('billing_kec'),
							   'billing_prov' => $this->input->post('billing_prov'),
							   'billing_postal_code' => $this->input->post('billing_postal_code'),
							   'billing_phone' => $this->input->post('billing_phone'),
							   'billing_kec' => $this->input->post('billing_kec'),
							   'billing_city' => $this->input->post('billing_city'),
							   'billing_country' => $this->input->post('billing_country'),
							   'billing_level' => $this->input->post('billing_level')
						);
						
				$this->db->where('billing_id', $this->input->post('billing_id'));
				$this->db->update('billing', $billing); 
				$billing_id = $this->input->post('billing_id');
				
				// masukkan shipping info
				$shipper_id = $this->input->post('shipper_id');
				$shipper_alias_name = $this->input->post('shipper_alias_name');
				if (!empty($shipper_alias_name)) {
					$shipper_alias = array('billing_alias_name' => $this->input->post('shipper_alias_name'));
					$this->db->where('billing_id', $shipper_id);
					$this->db->update('billing', $shipper_alias); 
				}
				
				// masukkan order
				//$invoice
				$discount_amount = $this->input->post('discount_amount');
				$exp_cost = $this->input->post('exp_cost');
				$adjustment_desc = $this->input->post('adjustment_desc');
				$adjustment_nominal = $this->input->post('adjustment_nominal');
				
				$order = array(
							   'billing_id' => $billing_id,
							   'shipper_id' => $shipper_id,
							   'order_date' => date('Y-m-d', strtotime($this->input->post('order_date'))),
							   'expedition' => $this->input->post('expedition'),
							   'service' => $this->input->post('service'),
							   'exp_cost' => $this->input->post('exp_cost'),
							   'discount_amount' => $this->input->post('discount_amount'),
							   'bank_account_id' => $this->input->post('bank_account_id'),
							   'order_status' => $this->input->post('order_status'),
							   'package_status' => $this->input->post('package_status'),
							   'purchase_date' => date('Y-m-d', strtotime($this->input->post('purchase_date'))),
							   'purchase_nominal_cash' => $this->input->post('purchase_nominal_cash'),
							   'purchase_nominal_credit' => $this->input->post('purchase_nominal_credit'),
							   'order_channel' => $this->input->post('order_channel')
						);
				$this->db->where('id', $this->input->post('order_id'));
				$this->db->update('orders', $order); 
				
				// CART
				$cogsAmount = 0;
				$totalCogsAmount = 0;
				$totalOrderedQty = 0;
				// masukkan produk kedalam tb_cart
				$this->db->join('tb_product', 'tb_product.product_id = tb_stock.product_id');
				$queryStock = $this->db->get('tb_stock');
						
				if ($queryStock->num_rows > 0) {
					
					foreach ($queryStock->result() as $rowStock) {
						
						$purchase_qty = $this->input->post('qty'.$rowStock->stock_id);
						$choosenPriceAmount = 0;
						// SPECIAL PRICES >>>>
						if ($this->input->post('special_price') == true) {
							$choosenPriceAmount = $rowStock->current_special_price;
						}
						
						// WHOLESALE PRICES >>>>
						if ($this->input->post('wholesale_price') == true) {
							$choosenPriceAmount = $rowStock->current_wholesale_price;							
						}
						
						// cart amount per stock
						$cartAmount = $purchase_qty * $choosenPriceAmount;
						//$cogsAmount = $purchase_qty * $rowStock->stock_cogs;
						
						// UPDATE CART - ($stock_id, $order_id, $qty, $amount, $date)
						//echo 'cogs before add to cart : '.$totalCogsAmount;
						
						$this->add_to_cart($rowStock->stock_id, $order_id, $purchase_qty, $cartAmount, date('Y-m-d', strtotime($this->input->post('order_date'))));
						
						// INVENTORY COGS
						$inventory_cogs = 0;
						//echo '<br/> stock id : '.$rowStock->stock_id;
						
						$this->db->where('stock_id', $rowStock->stock_id);
						$this->db->order_by('inventory_date');
						$queryInventory = $this->db->get('tb_inventory');
						if ($queryInventory->num_rows > 0) {
							
							$this->db->where('stock_id', $rowStock->stock_id);
							$this->db->where('order_id', $order_id);
							$cartContainThisStock = $this->db->get('tb_cart');
							
							if ($cartContainThisStock->num_rows > 0) {
								//echo ': exist';
								$cart_qty = $purchase_qty;
								//echo '<br/> cart_qty 1: '.$cart_qty;
								
								//echo '<br/> inventory_cogs before: '.$inventory_cogs;
								foreach ($queryInventory->result() as $rowInventory) {
									//echo '<br/> inventory_cogs run : '.$inventory_cogs;
									//echo '<br/> cart_qty run : '.$cart_qty;
									
									if ($cart_qty > 0 && $cart_qty > $rowInventory->inventory_qty_init) { // 7 > 5
										$inventory_cogs = $inventory_cogs + ($rowInventory->inventory_qty_init * $rowInventory->inventory_cogs);
										$cart_qty = $cart_qty - $rowInventory->inventory_qty_init;
									} else {
										$inventory_cogs = $inventory_cogs + ($cart_qty * $rowInventory->inventory_cogs);
										$cart_qty = 0;
										break;
									}
									
									
								}
								
								//echo '<br/> inventory_cogs after: '.$inventory_cogs;
								$totalOrderedQty = $totalOrderedQty + $purchase_qty;
								$totalCogsAmount = $totalCogsAmount + $inventory_cogs;
								//echo '<br/> totalCogsAmount 1: '.$totalCogsAmount;
							} else {
								//echo ': not exist';
							}
						}
					}
				}
				
				// ==== INVENTORY ===============
				//echo ' totalCogsAmount 2: '.$totalCogsAmount;
				
				$this->db->where('order_id', $order_id);
				$query = $this->db->get('tb_inventory');
				$inv = array (
					'inventory_date' => date('Y-m-d', strtotime($this->input->post('purchase_date'))),	
					'inventory_desc' => "Order by ".$this->input->post('billing_name'),
					'inventory_qty' =>  $totalOrderedQty,
					'inventory_nominal' => '-'.$totalCogsAmount,
					'inventory_type_id' => 24,
					'order_id' => $order_id
				);
					
				if ($query->num_rows == 0) {
					$this->db->insert('tb_inventory', $inv);
				} else {
					$this->db->where('order_id', $order_id);
					$this->db->update('tb_inventory', $inv);
				}
				// ===============================
				
				// ======== COLLECTING CART AMOUNT IN SAME ORDER ============
				$totalCartAmount = 0;
				//$totalCogsAmount = 0;
				$totalChossenPriceAmount = 0;
				
				$this->db->where('order_id', $order_id);
				$this->db->join('tb_stock', 'tb_stock.stock_id = tb_cart.stock_id');
				$this->db->join('tb_product', 'tb_product.product_id = tb_stock.product_id');
				$queryCart = $this->db->get('tb_cart');
				
				if ($queryCart->num_rows > 0) {
					foreach ($queryCart->result() as $rowCart) {
						// SALES >>>>
						$cartAmount = $rowCart->cart_amount;
						$totalCartAmount += $cartAmount;
						
						// SPECIAL PRICES >>>>
						if ($this->input->post('special_price') == true) {
							$choosenPriceAmount = $rowCart->cart_qty * $rowCart->current_special_price;
							$totalChossenPriceAmount += $choosenPriceAmount;
						}
						
						// WHOLESALE PRICES >>>>
						if ($this->input->post('wholesale_price') == true) {
							$choosenPriceAmount = $rowCart->cart_qty * $rowCart->current_wholesale_price;
							$totalChossenPriceAmount += $choosenPriceAmount;
						}
					}

				}				
				
				
				// GET THE AUTOMATIC DISCOUNT
				// x = 1000000 - 750000 = 250000
				$discount_amount = $totalCartAmount - $totalChossenPriceAmount;
				
				// GET TOTAL AMOUNT
				$totalAmount = $totalCartAmount + $exp_cost - $discount_amount + $adjustment_nominal;
				
				
				// PRICE LEVEL
				$price_level = 0;
				if ($this->input->post('special_price') == true) {$price_level = 1;}
				if ($this->input->post('wholesale_price') == true) {$price_level = 2;}
				
				$data = array(
						'product_amount' => $totalCartAmount,
						'total_amount' => $totalAmount,
						'discount_amount' => $discount_amount,
						'price_level' => $price_level,
						'adjustment_desc' => $adjustment_desc,
						'adjustment_nominal' => $adjustment_nominal
				);
				
				$this->db->where('id', $order_id);
				$this->db->update('orders', $data);

				$payment_process_var = array(
						'payment_method' => $this->input->post('payment_method'),
						'order_id' => $order_id,
						'billing_id' => $billing_id,
						'shipper_id' => $shipper_id,
						'billing_name' => $this->input->post('billing_name'),
						'purchase_date' => $this->input->post('purchase_date'),
						'total_amount' => $totalAmount,
						'order_status' => $this->input->post('order_status'),
						'purchase_nominal_cash' => $this->input->post('purchase_nominal_cash'),
						'purchase_nominal_credit' => $this->input->post('purchase_nominal_credit'),
						'bank_account_id' => $this->input->post('bank_account_id')
				);
				
				$this->do_payment_process($payment_process_var);
				
				// add to activity
				$session_data = $this->session->userdata('logged_in');
				$nominal_sales = $totalCartAmount - $discount_amount;
				$activity_desc = 'ORDER - Update [ORDER ID : <b>'.$order_id.'</b> | TO <b>'.$this->input->post('billing_name').'</b> FROM <b>'.$this->input->post('shipper_name').'</b> | 
									NOMINAL : <b>'.$nominal_sales.'</b>]';
				$this->activity_model->add_activity($session_data['user_id'], $activity_desc);
				
				
				redirect(site_url('order'));
				
		} else {
			 redirect(site_url('login'));
		}
	}
	
	private function do_payment_process($var) {
		
		// =================================================================================================
		// === START PAYMENT PROCESS
		/* VAR
			- payment method
			- billing id
			- order id
			- shipper
			- billing_name
			- purchase date
			- total amount
			- order status
			- purchase nominal cash
			- purchase nominal credit
			- bank account id
		*/
		// =================================================================================================
		/*
		echo 'PM '.$var['payment_method'].' | ';
		echo 'BI '.$var['billing_id'].' | ';
		echo 'SH '.$var['shipper_id'].' | ';
		echo 'OR '.$var['order_id'].' | ';
		echo 'BN '.$var['billing_name'].' | ';
		echo 'PD '.$var['purchase_date'].' | ';
		echo 'TA '.$var['total_amount'].' | ';
		echo 'OS '.$var['order_status'].' | ';
		echo 'PN '.$var['purchase_nominal_cash'].' | ';
		echo 'PC '.$var['purchase_nominal_credit'].' | ';
		echo 'BA '.$var['bank_account_id'].' | ';
		*/
		// ========= JIKA BAYAR VIA WALLET =========
		
		if ($var['payment_method'] == 'WALLET') {
			
			$wallet = null;
			$this->db->where('billing_id', $var['billing_id']);
			$this->db->where('billing_level', 47); // AGEN
			$billing = $this->db->get('billing');
			if ($billing->num_rows > 0) {
				$wallet = array(
				   'billing_id' => $var['billing_id'],
				   'order_id' => $var['order_id']
				);
			} else {
				// Its a dropship transaction
				$wallet = array(
				   'billing_id' => $var['shipper_id'],
				   'order_id' => $var['order_id']
				);
			}
			
			// INSERT WALLET OF AGENT
			$this->db->where('order_id', $var['order_id']);
			$query_wallet = $this->db->get('tb_wallet');
			if ($query_wallet->num_rows == 0) {
				$this->db->insert('tb_wallet', $wallet); 
			} else {
				$this->db->where('order_id', $var['order_id']);
				$query_wallet = $this->db->get('tb_wallet');
				if ($wallet->num_rows > 0) {
					$this->db->update('tb_wallet', $wallet); 
				}
			}
				
			// INCOME REVENUE
			$this->db->where('income_type_id', 23);
			$this->db->where('order_id', $var['order_id']);
			$query = $this->db->get('tb_income');
				
			$omset = array (
				'income_desc' => "Order by ".$var['billing_name'],
				'income_date' => date('Y-m-d', strtotime($var['purchase_date'])),
				'income_nominal' => $var['total_amount'],
				'income_type_id' => 23,
				'order_id' => $var['order_id']
			);
						
			if ($query->num_rows == 0) {
				$this->db->insert('tb_income', $omset);
			} else {
				$this->db->where('income_type_id', 23);
				$this->db->where('order_id', $var['order_id']);
				$this->db->update('tb_income',$omset);
			}
						
			// ERASE CASH & ACREC
			// ERASE CASH
			$this->db->where('order_id', $var['order_id']);
			$cash = $this->db->get('tb_cash');
			if ($cash->num_rows > 0) {
				$this->db->delete('tb_cash', array('order_id' => $var['order_id'])); 
			}
			
			// ERASE ACREC
			$this->db->where('order_id', $var['order_id']);
			$cash = $this->db->get('tb_acrec');
			if ($cash->num_rows > 0) {
				$this->db->delete('tb_acrec', array('order_id' => $var['order_id'])); 
			}
		} else if ($var['payment_method'] == 'ATM_TRANSFER') {
			// ========= JIKA BAYAR VIA ATM =========
					
			$this->db->where('id', $var['order_id']);
			$queryOrder = $this->db->get('orders');
			
			$row = $queryOrder->row();	
			// masukkan ke dalam income ,cash dan inventory bila order sudah dibayar
			if ($row->order_status >= '1') {
				
				// INCOME REVENUE
				$this->db->where('income_type_id', 23);
				$this->db->where('order_id', $var['order_id']);
				$query = $this->db->get('tb_income');
					
				$omset = array (
					'income_desc' => "Order by ".$var['billing_name'],
					'income_date' => date('Y-m-d', strtotime($var['purchase_date'])),
					'income_nominal' => $var['total_amount'],
					'income_type_id' => 23,
					'order_id' => $var['order_id']
				);
							
				if ($query->num_rows == 0) {
					$this->db->insert('tb_income', $omset);
				} else {
					$this->db->where('income_type_id', 23);
					$this->db->where('order_id', $var['order_id']);
					$this->db->update('tb_income',$omset);
				}
					
				if ($var['purchase_nominal_cash'] > 0) {
					// CASH
					$this->db->where('order_id', $var['order_id']);
					$query = $this->db->get('tb_cash');
					
					$cash = array (
						'cash_date' => date('Y-m-d', strtotime($var['purchase_date'])),	
						'cash_desc' => "Order by ".$var['billing_name'],
						'cash_nominal' => $var['purchase_nominal_cash'],
						'cash_type_id' => 23,
						'bank_account_id' => $var['bank_account_id'],
						'order_id' => $var['order_id']
					);
								
					if ($query->num_rows == 0) {
						$this->db->insert('tb_cash', $cash);
					} else {
						$this->db->where('order_id', $var['order_id']);
						$this->db->update('tb_cash',$cash);
					}
				}	
							
				if ($var['purchase_nominal_credit'] > 0) {
					// ACC RECEIVE
					$this->db->where('order_id', $var['order_id']);
					$query = $this->db->get('tb_acrec');
					$acc_receive = array (
						'acrec_date' => date('Y-m-d', strtotime($var['purchase_date'])),	
						'acrec_desc' => "Order by ".$var['billing_name'],
						'acrec_nominal' => $var['purchase_nominal_credit'],
						'acrec_type_id' => 23,
						'order_id' => $var['order_id']
					);
					
					if ($query->num_rows == 0) {
						$this->db->insert('tb_acrec', $acc_receive);
					} else {
						$this->db->where('order_id', $var['order_id']);
						$this->db->update('tb_acrec',$acc_receive);
					}
				}				
			}
			
			// DELETE TRX ON WALLET
			$this->db->where('order_id', $var['order_id']);
			$wallet = $this->db->get('tb_wallet');
			if ($wallet->num_rows != 0) {
				$this->db->delete('tb_wallet', array('order_id' => $var['order_id'])); 
			}
		}
		$msg = '<p>Order successfully edited.</p>';
		$this->session->set_flashdata('success_message',$msg);
				
				
		// =================================================================================================
		// : : : END PAYMENT PROCESS
		// =================================================================================================
				
	}
	
	public function add() {
		if($this->session->userdata('logged_in')) {	
			
			// get inventory
			$this->db->order_by('stock_desc');
			$this->db->where('tb_product.status',1);
			$this->db->where('stock_qty >',0);
			$this->db->join('tb_product', 'tb_product.product_id = tb_stock.product_id','left');
			$data['stock']=$this->db->get('tb_stock')->result();
			
			// get billing level
			$this->db->where('option_type', 'BILL_LV');
			$this->db->order_by('option_desc');
			$data['billing_level'] = $this->db->get('tb_options')->result();
			
			// get order channel
			$this->db->where('option_type', 'ORD_CHANNEL');
			$this->db->order_by('option_desc');
			$data['order_channel'] = $this->db->get('tb_options')->result();
			
			// get Provinsi
			$this->db->where('option_type', 'PROVINCE');
			$this->db->order_by('option_desc');
			$data['provinces'] = $this->db->get('tb_options')->result();
			
			// get shipper
			$this->db->where('billing_level', 47);
			$this->db->where('billing_flag1 <>', 103);
			$this->db->order_by('billing_name');
			$data['shippers'] = $this->db->get('billing')->result();
			
			// get bank account
			$this->db->order_by('bank_account_name');
			$data['bank_account']=$this->db->get('bank_account')->result();
			
			// get liabilities
			$this->db->where('option_code','ACC_PAY');
			$this->db->order_by('option_desc');
			$data['liabilities_type'] = $this->db->get('tb_options')->result();
			
			
			// get zero billing
			$data['billing'] = null;
			$data['last_order'] = null;
			$data['old_shipper'] = null;
			
			$data['page'] = "orderAdd";
			$this->load->view('dashboard',$data);
		} else {
			 redirect(site_url('login'));
		}
	}
	
	public function update() {
		if($this->session->userdata('logged_in')) {	
			$data['page'] = "orderUpdate";
			// get order data
			
			$data['orders']=$this->db->select('*, orders.id AS main_order_id, orders.shipper_id as shipper_id,
							ship.billing_alias_name as ship_alias_name,
							
							billing.billing_id as bill_id,
							billing.billing_name as bill_name,
							billing.billing_street as bill_street,
							billing.billing_kec as bill_kec,
							billing.billing_city as bill_city,
							billing.billing_prov as bill_prov,
							billing.billing_postal_code as bill_postal_code,
							billing.billing_country as bill_country,
							billing.billing_phone as bill_phone,
							billing.billing_level as bill_level
							');
			$data['orders']=$this->db->from('orders');
			$data['orders']=$this->db->join('billing', 'orders.billing_id = billing.billing_id','left');
			$data['orders']=$this->db->join('billing as ship', 'orders.shipper_id = ship.billing_id','left');
			$data['orders']=$this->db->join('bank_account', 'orders.bank_account_id = bank_account.id','left');
			$data['orders']=$this->db->join('tb_options AS liabilities_option', 'liabilities_option.option_id = orders.liabilities_type_id','left');
			$data['orders']=$this->db->where('orders.id',$this->uri->segment(3));
			$data['orders']=$this->db->get()->result();
			
			$this->db->where('id',$this->uri->segment(3));
			$query=$this->db->get('orders');
			$order = $query->row();
			
			if ($query->num_rows == 0) {
				$msg = '<p>Order tidak ditemukan.</p>';
				$this->session->set_flashdata('error_message',$msg);	
				redirect(site_url('orderAction'));
			}
			
			
			$this->db->select('*');
			
			$this->db->where('tb_stock.stock_id in (select cart.stock_id from tb_cart as cart where cart.order_id = '.$order->id.') OR 
							  tb_stock.stock_qty > 0', NULL, false);
			$this->db->where('tb_product.status',1);
			$this->db->join('tb_product', 'tb_product.product_id = tb_stock.product_id');
			$this->db->order_by('tb_stock.stock_desc');
			$data['stock']=$this->db->get('tb_stock')->result();
			
			// get estimated weight of cart
			$data['total_weight']=$this->OrderModel->get_estimated_weight($order->id);
			
			// get tb_cart
			$this->db->where('order_id',$order->id);
			$data['cart']=$this->db->get('tb_cart')->result();
			
			// get bank account
			$this->db->order_by('bank_account_name');
			$data['bank_account']=$this->db->get('bank_account')->result();
			
			// get billing level
			$this->db->where('option_type', 'BILL_LV');
			$this->db->order_by('option_desc');
			$data['billing_level'] = $this->db->get('tb_options')->result();
			
			// get Provinsi
			$this->db->where('option_type', 'PROVINCE');
			$this->db->order_by('option_desc');
			$data['provinces'] = $this->db->get('tb_options')->result();
			
			// get shipper
			$this->db->where('billing_level', 47);
			$this->db->where('billing_flag1 <>', 103);
			$this->db->order_by('billing_name');
			$data['shippers'] = $this->db->get('billing')->result();
			
			// get order channel
			$this->db->where('option_type', 'ORD_CHANNEL');
			$this->db->order_by('option_desc');
			$data['order_channel'] = $this->db->get('tb_options')->result();
			
			// get wallet
			$this->db->where('order_id', $order->id);
			$wallet = $this->db->get('tb_wallet');
			$data['wallet_id'] = null; 
			if ($wallet->num_rows > 0) {
				$data['wallet_id'] = $wallet->row()->wallet_id;
			} 

			
			// get shipper from old table
			$data['old_shipper'] = '';
			$this->db->where('id', $order->id);
			$this->db->limit(1);
			$order = $this->db->get('orders');
			$shipper_id = $order->row()->shipper_id;
			
			$this->db->where('shipper_id', $shipper_id);
			$this->db->limit(1);
			$shipper = $this->db->get('shipper');
			$shipper_name = $shipper->row()->shipper_name;
			$data['old_shipper'] = $shipper_name;
			$this->load->view('dashboard',$data);
		} else {
			 redirect(site_url('login'));
		}
	}
	
	public function detail() {
		if($this->session->userdata('logged_in')) {	
			$data['page'] = "orderDetail";
			// get order data
			
			$data['orders']=$this->db->select('*');
			$data['orders']=$this->db->from('orders');
			$data['orders']=$this->db->join('billing', 'orders.billing_id = billing.billing_id');
			$data['orders']=$this->db->join('shipper', 'orders.shipper_id = billing.shipper_id');
			$data['orders']=$this->db->where('orders.id',$this->uri->segment(3));
			$data['orders']=$this->db->get()->result();
			
			$this->db->where('id',$this->uri->segment(3));
			$query=$this->db->get('orders');
			$order = $query->row();
			
			if ($query->num_rows == 0) {
				$msg = '<p>Order tidak ditemukan.</p>';
				$this->session->set_flashdata('error_message',$msg);	
				redirect(site_url('orderAction'));
			}
			
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
				   'billing_name' => $this->input->post('billing_name'),
				   'billing_phone' => $this->input->post('billing_phone'),
				   'billing_kec' => $this->input->post('billing_kec'),
				   'billing_city' => $this->input->post('billing_city')
			);
			
			$this->GenericModel->searchterm_handler($searchparam);
			
			redirect(site_url('order'));
		} else {
			 redirect(site_url('login'));
		}
	}
	
	
	public function index() {	
		if($this->session->userdata('logged_in')) {	
			$this->load->helper('form');
			$this->load->library('form_validation');
		
			// SEARCHING TERMS
			$searchterm = $this->session->userdata('searchterm');
			
			// Paging
			$config = array();
			$config["base_url"] = base_url("index.php/order/index");
			$config["total_rows"] = $this->OrderModel->record_count($searchterm);
			$config["per_page"] = 40;
			
			$this->pagination->initialize($config);
			$page = $this->uri->segment(3);
			
			$data['listOrderPending'] = $this->OrderModel->fetch_orders_pending($config["per_page"], $page, $searchterm);
			$data['listOrderComplete'] = $this->OrderModel->fetch_orders_complete($config["per_page"], $page, $searchterm);
			$data["links"] = $this->pagination->create_links();
			$data["row"] = 1+$page;
			
			$data['page']="orderList";
			
			$this->load->view('dashboard',$data);
			
		} else {
			 redirect(site_url('login'));
		}
	}
	
	function cetak_nota_dan_alamat() {
		$this->load->library("Pdf");
		
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);    
		
		
		$pdf->SetMargins(3, 2, PDF_MARGIN_RIGHT);
		
		// set some language-dependent strings (optional)
		if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
			require_once(dirname(__FILE__).'/lang/eng.php');
			$pdf->setLanguageArray($l);
		}   
	 
		// set default font subsetting mode
		$pdf->setFontSubsetting(true);   
	 
		// Set font
		$pdf->SetFont('dejavusans', '', 9, '', true);   
	 
		// Add a page
		$pdf->AddPage(); 
	 
		// FETCHING DATA
		$order_id = $this->uri->segment(3);
		$this->db->where('id', $order_id);
		$this->db->join('billing', 'billing.billing_id = orders.billing_id');
		$this->db->limit(1);
		$queryorder = $this->db->get('orders');
		
		$subtotal = 0;
		$diskon = $queryorder->row()->discount_amount;
		$ongkir = $queryorder->row()->exp_cost;
		$order_date = $queryorder->row()->order_date;
		$order_id = $order_id;
		$billing_name = $queryorder->row()->billing_name;
		//$order_date = date("Y-m-d", strtotime(($$queryorder->row()->order_date)));
		
		// ====================== CONTENT LOGIC ================================================
		
		/** $subtotal = 0;
		$diskon = $this->input->post('discount_amount');
		$ongkir = $this->input->post('exp_cost');
		$order_id = $this->input->post('order_id');
		$billing_name = $this->input->post('billing_name');
		$order_date = $this->input->post('order_date');
		**/
		$html= '';
		//echo 'masuk alamat';
		$html .= $this->alamat_html($order_id);
		//echo 'keluar alamat';
		$html .= '
		<table border="1px" width="270px" cellpadding="3px" style="margin-top: 5px;">
			<tr><td colspan="4" align="right">'.$billing_name.'</td></tr>
			<tr>
				<td colspan="2">Nota No : '.$order_id.'</td>
				<td colspan="2" align="right">Tanggal : '.date('d-M-Y', strtotime($order_date)).'</td>
			</tr>	
		
			<tr align="center">
				<td width="20%">Kode</td>
				<td width="80%" colspan="2">Nama Barang</td>
			</tr>';
		
		
		// masukkan produk kedalam tb_cart
		$this->db->where('order_id', $order_id);
		$this->db->join('tb_stock', 'tb_stock.stock_id = tb_cart.stock_id');
		$this->db->join('tb_product', 'tb_product.product_id = tb_stock.product_id');
		
		$queryCart = $this->db->get('tb_cart');
		
		if ($queryCart->num_rows > 0) {
			$total_qty = 0;
			$subtotal = 0;
			$cart = array(null);
			foreach ($queryCart->result() as $rowCart) {
				if ($rowCart->cart_qty > 0) {
					$cart_in_html = '<tr>
						<td align="center">'.$rowCart->product_code.'</td>
						<td colspan="2">'.$rowCart->cart_qty.' PCS &nbsp;'.$rowCart->product_name.' - '.$rowCart->stock_desc.'</td>
						</tr>';
								  
					array_push($cart, $cart_in_html);
				
					$total_qty = $total_qty + $rowCart->cart_qty;
				}
			}
			sort($cart);
			foreach ($cart as $row) {
				$html .= $row;
			}
		}
		
		
				  
		// Subtotal
		$html .= '<tr>
					<td colspan="2">'.'Total Barang '.'</td>
					<td align="center"><font size="12">'.$total_qty.'</font></td>
					
				  </tr>';
				  
		$html .= '</table>';
		
		
		// Print text using writeHTMLCell()
		$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);   
		// ---------------------------------------------------------    
		// Close and output PDF document
		// This method has several options, check the source code documentation for more information.
		$pdf->Output('example_002.pdf', 'I');    
	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */

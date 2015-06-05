<div class="pageicon"><span class="iconfa-laptop"></span></div>
<div class="pagetitle">
		<h1>Barang / Bahan Mentah</h1>
</div>
			<div class="maincontentinner">
				<div class="success message">
					<?php echo $this->session->flashdata('success_message'); ?>
				</div>
				<div class="error message">
					<?php echo $this->session->flashdata('error_message'); ?>
				</div>
			
				<form method="post" action="<?=base_url()?>index.php/material_inventory/search">
					<div id="wiz1step1" class="formwiz">
						<hr>
						<a href="<?=base_url()?>index.php/material_inventory/lists" title="Inventory Barang Mentah" class="btn">Inventory Barang Mentah</a>&nbsp;
						<a href="<?=base_url()?>index.php/on_process_inventory/lists" title="Inventory Barang Jadi" class="btn">Inventory Barang 1/2 Jadi</a>&nbsp;
						<a href="<?=base_url()?>index.php/inventory/lists" title="Inventory Barang Jadi" class="btn">Inventory Barang Jadi</a>&nbsp;
						
						<h4 class="widgettitle">&nbsp; Search</h4>
						<div class="widgetcontent">
							<ul class="search-field">
								<li>
									<label>Description</label>
										<span class="field">
										<input type="text" class="input-xlarge" name="inventory_desc" id="inventory_desc" /> 
									</span>
								</li>
								
							</ul>
							<div>
								<button class="btn btn-primary">Search</button>
							</div>							
						</div>
					</div>
				</form>
					<div style="float:left">
						<a href="<?=base_url()?>index.php/material_inventory/add" title="Add" class="btn btn_success">Add</a>&nbsp;
					</div>
					<?php echo $links; ?>
				
                <table width="100%" class="table table-bordered" id="dyntable">
                    <colgroup>
                        <col class="con0" style="align: center; width: 4%" />
                        <col class="con1" />
                        <col class="con0" />
                        <col class="con1" />
                        <col class="con0" />
                        <col class="con1" />
						<col class="con0" />
                        <col class="con1" />
						<col class="con0" />
                        <col class="con1" />
						<col class="con0" />
                    </colgroup>
                    <thead>
                        <tr>
                          	<th width="3%" class="head1 center">No</th>
							<th width="4%" class="head0 center nosort"><input type="checkbox" class="checkall" /></th>
							<th width="10%" class="head1 center">Kode</th>
							<th width="18%" class="head0 center">Nama Bahan</th>
							<th width="8%" class="head1 center">Tanggal Masuk</th>
							<th width="8%" class="head0 center">Ukuran saat masuk</th>
                            <th width="8%" class="head0 center">Sisa saat ini</th>
                            <th width="10%" class="head1 center">Type</th>
                        </tr>
                    </thead>
                    <tbody>
						
                    	<?php 
							if ($list_material_inventory == null) {
							?><tr class="gradeX"><td colspan="8"><font class="no-data-tabel">Data not found</font></td></tr><?php
							} else {
								$i = 1;
								foreach($list_material_inventory as $item):?>
                        <tr class="gradeX">
						
							<td class="center"><?=$row++;?></td>
							<td><?=$item->material_code?></td>
                            <td><?=$item->material_nama_bahan?></td>
							<td class="center"><?php echo date("d-M-Y", strtotime($item->material_date_init))?></td>
                            <td class="center"><?=$item->material_qty_init?></td>
							<td class="right nominal"><?=$item->material_qty?></td>
							<td class="center"><?=$item->option_desc?></td>
                        </tr>
                        <?php endforeach; 
						}?>
                    </tbody>
                </table>
				<p><?php echo $links; ?></p>
			

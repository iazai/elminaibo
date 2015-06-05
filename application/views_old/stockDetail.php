
<div class="pageicon"><span class="iconfa-laptop"></span></div>
<?php foreach ($stock as $item){ ?>	
<div class="pagetitle">
		<h1>Stock Detail <?=$item->product_name?> - <?=$item->stock_desc?></h1>
		<h4>Free : <?=$item->stock_qty?> </h4>
</div>

			<div class="maincontentinner">
				<div class="success message">
					<?php echo $this->session->flashdata('success_message'); ?>
				</div>
				<div class="error message">
					<?php echo $this->session->flashdata('error_message'); ?>
				</div>
			
				<?php $session_data = $this->session->userdata('logged_in'); ?>
				<div style="float:left">
					<a href="<?=base_url()?>index.php/stock/lists" title="Back" style = "color:#fff;" class="btn btn-success">Back</a>&nbsp;
				</div>
				<div>
					
				</div>

                <table width="100%" class="table table-bordered tablesorter" id="stockDetailTable" >
                    <colgroup>
                        <col class="con0" style="align: center; width: 4%" />
                        <col class="con1" />
                        <col class="con0" />
                        <col class="con1" />
                        <col class="con0" />
                        <col class="con1" />
						<col class="con0" />
						<col class="con0" />
                    </colgroup>
                    <thead>
                        <tr>
                          	<th width="2%" class="head1 center">No</th>
							<th width="8%" class="head1 center">Date</th>
							<th width="2%" class="head1 center">Re stock</th>
							<th width="2%" class="head0 center">Reject</th>
							<th width="2%" class="head1 center">Adj.</th>
							<th width="2%" class="head1 center">Keep</th>
							<th width="2%" class="head1 center">Sold</th>
							<th width="2%" class="head1 center">Final Stock</th>
                        </tr>
                    </thead>
                    <tbody>
					
						<tr class="gradeX">
							<form id="adjust_stock" name="adjust_stock" class="stdform" method="post" action="<?=base_url()?>index.php/stock/doAdjust/" />
							<input type="hidden" value="<?=$item->stock_id?>" name="stock_id"/>
							<td class="center">*</td>
		                    <td class="center"><input type="text" class="input-small validate[required]" name="general_date" id ="general_date"/></td>
							<td class="center"><input type="text" style="width:30px;" disabled name="restock_qty" id ="restock_qty"/></td>
							<td class="center"><input type="text" style="width:30px;" disabled name="reject_qty" id ="reject_qty"/></td>
							<td class="center"><input type="text" class="validate[required]" style="width:30px;" name="adj_qty" id ="adj_qty"/></td>
							<td class="center" colspan=3><button class="btn btn-primary" style="width:auto;">SUBMIT</button></td>
							</form>
                        </tr>
<?php } ?>
                    	<?php  if ($list_stock_detail == null) {
							?><tr class="gradeX"><td colspan="8"><font class="no-data-tabel">Data not found</font></td></tr><?php
							} else {
							$i = 1;
							foreach($list_stock_detail as $item) { ?>
                        
						<tr class="gradeX">
						
							<td class="center"><?=$i++;?></td>
		                    <td class="center"><?php echo date("d-M-Y", strtotime($item->general_date))?></td>
							
							<td class="center"><?=$item->restock_qty?></td>
							<td class="center"><?=$item->reject_qty?></td>
							<td class="center"><?=$item->adj_qty?></td>
							<td class="center"><?=$item->keeped_qty?></td>
							<td class="center"><?=$item->sold_qty?></td>
							<td class="center"><?=$item->free_qty?></td>
                        </tr>
                        <?php } }?>
						
						
                    </tbody>
                </table>
				
<script>
	$(document).ready(function(){
		$("#adjust_stock").validationEngine();
    });
	
	$(function() {
		$( "#general_date" ).datepicker({dateFormat: "dd-M-yy"});
	}); 
	
	$(document).ready(function() {
		$("stockDetailTable").tablesorter();
	});
	
</script>
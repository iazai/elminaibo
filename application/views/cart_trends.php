<script type="text/javascript" src="<?=base_url()?>assets/js/highcharts.js"></script>
<script type="text/javascript" src="<?=base_url()?>assets/js/jquery.highchartTable.js"></script>

<div class="pageicon"><span class="iconfa-laptop"></span></div>
<div class="pagetitle">
		<h1>Cart Trends</h1>
		<h5><?=$page_title?></h5>
</div>
			<div class="maincontentinner">
				<div class="success message">
					<?php echo $this->session->flashdata('success_message'); ?>
				</div>
				<div class="error message">
					<?php echo $this->session->flashdata('error_message'); ?>
				</div>
			
				
				<a href="<?=base_url()?>index.php/cart_stats/sold_restock" title="INVENTORY STATS" class="btn">INVENTORY STATS</a>&nbsp;
				<a href="<?=base_url()?>index.php/cart_stats/trends" title="TRENDS" class="btn">TRENDS</a>&nbsp;
						
				<form method="post" action="<?=base_url()?>index.php/cart_stats/trends">
					<div id="wiz1step1" class="formwiz span3">
					<hr>
										
						<h4 class="widgettitle">Filter</h4>
						<div class="widgetcontent">
							<label>		
								
								<span class="field">
									<input type="text" name="stockname" id="stockname" style="width:205px;" placeholder="Stock Description">
								</span>	
								
								<span class="field">
									<select name="interval" id="interval" style="width:219px;" class="validate[required]">
										<option value> Interval : </option>
										<option value="DAY">Daily</option>
										<option value="MONTH">Monthly</option>
									</select>
								</span>	
								
								<span class="field">
									<select name="group_by" id="group_by" style="width:219px;" class="validate[required]" onchange="chooseInventory()">
										<option value> Group By : </option>
										<option value="PRODUCT">Product</option>
										<option value="STOCK">Stock</option>
									</select>
								</span>
								
								<span class="field">
									<select name="product_id" id="product_id" style="width:219px; display:none;" class="validate[required]">
										<option value> - Produk - </option>
									<?php foreach($products as $product): ?>
										<option value="<?php echo $product->product_id?>"><?php echo $product->product_name?></option>
									<?php endforeach; ?>
									</select>
									
									<select name="stock_id" id="stock_id" style="width:219px; display:none;" class="validate[required]">
										<option value> - Stock - </option>
									<?php foreach($stocks as $stock): ?>
										<option value="<?php echo $stock->stock_id?>"><?php echo $stock->product_name?> - <?php echo $stock->stock_desc?></option>
									<?php endforeach; ?>
									</select>
								</span>
								
								<span class="field" style="padding-right:5px;padding-bottom:5px;float:left;">
									<div id="datepicker" style="border: 1px solid #0c57a3;"></div>
								</span>
								
								<span class="field">
									<input type="text" id="startdate" name="startdate" placeholder="Range Awal">
									<input type="text" id="enddate" name="enddate" placeholder="Range Akhir">
								</span>
								
								<p class="field">
									<button class="btn btn-primary">SUBMIT</button>
								</p>
							<div class="clear"></div>
							</label>
						</div>
					</div>
				</form>
			<div id="wiz1step1" class="formwiz span7">	
					<hr>
				<?php 
					$all_qty = 0;
					if ($list_trends != null) {
						foreach ($list_trends as $kuantitas) {
							$all_qty += $kuantitas->total_qty;
						}
				?>		
				    <h4 class="widgettitle"><center>Grafik Penjualan. Total terjual : <?=$all_qty?><center></h4>					
					<hr/>
				<?php } ?>
				
				<table width="100%" class="table table-bordered highchart" id="rosetatable" 
					data-graph-container-before="1" data-graph-type="line">
                    <colgroup>
                        <col class="con0" style="align: center; width: 4%" />
                        <col class="con1" />
						<col class="con0" />
                    </colgroup>
                    <thead>
                        <tr>
                          	<th width="25%" class="center">Time</th>
							<th width="10%" class="center">Sold Qty</th>
							<th width="10%" class="center">Omset</th>
                        </tr>
                    </thead>
                    <tbody>
					
						
					<?php 
						$row = 1;?>
						
					<?php 
						if ($list_trends == null) {
						?>
						<tr class="gradeX">
							<td class="center"  colspan='3'><font class="no-data-tabel"><-- Belom difilter!<font></td>
                        </tr>
					   <?php 
						} else {
							foreach ($list_trends as $item) {?>
						<tr class="gradeX">
							<td><?=$item->dates?></td>
							<td class="center sold"><?=$item->total_qty?></td>
							<td class="center sold"><?=$item->total_omset/100000?></td>
                        </tr>
					   <?php }} ?>
					   
                    </tbody>
                </table>
			</div>


<style type="text/css">
		.dp-highlight .ui-state-default {
			background: #478DD5;
			color: #FFF;
		}
</style>
<script type="text/javascript">
		/*
		 * jQuery UI Datepicker: Using Datepicker to Select Date Range
		 * http://salman-w.blogspot.com/2013/01/jquery-ui-datepicker-examples.html
		 */
		 
		$(function() {
			$("#datepicker").datepicker({
				beforeShowDay: function(date) {
					var date1 = $.datepicker.parseDate($.datepicker._defaults.dateFormat, $("#startdate").val());
					var date2 = $.datepicker.parseDate($.datepicker._defaults.dateFormat, $("#enddate").val());
					return [true, date1 && ((date.getTime() == date1.getTime()) || (date2 && date >= date1 && date <= date2)) ? "dp-highlight" : ""];
				},
				onSelect: function(dateText, inst) {
					var date1 = $.datepicker.parseDate($.datepicker._defaults.dateFormat, $("#startdate").val());
					var date2 = $.datepicker.parseDate($.datepicker._defaults.dateFormat, $("#enddate").val());
					if (!date1 || date2) {
						$("#startdate").val(dateText);
						$("#enddate").val("");
						$(this).datepicker("option", "minDate", dateText);
					} else {
						$("#enddate").val(dateText);
						$(this).datepicker("option", "minDate", null);
					}
				}
			});
		});

	function chooseInventory() {
        if (document.getElementById('group_by').value == 'PRODUCT') {
            document.getElementById('stock_id').style.display="none";
			document.getElementById('product_id').style.display="block";
			
        } else if (document.getElementById('group_by').value == 'STOCK') {
            document.getElementById('stock_id').style.display="block";
			document.getElementById('product_id').style.display="none";
        }
    }

	$(document).ready(function() {
	  $('table.highchart').highchartTable();
	});
</script>

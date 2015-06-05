	<div class="pageicon"><span class="iconfa-laptop"></span></div>
	<div class="pagetitle">
		<h1>&nbsp;Input Data Pembeli </h1>
	</div>
</div><!--pageheader-->
        
        <div class="maincontent">
            <div class="maincontentinner">
			    <!-- START OF DEFAULT WIZARD -->
				<div class="span8">
					<div class="widgetbox personal-information">
						<div class="error message">
							<?php echo $this->session->flashdata('validation_error_message'); ?>
							<?php echo $this->session->flashdata('error_message'); ?>
						</div>
						<div class="success message">
							<?php echo $this->session->flashdata('success_message'); ?>
						</div>
				
						<form id="customerForm" class="stdform" method="post" action="<?=base_url()?>index.php/customer/doAdd" />
							<div id="wiz1step1" class="formwiz">
								<h4 class="widgettitle">&nbsp; Detail List Building </h4>
								<div class="widgetcontent">
								<p>
									<label>Nama Penerima <font style="color:red;">*</font></label>
									<span class="field">
										<input type="text" class="input-xlarge validate[required]" name="billing_name" id="billing_name" /> 
									</span>
								</p>
								<p>
									<label>Alamat Jalan <font style="color:red;">*</font></label>
									<span class="field">
										<input type="text" class="input-xlarge validate[required]" name="billing_street" id="billing_street" /> 
									</span>
								</p>
								<p>
									<label>Kecamatan <font style="color:red;">*</font></label>
									<span class="field">
										<input type="text" class="input-xlarge validate[required]" name="billing_kec" id="billing_kec" /> 
									</span>
								</p>
								<p>
									<label>Kota / Kabupaten <font style="color:red;">*</font></label>
									<span class="field">
										<input type="text" class="input-xlarge validate[required]" name="billing_city" id="billing_city" /> 
									</span>
								</p>
								<p>
									<label>Provinsi <font style="color:red;">*</font></label>
									<span class="field">
										<input type="text" class="input-xlarge validate[required]" name="billing_prov" id="billing_prov" /> 
									</span>
									</p>
								<p>
									<label>Negara <font style="color:red;">*</font></label>
									<span class="field">
										<select name="billing_country" id="billing_country" style="width:200px;" class="validate[required]">
											<option value="Indonesia">Indonesia</option>
												<option value="Malaysia">Malaysia</option>
												<option value="Hong Kong">Hong Kong</option>
												<option value="Singapore">Singapore</option>
												<option value="Taiwan">Taiwan</option>
										</select>
									</span>
								</p>
								<p>
									<label>No Telp <font style="color:red;">*</font></label>
									<span class="field"><input type="text" name="billing_phone" id="billing_phone" class="input-medium validate[required],custom[phone]" /></span>
								</p>
								<p>
									<label>Level<font style="color:red;">*</font></label>
									<span class="field">
										<select name="billing_level" id="billing_level" class="input-xlarge">
											<option value=''>- Choose One -</option>
										<?php foreach($billing_level as $level): ?>
											<option value="<?php echo $level->option_id?>"><?php echo $level->option_desc?></option>
										<?php endforeach; ?>
										</select>
									</span>
								</p>
								<p>
									<label>Dropship ?<font style="color:red;">*</font></label>
									<span class="field">
										<input id="is_ds" name="is_ds" type="checkbox" onclick="validateDS()" />
									</span>
								</p>
								<div id="dropshipForm" style="display:none;">
									<p>
										<label><font style="color:blue;">Nama Agen</font></label>
										<span class="field">
											<select name="agent_id" id="agent_id" class="input-xlarge">
												<option value=''>- Choose Agent -</option>
											<?php foreach($agents as $agent): ?>
												<option value="<?php echo $agent->billing_id?>"><?php echo $agent->billing_name?></option>
											<?php endforeach; ?>
											</select>
										</span>
									</p>
								</div>
								<p class="stdformbutton">
									<button class="btn btn-primary">SUBMIT</button>
									<button type="reset" class="btn">RESET</button>
								</p>
							</div>
						</div><!--#wiz1step1-->
					</form>
</div>
				                        
<script>
    $(document).ready(function(){
		$("#billing_form_").validationEngine();
    });
	
	function validateDS() {
        if (document.getElementById('is_ds').checked) {
            document.getElementById('dropshipForm').style.display="block";
        } else {
            document.getElementById('dropshipForm').style.display="none";
        }
    }
</script>
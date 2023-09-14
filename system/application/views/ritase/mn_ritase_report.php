<script>
	jQuery.maxZIndex = jQuery.fn.maxZIndex = function(opt) {
	    var def = { inc: 10, group: "*" };
	    jQuery.extend(def, opt);
	    var zmax = 0;
	    jQuery(def.group).each(function() {
	        var cur = parseInt(jQuery(this).css('z-index'));
	        zmax = cur > zmax ? cur : zmax;
	    });
	    if (!this.jquery)
	        return zmax;
	
	    return this.each(function() {
	        zmax += def.inc;
	        jQuery(this).css("z-index", zmax);
	    });
	}
	jQuery(document).ready(
		function()
		{
			showclock();
			jQuery("#date").datepicker(
				{
							dateFormat: 'yy-mm-dd'
						, 	startDate: '1900/01/01'
						, 	showOn: 'button'
						//, 	changeYear: true
						//,	changeMonth: true
						, 	buttonImage: '<?=base_url()?>assets/images/calendar.gif'
						, 	buttonImageOnly: true
						,	beforeShow: 
								function() 
								{	
									jQuery('#ui-datepicker-div').maxZIndex();
								}
				}
			);
			
			jQuery("#enddate").datepicker(
				{
							dateFormat: 'yy-mm-dd'
						, 	startDate: '1900/01/01'
						, 	showOn: 'button'
						//, 	changeYear: true
						//,	changeMonth: true
						, 	buttonImage: '<?=base_url()?>assets/images/calendar.gif'
						, 	buttonImageOnly: true
						,	beforeShow: 
								function() 
								{	
									jQuery('#ui-datepicker-div').maxZIndex();
								}
				}
			);
		}
	);
	
	function page(p)
	{
		if(p==undefined){
			p=0;
		}
		jQuery("#offset").val(p);
		jQuery("#result").html('<img src="<?php echo base_url();?>assets/transporter/images/loader2.gif">');
		jQuery("#loader").show();
		jQuery.post("<?=base_url();?>transporter/ritase/ritase_report", jQuery("#frmsearch").serialize(),
			function(r)
			{
				jQuery("#loader").hide();
				jQuery("#result").html(r.html);				
			}
			, "json"
		);
	}
	
	
	
	function frmsearch_onsubmit()
	{
		jQuery("#loader").show();
		page(0);
		return false;
	}
	
	
	function excel_onsubmit(){
		jQuery("#loader2").show();
		jQuery.post("<?=base_url();?>report/ritase_report_excel", jQuery("#frmsearch").serialize(),
			function(r)
			{
				jQuery("#loader2").hide();
				if(r.success == true){
					jQuery("#frmreq").attr("src", r.filename);			
				}else{
					alert(r.errMsg);
				}	
			}
			, "json"
		);
		
		return false;
	}
	
	
	function order(by)
	{						
		if (by == jQuery("#sortby").val())
		{
			if (jQuery("#orderby").val() == "asc")
			{
				jQuery("#orderby").val("desc");
			}
			else
			{
				jQuery("#orderby").val("asc");
			}
		}
		else
		{
			jQuery("#orderby").val('asc')
		}
		
		jQuery("#sortby").val(by);
		page(0);
	}
	
</script>
<div style="position: absolute; margin: 0; padding: 0; z-index: 1000; width: 100%;"> 
<?=$navigation;?>
	<div id="main" style="margin: 20px;">
	<br />
	<div class="block-border">
		<form class="block-content form" name="frmsearch" id="frmsearch" onsubmit="javascript:return frmsearch_onsubmit()">
        <h1>Ritase Report</h1>
			<table width="100%" cellpadding="3" class="tablelist" style="font-size: 12px;">
				<tr>
					<td>Vehicle</td>
					<td>
						<select id="vehicle" name="vehicle">
							<?php for($i=0; $i < count($vehicle); $i++) { ?>									
							<option value="<?php echo $vehicle[$i]->vehicle_device; ?>">
                            <?php echo $vehicle[$i]->vehicle_name." ".$vehicle[$i]->vehicle_no; ?>
                            </option>
							<?php } ?>							
						</select>
					</td>
				</tr>
				<tr>
					<td>Ritase</td>
					<td>
						<select name="ritase" id="ritase">
							<?php 
								if (isset($ritase) && count($ritase)>0)
								{
									for ($i=0;$i<count($ritase);$i++)
									{
							?>
										<option value="<?php echo $ritase[$i]->ritase_id.",".$ritase[$i]->ritase_geofence_name;?>"><?php echo $ritase[$i]->ritase_geofence_name;?></option>
							<?php
									}
								}
							?>
						</select>
					</td>
					<td></td>
				</tr>
				<tr><td>&nbsp;</td></tr>
				<tr id="filterdatestartend">
					<td width="10%">Date</td>
					<td>
						<input type="text" name="date" id="date" class="date-pick" />
						to
						<input type="text" name="enddate" id="enddate" class="date-pick" />
					</td>
				</tr>
				<tr><td>&nbsp;</td></tr>
				<tr>
					<td style="border: 0px;">&nbsp;</td>
					<td style="border: 0px;"><input class="btn_search2" id="btnsearchreport" type="submit" value="Search" />
					<input class="btn_export" type="button" name="excel" id="btnexcelreport" value="Export To Excel" onclick="javascript:return excel_onsubmit()" />
                    <img id="loader2" style="display: none;" src="<?php echo base_url();?>assets/images/ajax-loader.gif" />
					<!--input type="button" name="pdf" value="Export To PDF" onclick="javascript:return pdf_onsubmit()" /-->
					</td>
				</tr>
			</table>
		</form>		
		<br />
		<div id="result"></div>		
		<iframe id="frmreq" style="display:none;"></iframe>	
	</div>
    </div>
</div>

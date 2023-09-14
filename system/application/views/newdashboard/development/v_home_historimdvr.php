<style media="screen">
  div#modalinformationdetail {
    max-height: 500px;
    width: 65%;
    overflow-x: auto;
    position: fixed;
    z-index: 9;
    background-color: #f1f1f1;
    text-align: left;
    border: 1px solid #d3d3d3;
  }

  .modalClient{
    z-index: 9999;
    width: 100%;
    margin: auto;
  }

  .modalMaterial{
    z-index: 9999;
    width: 100%;
    margin: auto;
  }

/* AUTOCOMPLETE STYLE */
* {
  box-sizing: border-box;
}

body {
  font: 16px Arial;
}

/*the container must be positioned relative:*/
.autocompleteno_lambung {
  position: relative;
  display: inline-block;
}

.autocomplete_client {
  position: relative;
  display: inline-block;
}

.autocomplete_material {
  position: relative;
  display: inline-block;
}

input {
  border: 1px solid transparent;
  background-color: #f1f1f1;
  padding: 10px;
  font-size: 16px;
}

input[type=text] {
  /* background-color: #f1f1f1; */
  width: 100%;
}

input[type=submit] {
  background-color: DodgerBlue;
  color: #fff;
  cursor: pointer;
}

/* AUTOCOMPLETE NO LAMBUNG */
.autocompleteno_lambung-items {
  position: absolute;
  border: 1px solid #d4d4d4;
  border-bottom: none;
  border-top: none;
  z-index: 99;
  /*position the autocomplete items to be the same width as the container:*/
  top: 100%;
  left: 0;
  right: 0;
}

.autocompleteno_lambung-items div {
  padding: 10px;
  cursor: pointer;
  background-color: #fff;
  border-bottom: 1px solid #d4d4d4;
}

/*when hovering an item:*/
.autocompleteno_lambung-items div:hover {
  background-color: #e9e9e9;
}

/*when navigating through the items using the arrow keys:*/
.autocompleteno_lambung-active {
  background-color: DodgerBlue !important;
  color: #ffffff;
}

/* AUTOCOMPLETE CLIENT */
.autocomplete_client-items {
  position: absolute;
  border: 1px solid #d4d4d4;
  border-bottom: none;
  border-top: none;
  z-index: 99;
  /*position the autocomplete items to be the same width as the container:*/
  top: 100%;
  left: 0;
  right: 0;
}

.autocomplete_client-items div {
  padding: 10px;
  cursor: pointer;
  background-color: #fff;
  border-bottom: 1px solid #d4d4d4;
}

/*when hovering an item:*/
.autocomplete_client-items div:hover {
  background-color: #e9e9e9;
}

/*when navigating through the items using the arrow keys:*/
.autocomplete_client-active {
  background-color: DodgerBlue !important;
  color: #ffffff;
}

/* AUTOCOMPLETE MATERIAL */
.autocomplete_material-items {
  position: absolute;
  border: 1px solid #d4d4d4;
  border-bottom: none;
  border-top: none;
  z-index: 99;
  /*position the autocomplete items to be the same width as the container:*/
  top: 100%;
  left: 0;
  right: 0;
}

.autocomplete_material-items div {
  padding: 10px;
  cursor: pointer;
  background-color: #fff;
  border-bottom: 1px solid #d4d4d4;
}

/*when hovering an item:*/
.autocomplete_material-items div:hover {
  background-color: #e9e9e9;
}

/*when navigating through the items using the arrow keys:*/
.autocomplete_material-active {
  background-color: DodgerBlue !important;
  color: #ffffff;
}
</style>
<script src="<?php echo base_url(); ?>assets/dashboard/assets/plugins/jquery/jquery.min.js"></script>


<div class="sidebar-container">
  <?=$sidebar;?>
</div>
<div class="page-content-wrapper">
  <div class="page-content" id="page-content-new">
    <div class="row">
      <div class="col-md-12 col-sm-12">
        <div class="panel" id="panel_form">
          <header class="panel-heading" style="background-color:#221f1f;color:white;">MDVR REPORT - DEVELOPMENT</header>
          <div class="panel-body" id="bar-parent10">
            <form class="form-horizontal form" name="frmsearch" id="frmsearch" onsubmit="javascript:return frmsearch_onsubmit()">
        			<input type="hidden" name="offset" id="offset" value=""/>
        			<input type="hidden" id="sortby" name="sortby" value=""/>
        			<input type="hidden" id="orderby" name="orderby" value=""/>

						<div class="form-group row">
								<label class="col-lg-3 col-md-3 control-label">Contractor
								</label>
								<div class="col-lg-4 col-md-3">
									<select class="form-control select2" id="company" name="company" onchange="getVehicleByContractor()">
										<option value="all" selected>--All Contractor--</option>
										<?php
										$ccompany = count($rcompany);
										for ($i = 0; $i < $ccompany; $i++) {

											echo "<option value='" . $rcompany[$i]->company_id . "'>" . $rcompany[$i]->company_name . "</option>";
										}
										?>
									</select>
								</div>
							</div>

							<div class="form-group row" id="mn_vehicle">
								<label class="col-lg-3 col-md-3 control-label">Vehicle
								</label>
								<div class="col-lg-4 col-md-3">
                  <select class="form-control select2" name="vehicle" id="vehicle">
                  </select>
								</div>
							</div>

              <div class="form-group row" id="mn_vehicle">
								<label class="col-lg-3 col-md-3 control-label">Frekeunsi MDVR Offline
								</label>
								<div class="col-lg-4 col-md-3">
									<select id="frekuensianomali" name="frekuensianomali" class="form-control select2 multi">
										<option value="all">--All Frekeunsi--</option>
                    <?php for ($i=0; $i < 25; $i++) {?>
                      <option value="<?php echo $i ?>"><?php echo $i ?></option>
                    <?php } ?>
									</select>
								</div>
							</div>

                  <div class="form-group row" id="mn_vehicle">
                    <label class="col-lg-3 col-md-3 control-label">Periode
                    </label>
                    <div class="col-lg-4 col-md-3">
                      <select id="periode" name="periode" class="form-control select2" onchange="javascript:periode_onchange()">
                        <option value="today">Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="last7">Last 7 Days</option>
                        <option value="last30">Last 30 Days</option>
                        <option value="custom">Custom Date</option>
                      </select>
                    </div>
                  </div>

                  <div class="form-group row" id="mn_sdate" style="display:none">
                    <label class="col-lg-3 col-md-4 control-label">Start Date
                    </label>
                    <div class="input-group date form_date col-md-4" data-date="" data-date-format="dd-mm-yyyy" data-link-field="dtp_input2" data-link-format="yyyy-mm-dd">
                      <input class="form-control" size="5" type="text" readonly name="startdate" id="startdate" value="<?=date('d-m-Y')?>">
                      <span class="input-group-addon"><span class="fa fa-calendar"></span></span>
                    </div>
                    <input type="hidden" id="dtp_input2" value="" />

                    <input type="hidden" id="dtp_input3" value="" />
                  </div>

                  <div class="form-group row" id="mn_edate" style="display:none">
                    <label class="col-lg-3 col-md-4 control-label">End Date
                    </label>
                    <div class="input-group date form_date col-md-4" data-date="" data-date-format="dd-mm-yyyy" data-link-field="dtp_input2" data-link-format="yyyy-mm-dd">
                      <input class="form-control" size="5" type="text" readonly name="enddate" id="enddate" value="<?=date('d-m-Y')?>">
                      <span class="input-group-addon"><span class="fa fa-calendar"></span></span>
                    </div>
                    <input type="hidden" id="dtp_input2" value="" />
                    <input type="hidden" id="dtp_input3" value="" />
                  </div>

								<div class="text-right">
                  <button class="btn btn-success btn-circle" id="btnsearchreport" type="submit"/>Search</button>
								</div>
        		</form>
          </div>
        <!-- <div id="loader2" class="mdl-progress mdl-js-progress mdl-progress__indeterminate"></div> -->
        <div id="loader2" style="display:none;" class="mdl-progress mdl-js-progress mdl-progress__indeterminate is-upgraded" data-upgraded=",MaterialProgress">
            <div class="progressbar bar bar1" style="width: 0%;"></div>
            <div class="bufferbar bar bar2" style="width: 100%;"></div>
            <div class="auxbar bar bar3" style="width: 0%;"></div>
        </div>
      </div>

      </div>

    </div>
    <div id="result" style="width:100%"></div>
  </div>
  <!-- end page content -->

</div>

<script type="text/javascript" src="js/script.js"></script>
<script src="<?php echo base_url()?>assets/dashboard/assets/js/jquery-1.7.1.min.js" type="text/javascript"></script>

<script>
$(document).ready(function() {
  setTimeout(function(){
    appendthevehiclelist();
    // appendthecontractorlist();
  }, 3000);

  // function appendthecontractorlist(){
  //   $.post("<?php echo base_url() ?>maps/getdatacontractor", {}, function(response){
  //     // console.log("response : ", response);
  //     var data = response.data;
  //     var html = "";
  //
  //         html += '<option value="0">--All Contractor</option>';
  //         for (var i = 0; i < data.length; i++) {
  //           html += '<option value="'+data[i].company_id+'">'+data[i].company_name+'</option>';
  //         }
  //       $("#contractor").html(html);
  //   },"json");
  // }

  function appendthevehiclelist(){
    var privilegecode = '<?php echo $this->sess->user_id_role; ?>';
    var user_id       = '<?php echo $this->sess->user_id; ?>';
    var user_company  = '<?php echo $this->sess->user_company; ?>';
    var html = "";

    if (privilegecode == 5 || privilegecode == 6) {
      html += '<option value="all">--Vehicle List</option>';
      html += '<?php for ($i=0; $i < sizeof($vehicledata); $i++) {?>';
        var vCompany = '<?php echo $vehicledata[$i]['vehicle_company']; ?>';
        if (vCompany == user_company) {
          html += '<option value="<?php echo $vehicledata[$i]['vehicle_no'] ?>"><?php echo $vehicledata[$i]['vehicle_no'] ?></option>';
        }
      html += '<?php } ?>';
    }else {
      html += '<option value="all">--Vehicle List</option>';
      html += '<?php for ($i=0; $i < sizeof($vehicledata); $i++) {?>';
        html += '<option value="<?php echo $vehicledata[$i]['vehicle_no'] ?>"><?php echo $vehicledata[$i]['vehicle_no'] ?></option>';
      html += '<?php } ?>';
    }

      $("#vehicle").html(html);
  }
});

	 function page(p) {
		if (p == undefined) {
			p = 0;
		}

		jQuery("#offset").val(p);
		jQuery("#loader2").show();
		jQuery.post("<?= base_url(); ?>development/search_devicereport", jQuery("#frmsearch").serialize(),
			function(r) {
        console.log("response search_devicereport : ", r);
				if (r.error) {
					alert(r.message);
					jQuery("#loader2").hide();
					jQuery("#result").hide();
				} else {
					jQuery("#loader2").hide();
					jQuery("#result").show();
					jQuery("#result").html(r.html);
					jQuery("#total").html(r.total);
				}
			}, "json"
		);
    return false;
	}

	function frmsearch_onsubmit()
	{
    $("#first_load").val(0);
		jQuery("#loader2").show();
		page();
		return false;
	}

  function periode_onchange() {
    var data_periode = jQuery("#periode").val();
    if (data_periode == 'custom') {
      jQuery("#mn_sdate").show();
      jQuery("#mn_edate").show();
    } else {
      jQuery("#mn_sdate").hide();
      jQuery("#mn_edate").hide();
    }
  }

  function getVehicleByContractor() {
		var data_company = jQuery("#company").val();
    $("#vehicle").html("");
    $.post("<?php echo base_url() ?>development/vehicleByContractor", {companyid : data_company, valuemapsoption: 9999}, function(response){
      console.log("vehicleByContractor : ", response);
      var data = response.data;
      var html = "";

          html += '<option value="all">--Vehicle List</option>';
          for (var i = 0; i < data.length; i++) {
              if (data_company == 0) {
                html += '<option value="'+data[i].vehicle_device+'">'+data[i].vehicle_no+'</option>';
              }else {
                html += '<option value="'+data[i].vehicle_device+'">'+(i+1) + ". " + data[i].vehicle_no+'</option>';
              }
          }
        $("#vehicle").html(html);
    },"json");
	}

	function houronclick(){
		console.log("ok");
		$(".switch").html("<?php echo date("Y F d")?>");
	}

</script>

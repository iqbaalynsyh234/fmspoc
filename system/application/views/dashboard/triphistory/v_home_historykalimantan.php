<style media="screen">
  #page-content-new{
    width : 82.6%;
  }
</style>
<!-- start sidebar menu -->
<div class="sidebar-container">
  <?=$sidebar;?>
</div>
<!-- end sidebar menu -->

<!-- start page content -->
<div class="page-content-wrapper">
  <div class="page-content" id="page-content-new">
    <br>
    <?php if ($this->session->flashdata('notif')) {?>
      <div class="alert alert-success" id="notifnya" style="display: none;">
        <?php echo $this->session->flashdata('notif');?>
      </div>
      <?php }?>
        <!--<div class="alert alert-success" id="notifnya2" style="display: none;"></div>-->
        <div class="row">
          <div class="col-md-12" id="tabletriphistoryreport">
            <div class="card-box">
              <div class="card card-topline-green">
                <div class="card-head">
                  <header id="headernya1"><?php echo $title ?> </header>
                </div>
                <div class="card-body">
                  <form class="block-content form" name="frmsearch" id="frmsearch" method="post" action="<?=base_url(); ?>triphistory/search/<?=$this->uri->segment(2);?>/<?=$this->uri->segment(3);?>/<?=$this->uri->segment(4);?>" onsubmit="javascript: return frmsearch_submit()">
                    <input type="hidden" name="offset" id="offset" value="0" />
                    <input type="hidden" name="act" id="act" value="0" />
                    <input type="hidden" name="isanimate" id="isanimate" value="0" />
                    <table class="table">
                      <tr>
                        <td>Select Vehicle</td>
                        <td>
                          <select class="form-control" name="selectvehicle" id="selectvehicle" onchange="javascript:selectvehicle_onchange()">
                            <?php for($i=0; $i < count($vehicles); $i++) { ?>
                              <?php
                                if (in_array($this->uri->segment(2), array("workhour", "engine", "door", "alarm")))
                                {
                                  if (! in_array(strtoupper($vehicles[$i]->vehicle_type), $this->config->item("vehicle_gtp")) && $vehicles[$i]->vehicle_type != "TK309PTO" && $vehicles[$i]->vehicle_type != "GT06PTO")
                                  {
                                    continue;
                                  }
                                }
                              ?>
                            <option value="<?php echo $vehicles[$i]->vehicle_device1; ?>"<?php if ($vehicle->vehicle_id == $vehicles[$i]->vehicle_id) { echo " selected"; } ?>><?php if ($this->sess->user_type != 2) { echo $vehicles[$i]->user_name." - "; } ?><?php echo $vehicles[$i]->vehicle_name; ?> - <?php echo $vehicles[$i]->vehicle_no; ?></option>
                            <?php } ?>
                          </select>
                        </td>
                      </tr>

                      <tr id="tglperiod">
                        <td>Date Time</td>
      									<td>
                          <div class="input-group date form_date col-md-12" data-date="" data-date-format="dd/mm/yyyy" data-link-format="dd/mm/yyyy">
                              <input type='text' name="period1" id="period1"  class="form-control" size="5" type="text" readonly value="<?=$_POST['period1']; ?>"  maxlength='10' />
                              <span class="input-group-addon"><span class="fa fa-calendar"></span></span>
                          </div>
                        </td>
                        <td>
                            <?=$this->lang->line("luntil"); ?>
                        </td>

                        <td>
                          <div class="input-group date form_date col-md-12" data-date="" data-date-format="dd/mm/yyyy" data-link-format="dd/mm/yyyy">
                              <input type='text' name="period2" id="period2"  class="form-control" size="5" type="text" readonly value="<?=$_POST['period1']; ?>"  maxlength='10' />
                              <span class="input-group-addon"><span class="fa fa-calendar"></span></span>
                          </div>
                        </td>
      								</tr>
                      <tr>
                        <td>Hours</td>
                        <td>
                          <select name="hperiod1" id="hperiod1" class="form-control">
                            <?php for($i=0; $i < 24; $i++) { ?>
                                <option value="<?=$i?>"<?=($i == $_POST['hperiod1']) ? " selected" : ""?>><?=sprintf('%02d', $i)?></option>
                            <?php } ?>
                          </select>

                          <select name="mperiod1" id="mperiod1" class="form-control">
                            <?php for($i=0; $i < 60; $i++) { ?>
                                <option value="<?=$i?>"<?=($i == $_POST['mperiod1']) ? " selected" : ""?>><?=sprintf('%02d', $i)?></option>
                            <?php } ?>
                          </select>

                          <select name="speriod1" id="speriod1" class="form-control">
                            <?php for($i=0; $i < 60; $i++) { ?>
                                <option value="<?=$i?>"<?=($i == $_POST['speriod1']) ? " selected" : ""?>><?=sprintf('%02d', $i)?></option>
                            <?php } ?>
                          </select>
                        </td>

                        <td>
                          <?=$this->lang->line("luntil"); ?>
                        </td>

                        <td>
                          <select name="hperiod2" id="hperiod2" class="form-control">
                            <?php for($i=0; $i < 24; $i++) { ?>
                                <option value="<?=$i?>"<?php echo ($i == $_POST['hperiod2']) ? " selected" : ""?>><?=sprintf('%02d', $i)?></option>
                            <?php } ?>
                          </select>
                          <select name="mperiod2" id="mperiod2" class="form-control">
                            <?php for($i=0; $i < 60; $i++) { ?>
                                <option value="<?=$i?>"<? echo ($i == $_POST['mperiod2']) ? " selected" : ""?>><?=sprintf('%02d', $i)?></option>
                            <?php } ?>
                          </select>
                          <select name="speriod2" id="speriod2" class="form-control">
                            <?php for($i=0; $i < 60; $i++) { ?>
                                <option value="<?=$i?>"<? echo ($i == $_POST['speriod2']) ? " selected" : ""?>><?=sprintf('%02d', $i)?></option>
                            <?php } ?>
                          </select>
                        </td>
                    </tr>

                    <!-- <tr>
                      <td>
                        <?php echo $this->lang->line("lshow_per"); ?>
                      </td>
                      <td>
                        <select name="limit" id="limit" class="form-control">
                          <?php
                            $limits = $this->config->item("LIMITS");
                            foreach($limits as $limit) {
                          ?>
                          <option value="<?php echo $limit; ?>"><?php echo $limit; ?></option>
                          <?php } ?>
                        </select>
                      </td>
                      <td>
                        <?php echo $this->lang->line("ldata"); ?>
                      </td>
                    </tr> -->

                    <tr>
                      <td>Data</td>
                      <td>
                        <select id="data" name="data" class="form-control">
                          <option value="1"><?=$this->lang->line("ldetail"); ?></option>
                          <option value="2"><?=$this->lang->line("lsummary"); ?></option>
                        </select>
                      </td>
                    </tr>

                    <tr>
                      <td>Limit Data</td>
                      <td>
                        <select id="limitdata" name="limitdata" class="form-control">
                          <option value="100">100</option>
                          <option value="200">200</option>
                          <option value="500">500</option>
                        </select>
                      </td>
                    </tr>
                    <!-- <tr>
    									<td valign="top" style="border: 0px;" colspan="5">
                        <fieldset class="grey-bg required">
                          <legend>
                            <?php echo $this->lang->line("lexport_format"); ?>
                          </legend>
                            <input type="radio" name="format" id="format" value="csv;" checked /><?php echo $this->lang->line("lcsv_dot_comma"); ?><br />
          									<input type="radio" name="format" id="format" value="csv," /> <?php echo $this->lang->line("lcsv_comma"); ?><br />
          									<input type="radio" name="format" id="format" value="kml" /> <?php echo "Data KML"; ?>

    										<br/>

                        <input type="radio" name="format" id="format" value="excell" /> <?php echo "Excel"; ?>
                          <?php if ($this->sess->user_type == 1) { ?>
                            <br/>
                            <input type="radio" name="format" id="format" value="pdf" /> <?php echo "Pdf"; ?>
                          <?php } ?>
                        </fieldset>
                      </td>
    								</tr> -->
    								<tr>
                      <td></td>
                      <td></td>
                      <td></td>
                      <td>
                        <div class="text-right">
                          <img id="loadernyagan" src="<?php echo base_url();?>assets/transporter/images/loader2.gif" style="display: none;">
                          <button class="btn btn-primary" type="submit"/>Search</button>
                          <!-- <button class="btn btn-warning" type="button" onclick="javascript:page(0, 'export')" />Export</button> -->
                          <button class="btn btn-warning" onclick="javascript:fnExcelReport();" id="export_xcel" style="display:none;">Export to Excel</a>
                          <!-- <a class="button" href="javascript:void(0);" id="export_xcel">Export [InView] to Excel</a> -->
                        </div>
                        <br>
    									</td>
    								</tr>
                    </table>
                  </form>
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-12" id="historymapsresult" style="display: none;">
            <div class="card-box">
              <div class="card card-topline-green">
                <div class="card-head">
                  <header id="headerhistorymapsresult">History Maps</header>
                </div>
                <div class="card-body">
                  <div id="map_canvas"></div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-12" id="showresulthistory" style="display: none;">
            <div class="card-box">
              <div class="card card-topline-green">
                <div class="card-head">
                  <header id="headernya2">
                    <?php echo $title ?>
                  </header>
                  <button type="button" id="btnshowhistorymap" name="button" class="btn btn-primary" onclick="showhistorymaps()">
                    <span class="fa fa-search"></span>
                    Show Map
                  </button>
                  <button type="button" id="btnhidehistorymap" name="button" class="btn btn-warning" onclick="hidehistorymaps()" style="display: none;">
                    <span class="fa fa-close"></span>
                    Hide Map
                  </button>
                </div>
                <div class="card-body">
                  <div id="datahistory">

                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
  </div>
</div>

<script>
	// $(document).ready(
	// 	function()
	// 	{
	// 		$("#export_xcel").click(function()
	// 		{
	// 			window.open('data:application/vnd.ms-excel,' + encodeURIComponent($('#dvresult').html()));
	// 		});
  //
	// 		isnow_click();
	// 		selectvehicle_onchange();
	// 	}
	// );

  function fnExcelReport(){
    var tab_text         = "<table border='2px'><tr bgcolor='#87AFC6'>";
    var textRange; var j = 0;
    tab = document.getElementById('stprint'); // id of table

    for(j = 0 ; j < tab.rows.length ; j++){
      tab_text=tab_text+tab.rows[j].innerHTML+"</tr>";
      //tab_text=tab_text+"</tr>";
    }

    tab_text=tab_text+"</table>";
    tab_text= tab_text.replace(/<A[^>]*>|<\/A>/g, "");//remove if u want links in your table
    tab_text= tab_text.replace(/<img[^>]*>/gi,""); // remove if u want images in your table
    tab_text= tab_text.replace(/<input[^>]*>|<\/input>/gi, ""); // reomves input params

    var ua   = window.navigator.userAgent;
    var msie = ua.indexOf("MSIE ");

    if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./)){     // If Internet Explorer
      txtArea1.document.open("txt/html","replace");
      txtArea1.document.write(tab_text);
      txtArea1.document.close();
      txtArea1.focus();
      sa = txtArea1.document.execCommand("SaveAs",true,"Say Thanks to Sumit.xls");
    }else  {               //other browser not tested on IE 11
      sa = window.open('data:application/vnd.ms-excel,' + encodeURIComponent(tab_text));
    }
  }

	function selectvehicle_onchange()
	{
		var v = $("#selectvehicle").val();

		var myurl = "<?=base_url(); ?>trackers/search/<?=$this->uri->segment(2);?>/"+v;
		document.frmsearch.action = myurl;

		$.post("<?=base_url(); ?>trackers/menu/<?=$this->uri->segment(2);?>/"+v+"/", {},
			function(r)
			{
				$("#reportmenu").html(r.html);
			}
			, "json"
		);
	}

	function page(n, act)
	{
		$("#isanimate").val(0);

		if (! act) act = "list";
		if (! n) n = 0;

		$("#act").val(act);
		$("#offset").val(n);

		if (act == "export")
		{
			document.frmsearch.submit();
			return;
		}
    $("#loadernyagan").show();
    $("#historymapsresult").hide();
    $("#showresulthistory").hide();
		var v    = $("#selectvehicle").val();
		// $("#dvresult").html('<img src="<?php echo base_url();?>assets/transporter/images/loader2.gif">');
		$.post("<?=base_url(); ?>triphistory/search/<?=$this->uri->segment(2);?>/"+v+"/"+$("#offset").val(), $("#frmsearch").serialize(),
			function(r)
			{
        $("#export_xcel").show();
        $("#loadernyagan").hide();
        $("#btnshowhistorymap").show();
        $("#btnhidehistorymap").hide();
        console.log("response : ", r);
        $("#map_canvas").html("");
				selectvehicle_onchange();

				if (! r)
				{
					alert("Query failed. Please retry!");
					return;
				}

				if (r.error)
				{
					alert(r.message);
					return;
				}

        console.log("r.data.length : ", r.data.length);
        $("#headernya1").html("History Report " + r.title);
        $("#headernya2").html("History Report " + r.title);
				$("#datahistory").html(r.htmlhistory);
        $("#showresulthistory").show();
        $("#map_canvas").html(r.html);
			}
			, "json"
		);
	}

  function showhistorymaps(){
    $("#btnshowhistorymap").hide();
    $("#btnhidehistorymap").show();
    $("#historymapsresult").show();
  }

  function hidehistorymaps(){
    $("#btnshowhistorymap").show();
    $("#btnhidehistorymap").hide();
    $("#historymapsresult").hide();
  }


	function frmsearch_submit()
	{
    $("#export_xcel").hide();
		page(0);
		return false;
	}

	function isnow_click()
	{
		$("#tglperiod").attr("disabled", $("#isnow").attr("checked"));
		$("#jamperiod").attr("disabled", $("#isnow").attr("checked"));

		if ($("#isnow").attr("checked"))
		{
			$("#period1").css("background-color", "#cccccc");
			$("#period2").css("background-color", "#cccccc");

			$("#hperiod1").css("background-color", "#cccccc");
			$("#mperiod1").css("background-color", "#cccccc");
			$("#speriod1").css("background-color", "#cccccc");

			$("#hperiod2").css("background-color", "#cccccc");
			$("#mperiod2").css("background-color", "#cccccc");
			$("#speriod2").css("background-color", "#cccccc");
		}
		else
		{
			$("#period1").css("background-color", "#ffffff");
			$("#period2").css("background-color", "#ffffff");

			$("#hperiod1").css("background-color", "#ffffff");
			$("#mperiod1").css("background-color", "#ffffff");
			$("#speriod1").css("background-color", "#ffffff");

			$("#hperiod2").css("background-color", "#ffffff");
			$("#mperiod2").css("background-color", "#ffffff");
			$("#speriod2").css("background-color", "#ffffff");
		}

	}
</script>

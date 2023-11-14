<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/series-label.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>
<script src="https://code.highcharts.com/modules/export-data.js"></script>
<script src="https://code.highcharts.com/modules/accessibility.js"></script>

<link href="<?php base_url(); ?>assets/dashboard/assets/plugins/bootstrap-table-1.19.1/bootstrap-table.min.css" rel="stylesheet">
<script src="<?php base_url(); ?>assets/dashboard/assets/plugins/bootstrap-table-1.19.1/extensions/sticky-header/bootstrap-table-sticky-header.js"></script>
<script src="<?php base_url(); ?>assets/dashboard/assets/plugins/bootstrap-table-1.19.1/bootstrap-table.min.js"></script> 

<script>
    function frmsearch_onsubmit() {
        var company = $("#company").val();
        var periode = $("#periode").val();
        if (periode == "this_month") {
            if (company == "all") {
                alert("1 month data only for specific contractors!");
                return false;
            }
        }
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

    function company_onchange() {
        var data_company = jQuery("#company").val();
        var dc = data_company.split("@");
        var site = "<?= base_url() ?>hse/get_vehicle_by_company/" + dc[0];
        jQuery.ajax({
            url: site,
            success: function(response) {
                jQuery("#vehicle").html("");
                jQuery("#vehicle").html(response);
            },
            dataType: "html"
        });
    }
  </script>

</script>

</head>

<body>
 <!-- start sidebar menu -->
    <div class="sidebar-container">
        <?= $sidebar; ?>
    </div>
    <!-- end sidebar menu -->

    <!-- start page content -->
    <div class="page-content-wrapper">
         <div class="page-content">
            <form class="form-horizontal" name="frmsearch" id="frmsearch" onsubmit="javascript:return frmsearch_onsubmit()">
            <div class="row">
                <div class="col-md-12 col-sm-12">
                    <div class="panel"id="myChart">
                        <div class="card-header" style="text-align: center; font-size:22px;">
                            <b>DASHBOARD PROFILE CONTROL ROOM</b>
                        </div>
                        <div class="panel-body" id="bar-parent10">
                            <div class="form-group row">
                                <div class="col-lg-2 col-md-2">
                                    <!--<select id="contractor" name="contractor" class="form-control select2" >-->
                                    <select class="form-control select2" id="company" name="company" onchange="javascript:company_onchange()">

                                        <?php
                                        $pc = $this->sess->user_id_role;
                                        if (($pc == 0) || ($pc == 1) || ($pc == 2) || ($pc == 3) || ($pc == 4)) {
                                            echo '<option value="all">--All Contractor</option>';
                                        }
                                        $ccompany = count($rcompany);
                                        for ($i = 0; $i < $ccompany; $i++) {
                                            echo "<option value='" . $rcompany[$i]->company_id ."'>" . $rcompany[$i]->company_name . "</option>";
                                        }
                                        ?>
                                        
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-3">
                                    <select id="violationmasterselect" name="violationmasterselect" class="form-control select2" onchange="onchangefilter()">
                                        <option value="all">--All Violation</option>
                                            <option value="call">Call</option>
                                            <option value="cardistance">Car Distance</option>
                                            <option value="distracted">Distracted</option>
                                            <option value="fatigue">Fatigue</option>
                                            <option value="overspeed" selected>Overspeed</option>
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-2">
                                    <!-- <div class="input-group date form_date" data-date="" data-date-format="dd-mm-yyyy" data-link-field="dtp_input2" data-link-format="yyyy-mm-dd">
                                        <input class="form-control" type="text" readonly name="date" id="startdate" value="<?= date("d-m-Y"); ?>">
                                        <span class=" input-group-addon"><span class="fa fa-calendar"></span></span>
                                    </div> -->
                                    <!-- <select id="periode" name="periode" id="periode" class="form-control select2"> -->
                                    <select name="periode" id="periode" class="form-control select2" onchange="javascript:periode_onchange()">
                                        <option value="today">Today</option>
                                        <option value="yesterday">Yesterday</option>
                                        <option value="last7">Last 7 Days</option>
                                        <option value="this_month">This Month</option>
                                        <option value="custom">Custom Date</option>
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-2">
                                    <button class="btn btn-circle btn-success" id="btnsearchreport" type="submit" style="margin-left: 30px;">Search</button>
                                    <!-- <img id="loader2" style="display:none;" src="<?php echo base_url(); ?>assets/images/ajax-loader.gif" /> -->
                                </div>
                            </div>
                            <div class="form-group row" id="mn_sdate" style="display:none;">
                                <div class="col-lg-2 col-md-2">
                                    <div class="input-group date form_date" data-date="" data-date-format="dd-mm-yyyy" data-link-field="dtp_input2" data-link-format="yyyy-mm-dd">
                                        <input class="form-control" type="text" readonly name="sdate" id="startdate" value="<?= date("d-m-Y"); ?>">
                                        <span class=" input-group-addon"><span class="fa fa-calendar"></span></span>
                                    </div>
                                </div>
                                <div class="col-lg-1 col-md-1">
                                    s/d
                                </div>
                                <div class="col-lg-2 col-md-2">
                                    <div class="input-group date form_date" data-date="" data-date-format="dd-mm-yyyy" data-link-field="dtp_input2" data-link-format="yyyy-mm-dd">
                                        <input class="form-control" type="text" readonly name="edate" id="endtdate" value="<?= date("d-m-Y"); ?>">
                                        <span class=" input-group-addon"><span class="fa fa-calendar"></span></span>
                                    </div>
                                </div>
                            </div>
                            <div id="loader" style="display: none;" class="mdl-progress mdl-js-progress mdl-progress__indeterminate is-upgraded" data-upgraded=",MaterialProgress">
                                <div class="progressbar bar bar1" style="width: 0%;"></div>
                                <div class="bufferbar bar bar2" style="width: 100%;"></div>
                                <div class="auxbar bar bar3" style="width: 0%;"></div>
                            </div>
                        </div>

                    </div>

                </div>

            </div>
    </form>

    <div id="chart1" style="width: 50%; float: left;"></div>
    <div id="chart2" style="width: 50%; float: left;"></div>

    <?php //echo "<pre>" , var_dump($dataChart); ?>

    <script>
        function frmsearch_onsubmit(){

            const company = document.getElementById('company').value;
            const violation = document.getElementById('violationmasterselect').value;
            const periode = document.getElementById('periode').value;
            const startDate = document.getElementById('startdate').value;
            const endDate = document.getElementById('endtdate').value; 

            // var uri = "<?=base_url() . 'controlroom/room'?>";
            jQuery("#loader").show();
		    jQuery.post("<?=base_url()?>controlroom/search_violation2", jQuery("#frmsearch").serialize(),
                function(r)
                {
                    // console.log("List Delay:", r.list_delay);
                    // console.log("List alarm true: ", r.list_alarm_true);
                    // console.log("List Ontime:", r.list_ontime);

                    jQuery("#loader").hide();
                    if (r.error)
                    {
                        alert(r.message);
                        return false;
                    }

                    // Add '%' symbol to each data point
                    var list_alarm_true_with_percent = r.list_alarm_true.map(function(value) {
                        return value + '%';
                    });

                    var list_alarm_false_with_percent = r.list_alarm_false.map(function(value) {
                        return value + '%';
                    });

                    var data1 = {
                        title: {
                                text: 'DASHBOARD TRUE-FALSE ALARM'
                            },
                        subtitle: {
                            text: 'Periode<br>' + document.getElementById('startdate').value + ' s/d ' + document.getElementById('endtdate').value 
                        },
                        xAxis: {
                            type: 'datetime', 
                            title: {
                                text: 'Periode (Day)'
                            },
                            categories: r.list_periode,   
                        },
                        yAxis: {
                            title: {
                                text: 'Percentage %'
                            },
                        }, 
                        series: [{
                            name: 'True',
                            data: r.list_alarm_true,  
                            color: 'green' 
                        },
                        {
                            name: 'False', 
                            data: r.list_alarm_false, 
                            color: 'black'
                        }]
                    };

                    // Inisialisasi grafik pertama
                    Highcharts.chart('chart1', data1);
                    
                    var data2 = {
                    title: {
                        text: 'DASHBOARD LEAD TIME INTERVENSI'
                    },
                    subtitle: {
                        text: 'Periode<br>' + document.getElementById('startdate').value + ' s/d ' + document.getElementById('endtdate').value
                    },
                    xAxis: {
                        type: 'datetime', 
                        title: {
                            text: 'Periode (Day)'
                        },
                        categories: r.list_periode, // list periode //
                    },
                    yAxis: {
                        title: {
                            text: 'percentage %'
                        },
                    }, 
                    series: [{
                        name: 'Delay',
                        data: r.list_delay,
                        color: 'green'
                    },
                    {
                        name: 'On time', 
                        data: r.list_ontime, 
                        color: 'black'
                    }]
                };
                // Inisialisasi grafik kedua
                Highcharts.chart('chart2', data2);
            }
            , "json"
        );
        return false;
    }

    </script>

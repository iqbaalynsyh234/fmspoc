    <!-- Include Chart.js library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link href="<?php base_url(); ?>assets/dashboard/assets/plugins/bootstrap-table-1.19.1/bootstrap-table.min.css" rel="stylesheet">
    <script src="<?php base_url(); ?>assets/dashboard/assets/plugins/bootstrap-table-1.19.1/extensions/sticky-header/bootstrap-table-sticky-header.js"></script>
    <script src="<?php base_url(); ?>assets/dashboard/assets/plugins/bootstrap-table-1.19.1/bootstrap-table.min.js"></script> 
    
    <style type="text/css">
    /* edit style datepicker*/
    .datetimepicker {
        background: #1F50A2;
    }

    .prev,
    .switch,
    .next,
    .today {
        background: #FFF;
    }

    .dow {
        color: #FFF;
        padding: 6px;
    }

    .table-condensed tbody tr td {
        color: #FFF;
    }

    .datetimepicker .datetimepicker-days table tbody tr td:hover {
        background-color: #000;
    }

    .datetimepicker .datetimepicker-years table tbody tr td span:hover {
        background-color: #000;
    }

    .datetimepicker .datetimepicker-months table tbody tr td span:hover {
        background-color: #000;
    }


    /* edit Style graphic */
    .highcharts-data-label text {
        text-decoration: none;
    }

    .highcharts-drilldown-axis-label {
        pointer-events: none;
    }
    </style>

<script>
    function frmsearch_onsubmit() {
        var company = $("#company").val();
        // var vehicle = $("#vehicle").val();
        // var violation = $("#violation").val();
        var periode = $("#periode").val();
        // if (company != "all") {
        //     alert("Data Not Ready");
        //     return false;
        // }
        // if (vehicle != "all") {
        //     alert("Data Not Ready");
        //     return false;
        // }
        // if (violation != "all") {
        //     alert("Data Not Ready");
        //     return false;
        // }
        if (periode == "this_month") {
            if (company == "all") {
                alert("1 month data only for specific contractors!");
                return false;
            }
        }
        page();
        return false;
    }

    function page() {
        // if (p == undefined) {
        //     p = 0;
        // }
        // jQuery("#offset").val(p);
        jQuery("#result").hide();
        jQuery("#loader").show();

        jQuery.post("<?= base_url(); ?>controlroom/search_violation2", jQuery("#frmsearch").serialize(),
        function (r) {
            if (r.error) {
                console.log(r);
                alert(r.message);
                jQuery("#loader").hide();
                jQuery("#result").hide();
                return;
            } else {
                console.log(r);

                // Update data grafik chart1 dan chart2 dengan data dari hasil pencarian
                dataChart1.series[0].data = r.dataChart1True; // Ganti dengan data True dari hasil pencarian
                dataChart1.series[1].data = r.dataChart1False; // Ganti dengan data False dari hasil pencarian
                Highcharts.chart('chart1', dataChart1);

                dataChart2.series[0].data = r.dataChart2True; // Ganti dengan data True dari hasil pencarian
                dataChart2.series[1].data = r.dataChart2False; // Ganti dengan data False dari hasil pencarian
                Highcharts.chart('chart2', dataChart2);

                jQuery("#loader").hide();
                jQuery("#result").html(r.html);
                jQuery("#result").show();
            }
          }, "json"
      );
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
                        <div class="card-header" style="text-align: center; font-size:large;">
                            <b>Dashboard Profile Control Room</b>
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
                                            echo "<option value='" . $rcompany[$i]->company_id . "@" . $rcompany[$i]->company_name . "'>" . $rcompany[$i]->company_name . "</option>";
                                        }
                                        ?>
                                    </select>
                                    </div>
                                <div class="col-lg-2 col-md-2" style="display: none;">
                                    <select id="vehicle" name="vehicle" class="form-control select2">
                                        <option value="all">--All Vehicle</option>
                                        <?php
                                        // $cvehicle = count($rvehicle);
                                        // for ($i = 0; $i < $cvehicle; $i++) {
                                        //     echo "<option value='" . $rvehicle[$i]->vehicle_imei . "/" . $rvehicle[$i]->vehicle_device . "/" . $rvehicle[$i]->vehicle_company . "'>" . $rvehicle[$i]->vehicle_no . "</option>";
                                        // }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-2">
                                    <select id="violationmasterselect" name="violationmasterselect" class="form-control select2" onchange="onchangefilter()">
                                        <option value="0">--All Violation</option>
                                        <?php
                                        for ($i = 0; $i < sizeof($violationmaster); $i++) {?>
                                        <option value=<?php echo $violationmaster[$i]["alarmmaster_id"] ?>><?php echo $violationmaster[$i]["alarmmaster_name"] ?></option>
                                        <?php } ?>
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

    
    
    <script>

    $("#btnsearchreport").click(function (e) {
        e.preventDefault();
        const company = $("#company").val();
        const violation = $("#violation").val();
        const periode = $("#periode").val();
        const startDate = $("#startdate").val();
        const endDate = $("#endtdate").val();

        drawCharts(company, violation, periode, startDate, endDate);
    });
    
   </script>

    <script>
        // menruskan data kedalam bentuk json
        var chartData = <?php echo json_encode($chart_data); ?>;

        // membuat point
        var labels = chartData.map(item => item.alarm_report_start_time);
        var dataPoints = chartData.map(item => item.alarm_report_statusinterventation_up);

        // Get the canvas element
        var ctx = document.getElementById('myChart').getContext('2d');

        // Buat line chart
        var lineChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Alarm Report True/False Count',
                    data: dataPoints,
                    backgroundColor: 'rgba(0, 123, 255, 0.5)',
                    borderColor: 'rgba(0, 123, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    xAxes: [{
                        type: 'time',
                        time: {
                            unit: 'day'
                        },
                        scaleLabel: {
                            display: true,
                            labelString: 'Time'
                        }
                    }],
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        },
                        scaleLabel: {
                            display: true,
                            labelString: 'Count'
                        }
                    }]
                }
            }
        });
    </script>

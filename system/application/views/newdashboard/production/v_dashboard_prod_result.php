<script src="<?php echo base_url();?>assets/dashboard/assets/plugins/jquery/jquery.min.js"></script>
<script>
	  $(document).ready(function() { 
	 
	  getAllRitase_byPort();
	  
	  getAllRitase_byCompany();
	  
	  getAllRitase_byHour();
	 
	  });
</script>



<script>

function getAllRitase_byPort()
{
	
	/* var content_ritase_allport =  '<?php echo json_encode($content_ritase_allport); ?>';
	var content_ritase_allport_fix = JSON.parse(content_ritase_allport); */
	
	var content_all_hauling =  '<?php echo json_encode($content_all_hauling); ?>';
	var content_all_hauling_fix = JSON.parse(content_all_hauling);
	
	var content_all_hauling_level2 =  '<?php echo json_encode($content_all_hauling_level2); ?>';
	var content_all_hauling_level2_fix = JSON.parse(content_all_hauling_level2);
	
	console.log(content_all_hauling_level2_fix);
	// Create the chart
	Highcharts.chart('container_ritase_all_port', {
    chart: {
        type: 'column'
    },
    title: {
        text: '<?php echo $periode_show;?>'
    },
    subtitle: {
        text: ''
    },
    accessibility: {
        announceNewData: {
            enabled: true
        }
    },
    xAxis: {
        type: 'category'
    },
    yAxis: {
        title: {
            text: 'Total Tonnage'
        }

    },
    legend: {
        enabled: false
    },
    plotOptions: {
        series: {
            borderWidth: 0,
            dataLabels: {
                enabled: true,
                format: '{point.y:.0f}'
            }
        }
    },

    tooltip: {
        headerFormat: '<span style="font-size:11px">{series.name}</span><br>',
        pointFormat: '<span style="color:{point.color}">{point.name}</span>: <b>{point.y:.0f}</b><br/>'
    },

    series: [
        {
            name: "",
            colorByPoint: true,
          
			data: 
			[
                content_all_hauling_fix
			]
         }
    ],
    drilldown: {
        breadcrumbs: {
            position: {
                align: 'right'
            }
        },
        series: [
            
			{
                name: "level2",
                id: "content_level2",
                data: 
				
					content_all_hauling_level2_fix
				/*[
			
				
                    [
                        "PORT BIB",
						1000
                    ],
                    [
                        "PORT BIR",
                        1000
                    ],
					[
                        "PORT TIA",
						0
                    ]
                     
                ]*/
				
				
            }
			
		]
    }
});
	
}

function getAllRitase_byCompany(){
	
	var content_company =  '<?php echo json_encode($content_company); ?>';
	var content_company_fix = JSON.parse(content_company);
	
	var content_ton =  '<?php echo json_encode($content_ton); ?>';
	var content_ton_fix = JSON.parse(content_ton);
	
	var content_rit =  '<?php echo json_encode($content_rit); ?>';
	var content_rit_fix = JSON.parse(content_rit);
	
	var totala = 0;
	var totalb = 0;
	
	for(let i = 0; i < content_ton_fix.length; i++){
		var a = content_ton_fix[i];
		
		totala = totala + a;
	}
	
	for(let u = 0; u < content_rit_fix.length; u++){
		var b = content_rit_fix[u];
		
		totalb = totalb + b;
	}
	
	//console.log(totala);
	//console.log(totalb);
	
	Highcharts.chart('container_ritase_all_company', {
    chart: {
        type: 'bar'
    },
    title: {
        text: '<?php echo $periode_show;?>'
    },
    subtitle: {
        text: ''
    },
    xAxis: {
        categories: 
		content_company_fix
		//['RBT', 'BKA', 'GECL', 'MKS', 'MMS','BBS','EST','KMB','RAM','STLI']
		
		
		,
        title: {
            text: null
        }
    },
    yAxis: {
        min: 0,
        title: {
            text: 'Total',
            align: 'high'
        },
        labels: {
            overflow: 'justify'
        }
    },
    tooltip: {
        valueSuffix: ' Total'
    },
    plotOptions: {
        bar: {
            dataLabels: {
                enabled: true
            }
        }
    },
    legend: {
        layout: 'vertical',
        align: 'right',
        verticalAlign: 'top',
        x: -40,
        y: 80,
        floating: true,
        borderWidth: 1,
        backgroundColor:
            Highcharts.defaultOptions.legend.backgroundColor || '#FFFFFF',
        shadow: true
    },
    credits: {
        enabled: false
    },
    series: [
	
	{
        name: 'Tonnage',
		//name: 'Tonnage'+' ('+totala+')',
        //data: [48*30, 27*30, 36*30, 28*30, 15*30, 40*30, 13*30, 26*30, 8*30, 23*30]
		data: content_ton_fix
		
    }
	,
	{
        name: 'Ritase',
		//name: 'Ritase'+' ('+totalb+')',
        //data: [48, 27, 36, 28, 15, 40, 13, 26, 8, 23]
		data: content_rit_fix
    }
	
	
	]
});

}

function getAllRitase_byHour(){
	
	var content_hour_name =  '<?php echo json_encode($content_hour_name); ?>';
	var content_hour_name_fix = JSON.parse(content_hour_name);
	
	var content_ton =  '<?php echo json_encode($content_ton_hour); ?>';
	var content_ton_fix = JSON.parse(content_ton);
	
	var content_rit =  '<?php echo json_encode($content_rit_hour); ?>';
	var content_rit_fix = JSON.parse(content_rit);
	
	var totala = 0;
	var totalb = 0;
	
	for(let i = 0; i < content_ton_fix.length; i++){
		var a = content_ton_fix[i];
		
		totala = totala + a;
	}
	
	for(let u = 0; u < content_rit_fix.length; u++){
		var b = content_rit_fix[u];
		
		totalb = totalb + b;
	}
	
	
Highcharts.chart('container_ritase_all_hour', {
    chart: {
        type: 'column'
    },
    title: {
        text: '<?php echo $periode_show;?>'
    },
    subtitle: {
        text: ''
    },
    xAxis: {
        categories: 
		content_hour_name_fix
		/* [
            'Tonage',
            'Ritase'
           
        ] */
		
		,
        crosshair: true
    },
    yAxis: {
        min: 0,
        title: {
            text: 'Total per Jam'
        }
    },
    tooltip: {
        headerFormat: '<span style="font-size:15px">Jam {point.key}</span><table>',
        pointFormat: '<tr><td style="color:{series.color};padding:0"><span style="font-size:13px">{series.name}: </span></td>' +
            '<td style="padding:0"><b><span style="font-size:13px">{point.y:.0f}</span></b></td></tr>',
        footerFormat: '</table>',
        shared: true,
        useHTML: true
    },
    plotOptions: {
        column: {
            pointPadding: 0.2,
            borderWidth: 0,
			dataLabels: {
                enabled: true
            }
        }
    },
	
    series: [{
        name: 'Tonnage',
        //data: [49.9, 71.5, 106.4, 129.2, 144.0, 176.0, 135.6, 148.5, 216.4, 194.1, 95.6, 54.4]
		data: content_ton_fix

    }, {
        name: 'Ritase',
        //data: [83.6, 78.8, 98.5, 93.4, 106.0, 84.5, 105.0, 104.3, 91.2, 83.5, 106.6, 92.3]
		data: content_rit_fix

    }
	]
});

}

</script>


<div class="row" >						
				<!-- result -->
				
		
						<div class="col-lg-6 col-md-6 col-sm-3 col-3" style="bottom:13px">
							<figure class="highcharts-figure">
								<div id="container_ritase_all_port"></div>
							</figure>
						</div>	
			
						<div class="col-lg-6 col-md-6 col-sm-3 col-3" style="bottom:13px">
							<figure class="highcharts-figure">
								<div id="container_ritase_all_company"></div>
							</figure>
						</div>	
						<div class="col-lg-12 col-md-12 col-sm-3 col-3" style="bottom:13px">
							<figure class="highcharts-figure">
								<div id="container_ritase_all_hour"></div>
							</figure>
						</div>	
						
			
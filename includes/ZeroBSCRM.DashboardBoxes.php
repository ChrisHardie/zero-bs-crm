<?php 
/*!
 * Zero BS CRM
 * https://zerobscrm.com
 * V1.20
 *
 * Copyright 2017, Zero BS Software Ltd. & Zero BS CRM.com
 *
 * Date: 01/11/16
 */


/* ======================================================
  Breaking Checks ( stops direct access )
   ====================================================== */
    if ( ! defined( 'ZEROBSCRM_PATH' ) ) exit;
/* ======================================================
  / Breaking Checks
   ====================================================== */


/*
    add code in here for the dashboard boxes. Keeps it a bit tidier than ALL in the AdminPages.php file 

    action hooks to use:
        zbs_dashboard_pre_dashbox_post_totals:  shows up on the first row AFTER the total boxes
        zbs_dashboard_customiser_after_row_1:   lets you add a on / off control (if desired after tht total boxes checkbox controls)

*/

//example code to add a new box below the totals, but above the funnels. This one is NOT turn off-able (is off-able a word, lol).
add_action('zbs_dashboard_pre_dashbox_post_totals', 'zeroBS_dashboard_crm_list_growth', 1);
function zeroBS_dashboard_crm_list_growth(){

    //shows a chart of CRM growth over time, feat creep (will prob add) stack by status
    //using chart JS like the other charts on that page. 

    global $zbs;

    // ============== v2 of get counts per mo (better SQL perf)
    $col = '#00a0d2';
    $i=0;
    $date_group = date('M Y');
    $group_tot = 0;
    $newdata = array();



    for ($i = 0; $i < 24; $i++) {

        $timeStart = mktime(0, 0, 0, date("m")-$i, 1, date("Y"));
        $timeEnd = mktime(0, 0, 0, date("m")-($i-1), 1, date("Y"))-1; // 1 second before midnight of month after
        $date = date("M Y", $timeStart);
        $labels[$i] = "'" . $date . "'";
        $background[$i] = "'" . $col . "'";
        $labelsa[$i] = $date;

        // retrieve st
        $countInMonth = $zbs->DAL->contacts->getContacts(array(
            'olderThan'         => $timeEnd, // uts
            'newerThan'         => $timeStart, // uts
            'count'             => true,
            'page'              => -1,
            'ignoreowner'       => zeroBSCRM_DAL2_ignoreOwnership(ZBS_TYPE_CONTACT)
        ));
  
        $newdata[$date] = $countInMonth;
    }


  
    $labels = implode(",",array_reverse($labels));
    $background = implode(",", $background);


    $chartdata = array();
    foreach($labelsa as $label){
        if(array_key_exists($label, $newdata)){
          $chartdata[$label] = $newdata[$label];
        }else{
          $chartdata[$label] = 0;
        }
    }
    $chartdataStr = implode(",",array_reverse($chartdata));

    if(array_sum($newdata) == 0){ ?>
        <div class="col-12 zbs-growth"  id="settings_dashboard_growth_display" style="margin-right:22px;">
            <div class='panel' style="padding:20px;">
                <div class='ui message blue' style="text-align:center;margin-bottom:80px;margin-top:50px;">
                <?php _e("You do not have any contacts. You need contacts for your growth chart to show.","zero-bs-crm");?> 
                <br />
                <a href="<?php echo zbsLink('create',-1,'zerobs_customer',false,false); ?>" class="ui tiny green button" style="margin-top:1em"><?php _e('Add a Contact','zero-bs-crm'); ?></a>
                </div>
            </div>
        </div>
    <?php }else{ ?>
        <div class="col-12 zbs-growth"  id="settings_dashboard_growth_display" style="margin-right:22px;">
            <div class='panel' style="padding:20px;height:400px;padding-bottom:50px;">
                <div class="panel-heading" style="text-align:center">
                    <h4 class="panel-title text-muted font-light"><?php _e("Contacts added per month","zero-bs-crm");?></h4>
                </div>
                <canvas id="growth-chart" width="500px" height="400px"></canvas>
            </div>
        </div>
    <?php } 

    //what we want here is a chart (data summary) showing, per month, the contact growth 
    //limit to 24 months, but can (and probably should) add data filters to this later
    ?>

    <script type="text/javascript">
    
        jQuery(document).ready(function(){
            // WH added: don't draw if not there :)
            if (jQuery('#growth-chart').length){
                var ctx = document.getElementById("growth-chart");
                new Chart( ctx, {
                    type: 'bar',
                    data: {
                        labels: [<?php echo $labels; ?>],
                        datasets: [
                        {
                            label: "",
                            backgroundColor: [<?php echo $background; ?>],
                            data: [<?php echo $chartdataStr; ?>]
                        }
                        ]
                    },
                    options: {
                        responsive: true, 
                        maintainAspectRatio: false,
                        legend: { display: false },
                        title: {
                        display: false,
                        text: ''
                        },
                        scales: {
                        yAxes: [{
                            display: true,
                            ticks: {
                                beginAtZero: true   // minimum value will be 0.
                            }
                        }]
                    }


                    }
                });

            }
        });
    </script>



    <?php


}
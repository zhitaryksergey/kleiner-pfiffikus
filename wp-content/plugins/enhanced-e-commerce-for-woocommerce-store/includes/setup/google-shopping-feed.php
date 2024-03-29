<?php
class GoogleShoppingFeed {
  protected $site_url="";  
  protected $TVC_Admin_Helper="";
  protected $subscriptionId = "";
  public function __construct() {
    $this->TVC_Admin_Helper = new TVC_Admin_Helper();
    $this->subscriptionId = $this->TVC_Admin_Helper->get_subscriptionId(); 
    $this->site_url = "admin.php?page=conversios-google-shopping-feed&tab="; 
    $this->TVC_Admin_Helper->need_auto_update_db();    
    $this->create_form();
  }
  public function add_list_html($title, $val){
    return '<li>
      <div class="row">
        <div class="col-7 col-md-8 align-self-center pr-0">
            <span class="text">'.esc_attr($title).'</span>
        </div>
        <div class="col-5 col-md-4 align-self-center text-right">
            <span class="text"><strong>'. esc_attr($val).'</strong></span>
        </div>
      </div>
    </li>';
  }
  public function configuration_list_html($title, $val){
    $imge = (isset($val) && $val != "" && $val != 0) ? esc_url_raw(ENHANCAD_PLUGIN_URL.'/admin/images/config-success.svg') : esc_url_raw(ENHANCAD_PLUGIN_URL.'/admin/images/exclaimation.png');
    return '<li>
      <div class="row">
        <div class="col-7 col-md-7 col-lg-9 align-self-center pr-0">
            <span class="text">'.esc_attr($title).'</span>
        </div>
        <div class="col-5 col-md-5 col-lg-3 text-end">
            <div class="list-image"><img src="'.esc_url_raw($imge).'"></div>
        </div>
      </div>
    </li>';
  }
  public function configuration_error_list_html($title, $val, $call_domain_claim, $googleDetail){
    if(isset($googleDetail->google_merchant_center_id) && $googleDetail->google_merchant_center_id && $this->subscriptionId != "" ){
      return '<li>
        <div class="row">
          <div class="col-7 col-md-7 col-lg-7 align-self-center pr-0">
              <span class="text">'.esc_attr($title).'</span>
          </div>
          <div class="col-5 col-md-5 col-lg-5 text-end">
              <div class="list-image"><img id="refresh_'.esc_attr($call_domain_claim).'" onclick="'.esc_attr($call_domain_claim).'();" src="'. esc_url_raw(ENHANCAD_PLUGIN_URL.'/admin/images/refresh.png').'"><img src="' .esc_url_raw( ENHANCAD_PLUGIN_URL.'/admin/images/exclaimation.png').'" alt="no-config-success"/></div>
          </div>
        </div>
      </li>';
    }else{
      return '<li>
        <div class="row">
          <div class="col-7 col-md-7 col-lg-7 align-self-center pr-0">
              <span class="text">'.esc_attr($title).'</span>
          </div>
          <div class="col-5 col-md-5 col-lg-5 align-self-center text-right">
              <div class="list-image"><img src="' . esc_url_raw(ENHANCAD_PLUGIN_URL.'/admin/images/exclaimation.png').'" alt="no-config-success"/></div>
          </div>
        </div>
      </li>';
    }
  }
  public function create_form() {
    $googleDetail = [];    
    $google_detail = $this->TVC_Admin_Helper->get_ee_options_data();
    if(isset($google_detail['setting'])){
      if ($google_detail['setting']) {
        $googleDetail = $google_detail['setting'];
      }
    }      

    $syncProductStat = [];        
    $args = array('post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => -1);
    $products = new WP_Query($args);
    $totalActiveWooProduct = $products->found_posts;
    if(isset($google_detail['prod_sync_status'])){
      if ($google_detail['prod_sync_status']) {
        $syncProductStat = $google_detail['prod_sync_status'];
      }
    }
    $sync_product_total = 0; $sync_product_approved = 0; $sync_product_disapproved = 0; $sync_product_pending = 0;
    if($syncProductStat){
      $sync_product_total = (property_exists($syncProductStat,"total")) ? esc_attr($syncProductStat->total) : "0";
      $sync_product_approved = (property_exists($syncProductStat,"approved")) ? esc_attr($syncProductStat->approved) : "0";
      $sync_product_disapproved = (property_exists($syncProductStat,"disapproved")) ? esc_attr($syncProductStat->disapproved) : "0";
      $sync_product_pending = (property_exists($syncProductStat,"pending")) ? esc_attr($syncProductStat->pending) : "0";
    }
    $totalCampaigns = 0;$campaignActive = 0; $campaignClicks = 0;
    $campaignCost = 0; $campaignConversions = 0; $campaignSales = 0;
    // Get currency
    $currency = $this->TVC_Admin_Helper->get_user_currency_symbol();
    if(isset($google_detail['campaigns_list'])){
      if ($google_detail['campaigns_list']) {
        $campaigns_list = $google_detail['campaigns_list'];
        $totalCampaigns = count($campaigns_list);
        foreach ($campaigns_list as $campaign) {
          if ($campaign->active == 1) {
            $campaignActive = $campaignActive + $campaign->active;
          }
          $campaignClicks = $campaignClicks + esc_attr($campaign->clicks);
          $row_campaign_cost = esc_attr($campaign->cost);
          $campaignCost = $campaignCost + esc_attr($row_campaign_cost);
          $campaignConversions = $campaignConversions + esc_attr($campaign->conversions);
          $campaignSales = $campaignSales + esc_attr($campaign->sales);
        }
        if (count($campaigns_list) > 0) {
          $campaignConversions = $campaignConversions / count($campaigns_list);
        }
      }
      $campaignActive = (isset($googleDetail->google_ads_id) && esc_attr($googleDetail->google_ads_id) != "" ? esc_attr($campaignActive) : '0');
      $campaignCost = (isset($googleDetail->google_ads_id) && esc_attr($googleDetail->google_ads_id) != "" ? esc_attr($currency) . esc_attr($campaignCost) : '0');
      $campaignClicks = (isset($googleDetail->google_ads_id) && esc_attr($googleDetail->google_ads_id) != "" ? $campaignClicks : '0');
      $campaignConversions = (isset($googleDetail->google_ads_id) && esc_attr($googleDetail->google_ads_id) != "" ? esc_attr($campaignConversions) . "%" : '0');
      $campaignSales = (isset($googleDetail->google_ads_id) && esc_attr($googleDetail->google_ads_id) != "" ? esc_attr($currency) . esc_attr($campaignSales) : '0');
   }
  $last_api_sync_up = "";
  $date_formate=get_option('date_format')." ".get_option('time_format');
  if($date_formate ==""){
    $date_formate = 'M-d-Y H:i';
  }
  if(isset($google_detail['sync_time']) && esc_attr($google_detail['sync_time'])){   
    $last_api_sync_up = date( $date_formate, esc_attr($google_detail['sync_time']));  
  }
  $is_need_to_update = $this->TVC_Admin_Helper->is_need_to_update_api_to_db();
?>
<div class="tab-content">
	<div class="tab-pane show active" id="googleShoppingFeed">
    <div class="tab-card">
      <div class="row">
        <div class="col-md-6 col-lg-8 border-right">
          <?php if(esc_attr($this->subscriptionId) != ""){?>
            <div class="tvc-api-sunc">                    
              <span>
              <?php if(esc_attr($last_api_sync_up)){
                echo esc_html__("Details last synced at ","conversios").esc_attr($last_api_sync_up); 
              }else{
                echo esc_html__("Refresh sync up","conversios");
              }?></span><img id="refresh_api" onclick="call_tvc_api_sync_up();" src="<?php echo esc_url_raw(ENHANCAD_PLUGIN_URL.'/admin/images/refresh.png'); ?>">                    
            </div>
          <?php } ?>
          <?php          
            $last_auto_sync = $this->TVC_Admin_Helper->get_last_auto_sync_product_info();
            if(!empty($last_auto_sync) && isset($googleDetail->google_merchant_center_id) && $googleDetail->google_merchant_center_id && $this->subscriptionId != ""){

              $status = isset($last_auto_sync['status'])?esc_attr($last_auto_sync['status']):0;
              $status_text = array("0"=>"Failed","1"=>"Completed");
              $create_sync = (isset($last_auto_sync['create_sync']))?$last_auto_sync['create_sync']:"";
              $create_sync = date($date_formate,strtotime($create_sync));
              $next_sync = (isset($last_auto_sync['next_sync']))?$last_auto_sync['next_sync']:"";
              $next_sync = date($date_formate,strtotime($next_sync));
              ?>
              <div class="product-auto-sync-details">
                <strong><?php esc_html_e("Last auto product sync details","conversios"); ?></strong>
                <table>
                  <tr><th><?php esc_html_e("Last sync","conversios"); ?></th><th><?php esc_html_e("Sync product","conversios"); ?></th><th><?php esc_html_e("Status","conversios"); ?></th><th><?php esc_html_e("Upcoming sync","conversios"); ?></th></tr>
                  <tr>
                    <td><?php echo  esc_attr($create_sync);?></td>
                    <td><?php echo (isset($last_auto_sync['total_sync_product']))?esc_attr($last_auto_sync['total_sync_product']):"";?></td>
                    <td><?php echo esc_attr($status_text[$status]); ?></td>
                    <td><?php echo  esc_attr($next_sync);?></td>
                  </tr>
                </table>
              </div>
              <?php
            }
          ?>
          <div class="configuration-section" id="config-pt1">
            <div class="row confg-card gsf-sec">                    
              <div class="col-md-12 col-lg-4 mb-3 mb-lg-0">
                <div class="card configure-card">
                  <div class="card-header">
                    <h4 class="confg-title"><?php esc_html_e("Configuration","conversios"); ?></h4>
                  </div>
                  <div class="card-body">
                    <ul class="list-unstyled"><?php
                    $is_domain_claim = (isset($googleDetail->is_domain_claim))?esc_attr($googleDetail->is_domain_claim):"";
                    $is_site_verified = (isset($googleDetail->is_site_verified))?esc_attr($googleDetail->is_site_verified):"";
                      echo $this->configuration_list_html("Google merchant center",(isset($googleDetail->google_merchant_center_id))?esc_attr($googleDetail->google_merchant_center_id):"");
                      if($is_site_verified ==1){
                        echo $this->configuration_list_html(esc_html__("Site Verified","conversios"), esc_attr($is_site_verified));
                      }else{
                        echo $this->configuration_error_list_html(esc_html__("Site Verified","conversios"),esc_attr($is_site_verified),"call_site_verified", $googleDetail);
                      }
                      if($is_domain_claim ==1){
                        echo $this->configuration_list_html(esc_html__("Domain claim","conversios"),esc_attr($is_domain_claim));
                      }else{
                        echo $this->configuration_error_list_html(esc_html__("Domain claim","conversios"),esc_attr($is_domain_claim), 'call_domain_claim', $googleDetail);
                      }
                      echo $this->configuration_list_html(esc_html__("Google Ads linking","conversios"),((isset($googleDetail->google_ads_id)))?esc_attr($googleDetail->google_ads_id):"");
                      ?>
                      </ul>
                  </div>
                  <div class="card-footer">
                    <a href="<?php echo esc_url_raw($this->site_url.'gaa_config_page'); ?>" class="btn btn-primary" id="configuration"><?php esc_html_e("Edit","conversios"); ?></a>
                  </div>
                </div>
              </div>
             <div class="col-md-12 col-lg-4 mb-3 mb-lg-0">
               <div class="card">
                  <div class="card-header">
                    <h4 class="confg-title"><?php esc_html_e("Product Sync","conversios"); ?></h4>
                  </div>
                  <div class="card-body">
                    <ul class="list-unstyled">
                      <?php
                      echo $this->add_list_html(esc_html__("Active products in WooCommerce","conversios"), $totalActiveWooProduct)
                      .$this->add_list_html(esc_html__("Total synced products in Merchant center","conversios"), $sync_product_total)
                      .$this->add_list_html(esc_html__("Approved","conversios"), $sync_product_approved)
                      .$this->add_list_html(esc_html__("Disapproved","conversios"), $sync_product_disapproved)
                      .$this->add_list_html(esc_html__("Pending","conversios"), $sync_product_pending);
                      ?>
                    </ul>
                  </div>
                  <?php
                  if (isset($googleDetail->google_merchant_center_id) && esc_attr($googleDetail->google_merchant_center_id) != "") {?>
                  <div class="card-footer">
                    <a href="<?php echo esc_url_raw($this->site_url.'sync_product_page'); ?>" class="btn btn-primary" id="product-sync"><?php esc_html_e("Edit","conversios"); ?></a>
                  </div>
                  <?php } ?>
                </div>
              </div>
              <div class="col-md-12 col-lg-4 mb-3 mb-lg-0">
               <div class="card">
                  <div class="card-header">
                    <h4 class="confg-title"><?php esc_html_e("Smart  Shopping Campaigns","conversios"); ?></h4>
                  </div>
                  <div class="card-body">
                    <ul class="list-unstyled">
                      <?php
                      echo $this->add_list_html(esc_html__("Total campaign","conversios"), $totalCampaigns)
                      .$this->add_list_html(esc_html__("Active campaigns","conversios"), $campaignActive)
                      .$this->add_list_html(esc_html__("Cost","conversios"), $campaignCost)
                      .$this->add_list_html(esc_html__("Click","conversios"), $campaignClicks)
                      .$this->add_list_html(esc_html__("Conversion%","conversios"), $campaignConversions)
                      .$this->add_list_html(esc_html__("Sales","conversios"), $campaignSales); ?>               
                    </ul>
                  </div>
                  <?php if (isset($googleDetail->google_ads_id) && esc_attr($googleDetail->google_ads_id) != "") { ?>
                  <div class="card-footer">
                    <a href="<?php echo esc_url_raw($this->site_url.'shopping_campaigns_page'); ?>" id="smart-shopping-campaigns" class="btn btn-primary"><?php esc_html_e("Edit","conversios"); ?></a>
                  </div>
                  <?php }?>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6 col-lg-4">
          <?php echo get_tvc_help_html(); ?>
        </div>
      </div>
    </div>
	</div>
</div>
		
<script type="text/javascript">
  function call_site_verified(){
    var tvs_this = event.target;
    $("#refresh_call_site_verified").css("visibility","hidden");
    $(tvs_this).after('<div class="domain-claim-spinner tvc-nb-spinner" id="site-verified-spinner"></div>');
    jQuery.post(tvc_ajax_url,{
      action: "tvc_call_site_verified"
    },function( response ){
      var rsp = JSON.parse(response);    
      if(rsp.status == "success"){        
        tvc_helper.tvc_alert("success","",rsp.message,true);
        location.reload();
      }else{
        tvc_helper.tvc_alert("error","",rsp.message,true);
      }
      $("#site-verified-spinner").remove();
    });
  }
  function call_domain_claim(){
    var tvs_this = event.target;
    $("#refresh_call_domain_claim").css("visibility","hidden");
    $(tvs_this).after('<div class="domain-claim-spinner tvc-nb-spinner" id="domain-claim-spinner"></div>');
    jQuery.post(tvc_ajax_url,{
      action: "tvc_call_domain_claim"
    },function( response ){
      var rsp = JSON.parse(response);    
      if(rsp.status == "success"){
        tvc_helper.tvc_alert("success","",rsp.message,true);        
        //alert(rsp.message);
        location.reload();
      }else{
        tvc_helper.tvc_alert("error","",rsp.message,true)
      }
      $("#domain-claim-spinner").remove();
    });
  }
  $(document).ready(function() {
    var is_need_to_update = "<?php echo esc_attr($is_need_to_update); ?>";
    if(is_need_to_update == 1 || is_need_to_update == true){
      call_tvc_api_sync_up();
    }    
  });
  function call_tvc_api_sync_up(){
    var tvs_this = $("#refresh_api");
    $("#tvc_msg").remove();
    $("#refresh_api").css("visibility","hidden");
    $(tvs_this).after('<div class="tvc-nb-spinner" id="tvc-nb-spinner"></div>');
    tvc_helper.tvc_alert("error","<?php esc_html_e("Attention !","conversios"); ?>","<?php esc_html_e("Sync up is in the process do not refresh the page. it may take few minutes.","conversios"); ?>");
    jQuery.post(tvc_ajax_url,{
      action: "tvc_call_api_sync"
    },function( response ){
      var rsp = JSON.parse(response);    
      if(rsp.error == false){
        $("#tvc-nb-spinner").remove();
        tvc_helper.tvc_alert("success","",rsp.message,true,2000);
      }else{
        tvc_helper.tvc_alert("error","",rsp.message,true,2000);
      }  
      setTimeout(function(){ location.reload();}, 2000);    
    });
  }  
</script>
  <?php
  }
}
?>
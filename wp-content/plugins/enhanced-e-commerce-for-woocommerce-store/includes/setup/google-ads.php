<?php
class GoogleAds {
  protected $TVC_Admin_Helper="";
  protected $url = "";
  protected $subscriptionId = "";
  protected $google_detail;
  protected $customApiObj;
  protected $plan_id;
  public function __construct($theURL = '') {
    $this->TVC_Admin_Helper = new TVC_Admin_Helper();
    $this->customApiObj = new CustomApi();
    $this->url = $this->TVC_Admin_Helper->get_onboarding_page_url(); 
    $this->subscriptionId = $this->TVC_Admin_Helper->get_subscriptionId(); 
    $this->google_detail = $this->TVC_Admin_Helper->get_ee_options_data(); 
    $this->plan_id = $this->TVC_Admin_Helper->get_plan_id();     
    $this->create_form();
  }

  public function create_form() {
    $message = ""; $class="";
    if (isset($_POST['google-add'])) {
      $response = $this->customApiObj->updateTrackingOption($_POST);
      $googleDetail = $this->google_detail;
      $googleDetail_setting = $this->google_detail['setting'];
      if(isset($_POST['remarketing_tags'])){
        update_option('ads_ert', sanitize_text_field($_POST['remarketing_tags']) );
        $googleDetail_setting->remarketing_tags = sanitize_text_field($_POST['remarketing_tags']);
      }else{
        update_option('ads_ert', 0);
        $googleDetail_setting->remarketing_tags = 0;
      }
      if(isset($_POST['dynamic_remarketing_tags'])){
        update_option('ads_edrt', sanitize_text_field($_POST['dynamic_remarketing_tags']) );
        $googleDetail_setting->dynamic_remarketing_tags = sanitize_text_field($_POST['dynamic_remarketing_tags']);
      }else{
        update_option('ads_edrt', 0);
        $googleDetail_setting->dynamic_remarketing_tags = 0;
      }
      if($this->plan_id != 1){
        if(isset($_POST['google_ads_conversion_tracking'])){
          update_option('google_ads_conversion_tracking', sanitize_text_field($_POST['google_ads_conversion_tracking']) );
          $googleDetail_setting->google_ads_conversion_tracking = sanitize_text_field($_POST['google_ads_conversion_tracking']);
          $this->TVC_Admin_Helper->update_conversion_send_to();
        }else{
          update_option('google_ads_conversion_tracking', 0);
          $googleDetail_setting->google_ads_conversion_tracking = 0;
        }
      }
      if(isset($_POST['link_google_analytics_with_google_ads'])){
        $googleDetail_setting->link_google_analytics_with_google_ads = sanitize_text_field($_POST['link_google_analytics_with_google_ads']);
      }else{
        $googleDetail_setting->link_google_analytics_with_google_ads = 0;
      }
      $googleDetail['setting'] = $googleDetail_setting;                  
      $this->TVC_Admin_Helper->set_ee_options_data($googleDetail);      
      $class = 'alert-message tvc-alert-success';
      $message = esc_html__("Your tracking options have been saved.","conversios");                 
    }
        
    $googleDetail = [];
    if(isset($this->google_detail['setting'])){
      if ($this->google_detail['setting']) {
        $googleDetail = $this->google_detail['setting'];
      }
    }
    ?>
<div class="tab-content">
  <?php if($message){
    printf('<div class="%1$s"><div class="alert">%2$s</div></div>', esc_attr($class), esc_html($message));
  }?>
	<div class="tab-pane show active" id="googleAds">
		<div class="tab-card" >
			<div class="row">
        <div class="col-md-6 col-lg-8 border-right">
          <form method="post" name="google-analytic" class="tvc_ee_plugin_form"> 
        		<input type="hidden" name="subscription_id" value="<?php echo (($this->subscriptionId)?esc_attr($this->subscriptionId):"");?>">
              <div class="google-account-analytics">
                <div class="row mb-3">
                  <div class="col-6 col-md-6 col-lg-6">
                      <h2 class="ga-title"><?php esc_html_e("Connected Google Ads account:","conversios"); ?></h2>
                  </div>
                  <div class="col-6 col-md-6 col-lg-6 text-right">
                    <div class="acc-num">
                      <p class="ga-text">
                        <?php echo  (isset($googleDetail->google_ads_id) && $googleDetail->google_ads_id != '') ? esc_attr($googleDetail->google_ads_id) :'<span>'. esc_html__('Get started','conversios').'</span>'; ?>
                      </p>
                      <?php
                      if (isset($googleDetail->google_ads_id) && $googleDetail->google_ads_id != '') {
                        echo '<p class="ga-text text-right"><a href="' . esc_url_raw($this->url) . '" class="text-underline"><img src="'. esc_url_raw(ENHANCAD_PLUGIN_URL.'/admin/images/icon/refresh.svg').'" alt="refresh"/></a></p>';
                      } else { 
                        echo '<p class="ga-text text-right"><a href="' . $this->url . '" class="text-underline"><img src="'. esc_url_raw(ENHANCAD_PLUGIN_URL.'/admin/images/icon/add.svg').'" alt="connect account"/></a></p>';
                      }?>
                    </div>
                  </div>
                  
                </div>
                <div class="row mb-3">
                  <div class="col-6 col-md-6 col-lg-6">
                    <h2 class="ga-title"><?php esc_html_e("Linked Google Analytics Account:","conversios"); ?></h2>
                  </div>
                  <div class="col-6 col-md-6 col-lg-6 text-right">
                    <div class="acc-num">
                      <p class="ga-text">
                        <?php echo isset($googleDetail->property_id) && $googleDetail->property_id != '' ? esc_attr($googleDetail->property_id) : '<span>'. esc_html__('Get started','conversios').'</span>';?>
                      </p>
                      <?php
                      if(isset($googleDetail->property_id) && $googleDetail->property_id != ''){
                          echo '<p class="ga-text text-right"><a href="' . esc_url_raw($this->url) . '" class="text-underline"><img src="'. esc_url_raw(ENHANCAD_PLUGIN_URL.'/admin/images/icon/refresh.svg').'" alt="refresh"/></a></p>';
                      }else{
                          echo '<p class="ga-text text-right"><a href="' . $this->url . '" class="text-underline"><img src="'. esc_url_raw(ENHANCAD_PLUGIN_URL.'/admin/images/icon/add.svg').'" alt="connect account"/></a></p>';
                      } ?>
                    </div>
                  </div>                  
                </div>
              <div class="row mb-3">
                <div class="col-6 col-md-6 col-lg-6">
                  <h2 class="ga-title"><?php esc_html_e("Linked Google Merchant Center Account:","conversios"); ?></h2>
                </div>
                <div class="col-6 col-md-6 col-lg-6 text-right">
                  <div class="acc-num">
                    <p class="ga-text"><?php echo isset($googleDetail->google_merchant_center_id) && $googleDetail->google_merchant_center_id != '' ? esc_attr($googleDetail->google_merchant_center_id) :'<span>'. esc_html__('Get started','conversios').'</span>'; ?>
                    </p>
                    <?php
                    if (isset($googleDetail->google_merchant_center_id) && $googleDetail->google_merchant_center_id != '') {
                      echo '<p class="ga-text text-right"><a target="_blank" href="' . $this->url . '" class="text-underline"><img src="'. esc_url_raw(ENHANCAD_PLUGIN_URL.'/admin/images/icon/refresh.svg').'" alt="refresh"/></a></p>';
                    } else {
                      echo '<p class="ga-text text-right"><a href="#" class="text-underline" data-toggle="modal" data-target="#tvc_google_connect"><img src="'.esc_url_raw(ENHANCAD_PLUGIN_URL.'/admin/images/icon/add.svg').'" alt="connect account"/></a></p>';
                    } ?>
                  </div>
                </div>                
              </div>
              <?php
              if (isset($googleDetail->google_ads_id) && $googleDetail->google_ads_id != '') { ?>
              <div class="row">
                <div class="col-md-12">
                  <div class="form-group">
                    <div class="tvc-custom-control tvc-custom-checkbox">
                      <input type="checkbox" class="tvc-custom-control-input" id="customCheck1" name="remarketing_tags" value="1" <?php echo (esc_attr($googleDetail->remarketing_tags) == 1) ? 'checked="checked"' : ''; ?> >
                      <label class="custom-control-label" for="customCheck1"><?php esc_html_e("Enable remarketing tags","conversios"); ?></label>
                    </div>
                  </div>
                </div>
                <div class="col-md-12">
                  <div class="form-group">
                    <div class="tvc-custom-control tvc-custom-checkbox">
                      <input type="checkbox" class="tvc-custom-control-input" id="customCheck2" name="dynamic_remarketing_tags" value="1" <?php echo (esc_attr($googleDetail->dynamic_remarketing_tags) == 1) ? 'checked="checked"' : ''; ?>>
                      <label class="custom-control-label" for="customCheck2"><?php esc_html_e("Enable dynamic remarketing tags","conversios"); ?></label>
                    </div>
                  </div>
                </div>
                <?php if($this->plan_id != 1){ ?>
                <div class="col-md-12">
                  <div class="form-group">
                    <div class="tvc-custom-control tvc-custom-checkbox">
                      <input type="checkbox" class="tvc-custom-control-input" id="google_ads_conversion_tracking" name="google_ads_conversion_tracking" value="1" <?php echo (esc_attr($googleDetail->google_ads_conversion_tracking) == 1) ? 'checked="checked"' : ''; ?>>
                      <label class="custom-control-label" for="google_ads_conversion_tracking"><?php esc_html_e("Enable Google Ads conversion tracking","conversios"); ?></label>
                    </div>
                  </div>
                </div>
              <?php } ?>
                <div class="col-md-12">
                  <div class="form-group">
                    <div class="tvc-custom-control tvc-custom-checkbox">
                      <input type="checkbox" class="tvc-custom-control-input" id="customCheck3" name="link_google_analytics_with_google_ads" value="1" <?php echo (esc_attr($googleDetail->link_google_analytics_with_google_ads) == 1) ? 'checked="checked"' : ''; ?> >
                      <label class="custom-control-label" for="customCheck3"><?php esc_html_e("Link Google analytics with google ads","conversios"); ?></label>
                    </div>
                  </div>
                </div>
              </div>
              <?php
              }else{ ?>
              <h2 class="ga-title"><?php esc_html_e("Connect Google Ads account to enable below features.","conversios"); ?></h2>
              <br>
              <ul>
                <li><img src="<?php echo esc_url_raw(ENHANCAD_PLUGIN_URL.'/admin/images/config-success.svg'); ?>" alt="configuration  success" class="config-success"><?php esc_html_e("Enable remarketing tags","conversios"); ?></li>
                <li><img src="<?php echo esc_url_raw(ENHANCAD_PLUGIN_URL.'/admin/images/config-success.svg'); ?>" alt="configuration  success" class="config-success"><?php esc_html_e("Enable dynamic remarketing tags","conversios"); ?></li>
                <li><img src="<?php echo esc_url_raw(ENHANCAD_PLUGIN_URL.'/admin/images/config-success.svg'); ?>" alt="configuration  success" class="config-success"><?php esc_html_e("Link Google analytics with google ads","conversios"); ?></li>        
              </ul>
              <?php
              } ?>
            </div>
            <?php
            if (isset($googleDetail->google_ads_id) && $googleDetail->google_ads_id != '') { ?>
            <div class="text-left">
              <button type="submit" id="google-add" class="btn btn-primary btn-success" name="google-add"><?php esc_html_e("Save","conversios"); ?></button>
            </div>
            <?php } ?>
          </form>
        </div>
        <div class="col-md-6 col-lg-4">          
          <?php echo get_tvc_google_ads_help_html(); ?>          
        </div>
      </div>
    </div>
	</div>
</div>		
<?php 
    }
}
?>
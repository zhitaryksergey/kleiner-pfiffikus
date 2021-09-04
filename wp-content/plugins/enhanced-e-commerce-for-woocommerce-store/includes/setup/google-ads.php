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
        update_option('ads_ert', $_POST['remarketing_tags']);
        $googleDetail_setting->remarketing_tags = $_POST['remarketing_tags'];
      }else{
        update_option('ads_ert', 0);
        $googleDetail_setting->remarketing_tags = 0;
      }
      if(isset($_POST['dynamic_remarketing_tags'])){
        update_option('ads_edrt', $_POST['dynamic_remarketing_tags']);
        $googleDetail_setting->dynamic_remarketing_tags = $_POST['dynamic_remarketing_tags'];
      }else{
        update_option('ads_edrt', 0);
        $googleDetail_setting->dynamic_remarketing_tags = 0;
      }
      if($this->plan_id != 1){
        if(isset($_POST['google_ads_conversion_tracking'])){
          update_option('google_ads_conversion_tracking', $_POST['google_ads_conversion_tracking']);
          $googleDetail_setting->google_ads_conversion_tracking = $_POST['google_ads_conversion_tracking'];
          $this->TVC_Admin_Helper->update_conversion_send_to();
        }else{
          update_option('google_ads_conversion_tracking', 0);
          $googleDetail_setting->google_ads_conversion_tracking = 0;
        }
      }
      if(isset($_POST['link_google_analytics_with_google_ads'])){
        $googleDetail_setting->link_google_analytics_with_google_ads = $_POST['link_google_analytics_with_google_ads'];
      }else{
        $googleDetail_setting->link_google_analytics_with_google_ads = 0;
      }
      $googleDetail['setting'] =$googleDetail_setting;                  
      $this->TVC_Admin_Helper->set_ee_options_data($googleDetail);      
      $class = 'alert-message tvc-alert-success';
      $message = esc_html__('Your tracking options have been saved.');                 
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
        		<input type="hidden" name="subscription_id" value="<?php echo (($this->subscriptionId)?$this->subscriptionId:"");?>">
              <div class="google-account-analytics">
                <div class="row mb-3">
                  <div class="col-6 col-md-6 col-lg-6">
                      <h2 class="ga-title">Connected Google Ads account:</h2>
                  </div>
                  <div class="col-6 col-md-6 col-lg-6 text-right">
                    <div class="acc-num">
                      <p class="ga-text">
                        <?php echo  ((isset($googleDetail->google_ads_id) && $googleDetail->google_ads_id != '') ? $googleDetail->google_ads_id : '<span>Get started</span>'); ?>
                      </p>
                      <?php
                      if (isset($googleDetail->google_ads_id) && $googleDetail->google_ads_id != '') {
                        echo '<p class="ga-text text-right"><a href="' . $this->url . '" class="text-underline"><img src="'. ENHANCAD_PLUGIN_URL.'/admin/images/icon/refresh.svg" alt="refresh"/></a></p>';
                      } else { 
                        echo '<p class="ga-text text-right"><a href="' . $this->url . '" class="text-underline"><img src="'. ENHANCAD_PLUGIN_URL.'/admin/images/icon/add.svg" alt="connect account"/></a></p>';
                      }?>
                    </div>
                  </div>
                  
                </div>
                <div class="row mb-3">
                  <div class="col-6 col-md-6 col-lg-6">
                    <h2 class="ga-title">Linked Google Analytics Account:</h2>
                  </div>
                  <div class="col-6 col-md-6 col-lg-6 text-right">
                    <div class="acc-num">
                      <p class="ga-text">
                        <?php echo (isset($googleDetail->property_id) && $googleDetail->property_id != '' ? $googleDetail->property_id : '<span>Get started</span>');?>
                      </p>
                      <?php
                      if(isset($googleDetail->property_id) && $googleDetail->property_id != ''){
                          echo '<p class="ga-text text-right"><a href="' . $this->url . '" class="text-underline"><img src="'. ENHANCAD_PLUGIN_URL.'/admin/images/icon/refresh.svg" alt="refresh"/></a></p>';
                      }else{
                          echo '<p class="ga-text text-right"><a href="' . $this->url . '" class="text-underline"><img src="'. ENHANCAD_PLUGIN_URL.'/admin/images/icon/add.svg" alt="connect account"/></a></p>';
                      } ?>
                    </div>
                  </div>                  
                </div>
              <div class="row mb-3">
                <div class="col-6 col-md-6 col-lg-6">
                  <h2 class="ga-title">Linked Google Merchant Center Account:</h2>
                </div>
                <div class="col-6 col-md-6 col-lg-6 text-right">
                  <div class="acc-num">
                    <p class="ga-text"><?php echo (isset($googleDetail->google_merchant_center_id) && $googleDetail->google_merchant_center_id != '' ? $googleDetail->google_merchant_center_id : '<span>Get started</span>'); ?>
                    </p>
                    <?php
                    if (isset($googleDetail->google_merchant_center_id) && $googleDetail->google_merchant_center_id != '') {
                      echo '<p class="ga-text text-right"><a target="_blank" href="' . $this->url . '" class="text-underline"><img src="'. ENHANCAD_PLUGIN_URL.'/admin/images/icon/refresh.svg" alt="refresh"/></a></p>';
                    } else {
                      echo '<p class="ga-text text-right"><a href="#" class="text-underline" data-toggle="modal" data-target="#tvc_google_connect"><img src="'. ENHANCAD_PLUGIN_URL.'/admin/images/icon/add.svg" alt="connect account"/></a></p>';
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
                      <input type="checkbox" class="tvc-custom-control-input" id="customCheck1" name="remarketing_tags" value="1" <?php echo ($googleDetail->remarketing_tags == 1) ? 'checked="checked"' : ''; ?> >
                      <label class="custom-control-label" for="customCheck1">Enable remarketing tags</label>
                    </div>
                  </div>
                </div>
                <div class="col-md-12">
                  <div class="form-group">
                    <div class="tvc-custom-control tvc-custom-checkbox">
                      <input type="checkbox" class="tvc-custom-control-input" id="customCheck2" name="dynamic_remarketing_tags" value="1" <?php echo ($googleDetail->dynamic_remarketing_tags == 1) ? 'checked="checked"' : ''; ?>>
                      <label class="custom-control-label" for="customCheck2">Enable dynamic remarketing tags</label>
                    </div>
                  </div>
                </div>
                <?php if($this->plan_id != 1){ ?>
                <div class="col-md-12">
                  <div class="form-group">
                    <div class="tvc-custom-control tvc-custom-checkbox">
                      <input type="checkbox" class="tvc-custom-control-input" id="google_ads_conversion_tracking" name="google_ads_conversion_tracking" value="1" <?php echo ($googleDetail->google_ads_conversion_tracking == 1) ? 'checked="checked"' : ''; ?>>
                      <label class="custom-control-label" for="google_ads_conversion_tracking">Enable Google Ads conversion tracking</label>
                    </div>
                  </div>
                </div>
              <?php } ?>
                <div class="col-md-12">
                  <div class="form-group">
                    <div class="tvc-custom-control tvc-custom-checkbox">
                      <input type="checkbox" class="tvc-custom-control-input" id="customCheck3" name="link_google_analytics_with_google_ads" value="1" <?php echo ($googleDetail->link_google_analytics_with_google_ads == 1) ? 'checked="checked"' : ''; ?> >
                      <label class="custom-control-label" for="customCheck3">Link Google analytics with google ads</label>
                    </div>
                  </div>
                </div>
              </div>
              <?php
              }else{
                $icon_img ='<img src="'.ENHANCAD_PLUGIN_URL.'/admin/images/config-success.svg" alt="configuration  success" class="config-success">';
              ?>
              <h2 class="ga-title">Connect Google Ads account to enable below features.</h2>
              <br>
              <ul>
                  <li><?php echo $icon_img;?>Enable remarketing tags</li>
                  <li><?php echo $icon_img;?>Enable dynamic remarketing tags</li>
                  <li><?php echo $icon_img;?>Link Google analytics with google ads</li>        
              </ul>
              <?php
              } ?>
            </div>
            <?php
            if (isset($googleDetail->google_ads_id) && $googleDetail->google_ads_id != '') { ?>
            <div class="text-left">
                <button type="submit" id="google-add" class="btn btn-primary btn-success" name="google-add">Save</button>
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
<?php echo get_connect_google_popup_html();
    }
}
?>
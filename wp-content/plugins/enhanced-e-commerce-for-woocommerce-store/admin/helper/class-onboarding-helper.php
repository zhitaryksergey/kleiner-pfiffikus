<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       
 * @since      1.0.0
 *
 * Woo Order Reports
 */

if(!defined('ABSPATH')){
	exit; // Exit if accessed directly
}
if(!class_exists('Conversios_Onboarding_Helper')):
	class Conversios_Onboarding_Helper{
		protected $apiDomain;
		protected $token;
		public function __construct(){
			$this->req_int();
      //analytics
			add_action('wp_ajax_get_analytics_web_properties', array($this,'get_analytics_web_properties') );
      add_action('wp_ajax_save_analytics_data', array($this,'save_analytics_data') );
      //googl_ads
      add_action('wp_ajax_list_googl_ads_account', array($this,'list_googl_ads_account') );
      add_action('wp_ajax_create_google_ads_account', array($this,'create_google_ads_account') );
      add_action('wp_ajax_save_google_ads_data', array($this,'save_google_ads_data') );
      add_action('wp_ajax_link_analytic_to_ads_account', array($this,'link_analytic_to_ads_account') );
      add_action('wp_ajax_get_conversion_list', array($this,'get_conversion_list') );
      
      //google_merchant
      add_action('wp_ajax_list_google_merchant_account', array($this,'list_google_merchant_account') );
      add_action('wp_ajax_create_google_merchant_center_account', array($this,'create_google_merchant_center_account') );
      add_action('wp_ajax_save_merchant_data', array($this,'save_merchant_data') );
      add_action('wp_ajax_link_google_ads_to_merchant_center', array($this,'link_google_ads_to_merchant_center') );

      //get subscription details
      add_action('wp_ajax_get_subscription_details', array($this,'get_subscription_details') );
      add_action('wp_ajax_update_setup_time_to_subscription', array($this,'update_setup_time_to_subscription') );
      
      
      
		}

		public function req_int(){
			if (!class_exists('CustomApi.php')) {
        require_once(ENHANCAD_PLUGIN_DIR . 'includes/setup/CustomApi.php');
      }
		}
		protected function admin_safe_ajax_call( $nonce, $registered_nonce_name ) {
			// only return results when the user is an admin with manage options
			if ( is_admin() && wp_verify_nonce($nonce,$registered_nonce_name) ) {
				return true;
			} else {
				return false;
			}
		}
		
		/**
		 * Ajax code for get analytics web properties.
		 * @since    4.0.2
		 */
		public function get_analytics_web_properties(){
			$nonce = (isset($_POST['conversios_onboarding_nonce']))?sanitize_text_field($_POST['conversios_onboarding_nonce']):"";
			if($this->admin_safe_ajax_call($nonce, 'conversios_onboarding_nonce')){	
			  $data = isset($_POST['tvc_data'])?sanitize_text_field($_POST['tvc_data']):"";
        $tvc_data = json_decode(str_replace("&quot;", "\"", $data));
				$api_obj = new Conversios_Onboarding_ApiCall(sanitize_text_field($tvc_data->access_token), sanitize_text_field($tvc_data->refresh_token));
			  echo json_encode($api_obj->getAnalyticsWebProperties($_POST));
			  wp_die();
			}else{
				echo esc_html__("Admin security nonce is not verified.","conversios");
			}
		}

    /**
     * Ajax code for save analytics data.
     * @since    4.0.2
     */
    public function save_analytics_data(){
      $nonce = (isset($_POST['conversios_onboarding_nonce']))?sanitize_text_field($_POST['conversios_onboarding_nonce']):"";
      if($this->admin_safe_ajax_call($nonce, 'conversios_onboarding_nonce')){ 
        $data = isset($_POST['tvc_data'])?sanitize_text_field($_POST['tvc_data']):"";
        $tvc_data = json_decode(str_replace("&quot;", "\"", $data));
        $api_obj = new Conversios_Onboarding_ApiCall(sanitize_text_field($tvc_data->access_token), sanitize_text_field($tvc_data->refresh_token));
        /*sendingblue*/
        $data = array();
        $data["email"] = $tvc_data->g_mail;
        $data["attributes"]["PRODUCT"] = "Woocommerce Free Plugin";
        $data["attributes"]["SET_GA"] = true;
        $data["listIds"]=[40,41];
        $data["updateEnabled"]=true;
        $this->add_sendinblue_contant($data, $api_obj);
        /*end sendingblue*/        
        echo json_encode($api_obj->saveAnalyticsData($_POST));
        wp_die();
      }else{
        echo esc_html__("Admin security nonce is not verified.","conversios");
      }
    }

    /**
     * Ajax code for list googl ads account.
     * @since    4.0.2
     */
    public function list_googl_ads_account(){
      $nonce = (isset($_POST['conversios_onboarding_nonce']))?sanitize_text_field($_POST['conversios_onboarding_nonce']):"";
      if($this->admin_safe_ajax_call($nonce, 'conversios_onboarding_nonce')){ 
        $data = isset($_POST['tvc_data'])?sanitize_text_field($_POST['tvc_data']):"";
        $tvc_data = json_decode(str_replace("&quot;", "\"", $data));        
        $api_obj = new Conversios_Onboarding_ApiCall(sanitize_text_field($tvc_data->access_token), sanitize_text_field($tvc_data->refresh_token));
        echo json_encode($api_obj->getGoogleAdsAccountList($_POST));
        wp_die();
      }else{
        echo esc_html__("Admin security nonce is not verified.","conversios");
      }
    }
    /**
     * Ajax code for create google ads account.
     * @since    4.0.2
     */
    public function create_google_ads_account(){
      $nonce = (isset($_POST['conversios_onboarding_nonce']))?sanitize_text_field($_POST['conversios_onboarding_nonce']):"";
      if($this->admin_safe_ajax_call($nonce, 'conversios_onboarding_nonce')){ 
        $data = isset($_POST['tvc_data'])?sanitize_text_field($_POST['tvc_data']):"";
        $tvc_data = json_decode(str_replace("&quot;", "\"", $data));
        $api_obj = new Conversios_Onboarding_ApiCall(sanitize_text_field($tvc_data->access_token), sanitize_text_field($tvc_data->refresh_token));
        echo json_encode($api_obj->createGoogleAdsAccount($_POST));
        wp_die();
      }else{
        echo esc_html__("Admin security nonce is not verified.","conversios");
      }
    }

    /**
     * Ajax code for save google ads data.
     * @since    4.0.2
     */
    public function save_google_ads_data(){
      $nonce = (isset($_POST['conversios_onboarding_nonce']))?sanitize_text_field($_POST['conversios_onboarding_nonce']):"";
      if($this->admin_safe_ajax_call($nonce, 'conversios_onboarding_nonce')){ 
        $data = isset($_POST['tvc_data'])?sanitize_text_field($_POST['tvc_data']):"";
        $tvc_data = json_decode(str_replace("&quot;", "\"", $data));
        $api_obj = new Conversios_Onboarding_ApiCall(sanitize_text_field($tvc_data->access_token), sanitize_text_field($tvc_data->refresh_token));
        /*sendingblue*/
        $data = array();
        $data["email"] = sanitize_email($tvc_data->g_mail);
        $data["attributes"]["PRODUCT"] = sanitize_text_field("Woocommerce Free Plugin");
        $data["attributes"]["SET_ADS"] = true;
        $data["listIds"]=[40,41];
        $data["updateEnabled"]=true;
        $this->add_sendinblue_contant($data, $api_obj);
        /*end sendingblue*/
        echo json_encode($api_obj->saveGoogleAdsData($_POST));
        wp_die();
      }else{
        echo esc_html__("Admin security nonce is not verified.","conversios");
      }
    }

    /**
     * Ajax code for link analytic to ads account.
     * @since    4.0.2
     */
    public function link_analytic_to_ads_account(){
      $nonce = (isset($_POST['conversios_onboarding_nonce']))?sanitize_text_field($_POST['conversios_onboarding_nonce']):"";
      if($this->admin_safe_ajax_call($nonce, 'conversios_onboarding_nonce')){ 
        $data = isset($_POST['tvc_data'])?sanitize_text_field($_POST['tvc_data']):"";
        $tvc_data = json_decode(str_replace("&quot;", "\"", $data));
        $api_obj = new Conversios_Onboarding_ApiCall(sanitize_text_field($tvc_data->access_token), sanitize_text_field($tvc_data->refresh_token));
        echo json_encode($api_obj->linkAnalyticToAdsAccount($_POST));
        wp_die();
      }else{
        echo esc_html__("Admin security nonce is not verified.","conversios");
      }
    }

    /**
     * Ajax code for list google merchant account.
     * @since    4.0.2
     */
    public function list_google_merchant_account(){
      $nonce = (isset($_POST['conversios_onboarding_nonce']))?sanitize_text_field($_POST['conversios_onboarding_nonce']):"";
      if($this->admin_safe_ajax_call($nonce, 'conversios_onboarding_nonce')){ 
        $data = isset($_POST['tvc_data'])?sanitize_text_field($_POST['tvc_data']):"";
        $tvc_data = json_decode(str_replace("&quot;", "\"", $data));
        $api_obj = new Conversios_Onboarding_ApiCall(sanitize_text_field($tvc_data->access_token), sanitize_text_field($tvc_data->refresh_token));
        echo json_encode($api_obj->listMerchantCenterAccount($_POST));
        wp_die();
      }else{
        echo esc_html__("Admin security nonce is not verified.","conversios");
      }
    }
    /**
     * Ajax code for link analytic to ads account.
     * @since    4.0.2
     */
    public function create_google_merchant_center_account(){
      $nonce = (isset($_POST['conversios_onboarding_nonce']))?sanitize_text_field($_POST['conversios_onboarding_nonce']):"";
      if($this->admin_safe_ajax_call($nonce, 'conversios_onboarding_nonce')){ 
        $data = isset($_POST['tvc_data'])?sanitize_text_field($_POST['tvc_data']):"";
        $tvc_data = json_decode(str_replace("&quot;", "\"", $data));
        $api_obj = new Conversios_Onboarding_ApiCall(sanitize_text_field($tvc_data->access_token), sanitize_text_field($tvc_data->refresh_token));
        echo json_encode($api_obj->createMerchantAccount($_POST));
        wp_die();
      }else{
        echo esc_html__("Admin security nonce is not verified.","conversios");
      }
    }

    /**
     * Ajax code for save merchant data.
     * @since    4.0.2
     */
    public function save_merchant_data(){
      $nonce = (isset($_POST['conversios_onboarding_nonce']))?sanitize_text_field($_POST['conversios_onboarding_nonce']):"";
      if($this->admin_safe_ajax_call($nonce, 'conversios_onboarding_nonce')){ 
        $data = isset($_POST['tvc_data'])?sanitize_text_field($_POST['tvc_data']):"";
        $tvc_data = json_decode(str_replace("&quot;", "\"", $data));
        $api_obj = new Conversios_Onboarding_ApiCall(sanitize_text_field($tvc_data->access_token), sanitize_text_field($tvc_data->refresh_token));
        /*sendingblue*/
        $data = array();
        $data["email"] = sanitize_email($tvc_data->g_mail);
        $data["attributes"]["PRODUCT"] = sanitize_text_field("Woocommerce Free Plugin");
        $data["attributes"]["SET_GMC"] = true;
        $data["listIds"]=[40,41];
        $data["updateEnabled"]=true;
        $this->add_sendinblue_contant($data, $api_obj);
        /*end sendingblue*/
        echo json_encode($api_obj->saveMechantData($_POST));
        wp_die();
      }else{
        echo esc_html__("Admin security nonce is not verified.","conversios");
      }
    }
    /**
     * Ajax code for link analytic to ads account.
     * @since    4.0.2
     */
    public function get_conversion_list(){
      $nonce = (isset($_POST['conversios_onboarding_nonce']))?sanitize_text_field($_POST['conversios_onboarding_nonce']):"";
      if($this->admin_safe_ajax_call($nonce, 'conversios_onboarding_nonce')){ 
        $data = isset($_POST['tvc_data'])?sanitize_text_field($_POST['tvc_data']):"";
        $tvc_data = json_decode(str_replace("&quot;", "\"", $data));
        $api_obj = new Conversios_Onboarding_ApiCall(sanitize_text_field($tvc_data->access_token), sanitize_text_field($tvc_data->refresh_token));
        unset($_POST['tvc_data']);
        unset($_POST['conversios_onboarding_nonce']);
        echo json_encode($api_obj->getConversionList($_POST));
        wp_die();
      }else{
        echo esc_html__("Admin security nonce is not verified.","conversios");
      }
    }
    
    /**
     * Ajax code for link google ads to merchant center.
     * @since    4.0.2
     */
    public function link_google_ads_to_merchant_center(){
      $nonce = (isset($_POST['conversios_onboarding_nonce']))?sanitize_text_field($_POST['conversios_onboarding_nonce']):"";
      if($this->admin_safe_ajax_call($nonce, 'conversios_onboarding_nonce')){ 
        $data = isset($_POST['tvc_data'])?sanitize_text_field($_POST['tvc_data']):"";
        $tvc_data = json_decode(str_replace("&quot;", "\"", $data));
        $api_obj = new Conversios_Onboarding_ApiCall(sanitize_text_field($tvc_data->access_token), sanitize_text_field($tvc_data->refresh_token));
        echo json_encode($api_obj->linkGoogleAdsToMerchantCenter($_POST));
        wp_die();
      }else{
        echo esc_html__("Admin security nonce is not verified.","conversios");
      }
    }
    /**
     * Ajax code for link google ads to merchant center.
     * @since    4.0.2
     */
    public function get_subscription_details(){
      $nonce = (isset($_POST['conversios_onboarding_nonce']))?sanitize_text_field($_POST['conversios_onboarding_nonce']):"";
      if($this->admin_safe_ajax_call($nonce, 'conversios_onboarding_nonce')){ 
        $data = isset($_POST['tvc_data'])?sanitize_text_field($_POST['tvc_data']):"";
        $tvc_data = json_decode(str_replace("&quot;", "\"", $data));
        $api_obj = new Conversios_Onboarding_ApiCall(sanitize_text_field($tvc_data->access_token), sanitize_text_field($tvc_data->refresh_token));
        echo json_encode($api_obj->getSubscriptionDetails($tvc_data, sanitize_text_field($_POST['subscription_id']) ));
        wp_die();
      }else{
        echo esc_html__("Admin security nonce is not verified.","conversios");
      }
    }
    
    /**
     * Ajax code for update setup time to subscription.
     * @since    4.0.2
     */
    public function update_setup_time_to_subscription(){
      $nonce = (isset($_POST['conversios_onboarding_nonce']))?sanitize_text_field($_POST['conversios_onboarding_nonce']):"";
      if($this->admin_safe_ajax_call($nonce, 'conversios_onboarding_nonce')){ 
        $data = isset($_POST['tvc_data'])?sanitize_text_field($_POST['tvc_data']):"";
        $tvc_data = json_decode(str_replace("&quot;", "\"", $data));
        $api_obj = new Conversios_Onboarding_ApiCall(sanitize_text_field($tvc_data->access_token), sanitize_text_field($tvc_data->refresh_token));
        $return_url = $this->save_wp_setting_from_subscription_api($api_obj, $tvc_data, sanitize_text_field($_POST['subscription_id']) );
        $return_rs = $api_obj->updateSetupTimeToSubscription($_POST);
        $return_rs->return_url = $return_url;
        echo json_encode($return_rs);
        wp_die();
      }else{
        echo esc_html__("Admin security nonce is not verified.","conversios");
      }
    }

    /**
     * save wp setting from subscription api
     * @since    4.0.2
     */
    public function save_wp_setting_from_subscription_api($api_obj, $tvc_data, $subscription_id){ 
        
      $TVC_Admin_Helper = new TVC_Admin_Helper(); 
      $google_detail = $api_obj->getSubscriptionDetails($tvc_data, $subscription_id);
      /**
       * active licence key while come from server page
       */
      $ee_additional_data = $TVC_Admin_Helper->get_ee_additional_data();
      if(isset($ee_additional_data['temp_active_licence_key']) && $ee_additional_data['temp_active_licence_key'] != ""){
        $licence_key = $ee_additional_data['temp_active_licence_key'];
        $TVC_Admin_Helper->active_licence($licence_key, sanitize_text_field($_GET['subscription_id']));
        unset($ee_additional_data['temp_active_licence_key']);
        $TVC_Admin_Helper->set_ee_additional_data($ee_additional_data);
      }
      if(property_exists($google_detail,"error") && $google_detail->error == false){
        /**
         * for save conversion send to in WP DB
         */      
        $googleDetail = $google_detail->data;
        if($googleDetail->plan_id != 1 && sanitize_text_field($googleDetail->google_ads_conversion_tracking) == 1){
          $TVC_Admin_Helper->update_conversion_send_to();
        }
        /**
         * for site verifecation
         */
        if(isset($googleDetail->google_merchant_center_id) && sanitize_text_field($googleDetail->google_merchant_center_id)){
          $this->site_verification_and_domain_claim($googleDetail);
        }

        $settings['subscription_id'] = sanitize_text_field($googleDetail->id);
        $settings['ga_eeT'] = (isset($googleDetail->enhanced_e_commerce_tracking) && sanitize_text_field($googleDetail->enhanced_e_commerce_tracking) == "1") ? "on" : "";
        
        $settings['ga_ST'] = (isset($googleDetail->add_gtag_snippet) && sanitize_text_field($googleDetail->add_gtag_snippet) == "1") ? "on" : "";           
        $settings['gm_id'] = sanitize_text_field($googleDetail->measurement_id);
        $settings['ga_id'] = sanitize_text_field($googleDetail->property_id);
        $settings['google_ads_id'] = sanitize_text_field($googleDetail->google_ads_id);
        $settings['google_merchant_id'] = sanitize_text_field($googleDetail->google_merchant_center_id);
        $settings['tracking_option'] = sanitize_text_field($googleDetail->tracking_option);
        $settings['ga_gUser'] = 'on';
        $settings['ga_Impr'] = 6;
        $settings['ga_IPA'] = 'on';
        $settings['ga_OPTOUT'] = 'on';
        $settings['ga_PrivacyPolicy'] = 'on';
        $settings['google-analytic'] = '';
        //update option in wordpress local database
        update_option('google_ads_conversion_tracking', $googleDetail->google_ads_conversion_tracking);
        update_option('ads_tracking_id', $googleDetail->google_ads_id);
        update_option('ads_ert', $googleDetail->remarketing_tags);
        update_option('ads_edrt', $googleDetail->dynamic_remarketing_tags);
        
        $TVC_Admin_Helper->save_ee_options_settings($settings);
        /*
         * function call for save API data in WP DB
         */
        $TVC_Admin_Helper->set_update_api_to_db($googleDetail);  
               
        /**
         * function call for save remarketing snippets in WP DB
         */
        $TVC_Admin_Helper->update_remarketing_snippets();
        /**
         * save gmail and view ID in WP DB
         */
        if(property_exists($tvc_data,"g_mail") && sanitize_email($tvc_data->g_mail)){
          update_option('ee_customer_gmail', $tvc_data->g_mail);     
        }
        $return_url = "admin.php?page=conversios-google-shopping-feed&tab=gaa_config_page";
        if(isset($googleDetail->google_merchant_center_id) || isset($googleDetail->google_ads_id) ){
          if( sanitize_text_field($googleDetail->google_merchant_center_id) != "" && sanitize_text_field($googleDetail->google_ads_id) != ""){      
            $return_url = esc_url_raw("admin.php?page=conversios-google-shopping-feed&tab=sync_product_page&welcome_msg=true");            
          }else{
            $return_url = esc_url_raw("admin.php?page=conversios-google-shopping-feed&tab=gaa_config_page&welcome_msg=true");
          }          
        }
        return $return_url;
      }
    }
    /**
     * site verification and_domain claim code
     * @since    4.0.2
     */
    public function site_verification_and_domain_claim($googleDetail){
      $TVC_Admin_Helper = new TVC_Admin_Helper();
      $ee_additional_data = $TVC_Admin_Helper->get_ee_additional_data();
      $customApiObj = new CustomApi();
      $postData = [
          'merchant_id' => sanitize_text_field($googleDetail->merchant_id),          
          'website_url' => esc_url_raw(get_site_url()),
          'subscription_id' => sanitize_text_field($googleDetail->id),
          'account_id' => sanitize_text_field($googleDetail->google_merchant_center_id)
      ];
      //is site verified
      if ($googleDetail->is_site_verified == '0') {
        $postData['method']="file";
        $siteVerificationToken = $customApiObj->siteVerificationToken($postData);
        if (isset($siteVerificationToken->error) && !empty($siteVerificationToken->errors)) {
            goto call_method_tag;
        } else {
          $myFile =ABSPATH.$siteVerificationToken->data->token; 
          if (!file_exists($myFile)) {
              $fh = fopen($myFile, 'w+');
              chmod($myFile,0777);
              $stringData = "google-site-verification: ".$siteVerificationToken->data->token;
              fwrite($fh, $stringData);
              fclose($fh);
          }
          $postData['method']="file";
          $siteVerification = $customApiObj->siteVerification($postData);
          if (isset($siteVerification->error) && !empty($siteVerification->errors)) {
            call_method_tag:
            //methd using tag
            $postData['method']="meta";
            $siteVerificationToken_tag = $customApiObj->siteVerificationToken($postData);
            if(isset($siteVerificationToken_tag->data->token) && $siteVerificationToken_tag->data->token){
              $ee_additional_data["add_site_varification_tag"]=1;
              $ee_additional_data["site_varification_tag_val"]=base64_encode($siteVerificationToken_tag->data->token);
              $TVC_Admin_Helper->set_ee_additional_data($ee_additional_data);
              sleep(1);
              $siteVerification_tag = $customApiObj->siteVerification($postData);
              if(isset($siteVerification_tag->error) && !empty($siteVerification_tag->errors)){
              }else{
                  $googleDetail->is_site_verified = '1';
              }
            }
          } else {
              $googleDetail->is_site_verified = '1';
          }
        }
      }
      //is domain claim
      if ($googleDetail->is_domain_claim == '0') {
          $claimWebsite = $customApiObj->claimWebsite($postData);
          if (isset($claimWebsite->error) && !empty($claimWebsite->errors)) {    
          } else {
              $googleDetail->is_domain_claim = '1';
          }
      }

      /**
       * function call for save API data in WP DB
       */
      $TVC_Admin_Helper->set_update_api_to_db($googleDetail);
    }
    /**
     * update contact details on sendinblue.
     * @since    4.0.2
     */
    function add_sendinblue_contant($data, $api_obj){
      $api_obj->TVC_CALL_API_sendinblue("POST", "https://api.sendinblue.com/v3/contacts", $data);    
    }
		
	}
endif; // class_exists
new Conversios_Onboarding_Helper();

if(!class_exists('Conversios_Onboarding_ApiCall') ){
	class Conversios_Onboarding_ApiCall {
		protected $apiDomain;
		protected $token;
    protected $merchantId;
    protected $access_token;
    protected $refresh_token;
		public function __construct($access_token, $refresh_token) {
      $merchantInfo = json_decode(file_get_contents(ENHANCAD_PLUGIN_DIR.'includes/setup/json/merchant-info.json'), true);
      $this->refresh_token = $refresh_token;
      $this->access_token = base64_encode( $this->generateAccessToken( base64_decode($access_token), base64_decode($this->refresh_token) ) );
      $this->apiDomain = TVC_API_CALL_URL;
      $this->token = 'MTIzNA==';
      $this->merchantId = sanitize_text_field($merchantInfo['merchantId']);
		}
    public function tc_wp_remot_call_post($url, $args){
      try {
        if(!empty($args)){    
          // Send remote request
          $args['timeout']= "1000";
          $request = wp_remote_post($url, $args);

          // Retrieve information
          $response_code = wp_remote_retrieve_response_code($request);

          $response_message = wp_remote_retrieve_response_message($request);
          $response_body = json_decode(wp_remote_retrieve_body($request));

          if ((isset($response_body->error) && $response_body->error == '')) {
            return new WP_REST_Response($response_body->data);
          } else {
              return new WP_Error($response_code, $response_message, $response_body);
          }
        }
      } catch (Exception $e) {
          return $e->getMessage();
      }
    }
    public function TVC_CALL_API_sendinblue($method, $url, $data, $headers = false){
      try {
        $args = array(
          'headers' => array(
              'api-key' => sanitize_text_field("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"),
              'Content-Type' => 'application/json'
          ),
          'method' => $method,
          'body' => $data
        );
        // Send remote request
        $request = wp_remote_post($url, $args);
        // Retrieve information
        $response_code = wp_remote_retrieve_response_code($request);
        $response_message = wp_remote_retrieve_response_message($request);
        $response_body = json_decode(wp_remote_retrieve_body($request));

        if ((isset($response_body->error) && $response_body->error == '')) {
          return new WP_REST_Response(
            array('status' => $response_code, 'message' => $response_message, 'data' => $response_body->data)
          );
        } else {
          return new WP_Error($response_code, $response_message, $response_body);
        }
      } catch (Exception $e) {
          return $e->getMessage();
      }
     
    }

    public function getSubscriptionDetails($tvc_data, $subscription_id){
      try{
        $tvc_data = (object)$tvc_data;
        $access_token = sanitize_text_field(base64_decode($this->access_token));
        $url = $this->apiDomain . '/customer-subscriptions/subscription-detail';
        $header = array("Authorization: Bearer MTIzNA==", "Content-Type" => "application/json", "AccessToken: $access_token");
        $data = [
          'subscription_id' => sanitize_text_field($subscription_id),//$this->subscription_id,
          'domain' => sanitize_text_field($tvc_data->user_domain)
        ];
        $args = array(
          'headers' =>$header,
          'method' => 'POST',
          'body' => wp_json_encode($data)
        );
        $result = $this->tc_wp_remot_call_post(esc_url_raw($url), $args);

        $return = new \stdClass();
        if($result->status == 200){
          $return->status = $result->status;
          $return->data = $result->data;
          $return->error = false;
          return $return;
        }else{
          $return->error = true;
          $return->data = $result->data;
          $return->status = $result->status;
          return $return;
        }
      }catch(Exception $e){
        return $e->getMessage();
      }
    }

		public function getAnalyticsWebProperties($postData) {
      try {
        $url = $this->apiDomain . '/google-analytics/account-list';
        
        $access_token = sanitize_text_field(base64_decode($this->access_token));
        $max_results = 10; 
        $page = (isset($postData['page']) && sanitize_text_field($postData['page']) >1)?sanitize_text_field($postData['page']):"1";
        if($page > 1){
          //set index
          $page = (($page-1) * $max_results)+1;
        }       
        $data = [
          'type' => sanitize_text_field($postData['type']),
          'page'=>sanitize_text_field($page),
          'max_results'=>sanitize_text_field($max_results)
        ];
        $args = array(
          'timeout' => 10000,
          'headers' => array(
            'Authorization' => "Bearer MTIzNA==",
            'Content-Type' => 'application/json',
            'AccessToken' => $access_token
          ),
          'body' => wp_json_encode($data)
        );
        $request = wp_remote_post(esc_url_raw($url), $args);
        
        // Retrieve information
        $response_code = wp_remote_retrieve_response_code($request);
        $response_message = wp_remote_retrieve_response_message($request);
        $response = json_decode(wp_remote_retrieve_body($request));
        $return = new \stdClass();
        if (isset($response->error) && $response->error == '') {
          $return->status = $response_code;
          $return->data = $response->data;
          $return->error = false;
          return $return;
        }else{
          $return->error = true;
          $return->data = $response->data;
          $return->status = $response_code;
          return $return;
        }
      } catch (Exception $e) {
        return $e->getMessage();
      }
    }

    public function getGoogleAdsAccountList($postData) {
      try {
        if($this->refresh_token != ""){
          $url = $this->apiDomain . '/adwords/list';
          $refresh_token = sanitize_text_field(base64_decode($this->refresh_token));
          $args = array(
            'timeout' => 10000,
            'headers' => array(
              'Authorization' => "Bearer MTIzNA==",
              'Content-Type' => 'application/json',
              'RefreshToken' => $refresh_token
            ),
            'body' => ""
          );
          $request = wp_remote_post(esc_url_raw($url), $args);
          
          // Retrieve information
          $response_code = wp_remote_retrieve_response_code($request);
          $response_message = wp_remote_retrieve_response_message($request);
          $response = json_decode(wp_remote_retrieve_body($request));
          $return = new \stdClass();
          if (isset($response->error) && $response->error == '') {
            $return->status = $response_code;
            $return->data = $response->data;
            $return->error = false;
            return $return;
          }else{
            $return->error = true;
            $return->data = $response->data;
            $return->status = $response_code;
            return $return;
          }       
        }else{
          return json_decode(array("error"=>true));
        }
      } catch (Exception $e) {
          return $e->getMessage();
      }
    }

    public function listMerchantCenterAccount() {
      try {
        $url = $this->apiDomain . '/gmc/user-merchant-center/list';
        $header = array("Authorization: Bearer MTIzNA==", "Content-Type" => "application/json");
        $data = [
          'access_token' => sanitize_text_field(base64_decode($this->access_token)),
        ];
        $args = array(
          'timeout' => 10000,
          'headers' =>$header,
          'method' => 'POST',
          'body' => wp_json_encode($data)
        );
        $result = $this->tc_wp_remot_call_post(esc_url_raw($url), $args);
        $return = new \stdClass();
        if($result->status == 200){
          $return->status = $result->status;
          $return->data = $result->data;
          $return->error = false;
          return $return;
        }else{
          $return->error = true;
          $return->data = $result->data;
          $return->status = $result->status;
          return $return;
        }
      } catch (Exception $e) {
        return $e->getMessage();
      }
    }

    public function createGoogleAdsAccount($postData) {
      try {
        //$tvc_data = (object)$postData['tvc_data'];
        $data = isset($_POST['tvc_data'])?sanitize_text_field($_POST['tvc_data']):"";
        $tvc_data = json_decode(str_replace("&quot;", "\"", $data));
        $url = $this->apiDomain . '/adwords/create-ads-account';
        $header = array("Authorization: Bearer MTIzNA==", "Content-Type" => "application/json");
        $data = [
          'subscription_id' => sanitize_text_field($tvc_data->subscription_id),
          'email' => sanitize_email($tvc_data->g_mail),
          'currency' => sanitize_text_field($tvc_data->currency_code),
          'time_zone' => sanitize_text_field($tvc_data->timezone_string), //'Asia/Kolkata',
          'domain' => sanitize_text_field($tvc_data->user_domain)
        ];
        $args = array(
          'headers' =>$header,
          'method' => 'POST',
          'body' => wp_json_encode($data)
        );
        $result = $this->tc_wp_remot_call_post(esc_url_raw($url), $args);
        $return = new \stdClass();
        if($result->status == 200){
          $return->status = $result->status;
          $return->data = $result->data;
          $return->error = false;
          return $return;
        }else{
          $return->error = true;
          $return->error = $result->errors;
          //$return->data = $result->data;
          $return->status = $result->status;
          return $return;
        }
      } catch (Exception $e) {
          return $e->getMessage();
      }
    }
    public function createMerchantAccount($postData) {
      try {
        $url = $this->apiDomain . '/gmc/create';
        $header = array(
            "Authorization: Bearer MTIzNA==",
            "Content-Type" => "application/json"
        );
        $data = [
          'merchant_id' => sanitize_text_field($this->merchantId), //'256922349',
          'name' => sanitize_text_field($postData['store_name']),
          'website_url' => esc_url_raw(sanitize_text_field($postData['website_url'])),
          'customer_id' => sanitize_text_field($postData['customer_id']),
          'adult_content' => isset($postData['adult_content']) && sanitize_text_field($postData['adult_content']) == 'true' ? true : false,
          'country' => sanitize_text_field($postData['country']),
          'users' => [
            [
              "email_address" => sanitize_email($postData['email_address']), //"sarjit@pivotdrive.ca"
              "admin" => true
            ]
          ],
          'business_information' => [
            'address' => [
                'country' => sanitize_text_field($postData['country'])
            ]
          ]
        ];
        $args = array(
          'timeout' => 10000,
          'headers' =>$header,
          'method' => 'POST',
          'body' => wp_json_encode($data)
        );
        $args['timeout']= "1000";
        $request = wp_remote_post(esc_url_raw($url), $args);
        
        // Retrieve information
        $response_code = wp_remote_retrieve_response_code($request);
        $response_message = wp_remote_retrieve_response_message($request);
        $response_body = json_decode(wp_remote_retrieve_body($request));
        if ((isset($response_body->error) && $response_body->error == '') || (!isset($response_body->error)) ) {
            return $response_body;
        } else {
          $return = new \stdClass();
          $return->error = true;
          //$return->data = $result->data;
          $return->status = $response_code;
          return $return;
          //return new WP_Error($response_code, $response_message, $response_body);
        }
      } catch (Exception $e) {
        return $e->getMessage();
      }
    }
    
    public function saveAnalyticsData($postData = array()) {
      try {
        $url = $this->apiDomain . '/customer-subscriptions/update-detail';
        $header = array("Authorization: Bearer MTIzNA==", "Content-Type" => "application/json");
        $data = array(
          'subscription_id' => sanitize_text_field((isset($postData['subscription_id']))?$postData['subscription_id'] : ''),
          'tracking_option' => sanitize_text_field((isset($postData['tracking_option']))?$postData['tracking_option'] : ''),
          'measurement_id' => sanitize_text_field((isset($postData['web_measurement_id']))?$postData['web_measurement_id'] : ''),
          'ga4_analytic_account_id' => sanitize_text_field((isset($postData['ga4_account_id']))?$postData['ga4_account_id'] : ''),
          'property_id' => sanitize_text_field((isset($postData['web_property_id'])) ? $postData['web_property_id'] : ''),
          'ua_analytic_account_id' => sanitize_text_field((isset($postData['ua_account_id'])) ? $postData['ua_account_id'] : ''),
          'enhanced_e_commerce_tracking' => sanitize_text_field((isset($postData['enhanced_e_commerce_tracking']) && $postData['enhanced_e_commerce_tracking'] == 'true') ? 1 : 0),
          'user_time_tracking' => sanitize_text_field((isset($postData['user_time_tracking']) && $postData['user_time_tracking']=='true')?1:0),
          'add_gtag_snippet' => sanitize_text_field((isset($postData['add_gtag_snippet']) && $postData['add_gtag_snippet'] == 'true')? 1:0),
          'client_id_tracking' => sanitize_text_field((isset($postData['client_id_tracking']) && $postData['client_id_tracking']=='true')?1:0),
          'exception_tracking' => sanitize_text_field((isset($postData['exception_tracking']) && $postData['exception_tracking']=='true')?1:0),
          'enhanced_link_attribution_tracking' => sanitize_text_field((isset($postData['enhanced_link_attribution_tracking']) && $postData['enhanced_link_attribution_tracking'] == 'true')? 1 : 0)
        );
        $args = array(
          'headers' =>$header,
          'method' => 'POST',
          'body' => wp_json_encode($data)
        );
        $result = $this->tc_wp_remot_call_post(esc_url_raw($url), $args);
        $return = new \stdClass();
        if($result->status == 200){
          $return->status = $result->status;
          $return->data = $result->data;
          $return->error = false;
          return $return;
        }else{
          $return->error = true;
          $return->data = $result->data;
          $return->status = $result->status;
          return $return;
        }
      } catch (Exception $e) {
        return $e->getMessage();
      }
    }

    public function saveGoogleAdsData($postData = array()){
      try {
        $url = $this->apiDomain . '/customer-subscriptions/update-detail';
        $header = array("Authorization: Bearer MTIzNA==", "Content-Type" => "application/json");
        $data = [
            'subscription_id' => sanitize_text_field((isset($postData['subscription_id']))?$postData['subscription_id'] : ''),
            'google_ads_id' => sanitize_text_field((isset($postData['google_ads_id']))? $postData['google_ads_id'] : ''),
            'remarketing_tags' => sanitize_text_field((isset($postData['remarketing_tags']) && $postData['remarketing_tags'] == 'true') ? 1 : 0),
            'dynamic_remarketing_tags' => sanitize_text_field((isset($postData['dynamic_remarketing_tags']) && $postData['dynamic_remarketing_tags'] == 'true') ? 1 : 0),
            'google_ads_conversion_tracking' => sanitize_text_field((isset($postData['google_ads_conversion_tracking']) && $postData['google_ads_conversion_tracking'] == 'true') ? 1 : 0),
            'link_google_analytics_with_google_ads' => sanitize_text_field((isset($postData['link_google_analytics_with_google_ads']) && $postData['link_google_analytics_with_google_ads'] == 'true') ? 1 : 0)
        ];
        $args = array(
          'timeout' => 10000,
          'headers' =>$header,
          'method' => 'POST',
          'body' => wp_json_encode($data)
        );
        $result = $this->tc_wp_remot_call_post(esc_url_raw($url), $args);
        $return = new \stdClass();
        if($result->status == 200){
          $return->status = $result->status;
          $return->data = $result->data;
          $return->error = false;
          return $return;
        }else{
          $return->error = true;
          $return->data = $result->data;
          $return->status = $result->status;
          return $return;
        }
      } catch (Exception $e) {
        return $e->getMessage();
      }
    }

    public function saveMechantData($postData = array()) {
      try {
        $url = $this->apiDomain . '/customer-subscriptions/update-detail';
        $header = array("Authorization: Bearer MTIzNA==", "Content-Type" => "application/json");
        $data = [
          'merchant_id' => sanitize_text_field(($postData['merchant_id'] == 'NewMerchant') ? $this->merchantId: $postData['merchant_id']),
          'subscription_id' => sanitize_text_field((isset($postData['subscription_id']))?$postData['subscription_id'] : ''),
          'google_merchant_center_id' => sanitize_text_field((isset($postData['google_merchant_center']))? $postData['google_merchant_center'] : ''),
          'website_url' => sanitize_text_field($postData['website_url']),
          'customer_id' => sanitize_text_field($postData['customer_id'])
        ];
        $args = array(
          'headers' =>$header,
          'method' => 'POST',
          'body' => wp_json_encode($data)
        );
        $result = $this->tc_wp_remot_call_post(esc_url_raw($url), $args);
        $return = new \stdClass();
        if($result->status == 200){
          $return->status = $result->status;
          $return->data = $result->data;
          $return->error = false;
          return $return;
        }else{
          $return->error = true;
          $return->data = $result->data;
          $return->status = $result->status;
          return $return;
        }
      } catch (Exception $e) {
        return $e->getMessage();
      }
    }

    public function linkAnalyticToAdsAccount($postData) {
      try {
        $url = $this->apiDomain . '/google-analytics/link-ads-to-analytics';
        $access_token = sanitize_text_field(base64_decode($this->access_token));
        $refresh_token = sanitize_text_field(base64_decode($this->refresh_token));
        if ($postData['type'] == "UA") {
          $data = [
            'type' => sanitize_text_field($postData['type']),
            'ads_customer_id' => sanitize_text_field($postData['ads_customer_id']), 
            'analytics_id' => sanitize_text_field($postData['analytics_id']), 
            'web_property_id' => sanitize_text_field($postData['web_property_id']), 
            'profile_id' => sanitize_text_field($postData['profile_id']),
          ];
        } else {
          $data = [
            'type' => sanitize_text_field($postData['type']),
            'ads_customer_id' => sanitize_text_field($postData['ads_customer_id']), 
            'analytics_id' => '', 
            'web_property_id' => sanitize_text_field($postData['web_property_id']), 
            'profile_id' => '', 
            'web_property' => sanitize_text_field($postData['web_property']),
          ];
        }
        
        $args = array(
          'timeout' => 10000,
          'headers' => array(
              'Authorization' => "Bearer $this->token",
              'Content-Type' => 'application/json',
              'AccessToken' => $access_token,
              'RefreshToken' => $refresh_token
          ),
          'method' => 'POST',
          'body' => wp_json_encode($data)
        );
        $request = wp_remote_post(esc_url_raw($url), $args);

        // Retrieve information
        $response_code = wp_remote_retrieve_response_code($request);
        $response_message = wp_remote_retrieve_response_message($request);
        $result = json_decode(wp_remote_retrieve_body($request));
        $return = new \stdClass();
        if($response_code == 200 && isset($result->error) && $result->error == ''){
          $return->status = $response_code;
          $return->data = $result->data;
          $return->error = false;
          return $return;
        }else{
          $return->error = true;
          $return->errors = $result->errors;
          //$return->data = $result->data;
          $return->status = $response_code;
          return $return;
        }
      } catch (Exception $e) {
          return $e->getMessage();
      }
    }
    public function linkGoogleAdsToMerchantCenter($postData) {
      try {
        $url = $this->apiDomain . '/adwords/link-ads-to-merchant-center';
        $access_token = sanitize_text_field(base64_decode($this->access_token));
        $data = [
          'merchant_id' => sanitize_text_field(($postData['merchant_id']) == 'NewMerchant' ?  $this->merchantId: $postData['merchant_id']),
          'account_id' => sanitize_text_field($postData['account_id']),
          'adwords_id' => sanitize_text_field($postData['adwords_id'])
        ];
        $args = array(
          'timeout' => 10000,
            'headers' => array(
                'Authorization' => "Bearer $this->token",
                'Content-Type' => 'application/json',
                'AccessToken' => $access_token
            ),
            'method' => 'POST',
            'body' => wp_json_encode($data)
        );

        // Send remote request
        $request = wp_remote_post(esc_url_raw($url), $args);

        // Retrieve information
        $response_code = wp_remote_retrieve_response_code($request);
        $response_message = wp_remote_retrieve_response_message($request);
        $result = json_decode(wp_remote_retrieve_body($request));
        $return = new \stdClass();
        if($response_code == 200){
          $return->status = $response_code;
          $return->data = $result->data;
          $return->error = false;
          return $return;
        }else{
          $return->error = true;
          $return->errors = $result->errors;
          //$return->data = $result->data;
          $return->status = $response_code;
          return $return;
        }
        
      } catch (Exception $e) {
        return $e->getMessage();
      }
    }
    public function updateSetupTimeToSubscription($postData) {
      try {
        $url = $this->apiDomain . '/customer-subscriptions/update-setup-time';
        $data = [
          'subscription_id' => sanitize_text_field((isset($postData['subscription_id']))?$postData['subscription_id'] : ''),
          'setup_end_time' => date('Y-m-d H:i:s')
        ];
        $args = array(
            'timeout' => 10000,
            'headers' => array(
                'Authorization' => "Bearer $this->token",
                'Content-Type' => 'application/json'
            ),
            'method' => 'POST',
            'body' => wp_json_encode($data)
        );

        // Send remote request
        $request = wp_remote_post(esc_url_raw($url), $args);

        // Retrieve information
        $response_code = wp_remote_retrieve_response_code($request);
        $response_message = wp_remote_retrieve_response_message($request);
        $result = json_decode(wp_remote_retrieve_body($request));
        $return = new \stdClass();
        if($response_code == 200){
          $return->status = $response_code;
          $return->data = $result->data;
          $return->error = false;
          return $return;
        }else{
          $return->error = true;
          $return->errors = $result->errors;
          //$return->data = $result->data;
          $return->status = $response_code;
          return $return;
        }
        
      } catch (Exception $e) {
        return $e->getMessage();
      }
    }

    public function getConversionList($postData) {
      try {
        if(!empty($postData)){
          foreach ($postData as $key => $value) {
            $postData[$key] = sanitize_text_field($value); 
          }
        }
        $url = $this->apiDomain . '/google-ads/conversion-list';
        $header = array(
            "Authorization: Bearer MTIzNA==",
            "Content-Type" => "application/json"
        );
        $args = array(
          'timeout' => 10000,
          'headers' =>$header,
          'method' => 'POST',
          'body' => wp_json_encode($postData)
        );
        $request = wp_remote_post(esc_url_raw($url), $args);
        $response_code = wp_remote_retrieve_response_code($request);
        $response_message = wp_remote_retrieve_response_message($request);
        $response = json_decode(wp_remote_retrieve_body($request));

        $return = new \stdClass();
        if ((isset($response->error) && $response->error == '')) {
          $return->status = $response_code;
          $return->data =$response->data;
          $return->error = false;
          if(isset($response->data) && count($response->data) > 0){
            $return->message = esc_html__("Google Ads conversion tracking setting success.","conversios");
          }else{
             $response = $this->createConversion($data); 
            if(isset($response->error) && $response->error == false){         
              $return->error = false;
              $return->message = esc_html__("Google Ads conversion tracking setting success.","conversios"); 
            }else{
             $return->error = true;
             $errors = json_decode($response->errors[0]);
             $return->errors = $errors->message;
            }
          }  
          return $return;
        }else{
          $return->error = true;
          $return->errors = $response->errors[0];
          //$return->data = $result->data;
          $return->status = $response_code;
          return $return;
        }
      } catch (Exception $e) {
        return $e->getMessage();
      }
    }

    public function createConversion($postData) {
      try {
        $url = $this->apiDomain . '/google-ads/create-conversion';
        $header = array("Authorization: Bearer MTIzNA==", "Content-Type" => "application/json");
        $data = [
          'customer_id' => sanitize_text_field((isset($postData['customer_id']))?$postData['customer_id'] : ''),
          'name' => "Order Conversion"
        ];
        $args = array(
          'headers' =>$header,
          'method' => 'POST',
          'body' => wp_json_encode($data)
        );
        $result = $this->tc_wp_remot_call_post(esc_url_raw($url), $args);
        $return = new \stdClass();
        if($result->status == 200){
          $return->status = $result->status;
          $return->data = $result->data;
          $return->error = false;
          return $return;
        }else{
          $return->error = true;
          $return->data = $result->data;
          $return->status = $result->status;
          return $return;
        }
      } catch (Exception $e) {
        return $e->getMessage();
      }
    }
    public function generateAccessToken($access_token, $refresh_token) {
      $url = "https://www.googleapis.com/oauth2/v1/tokeninfo?=" . $access_token;
      $request =  wp_remote_get(esc_url_raw($url), array( "access_token" => $access_token, 'timeout' => 10000 ));
      $response_code = wp_remote_retrieve_response_code($request);

      $response_message = wp_remote_retrieve_response_message($request);
      $result = json_decode(wp_remote_retrieve_body($request));
      
      if (isset($result->error) && $result->error) {
          $credentials = json_decode(file_get_contents(ENHANCAD_PLUGIN_DIR . 'includes/setup/json/client-secrets.json'), true);
          $url = 'https://www.googleapis.com/oauth2/v4/token';
          $header = array("content-type: application/json");
          $clientId = $credentials['web']['client_id'];
          $clientSecret = $credentials['web']['client_secret'];
          
          $data = [
              "grant_type" => 'refresh_token',
              "client_id" => sanitize_text_field($clientId),
              'client_secret' => sanitize_text_field($clientSecret),
              'refresh_token' => sanitize_text_field($refresh_token),
          ];
          $args = array(
            'timeout' => 10000,
            'headers' =>$header,
            'method' => 'POST',
            'body' => $data
          );
          $request = wp_remote_post(esc_url_raw($url), $args);
          // Retrieve information
          $response_code = wp_remote_retrieve_response_code($request);
          $response_message = wp_remote_retrieve_response_message($request);
          $response = json_decode(wp_remote_retrieve_body($request));
          if(isset($response->access_token)){
              return $response->access_token; 
          }else{
              //return $access_token;
          }
      } else {
        return $access_token;
      }
    }//generateAccessToken

	}
}
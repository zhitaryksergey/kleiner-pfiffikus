<?php
class CustomApi{
    private $apiDomain;
    private $token;
    protected $access_token;
    protected $refresh_token;
    public function __construct() {
        $this->apiDomain = TVC_API_CALL_URL;
        $this->token = 'MTIzNA==';
    }
    public function get_tvc_access_token(){
      if(!empty($this->access_token)){
          return $this->access_token;
      }else{
        $TVC_Admin_Helper = new TVC_Admin_Helper();
        $google_detail = $TVC_Admin_Helper->get_ee_options_data(); 
        if((isset($google_detail['setting']->access_token))){         
          $this->access_token = sanitize_text_field(base64_decode($google_detail['setting']->access_token));
        }
        return $this->access_token;
      }
    }

    public function get_tvc_refresh_token(){
        if(!empty($this->refresh_token)){
            return $this->refresh_token;
        }else{
            $TVC_Admin_Helper = new TVC_Admin_Helper();
            $google_detail = $TVC_Admin_Helper->get_ee_options_data();  
            if(isset($google_detail['setting']->refresh_token)){
                $this->refresh_token = sanitize_text_field(base64_decode($google_detail['setting']->refresh_token));
            }
            return $this->refresh_token;
        }
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

    public function is_allow_call_api(){
        $ee_options_data = unserialize(get_option('ee_options'));
        if(isset($ee_options_data['subscription_id'])) {
            return true;
        } else {
            return false;
        }
    }

    public function getGoogleAnalyticDetail($subscription_id = null) {
        try {
          
            $url = $this->apiDomain . '/customer-subscriptions/subscription-detail';
            $header = array(
                "Authorization: Bearer ".$this->token,
                "Content-Type" => "application/json"
            );
            $ee_options_data = unserialize(get_option('ee_options'));
            if($subscription_id == null && isset($ee_options_data['subscription_id'])) {
                $subscription_id = sanitize_text_field($ee_options_data['subscription_id']);
            } 
            $data = [
                'subscription_id' => sanitize_text_field($subscription_id),
                'domain' => get_site_url()
            ];
            if($subscription_id == ""){
              $return = new \stdClass();
              $return->error = true;
              return $return;
            }
            $args = array(
              'headers' =>$header,
              'method' => 'POST',
              'body' => wp_json_encode($data)
            );
            $result = $this->tc_wp_remot_call_post(esc_url_raw($url), $args);
            //print_r($result);
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

    public function updateTrackingOption($postData) {
        try {
            $url = $this->apiDomain . '/customer-subscriptions/tracking-options';

            if(!empty($postData)){
              foreach ($postData as $key => $value) {
                $postData[$key] = sanitize_text_field($value); 
              }
            }
            $args = array(
              'timeout' => 10000,
                'headers' => array(
                    'Authorization' => "Bearer $this->token",
                    'Content-Type' => 'application/json'
                ),
                'method' => 'PATCH',
                'body' => wp_json_encode($postData)
            );

            // Send remote request
            $request = wp_remote_post(esc_url_raw($url), $args);

            // Retrieve information
            $response_code = wp_remote_retrieve_response_code($request);
            $response_message = wp_remote_retrieve_response_message($request);
            $response_body = json_decode(wp_remote_retrieve_body($request));

            if ((isset($response_body->error) && $response_body->error == '')) {

                return new WP_REST_Response(
                        array(
                    'status' => $response_code,
                    'message' => $response_message,
                    'data' => $response_body->data
                        )
                );
            } else {
                return new WP_Error($response_code, $response_message, $response_body);
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
    public function add_survey_of_deactivate_plugin($postData) {
      try {
        $url = $this->apiDomain . "/customersurvey";
        if(!empty($postData)){
          foreach ($postData as $key => $value) {
            $postData[$key] = sanitize_text_field($value); 
          }
        }
        $header = array(
            "Authorization: Bearer MTIzNA==",
            "Content-Type" => "application/json"
        );
        $args = array(
          'headers' =>$header,
          'method' => 'POST',
          'body' => wp_json_encode($postData)
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
    public function active_licence_Key($licence_key, $subscription_id) {
        try {
            $header = array(
                "Authorization: Bearer MTIzNA==",
                "Content-Type" => "application/json"
            );
            $url = $this->apiDomain . "/licence/activation";
            $data = [
                'key' => sanitize_text_field($licence_key),
                'domain' => get_site_url(),
                'subscription_id'=>sanitize_text_field($subscription_id)
            ];
            $args = array(
              'timeout' => 10000,
              'headers' =>$header,
              'method' => 'POST',
              'body' => wp_json_encode($data)
            );
            $request = wp_remote_post(esc_url_raw($url), $args);
            // Retrieve information
            $response_code = wp_remote_retrieve_response_code($request);
            $response_message = wp_remote_retrieve_response_message($request);
            $response = json_decode(wp_remote_retrieve_body($request));
            $return = new \stdClass();
            if ((isset($response->error) && $response->error == '')) {
              //$return->status = $result->status;
              $return->data = $response->data;
              $return->error = false;
              return $return;
            }else{
             if (isset($response->data)) {
                    $return->error = false;
                    $return->data = $response->data;
                    $return->message = $response->message;
                } else {
                    $return->error = true;
                    $return->data = [];
                    if(isset($response->errors->key[0])){
                        $return->message = $response->errors->key[0]; 
                    }else{
                        $return->message = esc_html__("Check your entered licese key.","conversios");
                    }  

                }
                return $return;
              return $return;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
    public function get_remarketing_snippets($customer_id) {
        try {
            $header = array(
                "Authorization: Bearer MTIzNA==",
                "Content-Type" => "application/json"
            );
            $url = $this->apiDomain . "/google-ads/remarketing-snippets";
            $data = [
                'customer_id' => sanitize_text_field($customer_id)
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
    public function get_conversion_list($customer_id, $merchant_id) {
        try {
            $header = array(
                "Authorization: Bearer MTIzNA==",
                "Content-Type" => "application/json"
            );
            $url = $this->apiDomain . "/google-ads/conversion-list";
            $data = [
                'merchant_id' => sanitize_text_field($merchant_id),
                'customer_id' => sanitize_text_field($customer_id)
            ];
            $args = array(
              'timeout' => 10000,
              'headers' =>$header,
              'method' => 'POST',
              'body' => wp_json_encode($data)
            );

           // $result = $this->tc_wp_remot_call_post(esc_url_raw($url), $args);
            $request = wp_remote_post(esc_url_raw($url), $args);
            $response_code = wp_remote_retrieve_response_code($request);
            $response_message = wp_remote_retrieve_response_message($request);
            $result = json_decode(wp_remote_retrieve_body($request));
            $return = new \stdClass();
            if ((isset($result->error) && $result->error == '')) {
              $return->status = $response_code;
              $return->data = $result->data;
              $return->error = false;
              return $return;
            }else{
              $return->error = true;
              $return->errors = $result->errors;
              //$return->error = $result->data;
              $return->status = $response_code;
              return $return;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
    /**
     * @since 4.1.4
     * Get view ID for GA3 reporting API
     */
    public function get_analytics_viewid_currency($postData) {
      try {
        if(!empty($postData)){
          foreach ($postData as $key => $value) {
            $postData[$key] = sanitize_text_field($value); 
          }
        }
        $header = array(
            "Authorization: Bearer MTIzNA==",
            "Content-Type" => "application/json"
        );
        $url = $this->apiDomain . "/actionable-dashboard/analytics-viewid-currency";
        $postData['access_token']= $this->generateAccessToken($this->get_tvc_access_token(), $this->get_tvc_refresh_token());
        $args = array(
          'headers' =>$header,
          'method' => 'POST',
          'body' => wp_json_encode($postData)
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
    /**
     * @since 4.1.4
     * Get  google analytics reports call using reporting API
     */
    public function get_google_analytics_reports($postData) {
      try {
        if(!empty($postData)){
          foreach ($postData as $key => $value) {
            $postData[$key] = sanitize_text_field($value); 
          }
        }
        $url = $this->apiDomain . "/actionable-dashboard/google-analytics-reports";
        $header = array(
            "Authorization: Bearer MTIzNA==",
            "Content-Type" => "application/json"
        );
        
        $access_token = $this->generateAccessToken($this->get_tvc_access_token(), $this->get_tvc_refresh_token());
        if($access_token != ""){
            $postData['access_token']= $access_token; 
            $args = array(
              'headers' =>$header,
              'method' => 'POST',
              'body' => wp_json_encode($postData)
            );
            $result = $this->tc_wp_remot_call_post(esc_url_raw($url), $args);
            //print_r($result);
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
        }else{
            $return = new \stdClass();
            $return->error = true;
            $return->message = 'access_token_error';
            return $return;
        }           
      } catch (Exception $e) {
          return $e->getMessage();
      }
    }

    public function setGmcCategoryMapping($postData) {
      try {
        if(!empty($postData)){
          foreach ($postData as $key => $value) {
            $postData[$key] = sanitize_text_field($value); 
          }
        }
        $url = $this->apiDomain . '/gmc/gmc-category-mapping';

        $args = array(
            'timeout' => 10000,
            'headers' => array(
                'Authorization' => "Bearer $this->token",
                'Content-Type' => 'application/json'
            ),
            'method' => 'POST',
            'body' => wp_json_encode($postData)
        );

        // Send remote request
        $request = wp_remote_post(esc_url_raw($url), $args);

        // Retrieve information
        $response_code = wp_remote_retrieve_response_code($request);
        $response_message = wp_remote_retrieve_response_message($request);
        $response_body = json_decode(wp_remote_retrieve_body($request));

        if ((isset($response_body->error) && $response_body->error == '')) {
          return new WP_REST_Response(
            array(
            'status' => $response_code,
            'message' => $response_message,
            'data' => $response_body->data
            )
          );
        } else {
            return new WP_Error($response_code, $response_message, $response_body);
        }
      } catch (Exception $e) {
          return $e->getMessage();
      }
    }
    public function setGmcAttributeMapping($postData) {
      try {
        if(!empty($postData)){
          foreach ($postData as $key => $value) {
            $postData[$key] = sanitize_text_field($value); 
          }
        }
        $url = $this->apiDomain . '/gmc/gmc-attribute-mapping';

        $args = array(
          'timeout' => 10000,
            'headers' => array(
                'Authorization' => "Bearer $this->token",
                'Content-Type' => 'application/json'
            ),
            'method' => 'POST',
            'body' => wp_json_encode($postData)
        );

        // Send remote request
        $request = wp_remote_post($url, $args);

        // Retrieve information
        $response_code = wp_remote_retrieve_response_code($request);
        $response_message = wp_remote_retrieve_response_message($request);
        $response_body = json_decode(wp_remote_retrieve_body($request));

        if ((isset($response_body->error) && $response_body->error == '')) {

            return new WP_REST_Response(
                    array(
                'status' => $response_code,
                'message' => $response_message,
                'data' => $response_body->data
                    )
            );
        } else {
            return new WP_Error($response_code, $response_message, $response_body);
        }
      } catch (Exception $e) {
          return $e->getMessage();
      }
    }
    public function products_sync($postData) {
      try {
        if(!empty($postData)){
          foreach ($postData as $key => $value) {
            if( in_array($key, array("merchant_id", "account_id", "subscription_id") ) ){
              $postData[$key] = sanitize_text_field($value);
            }
          }
        }
        $url = $this->apiDomain . "/products/batch";            
        $args = array(
          'timeout' => 10000,
          'headers' => array(
            'Authorization' => "Bearer MTIzNA==",
            'Content-Type' => 'application/json',
            'AccessToken' => $this->generateAccessToken($this->get_tvc_access_token(), $this->get_tvc_refresh_token())
          ),
          'body' => wp_json_encode($postData)
        );
        $request = wp_remote_post(esc_url_raw($url), $args);
        
        // Retrieve information
        $response_code = wp_remote_retrieve_response_code($request);
        $response_message = wp_remote_retrieve_response_message($request);
        $response = json_decode(wp_remote_retrieve_body($request));
        $return = new \stdClass();
        if (isset($response->error) && $response->error == '') {
          $return->error = false;
          $return->products_sync = count($response->data->entries);
          return $return;
        }else{         
          $return->error = true;
          $return->arges =  $args;
          foreach($response->errors as $err){
              $return->message = $err;
              break;
          }                               
          return $return;
        }
      } catch (Exception $e) {
          return $e->getMessage();
      }
    }
    public function getSyncProductList($postData) {
      try {
        if(!empty($postData)){
          foreach ($postData as $key => $value) {
            $postData[$key] = sanitize_text_field($value); 
          }
        }
        $url = $this->apiDomain . "/products/list";
        $postData["maxResults"] = 50;
        $args = array(
          'timeout' => 10000,
          'headers' => array(
            'Authorization' => "Bearer MTIzNA==",
            'Content-Type' => 'application/json',
            'AccessToken' => $this->generateAccessToken($this->get_tvc_access_token(), $this->get_tvc_refresh_token())
          ),
          'body' => wp_json_encode($postData)
        );
        $request = wp_remote_post(esc_url_raw($url), $args);
        
        // Retrieve information
        $response_code = wp_remote_retrieve_response_code($request);
        $response_message = wp_remote_retrieve_response_message($request);
        $response = json_decode(wp_remote_retrieve_body($request));
        
        $return = new \stdClass();
        if (isset($response->error) && $response->error == '') {
          $return->status = $response_code;
          $return->error = false;
          $return->data = $response->data;
          $return->message = $response->message;
          return $return;
        }else{    
          $return->status = $response_code;     
          $return->error = true;
          foreach($response->errors as $err){
            $return->message = $err;
            break;
          }                               
          return $return;
        }
          
      } catch (Exception $e) {
          return $e->getMessage();
      }
    }
    

    public function getCampaignCurrencySymbol($postData) {
      try {
        if(!empty($postData)){
          foreach ($postData as $key => $value) {
            $postData[$key] = sanitize_text_field($value); 
          }
        }
        $url = $this->apiDomain . '/campaigns/currency-symbol';

        $args = array(
          'timeout' => 10000,
            'headers' => array(
                'Authorization' => "Bearer $this->token",
                'Content-Type' => 'application/json'
            ),
            'body' => wp_json_encode($postData)
        );

        // Send remote request
        $request = wp_remote_post(esc_url_raw($url), $args);

        // Retrieve information
        $response_code = wp_remote_retrieve_response_code($request);
        $response_message = wp_remote_retrieve_response_message($request);
        $response_body = json_decode(wp_remote_retrieve_body($request));
        if ((isset($response_body->error) && $response_body->error == '')) {

            return new WP_REST_Response(
                    array(
                'status' => $response_code,
                'message' => $response_message,
                'data' => $response_body->data
                    )
            );
        } else {
            return new WP_Error($response_code, $response_message, $response_body);
        }
      } catch (Exception $e) {
          return $e->getMessage();
      }
    }

    public function record_customer_feedback($postData){
      try {
        $url = $this->apiDomain . '/customerfeedback';
        $args = array(
          'timeout' => 10000,
          'headers' => array(
            'Authorization' => "Bearer MTIzNA==",
            'Content-Type' => 'application/json',
            'AccessToken' => $this->generateAccessToken($this->get_tvc_access_token(), $this->get_tvc_refresh_token())
          ),
          'body' => wp_json_encode($postData)
        );
        $request = wp_remote_post(esc_url_raw($url), $args);
        // Retrieve information
        $response_code = wp_remote_retrieve_response_code($request);
        $response_message = wp_remote_retrieve_response_message($request);
        $result = json_decode(wp_remote_retrieve_body($request));
        $return = new \stdClass();
        if ((isset($result->error) && $result->error == '')) {
          $return->message = "Your feedback was successfully recoded.";
          $return->error = false;
          return $return;
        }else{
          $return->error = true;
          $return->errors = $result->errors;          
          return $return;
        }
      } catch (Exception $e) {
          return $e->getMessage();
      }
    }

    public function generateAccessToken($access_token, $refresh_token) {
      $url = "https://www.googleapis.com/oauth2/v1/tokeninfo?=" . $access_token;
      $request =  wp_remote_get(esc_url_raw($url), array("access_token" => $access_token, 'timeout' => 10000));
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

    public function siteVerificationToken($postData) {
      try {
          $url = $this->apiDomain . '/gmc/site-verification-token';
          $header = array("Authorization: Bearer MTIzNA==",
              "Content-Type" => "application/json",
              "AccessToken:" . $this->generateAccessToken($this->get_tvc_access_token(), $this->get_tvc_refresh_token())
          );

          $data = [
              'merchant_id' => sanitize_text_field($postData['merchant_id']),
              'website' => sanitize_text_field($postData['website_url']),
              'account_id' => sanitize_text_field($postData['account_id']),
              'method' => sanitize_text_field($postData['method'])
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

    public function siteVerification($postData) {
        try {
            $url = $this->apiDomain . '/gmc/site-verification';
            $header = array("Authorization: Bearer MTIzNA==",
              "Content-Type" => "application/json",
              "AccessToken:" . $this->generateAccessToken($this->get_tvc_access_token(), $this->get_tvc_refresh_token())
            );

            $data = [
                'merchant_id' => sanitize_text_field($postData['merchant_id']),
                'website' => esc_url_raw($postData['website_url']),
                'subscription_id' => sanitize_text_field($postData['subscription_id']),
                'account_id' => sanitize_text_field($postData['account_id']),
                'method' => sanitize_text_field($postData['method'])
            ];

            $args = array(
              'timeout' => 10000,
              'headers' =>$header,
              'method' => 'POST',
              'body' => wp_json_encode($data)
            );
            $request = wp_remote_post(esc_url_raw($url), $args);
            // Retrieve information
            $response_code = wp_remote_retrieve_response_code($request);
            $response_message = wp_remote_retrieve_response_message($request);
            $result = json_decode(wp_remote_retrieve_body($request));
            

            $return = new \stdClass();
            if ((isset($result->error) && $result->error == '')) {
              
              $return->data = $result->data;
              $return->error = false;
              return $return;
            }else{
              $return->error = true;
              $return->errors = $result->errors;
              
              return $return;
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function claimWebsite($postData) {
      try {
          $url = $this->apiDomain . '/gmc/claim-website';
          $data = [
              'merchant_id' => sanitize_text_field($postData['merchant_id']),
              'account_id' => sanitize_text_field($postData['account_id']),
              'website' => esc_url_raw($postData['website_url']),
              'access_token' => $this->generateAccessToken($this->get_tvc_access_token(), $this->get_tvc_refresh_token()),
              'subscription_id' => sanitize_text_field($postData['subscription_id']),
          ];
          $args = array(
            'timeout' => 10000,
            'headers' => array(
              'Authorization' => "Bearer MTIzNA==",
              'Content-Type' => 'application/json',
              'AccessToken' => $this->generateAccessToken($this->get_tvc_access_token(), $this->get_tvc_refresh_token())
            ),
            'body' => wp_json_encode($data)
          );
          $request = wp_remote_post(esc_url_raw($url), $args);
          // Retrieve information
          $response_code = wp_remote_retrieve_response_code($request);
          $response_message = wp_remote_retrieve_response_message($request);
          $result = json_decode(wp_remote_retrieve_body($request));

          $return = new \stdClass();
          if ((isset($result->error) && $result->error == '')) {
            
            $return->data = $result->data;
            $return->error = false;
            return $return;
          }else{
            $return->error = true;
            $return->errors = $result->errors;
            
            return $return;
          }
      } catch (Exception $e) {
          return $e->getMessage();
      }
  }

}
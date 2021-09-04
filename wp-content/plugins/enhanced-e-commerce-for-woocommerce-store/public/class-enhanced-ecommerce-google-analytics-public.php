<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       tatvic.com
 * @since      1.0.0
 *
 * @package    Enhanced_Ecommerce_Google_Analytics
 * @subpackage Enhanced_Ecommerce_Google_Analytics/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Enhanced_Ecommerce_Google_Analytics
 * @subpackage Enhanced_Ecommerce_Google_Analytics/public
 * @author     Tatvic
 */
class Enhanced_Ecommerce_Google_Analytics_Public {
    /**
     * Init and hook in the integration.
     *
     * @access public
     * @return void
     */
    //set plugin version
     public $tvc_eeVer = PLUGIN_TVC_VERSION;
    /**
     * @var mixed $tvc_aga
     */
    protected $tvc_aga;
    /**
     * @var mixed $ga_id
     */
    protected $ga_id;
    /**
     * @var $ga_LC
     */
    protected $ga_LC;
    /**
     * @var mixed $ga_ST
     */
    protected $ga_ST;
    /**
     * @var bool $ga_gCkout
     */
    protected $ga_gCkout;
    /**
     * @var mixed $ga_eeT
     */
    protected $ga_eeT;
    /**
     * @var bool $ga_DF
     */
    protected $ga_DF;
    /**
     * @var int|mixed $ga_imTh
     */
    protected $ga_imTh;
    /**
     * @var bool $ga_OPTOUT
     */
    protected $ga_OPTOUT;
    /**
     * @var bool $ga_PrivacyPolicy
     */
    protected $ga_PrivacyPolicy;
    /**
     * @var bool $ga_IPA
     */
    protected $ga_IPA;
    /**
     * @var mixed $enhanced_e_commerce_tracking
     */
    protected $enhanced_e_commerce_tracking;
    /**
     * @var mixed $add_gtag_snippet
     */
    protected $add_gtag_snippet;
    /**
     * @var mixed $gm_id
     */
    protected $gm_id;
    /**
     * @var mixed $google_ads_id
     */
    protected $google_ads_id;
    /**
     * @var mixed $ga_excT
     */
    protected $ga_excT;
    protected $exception_tracking;
    protected $ga_elaT;
    protected $google_merchant_id;
    protected $tracking_option;
    protected $ga_gUser;
    protected $plugin_name;
    protected $version;
    protected $ads_ert;
    protected $ads_edrt;
    protected $ads_tracking_id;
    protected $TVC_Admin_Helper;
    protected $remarketing_snippet_id;
    protected $remarketing_snippets;
    /**
     * Enhanced_Ecommerce_Google_Analytics_Public constructor.
     * @param $plugin_name
     * @param $version
     */

    public function __construct($plugin_name, $version) {
        $this->TVC_Admin_Helper = new TVC_Admin_Helper();
        $this->plugin_name = $plugin_name;
        $this->version  = $version;
        $this->tvc_call_hooks();

        $this->tvc_aga = $this->get_option("tvc_aga");
        $this->ga_id = $this->get_option("ga_id");
        $this->ga_eeT = $this->get_option("ga_eeT");
        $this->enhanced_e_commerce_tracking = $this->get_option("enhanced_e_commerce_tracking");
        $this->ga_ST = $this->get_option("ga_ST"); //add_gtag_snippet
        $this->add_gtag_snippet = $this->get_option("add_gtag_snippet"); //add_gtag_snippet
        $this->gm_id = $this->get_option("gm_id"); //measurement_id
        $this->google_ads_id = $this->get_option("google_ads_id");
        $this->ga_excT = $this->get_option("ga_excT"); //exception_tracking
        $this->exception_tracking = $this->get_option("exception_tracking"); //exception_tracking
        $this->ga_elaT = $this->get_option("ga_elaT"); //enhanced_link_attribution_tracking
        $this->google_merchant_id = $this->get_option("google_merchant_id");
        $this->tracking_option = $this->get_option("tracking_option");
        $this->ga_gCkout = $this->get_option("ga_gCkout") == "on" ? true : false; //guest checkout
        $this->ga_gUser = $this->get_option("ga_gUser") == "on" ? true : false; //guest checkout
        $this->ga_DF = $this->get_option("ga_DF") == "on" ? true : false;
        $this->ga_imTh = $this->get_option("ga_Impr") == "" ? 6 : $this->get_option("ga_Impr");
        $this->ga_OPTOUT = $this->get_option("ga_OPTOUT") == "on" ? true : false; //Google Analytics Opt Out
        $this->ga_PrivacyPolicy = $this->get_option("ga_PrivacyPolicy") == "on" ? true : false;
        $this->ga_IPA = $this->get_option("ga_IPA") == "on" ? true : false; //IP Anony.
        $this->ads_ert = get_option('ads_ert'); //Enable remarketing tags
        $this->ads_edrt = get_option('ads_edrt'); //Enable dynamic remarketing tags
        $this->ads_tracking_id = get_option('ads_tracking_id'); 
        $this->ads_ert = get_option('ads_ert');
        $this->ads_edrt = get_option('ads_edrt');

        $remarketing = unserialize(get_option('ee_remarketing_snippets'));
        if(!empty($remarketing) && isset($remarketing['snippets']) && $remarketing['snippets']){
            $this->remarketing_snippets = base64_decode($remarketing['snippets']);
            $this->remarketing_snippet_id = isset($remarketing['id'])?$remarketing['id']:"";
        }

        if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
            // Put your plugin code here
            add_action('woocommerce_init' , function (){
                $this->ga_LC = get_woocommerce_currency(); //Local Currency from Back end
                $this->wc_version_compare("tvc_lc=" . json_encode($this->ga_LC) . ";");
            });
        }
    }
    public function tvc_call_hooks(){
        add_action("wp_head", array($this, "enqueue_scripts"));
        add_action("wp_head", array($this, "ee_settings"));
        add_action("wp_head", array($this, "add_google_site_verification_tag"),1);

        add_action("wp_footer", array($this, "t_products_impre_clicks"));
        add_action("woocommerce_after_shop_loop_item", array($this, "bind_product_metadata"));
        add_action("woocommerce_thankyou", array($this, "ecommerce_tracking_code"));
        add_action("woocommerce_after_single_product", array($this, "product_detail_view"));
        add_action("woocommerce_after_cart",array($this, "remove_cart_tracking"));
        //check out step 1,2,3
        add_action("woocommerce_before_checkout_form", array($this, "checkout_step_1_tracking"));
        add_action("woocommerce_before_checkout_form", array($this, "checkout_step_2_tracking"));
        add_action("woocommerce_before_checkout_form", array($this, "checkout_step_3_tracking"));
        add_action("woocommerce_after_add_to_cart_button", array($this, "add_to_cart"));
        //add version details in footer
        add_action("wp_footer", array($this, "add_plugin_details"));
        //Add Dev ID
        add_action("wp_head", array($this, "add_dev_id"));
        add_action("wp_footer",array($this, "tvc_store_meta_data"));
    }
    public function add_google_site_verification_tag(){
        $TVC_Admin_Helper = new TVC_Admin_Helper();
        $ee_additional_data = $TVC_Admin_Helper->get_ee_additional_data();
        if(isset($ee_additional_data['add_site_varification_tag']) && isset($ee_additional_data['site_varification_tag_val']) && $ee_additional_data['add_site_varification_tag'] == 1 && $ee_additional_data['site_varification_tag_val'] !="" ){
            echo base64_decode($ee_additional_data['site_varification_tag_val']);
        }        
                        
    }
    public function get_option($key){
        $ee_options = array();
        $my_option = get_option( 'ee_options' );
        
        if(!empty($my_option)){
            $ee_options = unserialize($my_option);
        }
        if(isset($ee_options[$key])){
            return $ee_options[$key];
        }
    }
    /**
     * Get store meta data for trouble shoot
     * @access public
     * @return void
     */
    function tvc_store_meta_data() {
        //only on home page
        global $woocommerce;
        $google_detail = $this->TVC_Admin_Helper->get_ee_options_data();
        $sub_data = array();
        if(isset($google_detail['setting'])){
            $googleDetail = $google_detail['setting'];
            $sub_data['sub_id'] = isset($googleDetail->id)?$googleDetail->id:"";
            $sub_data['cu_id']=isset($googleDetail->customer_id)?$googleDetail->customer_id:"";
            $sub_data['pl_id']=isset($googleDetail->plan_id)?$googleDetail->plan_id:"";
            $sub_data['ga_tra_option']=isset($googleDetail->tracking_option)?$googleDetail->tracking_option:"";
            $sub_data['ga_property_id']=isset($googleDetail->property_id)?$googleDetail->property_id:"";
            $sub_data['ga_measurement_id']=isset($googleDetail->measurement_id)?$googleDetail->measurement_id:"";
            $sub_data['ga_ads_id']=isset($googleDetail->google_ads_id)?$googleDetail->google_ads_id:"";
            $sub_data['ga_gmc_id']=isset($googleDetail->google_merchant_center_id)?$googleDetail->google_merchant_center_id:"";
            $sub_data['op_gtag_js']=isset($googleDetail->add_gtag_snippet)?$googleDetail->add_gtag_snippet:"";
            $sub_data['op_en_e_t']=isset($googleDetail->enhanced_e_commerce_tracking)?$googleDetail->enhanced_e_commerce_tracking:"";
            $sub_data['op_rm_t_t']=isset($googleDetail->remarketing_tags)?$googleDetail->remarketing_tags:"";
            $sub_data['op_dy_rm_t_t']=isset($googleDetail->dynamic_remarketing_tags)?$googleDetail->dynamic_remarketing_tags:"";
            $sub_data['op_li_ga_wi_ads']=isset($googleDetail->link_google_analytics_with_google_ads)?$googleDetail->link_google_analytics_with_google_ads:"";
            $sub_data['gmc_is_product_sync']=isset($googleDetail->is_product_sync)?$googleDetail->is_product_sync:"";
            $sub_data['gmc_is_site_verified']=isset($googleDetail->is_site_verified)?$googleDetail->is_site_verified:"";
            $sub_data['gmc_is_domain_claim']=isset($googleDetail->is_domain_claim)?$googleDetail->is_domain_claim:"";
            $sub_data['gmc_product_count']=isset($googleDetail->product_count)?$googleDetail->product_count:"";            
        }
        $tvc_sMetaData = array(
            'tvc_wcv' => $woocommerce->version,
            'tvc_wpv' => get_bloginfo('version'),
            'tvc_eev' => $this->tvc_eeVer,
            'tvc_cnf' => array(
                't_ee' => $this->ga_eeT,
                't_df' => $this->ga_DF,
                't_gUser'=>$this->ga_gUser,
                't_UAen'=>$this->ga_ST,
                't_thr' => $this->ga_imTh,
                't_IPA' => $this->ga_IPA,
                't_OptOut' => $this->ga_OPTOUT,
                't_PrivacyPolicy' => $this->ga_PrivacyPolicy
            ),
            'tvc_sub_data'=> $sub_data
        );
        $this->wc_version_compare("tvc_smd=" . json_encode($tvc_sMetaData) . ";");
    }
    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since4.0.0
     */
    public function enqueue_scripts() {
      //wp_enqueue_script($this->plugin_name, ENHANCAD_PLUGIN_URL . '/public/js/tvc-ee-google-analytics.js', array('jquery'), $this->version, false);
    }

    /**
     * add dev id
     *
     * @access public
     * @return void
     */
    function add_dev_id() {
        echo "<script>(window.gaDevIds=window.gaDevIds||[]).push('5CDcaG');</script>";
    }

    /**
     * display details of plugin
     *
     * @access public
     * @return void
     */
    function add_plugin_details() {
        echo '<!--Enhanced Ecommerce Google Analytics Plugin for Woocommerce by Tatvic Plugin Version:'.$this->tvc_eeVer.'-->';
    }

    /**
     * Check if tracking is disabled
     *
     * @access private
     * @param mixed $type
     * @return bool
     */
    private function disable_tracking($type) {
        if (is_admin() || "" == $type || current_user_can("manage_options")) {
            return true;
        }
    }

    /**
     * woocommerce version compare
     *
     * @access public
     * @return void
     */
    function wc_version_compare($codeSnippet) {
        global $woocommerce;

        if (version_compare($woocommerce->version, "2.1", ">=")) {
            wc_enqueue_js($codeSnippet);
        } else {
            $woocommerce->add_inline_js($codeSnippet);
        }
    }
    /**
     * Enhanced Ecommerce GA plugin Settings
     *
     * @access public
     * @return void
     */
    function ee_settings() {
        global $woocommerce;

        //common validation----start
        if (is_admin() || $this->ga_ST == "" || current_user_can("manage_options")) {
            return;
        }
        
        $tracking_id = $this->ga_id;
        $measurment_id = $this->gm_id;
        $tracking_opt = $this->tracking_option;


        if(!$this->ga_PrivacyPolicy) {
            return;
        }

        //common validation----end
        $set_domain_name = "auto";
        // IP Anonymization
        if ($this->ga_IPA) {
            $ga_ip_anonymization = '"anonymize_ip":true,';
        } else {
            $ga_ip_anonymization ="";
        }


        echo '<script  type="text/javascript" defer="defer">';
            echo 'var track_option = '.json_encode($tracking_opt).';';
            echo 'var ua_track_id = '.json_encode($tracking_id).';';
            echo 'var ga4_measure_id = '.json_encode($measurment_id).';';
            echo 'var adsTringId = '.json_encode($this->ads_tracking_id).';';
            echo 'var ads_ert = '.json_encode($this->ads_ert).';';
            echo 'var ads_edrt = '.json_encode($this->ads_edrt).';';
            echo 'var remarketing_snippet_id = '.json_encode($this->remarketing_snippet_id).';';
        echo '</script>';

        if($this->ga_OPTOUT) {
            echo '<script>
                // Set to the same value as the web property used on the site
                var gaProperty = "'.$tracking_id.'";
        
                // Disable tracking if the opt-out cookie exists.
                var disableStr = "ga-disable-" + gaProperty;
                if (document.cookie.indexOf(disableStr + "=true") > -1) {
                  window[disableStr] = true;
                }
        
                // Opt-out function
                function gaOptout() {
                var expDate = new Date;
                expDate.setMonth(expDate.getMonth() + 26);
                  document.cookie = disableStr + "=true; expires="+expDate.toGMTString()+";path=/";
                  window[disableStr] = true;
                }
                </script>';
        }

        if(($tracking_opt == "UA" || $tracking_id || $tracking_opt == "") && $tracking_opt != "BOTH"){
            //$id = esc_js($tracking_id);
            echo  '<script async src="https://www.googletagmanager.com/gtag/js?id='.esc_js($tracking_id).'"></script>
                <script>
                  window.dataLayer = window.dataLayer || [];
                  function gtag(){dataLayer.push(arguments);}
                  gtag("js", new Date());
                  gtag("config", "'.esc_js($tracking_id).'",{'.$ga_ip_anonymization.' "cookie_domain":"'.$set_domain_name.'"});
                </script>
                ';
            //echo $code;
        }
       
        if($tracking_opt == "GA4"){
            echo '<script async src="https://www.googletagmanager.com/gtag/js?id='.esc_js($measurment_id).'"></script>
                <script>
                  window.dataLayer = window.dataLayer || [];
                  function gtag(){dataLayer.push(arguments);}
                  gtag("js", new Date());
                  gtag("config", "'.esc_js($measurment_id).'",{'.$ga_ip_anonymization.' "cookie_domain":"'.$set_domain_name.'"});
                </script>
                ';
            
        }
        if($tracking_opt == "BOTH"){
            echo  '<script async src="https://www.googletagmanager.com/gtag/js?id='.esc_js($measurment_id).'"></script>
                <script>
                  window.dataLayer = window.dataLayer || [];
                  function gtag(){dataLayer.push(arguments);}
                  gtag("js", new Date());
                  gtag("config", "'.esc_js($measurment_id).'",{'.$ga_ip_anonymization.' "cookie_domain":"'.$set_domain_name.'"});
                  gtag("config", "'.esc_js($tracking_id).'");
                </script>
                ';
        }
        if($this->ads_ert || $this->ads_edrt){
          if(!empty($this->remarketing_snippets) && $this->remarketing_snippets){
            echo $this->remarketing_snippets;
          }else{
            $google_detail = $this->TVC_Admin_Helper->get_ee_options_data();
            if(isset($google_detail['setting'])){
                $googleDetail = $google_detail['setting'];
                echo  $googleDetail->google_ads_snippets;
            }
          }
        }
    }

    /**
     * Google Analytics eCommerce tracking
     *
     * @access public
     * @param mixed $order_id
     * @return void
     */
    function ecommerce_tracking_code($order_id) {

        global $woocommerce;
        if ($this->disable_tracking($this->ga_eeT) || current_user_can("manage_options") || get_post_meta($order_id, "_tracked", true) == 1){
            return;
        }

        // Doing eCommerce tracking so unhook standard tracking from the footer
        remove_action("wp_footer", array($this, "ee_settings"));

        // Get the order and output tracking code
        $order = new WC_Order($order_id);
        //Get Applied Coupon Codes
        $coupons_list = '';
        if(version_compare($woocommerce->version, "3.7", ">")){
            if ($order->get_coupon_codes()) {
                $coupons_count = count($order->get_coupon_codes());
                $i = 1;
                foreach ($order->get_coupon_codes() as $coupon) {
                    $coupons_list .= $coupon;
                    if ($i < $coupons_count)
                        $coupons_list .= ', ';
                    $i++;
                }
            }
        }else{
            if ($order->get_used_coupons()) {
                $coupons_count = count($order->get_used_coupons());
                $i = 1;
                foreach ($order->get_used_coupons() as $coupon) {
                    $coupons_list .= $coupon;
                    if ($i < $coupons_count)
                        $coupons_list .= ', ';
                    $i++;
                }
            }    
        }
        
        //get domain name if value is set
        if (!empty($this->ga_Dname)) {
            $set_domain_name = esc_js($this->ga_Dname);
        } else {
            $set_domain_name = "auto";
        }

        // Order items
        if ($order->get_items()) {
            foreach ($order->get_items() as $item) {
                $_product = $item->get_product();
                if (isset($_product->variation_data)) {
                    $categories=get_the_terms($_product->get_parent_id(), "product_cat");
                    $attributes=esc_js(wc_get_formatted_variation($_product->get_variation_attributes(), true));
                    if ($categories) {
                        foreach ($categories as $category) {
                            $out[] = $category->name;
                        }
                    }
                    $categories=esc_js(join(",", $out));
                } else {
                    $out = array();
                    if(version_compare($woocommerce->version, "2.7", "<")){
                        $categories = get_the_terms($_product->ID, "product_cat");
                    }else{
                        $categories = get_the_terms($_product->get_id(), "product_cat");
                    }

                    if ($categories) {
                        foreach ($categories as $category) {
                            $out[] = $category->name;
                        }
                    }
                    $categories=esc_js(join(",", $out));
                }
                //orderpage Prod json
                if (isset($_product->variation_data)) {
                    $orderpage_prod_Array[get_permalink($_product->ID)]=array(
                        "tvc_id" => esc_html($_product->ID),
                        "tvc_i" => esc_js($_product->get_sku() ? $_product->get_sku() : $_product->ID),
                        "tvc_n" => html_entity_decode($item["name"]),
                        "tvc_p" => esc_js($order->get_item_total($item)),
                        "tvc_c" => $categories,
                        "tvc_attr" => $attributes,
                        "tvc_q"=>esc_js($item["qty"])
                    );
                } else {
                    if(version_compare($woocommerce->version, "2.7", "<")){
                        $orderpage_prod_Array[get_permalink($_product->ID)]=array(
                        "tvc_id" => esc_html($_product->ID),
                        "tvc_i" => esc_js($_product->get_sku() ? $_product->get_sku() : $_product->ID),
                        "tvc_n" => html_entity_decode($item["name"]),
                        "tvc_p" => esc_js($order->get_item_total($item)),
                        "tvc_c" => $categories,
                        "tvc_q"=>esc_js($item["qty"])
                        );
                    }else{
                        $orderpage_prod_Array[get_permalink($_product->get_id())]=array(
                        "tvc_id" => esc_html($_product->get_id()),
                        "tvc_i" => esc_js($_product->get_sku() ? $_product->get_sku() : $_product->get_id()),
                        "tvc_n" => html_entity_decode($_product->get_title()),
                        "tvc_p" => esc_js($order->get_item_total($item)),
                        "tvc_c" => $categories,
                        "tvc_q"=>esc_js($item["qty"])
                        );
                    }
                }
  
            }
            //make json for prod meta data on order page
            $this->wc_version_compare("tvc_oc=" . json_encode($orderpage_prod_Array) . ";");
        }

        //get shipping cost based on version >2.1 get_total_shipping() < get_shipping
        if (version_compare($woocommerce->version, "2.1", ">=")) {
            $tvc_sc = $order->get_total_shipping();
        } else {
            $tvc_sc = $order->get_shipping();
        }
        //orderpage transcation data json
        $orderpage_trans_Array=array(
            "id"=> esc_js($order->get_order_number()),      // Transaction ID. Required
            "affiliation"=> esc_js(get_bloginfo('name')), // Affiliation or store name
            "revenue"=>esc_js($order->get_total()),        // Grand Total
            "tax"=> esc_js($order->get_total_tax()),        // Tax
            "shipping"=> esc_js($tvc_sc),    // Shipping
            "coupon"=>$coupons_list
        );
        //make json for trans data on order page
        $this->wc_version_compare("tvc_td=" . json_encode($orderpage_trans_Array) . ";");

        if($this->ga_id || $this->tracking_option == "UA" || $this->tracking_option == "BOTH") {
            $code ='
                     var items = [];
                    //set local currencies
                gtag("set", {"currency": tvc_lc});
                for(var t_item in tvc_oc){
                    items.push({
                        "id": tvc_oc[t_item].tvc_i,
                        "name": tvc_oc[t_item].tvc_n, 
                        "category": tvc_oc[t_item].tvc_c,
                        "attributes": tvc_oc[t_item].tvc_attr,
                        "price": tvc_oc[t_item].tvc_p,
                        "quantity": tvc_oc[t_item].tvc_q,
                    });
                   
                }
                gtag("event", "purchase", {
                    "transaction_id":tvc_td.id,
                    "affiliation": tvc_td.affiliation,
                    "value":tvc_td.revenue,
                    "tax": tvc_td.tax,
                    "shipping": tvc_td.shipping,
                    "coupon": tvc_td.coupon,
                    "event_category": "Enhanced-Ecommerce",
                    "event_label":"order_confirmation",
                    "non_interaction": true,
                    "items":items
                });
                if(adsTringId != "" && ( ads_ert == 1 || ads_edrt == 1)){
                    var ads_items = [];
                    var ads_value=0;
                    for(var t_item in tvc_oc){
                        ads_value=ads_value + parseFloat(tvc_oc[t_item].tvc_p);
                        ads_items.push({
                            item_id: tvc_oc[t_item].tvc_id,
                            google_business_vertical: "retail"
                        });
                    }
                    gtag("event","purchase", {
                        "send_to":remarketing_snippet_id,
                        "value": tvc_td.revenue,
                        "items": ads_items
                      });
                }
        ';

            //check woocommerce version
            $this->wc_version_compare($code);
            update_post_meta($order_id, "_tracked", 1);
        }
        // start GA4 or Both
        if( $this->gm_id && $this->tracking_option == "GA4") {
            $code = '            
            var items = [];
            for(var t_item in tvc_oc){
                items.push({
                    "item_id": tvc_oc[t_item].tvc_i,
                    "item_name": tvc_oc[t_item].tvc_n, 
                    "coupon": tvc_td.coupon,
                    "affiliation": tvc_td.affiliation,
                    "item_category": tvc_oc[t_item].tvc_c,
                    "item_variant": tvc_oc[t_item].tvc_attr,
                    "price": tvc_oc[t_item].tvc_p,
                    "currency": tvc_lc,
                    "quantity": tvc_oc[t_item].tvc_q,
                });
               
            }
            gtag("event", "purchase", {
                "transaction_id":tvc_td.id,
                "shipping": tvc_td.shipping,
                "affiliation": tvc_td.affiliation,
                "value":tvc_td.revenue,
                "currency": tvc_lc,
                "coupon": tvc_td.coupon,
                "tax": tvc_td.tax,
                "event_category": "Enhanced-Ecommerce",
                "event_label":"order_confirmation",
                "non_interaction": true,
                "items":items
            });
            
            if(adsTringId != "" && ( ads_ert == 1 || ads_edrt == 1)){
                    var ads_items = [];
                    var ads_value=0;
                    for(var t_item in tvc_oc){
                    ads_value=ads_value + parseFloat(tvc_oc[t_item].tvc_p);
                        ads_items.push({
                            item_id: tvc_oc[t_item].tvc_id,
                            google_business_vertical: "retail"
                        });
                    }
                    gtag("event","purchase", {
                        "send_to":remarketing_snippet_id,
                        "value": tvc_td.revenue,
                        "items": ads_items
                      });
                }
        ';

            //check woocommerce version
            $this->wc_version_compare($code);
            update_post_meta($order_id, "_tracked", 1);
        }
    }

    /**
     * Enhanced E-commerce tracking for single product add to cart
     *
     * @access public
     * @return void
     */
    function add_to_cart() {
        if ($this->disable_tracking($this->ga_eeT))
            return;
        //return if not product page
        if (!is_single())
            return;
        global $product,$woocommerce;

        if(version_compare($woocommerce->version, "2.7", "<")){
            $category = get_the_terms($product->ID, "product_cat");
        }else{
            $category = get_the_terms($product->get_id(), "product_cat");
        }
        $categories = "";
        if ($category) {
            foreach ($category as $term) {
                $categories.=$term->name . ",";
            }
        }
        //remove last comma(,) if multiple categories are there
        $categories = rtrim($categories, ",");
        if($this->ga_id || ($this->tracking_option == "UA" || $this->tracking_option == "BOTH")) {
            $code = '
                   var items = [];
                //set local currencies
                gtag("set", {"currency": tvc_lc});
                jQuery("[class*=single_add_to_cart_button]").click(function() {
                // Enhanced E-commerce Add to cart clicks
                    gtag("event", "add_to_cart", {
                        "event_category":"Enhanced-Ecommerce",
                        "event_label":"add_to_cart_click",
                        "non_interaction": true,
                        "items": [{
                            "id" : tvc_po.tvc_i,
                            "name": tvc_po.tvc_n,
                            "category" :tvc_po.tvc_c,
                            "price": tvc_po.tvc_p,
                            "quantity" :jQuery(this).parent().find("input[name=quantity]").val()
                        }]
                    });
                    //add remarketing and dynamicremarketing tags
                    if(adsTringId != "" && ( ads_ert == 1 || ads_edrt == 1)){
                        gtag("event","add_to_cart", {
                            "send_to":remarketing_snippet_id,
                            "value": tvc_po.tvc_p,
                            "items": [
                            {
                              "id": tvc_po.tvc_id, 
                              "google_business_vertical": "retail"
                            }
                          ]
                        });
                    }                                 
                });
                
            ';
            //check woocommerce version
            $this->wc_version_compare($code);
        }
        if($this->gm_id && $this->tracking_option == "GA4") {
            $code = '
            var items = [];
            jQuery("[class*=single_add_to_cart_button]").click(function() {
                gtag("event", "add_to_cart", {
                    "event_category":"Enhanced-Ecommerce",
                    "event_label":"add_to_cart_click",
                    "currency": tvc_lc,
                    "non_interaction": true,
                    "items": [{
                        "item_id" : tvc_po.tvc_i,
                        "item_name": tvc_po.tvc_n,
                        "item_category" :tvc_po.tvc_c,
                        "price": tvc_po.tvc_p,
                        "quantity" :jQuery(this).parent().find("input[name=quantity]").val()
                    }]
                });   
                
                //add remarketing and dynamicremarketing tags
                if(adsTringId != "" && ( ads_ert == 1 || ads_edrt == 1)){
                    gtag("event","add_to_cart", {
                        "send_to":remarketing_snippet_id,
                        "value": tvc_po.tvc_p,
                        "items": [
                        {
                          "id": tvc_po.tvc_id, 
                          "google_business_vertical": "retail"
                        }
                      ]
                    });
                }            
            });
        ';
        $this->wc_version_compare($code);
        }
    }

    /**
     * Enhanced E-commerce tracking for product detail view
     *
     * @access public
     * @return void
     */
    public function product_detail_view() {

        if ($this->disable_tracking($this->ga_eeT)) {
            return;
        }

        global $product,$woocommerce;
        if(version_compare($woocommerce->version, "2.7", "<")){
            $category = get_the_terms($product->ID, "product_cat");
        }else{
            $category = get_the_terms($product->get_id(), "product_cat");
        }
        $categories = "";
        if ($category) {
            foreach ($category as $term) {
                $categories.=$term->name . ",";
            }
        }
        //remove last comma(,) if multiple categories are there
        $categories = rtrim($categories, ",");
        //product detail view json
        if(version_compare($woocommerce->version, "2.7", "<")){
            $prodpage_detail_json = array(
                "tvc_id" => esc_html($product->id),
                "tvc_i" => $product->get_sku() ? $product->get_sku() : $product->id,
                "tvc_n" => $product->get_title(),
                "tvc_c" => $categories,
                "tvc_p" => $product->get_price()
            );
        }else{
            $prodpage_detail_json = array(
                "tvc_id" => esc_html($product->get_id()),
                "tvc_i" => $product->get_sku() ? $product->get_sku() : $product->get_id(),
                "tvc_n" => $product->get_title(),
                "tvc_c" => $categories,
                "tvc_p" => $product->get_price()
            );
        }

        if (empty($prodpage_detail_json)) {
            //prod page array
            $prodpage_detail_json = array();
        }
        //prod page detail view json
        $this->wc_version_compare("tvc_po=" . json_encode($prodpage_detail_json) . ";");
        if($this->ga_id || $this->tracking_option == "UA" || $this->tracking_option == "BOTH") {
            $code = '
            gtag("event", "view_item", {
                        "event_category":"Enhanced-Ecommerce",
                        "event_label":"product_impression_pp",
                        "items": [
                          {
                            "id": tvc_po.tvc_i,// Product details are provided in an impressionFieldObject.
                            "name":  tvc_po.tvc_n,
                            "category":tvc_po.tvc_c,
                          }
                        ],
                        "non_interaction": true
            })
            //add remarketing and dynamicremarketing tags
            if(adsTringId != "" && ( ads_ert == 1 || ads_edrt == 1)){
                gtag("event","view_item", {
                    "send_to":remarketing_snippet_id,
                    "value": tvc_po.tvc_p,
                    "items": [
                      {
                        "id": tvc_po.tvc_id, 
                        "google_business_vertical": "retail"
                      }
                    ]
                  });
            }
            ';
            //check woocommerce version
            if(is_product()){
                $this->wc_version_compare($code);
            }
        }

        if( $this->gm_id && $this->tracking_option == "GA4") {
            $code = '
                gtag("event", "view_item", {
                    "event_category":"Enhanced-Ecommerce",
                    "event_label":"product_impression_pp",
                    "currency": tvc_lc,
                    "items": [
                      {
                        "item_id": tvc_po.tvc_i,
                        "item_name":  tvc_po.tvc_n,
                        "item_category":tvc_po.tvc_c,
                      }
                    ],
                    "non_interaction": true
                })
                //add remarketing and dynamicremarketing tags
            if(adsTringId != "" && ( ads_ert == 1 || ads_edrt == 1)){
                gtag("event","view_item", {
                    "send_to":remarketing_snippet_id,
                    "value": tvc_po.tvc_p,
                    "items": [
                      {
                        "id": tvc_po.tvc_id, 
                        "google_business_vertical": "retail"
                      }
                    ]
                  });
            }
                ';
            //check woocommerce version
            if (is_product()) {
                $this->wc_version_compare($code);
            }
        }
    }

    /**
     * Enhanced E-commerce tracking for product impressions on category pages (hidden fields) , product page (related section)
     * home page (featured section and recent section)
     *
     * @access public
     * @return void
     */
    public function bind_product_metadata() {
        if ($this->disable_tracking($this->ga_eeT)) {
            return;
        }

        global $product,$woocommerce;
        if (version_compare($woocommerce->version, "2.7", "<")) {
            $category = get_the_terms($product->Id, "product_cat");
        } else {
            $category = get_the_terms($product->get_id(), "product_cat");
        }

        $categories = "";

        if ($category) {
            foreach ($category as $term) {
                $categories.=$term->name . ",";
            }
        }
        //remove last comma(,) if multiple categories are there
        $categories = rtrim($categories, ",");
        //declare all variable as a global which will used for make json
        global $homepage_json_fp,$homepage_json_ATC_link, $homepage_json_rp,$prodpage_json_relProd,$catpage_json,$prodpage_json_ATC_link,$catpage_json_ATC_link;
        //is home page then make all necessory json
        if (is_home() || is_front_page()) {
            if (!is_array($homepage_json_fp) && !is_array($homepage_json_rp) && !is_array($homepage_json_ATC_link)) {
                $homepage_json_fp = array();
                $homepage_json_rp = array();
                $homepage_json_ATC_link=array();
            }

            // ATC link Array
            if(version_compare($woocommerce->version, "2.7", "<")){
                $homepage_json_ATC_link[$product->add_to_cart_url()]=array("ATC-link"=>get_permalink($product->id));
            }else{
                $homepage_json_ATC_link[$product->add_to_cart_url()]=array("ATC-link"=>get_permalink($product->get_id()));
            }
            //check if product is featured product or not
            if ($product->is_featured()) {
                //check if product is already exists in homepage featured json
                if(version_compare($woocommerce->version, "2.7", "<")){
                    if(!array_key_exists(get_permalink($product->id),$homepage_json_fp)){
                        $homepage_json_fp[get_permalink($product->id)] = array(
                            "tvc_id" => esc_html($product->id),
                            "tvc_i" => esc_html($product->get_sku() ? $product->get_sku() : $product->id),
                            "tvc_n" => esc_html($product->get_title()),
                            "tvc_p" => esc_html($product->get_price()),
                            "tvc_c" => esc_html($categories),
                            "ATC-link"=>$product->add_to_cart_url()
                        );
                        //else add product in homepage recent product json
                    }else {
                        $homepage_json_rp[get_permalink($product->id)] =array(
                            "tvc_id" => esc_html($product->id),
                            "tvc_i" => esc_html($product->get_sku() ? $product->get_sku() : $product->id),
                            "tvc_n" => esc_html($product->get_title()),
                            "tvc_p" => esc_html($product->get_price()),
                            "tvc_c" => esc_html($categories)
                        );
                    }
                }else{
                    if(!array_key_exists(get_permalink($product->get_id()),$homepage_json_fp)){
                        $homepage_json_fp[get_permalink($product->get_id())] = array(
                            "tvc_id" => esc_html($product->get_id()),
                            "tvc_i" => esc_html($product->get_sku() ? $product->get_sku() : $product->get_id()),
                            "tvc_n" => esc_html($product->get_title()),
                            "tvc_p" => esc_html($product->get_price()),
                            "tvc_c" => esc_html($categories),
                            "ATC-link"=>$product->add_to_cart_url()
                        );
                        //else add product in homepage recent product json
                    }else {
                        $homepage_json_rp[get_permalink($product->get_id())] =array(
                            "tvc_id" => esc_html($product->get_id()),
                            "tvc_i" => esc_html($product->get_sku() ? $product->get_sku() : $product->get_id()),
                            "tvc_n" => esc_html($product->get_title()),
                            "tvc_p" => esc_html($product->get_price()),
                            "tvc_c" => esc_html($categories)
                        );
                    }
                }

            } else {
                //else prod add in homepage recent json
                if(version_compare($woocommerce->version, "2.7", "<")){
                    $homepage_json_rp[get_permalink($product->id)] =array(
                        "tvc_id" => esc_html($product->id),
                        "tvc_i" => esc_html($product->get_sku() ? $product->get_sku() : $product->id),
                        "tvc_n" => esc_html($product->get_title()),
                        "tvc_p" => esc_html($product->get_price()),
                        "tvc_c" => esc_html($categories)
                    );
                }else{
                    $homepage_json_rp[get_permalink($product->get_id())] =array(
                        "tvc_id" => esc_html($product->get_id()),
                        "tvc_i" => esc_html($product->get_sku() ? $product->get_sku() : $product->get_id()),
                        "tvc_n" => esc_html($product->get_title()),
                        "tvc_p" => esc_html($product->get_price()),
                        "tvc_c" => esc_html($categories)
                    );
                }

            }
        }
        //if product page then related product page array
        else if(is_product()){
            if(!is_array($prodpage_json_relProd) && !is_array($prodpage_json_ATC_link)){
                $prodpage_json_relProd = array();
                $prodpage_json_ATC_link = array();
            }
            // ATC link Array
            if(version_compare($woocommerce->version, "2.7", "<")){
                $prodpage_json_ATC_link[$product->add_to_cart_url()]=array("ATC-link"=>get_permalink($product->id));

                $prodpage_json_relProd[get_permalink($product->id)] = array(
                    "tvc_id" => esc_html($product->id),
                    "tvc_i" => esc_html($product->get_sku() ? $product->get_sku() : $product->id),
                    "tvc_n" => esc_html($product->get_title()),
                    "tvc_p" => esc_html($product->get_price()),
                    "tvc_c" => esc_html($categories),
                );
            }else{
                $prodpage_json_ATC_link[$product->add_to_cart_url()]=array("ATC-link"=>get_permalink($product->get_id()));

                $prodpage_json_relProd[get_permalink($product->get_id())] = array(
                    "tvc_id" => esc_html($product->get_id()),
                    "tvc_i" => esc_html($product->get_sku() ? $product->get_sku() : $product->get_id()),
                    "tvc_n" => esc_html($product->get_title()),
                    "tvc_p" => esc_html($product->get_price()),
                    "tvc_c" => esc_html($categories)

                );
            }
        }
        //category page, search page and shop page json
        else if (is_product_category() || is_search() || is_shop()) {
            if (!is_array($catpage_json) && !is_array($catpage_json_ATC_link)){
                $catpage_json=array();
                $catpage_json_ATC_link=array();
            }
            //cat page ATC array
            if(version_compare($woocommerce->version, "2.7", "<")){
                $catpage_json_ATC_link[$product->add_to_cart_url()]=array("ATC-link"=>get_permalink($product->id));

                $catpage_json[get_permalink($product->id)] =array(
                    "tvc_id" => esc_html($product->id),
                    "tvc_i" => esc_html($product->get_sku() ? $product->get_sku() : $product->id),
                    "tvc_n" => esc_html($product->get_title()),
                    "tvc_p" => esc_html($product->get_price()),
                    "tvc_c" => esc_html($categories),
                );
            }else{
                $catpage_json_ATC_link[$product->add_to_cart_url()]=array("ATC-link"=>get_permalink($product->get_id()));

                $catpage_json[get_permalink($product->get_id())] =array(
                    "tvc_id" => esc_html($product->get_id()),
                    "tvc_i" => esc_html($product->get_sku() ? $product->get_sku() : $product->get_id()),
                    "tvc_n" => esc_html($product->get_title()),
                    "tvc_p" => esc_html($product->get_price()),
                    "tvc_c" => esc_html($categories)

                );
            }
        }
    }

    /**
     * Enhanced E-commerce tracking for product impressions,clicks on Home pages
     *
     * @access public
     * @return void
     */
    function t_products_impre_clicks() {
        if ($this->disable_tracking($this->ga_eeT)) {
            return;
        }

        //get impression threshold
        $impression_threshold = $this->ga_imTh;

        //Product impression on Home Page
        global $homepage_json_fp,$homepage_json_ATC_link, $homepage_json_rp,$prodpage_json_relProd,$catpage_json,$prodpage_json_ATC_link,$catpage_json_ATC_link;
        //home page json for featured products and recent product sections
        //check if php array is empty
        if(empty($homepage_json_ATC_link)){
            $homepage_json_ATC_link=array(); //define empty array so if empty then in json will be []
        }
        if(empty($homepage_json_fp)){
            $homepage_json_fp=array(); //define empty array so if empty then in json will be []
        }
        if(empty($homepage_json_rp)){ //home page recent product array
            $homepage_json_rp=array();
        }
        if(empty($prodpage_json_relProd)){ //prod page related section array
            $prodpage_json_relProd=array();
        }
        if(empty($prodpage_json_ATC_link)){
            $prodpage_json_ATC_link=array(); //prod page ATC link json
        }
        if(empty($catpage_json)){ //category page array
            $catpage_json=array();
        }
        if(empty($catpage_json_ATC_link)){ //category page array
            $catpage_json_ATC_link=array();
        }
        //home page json
        $this->wc_version_compare("homepage_json_ATC_link=" . json_encode($homepage_json_ATC_link) . ";");
        $this->wc_version_compare("tvc_fp=" . json_encode($homepage_json_fp) . ";");
        $this->wc_version_compare("tvc_rcp=" . json_encode($homepage_json_rp) . ";");
        //product page json
        $this->wc_version_compare("tvc_rdp=" . json_encode($prodpage_json_relProd) . ";");
        $this->wc_version_compare("prodpage_json_ATC_link=" . json_encode($prodpage_json_ATC_link) . ";");
        //category page json
        $this->wc_version_compare("tvc_pgc=" . json_encode($catpage_json) . ";");
        $this->wc_version_compare("catpage_json_ATC_link=" . json_encode($catpage_json_ATC_link) . ";");
        if($this->ga_id || $this->tracking_option == "UA" || $this->tracking_option == "BOTH") {
            $hmpg_impressions_jQ = '
                var items = [];
                //set local currencies
                gtag("set", {"currency": tvc_lc});
                function t_products_impre_clicks(t_json_name,t_action){
                       t_send_threshold=0;
                       t_prod_pos=0;
                        t_json_length=Object.keys(t_json_name).length;
                            
                        for(var t_item in t_json_name) {
                    t_send_threshold++;
                    t_prod_pos++;
                    items.push({
                        "id": t_json_name[t_item].tvc_i,
                        "name": t_json_name[t_item].tvc_n,
                        "category": t_json_name[t_item].tvc_c,
                        "price": t_json_name[t_item].tvc_p,
                    });    
                            
                        if(t_json_length > ' . esc_js($impression_threshold) .' ){

                            if((t_send_threshold%' . esc_js($impression_threshold) . ')==0){
                                t_json_length=t_json_length-' . esc_js($impression_threshold) . ';
                                    gtag("event", "view_item_list", { "event_category":"Enhanced-Ecommerce",
                                         "event_label":"product_impression_"+t_action, "items":items,"non_interaction": true});
                                         items = [];
                                        }
                                        if(adsTringId != "" && ( ads_ert == 1 || ads_edrt == 1)){
                                            gtag("event","view_item_list", {
                                                "send_to":remarketing_snippet_id,
                                                "value": t_json_name[t_item].tvc_p,
                                                "items": [
                                                  {
                                                    "id": t_json_name[t_item].tvc_id, 
                                                    "google_business_vertical": "retail"
                                                  }
                                               ]
                                            });
                                        }
                            }else{
                
                               t_json_length--;
                               if(t_json_length==0){
                                       gtag("event", "view_item_list", { "event_category":"Enhanced-Ecommerce",
                                            "event_label":"product_impression_"+t_action, "items":items,"non_interaction": true});
                                            items = [];
                                        }
                                       if(adsTringId != "" && ( ads_ert == 1 || ads_edrt == 1)){
                                            gtag("event","view_item_list", {
                                                "send_to":remarketing_snippet_id,
                                                "value": t_json_name[t_item].tvc_p,
                                                "items": [
                                                  {
                                                    "id": t_json_name[t_item].tvc_id, 
                                                    "google_business_vertical": "retail"
                                                  }
                                               ]
                                            });
                                        }
                                }   
                            }
            }
                    
            //function for comparing urls in json object
            function prod_exists_in_JSON(t_url,t_json_name,t_action){
                                        if(t_json_name.hasOwnProperty(t_url)){
                                            t_call_fired=true;
                                            gtag("event", "select_content", {
                                                "event_category":"Enhanced-Ecommerce",
                                                "event_label":"product_click_"+t_action,
                                                "content_type": "product",
                                                "items": [
                                                {
                                                    "id":t_json_name[t_url].tvc_i,
                                                    "name": t_json_name[t_url].tvc_n,
                                                     "category":t_json_name[t_url].tvc_c,
                                                     "price": t_json_name[t_url].tvc_p,
                                                }
                                                ],
                                                "non_interaction": true
                                            });                    
                                       }else{
                                            t_call_fired=false;
                                        }
                                        return t_call_fired;
                                }
                    function prod_ATC_link_exists(t_url,t_ATC_json_name,t_prod_data_json,t_qty){
                        t_prod_url_key=t_ATC_json_name[t_url]["ATC-link"];
                        
                            if(t_prod_data_json.hasOwnProperty(t_prod_url_key)){
                                    t_call_fired=true;
                                // Enhanced E-commerce Add to cart clicks
                                    gtag("event", "add_to_cart", {
                                        "event_category":"Enhanced-Ecommerce",
                                        "event_label":"add_to_cart_click",
                                        "non_interaction": true,
                                        "items": [{
                                            "id" : t_prod_data_json[t_prod_url_key].tvc_i,
                                            "name":t_prod_data_json[t_prod_url_key].tvc_n,
                                            "category" : t_prod_data_json[t_prod_url_key].tvc_c,
                                            "price": t_prod_data_json[t_prod_url_key].tvc_p,
                                            "quantity" :t_qty
                                        }]
                                    });
                                    if(adsTringId != "" && ( ads_ert == 1 || ads_edrt == 1)){
                                        gtag("event","add_to_cart", {
                                            "send_to":remarketing_snippet_id,
                                            "value": t_prod_data_json[t_prod_url_key].tvc_p,
                                            "items": [
                                              {
                                                "id": t_prod_data_json[t_prod_url_key].tvc_id, 
                                                "google_business_vertical": "retail"
                                              }
                                            ]
                                        });
                                    }
                                 
                            }else{
                                       t_call_fired=false;
                            }    
                             return t_call_fired;
                     
                    }
                    
                    ';
                }
        if($this->gm_id && $this->tracking_option == "GA4") {
                    $hmpg_impressions_jQ = '
            var items = [];
            function t_products_impre_clicks(t_json_name,t_action){
               t_send_threshold=0;
               t_prod_pos=0;
               t_json_length=Object.keys(t_json_name).length;
            for(var t_item in t_json_name) {
                t_send_threshold++;
                t_prod_pos++;
                items.push({
                    "item_id": t_json_name[t_item].tvc_i,
                    "item_name": t_json_name[t_item].tvc_n,
                    "item_category": t_json_name[t_item].tvc_c,
                    "price": t_json_name[t_item].tvc_p,
                    "currency": tvc_lc
                });    
            if(t_json_length > ' . esc_js($impression_threshold) . ' ){
                        if((t_send_threshold%' . esc_js($impression_threshold) . ')==0){
                            t_json_length=t_json_length-' . esc_js($impression_threshold) . ';
                                gtag("event", "view_item_list", { 
                                    "event_category":"Enhanced-Ecommerce",
                                    "event_label":"product_impression_"+t_action, 
                                    "items":items,
                                    "non_interaction": true
                                });
                                items = [];
                            }
                        if(adsTringId != "" && ( ads_ert == 1 || ads_edrt == 1)){
                            gtag("event","view_item_list", {
                                "send_to":remarketing_snippet_id,
                                "value": t_json_name[t_item].tvc_p,
                                "items": [
                                  {
                                    "id": t_json_name[t_item].tvc_id, 
                                    "google_business_vertical": "retail"
                                  }
                               ]
                            });
                        }
                        }else{
                            t_json_length--;
                       if(t_json_length==0){
                               gtag("event", "view_item_list", { 
                                   "event_category":"Enhanced-Ecommerce",
                                   "event_label":"product_impression_"+t_action, 
                                   "items":items,
                                   "non_interaction": true
                               });
                               items = [];
                           }
                           if(adsTringId != "" && ( ads_ert == 1 || ads_edrt == 1)){
                            gtag("event","view_item_list", {
                                "send_to":remarketing_snippet_id,
                                "value": t_json_name[t_item].tvc_p,
                                "items": [
                                  {
                                    "id": t_json_name[t_item].tvc_id, 
                                    "google_business_vertical": "retail"
                                  }
                               ]
                            });
                        }
                       }   
                    }
            }
                
        //function for comparing urls in json object
        function prod_exists_in_JSON(t_url,t_json_name,t_action){
                if(t_json_name.hasOwnProperty(t_url)){
                    t_call_fired=true;
                    gtag("event", "select_item", {
                        "event_category":"Enhanced-Ecommerce",
                        "event_label":"product_click_"+t_action,
                        "items": [
                            {
                                "item_id":t_json_name[t_url].tvc_i,
                                "item_name": t_json_name[t_url].tvc_n,
                                "item_category":t_json_name[t_url].tvc_c,
                                "price": t_json_name[t_url].tvc_p,
                                "currency": tvc_lc
                            }
                        ],
                        "non_interaction": true
                    });      
                                  
               }else{
                    t_call_fired=false;
               }
               return t_call_fired;
            }
                function prod_ATC_link_exists(t_url,t_ATC_json_name,t_prod_data_json,t_qty){
                    t_prod_url_key=t_ATC_json_name[t_url]["ATC-link"];
                        if(t_prod_data_json.hasOwnProperty(t_prod_url_key)){
                                t_call_fired=true;
                            // Enhanced E-commerce Add to cart clicks
                                gtag("event", "add_to_cart", {
                                    "event_category":"Enhanced-Ecommerce",
                                    "event_label":"add_to_cart_click",
                                    "non_interaction": true,
                                    "items": [{
                                        "item_id" : t_prod_data_json[t_prod_url_key].tvc_i,
                                        "item_name":t_prod_data_json[t_prod_url_key].tvc_n,
                                        "item_category" : t_prod_data_json[t_prod_url_key].tvc_c,
                                        "price": t_prod_data_json[t_prod_url_key].tvc_p,
                                        "currency": tvc_lc,
                                        "quantity" :t_qty
                                    }]
                                });
                                
                            if(adsTringId != "" && ( ads_ert == 1 || ads_edrt == 1)){
                                gtag("event","add_to_cart", {
                                    "send_to":remarketing_snippet_id,
                                    "value": t_prod_data_json[t_prod_url_key].tvc_p,
                                    "items": [
                                      {
                                        "id": t_prod_data_json[t_prod_url_key].tvc_id, 
                                        "google_business_vertical": "retail"
                                      }
                                   ]
                                });
                            }
                        }else{
                            t_call_fired=false;
                        }    
                         return t_call_fired;
                }
                ';
                }
        if(is_home() || is_front_page()){
            $hmpg_impressions_jQ .='
                if(tvc_fp.length !== 0){
                    t_products_impre_clicks(tvc_fp,"fp");       
                }
                if(tvc_rcp.length !== 0){
                    t_products_impre_clicks(tvc_rcp,"rp");    
                }
                jQuery("a:not([href*=add-to-cart],.product_type_variable, .product_type_grouped)").on("click",function(){
            t_url=jQuery(this).attr("href");
                        //home page call for click
                        t_call_fired=prod_exists_in_JSON(t_url,tvc_fp,"fp");
                        if(!t_call_fired){
                            prod_exists_in_JSON(t_url,tvc_rcp,"rp");
                        }    
                });
                //ATC click
                jQuery("a[href*=add-to-cart]").on("click",function(){
            t_url=jQuery(this).attr("href");
                        t_qty=$(this).parent().find("input[name=quantity]").val();
                             //default quantity 1 if quantity box is not there             
                            if(t_qty=="" || t_qty===undefined){
                                t_qty="1";
                            }
                        t_call_fired=prod_ATC_link_exists(t_url,homepage_json_ATC_link,tvc_fp,t_qty);
                        if(!t_call_fired){
                            prod_ATC_link_exists(t_url,homepage_json_ATC_link,tvc_rcp,t_qty);
                        }
                    });   
             
                ';
        }else if(is_search()){
            $hmpg_impressions_jQ .='
                //search page json
                if(tvc_pgc.length !== 0){
                    t_products_impre_clicks(tvc_pgc,"srch");   
                }
                //search page prod click
                jQuery("a:not(.product_type_variable, .product_type_grouped)").on("click",function(){
                    t_url=jQuery(this).attr("href");
                     //cat page prod call for click
                     prod_exists_in_JSON(t_url,tvc_pgc,"srch");
                     });
                
            ';
        }else if (is_product()) {
            //product page releted products
            $hmpg_impressions_jQ .='
                if(tvc_rdp.length !== 0){
                    t_products_impre_clicks(tvc_rdp,"rdp");  
                }          
                //product click - image and product name
                jQuery("a:not(.product_type_variable, .product_type_grouped)").on("click",function(){
                    t_url=jQuery(this).attr("href");
                     //prod page related call for click
                     prod_exists_in_JSON(t_url,tvc_rdp,"rdp");
                });  
                //Prod ATC link click in related product section
                jQuery("a[href*=add-to-cart]").on("click",function(){
            t_url=jQuery(this).attr("href");
                        t_qty=$(this).parent().find("input[name=quantity]").val();
                             //default quantity 1 if quantity box is not there             
                            if(t_qty=="" || t_qty===undefined){
                                t_qty="1";
                            }
                prod_ATC_link_exists(t_url,prodpage_json_ATC_link,tvc_rdp,t_qty);
                });   
            ';
        }else if (is_product_category()) {
            $hmpg_impressions_jQ .='
                //category page json
                if(tvc_pgc.length !== 0){
                    t_products_impre_clicks(tvc_pgc,"cp");  
                }
               //Prod category ATC link click in related product section
                jQuery("a:not(.product_type_variable, .product_type_grouped)").on("click",function(){
                     t_url=jQuery(this).attr("href");
                     //cat page prod call for click
                     prod_exists_in_JSON(t_url,tvc_pgc,"cp");
                     });
               
        ';
        }else if(is_shop()){
            $hmpg_impressions_jQ .='
                //shop page json
                if(tvc_pgc.length !== 0){
                    t_products_impre_clicks(tvc_pgc,"sp");  
                }
                //shop page prod click
                jQuery("a:not(.product_type_variable, .product_type_grouped)").on("click",function(){
                    t_url=jQuery(this).attr("href");
                     //cat page prod call for click
                     prod_exists_in_JSON(t_url,tvc_pgc,"sp");
                     });
                
                     
        ';
        }
        //common ATC link for Category page , Shop Page and Search Page
        if(is_product_category() || is_shop() || is_search()){
            $hmpg_impressions_jQ .='
                     //ATC link click
                jQuery("a[href*=add-to-cart]").on("click",function(){
            t_url=jQuery(this).attr("href");
                        t_qty=$(this).parent().find("input[name=quantity]").val();
                             //default quantity 1 if quantity box is not there             
                            if(t_qty=="" || t_qty===undefined){
                                t_qty="1";
                            }
                       prod_ATC_link_exists(t_url,catpage_json_ATC_link,tvc_pgc,t_qty);
                    });      
                    ';
        }
        //on home page, product page , category page
        if (is_home() || is_front_page() || is_product() || is_product_category() || is_search() || is_shop()){
            $this->wc_version_compare($hmpg_impressions_jQ);
        }
    }

    /**
     * Enhanced E-commerce tracking for remove from cart
     *
     * @access public
     * @return void
     */
    public function remove_cart_tracking() {
        if ($this->disable_tracking($this->ga_eeT)) {
            return;
        }
        global $woocommerce;
        $cartpage_prod_array_main = array();
        foreach ($woocommerce->cart->cart_contents as $key => $item) {
            //Version compare
            if (version_compare($woocommerce->version, "2.7", "<")) {
                $prod_meta = get_product($item["product_id"]);
            } else {
                $prod_meta = wc_get_product($item["product_id"]);
            }
            if (version_compare($woocommerce->version, "3.3", "<")) {
                $cart_remove_link=html_entity_decode($woocommerce->cart->get_remove_url($key));
            } else {
                $cart_remove_link=html_entity_decode(wc_get_cart_remove_url($key));
            }
            $category = get_the_terms($item["product_id"], "product_cat");
            $categories = "";
            if ($category) {
                foreach ($category as $term) {
                    $categories.=$term->name . ",";
                }
            }
            //remove last comma(,) if multiple categories are there
            $categories = rtrim($categories, ",");
            if(version_compare($woocommerce->version, "2.7", "<")){
                $cartpage_prod_array_main[$cart_remove_link] =array(
                    "tvc_id" => esc_html($prod_meta->ID),
                    "tvc_i" => esc_html($prod_meta->get_sku() ? $prod_meta->get_sku() : $prod_meta->ID),
                    "tvc_n" => html_entity_decode($prod_meta->get_title()),
                    "tvc_p" => esc_html($prod_meta->get_price()),
                    "tvc_c" => esc_html($categories),
                    "tvc_q"=>$woocommerce->cart->cart_contents[$key]["quantity"]
                );
            }else{
                $cartpage_prod_array_main[$cart_remove_link] =array(
                    "tvc_id" => esc_html($prod_meta->get_id()),
                    "tvc_i" => esc_html($prod_meta->get_sku() ? $prod_meta->get_sku() : $prod_meta->get_id()),
                    "tvc_n" => html_entity_decode($prod_meta->get_title()),
                    "tvc_p" => esc_html($prod_meta->get_price()),
                    "tvc_c" => esc_html($categories),
                    "tvc_q"=>$woocommerce->cart->cart_contents[$key]["quantity"]
                );
            }
        }

        //Cart Page item Array to Json
        $this->wc_version_compare("tvc_cc=" . json_encode($cartpage_prod_array_main) . ";");
        if($this->ga_id || $this->tracking_option == "UA" || $this->tracking_option == "BOTH") {
            $code = '
                //set local currencies
                gtag("set", {"currency": tvc_lc});
            $("a[href*=\"?remove_item\"]").click(function(){
                t_url=jQuery(this).attr("href");
                        gtag("event", "remove_from_cart", {
                            "event_category":"Enhanced-Ecommerce",
                            "event_label":"remove_from_cart_click",
                            "items": [{
                                "id":tvc_cc[t_url].tvc_i,
                                "name": tvc_cc[t_url].tvc_n,
                                "category":tvc_cc[t_url].tvc_c,
                                "price": tvc_cc[t_url].tvc_p,
                                "quantity": tvc_cc[t_url].tvc_q
                            }],
                            "non_interaction": true
                        });
                  });
                  
                ';
            //check woocommerce version
            $this->wc_version_compare($code);
        }

        if($this->gm_id && $this->tracking_option == "GA4") {
             $code = '
            $("a[href*=\"?remove_item\"]").click(function(){
                t_url=jQuery(this).attr("href");
                        gtag("event", "remove_from_cart", {
                            "event_category":"Enhanced-Ecommerce",
                            "event_label":"remove_from_cart_click",
                            "currency": tvc_lc,
                            "items": [{
                                "item_id":tvc_cc[t_url].tvc_i,
                                "item_name": tvc_cc[t_url].tvc_n,
                                "item_category":tvc_cc[t_url].tvc_c,
                                "price": tvc_cc[t_url].tvc_p,
                                "currency": tvc_lc,
                                "quantity": tvc_cc[t_url].tvc_q
                            }],
                            "non_interaction": true
                        });
                  });
                  
                ';
                //check woocommerce version
                $this->wc_version_compare($code);
        }
    }

    /**
     * Enhanced E-commerce tracking checkout step 1
     *
     * @access public
     * @return void
     */
    public function checkout_step_1_tracking() {
        if ($this->disable_tracking($this->ga_eeT)) {
            return;
        }
        //call fn to make json
        $this->get_ordered_items();
        if($this->ga_id || $this->tracking_option == "UA" || $this->tracking_option == "BOTH") {
            $code= '
                    var items = [];
                    gtag("set", {"currency": tvc_lc});
                    for(var t_item in tvc_ch){
                        items.push({
                            "id": tvc_ch[t_item].tvc_i,
                            "name": tvc_ch[t_item].tvc_n,
                            "category": tvc_ch[t_item].tvc_c,
                            "attributes": tvc_ch[t_item].tvc_attr,
                            "price": tvc_ch[t_item].tvc_p,
                            "quantity": tvc_ch[t_item].tvc_q
                        });
                        }';

            $code_step_1 = $code . 'gtag("event", "begin_checkout", {"event_category":"Enhanced-Ecommerce",
                            "event_label":"checkout_step_1","items":items,"non_interaction": true });';

            //check woocommerce version and add code
            $this->wc_version_compare($code_step_1);
        }

        if( $this->gm_id && $this->tracking_option == "GA4") {
            $code = '
                var items = [];
                for(var t_item in tvc_ch){
                    items.push({
                        "item_id": tvc_ch[t_item].tvc_i,
                        "item_name": tvc_ch[t_item].tvc_n,
                        "item_category": tvc_ch[t_item].tvc_c,
                        "item_variant": tvc_ch[t_item].tvc_attr,
                        "price": tvc_ch[t_item].tvc_p,
                        "quantity": tvc_ch[t_item].tvc_q
                    });
                    }';

            $code_step_1 = $code . 'gtag("event", "begin_checkout", {
                "event_category":"Enhanced-Ecommerce",
                "event_label":"checkout_step_1",
                "items":items,
                "non_interaction": true
            });';

            //check woocommerce version and add code
            $this->wc_version_compare($code_step_1);
        }
    }

    /**
     * Enhanced E-commerce tracking checkout step 2
     *
     * @access public
     * @return void
     */
    public function checkout_step_2_tracking() {
        if ($this->disable_tracking($this->ga_eeT)) {
            return;
        }
        if($this->ga_id || $this->tracking_option == "UA" || $this->tracking_option == "BOTH" || $this->gm_id) {
            $code= '
                   var items = [];
                    gtag("set", {"currency": tvc_lc});
                    for(var t_item in tvc_ch){
                        items.push({
                            "id": tvc_ch[t_item].tvc_i,
                            "name": tvc_ch[t_item].tvc_n,
                            "category": tvc_ch[t_item].tvc_c,
                            "attributes": tvc_ch[t_item].tvc_attr,
                            "price": tvc_ch[t_item].tvc_p,
                            "quantity": tvc_ch[t_item].tvc_q
                        });
                        }';

            $code_step_2 = $code . 'gtag("event", "checkout_progress", {"checkout_step": 2,"event_category":"Enhanced-Ecommerce",
                            "event_label":"checkout_step_2","items":items,"non_interaction": true });';

            //if logged in and first name is filled - Guest Check out
            if (is_user_logged_in()) {
                $step2_onFocus = 't_tracked_focus=0;  if(t_tracked_focus===0){' . $code_step_2 . ' t_tracked_focus++;}';
            } else {
                //first name on focus call fire
                $step2_onFocus = 't_tracked_focus=0; jQuery("input[name=billing_first_name]").on("focus",function(){ if(t_tracked_focus===0){' . $code_step_2 . ' t_tracked_focus++;}});';
            }
            //check woocommerce version and add code
            $this->wc_version_compare($step2_onFocus);
        }
    }

    /**
     * Enhanced E-commerce tracking checkout step 3
     *
     * @access public
     * @return void
     */
    public function checkout_step_3_tracking() {

        if ($this->disable_tracking($this->ga_eeT)) {
            return;
        }
        if($this->ga_id || $this->tracking_option == "UA" || $this->tracking_option == "BOTH" || $this->gm_id) {
            $code= '
             var items = [];
                for(var t_item in tvc_ch){
                        items.push({
                            "id": tvc_ch[t_item].tvc_i,
                            "name": tvc_ch[t_item].tvc_n,
                            "category": tvc_ch[t_item].tvc_c,
                            "attributes": tvc_ch[t_item].tvc_attr,
                            "price": tvc_ch[t_item].tvc_p,
                            "quantity": tvc_ch[t_item].tvc_q
                        });
                        }';

            //check if guest check out is enabled or not
            $step_2_on_proceed_to_pay = (!is_user_logged_in() && !$this->ga_gCkout ) || (!is_user_logged_in() && $this->ga_gCkout && $this->ga_gUser);

            $code_step_3 = $code . 'gtag("event", "checkout_progress", {"checkout_step": 3,"event_category":"Enhanced-Ecommerce",
                            "event_label":"checkout_step_3","items":items,"non_interaction": true });';

            $inline_js = 't_track_clk=0; jQuery(document).on("click","#place_order",function(e){ if(t_track_clk===0){';
            if ($step_2_on_proceed_to_pay) {
                if (isset($code_step_2))
                    $inline_js .= $code_step_2;
            }
            $inline_js .= $code_step_3;
            $inline_js .= "t_track_clk++; }});";

            //check woocommerce version and add code
            $this->wc_version_compare($inline_js);
        }
    }

    /**
     * Get oredered Items for check out page.
     *
     * @access public
     * @return void
     */
    public function get_ordered_items() {
        global $woocommerce;
        $code = "";
        //get all items added into the cart
        foreach ($woocommerce->cart->cart_contents as $item) {
            //Version Compare
            if ( version_compare($woocommerce->version, "2.7", "<")) {
                $p = get_product($item["product_id"]);
            } else {
                $p = wc_get_product($item["product_id"]);
            }

            $category = get_the_terms($item["product_id"], "product_cat");
            $categories = "";
            if ($category) {
                foreach ($category as $term) {
                    $categories.=$term->name . ",";
                }
            }
            //remove last comma(,) if multiple categories are there
            $categories = rtrim($categories, ",");
            if(version_compare($woocommerce->version, "2.7", "<")){
                $chkout_json[get_permalink($p->ID)] = array(
                    "tvc_id" => esc_html($p->ID),
                    "tvc_i" => esc_js($p->get_sku() ? $p->get_sku() : $p->ID),
                    "tvc_n" => html_entity_decode($p->get_title()),
                    "tvc_p" => esc_js($p->get_price()),
                    "tvc_c" => $categories,
                    "tvc_q" => esc_js($item["quantity"]),
                    "isfeatured"=>$p->is_featured()
                );
            }else{
                $chkout_json[get_permalink($p->get_id())] = array(
                    "tvc_id" => esc_html($p->get_id()),
                    "tvc_i" => esc_js($p->get_sku() ? $p->get_sku() : $p->get_id()),
                    "tvc_n" => html_entity_decode($p->get_title()),
                    "tvc_p" => esc_js($p->get_price()),
                    "tvc_c" => $categories,
                    "tvc_q" => esc_js($item["quantity"]),
                    "isfeatured"=>$p->is_featured()
                );
            }
        }
        //return $code;
        //make product data json on check out page
        $this->wc_version_compare("tvc_ch=" . json_encode($chkout_json) . ";");
    }
}

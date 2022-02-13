<?php
if ( ! class_exists( 'TVCProductSyncHelper' ) ) {
	class TVCProductSyncHelper {
		protected $merchantId;
		protected $accountId;
		protected $currentCustomerId;
		protected $subscriptionId;
		protected $country;
		protected $site_url;
		protected $category_wrapper_obj;
		protected $TVC_Admin_Helper;
		protected $TVC_Admin_DB_Helper;
		public function __construct(){
			$this->includes();
			$this->add_table_in_db();
			$this->TVC_Admin_Helper = new TVC_Admin_Helper();
			$this->TVC_Admin_DB_Helper = new TVC_Admin_DB_Helper(); 
			$this->category_wrapper_obj = new Tatvic_Category_Wrapper();
			$this->merchantId = $this->TVC_Admin_Helper->get_merchantId();
			$this->accountId = $this->TVC_Admin_Helper->get_main_merchantId();
			$this->currentCustomerId = $this->TVC_Admin_Helper->get_currentCustomerId();
			$this->subscriptionId = $this->TVC_Admin_Helper->get_subscriptionId();
			$this->country = $this->TVC_Admin_Helper->get_woo_country();
			$this->site_url = "admin.php?page=conversios-google-shopping-feed&tab=";
			add_action('admin_init', array($this,'add_table_in_db'));
		}
		public function includes(){
		  if (!class_exists('Tatvic_Category_Wrapper')) {
		    require_once(__DIR__ . '/tatvic-category-wrapper.php');
		  }		  
		}
     /*
     * careate table batch wise for product sync
     */
		public function add_table_in_db(){     
      global $wpdb;
      $tablename = esc_sql($wpdb->prefix ."ee_product_sync_profile");
      $query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $tablename ) );   
      if ( $wpdb->get_var( $query ) === $tablename ) {          
      }else{     
        $sql_create = "CREATE TABLE `$tablename` ( `id` BIGINT(20) NOT NULL AUTO_INCREMENT , `profile_title` VARCHAR(100) NULL , `g_cat_id` INT(10) NULL , `g_attribute_mapping` LONGTEXT NOT NULL , `update_date` DATE NOT NULL , `status` INT(1) NOT NULL DEFAULT '1', PRIMARY KEY (`id`) );";         
        if(maybe_create_table( $tablename, $sql_create )){          
        }
      }

      $tablename = esc_sql($wpdb->prefix ."ee_prouct_pre_sync_data");
      $query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $tablename ) );   
      if ( $wpdb->get_var( $query ) === $tablename ) {          
      }else{     
        $sql_create = "CREATE TABLE `$tablename` ( `id` BIGINT(20) NOT NULL AUTO_INCREMENT , `w_product_id` BIGINT(20) NOT NULL , `w_cat_id` INT(10) NOT NULL , `g_cat_id` INT(10) NOT NULL , `product_sync_profile_id` INT(10) NOT NULL , `update_date` DATE NOT NULL , `status` INT(1) NOT NULL DEFAULT '0', PRIMARY KEY (`id`) );";         
        if(maybe_create_table( $tablename, $sql_create )){
        }
      }
    }

    public function get_product_category($product_id){
      $output    = [];
      $terms_ids = wp_get_post_terms( $product_id, 'product_cat', array('fields' => 'ids') );   
      // Loop though terms ids (product categories)
      foreach( $terms_ids as $term_id ) {
          $term_names = [];
          // Loop through product category ancestors
          foreach( get_ancestors( $term_id, 'product_cat') as $ancestor_id ){
            $term_names[] = get_term( $ancestor_id, 'product_cat')->name;
            if(isset($output[$ancestor_id]) && $output[$ancestor_id] != ""){
              unset($output[$ancestor_id]);
            }
          }
          $term_names[] = get_term( $term_id, 'product_cat' )->name;
          // Add the formatted ancestors with the product category to main array
          $output[$term_id] = implode(' > ', $term_names);
      }
      $output = array_values($output);
      return $output;
    }
    /*
     * careate products object for product sync
     */
    public function tvc_get_map_product_attribute($products, $tvc_currency, $merchantId, $product_batch_size = 100){
      if(!empty($products)){
        $items = [];
        $skipProducts = [];
        $product_ids = [];
        $batchId = time();
        $sync_profile = $this->TVC_Admin_DB_Helper->tvc_get_results('ee_product_sync_profile');
        // set profile id in array key
        $sync_profile_data = array();
        if(!empty($sync_profile)){
        	foreach ($sync_profile as $key => $value) {
        		$sync_profile_data[$value->id]= $value; 
        	}
        }
        if(empty($sync_profile_data)){
        	return array("error"=>true,"message"=>esc_html__("No product sync profiles find.","conversios"));
        }
        if(empty($products)){
        	return array("error"=>true,"message"=>esc_html__("Products not found.","conversios"));
        }
        $products_sync = 0;
        foreach ($products as $postkey => $postvalue) {
          $product_ids[] = $postvalue->w_product_id;
          $postmeta = [];
          $postmeta = $this->TVC_Admin_Helper->tvc_get_post_meta($postvalue->w_product_id);
          $prd = wc_get_product($postvalue->w_product_id);
          $postObj = (object) array_merge((array) get_post($postvalue->w_product_id), (array) $postmeta);
          
          $product = array(
            'offer_id'=>sanitize_text_field($postvalue->w_product_id),
            'channel'=>'online',
            'link'=> esc_url_raw(get_permalink($postvalue->w_product_id)),
            'google_product_category'=>sanitize_text_field($postvalue->g_cat_id)
          );

          $temp_product=array();
          $fixed_att_select_list = array("gender", "age_group", "shipping", "tax", "content_language", "target_country", "condition");
          $formArray = "";
          if(isset($sync_profile_data[$postvalue->product_sync_profile_id]) && $sync_profile_data[$postvalue->product_sync_profile_id]->g_attribute_mapping ){
          	$g_attribute_mapping = $sync_profile_data[$postvalue->product_sync_profile_id]->g_attribute_mapping;
          	$formArray = json_decode($g_attribute_mapping, true);
          }

          if(empty($formArray)){
        		return array("error"=>true,"message"=>esc_html__("Product sync profile not found.","conversios"));
        	}
          //$formArray = json_decode($postvalue->g_attribute_mapping, true);
          foreach ($fixed_att_select_list as $fixed_key) {
            if(isset($formArray[$fixed_key]) && $formArray[$fixed_key] != "" ){
              if($fixed_key == "shipping" && $formArray[$fixed_key] != ""){
                $temp_product[$fixed_key]['price']['value'] = sanitize_text_field($formArray[$fixed_key]);
                $temp_product[$fixed_key]['price']['currency'] = sanitize_text_field($tvc_currency);
                $temp_product[$fixed_key]['country'] = sanitize_text_field($formArray['target_country']);        
              }else if($fixed_key == "tax" && $formArray[$fixed_key] != ""){                
                $temp_product['taxes']['rate'] = sanitize_text_field($formArray[$fixed_key]);
                $temp_product['taxes']['country'] = sanitize_text_field($formArray['target_country']);
              }else if( $formArray[$fixed_key] != ""){
                $temp_product[$fixed_key] = sanitize_text_field($formArray[$fixed_key]);
              }          
            }
            unset($formArray[$fixed_key]);
          }

          $product = array_merge($temp_product,$product);

          if($prd->get_type() == "variable"){            
            /*$variation_attributes = $prd->get_variation_attributes();*/            
            //$p_variations = $prd->get_available_variations(); 
            $p_variations = $prd->get_children();                
            if(!empty($p_variations)){                    
              foreach ($p_variations as $v_key => $variation_id) {
                $variation = wc_get_product( $variation_id );
                if(empty($variation)){
                  continue;
                }
                $variation_description = wc_format_content( $variation->get_description() );
                unset($product['customAttributes']);
                $postmeta_var = (object)$this->TVC_Admin_Helper->tvc_get_post_meta($variation_id);
                $formArray_val = $formArray['title'];
                $product['title'] = (isset($postObj->$formArray_val))?sanitize_text_field($postObj->$formArray_val):get_the_title($postvalue->w_product_id);
                $tvc_temp_desc_key = $formArray['description'];
                $product['description'] = ( $variation_description != "")?sanitize_text_field($variation_description):sanitize_text_field($postObj->$tvc_temp_desc_key);
                $product['offer_id'] = $variation_id;
                $product['id'] = $variation_id;
                $product['item_group_id'] = $postvalue->w_product_id;
                $productTypes = $this->get_product_category($postvalue->w_product_id);
                if(!empty($productTypes)){
                  $product['productTypes'] = $productTypes;
                }
                $image_id = $variation->get_image_id();
                $product['image_link'] = esc_url_raw(wp_get_attachment_image_url($image_id, 'full'));
                $variation_attributes = $variation->get_variation_attributes();
                
                if(!empty($variation_attributes) ){
                  foreach($variation_attributes as $va_key => $va_value ){
                    $va_key = str_replace("_", " ", $va_key);                  
                    if (strpos($va_key, 'color') !== false) {
                      $product['color'] = $va_value;
                    }else if (strpos($va_key, 'size') !== false) {
                      $product['sizes'] = $va_value;
                    }else{
                      $va_key = str_replace("attribute", "", $va_key);
                      $product['customAttributes'][] = array("name"=>$va_key, "value" => $va_value);
                    }
                  }
                }
                
                foreach($formArray as $key => $value){
                  if($key == 'price'){
                    if(isset($postmeta_var->$value) && $postmeta_var->$value > 0){
                      $product[$key]['value'] = $postmeta_var->$value;
                    }else if(isset($postmeta_var->_regular_price) && $postmeta_var->_regular_price && $postmeta_var->_regular_price >0 ){
                      $product[$key]['value'] = $postmeta_var->_regular_price;
                    }else if(isset($postmeta_var->_price) && $postmeta_var->_price && $postmeta_var->_price >0 ){
                      $product[$key]['value'] = $postmeta_var->_price;
                    }else if(isset($postmeta_var->_sale_price) && $postmeta_var->_sale_price && $postmeta_var->_sale_price >0 ){
                      $product[$key]['value'] = $postmeta_var->_sale_price;
                    }else{ 
                      unset($product[$key]);
                    }
                    if(isset($product[$key]['value']) && $product[$key]['value'] >0){
                      $product[$key]['currency'] = sanitize_text_field($tvc_currency);
                    }else{
                      $skipProducts[$postmeta_var->ID] = $postmeta_var;
                    }
                  }else if($key == 'sale_price'){
                    if(isset($postmeta_var->$value) && $postmeta_var->$value > 0){
                      $product[$key]['value'] = $postmeta_var->$value;
                    }else if(isset($postmeta_var->_sale_price) && $postmeta_var->_sale_price && $postmeta_var->_sale_price >0 ){
                      $product[$key]['value'] = $postmeta_var->_sale_price;
                    }else{ 
                      unset($product[$key]);
                    }
                    if(isset($product[$key]['value']) && $product[$key]['value'] >0){
                      $product[$key]['currency'] = sanitize_text_field($tvc_currency);
                    }                                                
                  }else if($key == 'availability'){
                    $tvc_find = array("instock","outofstock","onbackorder");
                    $tvc_replace = array("in stock","out of stock","preorder");
                    if(isset($postmeta_var->$value) && $postmeta_var->$value != ""){
                      $stock_status = $postmeta_var->$value;
                      $stock_status = str_replace($tvc_find,$tvc_replace,$stock_status);
                      $product[$key] = sanitize_text_field($stock_status);
                    }else{
                      $stock_status = $postmeta_var->_stock_status;
                      $stock_status = str_replace($tvc_find,$tvc_replace,$stock_status);
                      $product[$key] = sanitize_text_field($stock_status);
                    }
                  }else if(isset($postmeta_var->$value) && $postmeta_var->$value != ""){
                    $product[$key] = sanitize_text_field($postmeta_var->$value);             
                  }else if(in_array($key, array("brand")) ){ //list of cutom option added
                    $yith_product_brand = $this->TVC_Admin_Helper->add_additional_option_val_in_map_product_attribute($key, $postvalue->w_product_id);
                    if($yith_product_brand != ""){
                      $product[$key] = sanitize_text_field($yith_product_brand);
                    }
                  }
                }
                $item = [
                  'merchant_id' => sanitize_text_field($merchantId),
                  'batch_id' => sanitize_text_field(++$batchId),
                  'method' => 'insert',
                  'product' => $product
                ];
                $items[] = $item;
              }
            }
            
          }else{
            //simpleproduct: 
            $image_id = $prd->get_image_id();
            $product['image_link'] = esc_url_raw(wp_get_attachment_image_url($image_id, 'full'));
            $productTypes = $this->get_product_category($postvalue->w_product_id);
            if(!empty($productTypes)){
              $product['productTypes'] = $productTypes;
            }
            //$product['productTypes'] = "Apparel & Accessories";   
            foreach($formArray as $key => $value){
              if($key == 'price'){
                if(isset($postObj->$value) && $postObj->$value > 0){
                  $product[$key]['value'] = $postObj->$value;
                }else if(isset($postObj->_regular_price) && $postObj->_regular_price && $postObj->_regular_price >0 ){
                  $product[$key]['value'] = $postObj->_regular_price;
                }else if(isset($postObj->_price) && $postObj->_price && $postObj->_price >0 ){
                  $product[$key]['value'] = $postObj->_price;
                }else if(isset($postObj->_sale_price) && $postObj->_sale_price && $postObj->_sale_price >0 ){
                  $product[$key]['value'] = $postObj->_sale_price;
                }
                if(isset($product[$key]['value']) && $product[$key]['value'] >0){
                  $product[$key]['currency'] = sanitize_text_field($tvc_currency);
                }else{
                  $skipProducts[$postObj->ID] = $postObj;
                }
              }else if($key == 'sale_price'){
                if(isset($postObj->$value) && $postObj->$value > 0){
                  $product[$key]['value'] = $postObj->$value;
                }else if(isset($postObj->_sale_price) && $postObj->_sale_price && $postObj->_sale_price >0 ){
                  $product[$key]['value'] = $postObj->_sale_price;
                }
                if(isset($product[$key]['value']) && $product[$key]['value'] >0){
                  $product[$key]['currency'] = sanitize_text_field($tvc_currency);
                }                  
              }else if($key == 'availability'){
                $tvc_find = array("instock","outofstock","onbackorder");
                $tvc_replace = array("in stock","out of stock","preorder");
                if(isset($postObj->$value) && $postObj->$value != ""){
                  $stock_status = $postObj->$value;
                  $stock_status = str_replace($tvc_find,$tvc_replace,$stock_status);
                  $product[$key] = sanitize_text_field($stock_status);
                }else{
                  $stock_status = $postObj->_stock_status;
                  $stock_status = str_replace($tvc_find,$tvc_replace,$stock_status);
                  $product[$key] = sanitize_text_field($stock_status);
                }
              }else if(isset($postObj->$value) && $postObj->$value != ""){
                $product[$key] = $postObj->$value;
              }else if(in_array($key, array("brand")) ){ //list of cutom option added
                $yith_product_brand = $this->TVC_Admin_Helper->add_additional_option_val_in_map_product_attribute($key, $postvalue->w_product_id);
                if($yith_product_brand != ""){
                  $product[$key] = sanitize_text_field($yith_product_brand);
                }
              }
            }
            $item = [
              'merchant_id' => sanitize_text_field($merchantId),
              'batch_id' => sanitize_text_field(++$batchId),
              'method' => 'insert',
              'product' => $product
            ];            
            $items[] = $item;
          }

          $products_sync++;
          if(count($items) >= $product_batch_size){
            return array('error'=>false, 'items' => $items, 'skip_products'=> $skipProducts, 'product_ids'=>$product_ids, 'last_sync_product_id'=> $postvalue->id, 'products_sync' => $products_sync);
          }
        }
        return array('error'=>false, 'items' => $items, 'skip_products'=> $skipProducts, 'product_ids'=>$product_ids, 'last_sync_product_id'=> $postvalue->id, 'products_sync' => $products_sync);        
      }
    }
    /*
     * batch wise sync product, its call from ajax fuction
     */
    public function call_batch_wise_sync_product($last_sync_product_id = null, $product_batch_size = 100){
    	if (!class_exists('CustomApi')) {
        require_once(ENHANCAD_PLUGIN_DIR . 'includes/setup/CustomApi.php');
      }
      $CustomApi = new CustomApi();
    	$product_count = $this->TVC_Admin_DB_Helper->tvc_row_count('ee_prouct_pre_sync_data');
      //$count = 0;
      $pre_last_sync_product_id = sanitize_text_field($last_sync_product_id);
      if( $product_count > 0 ){  
        $tvc_currency =  sanitize_text_field($this->TVC_Admin_Helper->get_woo_currency()); 
        $merchantId = sanitize_text_field($this->merchantId);
        $customerId = sanitize_text_field($this->currentCustomerId);
        $accountId = sanitize_text_field($this->accountId);
        $subscriptionId =  sanitize_text_field($this->subscriptionId);  
        $last_sync_product_id =sanitize_text_field(( $last_sync_product_id > 0)?$last_sync_product_id:0);
        global $wpdb;
        $tablename = $wpdb->prefix .'ee_prouct_pre_sync_data';
        
        $last_sync_product_id = esc_sql(intval($last_sync_product_id));
        $product_batch_size = esc_sql(intval($product_batch_size));
        $products = $wpdb->get_results( $wpdb->prepare("select * from  `$tablename` where `id` > %d LIMIT %d", $last_sync_product_id, $product_batch_size), OBJECT); 
        $entries = [];       
        if(!empty($products)){
          $p_map_attribute = $this->tvc_get_map_product_attribute($products, $tvc_currency, $merchantId, $product_batch_size);
          if(!empty($p_map_attribute) && isset($p_map_attribute['items']) && !empty($p_map_attribute['items'])){
            // call product sync API
            $data = [
              'merchant_id' => sanitize_text_field($accountId),
              'account_id' => sanitize_text_field($merchantId),
              'subscription_id' => sanitize_text_field($subscriptionId),
              'entries' => $p_map_attribute['items']
            ];
            $response = $CustomApi->products_sync($data);

            //$last_sync_product_id =end($products)->id;
            $last_sync_product_id = $p_map_attribute['last_sync_product_id'];
            if($response->error== false){ 
            	//"data"=> $p_map_attribute['items']
            	//$products_sync =count($products);
              $products_sync = $p_map_attribute['products_sync'];
            	return array('error'=> false, 'products_sync' => $products_sync, 'skip_products' => $p_map_attribute['skip_products'], 'last_sync_product_id' => $last_sync_product_id, "products" => $products, "p_map_attribute" => $p_map_attribute );
            }else{
            	return array('error'=> true, 'message' => esc_attr($response->message) , "products" => $products, "p_map_attribute" => $p_map_attribute);
            }          
            // End call product sync API
            $sync_product_ids = (isset($p_map_attribute['product_ids']))?$p_map_attribute['product_ids']:"";
          }else if(!empty($p_map_attribute['message'])){
          	return array('error'=> true, 'message' => esc_attr($p_map_attribute['message']) );
          }       
        }
      }

    }
		public function wooCommerceAttributes() {
		  global $wpdb;
		  $tve_table_prefix = $wpdb->prefix;
		  $column1 = json_decode(json_encode($this->TVC_Admin_Helper->getTableColumns($tve_table_prefix.'posts')), true);
		  $column2 = json_decode(json_encode($this->TVC_Admin_Helper->getTableData($tve_table_prefix.'postmeta', ['meta_key'])), true);
		  return array_merge($column1, $column2);
		}

		public function tvc_product_sync_popup_html(){			
			 
			ob_start();
			?>
			<div class="modal bs fade popup-modal create-campa overlay" id="syncProduct" data-backdrop="false">
			  <div class="modal-dialog modal-dialog-centered">
			    <div class="modal-content">      
			      <div class="modal-body">
			        <button type="button" class="btn-close tvc-popup-close" data-bs-dismiss="modal" aria-label="Close"> &times; </button>
			        <h5><?php esc_html_e("Map your product attributes","conversios"); ?></h5>
			        <p><?php esc_html_e("Google Merchant Center uses attributes to format your product information for Shopping Ads. Map your product attributes to the Merchant Center product attributes below. You can also edit each product’s individual attributes after you sync your products. Not all fields below are marked required, however based on your shop's categories and your country you might map a few optional attributes as well. See the full guide","conversios"); ?> <a target="_blank" href="<?php esc_url_raw("https://support.google.com/merchants/answer/7052112"); ?>"><?php esc_html_e("here","conversios"); ?></a>.
			        </p>
			        <div class="wizard-section campaign-wizard">
			          <div class="wizard-content">
			          	<input type="hidden" name="merchant_id" id="merchant_id" value="<?php echo esc_attr($this->merchantId); ?>">
			            <form class="tab-wizard wizard-	 wizard" id="productSync" method="POST">
			              <h5><span class="wiz-title"><?php esc_html_e("Category Mapping","conversios"); ?></span></h5>
			              <section>
			                <div class="card-wrapper">                                        
			                  <div class="row">
			                    <div class="col-6">
			                      <h6 class="heading-tbl"><img src="<?php echo esc_url_raw(ENHANCAD_PLUGIN_URL.'/admin/images/icon/woocommerce.svg'); ?>" alt="WooCommerce"/><?php esc_html_e("Commerce Category","conversios"); ?></h6>
			                    </div>
			                    <div class="col-6">
			                      <h6 class="heading-tbl gmc-image-heading"><img src="<?php echo esc_url_raw(ENHANCAD_PLUGIN_URL.'/admin/images/icon/google-shopping.svg'); ?>" alt="google-shopping"/><?php esc_html_e("Google Merchant Center Category","conversios"); ?></h6>
			                    </div>
			                  </div><?php echo $this->category_wrapper_obj->category_table_content('mapping'); ?>
			                </div>
			              </section>
			              <!-- Step 2 -->
			              <h5><span class="wiz-title"><?php esc_html_e("Product Attribution Mapping","conversios"); ?></span></h5>
			              <section>
			              <div class="card-wrapper">                                        
			                <div class="row">
			                  <div class="col-6">
			                    <h6 class="heading-tbl gmc-image-heading"><img src="<?php echo esc_url_raw(ENHANCAD_PLUGIN_URL.'/admin/images/icon/google-shopping.svg'); ?>" alt="google-shopping"/><?php esc_html_e("Google Merchant center product attributes","conversios"); ?></h6>
			                  </div>
			                  <div class="col-6">
			                    <h6 class="heading-tbl"><img src="<?php echo esc_url_raw(ENHANCAD_PLUGIN_URL.'/admin/images/icon/woocommerce.svg'); ?>" alt="WooCommerce"/><?php esc_html_e("Commerce product attributes","conversios"); ?></h6>
			                  </div>
			                </div>
			                <?php                      
                      $ee_mapped_attrs = unserialize(get_option('ee_prod_mapped_attrs'));
			                $wooCommerceAttributes = $this->wooCommerceAttributes();
			                foreach ($this->TVC_Admin_Helper->get_gmcAttributes() as $key => $attribute) {
			                  $sel_val="";
			                  echo '<div class="row">
			                    <div class="col-6 align-self-center">
			                      <div class="form-group">
			                        <span class="td-head">' . esc_attr($attribute["field"]) . " " . (isset($attribute["required"]) && esc_attr($attribute["required"]) == 1 ? '<span style="color: red;"> *</span>' : "") . '
			                        <div class="tvc-tooltip">
			                          <span class="tvc-tooltiptext tvc-tooltip-right">'.(isset($attribute["desc"])? esc_attr($attribute["desc"]):"") .'</span>
			                          <img src="'. esc_url_raw(ENHANCAD_PLUGIN_URL."/admin/images/icon/informationI.svg").'" alt=""/>
			                        </div>
			                        </span>                       
			                      </div>
			                    </div>
			                    <div class="col-6 align-self-center">
			                      <div class="form-group">';
			                        $tvc_select_option = $wooCommerceAttributes;
                              $tvc_select_option = $this->TVC_Admin_Helper->add_additional_option_in_tvc_select($tvc_select_option, $attribute["field"]);
			                        $require = (isset($attribute['required']) && $attribute['required'])?true:false;
			                        $sel_val_def = (isset($attribute['wAttribute']))?$attribute['wAttribute']:"";
			                        if($attribute["field"]=='link'){
			                            echo "product link";
			                        }else if($attribute["field"]=='shipping'){
			                          //$name, $class_id, string $label=null, $sel_val = null, bool $require = false
			                          $sel_val = (isset($ee_mapped_attrs[$attribute["field"]]))?$ee_mapped_attrs[$attribute["field"]]:$sel_val_def;
			                          echo $this->TVC_Admin_Helper->tvc_text($attribute["field"], 'number', '', esc_html__('Add shipping flat rate','conversios'), $sel_val, $require);
			                        }else if($attribute["field"]=='tax'){
			                          //$name, $class_id, string $label=null, $sel_val = null, bool $require = false
			                          $sel_val = (isset($ee_mapped_attrs[$attribute["field"]]))?esc_attr($ee_mapped_attrs[$attribute["field"]]):esc_attr($sel_val_def);
			                          echo $this->TVC_Admin_Helper->tvc_text($attribute["field"], 'number', '', 'Add TAX flat (%)', $sel_val, $require);
			                        }else if($attribute["field"]=='content_language'){
			                          echo $this->TVC_Admin_Helper->tvc_language_select($attribute["field"], 'content_language', esc_html__('Please Select Attribute','conversios'), 'en',$require);
			                        }else if($attribute["field"]=='target_country'){
			                          //$name, $class_id, bool $require = false
			                          echo $this->TVC_Admin_Helper->tvc_countries_select($attribute["field"], 'target_country', esc_html__('Please Select Attribute','conversios'), $require);
			                        }else{
			                          if(isset($attribute['fixed_options']) && $attribute['fixed_options'] !=""){
			                            $tvc_select_option_t = explode(",", $attribute['fixed_options']);
			                            $tvc_select_option=[];
			                            foreach( $tvc_select_option_t as $o_val ){
			                              $tvc_select_option[]['field'] = esc_attr($o_val);
			                            } 
			                            $sel_val = $sel_val_def;
			                            $this->TVC_Admin_Helper->tvc_select($attribute["field"],$attribute["field"],esc_html__('Please Select Attribute','conversios'), $sel_val, $require, $tvc_select_option);
			                          }else{ 
			                            $sel_val = (isset($ee_mapped_attrs[$attribute["field"]]))?$ee_mapped_attrs[$attribute["field"]]:$sel_val_def;
			                          //$name, $class_id, $label="Please Select", $sel_val, $require, $option_list
			                          $this->TVC_Admin_Helper->tvc_select($attribute["field"],$attribute["field"],esc_html__('Please Select Attribute','conversios'), $sel_val, $require, $tvc_select_option);
			                          }                          
			                        }
			                      echo '</div>
			                    </div>
			                  </div>';
			                }?>											
			              </div>
			              	<div class="product_batch_size">
                        <label>Product batch size 
                          <div class="tvc-tooltip">
                            <span class="tvc-tooltiptext tvc-tooltip-right">if you are an issue in product sync with the current batch size then dicrease the batch size.</span>
                            <img src="<?php echo esc_url_raw(ENHANCAD_PLUGIN_URL."/admin/images/icon/informationI.svg"); ?>" alt=""/>
                          </div>:
                        </label>  
                        <select id="product_batch_size">
                          <option value="10">10</option>
                          <option value="25">25</option>
                          <option selected="selected" value="50">50</option>
                          <option value="100">100</option>
                        </select>
                      </div>
			              </section>
			            </form>
			          </div>
			        </div>
			      </div>
			    </div>
			  </div>
			</div>
			<div class="progress-bar-wapper">
				<span class="tvc-sync-message"><?php esc_html_e("Initializing...","conversios"); ?></span>
				<div class="progress tvc-sync-progress-db">
				  <div class="progress-bar progress-bar-striped progress-bar-animated tvc-sync-progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
				</div>
				<div class="progress tvc-sync-progress-gmc">
				  <div class="progress-bar progress-bar-striped progress-bar-animated bg-success tvc-sync-success-progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
				</div>
				<div class="tvc-progress-info">
					<span class="tvc-sync-count">0</span>					
					<span class="tvc-total-count">--</span>
				</div>
			</div>
			<?php
			echo $this->add_product_sync_script();
			return ob_get_clean();
		}//tvc_product_sync_popup_html

		public function add_product_sync_script(){
			$shop_categories_list = $this->TVC_Admin_Helper->get_tvc_product_cat_list();
			?>
			<script>        
      $(document).ready(function() {
        $('#syncProduct .select2').each(function() {  
          var $p = $(this).parent();  
          $(this).select2({  
            dropdownParent: $p  
          });  
        });
      });
			
			$(".tab-wizard").steps({
			  headerTag: "h5",
			  bodyTag: "section",
			  transitionEffect: "fade",
			  titleTemplate: '<span class="step">#index#</span> #title#',
			  labels: {
			    finish: "Sync Products",
			    next: "Next",
			    previous: "Previous",
			  },
			  onStepChanging: function(e, currentIndex, newIndex) {
			    var shop_categories = JSON.parse("<?php echo $shop_categories_list; ?>");
			    var is_tvc_cat_selecte = false;
			    shop_categories.forEach(function(v,i){
			      if(is_tvc_cat_selecte == false && $("#category-"+v).val() != "" && $("#category-"+v).val() != 0){
			        is_tvc_cat_selecte =true;
			        return false;
			      }
			    });    
			    if(is_tvc_cat_selecte == 1 || is_tvc_cat_selecte == true){
			      return true;
			    }else{
			      tvc_helper.tvc_alert("error","","Select at least one Google Merchant Center Category.",true);
			      return false;
			    }
			  },
			  onStepChanged: function(event, currentIndex, priorIndex) {   
			    $('.steps .current').prevAll().addClass('disabled');    
			  },
			  onFinished: function(event, currentIndex) {
			    var valid=true;
			    jQuery(".field-required").each(function() {
			      if($(this).val()==0 && valid){
			        valid=false;
			        //$(this).select2('focus');
			      }
			    });
			    if(!valid){
			      tvc_helper.tvc_alert("error","","Please select all required fields");
			    }else{
			    	$(".actions a[href='#finish']").prop( "disabled", true );
			      submitProductSyncUp();

			    }//check for required fields end        	
			  }
			});

			function submitProductSyncUp(sync_progressive_data = null){
				jQuery("#feed-spinner").css("display", "block");
				$('.progress-bar-wapper').addClass('open');
				var data = {
						action:'tvcajax_product_sync_bantch_wise',
						merchant_id:'<?php echo esc_attr($this->merchantId); ?>',
						account_id:'<?php echo esc_attr($this->accountId); ?>',
						customer_id: '<?php echo esc_attr($this->currentCustomerId); ?>',
						subscription_id: '<?php echo esc_attr($this->subscriptionId); ?>',
						tvc_data: jQuery("#productSync").serialize(),
            product_batch_size: jQuery("#product_batch_size").val(),
						sync_progressive_data:sync_progressive_data
					}
				$.ajax({
	        type: "POST",
	        dataType: "json",
	        url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
	        data: data,
	        beforeSend: function(){
	        },
	        success: function(response){
	        	//console.log(response);
	        	jQuery("#feed-spinner").css("display", "none");
            if(response.status == "success"){
  	        	//var rs = JSON.parse(response);
  	        	let sync_produt_p = Math.round(response.sync_progressive_data.sync_produt_p);
  	        	let total_product = response.sync_progressive_data.total_product;
  	        	let sync_produt = response.sync_progressive_data.sync_produt;
  	        	let sync_message = response.sync_progressive_data.sync_message;
  	        	let sync_step = response.sync_progressive_data.sync_step;
  	        	let is_synced_up = response.sync_progressive_data.is_synced_up;
  	        	let last_sync_product_id = response.sync_progressive_data.last_sync_product_id;
  	        	let skip_products = response.sync_progressive_data.last_sync_product_id;
  	        	let tvc_sync_progress_bar_class = "tvc-sync-progress-bar";
  	        	if(sync_step == 1){
  	        		jQuery(".tvc-sync-progress-db").css("display","flex");
  	        	}else if(sync_step == 2){
  	        		jQuery(".tvc-sync-progress-db").hide();
  	        		jQuery(".tvc-sync-progress-gmc").css("display","flex");
  	        		tvc_sync_progress_bar_class = "tvc-sync-success-progress-bar";
  	        	}
  	        	
  	        	jQuery("."+tvc_sync_progress_bar_class).css("width",sync_produt_p+"%");
  	        	jQuery("."+tvc_sync_progress_bar_class).html(sync_produt_p+"%");
  	        	jQuery("."+tvc_sync_progress_bar_class).attr("aria-valuenow",sync_produt_p);
  	        	jQuery(".tvc-progress-info").show();
  	        	jQuery(".tvc-sync-count").html(sync_produt);
  	        	jQuery(".tvc-total-count").html(total_product);
  	        	jQuery(".tvc-sync-message").html(sync_message);
  	        	if(sync_step == 1 &&  is_synced_up == true){
  	        		is_synced_up = false;
  	        	}

  	        	if(is_synced_up == false && sync_step <= 2){
  	        		submitProductSyncUp(response.sync_progressive_data);
  	        	}else if(sync_step == 2 ){
  	        		setTimeout(function(){
  	        			$('.progress-bar-wapper').removeClass('open');
  	        			jQuery(".tvc-sync-progress-bar").css("width","0%");
  	        			jQuery(".tvc-sync-success-progress-bar").css("width","0%");
  	        			jQuery(".tvc-sync-progress-bar").html("0%");
  	        			jQuery(".tvc-sync-success-progress-bar").html("0%");
  	        			jQuery(".tvc-sync-progress-bar").attr("aria-valuenow","0");
  	        			jQuery(".tvc-sync-success-progress-bar").attr("aria-valuenow","0");
  	        			jQuery(".tvc-sync-count").html("0");
  	        			jQuery(".tvc-total-count").html("--");
  	        			jQuery(".tvc-sync-message").html("Initialization of products data for push data in Google shopping");
  	        			if (response.api_rs.error == false) {
  		        			var message = "Your products have been synced in your merchant center account. It takes up to 30 minutes to reflect the product data in merchant center. As soon as they are updated, they will be shown in the \"Product Sync\" dashboard.";
  				          if (response.sync_progressive_data.skip_products.length > 0) {
  				            message = message + "\n Because of pricing issues, " + response.sync_progressive_data.skip_products.length + " products did not sync.";
  				          }
  				          tvc_helper.tvc_alert("success","",message);			          
  				          window.location.replace("<?php echo esc_url_raw($this->site_url.'sync_product_page'); ?>");
  				        }else {
  					        tvc_helper.tvc_alert("error","",response.api_rs.message);
  					      }			          
  	        		}, 2000);
  	        	}
            }else{
              tvc_helper.tvc_alert("error","",response.message);
              setTimeout(function(){
                window.location.replace("<?php echo esc_url_raw($this->site_url.'sync_product_page'); ?>");
              }, 2000);
            }
	                
	        }
		    });
			}
      
			$(document).on("show.bs.modal", "#syncProduct", function (e) {
				jQuery("#feed-spinner").css("display", "block");
			  selectCategory();
			  $("select[id^=catmap]").each(function(){
			  	removeChildCategory($(this).attr("id"))
				});
			});

			function selectCategory() {
			  var country_id = "<?php echo esc_attr($this->country); ?>";
			  var customer_id = '<?php echo esc_attr($this->currentCustomerId); ?>';
			  var parent = "";
			  jQuery.post(
			    tvc_ajax_url,
			    {
			      action: "tvcajax-gmc-category-lists",
			      countryCode: country_id,
			      customerId: customer_id,
			      parent: parent
			    },
			    function( response ) {
			      var categories = JSON.parse(response);
			      var obj;
						$("select[id^=catmap]").each(function(){
							obj = $("#catmap-"+$(this).attr("catid")+"_0");
			      	obj.empty();
			    		obj.append("<option id='0' value='0' resourcename='0'>Select a category</option>");
			      	$.each(categories, function (i, value) {
			          obj.append("<option id=" + JSON.stringify(value.id) + " value=" + JSON.stringify(value.id) + " resourceName=" + JSON.stringify(value.resourceName) + ">" + value.name + "</option>");                
			        });
						});
						jQuery("#feed-spinner").css("display", "none");
			  });
			}

			function selectSubCategory(thisObj) {
				var selectId;
				var wooCategoryId;
				var GmcCategoryId;
				var GmcParent;
				selectId = thisObj.id;
				wooCategoryId = $(thisObj).attr("catid");
				GmcCategoryId = $(thisObj).find(":selected").val();
				GmcParent = $(thisObj).find(":selected").attr("resourcename");
			  //$("#"+selectId).select2().find(":selected").val();
			  // $("#"+selectId).select2().find(":selected").data("id");
			  //console.log(selectId+"--"+wooCategoryId+"--"+GmcCategoryId+"--"+GmcParent);
			  	
			  jQuery("#feed-spinner").css("display", "block");
				removeChildCategory(selectId);
				selectChildCategoryValue(wooCategoryId);
			  if (GmcParent != undefined) {
			  	var country_id = "<?php echo esc_attr($this->country); ?>";
			    var customer_id = '<?php echo esc_attr($this->currentCustomerId); ?>';
			  	jQuery.post(
			      tvc_ajax_url,
			      {
			        action: "tvcajax-gmc-category-lists",
			        countryCode: country_id,
			        customerId: customer_id,
			        parent: GmcParent
			      },
			      function( response ) {
			        var categories = JSON.parse(response);
			        var newId;
			      	var slitedId = selectId.split("_");
			      	newId = slitedId[0]+"_"+ ++slitedId[1];
			      	if(categories.length === 0){		
			      	}else{
			      		//console.log(newId);
			        	$("#"+newId).empty();
			        	$("#"+newId).append("<option id='0' value='0' resourcename='0'>Select a sub-category</option>");
			          $.each(categories, function (i, value) {
			            $("#"+newId).append("<option id=" + JSON.stringify(value.id) + " value=" + JSON.stringify(value.id) + " resourceName=" + JSON.stringify(value.resourceName) + ">" + value.name + "</option>");
			          });
			          $("#"+newId).addClass("form-control");
			          //$("#"+newId).select2();
			          $("#"+newId).css("display", "block");
			      	}
			      	jQuery("#feed-spinner").css("display", "none");
			      }
			    );	
			  }
			}

			function removeChildCategory(currentId){
				var currentSplit = currentId.split("_");
			  var childEleId;
				for (i = ++currentSplit[1]; i < 6; i++) {
					childEleId = currentSplit[0]+"_"+ i;
					//console.log($("#"+childEleId));
			  	$("#"+childEleId).empty();
					$("#"+childEleId).removeClass("form-control");
			    $("#"+childEleId).css("display", "none");
			    if ($("#"+childEleId).data("select2")) {
					  $("#"+childEleId).off("select2:select");
						$("#"+childEleId).select2("destroy");
			      $("#"+childEleId).removeClass("select2");
				 	}
				}
			}

			function selectChildCategoryValue(wooCategoryId){
				var childCatvala;
				for(i = 0; i < 6; i++){
					childCatvala = $("#catmap-"+wooCategoryId+"_"+i).find(":selected").attr("id");
			    childCatname = $("#catmap-"+wooCategoryId+"_"+i).find(":selected").text();
					if($("#catmap-"+wooCategoryId+"_"+0).find(":selected").attr("id") <= 0){
						$("#category-"+wooCategoryId).val(0);
					}else{
						if(childCatvala > 0){
							$("#category-"+wooCategoryId).val(childCatvala);
			        $("#category-name-"+wooCategoryId).val(childCatname);
						}
					}
				}
			}
			$( ".wizard-content" ).on( "click", ".change_prodct_feed_cat", function() {
			 // console.log( $( this ).attr("data-id") );
			  $(this).hide();
			  var feed_select_cat_id = $( this ).attr("data-id");
			  var woo_cat_id = $( this ).attr("data-cat-id");			  
			  jQuery("#category-"+woo_cat_id).val("0");
			  jQuery("#category-name-"+woo_cat_id).val("");
			  jQuery("#label-"+feed_select_cat_id).hide();
			  jQuery("#"+feed_select_cat_id).slideDown();
			});
			function changeProdctFeedCat(feed_select_cat_id){
			  jQuery("#label-"+feed_select_cat_id).hide();
			  jQuery("#"+feed_select_cat_id).slideDown();
			}
			</script>
			<?php
		}

	}
}
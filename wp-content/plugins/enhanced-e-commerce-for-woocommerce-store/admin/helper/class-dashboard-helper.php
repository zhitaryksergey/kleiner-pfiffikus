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
if(!class_exists('Conversios_Dashboard_Helper')){
	class Conversios_Dashboard_Helper{
		protected $ShoppingApi;
		protected $TVC_Admin_Helper;
		protected $CustomApi;
		public function __construct(){
			$this->req_int();
			$this->TVC_Admin_Helper = new TVC_Admin_Helper();
      $this->CustomApi = new CustomApi();
      $this->ShoppingApi = new ShoppingApi();
			add_action('wp_ajax_get_google_analytics_reports', array($this,'get_google_analytics_reports') );
			add_action('wp_ajax_get_google_ads_reports_chart', array($this,'get_google_ads_reports_chart') );
			add_action('wp_ajax_get_google_ads_campaign_performance', array($this,'get_google_ads_campaign_performance') );
		}

		public function req_int(){
			if (!class_exists('CustomApi')) {
        require_once(ENHANCAD_PLUGIN_DIR . 'includes/setup/CustomApi.php');
      }
      if (!class_exists('ShoppingApi')) {
	      require_once(ENHANCAD_PLUGIN_DIR . 'includes/setup/ShoppingApi.php');
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
		public function get_google_ads_campaign_performance(){
			$nonce = (isset($_POST['conversios_nonce']))?$_POST['conversios_nonce']:"";
			if($this->admin_safe_ajax_call($nonce, 'conversios_nonce')){
				$post_data = (object)$_POST;
				//$start_date = isset($post_data->start_date)?$post_data->start_date:date('Y-m-d',strtotime('-31 days'));
				$start_date = str_replace(' ', '',(isset($_POST['start_date']))?$_POST['start_date']:"");
				if($start_date != ""){
					$date = DateTime::createFromFormat('d-m-Y', $start_date);
					$start_date = $date->format('Y-m-d');
				}
				$start_date == (false !==strtotime( $start_date ))?date('Y-m-d', strtotime($start_date)):date( 'Y-m-d', strtotime( '-1 month' ));

				$end_date = str_replace(' ', '',(isset($_POST['end_date']))?$_POST['end_date']:"");
				if($end_date != ""){
					$date = DateTime::createFromFormat('d-m-Y', $end_date);
					$end_date = $date->format('Y-m-d');
				}
				$end_date == (false !==strtotime( $end_date ))?date('Y-m-d', strtotime($end_date)):date( 'Y-m-d', strtotime( 'now' ));

				$api_rs = $this->ShoppingApi->campaign_performance(2, 7, $start_date, $end_date); 
				if (isset($api_rs->error) && $api_rs->error == '') {
        	if(isset($api_rs->data) && $api_rs->data != ""){
        		$return = array('error'=>false, 'data'=>$api_rs->data);
        	}
        }else{
        	$errormsg= isset($api_rs->errors[0])?$api_rs->errors[0]:"";
        	$return = array('error'=>true,'errors'=>$errormsg);
        }	
				//print_r($account_performance_res);

			}else{
      	$return = array('error'=>true,'errors'=>'Admin security nonce is not verified.');
      }
      echo json_encode($return);
			wp_die();
		}
		public function get_google_ads_reports_chart(){
			$nonce = (isset($_POST['conversios_nonce']))?$_POST['conversios_nonce']:"";
			if($this->admin_safe_ajax_call($nonce, 'conversios_nonce')){
				$post_data = (object)$_POST;
				//$start_date = isset($post_data->start_date)?$post_data->start_date:date('Y-m-d',strtotime('-31 days'));
				$start_date = str_replace(' ', '',(isset($_POST['start_date']))?$_POST['start_date']:"");
				if($start_date != ""){
					$date = DateTime::createFromFormat('d-m-Y', $start_date);
					$start_date = $date->format('Y-m-d');
				}
				$start_date == (false !==strtotime( $start_date ))?date('Y-m-d', strtotime($start_date)):date( 'Y-m-d', strtotime( '-1 month' ));

				//$end_date = isset($post_data->end_date)?$post_data->end_date:date('Y-m-d',strtotime('-1day'));
				$end_date = str_replace(' ', '',(isset($_POST['end_date']))?$_POST['end_date']:"");
				if($end_date != ""){
					$date = DateTime::createFromFormat('d-m-Y', $end_date);
					$end_date = $date->format('Y-m-d');
				}
				$end_date == (false !==strtotime( $end_date ))?date('Y-m-d', strtotime($end_date)):date( 'Y-m-d', strtotime( 'now' ));

				$api_rs = $this->ShoppingApi->accountPerformance_for_dashboard(2, 7, $start_date, $end_date);
				if (isset($api_rs->error) && $api_rs->error == '') {
        	if(isset($api_rs->data) && $api_rs->data != ""){
        		$return = array('error'=>false, 'data'=>$api_rs->data);
        	}
        }else{
        	$return = array('error'=>true,'errors'=>$api_rs->error);
        }	
				//print_r($account_performance_res);

			}else{
      	$return = array('error'=>true,'errors'=>'Admin security nonce is not verified.');
      }
      echo json_encode($return);
			wp_die();
		}
		public function get_google_analytics_reports(){
			$nonce = (isset($_POST['conversios_nonce']))?$_POST['conversios_nonce']:"";
			if($this->admin_safe_ajax_call($nonce, 'conversios_nonce')){	
			  $post_data = (object)$_POST;
			  $ga_traking_type = isset($post_data->ga_traking_type)?$post_data->ga_traking_type:"";
			  $subscription_id = isset($post_data->subscription_id)?$post_data->subscription_id:"";
			  $view_id = isset($post_data->view_id)?$post_data->view_id:"";
			  
				$start_date = str_replace(' ', '',(isset($_POST['start_date']))?$_POST['start_date']:"");
				if($start_date != ""){
					$date = DateTime::createFromFormat('d-m-Y', $start_date);
					$start_date = $date->format('Y-m-d');
				}
				$start_date == (false !==strtotime( $start_date ))?date('Y-m-d', strtotime($start_date)):date( 'Y-m-d', strtotime( '-1 month' ));

				$end_date = str_replace(' ', '',(isset($_POST['end_date']))?$_POST['end_date']:"");
				if($end_date != ""){
					$date = DateTime::createFromFormat('d-m-Y', $end_date);
					$end_date = $date->format('Y-m-d');
				}
				$end_date == (false !==strtotime( $end_date ))?date('Y-m-d', strtotime($end_date)):date( 'Y-m-d', strtotime( 'now' ));

			  $return = array();
			  if($subscription_id != "" && $view_id !="" &&( $ga_traking_type == "UA" || $ga_traking_type == "BOTH" )){
			  	$data = array(
			  		'subscription_id'=>$subscription_id,
			  		'view_id'=>$view_id,
			  		'start_date'=>$start_date,
			  		'end_date'=>$end_date
			  	);

			  	$api_rs = $this->CustomApi->get_google_analytics_reports($data);
			  	if(isset($api_rs->error) && $api_rs->error == '') {
          	if(isset($api_rs->data) && $api_rs->data != ""){
          		$return = array('error'=>false, 'data'=>$api_rs->data,'errors'=>'');
          	}
          }else{
          	$return = array('error'=>true,'errors'=>$api_rs->message);
          }			  	
			  }else if($subscription_id != "" && ( $ga_traking_type == "GA4" || $ga_traking_type == "BOTH" )){
			  	$return = array('error'=>true,'errors'=>'GA4 Coming soon...');
			  }			  
      }else{
      	$return = array('error'=>true,'errors'=>'Admin security nonce is not verified.');
      }
      echo json_encode($return);
			wp_die();
		}
	}
}
new Conversios_Dashboard_Helper();
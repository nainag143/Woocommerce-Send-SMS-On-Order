<?php
/*
 Plugin Name: Naga Order SMS
 
 Description: This plugin allows to add SMS feature on Woocommerce after order created.
 Author: Nagaraju Janagani
 Version: 1.0
 
 */


class NAGASMS
{
	
	/**
	 * Call Required hooks
	 *
	 * @access 
	 * @since 1.0
	 * @return void
	 */
	function __construct(){
		register_activation_hook(__FILE__, array( __CLASS__, 'activation' ) );
		register_deactivation_hook(__FILE__, array( __CLASS__, 'deactivation' ) );
				
		add_action( 'admin_init', array( __CLASS__, 'register_woo_sms_tab' ) );
		
add_action('woocommerce_checkout_order_processed', array( __CLASS__, 'sendOrderProccedSMS' ) , 10 , 1);
		//add_action("init", array( __CLASS__, 'sendOrderProccedSMS' ) , 10 , 1);
		
	}
	
	/**
	 * Require a woocommerce for WordPress on activation
	 *
	 * @uses register_activation_hook
	 */
	public function activation(){
		/* Check WooCommercer Is Activated Or Not*/
		if ( !in_array( "woocommerce/woocommerce.php", apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) { 
			$exit_msg = 'Plugin Must Required Woocommerce Installed';
			exit( $exit_msg );
		}
		
		do_action( 'woo_sms_activate' );
			
	}
	
	
	public function get_plugin_url() {
		return plugin_dir_url( __FILE__ );
	}

	public function get_plugin_dir() {
		return plugin_dir_path( __FILE__ );
	}
	
	
	/**
	 * @uses register_deactivation_hook
	 */
	public function deactivation(){
		do_action( 'woo_sms_deactivate' );
	}
	
	
	/**
	 * @Register Woocommerce Setting Tab 
	 * @uses admin_init hook
	 */
	public static function register_woo_sms_tab() {
		require_once( self::get_plugin_dir() . 'inc/woo-settings-tab.php' );
		if ( is_admin() ) {
			$settings = new WC_SMS_Settings;
			$settings->setup();
		}
	}
	
	
	/*
	 *@uses woocommerce_checkout_order_processed 
	*/
	
	public function sendOrderProccedSMS($order_id){
		 global $woocommerce;
		 
		 require_once( self::get_plugin_dir() . 'inc/woo-settings-tab.php' );
		 $settings = new WC_SMS_Settings;
		 
		 $order = new WC_Order($order_id);
		 
		 $smsMessage = $settings->get_option( 'order_sms_text' );
		 //$smsMessage  = 'HELLO';
 		 $dd=date("md");
		 $firstName = get_post_meta($order_id,'_billing_first_name',true);
		 $replaceWord['ORDER_ID'] = $order_id;
		 $replaceWord['FIRSTNAME'] = $firstName;
		 $replaceWord['DATE']=$dd;
		 $replaceWord['TOTAL'] = $order->get_total();
		 $messageBody = self::rep_templates($smsMessage,$replaceWord);
		 $messageBody = urlencode($messageBody);
		 self::sendSMS($order_id,$messageBody);
		 
	}
	
	/*
		* Get Phone Number From billing information
	*/
	public function getPhoneNumber($order_id){
		global $woocommerce;
		$phoneNumber = get_post_meta($order_id,'_billing_phone',true);
		
		if(isset($phoneNumber) && !empty($phoneNumber)){
			return $phoneNumber;			
		}else{
			return false;
		}
	}

	
	/*
		Send Sms All Controller
	*/
	protected function sendSMS($order_id,$messageBody){
		global $woocommerce;
		require_once( self::get_plugin_dir() . 'inc/woo-settings-tab.php' );
		$settings = new WC_SMS_Settings;
		
		$APIKEY = $settings->get_option( 'api_key' );
		$phoneNumber = self::getPhoneNumber($order_id);
		$senderName = $settings->get_option( 'sendername_key' );;
		$smsType=  $settings->get_option( 'smstype' );;
		$username = $settings->get_option( 'hsp_username' );;
		 
		//$url="http://9ksms.pointsms.in/API/sms.php?username=khanawala&password=123456&from=KHAANA&to=$phoneNumber&msg=$messageBody&type=1&dnd_check=0";
		
		$url="https://chilangos.com.au/api.php?msg=$messageBody&num=$phoneNumber";
		 
		$data=wp_remote_get($url);
		
		$data=json_decode($data['body'],true);
		/*if(isset($data[1]['msgid']) && !empty($data[1]['msgid'])){
			return true;
		}else{
			$woocommerce->add_error( 'Unable to send the SMS' );
			exit;
		}*/
		
	}
	
	
	// To replace text in template
	public function rep_templates(&$t, $d){
		preg_match_all ( '/{\%(\w*)\%\}/' , $t , $matches );
		foreach($matches[1] as $m){
			//if($d[$m]!=null){
			$pattern = "/{\%".$m."\%\}/";
			$t = preg_replace( $pattern, $d[$m], $t);
			//}
		}		
		return $t;
	}
	
	
	
		
}

new WC_HSPSMS;

<?php 

abstract class WC_Abstract_Rpg extends WC_Payment_Gateway{

	protected $log_file_name = 'payments-gateways';	
	protected $valid_currencies = array( 'RUB' );
	protected $debug = 'no';	

	public function __construct(){

		// Bool. Can be set to true if you want payment fields to show
		// on the checkout (if doing a direct pay integration)
		$this->has_fields = false;
		
		// fefine settings
		$this->init_form_fields();
		// init settings. Now we can use get_option('setting_name')
		$this->init_settings();

		// Logs
		if ( 'yes' == $this->debug ) {
			$this->log = new WC_Logger();
		}

		// saving plugin admin options
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	
	}

	protected function log($message){
		if ( 'yes' == $this->debug )
			$this->log->add( $this->log_file_name, $message );
	}
	
	protected function notice($message){
		$this->log($message);
		wc_add_notice( $message, 'error' );
	}
	
	protected function error($message){
		$this->log($message);
		wc_add_notice( $message, 'error' );
	}
	
	protected function abort($message){
		header( 'HTTP/1.1 400 Bad Request' );
		$this->log($message);		
		die( $message );
	}
	
	/**
	 * Check if this gateway is enabled and available in the user's country
	 *
	 * @return bool
	 */
	function is_valid_for_use() {
		if ( ! in_array( get_woocommerce_currency(), $this->valid_currencies ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 */
	public function admin_options() {
		if ( $this->is_valid_for_use() ) {
			parent::admin_options();
		} else {
			?>
			<div class="inline error"><p><strong><?php _e( 'Gateway Disabled', 'woocommerce' ); ?></strong>: <?php _e( 'Plugin does not support your store currency.', 'woocommerce' ); ?></p></div>
			<?php
		}
	}
	
	/* is variable a numeric and integer */
	protected function is_numeric_int(&$v){
		if(is_numeric($v) and ((int)$v) == $v)
			return true;
		else false;
	}

	/* is variable a numeric and float */
	protected function is_numeric_float(&$v){
		if(is_numeric($v) and ((float)$v) == $v)
			return true;
		else false;
	}

	/**
	* Payment complete then status is:
	* complete - for virtual, downloadeble goods or (if real good is sended to cusmomer)
	* processing - for real goods if they olready payed but should be sended to customer
	**/
	protected function is_order_payed( $order ){
		if( $order ) // then order is exist variable not is false
			return $order->has_status( array('processing','completed') );
		else
			return false;
	}

	protected function createHash($msg){
		return hash('sha256', $msg);
	}

}
?>
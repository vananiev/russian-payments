<?php 

class WC_Rpg extends WC_Abstract_Rpg{

	public function __construct(){

		$this->id = 'russian_payments';
		$this->icon = plugin_dir_url(__FILE__) . '/rpg.png';
		// Title of the payment method shown on the admin page.
		$this->method_title = 'Russian Payments';
		$this->method_description = 'Принимайте оплату с помощью Яндекс.Денег, Webmoney, QIWI, Robokassa';

		// get saved options
		$this->enabled 			= false; // don't show plugin on checkout page
		$this->appid 			= $this->get_option( 'appid' );
		$this->secret 			= $this->get_option( 'secret' );
		$this->debug			= $this->get_option( 'debug' );
		$this->testmode			= $this->get_option( 'testmode' );

		/*
		action is perfomed then gateway GET result/fail/success URL
		result/fail/success URL: /?wc-api=woocommerce_api_<lower this class name> = /?wc-api=woocommerce_api_wc_rpg
		*/
		add_action('woocommerce_api_'.strtolower(get_class($this)), array($this, 'rest_api'));

		parent::__construct();
	}

	/**
	* These are the options you’ll show in admin on your gateway’s settings page
	**/
	function init_form_fields(){
		$this->form_fields = array(
			'appid' => array(
				'title' => __('AppId', 'woocommerce'),
				'type' => 'text',
				'description' => __('AppId вашего приложения', 'woocommerce'),
				'default' => 'demo'
			),
			'secret' => array(
				'title' => __('Secret', 'woocommerce'),
				'type' => 'text',
				'description' => __('Secret вашего приложения, не соощайте его никому', 'woocommerce'),
				'default' => 'demo'
			),
			'debug' => array(
				'title' => __('Debug', 'woocommerce'),
				'type' => 'checkbox',
				'label' => __('Включить логгирование (<code>'.wc_get_log_file_path( $this->log_file_name).'</code>)', 'woocommerce'),
				'default' => 'no'
			),
			'testmode' => array(
				'title' => __('Тестовый режим', 'woocommerce'),
				'type' => 'checkbox', 
				'label' => __('Включен', 'woocommerce'),
				'description' => __('В этом режиме плата за товар не снимается. Должен быть отключен при нормальной работе интерент-магазина', 'woocommerce'),
				'default' => 'no'
			),
		);
	}

/**
	* Calling by externel gateway to say about pay result or redirect user (success or fail)
	**/
	public function rest_api(){
		if( !isset($_GET['about']) ) $about = '';
		else  $about = $_GET['about'];
		switch($about){
		case 'result':
			$this->rest_api_result($_GET);
			return;
		case 'fail':
			$this->rest_api_fail($_GET);
			return;
		case 'success':
			$this->rest_api_success($_GET);
			return;
		}
		$this->abort( 'Unknown request' );
	}
	
	/**
	* External gateway say about pay result
	**/
	private function rest_api_result(array $response){
		$this->log("about=result response is get");
		if( !$this->check_response_hash($response) ){
			$this->abort( 'Invalid request' );
		}
		$order = wc_get_order($response['orderid']);
		if(!$order)
			$this->abort( 'Order not found' );

		if($this->is_reponce_data_ok($order, $response)){
			if ( $this->is_order_payed( $order ) ) {
				$this->log( 'Order id = ' . $order->id . ' is already payed.' );
			}else{
				$order->payment_complete();
				$this->log('successful payment for order id = ' . $order->id);
			}
			header('HTTP/1.1 200 OK');
			echo 'ok';
			exit;
		}else{
			$order->update_status( 'failed', 'Invalid pay data' );
			$this->abort( 'Invalid pay data' );
		}
		$err='unknown result response';
		$order->update_status( 'failed', $err );
		$this->abort($err);
		die($err);
	}

	/**
	* Check response hash
	**/
	private function check_response_hash(array $response){
		if( !isset($response['orderid']) or !$this->is_numeric_int($response['orderid'])){
			$this->log("can't get orderid from response");
			return false;
		}
		$order = wc_get_order($response['orderid']);
		if( !$order){
			$this->log(__FUNCTION__ . ": order not found");
			return false; // no such order
		}
		if( !isset($response['sum']) or !$this->is_numeric_float($response['sum']) or
			!isset($response['currency']) or
			!isset($response['status']) or
			!isset($response['timestamp']) or
			!isset($response['hash'])
			){
			$this->log("invalid parameters");
			return false;
		}
	
		return ($response['hash'] === $this->createHash($response['orderid'].':'.
			$response['sum'].':'.$response['currency'].':'.
			$this->secret.':'.$response['status'].':'.$response['timestamp']));
	}

	/**
	* Validate response parameters
	**/	
	private function is_reponce_data_ok($order, $response){
		$order_sum = number_format($order->get_total(), 2, '.', '');
		if( $response['sum'] < $order_sum ){
			$msg = "Payed({$response['sum']}) less then need({$order_sum})";
			$order->update_status( 'failed', $msg );
			$this->log($msg);
			return false;
		}
		$order_currency = $order->get_order_currency();
		if( $order_currency != $response['currency'] ){
			$msg = "Pay currency({$response['currency']}) is invalid, need '{$order_currency}'";
			$order->update_status( 'failed', $msg );
			$this->log($msg);
			return false;
		}
		return true;
	}

	/**
	* External gateway redirect user after fail payment
	**/
	private function rest_api_fail($response){
		if (!isset($response['hash']) or
			!isset($response['orderid']) or
			!isset($response['timestamp']) or
			$response['hash'] !== $this->createHash($response['orderid'].':'.$this->secret.':'.$response['timestamp'])
		)
			die('Invalid request');
		$this->log("about=fail response is get");
		if( isset($response['message']) )
			$message = $response['message'];
		else
			$message = 'Payment error was occurred';
		wc_add_notice( htmlentities($message,ENT_QUOTES,'UTF-8'), 'error' );

		if( isset($response['orderid']) and (false != ($order = wc_get_order($response['orderid']))) )
			$message .= ', orderid='.$order->id;

		$this->log( $message, 'error' );
		wp_redirect( WC()->cart->get_checkout_url() );
	}
	
	/**
	* External gateway redirect user after success payment
	**/
	private function rest_api_success($response){
		if (!isset($response['hash']) or
			!isset($response['orderid']) or
			!isset($response['timestamp']) or
			$response['hash'] !== $this->createHash($response['orderid'].':'.$this->secret.':'.$response['timestamp'])
		)
			die('Invalid request');
		$this->log("about=success response is get");
		WC()->cart->empty_cart();
		// go to thank you page
		$order = wc_get_order($response['orderid']);
		if( $order ){
			$this->log("payment was successful, orderid=".$order->id);
			wp_redirect( $this->get_return_url( $order ) );
		}
		else{ // no such order
			$this->log("payment was successful");
			wp_redirect( $this->get_return_url() );
		}
	}

};

?>
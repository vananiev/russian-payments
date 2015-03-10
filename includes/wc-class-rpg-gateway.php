<?php 

abstract class WC_Rpg_Gateway extends WC_Abstract_Rpg{

	protected $gateway='';
	protected $http_get_args = array();
	private $appid='';
	private $secret='';
	private $gateway_url='http://russian-payments.ru/pay.php';
	private $gateway_test_url = 'http://russian-payments.ru/test/pay.php';

	public function __construct(){
		
		// get global saved options
		$_rpg = new WC_Rpg();
		$this->appid 			= $_rpg->appid;
		$this->secret 			= $_rpg->secret;
		$this->debug			= $_rpg->debug;
		$this->testmode			= $_rpg->testmode;

		parent::__construct();
	}

	/**
	* These are the options you’ll show in admin on your gateway’s settings page
	**/
	//abstract function init_form_fields();
		
	/**
	 * Handling payment and processing the order.
	 * getting posed form then direct pay is used from checkout page
	 **/
	function process_payment($order_id){
		$order = wc_get_order( $order_id );
		if( !$order ){
			$this->error('order not found');
			return false;
		}
		
		if( false === ($data = $this->get_request_data($order)) ){
			$this->error("Can't create request data for payment gateway");
			return false;
		}
		$request_args = http_build_query($data);
		$gateway_url = ($this->testmode == 'no') ? $this->gateway_url : $this->gateway_test_url;

		$this->log('redirecting user for pay to external gateway = '.$this->gateway .
			(($this->testmode == 'yes') ? '(test mode)': ''));
		return array(
			'result' => 'success',
			'redirect'	=>  $gateway_url.'?'.$request_args
		);
	}
	
	/**
	* Generate agruments for external payment gateway
	* return false or array
	**/
	private function get_request_data(WC_Order $order){
		$currency = $order->get_order_currency();
		if( ! in_array( $currency, $this->valid_currencies ) ){
			$this->error('Order should be payed by Russian Roubles');
			return false;
		}
		$sum = number_format($order->get_total(), 2, '.', '');

		$data = array(
			'appid' => $this->appid,
			'orderid' => $order->id,
			'sum' => $sum,
			'currency' => $currency,
			'gateway' => $this->gateway,
            'timestamp' => time(),
			'description' => 'Order '.$order->id.' payment'
		);
		$data['hash'] = $this->generate_request_hash($data);
		if(!empty($this->http_get_args) and is_array($this->http_get_args))
			$data = $data + $this->http_get_args;

		return $data;
	}
	
	/**
	* Generate hash for dialog by external gateway
	**/
	private function generate_request_hash(array $data){
		return $this->createHash($data['appid'].':'.$data['orderid'].':'.$data['sum'].':'.
            $data['currency'].':'.	$this->secret.':'.$data['gateway'].':'.
            $data['timestamp'] . ':' . $data['description']);
	}
}
?>
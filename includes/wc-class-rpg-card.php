<?php 

class WC_Rpg_Card extends WC_Rpg_Gateway{

	protected $gateway = 'yandex';
	protected $http_get_args = array('paymentType'=>'AC');

	public function __construct(){

		$this->id = 'rpg_card';
		$this->icon = RPG_ROOT_URL . '/../assets/img/card.png';
		// Title of the payment method shown on the admin page.
		$this->method_title = 'Банковская карта';
		$this->method_description = 'Принимайте оплату с помощью Банковской карты '.
			'(<a href="/wp-admin/admin.php?page=wc-settings&tab=checkout&section=wc_rpg">Russian Payments</a>)';

		$this->enabled 			= $this->get_option( 'enabled' );
		$this->title 			= $this->get_option( 'title' );
		$this->description 		= $this->get_option( 'description' );

		parent::__construct();
	}

	/**
	* These are the options you’ll show in admin on your gateway’s settings page
	**/
	function init_form_fields(){
		$this->form_fields = array(
			'enabled' => array(
				'title' => __('Включить/Выключить', 'woocommerce'),
				'type' => 'checkbox',
				'label' => __('Включен', 'woocommerce'),
				'default' => 'yes'
			),
			'title' => array(
				'title' => __('Название', 'woocommerce'),
				'type' => 'text', 
				'description' => __( 'Это название, которое пользователь видит во время выбора способа оплат', 'woocommerce' ), 
				'default' => __('Банковская карта', 'woocommerce')
			),
			'description' => array(
				'title' => __( 'Description', 'woocommerce' ),
				'type' => 'textarea',
				'description' => __( 'Это описание, которое пользователь видит во время выбора способа оплат.', 'woocommerce' ),
				'default' => 'Комиссия 2%'
			),
		);
	}
}
?>
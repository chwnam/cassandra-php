<?php

namespace CassandraPHP;

require_once 'abstract-models.php';


final class Sales_Model implements APIResponseHandler {

	/** @var Domain $domains_id */
	private $domains_id;

	/** @var  \DateTime $order_date */
	private $order_date;

	private $post_status;

	/** @var string $order_currency */
	private $order_currency;

	/** @var string $customer_user_agent */
	private $customer_user_agent;

	/** @var int $customer_user */
	private $customer_user;

	/** @var string $created_via */
	private $created_via;

	/** @var string $order_version */
	private $order_version;

	/** @var string $billing_country */
	private $billing_country;

	/** @var string $billing_city */
	private $billing_city;

	/** @var string $billing_state */
	private $billing_state;

	/** @var string $shipping_country */
	private $shipping_country;

	/** @var string $shipping_city */
	private $shipping_city;

	/** @var string $shipping_state */
	private $shipping_state;

	/** @var string $payment_method */
	private $payment_method;

	/** @var string $order_total 소수점 문제가 있을 수 있으므로 문자열 그대로 처리 */
	private $order_total;

	/** @var \DateTime $completed_date */
	private $completed_date;
	/** @var array $sales_sub */
	private $sales_sub = array();

	/**
	 * @param \stdClass $response
	 *
	 * @return Sales_Model
	 */
	public static function from_response( \stdClass $response ) {

		$obj = new static();

		if ( property_exists( $response, 'domain' ) && $response->domain instanceof \stdClass ) {
			$obj->domains_id = Domain::from_response( $response->domain );
		}

		if ( property_exists( $response, 'post_date' ) ) {
			$obj->order_date = convert_datetime( $response->post_date, FALSE );
		}

		if ( property_exists( $response, 'post_status' ) ) {
			$obj->post_status = sanitize_text_field( $response->post_status );
		}

		if ( property_exists( $response, 'order_currency' ) ) {
			$obj->order_currency = sanitize_text_field( $response->order_currency );
		}

		if ( property_exists( $response, 'customer_user_agent' ) ) {
			$obj->customer_user_agent = sanitize_text_field( $response->customer_user_agent );
		}

		if ( property_exists( $response, 'customer_user' ) ) {
			$obj->customer_user = absint( $response->customer_user );
		}

		if ( property_exists( $response, 'created_via' ) ) {
			$obj->created_via = sanitize_text_field( $response->created_via );
		}

		if ( property_exists( $response, 'order_version' ) ) {
			$obj->order_version = sanitize_text_field( $response->order_version );
		}

		if ( property_exists( $response, 'billing_country' ) ) {
			$obj->billing_country = sanitize_text_field( $response->billing_country );
		}

		if ( property_exists( $response, 'billing_city' ) ) {
			$obj->billing_city = sanitize_text_field( $response->billing_city );
		}

		if ( property_exists( $response, 'billing_state' ) ) {
			$obj->billing_state = sanitize_text_field( $response->billing_state );
		}

		if ( property_exists( $response, 'shipping_country' ) ) {
			$obj->shipping_country = sanitize_text_field( $response->shipping_country );
		}

		if ( property_exists( $response, 'shipping_city' ) ) {
			$obj->shipping_city = sanitize_text_field( $response->shipping_city );
		}

		if ( property_exists( $response, 'shipping_state' ) ) {
			$obj->shipping_state = sanitize_text_field( $response->shipping_state );
		}

		if ( property_exists( $response, 'payment_method' ) ) {
			$obj->payment_method = sanitize_text_field( $response->payment_method );
		}

		if ( property_exists( $response, 'order_total' ) ) {
			$obj->order_total = sanitize_text_field( $response->order_total );
		}

		if ( property_exists( $response, 'completed_date' ) ) {
			$obj->completed_date = convert_datetime( $response->completed_date, FALSE );
		}

		if ( property_exists( $response, 'sales_sub' ) && is_array( $response->sales_sub ) ) {

			foreach ( $response->sales_sub as &$sub ) {

				if ( $sub instanceof \stdClass ) {
					$obj->sales_sub[] = Sales_Subs_Model::from_response( $sub );
				}
			}
		}

		return $obj;
	}

	/** @return Domain */
	public function get_domain() {

		return $this->domains_id;
	}

	/**
	 * @return \DateTime
	 */
	public function get_order_date() {

		return $this->order_date;
	}

	/**
	 * @return mixed
	 */
	public function get_post_status() {

		return $this->post_status;
	}

	/**
	 * @return string
	 */
	public function get_order_currency() {

		return $this->order_currency;
	}

	/**
	 * @return string
	 */
	public function get_customer_user_agent() {

		return $this->customer_user_agent;
	}

	/**
	 * @return int
	 */
	public function get_customer_user() {

		return $this->customer_user;
	}

	/**
	 * @return string
	 */
	public function get_created_via() {

		return $this->created_via;
	}

	/**
	 * @return string
	 */
	public function get_order_version() {

		return $this->order_version;
	}

	/**
	 * @return string
	 */
	public function get_billing_country() {

		return $this->billing_country;
	}

	/**
	 * @return string
	 */
	public function get_billing_city() {

		return $this->billing_city;
	}

	/**
	 * @return string
	 */
	public function get_billing_state() {

		return $this->billing_state;
	}

	/**
	 * @return string
	 */
	public function get_shipping_country() {

		return $this->shipping_country;
	}

	/**
	 * @return string
	 */
	public function get_shipping_city() {

		return $this->shipping_city;
	}

	/**
	 * @return string
	 */
	public function get_shipping_state() {

		return $this->shipping_state;
	}

	/**
	 * @return string
	 */
	public function get_payment_method() {

		return $this->payment_method;
	}

	/**
	 * @return string
	 */
	public function get_order_total() {

		return $this->order_total;
	}

	/**
	 * @return \DateTime
	 */
	public function get_completed_date() {

		return $this->completed_date;
	}

	/**
	 * @return array
	 */
	public function get_sales_sub() {

		return $this->sales_sub;
	}
}


final class Sales_Subs_Model implements APIResponseHandler {

	/** @var int $order_item_id */
	private $order_item_id;

	/** @var string $order_item_name */
	private $order_item_name;

	/** @var string $order_item_type */
	private $order_item_type;

	/** @var int $order_id */
	private $order_id;

	/** @var int $qty */
	private $qty;

	/** @var int $product_id */
	private $product_id;

	/** @var int $variation_id */
	private $variation_id;

	/** @var string $line_subtotal 소수점 문제가 있을 수 있으므로 문자열 그대로 처리 */
	private $line_subtotal;

	/** @var string $line_total 소수점 문제가 있을 수 있으므로 문자열 그대로 처리 */
	private $line_total;

	/**
	 * @param \stdClass $response
	 *
	 * @return Sales_Subs_Model
	 */
	public static function from_response( \stdClass $response ) {

		$obj = new static();

		if ( property_exists( $response, 'order_item_id' ) ) {
			$obj->order_item_id = absint( $response->order_item_id );
		}

		if ( property_exists( $response, 'order_item_name' ) ) {
			$obj->order_item_name = sanitize_text_field( $response->order_item_name );
		}

		if ( property_exists( $response, 'order_item_type' ) ) {
			$obj->order_item_type = sanitize_text_field( $response->order_item_type );
		}

		if ( property_exists( $response, 'order_id' ) ) {
			$obj->order_id = absint( $response->order_id );
		}

		if ( property_exists( $response, 'qty' ) ) {
			$obj->qty = absint( $response->qty );
		}

		if ( property_exists( $response, 'product_id' ) ) {
			$obj->product_id = absint( $response->product_id );
		}

		if ( property_exists( $response, 'variation_id' ) ) {
			$obj->variation_id = absint( $response->variation_id );
		}

		if ( property_exists( $response, 'line_subtotal' ) ) {
			$obj->line_subtotal = sanitize_text_field( $response->line_subtotal );
		}

		if ( property_exists( $response, 'line_total' ) ) {
			$obj->line_total = sanitize_text_field( $response->line_total );
		}

		return $obj;
	}

	/**
	 * @return int
	 */
	public function get_order_item_id() {

		return $this->order_item_id;
	}

	/**
	 * @return string
	 */
	public function get_order_item_name() {

		return $this->order_item_name;
	}

	/**
	 * @return string
	 */
	public function get_order_item_type() {

		return $this->order_item_type;
	}

	/**
	 * @return int
	 */
	public function get_order_id() {

		return $this->order_id;
	}

	/**
	 * @return int
	 */
	public function get_qty() {

		return $this->qty;
	}

	/**
	 * @return int
	 */
	public function get_product_id() {

		return $this->product_id;
	}

	/**
	 * @return int
	 */
	public function get_variation_id() {

		return $this->variation_id;
	}

	/**
	 * @return string
	 */
	public function get_line_subtotal() {

		return $this->line_subtotal;
	}

	/**
	 * @return string
	 */
	public function get_line_total() {

		return $this->line_total;
	}
}


final class ProductLogs implements APIResponseHandler {

	public $user_id;

	public $domain;

	public $created;

	public $customer_id;

	public $product_id;

	public $variation_id;

	public $quantity;

	public $price;

	public $product_name;

	public $product_version;

	public $term_name;

	public $log_type;

	public static function from_response( \stdClass $response ) {

		$obj = new static();

		if ( property_exists( $response, 'user_id' ) ) {
			$obj->user_id = absint( $response->user_id );
		}

		if ( property_exists( $response, 'domain' ) && $response->domain instanceof \stdClass ) {
			$obj->domain = Domain::from_response( $response->domain );
		}

		if ( property_exists( $response, 'created' ) ) {
			$obj->created = convert_datetime( $response->created, FALSE );
		}

		if ( property_exists( $response, 'customer_id' ) ) {
			$obj->customer_id = absint( $response->customer_id );
		}

		if ( property_exists( $response, 'product_id' ) ) {
			$obj->product_id = absint( $response->product_id );
		}

		if ( property_exists( $response, 'variation_id' ) ) {
			$obj->variation_id = absint( $response->variation_id );
		}

		if ( property_exists( $response, 'quantity' ) ) {
			$obj->quantity = absint( $response->quantity );
		}

		if ( property_exists( $response, 'price' ) ) {
			$obj->price = sanitize_text_field( $response->price );
		}

		if ( property_exists( $response, 'product_name' ) ) {
			$obj->product_name = sanitize_text_field( $response->product_name );
		}

		if ( property_exists( $response, 'product_version' ) ) {
			$obj->product_version = sanitize_text_field( $response->product_version );
		}

		if ( property_exists( $response, 'term_name' ) ) {
			$obj->term_name = sanitize_text_field( $response->term_name );
		}

		if ( property_exists( $response, 'log_type' ) ) {
			$obj->log_type = sanitize_text_field( $response->log_type );
		}

		return $obj;
	}
}

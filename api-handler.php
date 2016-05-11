<?php
namespace CassandraPHP;

require_once 'auth-models.php';
require_once 'sales-models.php';
require_once 'compatibility-functions.php';

define( 'WSKL_HOST_API_URL', 'https://www.dabory.com/cassandra/api/v1' );  // do not add slashes
define( 'WSKL_ALTERNATE_HOST_API_URL', 'http://www.dabory.com/cassandra/api/v1' );

/**
 * Prepared for CloudFlare Flexible SSL.
 *
 * 클라우드플레어에서 제공해주는 SSL 서비스를 이용하면 인증서 작업 없이도 간단하게 SSL 접속이 가능한데,
 * 서버 외부에서 접속하는 경우에는 큰 문제가 없다. 그러나 서버 내부에서 API 호출을 할 때에는 약간의 장애가 생간다.
 *
 * 일반 80 포트의 HTTP 환경 설정만으로도 HTTPS 를 가상적으로 호출해 주는 것이다보니, 사실 서버 내부는 HTTPS 443 포트에 대해
 * LISTEN 하지 않는데다가, 사실상 local server 를 대상으로 접속하니 인증서 문제도 발생할 수 있다.
 *
 * 이에 www.dabory.com 이라는 도메인의 IP가 로컬인 127.0.0.1 로 발견되는 경우에는 HTTP 로 호출을 하도록 결정한다.
 *
 * @return mixed|string|void
 */
function get_host_api_url() {

	$cassandra_ip_address = get_option( 'wskl_cassandra_ip_address', '' );
	$override_url         = get_option( 'wskl_develop_cassandra_url' );

	if ( empty( $cassandra_ip_address ) ) {
		$hostname = parse_url( WSKL_HOST_API_URL, PHP_URL_HOST );
		if ( $hostname ) {
			$cassandra_ip_address = gethostbyname( $hostname );
			update_option( 'wskl_cassandra_ip_address', $cassandra_ip_address );
		}
	}

	if ( wskl_debug_enabled() && ! empty( $override_url ) ) {
		return $override_url;
	}

	if ( $cassandra_ip_address == '127.0.0.1' ) {
		return WSKL_ALTERNATE_HOST_API_URL;
	}

	return WSKL_HOST_API_URL;
}


/**
 * Class BadResponseException
 *
 * 의도하지 않은 response 를 접수한 경우 발생하는 예외.
 *
 * @package wskl\libs\cassandra
 */
class BadResponseException extends \Exception {

	function handle_bad_response( $method, $extra_message = '', $die = FALSE ) {

		$message = sprintf(
			'Method %s(): Bad response occurred. Message: "%s%s%s"',
			$method,
			( empty( $extra_message ) ? '' : ' ' ),
			$extra_message,
			$this->getMessage()
		);
		error_log( $message );
		if ( $die ) {
			wp_die( $message );
		}
	}
}


/**
 * Class Rest_Api_Helper
 *
 * @package CassandraPHP
 */
class Rest_Api_Helper {

	/**
	 * @param string $url
	 * @param string $method
	 * @param mixed  $body
	 * @param array  $accepts Response code that regarded as success.
	 * @param array  $headers
	 *
	 * @return array 'code', and 'body' keys are present.
	 * @throws \CassandraPHP\BadResponseException
	 */
	public static function request(
		$url,
		$method,
		$body = NULL,
		array $accepts = array( 200, ),
		array $headers = array()
	) {

		$args = array(
			'headers' => &$headers,
			'method'  => strtoupper( $method ),
			'body'    => &$body,
		);

		/** @var \WP_Error|array $response */
		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			$message = $response->get_error_message();
			throw new BadResponseException( $message );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( array_search( $response_code, $accepts ) === FALSE ) {
			$message = sprintf( "Invalid response code '%s', message: %s", $response_code, $response_body );
			throw new BadResponseException( $message );
		}

		$content_type = wp_remote_retrieve_header( $response, 'content-type' );
		if ( $content_type == 'application/json' ) {
			$response_body = json_decode( $response_body, FALSE );
		}

		return array(
			'code' => $response_code,
			'body' => $response_body,
		);
	}
}


class ClientAPI {

	public static function activate(
		$key_type,
		$key_value,
		$site_url,
		$company_name = '',
		$activate = FALSE
	) {

		assert( $key_type && $key_value && $site_url );

		$obj = NULL;

		try {

			$url  = get_host_api_url() . '/auth/activate/';
			$body = array(
				'key_type'     => $key_type,
				'key_value'    => $key_value,
				'site_url'     => $site_url,
				'company_name' => $company_name,
				'activate'     => $activate,
			);

			$response = Rest_Api_Helper::request(
				$url,
				'POST',
				$body,
				array( 200, 403, )
			);

			if ( $response['code'] == 200 ) {
				$obj = OrderItemRelation::from_response( $response['body'] );
			}

		} catch( BadResponseException $e ) {
			$e->handle_bad_response( __METHOD__ );
		}

		return $obj;
	}

	public static function verify( $key_type, $key_value, $site_url ) {

		if ( empty( $key_type ) || empty( $key_value ) ) {
			return NULL;
		};

		$obj = NULL;

		try {

			$url  = get_host_api_url() . '/auth/verify/';
			$body = array(
				'key_type'  => &$key_type,
				'key_value' => &$key_value,
				'site_url'  => &$site_url,
			);

			$response = Rest_Api_Helper::request(
				$url,
				'POST',
				$body,
				array( 200, 403, )
			);

			assert( isset( $response['code'] ) && isset( $response['body'] ) );

			if ( $response['code'] == 200 ) {
				$obj = OrderItemRelation::from_response( $response['body'] );
			}

		} catch( BadResponseException $e ) {

			$e->handle_bad_response( __METHOD__ );

			$obj = FALSE;
		}

		return $obj;
	}
}


class SalesAPI {

	public static function send_data(
		$key_type,
		$key_value,
		$site_url,
		$user_id,
		$order
	) {

		$obj = NULL;

		try {

			$url  = get_host_api_url() . '/logs/sales/';
			$body = json_encode(
				static::create_body(
					$key_type,
					$key_value,
					$site_url,
					$user_id,
					$order
				)
			);

			$headers = array( 'content-type' => 'application/json', );

			$response = Rest_Api_Helper::request(
				$url,
				'POST',
				$body,
				array( 201, ),
				$headers
			);

			$obj = Sales_Model::from_response( $response['body'] );

		} catch( BadResponseException $e ) {
			$e->handle_bad_response( __METHOD__ );
		}

		return $obj;
	}

	/**
	 * @param string $key_type
	 * @param string $key_value
	 * @param string $site_url
	 * @param string $user_id
	 * @param mixed  $order
	 *
	 * @return array
	 */
	private static function create_body(
		$key_type,
		$key_value,
		$site_url,
		$user_id,
		$order
	) {

		/** @var \WC_Order $order */
		$order = wc_get_order( $order );

		assert( $order instanceof \WC_Order, 'Sale object creation failed: $order is not a \WC_Order object.' );

		/** @noinspection PhpUndefinedFieldInspection */
		$body = array(
			'key_type'            => $key_type,
			'key_value'           => $key_value,
			'site_url'            => $site_url,
			'user_id'             => $user_id,
			// Casper's User ID
			'order_id'            => $order->id,
			'order_date'          => $order->order_date,
			'post_status'         => $order->post_status,
			'order_currency'      => $order->order_currency,
			'customer_user_agent' => $order->customer_user_agent,
			'customer_user'       => $order->customer_user,
			'created_via'         => $order->created_via,
			'order_version'       => $order->order_version,
			'billing_country'     => $order->billing_country,
			'billing_city'        => $order->billing_city,
			'billing_state'       => $order->billing_state,
			'shipping_country'    => $order->shipping_country,
			'shipping_city'       => $order->shipping_city,
			'shipping_state'      => $order->shipping_state,
			'payment_method'      => $order->payment_method,
			'order_total'         => $order->order_total,
			'completed_date'      => $order->completed_date,
			'sales_sub'           => array(),
		);

		$sales_sub = &$body['sales_sub'];
		$items     = $order->get_items();
		foreach ( $items as $order_item_id => &$item ) {

			$sales_sub[] = array(
				'order_item_id'   => $order_item_id,
				'order_item_name' => $item['name'],
				'order_item_type' => $item['type'],
				'order_id'        => $order->id,
				'qty'             => $item['qty'],
				'product_id'      => $item['product_id'],
				'variation_id'    => $item['variation_id'],
				'line_subtotal'   => $item['line_subtotal'],
				'line_total'      => $item['line_total'],
			);
		}

		return $body;
	}
}


abstract class ProductLogAPI {

	public static function send_data(
		$url,
		$key_type,
		$key_value,
		$site_url,
		$user_id,
		$product_id,
		$quantity,
		$variation_id = 0
	) {

		$obj = NULL;

		try {

			$body    = json_encode(
				static::create_body(
					$key_type,
					$key_value,
					$site_url,
					$user_id,
					$product_id,
					$quantity,
					$variation_id
				)
			);
			$headers = array( 'content-type' => 'application/json', );

			$response = Rest_Api_Helper::request(
				$url,
				'POST',
				$body,
				array( 201, ),
				$headers
			);
			$obj      = ProductLogs::from_response( $response['body'] );

		} catch( BadResponseException $e ) {
			$e->handle_bad_response( __METHOD__ );
		}

		return $obj;
	}

	private static function create_body(
		$key_type,
		$key_value,
		$site_url,
		$user_id,
		$product_id,
		$quantity,
		$variation_id
	) {

		/** @var \WC_Product $product */
		$product = wc_get_product( $product_id );
		assert(
			$product instanceof \WC_Product,
			'Product object retrieval failed: $product is not a \WC_Product.'
		);

		$terms = wp_get_post_terms( $product_id, 'product_cat' );
		if ( is_array( $terms ) ) {
			$term_names = array_map(
				function ( $t ) { return $t->name; },
				$terms
			);
			sort( $term_names );
			$term_name = join( '|', $term_names );
		} else {
			$term_name = '';
		}

		/** @noinspection PhpUndefinedFieldInspection */
		$body = array(
			'key_type'        => $key_type,
			'key_value'       => $key_value,
			'site_url'        => $site_url,
			'user_id'         => (int) $user_id,    // Casper's User ID
			'customer_id'     => get_current_user_id(),
			'product_id'      => (int) $product_id,
			'variation_id'    => (int) $variation_id,
			'quantity'        => (int) $quantity,
			'product_name'    => $product->get_title(),
			'price'           => $product->get_price(),
			'product_version' => $product->product_version,
			'term_name'       => $term_name,
		);

		return $body;
	}
}


class AddToCartAPI extends ProductLogAPI {

	public static function send_data(
		$key_type,
		$key_value,
		$site_url,
		$user_id,
		$product_id,
		$quantity,
		$variation_id = 0
	) {

		return parent::send_data(
			get_host_api_url() . '/logs/add-to-carts/',
			$key_type,
			$key_value,
			$site_url,
			$user_id,
			$product_id,
			$quantity,
			$variation_id
		);
	}
}


class TodaySeenAPI extends ProductLogAPI {

	public static function send_data(
		$key_type,
		$key_value,
		$site_url,
		$user_id,
		$product_id,
		$quantity,
		$variation_id = 0
	) {

		return parent::send_data(
			get_host_api_url() . '/logs/today-seen/',
			$key_type,
			$key_value,
			$site_url,
			$user_id,
			$product_id,
			$quantity,
			$variation_id
		);
	}
}


class WishListAPI extends ProductLogAPI {

	public static function send_data(
		$key_type,
		$key_value,
		$site_url,
		$user_id,
		$product_id,
		$quantity,
		$variation_id = 0
	) {

		return parent::send_data(
			get_host_api_url() . '/logs/wish-lists/',
			$key_type,
			$key_value,
			$site_url,
			$user_id,
			$product_id,
			$quantity,
			$variation_id
		);
	}
}


class PostAPI {

	public static function send_post(
		$key_type,
		$key_value,
		$site_url,
		$user_id,
		$post_id
	) {

		assert( $key_type && $key_value && $site_url );

		$casper_post_id = NULL;

		try {

			$url = get_host_api_url() . '/posts/';

			$body = array_merge(
				array(
					'key_type'  => $key_type,
					'key_value' => $key_value,
					'site_url'  => $site_url,
					'user_id'   => $user_id,
				),
				static::create_post_field( $post_id )
			);

			$response = Rest_Api_Helper::request(
				$url,
				'POST',
				$body,
				array( 201, )
			);

			$casper_post_id = $response['body']->id;

		} catch( BadResponseException $e ) {
			$e->handle_bad_response( __METHOD__ );
		}

		return $casper_post_id;
	}

	private static function create_post_field( $post_id ) {

		$post = get_post( $post_id, ARRAY_A );

		$post['post_id'] = $post['ID'];
		unset( $post['ID'] );

		$post['postmeta'] = serialize( get_post_meta( $post_id, '', TRUE ) );

		return $post;
	}
}


/***
 * Class IssueAPI
 *
 * 키 발급과 관련된 API 클래스
 *
 * @package casper\libs\cassandra
 */
class IssueAPI {

	/**
	 * 키 발급 API 호출.
	 *
	 * @param integer $order_item_id woocommerce_order_items.order_item_id
	 * @param string  $key_type      발급할 키의 타입
	 * @param integer $user_id       wp_users.ID
	 * @param string  $duration      유효 기간
	 * @param null    $issue_date    발급 일자. NULL 일 경우 오늘.
	 *
	 * @return null|OrderItemRelation 성공하는 경우 OrderItemRelation, 실패하는 경우 NULL 을
	 *                                리턴.
	 */
	public static function issue( $order_item_id, $key_type, $user_id, $duration, $issue_date = NULL ) {

		$obj = NULL;

		try {

			$url  = get_host_api_url() . '/auth/issue/';
			$body = array(
				'order_item_id' => $order_item_id,
				'user_id'       => $user_id,
				'key_type'      => $key_type,
				'duration'      => $duration,
				'issue_date'    => $issue_date,
			);

			$response = Rest_Api_Helper::request(
				$url,
				'POST',
				$body,
				array( 201, )
			);

			$obj = OrderItemRelation::from_response( $response['body'] );

		} catch( BadResponseException $e ) {
			$e->handle_bad_response( __METHOD__ );
		}

		return $obj;
	}
}


/**
 * Class OrderItemAPI
 *
 * OrderItemRelation 관련 API 호출 클래스
 *
 * @package casper\libs\cassandra
 */
class OrderItemAPI {

	/**
	 * OrderItemRelation 목록
	 *
	 * @return array
	 * @throws BadResponseException
	 */
	public static function get_list() {

		$url    = get_host_api_url() . '/auth/order-items/';
		$output = array();

		try {
			$response = Rest_Api_Helper::request( $url, 'GET' );
			if ( property_exists( $response['body'], 'next' ) && property_exists( $response['body'], 'results' ) ) {
				$output = OrderItemRelation::from_response_list( $response['body']->results );
			}

		} catch( BadResponseException $e ) {
			$e->handle_bad_response( __METHOD__ );
		}

		// TODO: pagination 을 고려하지 않았다. 이를 고려할 수 있도록 수정이 필요.
		return $output;
	}

	/**
	 * 모든 OrderItemRelation 목록을 가져움.
	 *
	 * @return array
	 */
	public static function get_all_list() {

		$url = get_host_api_url() . '/auth/order-items/';

		$output = array();

		try {

			$response = Rest_Api_Helper::request( $url, 'GET' );

			do {
				if ( property_exists( $response['body'], 'next' ) && property_exists( $response['body'], 'results' ) ) {
					$bunch  = OrderItemRelation::from_response_list( $response['body']->results );
					$output = array_merge( $output, $bunch );

					$url = $response['body']->next;
					if ( $url ) {
						$response = Rest_Api_Helper::request( $url, 'GET' );
					}
				} else {
					break;
				}
			} while ( $url );

		} catch( BadResponseException $e ) {
			$e->handle_bad_response( __METHOD__ );
		}

		return $output;
	}

	/**
	 * 조금 더 편하게 WC_Order 객체만으로 정보를 가져 올 수 있도록 포장.
	 *
	 * @param \WC_Order $order
	 *
	 * @see   OrderItemAPI::get()
	 *
	 * @return array|null|OrderItemRelation
	 */
	public static function get_from_wc_order( \WC_Order $order ) {

		assert( $order->get_item_count() == 1, 'We allow only one item per order.' );

		$response = array();

		foreach ( $order->get_items() as $order_item_id => &$order_item ) {
			assert( $order_item['qty'] == 1, 'We allow only one item per order.' );
			try {
				$response = static::get( $order_item_id );
			} catch( BadResponseException $e ) {
				$e->handle_bad_response( __METHOD__ );
				$response = NULL;
			}
		}

		return $response;
	}

	/**
	 * $order_item_id 의 OrderItemRelation 정보를 가져 옴.
	 *
	 * @param $order_item_id
	 *
	 * @return null|OrderItemRelation 찾으면 OrderItemRelation, 아니면 NULL 을 리턴.
	 */
	public static function get( $order_item_id ) {

		$oid = absint( $order_item_id );
		assert( $oid > 0, '`$order_item_id` must be a positive integer.' );

		$obj = NULL;

		try {

			$url      = get_host_api_url() . "/auth/order-items/{$oid}/";
			$response = Rest_Api_Helper::request( $url, 'GET', array( 200, 404, ) );

			// Cassandra can often respond 404.
			if ( $response['code'] == 200 ) {
				$obj = OrderItemRelation::from_response( $response['body'] );
			}

		} catch( BadResponseException $e ) {

			$order_id = casper_get_order_id_by_order_item_id( $oid );
			$message  = sprintf( 'Order ID: %s', $order_id );
			$e->handle_bad_response( __METHOD__, $message );

			$obj = NULL;
		}

		return $obj;
	}

	/**
	 * 한 사용자의 모든 키 목록을 가져 옴.
	 *
	 * @param $user_id
	 *
	 * @return array
	 */
	public static function get_by_user( $user_id ) {

		$uid = absint( $user_id );
		assert( $uid !== FALSE, '`$user_id` is not a valid integer.' );

		$url    = get_host_api_url() . "/auth/order-items/user/{$uid}/";
		$output = array();

		try {

			$response = Rest_Api_Helper::request( $url, 'GET' );

			do {
				if ( property_exists( $response['body'], 'next' ) && property_exists( $response['body'], 'results' )
				) {
					$bunch  = OrderItemRelation::from_response_list( $response['body']->results );
					$output = array_merge( $output, $bunch );

					$url = $response['body']->next;

					if ( $url ) {
						$response = Rest_Api_Helper::request( $url, 'GET' );
					}
				} else {
					break;
				}
			} while ( $url );

		} catch( BadResponseException $e ) {
			$e->handle_bad_response( __METHOD__ );
		}

		return $output;
	}

	/**
	 * OrderItemRelation 을 삭제함(키는 삭제하지 않음)
	 *
	 * @param $order_item_id
	 *
	 * @return bool
	 */
	public static function delete( $order_item_id ) {


		$order_item_id = absint( $order_item_id );
		assert( $order_item_id !== FALSE, '`$order_item_id` is not a valid integer.' );

		try {
			$url      = get_host_api_url() . "/order-items/{$order_item_id}/";
			$response = Rest_Api_Helper::request( $url, 'DELETE', array(), array( 204, 404, ) );

			// Cassandra can often respond 404.
			if ( $response['code'] == 204 ) {
				return TRUE;
			}
		} catch( BadResponseException $e ) {
			$e->handle_bad_response( __METHOD__ );
		}

		return FALSE;
	}
}

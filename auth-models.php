<?php
namespace CassandraPHP;

require_once 'abstract-models.php';


/**
 * Class Domain
 */
final class Domain extends CreatedUpdatedMixin implements APIResponseHandler {

	/**
	 * @var string 회사 이름. 선택 사항.
	 */
	private $company_name;

	/**
	 * @var string 회사 URL. 필수 사항.
	 */
	private $url;

	/**
	 * @var \DateTime|NULL. 도메인 비활성화된 시간. NULL 이면 활성화되어 있음.
	 */
	private $deactivated;

	/**
	 * Mapping method. Rest API 호출 후 전달되는 JSON 은 parse 되어 stdClass 로 변환된다.
	 * 이 때 stdClass 는 사용하기 매우 불안정하므로 Domain 부분은 Domain class 와 매핑시킨다.
	 *
	 * @param \stdClass $response
	 *
	 * @return Domain
	 */
	public static function from_response( \stdClass $response ) {

		$obj = new static();

		$obj->company_name = esc_textarea( $response->company_name );
		$obj->url          = esc_url( $response->url );

		if ( $response->deactivated ) {
			$obj->deactivated = static::convert_datetime( $response->deactivated );
		} else {
			$obj->deactivated = NULL;
		}

		$obj->set_created( $response->created );
		$obj->set_updated( $response->updated );

		return $obj;
	}

	/**
	 * @return string company_name getter
	 */
	public function get_company_name() {

		return $this->company_name;
	}

	/**
	 * @return string url getter
	 */
	public function get_url() {

		return $this->url;
	}

	/**
	 * @return bool
	 */
	public function is_deactivated() {

		return $this->deactivated != NULL;
	}

	/**
	 * @return \DateTime|NULL deactivated getter
	 */
	public function get_deactivated() {

		return $this->deactivated;
	}
}


/**
 * Class Key
 */
final class Key extends CreatedUpdatedMixin implements APIResponseHandler {

	/**
	 * @var string 키 정보
	 */
	private $key;

	/**
	 * @var string. Because it is too typical, that it is substituted to a string. Originally, it is a foreign key.
	 */
	private $type;

	/**
	 * @var boolean 활성화 여부
	 */
	private $_is_active;

	/**
	 * @var \DateTime It is originally a 'date' class. Its time is always 00:00:00 in KST.
	 */
	private $issue_date;

	/**
	 * @var \DateTime It is originally a 'date' class. Its time is always 23:59:59 in KST.
	 */
	private $expire_date;

	/**
	 * Mapping method. Rest API 호출 후 전달되는 JSON 은 parse 되어 stdClass 로 변환된다.
	 * 이 때 stdClass 는 사용하기 매우 불안정하므로 Key class 와 매핑시킨다.
	 *
	 * @param \stdClass $response
	 *
	 * @return Key
	 */
	public static function from_response( \stdClass $response ) {

		$obj = new static();

		$obj->key        = esc_textarea( $response->key );
		$obj->type       = esc_textarea( $response->type->type );
		$obj->_is_active = filter_var( $response->is_active, FILTER_VALIDATE_BOOLEAN );
		$obj->issue_date = static::convert_datetime( $response->issue_date );

		$obj->expire_date = static::convert_datetime( $response->expire_date );
		$obj->expire_date->setTime( 23, 59, 59 );

		$obj->set_created( $response->created );
		$obj->set_updated( $response->updated );

		return $obj;
	}

	/**
	 * @return string key getter
	 */
	public function get_key() {

		return $this->key;
	}

	/**
	 * @return string type getter
	 */
	public function get_type() {

		return $this->type;
	}

	/**
	 * @return bool is_active
	 */
	public function is_active() {

		return $this->_is_active;
	}

	/**
	 * @return \DateTime issue_date getter
	 */
	public function get_issue_date() {

		return $this->issue_date;
	}

	/**
	 * @return \DateTime expire_date getter
	 */
	public function get_expire_date() {

		return $this->expire_date;
	}
}


/**
 * Class OrderItemRelation
 */
final class OrderItemRelation implements APIResponseHandler {

	/**
	 * @var integer
	 */
	private $order_item_id;

	/**
	 * @var Key|integer
	 */
	private $key;

	/**
	 * @var Domain|integer
	 */
	private $domain;

	/**
	 * @var integer
	 */
	private $user_id;

	public static function from_response_list( array &$response ) {

		$output = array();

		foreach ( $response as &$elem ) {
			$output[] = static::from_response( $elem );
		}

		return $output;
	}

	/**
	 * @param \stdClass $response
	 *
	 * @return OrderItemRelation
	 */
	public static function from_response( \stdClass $response ) {

		$obj = new static();

		$obj->order_item_id = absint( $response->order_item_id );

		if ( is_numeric( $response->key ) ) {

			$obj->key = absint( $response->key );
			assert( $obj->key !== FALSE, '$response->key assertion failed.' );

		} else if ( $response->key instanceof \stdClass ) {

			$obj->key = Key::from_response( $response->key );

		} else {

			assert( FALSE, 'unknown $response->key structure.' );
		}

		if ( is_numeric( $response->domain ) ) {

			$obj->domain = absint( $response->domain );
			assert( $obj->domain !== FALSE, '$response->domain assertion failed.' );

		} else if ( $response->domain instanceof \stdClass ) {

			$obj->domain = Domain::from_response( $response->domain );

		} else if ( $response->domain === NULL ) {

			// It can be null. Ok, do nothing.
			$obj->domain = NULL;

		} else {

			assert( FALSE, 'unknown $response->domain structure.' );
		}

		$obj->user_id = absint( $response->user_id );
		assert( $obj->user_id !== FALSE, '$response->user_id assertion failed.' );

		return $obj;
	}

	public function get_order_item_id() {

		return $this->order_item_id;
	}

	public function get_key() {

		return $this->key;
	}

	public function get_domain() {

		return $this->domain;
	}

	public function get_user_id() {

		return $this->user_id;
	}
}

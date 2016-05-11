<?php

namespace CassandraPHP;

if ( ! defined( 'CASSANDRA_DEFAULT_TIMEZONE' ) ) {
	define( 'CASSANDRA_DEFAULT_TIMEZONE', 'Asia/Seoul' );
}

/**
 * response 로부터 오는 텍스트나 아니면 \DateTime 객체를 안전하게 \DateTime 객체로 리턴.
 *
 * @param mixed  $datetime
 * @param string $timezone
 * @param bool   $correct_timezone
 *
 * @return \DateTime
 */
function convert_datetime( $datetime, $correct_timezone = TRUE, $timezone = CASSANDRA_DEFAULT_TIMEZONE ) {

	static $tz = NULL;

	if ( ! $tz ) {
		$tz = new \DateTimeZone( $timezone );
	}

	$obj = FALSE;

	if ( is_string( $datetime ) ) {

		if ( $correct_timezone ) {
			$obj = new \DateTime( $datetime, $tz );
		} else {
			$obj = new \DateTime( $datetime );
		}
	}

	if ( $datetime instanceof \DateTime ) {

		$obj = clone $datetime;

		if ( $correct_timezone && $tz != $obj->getTimezone() ) {
			$obj->setTimezone( $tz );
		}
	}

	assert( $obj instanceof \DateTime, sprintf( '$obj is not a \DateTime object' ) );

	return $obj;
}


/**
 * Interface APIResponseHandler
 *
 * @package casper\libs\cassandra
 */
interface APIResponseHandler {

	public static function from_response( \stdClass $response );
}


/**
 * Class CreatedMixin
 */
class CreatedMixin {

	/**
	 * @var \DateTime 생성일자.
	 */
	private $created;

	/**
	 * @return \DateTime created getter
	 */
	public function get_created() {

		return $this->created;
	}

	/**
	 * @param $created mixed created setter
	 *
	 * @see   CreatedMixin::convert_datetime()
	 */
	protected function set_created( $created ) {

		$this->created = static::convert_datetime( $created );
	}

	/**
	 * response 로부터 오는 텍스트나 아니면 \DateTime 객체를 안전하게 \DateTime 객체로 리턴.
	 *
	 * @param $datetime mixed
	 *
	 * @return \DateTime
	 */
	protected static function convert_datetime( $datetime ) {

		return \CassandraPHP\convert_datetime( $datetime );
	}
}


/**
 * Class CreatedUpdatedMixin
 */
class CreatedUpdatedMixin extends CreatedMixin {

	/**
	 * @var \DateTime 수정일자.
	 */
	private $updated;

	/**
	 * @return \DateTime updated getter
	 */
	public function get_updated() {

		return $this->updated;
	}

	/**
	 * @param $updated mixed 문자열이나 \DateTime 객체.
	 *
	 * @see   CreatedMixin::convert_datetime()
	 */
	protected function set_updated( $updated ) {

		$this->updated = static::convert_datetime( $updated );
	}
}

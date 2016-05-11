<?php
/**
 * Functions for compatibility issues
 */

/** only if WSKL Plugin does not exist */
if ( ! function_exists( 'wskl_debug_enabled' ) ) {
	function wskl_debug_enabled() {

		return WP_DEBUG && defined( 'WSKL_DEBUG' ) && WSKL_DEBUG;
	}
}

if ( ! function_exists( 'casper_get_order_id_by_order_item_id' ) ) {

	/**
	 * @param $order_item_id
	 *
	 * @return int
	 */
	function casper_get_order_id_by_order_item_id( $order_item_id ) {

		$oid      = absint( $order_item_id );
		$order_id = 0;

		if ( $oid ) {

			/** @var \wpdb $wpdb */
			global $wpdb;

			/** @noinspection SqlResolve */
			$order_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT `order_id` FROM `{$wpdb->prefix}woocommerce_order_items` WHERE `order_item_id`=%d",
					$oid
				)
			);
		}

		return $order_id;
	}
}

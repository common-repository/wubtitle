<?php
/**
 * In this file is implemented the cancel subscription request.
 *
 * @author     Nicola Palermo
 * @since      0.1.0
 * @package    Wubtitle\Api
 */

namespace Wubtitle\Api;

use Wubtitle\Loader;

/**
 * This class describes the cancel subscription request.
 */
class ApiCancelSubscription {
	/**
	 * Init class actions
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'wp_ajax_cancel_subscription', array( $this, 'remote_request' ) );
	}

	/**
	 * Endpoint call to unsubscribe.
	 *
	 * @return void
	 */
	public function remote_request() {
		if ( ! isset( $_POST['_ajax_nonce'] ) ) {
			wp_send_json_error( __( 'No Nonce', 'wubtitle' ) );
		} elseif ( ! isset( $_POST['action'] ) ) {
			wp_send_json_error( __( 'No Cancel', 'wubtitle' ) );
		} elseif ( ! check_ajax_referer( 'itr_ajax_nonce', sanitize_text_field( wp_unslash( $_POST['_ajax_nonce'] ) ) ) ) {
			wp_send_json_error( __( 'Invalid', 'wubtitle' ) );
		}

		$license_key = get_option( 'wubtitle_license_key' );

		$response = wp_remote_post(
			WUBTITLE_ENDPOINT . 'stripe/customer/unsubscribe',
			array(
				'method'  => 'POST',
				'headers' => array(
					'Content-Type' => 'application/json; charset=utf-8',
					'licenseKey'   => $license_key,
					'domainUrl'    => get_option( 'siteurl' ),
				),
			)
		);

		$code_response = wp_remote_retrieve_response_code( $response );

		$message = array(
			'200' => __( 'Deleted successfully', 'wubtitle' ),
			'400' => __( 'Bad Request. Please try again in a few minutes', 'wubtitle' ),
			'401' => __( 'Unauthorized', 'wubtitle' ),
			'403' => __( 'Forbidden', 'wubtitle' ),
			'404' => __( 'Not Found', 'wubtitle' ),
			'500' => __( 'Internal server error', 'wubtitle' ),
			'502' => __( 'Bad gateway', 'wubtitle' ),
		);
		if ( 200 === $code_response ) {
			Loader::get( 'cron' )->get_remote_data();
		}
		wp_send_json_success( $message[ $code_response ] );
	}


}

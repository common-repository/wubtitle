<?php
/**
 * In this file is implemented stripe related functions.
 *
 * @author     Alessio Catania
 * @since      1.0.0
 * @package    Wubtitle\Api
 */

namespace Wubtitle\Api;

use Wubtitle\Loader;

/**
 * This class implements stripe related functions.
 */
class ApiPricingPlan {
	/**
	 * Init delle action
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'wp_ajax_reactivate_plan', array( $this, 'reactivate_plan' ) );
		add_action( 'wp_ajax_change_plan', array( $this, 'change_plan' ) );
		add_action( 'wp_ajax_create_subscription', array( $this, 'create_subscription' ) );
		add_action( 'wp_ajax_confirm_subscription', array( $this, 'confirm_subscription' ) );
		add_action( 'wp_ajax_check_plan_change', array( $this, 'check_plan_change' ) );
	}
	/**
	 * Calls the backend endpoint to confirm the plan change.
	 *
	 * @return void
	 */
	public function change_plan() {
		$wanted_plan_rank  = get_option( 'wubtitle_wanted_plan_rank' );
		$all_plans         = get_option( 'wubtitle_all_plans' );
		$wanted_plan       = $all_plans[ $wanted_plan_rank ]['stripe_code'];
		$current_plan_rank = get_option( 'wubtitle_plan_rank' );
		$is_upgrade        = $wanted_plan_rank > $current_plan_rank;
		if ( ! isset( $_POST['_ajax_nonce'] ) || empty( $wanted_plan ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'An error occurred. Please try again in a few minutes.', 'wubtitle' ) );
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST['_ajax_nonce'] ) );
		check_ajax_referer( 'itr_ajax_nonce', $nonce );
		$body        = array(
			'data' => array(
				'planId' => $wanted_plan,
			),
		);
		$license_key = get_option( 'wubtitle_license_key' );
		if ( empty( $license_key ) ) {
			wp_send_json_error( __( 'Unable to create subtitles. The product license key is missing.', 'wubtitle' ) );
		}
		$response      = wp_remote_post(
			WUBTITLE_ENDPOINT . 'stripe/customer/update',
			array(
				'method'  => 'POST',
				'timeout' => 15,
				'headers' => array(
					'licenseKey'   => $license_key,
					'domainUrl'    => get_option( 'siteurl' ),
					'Content-Type' => 'application/json; charset=utf-8',
				),
				'body'    => wp_json_encode( $body ),
			)
		);
		$code_response = $this->is_successful_response( $response ) ? wp_remote_retrieve_response_code( $response ) : '500';
		$message       = array(
			'400' => __( 'An error occurred. Please try again in a few minutes', 'wubtitle' ),
			'401' => __( 'An error occurred. Please try again in a few minutes', 'wubtitle' ),
			'403' => __( 'Access denied', 'wubtitle' ),
			'500' => __( 'Could not contact the server', 'wubtitle' ),
			''    => __( 'Could not contact the server', 'wubtitle' ),
		);
		if ( 200 !== $code_response ) {
			wp_send_json_error( $message[ $code_response ] );
		}
		delete_option( 'wubtitle_wanted_plan_rank' );
		$response_body = json_decode( wp_remote_retrieve_body( $response ) );
		$status        = $response_body->data->status;
		if ( 'requires_action' === $status ) {
			$data = array(
				'paymentMethod' => $response_body->data->paymentMethod,
				'clientSecret'  => $response_body->data->clientSecret,
			);
		}
		$data['status']    = $status;
		$data['isUpgrade'] = $is_upgrade;
		wp_send_json_success( $data );
	}
	/**
	 * Calls the endpoint for plan reactivation.
	 *
	 * @return void
	 */
	public function reactivate_plan() {
		if ( ! isset( $_POST['_ajax_nonce'] ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'An error occurred. Please try again in a few minutes.', 'wubtitle' ) );
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST['_ajax_nonce'] ) );
		check_ajax_referer( 'itr_ajax_nonce', $nonce );
		$license_key = get_option( 'wubtitle_license_key' );
		if ( empty( $license_key ) ) {
			wp_send_json_error( __( 'Unable to create subtitles. The product license key is missing.', 'wubtitle' ) );
		}
		update_option( 'wubtitle_is_reactivating', true );
		$response      = wp_remote_post(
			WUBTITLE_ENDPOINT . 'stripe/customer/reactivate',
			array(
				'method'  => 'POST',
				'headers' => array(
					'licenseKey' => $license_key,
					'domainUrl'  => get_option( 'siteurl' ),
				),
			)
		);
		$code_response = $this->is_successful_response( $response ) ? wp_remote_retrieve_response_code( $response ) : '500';
		$message       = array(
			'400' => __( 'An error occurred. Please try again in a few minutes', 'wubtitle' ),
			'401' => __( 'An error occurred while creating the transcriptions. Please try again in a few minutes', 'wubtitle' ),
			'403' => __( 'Access denied', 'wubtitle' ),
			'500' => __( 'Could not contact the server', 'wubtitle' ),
		);
		if ( 200 !== $code_response ) {
			wp_send_json_error( $message[ $code_response ] );
		}
		wp_send_json_success();
	}
	/**
	 *  Creates request body.
	 *
	 * @param string $pricing_plan pricing plan.
	 * @param string $site_url site url.
	 * @return array<array<string>>|false
	 */
	public function set_body_request( $pricing_plan, $site_url ) {
		if ( ! is_string( $pricing_plan ) || ! filter_var( $site_url, FILTER_VALIDATE_URL ) ) {
			return false;
		}
		return array(
			'data' => array(
				'planId'    => $pricing_plan,
				'domainUrl' => $site_url,
				'siteLang'  => explode( '_', get_locale(), 2 )[0],
			),
		);
	}
	/**
	 * Check if free user.
	 *
	 * @return void
	 */
	public function check_plan_change() {
		if ( get_option( 'wubtitle_free' ) ) {
			wp_send_json_success();
		}
		wp_send_json_success( 'change_plan' );
	}
	/**
	 * Gets the data from JavaScript and sends it to the endpoint.
	 *
	 * @param string $plan_rank rank of plan.
	 * @return string|array<string>
	 */
	public function send_wanted_plan_info( $plan_rank ) {
		$site_url     = get_option( 'siteurl' );
		$all_plans    = get_option( 'wubtitle_all_plans' );
		$pricing_plan = $all_plans[ $plan_rank ]['stripe_code'];
		$body         = $this->set_body_request( $pricing_plan, $site_url );
		if ( ! $body ) {
			return __( 'An error occurred. Please try again in a few minutes.', 'wubtitle' );
		}
		$license_key = get_option( 'wubtitle_license_key' );
		if ( empty( $license_key ) ) {
			return __( 'The product license key is missing.', 'wubtitle' );
		}

		$url_endpoint = WUBTITLE_ENDPOINT . 'stripe/customer/update/preview';
		unset( $body['data']['siteLang'] );
		unset( $body['data']['domainUrl'] );

		$response      = wp_remote_post(
			$url_endpoint,
			array(
				'method'  => 'POST',
				'headers' => array(
					'licenseKey'   => $license_key,
					'domainUrl'    => get_option( 'siteurl' ),
					'Content-Type' => 'application/json; charset=utf-8',
				),
				'body'    => wp_json_encode( $body ),
			)
		);
		$code_response = $this->is_successful_response( $response ) ? wp_remote_retrieve_response_code( $response ) : '500';
		$message       = array(
			'400' => __( 'An error occurred. Please try again in a few minutes', 'wubtitle' ),
			'401' => __( 'An error occurred. Please try again in a few minutes', 'wubtitle' ),
			'403' => __( 'Access denied', 'wubtitle' ),
			'500' => __( 'Could not contact the server', 'wubtitle' ),
			''    => __( 'Could not contact the server', 'wubtitle' ),
		);
		// 200 if it is not the first payment.
		if ( 200 !== $code_response ) {
			wp_send_json_error( $message[ $code_response ] );
		}
		$response_body = json_decode( wp_remote_retrieve_body( $response ) );
		$response_data = array(
			'amount_preview' => $response_body->data->amountPreview,
			'taxes_preview'  => $response_body->data->taxesPreview,
			'taxable'        => $response_body->data->taxable,
			'name'           => $response_body->data->card->name,
			'email'          => $response_body->data->card->email,
			'expiration'     => $response_body->data->card->expiration,
			'cardNumber'     => $response_body->data->card->cardNumber,
		);
		update_option( 'wubtitle_wanted_plan_rank', $plan_rank );
		return $response_data;
	}

	/**
	 * Checks if the request was successful.
	 *
	 * @param array<string, mixed>|\WP_Error $response response to the request.
	 * @return bool
	 */
	private function is_successful_response( $response ) {
		if ( ! is_wp_error( $response ) ) {
			return true;
		}
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
			// phpcs:disable WordPress.PHP.DevelopmentFunctions
			error_log( print_r( $response->get_error_message(), true ) );
			// phpcs:enable
		}
		return false;
	}

	/**
	 * Create stripe subscription.
	 *
	 * @return void
	 */
	public function create_subscription() {
		if ( ! isset( $_POST['_ajax_nonce'], $_POST['invoiceObject'], $_POST['email'], $_POST['actionCheckout'] ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'An error occurred. Please try again in a few minutes.', 'wubtitle' ) );
		}
		$action         = sanitize_text_field( wp_unslash( $_POST['actionCheckout'] ) );
		$email          = sanitize_text_field( wp_unslash( $_POST['email'] ) );
		$nonce          = sanitize_text_field( wp_unslash( $_POST['_ajax_nonce'] ) );
		$invoice_data   = sanitize_text_field( wp_unslash( $_POST['invoiceObject'] ) );
		$invoice_object = json_decode( $invoice_data );
		check_ajax_referer( 'itr_ajax_nonce', $nonce );

		$invoice_details = Loader::get( 'invoice_helper' )->build_invoice_array( $invoice_object );
		if ( ! $invoice_details ) {
			wp_send_json_error( __( 'An error occurred. Please try again in a few minutes.', 'wubtitle' ) );
		}

		$body = array(
			'data' => array(
				'email'          => $email,
				'siteLang'       => explode( '_', get_locale(), 2 )[0],
				'invoiceDetails' => $invoice_details,
				'action'         => $action,
			),
		);

		$license_key   = get_option( 'wubtitle_license_key' );
		$response      = wp_remote_post(
			WUBTITLE_ENDPOINT . 'stripe/checkout/setup',
			array(
				'method'  => 'POST',
				'timeout' => 10,
				'headers' => array(
					'Content-Type' => 'application/json; charset=utf-8',
					'licenseKey'   => $license_key,
					'domainUrl'    => get_option( 'siteurl' ),
				),
				'body'    => wp_json_encode( $body ),
			)
		);
		$code_response = $this->is_successful_response( $response ) ? wp_remote_retrieve_response_code( $response ) : '500';
		$message       = array(
			'400' => __( 'An error occurred. Please try again in a few minutes', 'wubtitle' ),
			'401' => __( 'An error occurred. Please try again in a few minutes', 'wubtitle' ),
			'403' => __( 'Access denied', 'wubtitle' ),
			'500' => __( 'Could not contact the server', 'wubtitle' ),
			''    => __( 'Could not contact the server', 'wubtitle' ),
		);
		$response_body = json_decode( wp_remote_retrieve_body( $response ) );
		if ( 201 !== $code_response ) {
			$message = 402 === $code_response ? $response_body->errors->title : $message[ $code_response ];
			wp_send_json_error( $message );
		}
		if ( 'updateInvoice' === $action ) {
			wp_send_json_success( 'updateInvoice' );
		}
		$client_secret = $response_body->data->clientSecret;
		wp_send_json_success( $client_secret );
	}

	/**
	 * Confirm stripe subscription.
	 *
	 * @return void
	 */
	public function confirm_subscription() {
		if ( ! isset( $_POST['_ajax_nonce'], $_POST['setupIntent'], $_POST['actionCheckout'], $_POST['name'], $_POST['email'] ) ) {
			wp_send_json_error( __( 'An error occurred. Please try again in a few minutes.', 'wubtitle' ) );
		}
		$action       = sanitize_text_field( wp_unslash( $_POST['actionCheckout'] ) );
		$name         = sanitize_text_field( wp_unslash( $_POST['name'] ) );
		$email        = sanitize_text_field( wp_unslash( $_POST['email'] ) );
		$setup_object = sanitize_text_field( wp_unslash( $_POST['setupIntent'] ) );
		$nonce        = sanitize_text_field( wp_unslash( $_POST['_ajax_nonce'] ) );
		$setup_intent = json_decode( $setup_object )->setupIntent;
		check_ajax_referer( 'itr_ajax_nonce', $nonce );
		$body = array(
			'data' => array(
				'setupIntent' => $setup_intent,
				'action'      => $action,
				'name'        => $name,
				'email'       => $email,
			),
		);
		if ( 'create' === $action ) {
			if ( ! isset( $_POST['planId'] ) ) {
				wp_send_json_error( __( 'An error occurred. Please try again in a few minutes.', 'wubtitle' ) );
			}
			$plan_id                = sanitize_text_field( wp_unslash( $_POST['planId'] ) );
			$body['data']['coupon'] = ! empty( $_POST['coupon'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon'] ) ) : '';
			$body['data']['planId'] = $plan_id;
		}
		$license_key   = get_option( 'wubtitle_license_key' );
		$response      = wp_remote_post(
			WUBTITLE_ENDPOINT . 'stripe/checkout/confirm',
			array(
				'method'  => 'POST',
				'timeout' => 10,
				'headers' => array(
					'Content-Type' => 'application/json; charset=utf-8',
					'licenseKey'   => $license_key,
					'domainUrl'    => get_option( 'siteurl' ),
				),
				'body'    => wp_json_encode( $body ),
			)
		);
		$code_response = $this->is_successful_response( $response ) ? wp_remote_retrieve_response_code( $response ) : '500';
		$response_body = json_decode( wp_remote_retrieve_body( $response ) );
		if ( 200 !== $code_response ) {
			$data = $this->error_confirm_manager( $code_response, $response_body );
			wp_send_json_error( $data );
		}
		$data = array(
			'status'       => $response_body->data->status,
			'clientSecret' => $response_body->data->clientSecret,
		);
		if ( 'updateInvoice' === $action ) {
			wp_send_json_success();
		}
		wp_send_json_success( $data );
	}
	/**
	 * Manager of confirm error.
	 *
	 * @param int|string $code_response code response.
	 * @param mixed      $response_body body of response.
	 *
	 * @return string|array<mixed>
	 */
	public function error_confirm_manager( $code_response, $response_body ) {
		$message = array(
			'400' => __( 'An error occurred. Please try again in a few minutes', 'wubtitle' ),
			'401' => __( 'An error occurred. Please try again in a few minutes', 'wubtitle' ),
			'403' => __( 'Access denied', 'wubtitle' ),
			'500' => __( 'Could not contact the server', 'wubtitle' ),
			''    => __( 'Could not contact the server', 'wubtitle' ),
		);
		if ( 400 !== $code_response || ! isset( $response_body->errors->title ) ) {
			return 402 === $code_response ? $response_body->errors->title : $message[ $code_response ];
		}
		$error         = $response_body->errors->title;
		$error_message = array(
			'WRONG_CUSTOMER'    => __( 'Unable to apply discount. You are not authorized to use this coupon', 'wubtitle' ),
			'FIRST_TRANSACTION' => __( 'Unable to apply discount. This coupon can only be used on the first purchase', 'wubtitle' ),
			'GENERIC_ERROR'     => __( 'Unable to apply discount. Please try again later or enter a different discount code', 'wubtitle' ),
		);
		$data          = array(
			'couponError' => true,
			'message'     => $error_message[ $error ],
		);
		return $data;
	}
}


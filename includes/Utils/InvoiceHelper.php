<?php
/**
 * Helper for create a invoice.
 *
 * @author     Alessio Catania
 * @since      1.0.0
 * @package    Wubtitle\Utils
 */

namespace Wubtitle\Utils;

/**
 * Class helper for invoice
 */
class InvoiceHelper {

	/**
	 * Init delle action
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'wp_ajax_check_vat_code', array( $this, 'check_vat_code' ) );
		add_action( 'wp_ajax_check_fiscal_code', array( $this, 'check_fiscal_code' ) );
		add_action( 'wp_ajax_check_coupon', array( $this, 'check_coupon' ) );
	}

	/**
	 * Calls the backend endpoint to check vat code.
	 *
	 * @return void
	 */
	public function check_vat_code() {
		if ( ! isset( $_POST['_ajax_nonce'], $_POST['price_plan'], $_POST['vat_code'], $_POST['country'] ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'An error occurred. Please try again in a few minutes.', 'wubtitle' ) );
		}
		$nonce        = sanitize_text_field( wp_unslash( $_POST['_ajax_nonce'] ) );
		$price        = (float) sanitize_text_field( wp_unslash( $_POST['price_plan'] ) );
		$vat_code     = sanitize_text_field( wp_unslash( $_POST['vat_code'] ) );
		$country      = sanitize_text_field( wp_unslash( $_POST['country'] ) );
		$company_name = isset( $_POST['companyName'] ) ? sanitize_text_field( wp_unslash( $_POST['companyName'] ) ) : '';
		check_ajax_referer( 'itr_ajax_nonce', $nonce );
		$eu_countries_file = wp_remote_get( WUBTITLE_URL . 'build_form/europeanCountries.json' );
		$eu_countries      = json_decode( wp_remote_retrieve_body( $eu_countries_file ) );
		if ( ! in_array( $country, $eu_countries, true ) ) {
			$vat_code = '';
		}
		$body        = array(
			'data' => array(
				'vatCode'     => $vat_code,
				'price'       => $price,
				'countryCode' => $country,
				'companyName' => $company_name,
			),
		);
		$license_key = get_option( 'wubtitle_license_key' );
		if ( empty( $license_key ) ) {
			wp_send_json_error( __( 'Error. The product license key is missing.', 'wubtitle' ) );
		}
		$response      = wp_remote_post(
			WUBTITLE_ENDPOINT . 'stripe/customer/tax',
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
		$code_response = wp_remote_retrieve_response_code( $response );
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
		$response_body = json_decode( wp_remote_retrieve_body( $response ) );
		$taxable       = $response_body->data->taxable;
		wp_send_json_success( $taxable );
	}

	/**
	 * Calls the backend endpoint to check fiscal code.
	 *
	 * @return void
	 */
	public function check_fiscal_code() {
		if ( ! isset( $_POST['_ajax_nonce'], $_POST['fiscalCode'] ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'An error occurred. Please try again in a few minutes.', 'wubtitle' ) );
		}
		$nonce       = sanitize_text_field( wp_unslash( $_POST['_ajax_nonce'] ) );
		$fiscal_code = sanitize_text_field( wp_unslash( $_POST['fiscalCode'] ) );
		check_ajax_referer( 'itr_ajax_nonce', $nonce );
		$body        = array(
			'data' => array(
				'fiscalCode' => $fiscal_code,
			),
		);
		$license_key = get_option( 'wubtitle_license_key' );
		if ( empty( $license_key ) ) {
			wp_send_json_error( __( 'Error. The product license key is missing.', 'wubtitle' ) );
		}
		$response      = wp_remote_post(
			WUBTITLE_ENDPOINT . 'fiscalcode/check',
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
		$code_response = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code_response ) {
			wp_send_json_error( __( 'An error occurred. Please try again in a few minutes', 'wubtitle' ) );
		}
		$response_body = json_decode( wp_remote_retrieve_body( $response ) );
		$check         = $response_body->data->check;
		wp_send_json_success( $check );
	}

	/**
	 * Build a array containing the invoice data.
	 *
	 * @param object $invoice_object invoice data object.
	 *
	 * @return array<string>|false
	 */
	public function build_invoice_array( $invoice_object ) {
		$eu_countries_file = wp_remote_get( WUBTITLE_URL . 'build_form/europeanCountries.json' );
		$eu_countries      = json_decode( wp_remote_retrieve_body( $eu_countries_file ) );
		if ( ! isset( $invoice_object->invoice_firstname, $invoice_object->invoice_lastname, $invoice_object->invoice_email, $invoice_object->telephone, $invoice_object->prefix, $invoice_object->address, $invoice_object->city, $invoice_object->country ) ) {
			return false;
		}
		$invoice_details = array(
			'Name'            => $invoice_object->invoice_firstname,
			'LastName'        => $invoice_object->invoice_lastname,
			'Email'           => $invoice_object->invoice_email,
			'Telephone'       => $invoice_object->telephone,
			'TelephonePrefix' => substr( $invoice_object->prefix, 1 ),
			'Address'         => $invoice_object->address,
			'City'            => $invoice_object->city,
			'Country'         => $invoice_object->country,
		);
		if ( ! isset( $eu_countries ) || ! in_array( $invoice_object->country, $eu_countries, true ) ) {
			if ( ! empty( $invoice_object->company_name ) ) {
				$invoice_details['CompanyName'] = $invoice_object->company_name;
			}
			return $invoice_details;
		}
		if ( ! empty( $invoice_object->company_name ) ) {
			$invoice_details['CompanyName'] = $invoice_object->company_name;
			if ( empty( $invoice_object->vat_code ) ) {
				return false;
			}
			$invoice_details['VatCode'] = $invoice_object->vat_code;
		}
		if ( 'IT' === $invoice_object->country ) {
			$invoice_details = $this->italian_invoice( $invoice_details, $invoice_object );
		}
		return $invoice_details;
	}

	/**
	 * Function for add fields for italian invoice
	 *
	 * @param array<string> $invoice_details array content init value.
	 * @param object        $invoice_object invoice data object.
	 *
	 * @return mixed
	 */
	public function italian_invoice( $invoice_details, $invoice_object ) {
		if ( empty( $invoice_object->cap ) || empty( $invoice_object->province ) ) {
			return false;
		}
		$invoice_details['PostCode'] = $invoice_object->cap;
		$invoice_details['Province'] = $invoice_object->province;
		if ( ! empty( $invoice_object->fiscal_code ) ) {
			$invoice_details['FiscalCode'] = $invoice_object->fiscal_code;
		}
		if ( ! empty( $invoice_object->destination_code ) ) {
			$invoice_details['DestinationCode'] = $invoice_object->destination_code;
		}
		return $invoice_details;
	}
	/**
	 * Calls the aws endpoint to receive the invoice data.
	 *
	 * @return array<mixed>|false
	 */
	public function get_invoice_data() {
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		// warning camel case.
		$license_key = get_option( 'wubtitle_license_key' );
		if ( empty( $license_key ) ) {
			wp_send_json_error( __( 'Error. The product license key is missing.', 'wubtitle' ) );
		}
		$response      = wp_remote_post(
			WUBTITLE_ENDPOINT . 'stripe/customer/invoice-details',
			array(
				'method'  => 'POST',
				'headers' => array(
					'licenseKey' => $license_key,
					'domainUrl'  => get_option( 'siteurl' ),
				),
			)
		);
		$code_response = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code_response ) {
			return false;
		}
		$response_body = json_decode( wp_remote_retrieve_body( $response ) );
		if ( ! isset( $response_body->data->invoiceDetails, $response_body->data->paymentDetails, $response_body->data->taxable ) ) {
			return false;
		}
		$invoice_details = $response_body->data->invoiceDetails;
		$payment_details = $response_body->data->paymentDetails;
		$is_taxable      = $response_body->data->taxable;
		$invoice_data    = array(
			'invoice_firstname' => $invoice_details->Name,
			'invoice_email'     => $invoice_details->Email,
			'invoice_lastname'  => $invoice_details->LastName,
			'telephone'         => $invoice_details->Telephone,
			'prefix'            => $invoice_details->TelephonePrefix,
			'company_name'      => $invoice_details->CompanyName,
			'address'           => $invoice_details->Address,
			'cap'               => $invoice_details->PostCode,
			'city'              => $invoice_details->City,
			'province'          => $invoice_details->Province,
			'country'           => $invoice_details->Country,
			'vat_code'          => $invoice_details->VatCode,
			'fiscal_code'       => $invoice_details->FiscalCode,
			'destination_code'  => $invoice_details->DestinationCode,
		);
		$payment_data    = array(
			'name'       => $payment_details->name,
			'email'      => $payment_details->email,
			'expiration' => $payment_details->expiration,
			'cardNumber' => $payment_details->cardNumber,
		);
		return array(
			'invoice_data' => $invoice_data,
			'payment_data' => $payment_data,
			'taxable'      => $is_taxable,
		);
	}


	/**
	 * Check coupon code.
	 *
	 * @return void
	 */
	public function check_coupon() {
		if ( ! isset( $_POST['_ajax_nonce'], $_POST['coupon'], $_POST['planId'] ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'An error occurred. Please try again in a few minutes.', 'wubtitle' ) );
		}
		$coupon  = sanitize_text_field( wp_unslash( $_POST['coupon'] ) );
		$nonce   = sanitize_text_field( wp_unslash( $_POST['_ajax_nonce'] ) );
		$plan_id = sanitize_text_field( wp_unslash( $_POST['planId'] ) );
		check_ajax_referer( 'itr_ajax_nonce', $nonce );

		$body = array(
			'data' => array(
				'coupon' => $coupon,
				'planId' => $plan_id,
			),
		);

		$license_key   = get_option( 'wubtitle_license_key' );
		$response      = wp_remote_post(
			WUBTITLE_ENDPOINT . 'stripe/customer/create/preview',
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
		$code_response = ! is_wp_error( $response ) ? wp_remote_retrieve_response_code( $response ) : '500';
		$response_body = json_decode( wp_remote_retrieve_body( $response ) );
		$message       = array(
			'400' => __( 'An error occurred. Please try again in a few minutes', 'wubtitle' ),
			'401' => __( 'An error occurred. Please try again in a few minutes', 'wubtitle' ),
			'403' => __( 'Access denied', 'wubtitle' ),
			'500' => __( 'Could not contact the server', 'wubtitle' ),
			''    => __( 'Could not contact the server', 'wubtitle' ),
		);
		if ( 200 !== $code_response ) {
			$message['402'] = $response_body->errors->title;
			$message        = $message[ $code_response ];
			if ( 400 === $code_response && 'INVALID_COUPON' === $response_body->errors->title ) {
				$message = __( 'Invalid Coupon', 'wubtitle' );
			}
			wp_send_json_error( $message );
		}
		$data = array(
			'price'            => $response_body->data->netAmount,
			'newTax'           => $response_body->data->taxAmount,
			'newTotal'         => $response_body->data->totalAmount,
			'duration'         => $response_body->data->duration,
			'durationInMonths' => isset( $response_body->data->durationInMonths ) ? $response_body->data->durationInMonths : '',
		);
		wp_send_json_success( $data );
	}
}

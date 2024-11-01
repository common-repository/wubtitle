<?php
/**
 * This file handles the payment template.
 *
 * @author     Nicola Palermo
 * @since      0.1.0
 * @package    Wubtitle\Dashboard
 */

namespace Wubtitle\Dashboard;

use Wubtitle\Loader;

/**
 * This class handles Payment Templates.
 */
class PaymentTemplate {

	/**
	 * Stripe key.
	 *
	 * @var string
	 */
	private $stripe_key;
	/**
	 * Init class actions
	 *
	 * @return void
	 */
	public function run() {
		$stripe_key = 'pk_live_PvwHkJ49ry3lfXwkXIx2YKBE00S15aBYz7';
		if ( defined( 'WP_WUBTITLE_ENV' ) && 'development' === WP_WUBTITLE_ENV ) {
			$stripe_key = 'pk_test_lFmjf2Dz7VURTslihG0xys7m00NjW2BOPI';
		}
		$this->stripe_key = $stripe_key;
		add_action( 'wp_ajax_payment_template', array( $this, 'load_payment_template' ) );
		add_action( 'wp_ajax_update_template', array( $this, 'load_update_template' ) );
		add_action( 'wp_ajax_change_plan_template', array( $this, 'change_plan_template' ) );
		add_action( 'wp_ajax_custom_form_template', array( $this, 'load_custom_form' ) );
		add_action( 'wp_ajax_thankyou_page', array( $this, 'load_thankyou_page' ) );
	}


	/**
	 * Popup window template displayed on license buying button click.
	 *
	 * @return void
	 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
	 */
	public function change_plan_template() {
		if ( ! isset( $_POST['_ajax_nonce'], $_POST['priceinfo'], $_POST['wantedPlanRank'] ) ) {
			wp_send_json_error( __( 'An error occurred. Please try again in a few minutes.', 'wubtitle' ) );
		}
		$nonce             = sanitize_text_field( wp_unslash( $_POST['_ajax_nonce'] ) );
		$price_info_data   = sanitize_text_field( wp_unslash( $_POST['priceinfo'] ) );
		$wanted_plan_rank  = sanitize_text_field( wp_unslash( $_POST['wantedPlanRank'] ) );
		$price_info_object = json_decode( $price_info_data );
		check_ajax_referer( 'itr_ajax_nonce', $nonce );
		$plan_rank        = get_option( 'wubtitle_plan_rank' );
		$wanted_plan_info = Loader::get( 'send_pricing_plan' )->send_wanted_plan_info( $wanted_plan_rank );
		if ( is_array( $wanted_plan_info ) ) {
			$amount_preview = $wanted_plan_info['amount_preview'];
			$name           = $wanted_plan_info['name'];
			$email          = $wanted_plan_info['email'];
			$expiration     = $wanted_plan_info['expiration'];
			$card_number    = $wanted_plan_info['cardNumber'];
			$taxes_preview  = $wanted_plan_info['taxes_preview'];
			$taxable        = $wanted_plan_info['taxable'];
		}
		$tax_wanted_plan = $price_info_object[ $wanted_plan_rank ]->taxAmount;
		$includes_file   = 'Templates/downgrade_plan_template';
		if ( $wanted_plan_rank > $plan_rank ) {
			$includes_file = 'Templates/upgrade_plan_template';
		}
		if ( current_user_can( 'manage_options' ) ) {
			ob_start();
			wp_enqueue_style( 'wubtitle_font_family', 'https://fonts.googleapis.com/css?family=Days+One|Open+Sans&display=swap', array(), WUBTITLE_VER );
			wp_enqueue_script( 'fa', 'https://kit.fontawesome.com/b78c2a4b89.js', array(), '1.0', true );
			wp_enqueue_style( 'wubtitle_style_template', WUBTITLE_URL . 'assets/css/payment_template.css', array(), WUBTITLE_VER );
			wp_enqueue_script( 'stripe_script', 'https://js.stripe.com/v3/', array(), WUBTITLE_VER, true );
			wp_enqueue_script( 'wubtitle_change_plan', WUBTITLE_URL . 'assets/payment/change_plan_script.js', array(), WUBTITLE_VER, true );
			wp_localize_script(
				'wubtitle_change_plan',
				'WP_GLOBALS',
				array(
					'adminAjax' => admin_url( 'admin-ajax.php' ),
					'nonce'     => wp_create_nonce( 'itr_ajax_nonce' ),
					'stripeKey' => $this->stripe_key,
				)
			);
			include $includes_file . '.php';
			$html = ob_get_clean();
			wp_send_json_success( $html );
		}
		$html = 'Error';
		wp_send_json_error( $html );
	}
	/**
	 * Load the payment template
	 *
	 * @return void
	 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
	 */
	public function load_payment_template() {
		if ( ! isset( $_POST['_ajax_nonce'], $_POST['priceinfo'] ) ) {
			wp_send_json_error( __( 'An error occurred. Please try again in a few minutes.', 'wubtitle' ) );
		}
		$nonce             = sanitize_text_field( wp_unslash( $_POST['_ajax_nonce'] ) );
		$price_info_data   = sanitize_text_field( wp_unslash( $_POST['priceinfo'] ) );
		$price_info_object = json_decode( $price_info_data );
		check_ajax_referer( 'itr_ajax_nonce', $nonce );
		if ( current_user_can( 'manage_options' ) ) {
			ob_start();
			wp_enqueue_style( 'wubtitle_font_family', 'https://fonts.googleapis.com/css?family=Days+One|Open+Sans&display=swap', array(), WUBTITLE_VER );
			wp_enqueue_style( 'wubtitle_style_template', WUBTITLE_URL . 'assets/css/payment_template.css', array(), WUBTITLE_VER );
			wp_enqueue_script( 'wubtitle_change_plan', WUBTITLE_URL . 'assets/payment/payment_template.js', array(), WUBTITLE_VER, true );
			wp_localize_script(
				'wubtitle_change_plan',
				'WP_GLOBALS',
				array(
					'adminAjax' => admin_url( 'admin-ajax.php' ),
					'nonce'     => wp_create_nonce( 'itr_ajax_nonce' ),
				)
			);
			include 'Templates/payment_template.php';
			$html = ob_get_clean();
			wp_send_json_success( $html );
		}
		$html = 'Error';
		wp_send_json_error( $html );
	}
		/**
		 * Load Thank you page
		 *
		 * @return void
		 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
		 */
	public function load_thankyou_page() {
		if ( ! isset( $_POST['_ajax_nonce'], $_POST['mode'] ) ) {
			wp_send_json_error( __( 'An error occurred. Please try again in a few minutes.', 'wubtitle' ) );
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST['_ajax_nonce'] ) );
		$mode  = sanitize_text_field( wp_unslash( $_POST['mode'] ) );
		check_ajax_referer( 'itr_ajax_nonce', $nonce );
		$message = __( 'Update successfull!', 'wubtitle' );
		if ( 'upgrade' === $mode ) {
			$message = __( 'Upgrade successfull!', 'wubtitle' );
		} elseif ( 'downgrade' === $mode ) {
			$message = __( 'Downgrade successfull!', 'wubtitle' );
		}
		if ( current_user_can( 'manage_options' ) ) {
			ob_start();
			wp_enqueue_style( 'wubtitle_font_family', 'https://fonts.googleapis.com/css?family=Days+One|Open+Sans&display=swap', array(), WUBTITLE_VER );
			wp_enqueue_style( 'wubtitle_style_template', WUBTITLE_URL . 'assets/css/payment_template.css', array(), WUBTITLE_VER );
			wp_enqueue_script( 'wubtitle_change_plan', WUBTITLE_URL . 'assets/payment/payment_template.js', array(), WUBTITLE_VER, true );
			wp_localize_script(
				'wubtitle_change_plan',
				'WP_GLOBALS',
				array(
					'adminAjax' => admin_url( 'admin-ajax.php' ),
					'nonce'     => wp_create_nonce( 'itr_ajax_nonce' ),
				)
			);
			include 'Templates/thankyou_page.php';
			$html = ob_get_clean();
			wp_send_json_success( $html );
		}
		$html = 'Error';
		wp_send_json_error( $html );
	}
	/**
	 * Load the update template.
	 *
	 * @return void
	 */
	public function load_update_template() {
		$data    = Loader::get( 'invoice_helper' )->get_invoice_data();
		$taxable = true;
		if ( $data ) {
			$invoice_object = (object) $data['invoice_data'];
			$payment_object = (object) $data['payment_data'];
			$taxable        = $data['taxable'];
		}
		if ( ! isset( $_POST['_ajax_nonce'], $_POST['priceinfo'] ) ) {
			wp_send_json_error( __( 'An error occurred. Please try again in a few minutes.', 'wubtitle' ) );
		}
		$nonce             = sanitize_text_field( wp_unslash( $_POST['_ajax_nonce'] ) );
		$price_info_data   = sanitize_text_field( wp_unslash( $_POST['priceinfo'] ) );
		$price_info_object = json_decode( $price_info_data );
		check_ajax_referer( 'itr_ajax_nonce', $nonce );
		$plan_rank                = get_option( 'wubtitle_plan_rank' );
		$plans                    = get_option( 'wubtitle_all_plans' );
		$expiration_date          = get_option( 'wubtitle_expiration_date' );
		$friendly_expiration_date = date_i18n( get_option( 'date_format' ), $expiration_date );
		if ( current_user_can( 'manage_options' ) ) {
			ob_start();
			$current_plan = $plans[ $plan_rank ];
			wp_enqueue_script( 'wubtitle_stripe_form', WUBTITLE_URL . 'build_form/index.js', array( 'wp-element', 'wp-i18n' ), WUBTITLE_VER, true );
			wp_set_script_translations( 'wubtitle_stripe_form', 'wubtitle', WUBTITLE_DIR . 'languages' );
			wp_localize_script(
				'wubtitle_stripe_form',
				'WP_GLOBALS',
				array(
					'pricePlan'        => $price_info_object[ $plan_rank ]->price,
					'taxAmount'        => $price_info_object[ $plan_rank ]->taxAmount,
					'taxPercentage'    => $price_info_object[ $plan_rank ]->taxPercentage,
					'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
					'ajaxNonce'        => wp_create_nonce( 'itr_ajax_nonce' ),
					'namePlan'         => $current_plan['name'],
					'expirationDate'   => $friendly_expiration_date,
					'isTaxable'        => $taxable,
					'stripeKey'        => $this->stripe_key,
					'invoicePreValues' => $data && null !== $invoice_object ? $invoice_object : null,
					'paymentPreValues' => $data && isset( $payment_object ) ? $payment_object : null,
					'siteDomain'       => get_option( 'siteurl' ),
				)
			);
			wp_enqueue_style( 'wubtitle_style_form', WUBTITLE_URL . 'assets/css/stripeStyle.css', array(), WUBTITLE_VER );
			wp_enqueue_style( 'wubtitle_font_awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.6.0/css/font-awesome.min.css', array(), WUBTITLE_VER );
			include 'Templates/update_template.php';
			$html = ob_get_clean();
			wp_send_json_success( $html );
		}
		$html = 'Error';
		wp_send_json_error( $html );
	}
	/**
	 * Load stripe custom form template.
	 *
	 * @return void
	 */
	public function load_custom_form() {
		if ( ! isset( $_POST['_ajax_nonce'], $_POST['planRank'], $_POST['priceinfo'] ) ) {
			wp_send_json_error( __( 'An error occurred. Please try again in a few minutes.', 'wubtitle' ) );
		}
		$nonce             = sanitize_text_field( wp_unslash( $_POST['_ajax_nonce'] ) );
		$price_info_data   = sanitize_text_field( wp_unslash( $_POST['priceinfo'] ) );
		$price_info_object = json_decode( $price_info_data );
		check_ajax_referer( 'itr_ajax_nonce', $nonce );
		if ( current_user_can( 'manage_options' ) ) {
			ob_start();
			$plan_rank   = sanitize_text_field( wp_unslash( $_POST['planRank'] ) );
			$plans       = get_option( 'wubtitle_all_plans' );
			$wanted_plan = $plans[ $plan_rank ];
			wp_enqueue_script( 'wubtitle_stripe_form', WUBTITLE_URL . 'build_form/index.js', array( 'wp-element', 'wp-i18n' ), WUBTITLE_VER, true );
			wp_localize_script(
				'wubtitle_stripe_form',
				'WP_GLOBALS',
				array(
					'pricePlan'     => $price_info_object[ $plan_rank ]->price,
					'taxAmount'     => $price_info_object[ $plan_rank ]->taxAmount,
					'taxPercentage' => $price_info_object[ $plan_rank ]->taxPercentage,
					'planId'        => $wanted_plan['stripe_code'],
					'namePlan'      => $wanted_plan['name'],
					'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
					'ajaxNonce'     => wp_create_nonce( 'itr_ajax_nonce' ),
					'stripeKey'     => $this->stripe_key,
					'siteDomain'    => get_option( 'siteurl' ),
				)
			);
			wp_enqueue_style( 'wubtitle_style_form', WUBTITLE_URL . 'assets/css/stripeStyle.css', array(), WUBTITLE_VER );
			wp_enqueue_style( 'wubtitle_font_awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.6.0/css/font-awesome.min.css', array(), WUBTITLE_VER );
			include 'Templates/custom_form.php';
			$html = ob_get_clean();
			wp_send_json_success( $html );
		}
		$html = 'Error';
		wp_send_json_error( $html );
	}
}

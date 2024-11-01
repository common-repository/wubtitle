<?php
/**
 * This file describes how to include the cancel template.
 *
 * @author     Nicola Palermo
 * @since      0.1.0
 * @package    Wubtitle\Dashboard
 */

namespace Wubtitle\Dashboard;

/**
 * This class handle the cancel template.
 */
class CancelPage {
	/**
	 * Init class actions
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'wp_ajax_cancel_template', array( $this, 'load_cancel_template' ) );
	}

	/**
	 * Popup window template displayed on license "cancel" button click.
	 *
	 * @return void
	 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
	 */
	public function load_cancel_template() {
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
			wp_enqueue_script( 'wubtitle_stripe_script', 'https://js.stripe.com/v3/', array(), WUBTITLE_VER, true );
			wp_enqueue_script( 'wubtitle_payment_script', WUBTITLE_URL . 'assets/payment/payment_template.js', array(), WUBTITLE_VER, true );
			wp_localize_script(
				'wubtitle_payment_script',
				'WP_GLOBALS',
				array(
					'adminAjax'   => admin_url( 'admin-ajax.php' ),
					'nonce'       => wp_create_nonce( 'itr_ajax_nonce' ),
					'wubtitleEnv' => defined( 'WP_WUBTITLE_ENV' ) ? esc_html( WP_WUBTITLE_ENV ) : '',
				)
			);
			include 'Templates/cancel_template.php';
			$html = ob_get_clean();
			wp_send_json_success( $html );
		}
		$html = 'Error';
		wp_send_json_error( $html );
	}

}

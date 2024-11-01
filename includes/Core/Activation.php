<?php
/**
 * In this file is implemented the functions performed when the plugin is activated.
 *
 * @author     Alessio Catania
 * @since      0.1.0
 * @package    Wubtitle\Api
 */

namespace Wubtitle\Core;

/**
 * This class implements the functions performed when the plugin is activated.
 */
class Activation {
	/**
	 * Init class action.
	 *
	 * @return void
	 */
	public function run() {
		register_activation_hook( WUBTITLE_FILE_URL, array( $this, 'wubtitle_activation_license_key' ) );
		add_action( '_core_updated_successfully', array( $this, 'wubtitle_activation_license_key' ), 10, 1 );
		add_filter( 'upgrader_post_install', array( $this, 'post_install' ), 10, 3 );
	}

	/**
	 * After upgrade run plugin activation.
	 *
	 * @param array<mixed> ...$args installation result data.
	 * @return void
	 */
	public function post_install( ...$args ) {
		$name_plugin = $args[1]['plugin'];
		if ( WUBTITLE_NAME . '/wubtitle.php' === $name_plugin ) {
			$this->wubtitle_activation_license_key();
		}
	}

	/**
	 * When the plugin is activated calls the endpoint to receive the license key.
	 *
	 * @param string $wp_version WordPress version.
	 *
	 * @return void
	 */
	public function wubtitle_activation_license_key( $wp_version = '' ) {
		$site_url      = get_option( 'siteurl' );
		$wubtitle_data = get_plugin_data( WUBTITLE_FILE_URL );
		$body          = array(
			'data' => array(
				'domainUrl'     => $site_url,
				'siteLang'      => explode( '_', get_locale(), 2 )[0],
				'wpVersion'     => empty( $wp_version ) ? $GLOBALS['wp_version'] : $wp_version,
				'pluginVersion' => $wubtitle_data['Version'],
			),
		);
		$response      = wp_remote_post(
			WUBTITLE_ENDPOINT . 'key/create',
			array(
				'method'  => 'POST',
				'headers' => array(
					'Content-Type' => 'application/json; charset=utf-8',
				),
				'body'    => wp_json_encode( $body ),
			)
		);
		$code_response = wp_remote_retrieve_response_code( $response );
		if ( 200 === $code_response ) {
			$response_body = json_decode( wp_remote_retrieve_body( $response ) );
			update_option( 'wubtitle_token', $response_body->data->token, false );
			update_option( 'wubtitle_token_time', time() + ( MINUTE_IN_SECONDS * 5 ), false );
		}
	}

}

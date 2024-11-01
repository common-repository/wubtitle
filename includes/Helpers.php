<?php
/**
 * This file handles a class of helper methods.
 *
 * @author     Nicola Palermo
 * @since      1.0.0
 * @package    Wubtitle
 */

namespace Wubtitle;

use \Firebase\JWT\JWT;

/**
 * This class handles some helper methods used throughout the plugin.
 */
class Helpers {

	/**
	 * Check if gutenberg is active.
	 *
	 * @return bool
	 */
	public function is_gutenberg_active() {
		$gutenberg = ! ( false === has_filter( 'replace_editor', 'gutenberg_init' ) );

		$block_editor = false;
		// Block editor since 5.0.
		if ( isset( $GLOBALS['wp_version'] ) ) {
			$block_editor = version_compare( $GLOBALS['wp_version'], '5.0-beta', '>' );
		}

		if ( ! $gutenberg && ! $block_editor ) {
			return false;
		}

		if ( $this->is_classic_editor_active() ) {
			$editor_option       = get_option( 'classic-editor-replace' );
			$block_editor_active = array( 'no-replace', 'block' );

			return in_array( $editor_option, $block_editor_active, true );
		}

		return true;
	}

	/**
	 * Check if classic editor is active.
	 *
	 * @return bool
	 */
	public function is_classic_editor_active() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return is_plugin_active( 'classic-editor/classic-editor.php' );
	}

	/**
	 * Return an error code.
	 *
	 * @param int|string $status error status code.
	 * @param bool       $verified state of retrived data.
	 * @param int        $error_type api call message.
	 * @return int|string|false
	 */
	public function check_has_error( $status, $verified, $error_type ) {

		// xxx handles a generic error, 4xx and 5xx handles all 400 or 500 errors.
		$error = false;
		if ( 200 === $status && ! $verified ) {
			$error = $error_type;
		} elseif ( 500 <= $status && 600 > $status ) {
			$error = '5xx';
		} elseif ( 400 <= $status && 500 > $status ) {
			$error = '4xx';
		}

		return $error;
	}

	/**
	 * JWT authentication
	 *
	 * @param \WP_REST_Request $request request value.
	 * @return bool|object
	 */
	public function authorizer( $request ) {
		$headers = $request->get_headers();
		if ( ! isset( $headers['jwt'] ) ) {
			return false;
		}
		$jwt            = $headers['jwt'][0];
		$db_license_key = get_option( 'wubtitle_license_key' );
		try {
			JWT::decode( $jwt, $db_license_key, array( 'HS256' ) );
		} catch ( \Exception $e ) {
			return false;
		}
		return true;
	}

	/**
	 * Get languages supported for transcriptions
	 *
	 * @return array<string>
	 */
	public function get_languages() {
		return array(
			'it-IT' => __( 'Italian', 'wubtitle' ),
			'en-US' => __( 'US English', 'wubtitle' ),
			'es-ES' => __( 'Spanish', 'wubtitle' ),
			'de-DE' => __( 'German', 'wubtitle' ),
			'zh-CN' => __( 'Chinese', 'wubtitle' ),
			'fr-FR' => __( 'French', 'wubtitle' ),
			'ar-AE' => __( 'Gulf Arabic', 'wubtitle' ),
			'ar-SA' => __( 'Modern Standard Arabic', 'wubtitle' ),
			'nl-NL' => __( 'Dutch', 'wubtitle' ),
			'en-AU' => __( 'Australian English', 'wubtitle' ),
			'en-WL' => __( 'Welsh English', 'wubtitle' ),
			'es-US' => __( 'US Spanish', 'wubtitle' ),
			'fr-CA' => __( 'Canadian French', 'wubtitle' ),
			'fa-IR' => __( 'Farsi', 'wubtitle' ),
			'de-CH' => __( 'Swiss German', 'wubtitle' ),
			'he-IL' => __( 'Hebrew', 'wubtitle' ),
			'hi-IN' => __( 'Indian Hindi', 'wubtitle' ),
			'id-ID' => __( 'Indonesian', 'wubtitle' ),
			'ja-JP' => __( 'Japanese', 'wubtitle' ),
			'ko-KR' => __( 'Korean', 'wubtitle' ),
			'ms-MY' => __( 'Malay', 'wubtitle' ),
			'pt-PT' => __( 'Portuguese', 'wubtitle' ),
			'ru-RU' => __( 'Russian', 'wubtitle' ),
			'ta-IN' => __( 'Tamil', 'wubtitle' ),
			'te-IN' => __( 'Telugu', 'wubtitle' ),
			'tr-TR' => __( 'Turkish', 'wubtitle' ),
			'en-IN' => __( 'Indian English', 'wubtitle' ),
			'en-IE' => __( 'Irish English', 'wubtitle' ),
			'en-AB' => __( 'Scottish English', 'wubtitle' ),
			'en-GB' => __( 'British English', 'wubtitle' ),
			'pt-BR' => __( 'Brazilian Portuguese', 'wubtitle' ),
		);
	}

}

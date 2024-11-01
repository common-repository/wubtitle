<?php
/**
 * Loads the plugin classes.
 *
 * @package Wubtitle
 */

namespace Wubtitle;

/**
 * This class describes the plugin loader.
 */
class Loader {
	/**
	 * Array containing Loader instantiated objects.
	 *
	 * @var array<string,mixed>
	 */
	private static $services = array();

	/**
	 * Instantiate main classes.
	 *
	 * @return void
	 */
	public static function init() {
		load_plugin_textdomain( 'wubtitle', false, WUBTITLE_NAME . '/languages' );
		$classes = array(
			'gutenber'               => Gutenberg\VideoBlock::class,
			'settings'               => Dashboard\Settings::class,
			'request'                => Api\ApiRequest::class,
			'license_validation'     => Api\ApiLicenseValidation::class,
			'custom_media_library'   => MediaLibrary\ListingSubtitles::class,
			'subtitle'               => Core\Subtitle::class,
			'store_subtitle'         => Api\ApiStoreSubtitle::class,
			'extented_media_library' => MediaLibrary\MediaLibraryExtented::class,
			'send_pricing_plan'      => Api\ApiPricingPlan::class,
			'payment_template'       => Dashboard\PaymentTemplate::class,
			'activation'             => Core\Activation::class,
			'cancel_template'        => Dashboard\CancelPage::class,
			'cancel_subscription'    => Api\ApiCancelSubscription::class,
			'cron'                   => Core\Cron::class,
			'api_auth_plan'          => Api\ApiAuthUpgradePlan::class,
			'api_get_transcript'     => Api\ApiGetTranscript::class,
			'helpers'                => Helpers::class,
			'extends_transcription'  => MediaLibrary\TrascriptionsExtends::class,
			'transcript_cpt'         => Core\CustomPostTypes\Transcript::class,
			'shortcode'              => Core\Shortcode::class,
			'youtube_source'         => Core\Sources\YouTube::class,
			'trascription_block'     => Gutenberg\TranscriptionBlock::class,
			'invoice_helper'         => Utils\InvoiceHelper::class,
			'sync_attachments'       => Utils\SyncAttachments::class,
		);

		foreach ( $classes as $key => $class ) {
			$instance = new $class();
			self::bind( $key, $instance );
			if ( method_exists( $instance, 'run' ) ) {
				$instance->run();
			}
		}
	}

	/**
	 * Add to container class instance.
	 *
	 * @param string $key instance name.
	 * @param mixed  $item class instance.
	 * @return void
	 */
	public static function bind( $key, $item ) {
		( self::$services )[ $key ] = $item;
	}

	/**
	 * Search in the container, then return a class instance.
	 *
	 * @param string $key instance name.
	 * @return mixed|false
	 */
	public static function get( $key ) {
		if ( ! isset( self::$services[ $key ] ) ) {
			return false;
		}

		return ( self::$services )[ $key ];
	}
}

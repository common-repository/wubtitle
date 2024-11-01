<?php
/**
 * In this file is created a new endpoint for the license key validation
 *
 * @author     Nicola Palermo
 * @since      0.1.0
 * @package    Wubtitle\Api
 */

namespace Wubtitle\Api;

use WP_Error;
use WP_REST_Response;
use \Firebase\JWT\JWT;
use Wubtitle\Helpers;

/**
 * This class manages the endpoint for the license key validation.
 */
class ApiLicenseValidation {
	/**
	 * Instance of class helpers.
	 *
	 * @var mixed
	 */
	private $helpers;

	/**
	 * Init class action.
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'rest_api_init', array( $this, 'register_license_validation_route' ) );
		add_action( 'rest_api_init', array( $this, 'register_reset_invalid_license_route' ) );
		$this->helpers = new Helpers();
	}

	/**
	 * Creates new REST route.
	 *
	 * @return void
	 */
	public function register_license_validation_route() {
		register_rest_route(
			'wubtitle/v1',
			'/job-list',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_job_list' ),
				'permission_callback' => function( $request ) {
					return $this->helpers->authorizer( $request );
				},
			)
		);
	}

	/**
	 * Creates new REST route.
	 *
	 * @return void
	 */
	public function register_reset_invalid_license_route() {
		register_rest_route(
			'wubtitle/v1',
			'/reset-user',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'get_init_data' ),
				'permission_callback' => function( $request ) {
					$headers          = $request->get_headers();
					$token            = $headers['token'][0] ?? '';
					if ( ! defined( 'WP_ADMIN' ) ) {
						define( 'WP_ADMIN', true );
					}
					wp_cache_delete( 'wubtitle_token', 'options' );
					wp_cache_delete( 'wubtitle_token_time', 'options' );
					$current_token    = get_option( 'wubtitle_token' );
					$token_expiration = get_option( 'wubtitle_token_time' );
					if ( $token !== $current_token && time() > $token_expiration ) {
						return false;
					}
					return true;
				},
			)
		);
	}


	/**
	 * Reset user data.
	 *
	 * @param \WP_REST_Request $request valori della richiesta.
	 * @return WP_REST_Response|array<mixed>
	 */
	public function get_init_data( $request ) {
		$params = json_decode( $request->get_body() )->data;
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		// warning camel case.
		update_option( 'wubtitle_free', $params->isFree, false );
		update_option( 'wubtitle_license_key', $params->licenseKey, false );
		// phpcs:enable
		$plans          = $params->plans;
		$wubtitle_plans = array_reduce( $plans, array( $this, 'plans_reduce' ), array() );
		update_option( 'wubtitle_all_plans', $wubtitle_plans, false );

		$message = array(
			'data' => array(
				'status' => '200',
				'title'  => 'Success',
			),
		);

		delete_option( 'wubtitle_token' );
		delete_option( 'wubtitle_token_time' );

		return $message;
	}

	/**
	 * Gets uuid jobs and returns it.
	 *
	 * @return array<string,array<string,array<int,mixed>>>
	 */
	public function get_job_list() {
		$args     = array(
			'post_type'      => 'attachment',
			'posts_per_page' => -1,
			'meta_key'       => 'wubtitle_status',
			'meta_value'     => 'pending',
		);
		$media    = get_posts( $args );
		$job_list = array();
		foreach ( $media as  $file ) {
			$job_list[] = get_post_meta( $file->ID, 'wubtitle_job_uuid', true );
		}
		$data = array(
			'data' => array(
				'job_list' => $job_list,
			),
		);
		return $data;
	}

	/**
	 * Callback function array_reduce
	 *
	 * @param mixed $accumulator empty array.
	 * @param mixed $item object to reduce.
	 *
	 * @return mixed
	 */
	public function plans_reduce( $accumulator, $item ) {
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		// warning camel case.
		$accumulator[ $item->rank ] = array(
			'name'             => $item->name,
			'stripe_code'      => $item->id,
			'totalJobs'        => $item->totalJobs,
			'totalSeconds'     => $item->totalSeconds,
			'dot_list'         => $item->dotlist,
			'icon'             => $item->icon,
			'supportedFormats' => $item->supportedFormats,
			'dotlistV4'        => $item->dotlistV4,
		);
		return $accumulator;
	}
}

<?php
/**
 * In this file is created a new endpoint for plan change authorization.
 *
 * @author     Nicola Palermo
 * @since      1.0.0
 * @package    Wubtitle\Api
 */

namespace Wubtitle\Api;

use WP_REST_Response;
use Wubtitle\Helpers;

/**
 * This class manages authorization to change plans.
 */
class ApiAuthUpgradePlan {

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
		add_action( 'rest_api_init', array( $this, 'register_auth_plan_route' ) );
		add_action( 'rest_api_init', array( $this, 'register_reactivate_plan_route' ) );
		$this->helpers = new Helpers();
	}

	/**
	 * Creates new REST route
	 *
	 * @return void
	 */
	public function register_auth_plan_route() {
		register_rest_route(
			'wubtitle/v1',
			'/auth-plan',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'return_plan' ),
				'permission_callback' => function( $request ) {
					return $this->helpers->authorizer( $request );
				},
			)
		);
	}
	/**
	 * Creates a rest endpoint for the reactivation plan.
	 *
	 * @return void
	 */
	public function register_reactivate_plan_route() {
		register_rest_route(
			'wubtitle/v1',
			'/reactivate-plan',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'reactivate_plan' ),
				'permission_callback' => function( $request ) {
					return $this->helpers->authorizer( $request );
				},
			)
		);
	}

	/**
	 * JWT authentication.
	 *
	 * @return WP_REST_Response|array<string,array<string,bool>>
	 */
	public function reactivate_plan() {
		$is_reactivating = (bool) get_option( 'wubtitle_is_reactivating' );
		update_option( 'wubtitle_is_reactivating', false );
		$message = array(
			'data' => array(
				'is_reactivating' => $is_reactivating,
			),
		);
		return $message;
	}

	/**
	 * Gets and returns the chosen plan to backend
	 *
	 * @return array<array<string>>
	 */
	public function return_plan() {
		$plan_rank       = get_option( 'wubtitle_wanted_plan_rank' );
		$all_plans       = get_option( 'wubtitle_all_plans' );
		$plan_to_upgrade = $all_plans[ $plan_rank ]['stripe_code'];

		$data = array(
			'data' => array(
				'plan_code' => $plan_to_upgrade,
			),
		);
		return $data;
	}
}

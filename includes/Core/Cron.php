<?php
/**
 * This file describes handle WP_Cron functions.
 *
 * @author     Nicola Palermo
 * @since      0.1.0
 * @package    Wubtitle\Core
 */

namespace Wubtitle\Core;

use Wubtitle\Loader;

/**
 * This class handle WP_Cron functions.
 */
class Cron {

	/**
	 * Init class actions.
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'e2w_cron', array( $this, 'get_remote_data' ) );
		register_activation_hook( WUBTITLE_FILE_URL, array( $this, 'get_remote_data' ) );
		register_deactivation_hook( WUBTITLE_FILE_URL, array( $this, 'unschedule_cron' ) );
		add_action( 'init', array( $this, 'schedule_cron' ) );
	}

	/**
	 * Add new scedule cron.
	 *
	 * @return void
	 */
	public function schedule_cron() {
		if ( ! wp_next_scheduled( 'e2w_cron' ) ) {
			wp_schedule_event( time(), 'twicedaily', 'e2w_cron' );
		}
	}


	/**
	 * Remove cron on plugin disable.
	 *
	 * @return void
	 */
	public function unschedule_cron() {
		// when the last event was scheduled.
		$timestamp = wp_next_scheduled( '' );
		// unschedule previous event if any.
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, '' );
		}
	}


	/**
	 * Get info from remote and DB update.
	 *
	 * @return array<mixed>|false|void
	 */
	public function get_remote_data() {
		$license_key = get_option( 'wubtitle_license_key' );

		$body = array(
			'data' => array(
				'siteLang' => explode( '_', get_locale(), 2 )[0],
			),
		);

		$response      = wp_remote_post(
			WUBTITLE_ENDPOINT . 'subscription/info',
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
		$code_response = wp_remote_retrieve_response_code( $response );
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		// warning camel case.
		$token_expiration = get_option( 'wubtitle_token_time' );
		if ( 401 === $code_response || 403 === $code_response ) {
			if ( time() > $token_expiration ) {
				Loader::get( 'activation' )->wubtitle_activation_license_key();
			}
			return false;
		}
		if ( 200 === $code_response ) {
			$body_response      = json_decode( wp_remote_retrieve_body( $response ) );
			$plans              = $body_response->data->plans;
			$wubtitle_plans     = array();
			$price_info_plans   = array();
			$wubtitle_plan_rank = '';
			$total_jobs         = 0;
			$total_seconds      = 0;
			foreach ( $plans as $plan ) {
				$wubtitle_plans[ $plan->rank ]   = array(
					'name'             => $plan->name,
					'stripe_code'      => $plan->id,
					'totalJobs'        => $plan->totalJobs,
					'totalSeconds'     => $plan->totalSeconds,
					'dot_list'         => $plan->dotlist,
					'icon'             => $plan->icon,
					'supportedFormats' => $plan->supportedFormats,
					'dotlistV4'        => $plan->dotlistV4,
				);
				$price_info_plans[ $plan->rank ] = array(
					'price'         => $plan->price,
					'taxAmount'     => $plan->taxAmount,
					'taxPercentage' => $plan->taxPercentage,
				);
				if ( $body_response->data->currentPlan === $plan->id ) {
					$wubtitle_plan_rank = $plan->rank;
					$total_jobs         = $plan->totalJobs;
					$total_seconds      = $plan->totalSeconds;
				}
			}
			update_option( 'wubtitle_plan', $body_response->data->currentPlan, false );
			update_option( 'wubtitle_plan_rank', $wubtitle_plan_rank, false );
			update_option( 'wubtitle_all_plans', $wubtitle_plans, false );
			$is_free_plan = 0 === $wubtitle_plan_rank;
			update_option( 'wubtitle_free', $is_free_plan, false );
			update_option( 'wubtitle_expiration_date', $body_response->data->expirationDate, false );
			update_option( 'wubtitle_is_first_month', $body_response->data->isFirstMonth, false );
			update_option( 'wubtitle_is_canceling', $body_response->data->isCanceling, false );
			update_option( 'wubtitle_total_jobs', $total_jobs, false );
			update_option( 'wubtitle_total_seconds', $total_seconds, false );
			update_option( 'wubtitle_jobs_done', $body_response->data->consumedJobs, false );
			update_option( 'wubtitle_seconds_done', $body_response->data->consumedSeconds, false );
			return $price_info_plans;
		}
	}
}

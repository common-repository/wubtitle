<?php
/**
 * In this file is implemented the request to the APIGateway.
 *
 * @author     Alessio Catania
 * @since      0.1.0
 * @package    Wubtitle\Api
 */

namespace Wubtitle\Api;

/**
 * This class implements the request to the APIGateway.
 */
class ApiRequest {
	/**
	 * Init class action.
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'wp_ajax_submitVideo', array( $this, 'send_request' ) );
		add_action( 'wp_ajax_nopriv_submitVideo', array( $this, 'send_request' ) );
		add_action( 'init', array( $this, 'status_register_meta' ) );
	}
	/**
	 * Get media attachment.
	 *
	 * @param integer $id_attachment video id.
	 * @return mixed
	 */
	public function get_media_metadata( $id_attachment ) {
		return wp_get_attachment_metadata( $id_attachment );
	}

	/**
	 * Checks the validation and sanitize input.
	 *
	 * @param array<string> $array post.
	 * @return array<mixed>|false
	 */
	public function sanitize_input( $array ) {
		if ( ! isset( $array['id_attachment'] ) || ! isset( $array['src_attachment'] ) || ! isset( $array['lang'] ) ) {
			return false;
		}
		$array['id_attachment']  = sanitize_text_field( wp_unslash( $array['id_attachment'] ) );
		$array['src_attachment'] = sanitize_text_field( wp_unslash( $array['src_attachment'] ) );
		return $array;
	}
	/**
	 *  Creates body request.
	 *
	 * @param array<string> $data it contains id_attachment and src_attachment.
	 * @return false|array<mixed>
	 */
	public function set_body_request( $data ) {
		$lang          = $data['lang'];
		$id_attachment = (int) $data['id_attachment'];
		$video_data    = $this->get_media_metadata( $id_attachment );
		if ( ! is_numeric( $id_attachment ) || $video_data['filesize'] <= 0 || $video_data['length'] <= 0 || ! wp_http_validate_url( $data['src_attachment'] ) ) {
			return false;
		}
		$body = array(
			'source' => 'INTERNAL',
			'data'   => array(
				'attachmentId' => $id_attachment,
				'url'          => $data['src_attachment'],
				'size'         => $video_data['filesize'],
				'duration'     => $video_data['length'],
				'lang'         => $lang,
			),
		);
		return $body;
	}
	/**
	 * Sends the request to start the job.
	 *
	 * @return void
	 */
	public function send_request() {
		$license_key = get_option( 'wubtitle_license_key' );

		if ( ! isset( $_POST['_ajax_nonce'] ) ) {
			wp_send_json_error( __( 'An error occurred while creating the subtitles. Please try again in a few minutes.', 'wubtitle' ) );
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST['_ajax_nonce'] ) );
		check_ajax_referer( 'itr_ajax_nonce', $nonce );
		$data_attachment = $this->sanitize_input( $_POST );
		if ( ! $data_attachment ) {
			wp_send_json_error( __( 'An error occurred while creating the subtitles. Please try again in a few minutes.', 'wubtitle' ) );
		}
		if ( empty( $license_key ) ) {
			wp_send_json_error( __( 'Unable to create subtitles. The product license key is missing.', 'wubtitle' ) );
		}
			$body = $this->set_body_request( $data_attachment );
		if ( ! $body ) {
			wp_send_json_error( __( 'An error occurred while creating the subtitles. Please try again in a few minutes.', 'wubtitle' ) );
		}
			$response = $this->send_job_to_backend( $body, $license_key );

			$code_response = $this->is_successful_response( $response ) ? wp_remote_retrieve_response_code( $response ) : '500';

		if ( 429 === $code_response ) {
			$message_error = $this->get_error_message( (array) $response );
			wp_send_json_error( $message_error );
		}

			$message = array(
				'400' => __( 'An error occurred while creating the subtitles. Please try again in a few minutes', 'wubtitle' ),
				'401' => __( 'An error occurred while creating the subtitles. Please try again in a few minutes', 'wubtitle' ),
				'403' => __( 'Unable to create subtitles. Invalid product license', 'wubtitle' ),
				'500' => __( 'Could not contact the server', 'wubtitle' ),
			);
			if ( 201 !== $code_response ) {
				wp_send_json_error( $message[ $code_response ] );
			}
			$response_body = json_decode( wp_remote_retrieve_body( $response ) );
			$this->update_uuid_status_and_lang( $data_attachment['id_attachment'], $data_attachment['lang'], $response_body->data->jobId );
			wp_send_json_success( $code_response );
	}
	/**
	 * Checks if the request was successful.
	 *
	 * @param array<mixed>|\WP_Error $response response to the request.
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
	 * Registers wubtitle status.
	 *
	 * @return void
	 */
	public function status_register_meta() {
		register_post_meta(
			'attachment',
			'wubtitle_status',
			array(
				'show_in_rest'  => true,
				'type'          => 'string',
				'single'        => true,
				'auth_callback' => function() {
					return current_user_can( 'edit_posts' );
				},
			)
		);
		register_post_meta(
			'attachment',
			'wubtitle_lang_video',
			array(
				'show_in_rest' => true,
				'type'         => 'string',
				'single'       => true,
			)
		);
	}
	/**
	 * Calls to the endpoint and return the response.
	 *
	 * @param array<mixed> $body body request.
	 * @param string       $license_key user license.
	 * @return array<string, mixed>|\WP_Error
	 */
	public function send_job_to_backend( $body, $license_key ) {
		$response = wp_remote_post(
			WUBTITLE_ENDPOINT . 'job/create',
			array(
				'method'  => 'POST',
				'timeout' => 10,
				'headers' => array(
					'licenseKey'   => $license_key,
					'domainUrl'    => get_option( 'siteurl' ),
					'Content-Type' => 'application/json; charset=utf-8',
				),
				'body'    => wp_json_encode( $body ),
			)
		);
		return $response;
	}
	/**
	 * Updates or adds uuid, status and video lang.
	 *
	 * @param int    $id_attachment video id .
	 * @param string $lang video lang.
	 * @param string $job_id uuid jobs.
	 * @return void
	 */
	public function update_uuid_status_and_lang( $id_attachment, $lang, $job_id ) {
		update_post_meta( $id_attachment, 'wubtitle_lang_video', $lang );
		update_post_meta( $id_attachment, 'wubtitle_job_uuid', $job_id );
		update_post_meta( $id_attachment, 'wubtitle_status', 'pending' );
	}
	/**
	 * Manages error 429.
	 *
	 * @param array<mixed> $response aws endpoint response.
	 * @return string
	 */
	public function get_error_message( $response ) {
		$response_body = json_decode( wp_remote_retrieve_body( $response ) );
		$title_error   = json_decode( $response_body->errors->title );
		$reason        = $title_error->reason;
		$error_message = array(
			'NO_AVAILABLE_JOBS'     => __( 'Error, no more video left for your subscription plan', 'wubtitle' ),
			'NO_AVAILABLE_LANGUAGE' => __( 'Error, language not supported for your subscription plan', 'wubtitle' ),
			'NO_AVAILABLE_FORMAT'   => __( 'Unsupported video format for free plan', 'wubtitle' ),
		);
		if ( 'NO_AVAILABLE_MINUTES' === $reason ) {
			// phpcs:disable
			// camelcase object
			$error_message['NO_AVAILABLE_MINUTES'] = __( 'Error, video length is longer than minutes available for your subscription plan (minutes left', 'wubtitle' ) . ' ' . date_i18n( 'i:s', $title_error->videoTimeLeft ) . __( ', video left', 'wubtitle' ) . ' ' . $title_error->jobsLeft . ')';
			// phpcs:enable
		}
		return $error_message[ $reason ];
	}
}

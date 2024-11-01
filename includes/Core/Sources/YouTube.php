<?php
/**
 * This file describes handle operation on subtitle.
 *
 * @author     Nicola Palermo
 * @since      1.0.0
 * @package    Wubtitle\Core
 */

namespace Wubtitle\Core\Sources;

/**
 * This class handle subtitles.
 */
class YouTube implements \Wubtitle\Core\VideoSource {

	/**
	 * Sends job to backend endpoint.
	 *
	 * @param string $id_video id video youtube.
	 * @return array<string, mixed>|\WP_Error
	 */
	public function send_job_to_backend( $id_video ) {
		$response = wp_remote_post(
			WUBTITLE_ENDPOINT . 'job/create',
			array(
				'method'  => 'POST',
				'headers' => array(
					'licenseKey'   => get_option( 'wubtitle_license_key' ),
					'domainUrl'    => get_option( 'siteurl' ),
					'Content-Type' => 'application/json; charset=utf-8',
				),
				'body'    => wp_json_encode(
					array(
						'source' => 'YOUTUBE',
						'data'   => array(
							'youtubeId' => $id_video,
						),
					)
				),
			)
		);
		return $response;
	}

	/**
	 * Gets the trascription.
	 *
	 * @param string $id_video id video.
	 * @param string $title_video video title.
	 * @param string $text transcription content.
	 * @param string $from where the request starts.
	 * @return bool|string|int|\WP_Error
	 */
	public function insert_transcript( $id_video, $title_video, $text, $from = '' ) {
		$trascript_post = array(
			'post_title'   => $title_video,
			'post_content' => $text,
			'post_type'    => 'transcript',
			'post_status'  => 'publish',
			'meta_input'   => array(
				'_video_id'          => $id_video,
				'_transcript_source' => 'youtube',
			),
		);
		$id_transcript  = wp_insert_post( $trascript_post );

		return 'default_post_type' === $from ? $id_transcript : $text;
	}

	/**
	 * Get youtube video info
	 *
	 * @param array<string> $url_parts parts of url.
	 *
	 * @return array<mixed>|false
	 */
	public function get_video_info( $url_parts ) {
		$query_params = array();
		parse_str( $url_parts['query'], $query_params );
		if ( ! array_key_exists( 'v', $query_params ) ) {
			return array(
				'success' => false,
				'message' => __( 'Url not a valid youtube url', 'wubtitle' ),
			);
		}
		$id_video     = $query_params['v'];
		$get_info_url = 'https://www.youtube.com/youtubei/v1/player?key=AIzaSyAO_FJ2SlqU8Q4STEHLGCilw_Y9_11qcW8';

		$body = array(
			'context'         => array(
				'client'  => array(
					'hl'               => 'en',
					'clientName'       => 'WEB',
					'clientVersion'    => '2.20210721.00.00',
					'clientFormFactor' => 'UNKNOWN_FORM_FACTOR',
					'clientScreen'     => 'WATCH',
					'mainAppWebInfo'   => array(
						'graftUrl' => "/watch?v=$id_video",
					),
				),
				'user'    => array(
					'lockedSafetyMode' => false,
				),
				'request' => array(
					'useSsl'                  => true,
					'internalExperimentFlags' => array(),
					'consistencyTokenJars'    => array(),
				),
			),
			'videoId'         => $id_video,
			'playbackContext' => array(
				'contentPlaybackContext' => array(
					'vis'                   => 0,
					'splay'                 => false,
					'autoCaptionsDefaultOn' => false,
					'autonavState'          => 'STATE_NONE',
					'html5Preference'       => 'HTML5_PREF_WANTS',
					'lactMilliseconds'      => '-1',
				),
			),
			'racyCheckOk'     => false,
			'contentCheckOk'  => false,
		);

		$response      = wp_remote_post(
			$get_info_url,
			array(
				'headers' => array(
					'Accept-Language' => get_locale(),
					'Content-Type'    => 'application/json; charset=utf-8',
				),
				'body'    => wp_json_encode( $body ),
			)
		);
		$response_body = json_decode( wp_remote_retrieve_body( $response ) );
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return array(
				'success' => false,
				'message' => __( 'Url not a valid youtube url', 'wubtitle' ),
			);
		}
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		// warning camel case.
		$title_video = $response_body->videoDetails->title;
		// phpcs:enable
		$languages  = $response_body->captions->playerCaptionsTracklistRenderer->captionTracks;
		$video_info = array(
			'success'   => true,
			'source'    => 'youtube',
			'languages' => $languages,
			'title'     => $title_video,
		);
		return $video_info;
	}

	/**
	 * Get transcript video.
	 *
	 * @param string $id_video id video.
	 * @param string $video_title video title.
	 * @param string $from where the request comes from.
	 * @param string $subtitle url video youtube subtitle.
	 *
	 * @return array<mixed>
	 */
	public function get_transcript( $id_video, $video_title, $from, $subtitle ) {
		$response      = $this->send_job_to_backend( $id_video );
		$response_code = wp_remote_retrieve_response_code( $response );

		$message = array(
			'400' => __( 'An error occurred while creating the transcriptions. Please try again in a few minutes', 'wubtitle' ),
			'401' => __( 'An error occurred while creating the transcriptions. Please try again in a few minutes', 'wubtitle' ),
			'403' => __( 'Unable to create transcriptions. Invalid product license', 'wubtitle' ),
			'500' => __( 'Could not contact the server', 'wubtitle' ),
			'429' => __( 'Error, no more video left for your subscription plan', 'wubtitle' ),
		);
		if ( 201 !== $response_code ) {
			return array(
				'success' => false,
				'message' => $message[ $response_code ],
			);
		}

		if ( empty( $subtitle ) ) {
			return array(
				'success' => false,
				'message' => __( 'Transcript not avaiable for this video.', 'wubtitle' ),
			);
		}
		$subtitle = $subtitle . '&fmt=json3';
		$response = wp_remote_get( $subtitle );
		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => __( 'Transcript not avaiable for this video.', 'wubtitle' ),
			);
		}
		$text          = '';
		$response_body = json_decode( $response['body'] );
		foreach ( $response_body->events as $event ) {
			if ( isset( $event->segs ) ) {
				foreach ( $event->segs as $seg ) {
					$text .= $seg->utf8;
				}
			}
		}

		$text = str_replace( "\n", ' ', $text );

		$url_subtitle_parts    = wp_parse_url( $subtitle );
		$query_subtitle_params = array();
		parse_str( $url_subtitle_parts['query'], $query_subtitle_params );
		$lang = $query_subtitle_params['lang'];

		$video_title = $video_title . ' (' . $lang . ')';
		$id_video    = $id_video . $lang;
		$transcript  = $this->insert_transcript( $id_video, $video_title, $text, $from );
		if ( ! $transcript ) {
			return array(
				'success' => false,
				'message' => __( 'Transcript not avaiable for this video.', 'wubtitle' ),
			);
		}
		return array(
			'success' => true,
			'data'    => $transcript,
		);
	}

	/**
	 * Create and return id video.
	 *
	 * @param string        $subtitle url youtube subtitle.
	 * @param array<string> $url_parts url parts.
	 *
	 * @return array<string> id video.
	 */
	public function get_ids_video_transcription( $subtitle, $url_parts ) {
		$url_subtitle_parts    = wp_parse_url( $subtitle );
		$query_subtitle_params = array();
		parse_str( $url_subtitle_parts['query'], $query_subtitle_params );
		$lang = $query_subtitle_params['lang'];

		$query_video_params = array();
		parse_str( $url_parts['query'], $query_video_params );
		return array(
			'id_transcription' => $query_video_params['v'] . $lang,
			'id_video'         => $query_video_params['v'],
		);
	}
}

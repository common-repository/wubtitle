<?php
/**
 * This file describes handle operation on subtitle.
 *
 * @author     Nicola Palermo
 * @since      1.0.0
 * @package    Wubtitle\Core
 */

namespace Wubtitle\Core;

/**
 * This class handle subtitles.
 */
interface VideoSource {

	/**
	 * Interface method send job to backend.
	 *
	 * @param string $id_video video id.
	 * @return array<string>|\WP_Error
	 */
	public function send_job_to_backend( $id_video );

	/**
	 * Interface method for calling and retrieving transcripts.
	 *
	 * @param string $id_video embed video id.
	 * @param string $video_title video title.
	 * @param string $from where the request comes from.
	 * @param string $subtitle url or code language of subtitle.
	 *
	 * @return array<mixed>
	 */
	public function get_transcript( $id_video, $video_title, $from, $subtitle );

		/**
		 * Gets the trascription.
		 *
		 * @param string $id_video id video.
		 * @param string $title_video video title.
		 * @param string $text transcription content.
		 * @param string $from where the request starts.
		 * @return bool|string|int|\WP_Error
		 */
	public function insert_transcript( $id_video, $title_video, $text, $from );

	/**
	 * Interface method for get video info
	 *
	 * @param array<string> $url_parts parts of url.
	 *
	 * @return array<mixed>|false
	 */
	public function get_video_info( $url_parts );

	/**
	 * Interface method for get id video
	 *
	 * @param string        $subtitle code languages or url subtitle.
	 * @param array<string> $url_parts url parts.
	 *
	 * @return array<string> id video.
	 */
	public function get_ids_video_transcription( $subtitle, $url_parts );
}

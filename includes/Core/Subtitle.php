<?php
/**
 * This file describes handle operation on subtitle.
 *
 * @author     Nicola Palermo
 * @since      0.1.0
 * @package    Wubtitle\Core
 */

namespace Wubtitle\Core;

/**
 * This class handle subtitles.
 */
class Subtitle {
	/**
	 * Init class actions
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'delete_attachment', array( $this, 'delete_subtitle' ) );
	}

	/**
	 * Handle the subtitle deletion.
	 *
	 * @param int $id_deleted_attachment file params.
	 * @return void
	 */
	public function delete_subtitle( $id_deleted_attachment ) {
		$releted_vtt = get_post_meta( $id_deleted_attachment, 'wubtitle_subtitle', true );
		wp_delete_attachment( $releted_vtt );
	}

}

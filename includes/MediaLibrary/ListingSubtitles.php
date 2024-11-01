<?php
/**
 * This file implements new coumns to media library.
 *
 * @author     Alessio Catania
 * @since      0.1.0
 * @package    Wubtitle\Gutenberg
 */

namespace Wubtitle\MediaLibrary;

/**
 * This class implements new coumns to media library.
 */
class ListingSubtitles {

	/**
	 * Init class actions
	 *
	 * @return void
	 */
	public function run() {
		add_filter( 'manage_media_columns', array( $this, 'wubtitle_status_column' ) );
		add_action( 'manage_media_custom_column', array( $this, 'wubtitle_status_value' ), 10, 2 );
		add_action( 'admin_print_styles-upload.php', array( $this, 'wubtitle_column_width' ) );
		add_action( 'pre_get_posts', array( $this, 'wubtitle_exclude_subtitle_file' ) );
	}

	/**
	 * Add a new column
	 *
	 * @param array<string> $cols media library columns.
	 * @return array<string>
	 */
	public function wubtitle_status_column( $cols ) {
		$cols['wubtitle_status'] = __( 'Subtitle', 'wubtitle' );
		return $cols;
	}

	/**
	 * Shows subtitles state.
	 *
	 * @param string $column_name column name.
	 * @param int    $id_media attachment id.
	 * @return void
	 */
	public function wubtitle_status_value( $column_name, $id_media ) {
		$all_status = array(
			'pending'  => __( 'Generating', 'wubtitle' ),
			'draft'    => __( 'Draft', 'wubtitle' ),
			'enabled'  => __( 'Published', 'wubtitle' ),
			'notvideo' => '__',
			'notfound' => __( 'None', 'wubtitle' ),
			'error'    => __( 'Error', 'wubtitle' ),
		);
		$status     = get_post_meta( $id_media, 'wubtitle_status', true );
		$status     = '' === $status ? 'notfound' : $status;
		$type       = get_post_mime_type( $id_media );
		if ( ! $type ) {
			$type = 'none';
		}
		$mime_type = explode( '/', $type )[0];
		$status    = ( 'video' !== $mime_type ) ? 'notvideo' : $status;
		if ( 'wubtitle_status' === $column_name ) {
			echo esc_html( $all_status[ $status ] );
		}
	}

	/**
	 *  Enqueue new column style.
	 *
	 * @return void
	 */
	public function wubtitle_column_width() {
		wp_enqueue_style( 'wubtitle_column_style', plugins_url( '../../assets/css/columnStyle.css', __FILE__ ), array(), WUBTITLE_VER );
	}

	/**
	 * Exclude subtitles files from media library.
	 *
	 * @param \WP_Query $query instanza di WP_QUERY.
	 * @return void
	 */
	public function wubtitle_exclude_subtitle_file( $query ) {
		if ( is_admin() && 'attachment' === $query->get( 'post_type' ) ) {
			$query->set( 'meta_key', 'is_subtitle' );
			$query->set( 'meta_compare', 'NOT EXISTS' );
		}
	}
}

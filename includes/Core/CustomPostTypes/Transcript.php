<?php
/**
 * This file describes the transcript custom post type.
 *
 * @author     Nicola Palermo
 * @since      1.0.0
 * @package    Wubtitle\Core\CustomPostTypes
 */

namespace Wubtitle\Core\CustomPostTypes;

use \Wubtitle\Core\Sources\YouTube;

/**
 * This class handle the transcript custom post type methods.
 */
class Transcript {
	/**
	 * Init class actions.
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'init', array( $this, 'register_transcript_cpt' ) );

		add_action( 'save_post_transcript', array( $this, 'save_postdata' ) );

		add_filter( 'manage_transcript_posts_columns', array( $this, 'set_custom_transcript_column' ) );
		add_action( 'manage_transcript_posts_custom_column', array( $this, 'transcript_custom_column_values' ), 10, 2 );
	}

	/**
	 * Adds new column.
	 *
	 * @param array<string> $columns columns of the post.
	 * @return array<string>
	 */
	public function set_custom_transcript_column( $columns ) {
		$columns['shortcode'] = __( 'Shortcode', 'wubtitle' );
		return $columns;
	}

	/**
	 * Manages the content of the columns
	 *
	 * @param string $column column to manage.
	 * @param int    $post_id id of post.
	 * @return void
	 */
	public function transcript_custom_column_values( $column, $post_id ) {
		switch ( $column ) {
			case 'shortcode':
				echo esc_html( '[transcript id=' . $post_id . ']' );
				break;
		}
	}

	/**
	 * Update option hook callback.
	 *
	 *  @param int $post_id id of the post.
	 *
	 * @return void
	 */
	public function save_postdata( $post_id ) {
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST['source'] ) || ! isset( $_POST['url'] ) || ! isset( $_POST['transcript_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['transcript_nonce'] ) ), 'transcript_data' ) ) {
			return;
		}

		update_post_meta(
			$post_id,
			'_transcript_url',
			sanitize_text_field( wp_unslash( $_POST['url'] ) )
		);
		update_post_meta(
			$post_id,
			'_transcript_source',
			sanitize_text_field( wp_unslash( $_POST['source'] ) )
		);
	}


	/**
	 * Registers a new post type.
	 *
	 * @return void
	 */
	public function register_transcript_cpt() {
		$labels = array(
			'name'                     => __( 'Transcripts', 'wubtitle' ),
			'singular_name'            => __( 'Transcript', 'wubtitle' ),
			'menu_name'                => __( 'Transcripts', 'wubtitle' ),
			'all_items'                => __( 'All transcripts', 'wubtitle' ),
			'add_new'                  => __( 'Add new', 'wubtitle' ),
			'add_new_item'             => __( 'Add new transcript', 'wubtitle' ),
			'edit_item'                => __( 'Edit transcript', 'wubtitle' ),
			'new_item'                 => __( 'New transcript', 'wubtitle' ),
			'view_item'                => __( 'View transcript', 'wubtitle' ),
			'view_items'               => __( 'View transcripts', 'wubtitle' ),
			'search_items'             => __( 'Search transcripts', 'wubtitle' ),
			'not_found'                => __( 'No Transcripts found', 'wubtitle' ),
			'not_found_in_trash'       => __( 'No Transcripts found in trash', 'wubtitle' ),
			'parent'                   => __( 'Parent transcript:', 'wubtitle' ),
			'archives'                 => __( 'Transcript archives', 'wubtitle' ),
			'insert_into_item'         => __( 'Insert into Transcript', 'wubtitle' ),
			'uploaded_to_this_item'    => __( 'Upload to this Transcript', 'wubtitle' ),
			'filter_items_list'        => __( 'Filter Transcripts list', 'wubtitle' ),
			'items_list_navigation'    => __( 'Transcripts list navigation', 'wubtitle' ),
			'items_list'               => __( 'Transcripts list', 'wubtitle' ),
			'attributes'               => __( 'Transcripts attributes', 'wubtitle' ),
			'name_admin_bar'           => __( 'Transcript', 'wubtitle' ),
			'item_published'           => __( 'Transcript published', 'wubtitle' ),
			'item_published_privately' => __( 'Transcript published privately.', 'wubtitle' ),
			'item_reverted_to_draft'   => __( 'Transcript reverted to draft.', 'wubtitle' ),
			'item_scheduled'           => __( 'Transcript scheduled', 'wubtitle' ),
			'item_updated'             => __( 'Transcript updated.', 'wubtitle' ),
			'parent_item_colon'        => __( 'Parent transcript:', 'wubtitle' ),
		);

		$args = array(
			'label'        => __( 'Transcripts', 'wubtitle' ),
			'labels'       => $labels,
			'description'  => __( 'Video Transcripts', 'wubtitle' ),
			'show_in_rest' => true,
			'map_meta_cap' => true,
			'hierarchical' => false,
			'supports'     => array( 'title', 'editor', 'revisions' ),
		);

		if ( WP_DEBUG ) {
			$args['show_ui']       = true;
			$args['menu_position'] = 83;
			$args['menu_icon']     = 'dashicons-format-chat';
		}

		register_post_type( 'transcript', $args );
	}

}

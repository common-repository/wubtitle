<?php
/**
 * Helper to sync attachments and add wpml plugin support.
 *
 * @author     Alessio Catania
 * @since      1.2.1
 * @package    Wubtitle\Utils
 */

namespace Wubtitle\Utils;

/**
 * Class helper to sync attachments post meta.
 */
class SyncAttachments {

	/**
	 * Init action
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'updated_post_meta', array( $this, 'sync_post_meta' ), 10, 4 );
		add_action( 'added_post_meta', array( $this, 'sync_post_meta' ), 10, 4 );
	}

	/**
	 * Recover all attachment clones and duplicate wubtitle post meta.
	 *
	 * @param string|int ...$args attachment info.
	 * @return void
	 */
	public function sync_post_meta( ...$args ) {
		if ( ! is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ) {
			return;
		}

		$object_id  = $args[1];
		$meta_key   = $args[2];
		$meta_value = $args[3];
		global $sitepress;
		global $wpdb;

		$sync_meta_keys = array(
			'wubtitle_lang_video',
			'wubtitle_job_uuid',
			'wubtitle_status',
			'wubtitle_subtitle',
			'is_subtitle',
		);
		remove_action( 'updated_post_meta', array( $this, 'sync_post_meta' ) );
		remove_action( 'added_post_meta', array( $this, 'sync_post_meta' ) );
		if ( 'wubtitle_transcript' === $meta_key ) {
			$trid               = $sitepress->get_element_trid( $meta_value, 'post_attachment' );
			$translations_query = $wpdb->prepare( "SELECT * FROM wp_icl_translations WHERE trid = %d AND element_type = 'post_attachment'", $trid );
			// phpcs:disable
			$translations       = $wpdb->get_results( $translations_query );
			// phpcs:enable
			$args  = array(
				'post_type'      => 'transcript',
				'posts_per_page' => 1,
				'meta_key'       => 'wubtitle_transcript',
				'meta_value'     => $meta_value,
			);
			$posts = get_posts( $args );
			foreach ( $translations as $translation ) {
				if ( $translation->element_id !== (string) $meta_value ) {
					$trascript_post = array(
						'post_title'   => $posts[0]->post_title,
						'post_content' => $posts[0]->post_content,
						'post_status'  => 'publish',
						'post_type'    => 'transcript',
						'meta_input'   => array(
							'wubtitle_transcript' => $translation->element_id,
						),
					);
					wp_insert_post( $trascript_post );
				}
			}
		}

		if ( in_array( $meta_key, $sync_meta_keys, true ) ) {
			$trid               = $sitepress->get_element_trid( $object_id, 'post_attachment' );
			$translations_query = $wpdb->prepare( "SELECT * FROM wp_icl_translations WHERE trid = %d AND element_type = 'post_attachment'", $trid );
			// phpcs:disable
			$translations       = $wpdb->get_results( $translations_query );
			// phpcs:enable

			foreach ( $translations as $translation ) {
				if ( $translation->element_id !== $object_id ) {
					update_post_meta( $translation->element_id, $meta_key, $meta_value );
				}
			}
		}
		add_action( 'updated_post_meta', array( $this, 'sync_post_meta' ), 10, 4 );
		add_action( 'added_post_meta', array( $this, 'sync_post_meta' ), 10, 4 );
	}

}


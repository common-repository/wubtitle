<?php
/**
 * This file implements Transcription Block.
 *
 * @author     Alessio Catania
 * @since      1.0.0
 * @package    Wubtitle\Gutenberg
 */

namespace Wubtitle\Gutenberg;

/**
 * This class describes The Gutenberg Transcription Block.
 */
class TranscriptionBlock {
	/**
	 * Init class actions.
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'init', array( $this, 'create_transcription_block' ) );
		add_filter( 'rest_transcript_query', array( $this, 'add_parameters_query' ), 10, 2 );
	}
	/**
	 * Adds the parameters used by the transcript custom post type.
	 *
	 * @param array<string>    $args argomenti per la query.
	 * @param \WP_REST_Request $request oggetto contente i parametri custom.
	 * @return array<string>
	 */
	public function add_parameters_query( $args, $request ) {
		$url_parts    = wp_parse_url( $request->get_param( 'metaValue' ) );
		$query_params = array();
		if ( ! isset( $url_parts['query'] ) ) {
			return $args;
		}
		parse_str( $url_parts['query'], $query_params );
		$id_video = $query_params['v'];
		if ( $request->get_param( 'metaKey' ) ) {
				$args['meta_key']   = $request->get_param( 'metaKey' );
				$args['meta_value'] = $id_video;
		}
		return $args;
	}
	/**
	 * Register the wubtitle block for gutenberg.
	 *
	 * @return void
	 */
	public function create_transcription_block() {
		wp_register_script( 'trascription_block_script', WUBTITLE_URL . '/build/index.js', array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-data' ), 'transcription_block', false );
		wp_register_style( 'stylesheet_transcription_block', WUBTITLE_URL . '/assets/css/transBlockStyle.css', array(), 'transcript_block_style' );

		register_block_type(
			'wubtitle/transcription',
			array(
				'editor_style' => 'stylesheet_transcription_block',
			)
		);
	}

}

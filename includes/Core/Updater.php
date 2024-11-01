<?php
/**
 * This file implements the plugin updates
 *
 * @author      Alessio Catania
 * @since       1.0.0
 * @package     Wubtitle\Core
 */

namespace Wubtitle\Core;

/**
 * This class implements the plugin updates
 */
class Updater {
	/**
	 * Plugin data.
	 *
	 * @var array<mixed>
	 */
	private $plugin_data;
	/**
	 * Github username.
	 *
	 * @var string
	 */
	private $username;
	/**
	 * Github repository.
	 *
	 * @var string
	 */
	private $repo;
	/**
	 * Github response.
	 *
	 * @var mixed
	 */
	private $release_info;
	/**
	 * Plugin Activated
	 *
	 * @var boolean
	 */
	private $plugin_activated;

	/**
	 * Init actions
	 *
	 * @return void
	 */
	public function run() {
		$this->plugin_data = get_plugin_data( WUBTITLE_FILE_URL );
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'set_transient' ) );
		add_filter( 'plugins_api', array( $this, 'set_release_info' ), 10, 3 );
		add_filter( 'upgrader_pre_install', array( $this, 'pre_install' ), 10 );
		add_filter( 'upgrader_post_install', array( $this, 'post_install' ), 10, 3 );
		$this->username = 'CTMobi';
		$this->repo     = 'Wubtitle-Wordpress-Plugin';
	}
	/**
	 * Get release info from github repository.
	 *
	 * @return void
	 */
	private function get_release_info() {
		if ( ! empty( $this->release_info ) ) {
			return;
		}
		$repo_url        = "https://api.github.com/repos/{$this->username}/{$this->repo}/releases/latest";
		$github_response = wp_remote_get( $repo_url );
		if ( is_wp_error( $github_response ) ) {
			return;
		}
		$code_response = wp_remote_retrieve_response_code( $github_response );
		if ( 200 !== $code_response ) {
			return;
		}
		$this->release_info = json_decode( wp_remote_retrieve_body( $github_response ) );
	}
	/**
	 * Set transient for release details.
	 *
	 * @param mixed $transient contains the release info.
	 * @return object
	 */
	public function set_transient( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}
		$this->get_release_info();
		if ( ! is_object( $this->release_info ) || ! property_exists( $this->release_info, 'tag_name' ) || ! property_exists( $this->release_info, 'assets' ) ) {
			return $transient;
		}
		$do_update = version_compare( $this->release_info->tag_name, $transient->checked[ WUBTITLE_NAME . '/wubtitle.php' ], '>' );
		if ( $do_update ) {
			$package       = $this->release_info->assets[0]->browser_download_url;
			$plugin_url    = $this->plugin_data['PluginURI'];
			$transient_obj = (object) array(
				'slug'        => WUBTITLE_NAME,
				'new_version' => $this->release_info->tag_name,
				'url'         => $plugin_url,
				'package'     => $package,
				'icons'       => array(
					'1x' => WUBTITLE_URL . 'assets/img/icon128.jpg',
					'2x' => WUBTITLE_URL . 'assets/img/icon256.jpg',
				),
			);
			// @phpstan-ignore-next-line. reports response as an undefined property
			$transient->response[ WUBTITLE_NAME . '/wubtitle.php' ] = $transient_obj;
		}
		return $transient;
	}
	/**
	 * Push information to get the update information.
	 *
	 * @param mixed ...$args plugin info.
	 * @return false|object
	 */
	public function set_release_info( ...$args ) {
		$response = (object) $args[2];
		$this->get_release_info();
		if ( ! property_exists( $response, 'slug' ) || WUBTITLE_NAME !== $response->slug ) {
			return false;
		}
		$response->last_updated  = $this->release_info->published_at;
		$response->slug          = WUBTITLE_NAME;
		$response->plugin_name   = $this->plugin_data['Name'];
		$response->version       = $this->release_info->tag_name;
		$response->author        = $this->plugin_data['AuthorName'];
		$response->homepage      = $this->plugin_data['PluginURI'];
		$response->download_link = $this->release_info->assets[0]->browser_download_url;
		$response->sections      = array(
			'description' => $this->plugin_data['Description'],
			'changelog'   => $this->release_info->body,
		);
		$response->banners       = array(
			'low'  => WUBTITLE_URL . 'assets/img/bannerlow.jpg',
			'high' => WUBTITLE_URL . 'assets/img/bannerhigh.jpg',
		);
		$response->name          = 'WUBTITLE';
		return $response;
	}
	/**
	 * Reactivate the plugin and rename the folder with the original name.
	 *
	 * @param array<mixed> ...$args installation result data.
	 * @return array<mixed>
	 */
	public function post_install( ...$args ) {
		$result = $args[2];

		global $wp_filesystem;
		$wp_filesystem->move( $result['destination'], WUBTITLE_DIR );
		$result['destination'] = WUBTITLE_DIR;

		// if the plug-in was activated, it must be reactivated after installation.
		if ( $this->plugin_activated ) {
			activate_plugin( WUBTITLE_NAME . '/wubtitle.php' );
		}
		return $result;
	}
	/**
	 * Check if the plugin is active before installing the update
	 *
	 *  @return void
	 */
	public function pre_install() {
		$this->plugin_activated = is_plugin_active( WUBTITLE_NAME . '/wubtitle.php' );
	}
}

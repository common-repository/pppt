<?php
/**
 *
 * @link              https://xuxu.fr
 * @since             1.0.0
 * @package           Pppt
 *
 * @wordpress-plugin
 * Plugin Name:       PPPT
 * Description:       Archive plugins you don't need anymore, and get it back when you want it.
 * Version:           1.0.2
 * Author:            Xuan NGUYEN
 * Author URI:        https://xuxu.fr
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       pppt
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'PPPT_VERSION', '1.0.2' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-pppt-activator.php
 */
function activate_pppt() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-pppt-activator.php';
	Pppt_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-pppt-deactivator.php
 */
function deactivate_pppt() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-pppt-deactivator.php';
	Pppt_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_pppt' );
register_deactivation_hook( __FILE__, 'deactivate_pppt' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-pppt.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_pppt() {

	$plugin = new Pppt();
	$plugin->run();

}
run_pppt();

/**
 * Check the archive pppt plugins directory and retrieve all plugin files with any plugin data.
 *
 * @since 1.0.0
 * @return array Key is the plugin file path and the value is an array of the plugin data.
 */
function get_archive_plugins() {
	$destination = wp_upload_dir();
	define( 'WP_ARCHIVE_PLUGIN_DIR', $destination['basedir'] . '/pppt/' );

	$cache_archive_plugins = wp_cache_get( 'archive_plugins', 'archive_plugins' );
	if ( ! $cache_archive_plugins ) {
		$cache_archive_plugins = array();
	}

	if ( isset( $cache_archive_plugins[ $plugin_folder ] ) ) {
		return $cache_archive_plugins[ $plugin_folder ];
	}

	$wp_archive_plugins = array();
	$plugin_root        = WP_ARCHIVE_PLUGIN_DIR;
	if ( ! empty( $plugin_folder ) ) {
		$plugin_root .= $plugin_folder;
	}

	// Files in wp-content/uploads/pppt directory
	$plugins_dir  = @ opendir( $plugin_root );
	$plugin_files = array();
	if ( $plugins_dir ) {
		while ( ( $file = readdir( $plugins_dir ) ) !== false ) {
			if ( substr( $file, 0, 1 ) == '.' ) {
				continue;
			}
			if ( is_dir( $plugin_root . '/' . $file ) ) {
				$plugins_subdir = @ opendir( $plugin_root . '/' . $file );
				if ( $plugins_subdir ) {
					while ( ( $subfile = readdir( $plugins_subdir ) ) !== false ) {
						if ( substr( $subfile, 0, 1 ) == '.' ) {
							continue;
						}
						if ( substr( $subfile, -4 ) == '.php' ) {
							$plugin_files[] = "$file/$subfile";
						}
					}
					closedir( $plugins_subdir );
				}
			} else {
				if ( substr( $file, -4 ) == '.php' ) {
					$plugin_files[] = $file;
				}
			}
		}
		closedir( $plugins_dir );
	}

	if ( empty( $plugin_files ) ) {
		return $wp_plugins;
	}

	foreach ( $plugin_files as $plugin_file ) {
		if ( ! is_readable( "$plugin_root/$plugin_file" ) ) {
			continue;
		}

		$plugin_data = get_plugin_data( "$plugin_root/$plugin_file", false, false ); //Do not apply markup/translate as it'll be cached.

		if ( empty( $plugin_data['Name'] ) ) {
			continue;
		}

		$wp_plugins[ plugin_basename( $plugin_file ) ] = $plugin_data;
	}

	uasort( $wp_plugins, '_sort_uname_callback' );

	$cache_archive_plugins[ $plugin_folder ] = $wp_plugins;
	wp_cache_set( 'archive_plugins', $cache_archive_plugins, 'archive_plugins' );

	return $wp_plugins;
}

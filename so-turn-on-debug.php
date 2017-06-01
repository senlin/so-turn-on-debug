<?php
/**
 * Plugin Name: SO Turn On Debug
 * Plugin URI:  https://so-wp.com/plugin/so-turn-on-debug/
 * Description: Use this plugin to turn on WP_DEBUG on sites where there is no access to the wp-config.php file
 * Version:     1.0.1
 * Author:      SO WP
 * Author URI:  https://so-wp.com/plugins/
 * Text Domain: so-turn-on-debug
 * License:     GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 * 
 * We used the premium [WP Rocket](https://rocket.me) plugin as an example
 * on how to write to the wp-config file, like they do when setting WP_CACHE to true
 *
 */

// don't load the plugin file directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'DOING_AJAX' ) && ! defined( 'DOING_AUTOSAVE' ) ) {
    add_action( 'admin_init', 'sotod_check_wp_debug_define' );
}

function sotod_check_wp_debug_define() {
	if( defined( 'WP_DEBUG' ) && ! WP_DEBUG ) {
		sotod_set_wp_debug_define( true );
	}
}

/**
 * Added or set the value of the WP_DEBUG constant
 *
 * @since 1.0.0
 *
 * @param bool $turn_it_on The value of WP_DEBUG constant
 * @return void
 */
function sotod_set_wp_debug_define( $turn_it_on ) {
	// If WP_DEBUG is already defined, no need to do anything
	if( ( $turn_it_on && defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
		return;
	}

	// Get path of the config file
	$config_file_path = sotod_find_wpconfig_path();
    if ( ! $config_file_path ) {
		return;
    }

	// Get content of the config file
	$config_file = file( $config_file_path );

	// Get the value of WP_DEBUG constant
	$turn_it_on = $turn_it_on ? 'true' : 'false';

	/**
	 * Filter allow to change the value of WP_DEBUG constant
	 *
	 * @since 1.0.0
	 *
	 * @param string $turn_it_on The value of WP_DEBUG constant
	*/
	apply_filters( 'sotod_set_wp_debug_define', $turn_it_on );

	// Lets find out if the constant WP_DEBUG is defined or not
	$is_wp_debug_exist = false;

	// Get WP_DEBUG constant define
	$constant = "define( 'WP_DEBUG', $turn_it_on ); // Added by SO Turn On Debug plugin". "\r\n";

	foreach ( $config_file as &$line ) {
		if ( ! preg_match( '/^define\(\s*\'([A-Z_]+)\',(.*)\)/', $line, $match ) ) {
			continue;
		}

		if ( $match[1] == 'WP_DEBUG' ) {
			$is_wp_debug_exist = true;
			$line = $constant;
		}
	}
	unset( $line );

	// If the constant does not exist, create it
	if ( ! $is_wp_debug_exist ) {
		array_shift( $config_file );
		array_unshift( $config_file, "<?php\r\n", $constant );
	}

	// Insert the constant in wp-config.php file
	$handle = @fopen( $config_file_path, 'w' );
	foreach( $config_file as $line ) {
		@fwrite( $handle, $line );
	}

	@fclose( $handle );

	// Update the writing permissions of wp-config.php file
	$chmod = defined( 'FS_CHMOD_FILE' ) ? FS_CHMOD_FILE : 0644;
	@chmod( $config_file_path, $chmod );
}

/**
 * Try to find the correct wp-config.php file, support one level up in filetree
 *
 * @since 1.0.0
 *
 * @return string|bool The path of wp-config.php file or false
 */
function sotod_find_wpconfig_path() {
	$config_file     = ABSPATH . 'wp-config.php';
	$config_file_alt = dirname( ABSPATH ) . '/wp-config.php';

	if ( file_exists( $config_file ) && is_writable( $config_file ) ) {
		return $config_file;
	} elseif ( @file_exists( $config_file_alt ) && is_writable( $config_file_alt ) && ! file_exists( dirname( ABSPATH ) . '/wp-settings.php' ) ) {
		return $config_file_alt;
	}

	// No writable file found
	return false;
}


/**
 * Set WP_DEBUG to false upon deactivation of the plugin
 *
 * @since 1.0.0
 *
 * @return string|bool The path of wp-config.php file or false
 */
register_deactivation_hook( __FILE__, 'sotod_deactivation' );

function sotod_deactivation() {
    // set WP_DEBUG back to false
    set_sotod_wp_debug_off( false );
}

function set_sotod_wp_debug_off( $turn_it_off ) {
	// Get path of the config file
	$config_file_path = sotod_find_wpconfig_path();
    if ( ! $config_file_path ) {
		return;
    }
	// Get content of the config file
	$config_file = file( $config_file_path );

	// Get the value of WP_DEBUG constant
	$turn_it_off = $turn_it_off ? 'true' : 'false';

	/**
	 * Filter allow to change the value of WP_DEBUG constant
	 *
	 * @since 1.0.0
	 *
	 * @param string $turn_it_on The value of WP_DEBUG constant
	*/
	apply_filters( 'sotod_set_wp_debug_define', $turn_it_off );

	// Lets find out if the constant WP_DEBUG is defined or not
	$is_wp_debug_exist = true;

	// Get WP_DEBUG constant define
	$constant = "define( 'WP_DEBUG', $turn_it_off ); // Added by SO Turn On Debug plugin". "\r\n";

	foreach ( $config_file as &$line ) {
		if ( ! preg_match( '/^define\(\s*\'([A-Z_]+)\',(.*)\)/', $line, $match ) ) {
			continue;
		}

		if ( $match[1] == 'WP_DEBUG' ) {
			$is_wp_debug_exist = true;
			$line = $constant;
		}
	}
	unset( $line );

	// If the constant does not exist, create it
	if ( ! $is_wp_debug_exist ) {
		array_shift( $config_file );
		array_unshift( $config_file, "<?php\r\n", $constant );
	}

	// Insert the constant in wp-config.php file
	$handle = @fopen( $config_file_path, 'w' );
	foreach( $config_file as $line ) {
		@fwrite( $handle, $line );
	}

	@fclose( $handle );

	// Update the writing permissions of wp-config.php file
	$chmod = defined( 'FS_CHMOD_FILE' ) ? FS_CHMOD_FILE : 0644;
	@chmod( $config_file_path, $chmod );
}

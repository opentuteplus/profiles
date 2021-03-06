<?php

/**
 * The Profiles Plugin
 *
 * Profiles is built on the solid foundation of the BuddyPress plugin. 
 * We decided to fork this project to provide a more generic Profiles 
 * plugin that could be easily extended for more specific use-cases.
 *
 * @package Profiles
 * @suprofilesackage Main
 */

/**
 * Plugin Name: Profiles
 * Plugin URI:  https://wordpress.org/plugins/profiles/
 * Description: Profiles is built on the solid foundation of the Profiles plugin. We decided to fork this project to provide a more generic Profiles plugin that could be easily extended for more specific use-cases.
 * Author:      OpenTute+
 * Author URI:  http://opentuteplus.com/
 * Version:     0.0.1
 * Text Domain: profiles
 * Domain Path: /profiles-languages/
 * License:     GPLv3 or later (license.txt)
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Assume you want to load from build
$profiles_loader = dirname( __FILE__ ) . '/build/profiles-loader.php';

// Load from source if no build exists
if ( ! file_exists( $profiles_loader ) || defined( 'Profiles_LOAD_SOURCE' ) ) {
	$profiles_loader = dirname( __FILE__ ) . '/src/profiles-loader.php';
	$subdir = 'src';
} else {
	$subdir = 'build';
}

// Set source subdirectory
define( 'Profiles_SOURCE_SUBDIRECTORY', $subdir );

// Define overrides - only applicable to those running trunk
if ( ! defined( 'Profiles_PLUGIN_DIR' ) ) {
	define( 'Profiles_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'Profiles_PLUGIN_URL' ) ) {
	// Be nice to symlinked directories
	define( 'Profiles_PLUGIN_URL', plugins_url( trailingslashit( basename( constant( 'Profiles_PLUGIN_DIR' ) ) ) ) );
}

// Include Profiles
include( $profiles_loader );

// Unset the loader, since it's loaded in global scope
unset( $profiles_loader );

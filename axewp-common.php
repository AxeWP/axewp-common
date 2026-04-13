<?php
/**
 * AxeWP Common - Test Bootstrap Plugin
 *
 * This file exists solely to allow wp-env to load this library as a plugin
 * for testing purposes within WordPress.
 *
 * @package AxeWP\Common
 *
 * Plugin Name: AxeWP Common (Test Bootstrap)
 * Description: Shared WordPress and WPGraphQL utilities. This plugin file is for testing only.
 * Version:     0.0.1
 * Author:      AxePress Development
 * License:     GPL-3.0-or-later
 * Requires at least: 6.6
 * Requires PHP: 8.2
 * WPGraphQL at least: 2.4.0
 */

declare( strict_types = 1 );

namespace AxeWP\Common;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Define the plugin constants.
 */
function constants(): void {
	/**
	 * File path to the plugin's main file.
	 */
	define( 'AXEWP_COMMON_FILE', __FILE__ );

	/**
	 * Version of the plugin.
	 */
	define( 'AXEWP_COMMON_VERSION', '0.0.1' );

	/**
	 * Root path to the plugin directory.
	 */
	define( 'AXEWP_COMMON_PATH', plugin_dir_path( AXEWP_COMMON_FILE ) );

	/**
	 * Root URL to the plugin directory.
	 */
	define( 'AXEWP_COMMON_URL', plugin_dir_url( AXEWP_COMMON_FILE ) );
}

constants();

// Fatal if the file isnt found, as the plugin cannot function without it.
require_once __DIR__ . '/vendor/autoload.php';

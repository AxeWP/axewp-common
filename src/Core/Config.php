<?php
/**
 * Config for integrating the library with the plugin
 *
 * @package AxeWP\Common\Core\Config
 */

declare( strict_types = 1 );

namespace AxeWP\Common\Core;

if ( ! class_exists( '\\AxeWP\\Common\\Core\\Config' ) ) { // @codeCoverageIgnore
	/**
	 * Class - Config
	 */
	class Config {
		/**
		 * The hook prefix for the plugin.
		 */
		public static string $hook_prefix;

		/**
		 * Sets the hook prefix for the plugin.
		 *
		 * @param string $hook_prefix the hook prefix to use for this plugin.
		 */
		public static function set_hook_prefix( string $hook_prefix ): void {
			self::$hook_prefix = $hook_prefix;
		}

		/**
		 * Gets the hook prefix for the plugin.
		 */
		public static function hook_prefix(): string {
			if ( empty( self::$hook_prefix ) ) {
				_doing_it_wrong( __METHOD__, esc_html__( 'The hook prefix has not been set. Use ::set_hook_prefix() to set it.', 'axewp' ), '0.1.0' );
			}

			return self::$hook_prefix;
		}
	}
}

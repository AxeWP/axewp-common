<?php
/**
 * Trait for getting Type Names.
 *
 * @package AxeWP\Common\GraphQL\Traits
 */

declare( strict_types = 1 );

namespace AxeWP\Common\GraphQL\Traits;

use AxeWP\Common\Core\Config;

// Bail if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! trait_exists( '\\AxeWP\\Common\\GraphQL\\Traits\\TypeNameTrait' ) ) {

	/**
	 * Trait - TypeNameTrait
	 */
	trait TypeNameTrait {
		/**
		 * Defines the GraphQL type name registered in WPGraphQL.
		 *
		 * @return non-empty-string The GraphQL type name.
		 */
		abstract protected static function type_name(): string;

		/**
		 * Gets the GraphQL type name.
		 *
		 * @return non-empty-string The GraphQL type name.
		 */
		final public static function get_type_name(): string {
			$type_name   = static::type_name();
			$hook_prefix = Config::hook_prefix();

			/**
			 * Filter the GraphQL type name.
			 *
			 * Useful for adding a namespace or preventing plugin conflicts.
			 *
			 * @param string $prefix the prefix for the type.
			 * @param string $type the GraphQL type name.
			 */
			return apply_filters( $hook_prefix . '_type_prefix', '', $type_name ) . $type_name; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		}
	}
}

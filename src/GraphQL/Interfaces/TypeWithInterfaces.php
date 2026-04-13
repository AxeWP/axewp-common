<?php
/**
 * Interface for for classes that register a GraphQL type with interfaces to the GraphQL schema.
 *
 * @package AxeWP\Common\GraphQL\Interfaces
 */

declare( strict_types = 1 );

namespace AxeWP\Common\GraphQL\Interfaces;

// Bail if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

if ( ! interface_exists( '\\AxeWP\\Common\\GraphQL\\Interfaces\\TypeWithInterfaces' ) ) { // @codeCoverageIgnore

	/**
	 * Interface - TypeWithInterfaces.
	 */
	interface TypeWithInterfaces extends GraphQLType {
		/**
		 * Gets the array of GraphQL interfaces that should be applied to the type.
		 *
		 * @return string[]
		 */
		public static function get_interfaces(): array;
	}
}

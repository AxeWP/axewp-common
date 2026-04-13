<?php
/**
 * Interface for classes that register a GraphQL type to the GraphQL schema.
 *
 * @package AxeWP\Common\GraphQL\Interfaces
 */

declare( strict_types = 1 );

namespace AxeWP\Common\GraphQL\Interfaces;

// Bail if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

if ( ! interface_exists( '\\AxeWP\\Common\\GraphQL\\Interfaces\\GraphQLType' ) ) { // @codeCoverageIgnore

	/**
	 * Interface - GraphQLType
	 */
	interface GraphQLType {
		/**
		 * Initialize the class.
		 */
		public function init(): void;

		/**
		 * Register a type to the GraphQL Schema.
		 */
		public function register(): void;
	}
}

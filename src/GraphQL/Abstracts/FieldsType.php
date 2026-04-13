<?php
/**
 * Abstract class to make it easy to register Fields to an existing type in WPGraphQL.
 *
 * @package AxeWP\Common\GraphQL\Abstracts
 */

declare( strict_types = 1 );

namespace AxeWP\Common\GraphQL\Abstracts;

use AxeWP\Common\GraphQL\Interfaces\GraphQLType;
use AxeWP\Common\GraphQL\Interfaces\TypeWithFields;

// Bail if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\\AxeWP\\Common\\GraphQL\\Abstracts\\FieldsType' ) ) {

	/**
	 * Class - FieldsType
	 */
	abstract class FieldsType implements GraphQLType, TypeWithFields {
		/**
		 * {@inheritDoc}
		 */
		public function init(): void {
			add_action( 'graphql_register_types', [ $this, 'register' ] );
		}

		/**
		 * Defines the GraphQL type name registered in WPGraphQL.
		 */
		abstract protected static function type_name(): string;

		/**
		 * Gets the GraphQL type name.
		 */
		abstract public static function get_type_name(): string;

		/**
		 * Register Fields to the GraphQL Schema.
		 */
		public function register(): void {
			register_graphql_fields( static::get_type_name(), static::get_fields() );
		}
	}
}

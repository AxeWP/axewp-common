<?php
/**
 * Trait for getting possible resolve types.
 *
 * @package AxeWP\Common\GraphQL\Traits
 */

declare( strict_types = 1 );

namespace AxeWP\Common\GraphQL\Traits;

use WPGraphQL;

// Bail if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! trait_exists( '\\AxeWP\\Common\\GraphQL\\Traits\\TypeResolverTrait' ) ) {

	/**
	 * Trait - TypeResolverTrait
	 *
	 * @phpstan-import-type TypeDef from \WPGraphQL\Registry\TypeRegistry
	 */
	trait TypeResolverTrait {
		/**
		 * Gets the name of the GraphQL type to that the interface/union resolves to.
		 *
		 * @param mixed $value The value from the resolver of the parent field.
		 */
		abstract public static function get_resolved_type_name( $value ): ?string;

		/**
		 * The type resolver function used in the `resolveType` callback of the GraphQL type.
		 *
		 * @param mixed $value The value from the resolver of the parent field.
		 *
		 * @return ?TypeDef The resolved type definition, or null if resolution fails.
		 * @throws \UnexpectedValueException If the resolved type name is empty or invalid.
		 */
		protected static function resolve_type( $value ) {
			$type_name = static::get_resolved_type_name( $value );

			if ( empty( $type_name ) ) {
				throw new \UnexpectedValueException(
				// translators: the GraphQL type name.
					sprintf( esc_html__( 'The value passed to %s failed to resolve to a valid GraphQL type', 'axewp' ), static::class )
				);
			}

			$type_registry = WPGraphQL::get_type_registry();

			return $type_registry->get_type( $type_name );
		}
	}
}

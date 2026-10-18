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
		abstract protected static function get_resolved_type_name( $value ): ?string;

		/**
		 * The type resolver function used in the `resolveType` callback of the GraphQL type.
		 *
		 * @param mixed $value The value from the resolver of the parent field.
		 *
		 * @return TypeDef The resolved type definition.
		 * @throws \UnexpectedValueException If the resolved type name is empty or isn't a registered type.
		 */
		protected static function resolve_type( $value ) {
			$type_name = static::get_resolved_type_name( $value );

			if ( empty( $type_name ) ) {
				throw new \UnexpectedValueException(
					sprintf( 'The value passed to %s failed to resolve to a valid GraphQL type', static::class )
				);
			}

			$type = WPGraphQL::get_type_registry()->get_type( $type_name );

			if ( null === $type ) {
				throw new \UnexpectedValueException(
					sprintf( '%1$s resolved the type name "%2$s", which is not registered in the GraphQL schema.', static::class, $type_name )
				);
			}

			return $type;
		}
	}
}

<?php
/**
 * Abstract class to make it easy to register Types to WPGraphQL.
 *
 * @package AxeWP\Common\GraphQL\Abstracts
 */

declare( strict_types = 1 );

namespace AxeWP\Common\GraphQL\Abstracts;

use AxeWP\Common\Contracts\Interfaces\Registrable;
use AxeWP\Common\GraphQL\Interfaces\GraphQLType;
use AxeWP\Common\GraphQL\Traits\TypeNameTrait;

// Bail if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\\AxeWP\\Common\\GraphQL\\Abstracts\\Type' ) ) {

	/**
	 * Class - Type
	 *
	 * @phpstan-type BaseTypeConfig array{
	 *  description:callable():string,
	 *  eagerlyLoadType:bool
	 * }
	 *
	 * @template TypeConfig of array
	 */
	abstract class Type implements GraphQLType, Registrable {
		use TypeNameTrait;

		/**
		 * Gets the GraphQL type description.
		 */
		abstract protected static function get_description(): string;

		/**
		 * {@inheritDoc}
		 */
		public function init(): void {
			add_action( 'graphql_register_types', [ $this, 'register' ] );
		}

		/**
		 * {@inheritDoc}
		 */
		public function register_hooks(): void {
			add_action( 'graphql_register_types', [ $this, 'register' ] );
		}

		/**
		 * Gets the $config array used to register the type to WPGraphQL.
		 *
		 * @return TypeConfig&BaseTypeConfig
		 */
		protected static function get_type_config(): array {
			return [
				'description'     => static fn (): string => static::get_description(),
				'eagerlyLoadType' => static::should_load_eagerly(),
			];
		}

		/**
		 * Whether the type should be loaded eagerly by WPGraphQL. Defaults to false.
		 *
		 * Eager load should only be necessary for types that are not referenced directly (e.g. in Unions, Interfaces ).
		 */
		protected static function should_load_eagerly(): bool {
			return false;
		}
	}
}

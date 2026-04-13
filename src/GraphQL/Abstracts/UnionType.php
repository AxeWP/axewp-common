<?php
/**
 * Abstract class to make it easy to register Union types to WPGraphQL.
 *
 * @package AxeWP\Common\GraphQL\Abstracts
 */

declare( strict_types = 1 );

namespace AxeWP\Common\GraphQL\Abstracts;

use AxeWP\Common\GraphQL\Traits\TypeResolverTrait;

// Bail if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\\AxeWP\\Common\\GraphQL\\Abstracts\\UnionType' ) ) {

	/**
	 * Class - UnionType
	 *
	 * phpcs:disable SlevomatCodingStandard.Namespaces.FullyQualifiedClassNameInAnnotation -- PHPStan formatting.
	 *
	 * @phpstan-type UnionTypeConfig array{
	 *   description:callable():string,
	 *   eagerlyLoadType: bool,
	 *   typeNames: string[],
	 *   resolveType: callable,
	 * }
	 *
	 * @extends \AxeWP\Common\GraphQL\Abstracts\Type<UnionTypeConfig>
	 *
	 * phpcs:enable SlevomatCodingStandard.Namespaces.FullyQualifiedClassNameInAnnotation
	 */
	abstract class UnionType extends Type {
		use TypeResolverTrait;

		/**
		 * Gets the array of possible GraphQL types that can be resolved to.
		 *
		 * @return string[]
		 */
		abstract public static function get_possible_types(): array;

		/**
		 * {@inheritDoc}
		 */
		public function register(): void {
			register_graphql_union_type( static::get_type_name(), static::get_type_config() );
		}

		/**
		 * {@inheritDoc}
		 *
		 * @return UnionTypeConfig
		 */
		protected static function get_type_config(): array {
			$config                = parent::get_type_config();
			$config['typeNames']   = static::get_possible_types();
			$config['resolveType'] = [ static::class, 'resolve_type' ];

			return $config;
		}

		/**
		 * {@inheritDoc}
		 */
		protected static function should_load_eagerly(): bool {
			return true;
		}
	}
}

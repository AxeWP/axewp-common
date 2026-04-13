<?php
/**
 * Abstract class to make it easy to register Interface types to WPGraphQL.
 *
 * @package AxeWP\Common\GraphQL\Abstracts
 */

declare( strict_types = 1 );

namespace AxeWP\Common\GraphQL\Abstracts;

use AxeWP\Common\GraphQL\Interfaces\TypeWithFields;

// Bail if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\\AxeWP\\Common\\GraphQL\\Abstracts\\InterfaceType' ) ) {

	/**
	 * Class - InterfaceType
	 *
	 * phpcs:disable SlevomatCodingStandard.Namespaces.FullyQualifiedClassNameInAnnotation -- PHPStan formatting.
	 *
	 * @phpstan-import-type FieldConfig from \AxeWP\Common\GraphQL\Interfaces\TypeWithFields
	 *
	 * @phpstan-type InterfaceTypeConfig array{
	 *  description:callable():string,
	 *  eagerlyLoadType: bool,
	 *  fields: array<string,FieldConfig>,
	 *  resolveType?: callable,
	 *  interfaces?: string[],
	 * }
	 *
	 * @extends \AxeWP\Common\GraphQL\Abstracts\Type<InterfaceTypeConfig>
	 *
	 * phpcs:enable SlevomatCodingStandard.Namespaces.FullyQualifiedClassNameInAnnotation
	 */
	abstract class InterfaceType extends Type implements TypeWithFields {
		/**
		 * {@inheritDoc}
		 */
		public function register(): void {
			register_graphql_interface_type( static::get_type_name(), static::get_type_config() );
		}

		/**
		 * {@inheritDoc}
		 */
		protected static function get_type_config(): array {
			$config           = parent::get_type_config();
			$config['fields'] = static::get_fields();

			if ( method_exists( static::class, 'resolve_type' ) ) {
				$config['resolveType'] = [ static::class, 'resolve_type' ];
			}

			if ( method_exists( static::class, 'get_interfaces' ) ) {
				$config['interfaces'] = static::get_interfaces();
			}

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

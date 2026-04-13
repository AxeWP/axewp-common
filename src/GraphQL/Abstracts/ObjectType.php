<?php
/**
 * Abstract class to make it easy to register Object types to WPGraphQL.
 *
 * @package AxeWP\Common\GraphQL\Abstracts
 */

declare( strict_types = 1 );

namespace AxeWP\Common\GraphQL\Abstracts;

use AxeWP\Common\GraphQL\Interfaces\TypeWithFields;

// Bail if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\\AxeWP\\Common\\GraphQL\\Abstracts\\ObjectType' ) ) {

	/**
	 * Class - ObjectType
	 *
	 * phpcs:disable SlevomatCodingStandard.Namespaces.FullyQualifiedClassNameInAnnotation -- PHPStan formatting.
	 *
	 * @phpstan-import-type FieldConfig from \AxeWP\Common\GraphQL\Interfaces\TypeWithFields
	 * @phpstan-import-type ConnectionConfig from \AxeWP\Common\GraphQL\Interfaces\TypeWithConnections
	 *
	 * @phpstan-type ObjectTypeConfig array{
	 *   description:callable():string,
	 *   eagerlyLoadType: bool,
	 *   fields: array<string,FieldConfig>,
	 *   connections?: array<string,ConnectionConfig>,
	 *   resolveType?: callable,
	 *   interfaces?: string[],
	 * }
	 *
	 * @extends \AxeWP\Common\GraphQL\Abstracts\Type<ObjectTypeConfig>
	 *
	 * phpcs:enable SlevomatCodingStandard.Namespaces.FullyQualifiedClassNameInAnnotation
	 */
	abstract class ObjectType extends Type implements TypeWithFields {
		/**
		 * {@inheritDoc}
		 */
		public function register(): void {
			register_graphql_object_type( static::get_type_name(), static::get_type_config() );
		}

		/**
		 * {@inheritDoc}
		 *
		 * @return ObjectTypeConfig
		 */
		protected static function get_type_config(): array {
			$config = parent::get_type_config();

			$config['fields'] = static::get_fields();

			if ( method_exists( static::class, 'get_connections' ) ) {
				$config['connections'] = static::get_connections();
			}

			if ( method_exists( static::class, 'get_interfaces' ) ) {
				$config['interfaces'] = static::get_interfaces();
			}

			return $config;
		}
	}
}

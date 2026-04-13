<?php
/**
 * Abstract class to make it easy to register Enum types to WPGraphQL.
 *
 * @package AxeWP\Common\GraphQL\Abstracts
 */

declare( strict_types = 1 );

namespace AxeWP\Common\GraphQL\Abstracts;

// Bail if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\\AxeWP\\Common\\GraphQL\\Abstracts\\EnumType' ) ) {

	/**
	 * Class - EnumType
	 *
	 * phpcs:disable SlevomatCodingStandard.Namespaces.FullyQualifiedClassNameInAnnotation -- PHPStan formatting.
	 *
	 * @phpstan-type EnumValueConfig array{
	 *   description:callable():string,
	 *   value:mixed,
	 *   deprecationReason?:callable():string
	 * }
	 *
	 * @phpstan-type EnumTypeConfig array{
	 *  description: callable():string,
	 *  eagerlyLoadType: bool,
	 *  values:array<string,EnumValueConfig>,
	 * }
	 *
	 * @extends \AxeWP\Common\GraphQL\Abstracts\Type<EnumTypeConfig>
	 *
	 * phpcs:enable SlevomatCodingStandard.Namespaces.FullyQualifiedClassNameInAnnotation
	 */
	abstract class EnumType extends Type {
		/**
		 * Gets the Enum values configuration array.
		 *
		 * @return array<string,EnumValueConfig>
		 */
		abstract public static function get_values(): array;

		/**
		 * {@inheritDoc}
		 */
		public function register(): void {
			register_graphql_enum_type( static::get_type_name(), static::get_type_config() );
		}

		/**
		 * {@inheritDoc}
		 *
		 * @return EnumTypeConfig
		 */
		protected static function get_type_config(): array {
			$config = parent::get_type_config();

			$config['values'] = static::get_values();

			return $config;
		}
	}
}

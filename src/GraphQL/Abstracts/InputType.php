<?php
/**
 * Abstract class to make it easy to register Input types to WPGraphQL.
 *
 * @package AxeWP\Common\GraphQL\Abstracts
 */

declare( strict_types = 1 );

namespace AxeWP\Common\GraphQL\Abstracts;

use AxeWP\Common\GraphQL\Interfaces\TypeWithInputFields;

// Bail if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\\AxeWP\\Common\\GraphQL\\Abstracts\\InputType' ) ) {

	/**
	 * Class - InputType
	 *
	 * phpcs:disable SlevomatCodingStandard.Namespaces.FullyQualifiedClassNameInAnnotation -- PHPStan formatting.
	 *
	 * @phpstan-import-type InputFieldConfig from \AxeWP\Common\GraphQL\Interfaces\TypeWithInputFields
	 *
	 * @phpstan-type InputTypeConfig array{
	 *  description: callable():string,
	 *  eagerlyLoadType: bool,
	 *  fields: array<string,InputFieldConfig>,
	 * }
	 *
	 * @extends \AxeWP\Common\GraphQL\Abstracts\Type<InputTypeConfig>
	 *
	 * phpcs:enable SlevomatCodingStandard.Namespaces.FullyQualifiedClassNameInAnnotation
	 */
	abstract class InputType extends Type implements TypeWithInputFields {
		/**
		 * {@inheritDoc}
		 */
		public function register(): void {
			register_graphql_input_type( static::get_type_name(), static::get_type_config() );
		}

		/**
		 * {@inheritDoc}
		 *
		 * @return InputTypeConfig
		 */
		protected static function get_type_config(): array {
			$config           = parent::get_type_config();
			$config['fields'] = static::get_fields();

			return $config;
		}
	}
}

<?php
/**
 * Interface for classes that register a GraphQL type with input fields to the GraphQL schema.
 *
 * @package AxeWP\Common\GraphQL\Interfaces
 */

declare( strict_types = 1 );

namespace AxeWP\Common\GraphQL\Interfaces;

// Bail if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

if ( ! interface_exists( '\\AxeWP\\Common\\GraphQL\\Interfaces\\TypeWithInputFields' ) ) { // @codeCoverageIgnore

	/**
	 * Interface - TypeWithInputFields.
	 *
	 * @phpstan-type InputFieldConfig array{
	 *   type:string|array<string,string|array<string,string>>,
	 *   description: callable(): string,
	 *   defaultValue?:string
	 * }
	 */
	interface TypeWithInputFields extends GraphQLType {
		/**
		 * Gets the input fields for the type.
		 *
		 * @return array<string,InputFieldConfig>
		 */
		public static function get_fields(): array;
	}
}

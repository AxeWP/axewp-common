<?php
/**
 * Interface for classes that register a GraphQL type with connections to the GraphQL schema.
 *
 * @package AxeWP\Common\GraphQL\Interfaces
 */

declare( strict_types = 1 );

namespace AxeWP\Common\GraphQL\Interfaces;

// Bail if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

if ( ! interface_exists( '\\AxeWP\\Common\\GraphQL\\Interfaces\\TypeWithConnections' ) ) { // @codeCoverageIgnore

	/**
	 * Interface - TypeWithConnections
	 *
	 * phpcs:disable SlevomatCodingStandard.Namespaces.FullyQualifiedClassNameInAnnotation -- PHPStan formatting.
	 *
	 * @phpstan-type ConnectionConfigArgs array{
	 *   type: string|array<string,string|array<string,string>>,
	 *   description: callable(): string,
	 *   defaultValue?: mixed
	 * }
	 *
	 * @phpstan-type ConnectionConfig array{
	 *   toType: string,
	 *   description: callable():string,
	 *   args?: array<string,ConnectionConfigArgs>,
	 *   connectionInterfaces?: string[],
	 *   oneToOne?: bool,
	 *   resolve?: callable
	 * }
	 *
	 * phpcs:enable SlevomatCodingStandard.Namespaces.FullyQualifiedClassNameInAnnotation
	 */
	interface TypeWithConnections extends GraphQLType {
		/**
		 * Gets the properties for the type.
		 *
		 * @return array<string,ConnectionConfig>
		 */
		public static function get_connections(): array;
	}
}

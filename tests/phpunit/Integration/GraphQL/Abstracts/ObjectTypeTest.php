<?php
/**
 * ObjectTypeTest file.
 *
 * @package AxeWP\Common\Tests\Integration\GraphQL\Abstracts
 */

declare( strict_types = 1 );

namespace AxeWP\Common\Tests\Integration\GraphQL\Abstracts;

use AxeWP\Common\Core\Config;
use AxeWP\Common\GraphQL\Abstracts\ObjectType;
use AxeWP\Common\GraphQL\Interfaces\TypeWithConnections;
use AxeWP\Common\GraphQL\Interfaces\TypeWithInterfaces;
use AxeWP\Common\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Concrete test double for ObjectType.
 */
final class ConcreteObjectTypeTestDouble extends ObjectType {
	/**
	 * {@inheritDoc}
	 */
	protected static function type_name(): string {
		return 'TestObjectType';
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function get_description(): string {
		return 'Test object type';
	}

	/**
	 * Get fields.
	 *
	 * @return array<string,array{type:string,description:callable():string}>
	 */
	public static function get_fields(): array {
		return [
			'id' => [
				'type'        => 'ID',
				'description' => static fn (): string => 'The ID',
			],
		];
	}
}

/**
 * Object type test double that implements TypeWithConnections.
 */
final class ConnectingObjectTypeTestDouble extends ObjectType implements TypeWithConnections {
	/**
	 * {@inheritDoc}
	 */
	protected static function type_name(): string {
		return 'ConnectingTestObjectType';
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function get_description(): string {
		return 'Connecting test object type';
	}

	/**
	 * Get fields.
	 *
	 * @return array<string,array{type:string,description:callable():string}>
	 */
	public static function get_fields(): array {
		return [
			'id' => [
				'type'        => 'ID',
				'description' => static fn (): string => 'The ID',
			],
		];
	}

	/**
	 * Get connections.
	 *
	 * @return array<string,array{toType:string,description:callable():string}>
	 */
	public static function get_connections(): array {
		return [
			'children' => [
				'toType'      => 'ChildNode',
				'description' => static fn (): string => 'Child nodes',
			],
		];
	}
}

/**
 * Object type test double that implements TypeWithInterfaces.
 */
final class InterfacingObjectTypeTestDouble extends ObjectType implements TypeWithInterfaces {
	/**
	 * {@inheritDoc}
	 */
	protected static function type_name(): string {
		return 'InterfacingTestObjectType';
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function get_description(): string {
		return 'Interfacing test object type';
	}

	/**
	 * Get fields.
	 *
	 * @return array<string,array{type:string,description:callable():string}>
	 */
	public static function get_fields(): array {
		return [
			'id' => [
				'type'        => 'ID',
				'description' => static fn (): string => 'The ID',
			],
		];
	}

	/**
	 * Get interfaces.
	 *
	 * @return string[]
	 */
	public static function get_interfaces(): array {
		return [ 'Node' ];
	}
}

/**
 * Class - ObjectTypeTest
 */
#[CoversClass( ObjectType::class )]
class ObjectTypeTest extends TestCase {
	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();

		Config::set_hook_prefix( 'test_graphql' );

		// Enable public introspection for schema queries.
		$settings                                 = get_option( 'graphql_general_settings', [] );
		$settings['public_introspection_enabled'] = 'on';
		update_option( 'graphql_general_settings', $settings );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		\WPGraphQL::clear_schema();
		Config::$hook_prefix = '';

		parent::tearDown();
	}

	/**
	 * Test that the object type is registered in the schema with its fields.
	 */
	public function test_object_type_is_registered_with_fields(): void {
		$instance = new ConcreteObjectTypeTestDouble();
		$instance->register_hooks();
		\WPGraphQL::clear_schema();

		$query = '
			{
				__type(name: "TestObjectType") {
					name
					kind
					fields {
						name
						type {
							name
						}
					}
				}
			}
		';

		$actual = graphql( [ 'query' => $query ] );

		$this->assertArrayNotHasKey( 'errors', $actual, 'GraphQL response should not contain errors.' );
		$this->assertArrayHasKey( 'data', $actual );
		$this->assertArrayHasKey( '__type', $actual['data'] );

		$type = $actual['data']['__type'];

		$this->assertSame( 'TestObjectType', $type['name'] );
		$this->assertSame( 'OBJECT', $type['kind'] );

		$field_names = array_column( $type['fields'], 'name' );
		$this->assertContains( 'id', $field_names );
	}

	/**
	 * Test that the object type with connections registers the connection field.
	 */
	public function test_object_type_with_connections_has_connection_field(): void {
		// Register the target type for the connection.
		add_action(
			'graphql_register_types',
			static function (): void {
				register_graphql_object_type(
					'ChildNode',
					[
						'description' => static fn (): string => 'A child node',
						'fields'      => [
							'id' => [
								'type'        => 'ID',
								'description' => static fn (): string => 'The ID',
							],
						],
					]
				);
			}
		);

		$instance = new ConnectingObjectTypeTestDouble();
		$instance->register_hooks();
		\WPGraphQL::clear_schema();

		$query = '
			{
				__type(name: "ConnectingTestObjectType") {
					name
					kind
					fields {
						name
					}
				}
			}
		';

		$actual = graphql( [ 'query' => $query ] );

		$this->assertArrayNotHasKey( 'errors', $actual, 'GraphQL response should not contain errors.' );
		$this->assertArrayHasKey( 'data', $actual );
		$this->assertArrayHasKey( '__type', $actual['data'] );

		$type        = $actual['data']['__type'];
		$field_names = array_column( $type['fields'], 'name' );

		$this->assertSame( 'ConnectingTestObjectType', $type['name'] );
		$this->assertSame( 'OBJECT', $type['kind'] );
		$this->assertContains( 'children', $field_names );
	}

	/**
	 * Test that the object type with interfaces implements the specified interface.
	 */
	public function test_object_type_with_interfaces_implements_node(): void {
		$instance = new InterfacingObjectTypeTestDouble();
		$instance->register_hooks();
		\WPGraphQL::clear_schema();

		$query = '
			{
				__type(name: "InterfacingTestObjectType") {
					name
					kind
					interfaces {
						name
					}
				}
			}
		';

		$actual = graphql( [ 'query' => $query ] );

		$this->assertArrayNotHasKey( 'errors', $actual, 'GraphQL response should not contain errors.' );
		$this->assertArrayHasKey( 'data', $actual );
		$this->assertArrayHasKey( '__type', $actual['data'] );

		$type            = $actual['data']['__type'];
		$interface_names = array_column( $type['interfaces'], 'name' );

		$this->assertSame( 'InterfacingTestObjectType', $type['name'] );
		$this->assertContains( 'Node', $interface_names );
	}
}

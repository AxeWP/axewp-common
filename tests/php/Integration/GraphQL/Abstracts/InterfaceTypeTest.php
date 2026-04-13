<?php
/**
 * InterfaceTypeTest file.
 *
 * @package AxeWP\Common\Tests\Integration\GraphQL\Abstracts
 */

declare( strict_types = 1 );

namespace AxeWP\Common\Tests\Integration\GraphQL\Abstracts;

use AxeWP\Common\Core\Config;
use AxeWP\Common\GraphQL\Abstracts\InterfaceType;
use AxeWP\Common\GraphQL\Traits\TypeResolverTrait;
use AxeWP\Common\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Interface type test double.
 */
final class ConcreteInterfaceTypeTestDouble extends InterfaceType {
	/**
	 * @return non-empty-string
	 */
	protected static function type_name(): string {
		return 'TestInterfaceType';
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function get_description(): string {
		return 'Test interface type';
	}

	/**
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
 * Resolving interface type test double.
 */
final class ResolvingInterfaceTypeTestDouble extends InterfaceType {
	use TypeResolverTrait;

	/**
	 * @return non-empty-string
	 */
	protected static function type_name(): string {
		return 'ResolvingTestInterface';
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function get_description(): string {
		return 'Resolving interface';
	}

	/**
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
	 * Resolve the type name from the value.
	 *
	 * @param mixed $value Source value passed to the resolver.
	 */
	public static function get_resolved_type_name( $value ): ?string {
		return is_array( $value ) ? ( $value['type'] ?? null ) : null;
	}
}

/**
 * Interfacing interface type test double.
 */
final class InterfacingInterfaceTypeTestDouble extends InterfaceType {
	/**
	 * @return non-empty-string
	 */
	protected static function type_name(): string {
		return 'InterfacingTestInterface';
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function get_description(): string {
		return 'Interfacing interface';
	}

	/**
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
	 * @return string[]
	 */
	public static function get_interfaces(): array {
		return [ 'Node' ];
	}
}

/**
 * Tests interface type behavior.
 */
#[CoversClass( InterfaceType::class )]
final class InterfaceTypeTest extends TestCase {
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
	 * Test that the interface type is registered with its fields.
	 */
	public function test_interface_type_is_registered_with_fields(): void {
		$instance = new ConcreteInterfaceTypeTestDouble();
		$instance->register_hooks();
		\WPGraphQL::clear_schema();

		$query = '
			{
				__type(name: "TestInterfaceType") {
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

		$this->assertSame( 'TestInterfaceType', $type['name'] );
		$this->assertSame( 'INTERFACE', $type['kind'] );

		$field_names = array_column( $type['fields'], 'name' );
		$this->assertContains( 'id', $field_names );
	}

	/**
	 * Test that interface types load eagerly by verifying the type exists in the schema.
	 */
	public function test_interface_type_is_eagerly_loaded(): void {
		$instance = new ConcreteInterfaceTypeTestDouble();
		$instance->register_hooks();
		\WPGraphQL::clear_schema();

		// If the type appears in introspection without being referenced by a field,
		// it was loaded eagerly.
		$query = '
			{
				__type(name: "TestInterfaceType") {
					name
				}
			}
		';

		$actual = graphql( [ 'query' => $query ] );

		$this->assertArrayNotHasKey( 'errors', $actual, 'GraphQL response should not contain errors.' );
		$this->assertNotNull( $actual['data']['__type'] );
	}

	/**
	 * Test that an interface with a type resolver is registered successfully.
	 */
	public function test_resolving_interface_type_is_registered(): void {
		$instance = new ResolvingInterfaceTypeTestDouble();
		$instance->register_hooks();
		\WPGraphQL::clear_schema();

		$query = '
			{
				__type(name: "ResolvingTestInterface") {
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

		$type = $actual['data']['__type'];

		$this->assertSame( 'ResolvingTestInterface', $type['name'] );
		$this->assertSame( 'INTERFACE', $type['kind'] );
	}

	/**
	 * Test that an interface implementing other interfaces is registered correctly.
	 */
	public function test_interfacing_interface_type_implements_node(): void {
		$instance = new InterfacingInterfaceTypeTestDouble();
		$instance->register_hooks();
		\WPGraphQL::clear_schema();

		$query = '
			{
				__type(name: "InterfacingTestInterface") {
					name
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

		$this->assertSame( 'InterfacingTestInterface', $type['name'] );
		$this->assertContains( 'Node', $interface_names );
	}
}

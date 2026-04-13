<?php
/**
 * UnionTypeTest file.
 *
 * @package AxeWP\Common\Tests\Integration\GraphQL\Abstracts
 */

declare( strict_types = 1 );

namespace AxeWP\Common\Tests\Integration\GraphQL\Abstracts;

use AxeWP\Common\Core\Config;
use AxeWP\Common\GraphQL\Abstracts\UnionType;
use AxeWP\Common\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Union type test double.
 */
final class ConcreteUnionTypeTestDouble extends UnionType {
	/**
	 * @return non-empty-string
	 */
	protected static function type_name(): string {
		return 'TestUnionType';
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function get_description(): string {
		return 'Test union type';
	}

	/**
	 * @return string[]
	 */
	public static function get_possible_types(): array {
		return [ 'TypeA', 'TypeB' ];
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
 * Tests union type behavior.
 */
#[CoversClass( UnionType::class )]
final class UnionTypeTest extends TestCase {
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
	 * Test that the union type is registered with its possible types.
	 */
	public function test_union_type_is_registered_with_possible_types(): void {
		// Register the member types so the union can reference them.
		add_action(
			'graphql_register_types',
			static function (): void {
				foreach ( [ 'TypeA', 'TypeB' ] as $type_name ) {
					register_graphql_object_type(
						$type_name,
						[
							'description' => static fn (): string => "Test type {$type_name}",
							'fields'      => [
								'id' => [
									'type'        => 'ID',
									'description' => static fn (): string => 'The ID',
								],
							],
						]
					);
				}
			}
		);

		$instance = new ConcreteUnionTypeTestDouble();
		$instance->register_hooks();
		\WPGraphQL::clear_schema();

		$query = '
			{
				__type(name: "TestUnionType") {
					name
					kind
					possibleTypes {
						name
					}
				}
			}
		';

		$actual = graphql( [ 'query' => $query ] );

		$this->assertArrayNotHasKey( 'errors', $actual, 'GraphQL response should not contain errors.' );
		$this->assertArrayHasKey( 'data', $actual );
		$this->assertArrayHasKey( '__type', $actual['data'] );

		$type       = $actual['data']['__type'];
		$type_names = array_column( $type['possibleTypes'], 'name' );

		$this->assertSame( 'TestUnionType', $type['name'] );
		$this->assertSame( 'UNION', $type['kind'] );
		$this->assertContains( 'TypeA', $type_names );
		$this->assertContains( 'TypeB', $type_names );
	}

	/**
	 * Test that union types load eagerly by verifying the type exists in the schema.
	 */
	public function test_union_type_is_eagerly_loaded(): void {
		// Register the member types so the union can reference them.
		add_action(
			'graphql_register_types',
			static function (): void {
				foreach ( [ 'TypeA', 'TypeB' ] as $type_name ) {
					register_graphql_object_type(
						$type_name,
						[
							'description' => static fn (): string => "Test type {$type_name}",
							'fields'      => [
								'id' => [
									'type'        => 'ID',
									'description' => static fn (): string => 'The ID',
								],
							],
						]
					);
				}
			}
		);

		$instance = new ConcreteUnionTypeTestDouble();
		$instance->register_hooks();
		\WPGraphQL::clear_schema();

		// If the type appears in introspection without being referenced by a field,
		// it was loaded eagerly.
		$query = '
			{
				__type(name: "TestUnionType") {
					name
				}
			}
		';

		$actual = graphql( [ 'query' => $query ] );

		$this->assertArrayNotHasKey( 'errors', $actual, 'GraphQL response should not contain errors.' );
		$this->assertNotNull( $actual['data']['__type'] );
	}
}

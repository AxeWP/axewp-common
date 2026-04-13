<?php
/**
 * EnumTypeTest file.
 *
 * @package AxeWP\Common\Tests\Integration\GraphQL\Abstracts
 */

declare( strict_types = 1 );

namespace AxeWP\Common\Tests\Integration\GraphQL\Abstracts;

use AxeWP\Common\Core\Config;
use AxeWP\Common\GraphQL\Abstracts\EnumType;
use AxeWP\Common\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Concrete test double for EnumType.
 */
final class ConcreteEnumTypeTestDouble extends EnumType {
	/**
	 * {@inheritDoc}
	 */
	protected static function type_name(): string {
		return 'TestEnumType';
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function get_description(): string {
		return 'Test enum type';
	}

	/**
	 * Get values.
	 *
	 * @return array<string,array{value:string,description:callable():string}>
	 */
	public static function get_values(): array {
		return [
			'VALUE_A' => [
				'value'       => 'a',
				'description' => static fn (): string => 'Value A',
			],
		];
	}
}

/**
 * Class - EnumTypeTest
 */
#[CoversClass( EnumType::class )]
class EnumTypeTest extends TestCase {
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

		$instance = new ConcreteEnumTypeTestDouble();
		$instance->register_hooks();

		\WPGraphQL::clear_schema();
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
	 * Test that the enum type is registered in the schema with its values.
	 */
	public function test_enum_type_is_registered_in_schema(): void {
		$query = '
			{
				__type(name: "TestEnumType") {
					name
					kind
					enumValues {
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

		$this->assertSame( 'TestEnumType', $type['name'] );
		$this->assertSame( 'ENUM', $type['kind'] );

		$enum_value_names = array_column( $type['enumValues'], 'name' );
		$this->assertContains( 'VALUE_A', $enum_value_names );
	}
}

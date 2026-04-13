<?php
/**
 * InputTypeTest file.
 *
 * @package AxeWP\Common\Tests\Integration\GraphQL\Abstracts
 */

declare( strict_types = 1 );

namespace AxeWP\Common\Tests\Integration\GraphQL\Abstracts;

use AxeWP\Common\Core\Config;
use AxeWP\Common\GraphQL\Abstracts\InputType;
use AxeWP\Common\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Concrete test double for InputType.
 */
final class ConcreteInputTypeTestDouble extends InputType {
	/**
	 * {@inheritDoc}
	 */
	protected static function type_name(): string {
		return 'TestInputType';
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function get_description(): string {
		return 'Test input type';
	}

	/**
	 * Get fields.
	 *
	 * @return array<string,array{type:string,description:callable():string}>
	 */
	public static function get_fields(): array {
		return [
			'name' => [
				'type'        => 'String',
				'description' => static fn (): string => 'The name',
			],
		];
	}
}

/**
 * Class - InputTypeTest
 */
#[CoversClass( InputType::class )]
class InputTypeTest extends TestCase {
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

		$instance = new ConcreteInputTypeTestDouble();
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
	 * Test that the input type is registered in the schema with its fields.
	 */
	public function test_input_type_is_registered_in_schema(): void {
		$query = '
			{
				__type(name: "TestInputType") {
					name
					kind
					inputFields {
						name
						type {
							name
							kind
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

		$this->assertSame( 'TestInputType', $type['name'] );
		$this->assertSame( 'INPUT_OBJECT', $type['kind'] );

		$field_names = array_column( $type['inputFields'], 'name' );
		$this->assertContains( 'name', $field_names );
	}
}

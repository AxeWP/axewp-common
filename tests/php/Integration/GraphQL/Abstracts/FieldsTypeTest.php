<?php
/**
 * FieldsTypeTest file.
 *
 * @package AxeWP\Common\Tests\Integration\GraphQL\Abstracts
 */

declare( strict_types = 1 );

namespace AxeWP\Common\Tests\Integration\GraphQL\Abstracts;

use AxeWP\Common\Core\Config;
use AxeWP\Common\GraphQL\Abstracts\FieldsType;
use AxeWP\Common\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Concrete test double for FieldsType.
 */
final class ConcreteFieldsTypeTestDouble extends FieldsType {
	/**
	 * {@inheritDoc}
	 */
	protected static function type_name(): string {
		return 'TestFieldsType';
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_type_name(): string {
		return 'Post';
	}

	/**
	 * Get fields.
	 *
	 * @return array<string,array{type:string,description:callable():string}>
	 */
	public static function get_fields(): array {
		return [
			'testField' => [
				'type'        => 'String',
				'description' => static fn (): string => 'A test field',
			],
		];
	}
}

/**
 * Class - FieldsTypeTest
 */
#[CoversClass( FieldsType::class )]
class FieldsTypeTest extends TestCase {
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

		$instance = new ConcreteFieldsTypeTestDouble();
		$instance->init();

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
	 * Test that init adds the graphql_register_types action.
	 */
	public function test_init_adds_graphql_register_types_action(): void {
		$instance = new ConcreteFieldsTypeTestDouble();
		$instance->init();

		$this->assertNotFalse( has_action( 'graphql_register_types', [ $instance, 'register' ] ) );
	}

	/**
	 * Test that fields are registered on the target type in the schema.
	 */
	public function test_fields_are_registered_on_target_type(): void {
		$query = '
			{
				__type(name: "Post") {
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

		$field_names = array_column( $actual['data']['__type']['fields'], 'name' );
		$this->assertContains( 'testField', $field_names );
	}
}

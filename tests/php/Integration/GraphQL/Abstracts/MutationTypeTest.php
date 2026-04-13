<?php
/**
 * MutationTypeTest file.
 *
 * @package AxeWP\Common\Tests\Integration\GraphQL\Abstracts
 */

declare( strict_types = 1 );

namespace AxeWP\Common\Tests\Integration\GraphQL\Abstracts;

use AxeWP\Common\Core\Config;
use AxeWP\Common\GraphQL\Abstracts\MutationType;
use AxeWP\Common\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Mutation type test double.
 */
final class ConcreteMutationTypeTestDouble extends MutationType {
	/**
	 * @return non-empty-string
	 */
	protected static function type_name(): string {
		return 'TestMutation';
	}

	/**
	 * @return array<string,array{type:string,description:callable():string}>
	 */
	public static function get_input_fields(): array {
		return [
			'input' => [
				'type'        => 'String',
				'description' => static fn (): string => 'Input field',
			],
		];
	}

	/**
	 * @return array<string,array{type:string,description:callable():string}>
	 */
	public static function get_output_fields(): array {
		return [
			'result' => [
				'type'        => 'String',
				'description' => static fn (): string => 'Result field',
			],
		];
	}

	/**
	 * @return callable():array{result:string}
	 */
	public static function mutate_and_get_payload(): callable {
		return static fn (): array => [ 'result' => 'ok' ];
	}
}

/**
 * Tests mutation type behavior.
 */
#[CoversClass( MutationType::class )]
final class MutationTypeTest extends TestCase {
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

		$instance = new ConcreteMutationTypeTestDouble();
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
	 * Verifies mutation descriptions default to an empty string.
	 */
	public function test_get_description_returns_empty_string(): void {
		$this->assertSame( '', ConcreteMutationTypeTestDouble::get_description() );
	}

	/**
	 * Verifies the mutation is registered and can be executed via GraphQL.
	 */
	public function test_mutation_is_registered_and_executable(): void {
		$query = '
			mutation TestMutation($input: TestMutationInput!) {
				testMutation(input: $input) {
					result
				}
			}
		';

		$actual = graphql(
			[
				'query'     => $query,
				'variables' => [
					'input' => [
						'input' => 'hello',
					],
				],
			]
		);

		$this->assertArrayNotHasKey( 'errors', $actual, 'GraphQL response should not contain errors.' );
		$this->assertArrayHasKey( 'data', $actual );
		$this->assertSame( 'ok', $actual['data']['testMutation']['result'] );
	}

	/**
	 * Verifies the mutation input type is registered in the schema.
	 */
	public function test_mutation_input_type_is_registered_in_schema(): void {
		$query = '
			{
				__type(name: "TestMutationInput") {
					name
					kind
					inputFields {
						name
					}
				}
			}
		';

		$actual = graphql( [ 'query' => $query ] );

		$this->assertArrayNotHasKey( 'errors', $actual, 'GraphQL response should not contain errors.' );
		$this->assertArrayHasKey( 'data', $actual );
		$this->assertArrayHasKey( '__type', $actual['data'] );

		$type              = $actual['data']['__type'];
		$input_field_names = array_column( $type['inputFields'], 'name' );

		$this->assertSame( 'TestMutationInput', $type['name'] );
		$this->assertContains( 'input', $input_field_names );
	}
}

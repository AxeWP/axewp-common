<?php
/**
 * ConnectionTypeTest file.
 *
 * @package AxeWP\Common\Tests\Integration\GraphQL\Abstracts
 */

declare( strict_types = 1 );

namespace AxeWP\Common\Tests\Integration\GraphQL\Abstracts;

use AxeWP\Common\Core\Config;
use AxeWP\Common\GraphQL\Abstracts\ConnectionType;
use AxeWP\Common\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Connection type test double with noop register.
 */
final class ConcreteConnectionTypeTestDouble extends ConnectionType {
	/**
	 * @return non-empty-string
	 */
	protected static function type_name(): string {
		return 'TestConnectionType';
	}

	/**
	 * @return array<string,array{type:string,description:callable():string}>
	 */
	protected static function connection_args(): array {
		return [
			'first' => [
				'type'        => 'Int',
				'description' => static fn (): string => 'First N items',
			],
			'after' => [
				'type'        => 'String',
				'description' => static fn (): string => 'After cursor',
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		// Noop for testing.
	}
}

/**
 * Connection type test double that actually registers a connection.
 */
final class RegisteringConnectionTypeTestDouble extends ConnectionType {
	/**
	 * @return non-empty-string
	 */
	protected static function type_name(): string {
		return 'Post';
	}

	/**
	 * @return array<string,array{type:string,description:callable():string}>
	 */
	protected static function connection_args(): array {
		return [
			'testArg' => [
				'type'        => 'String',
				'description' => static fn (): string => 'A test arg',
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		register_graphql_connection(
			array_merge(
				self::get_connection_config(
					[
						'fromType'      => 'RootQuery',
						'fromFieldName' => 'testConnection',
						'resolve'       => static fn () => null,
					]
				),
				[ 'connectionArgs' => self::connection_args() ]
			)
		);
	}
}

/**
 * Tests connection type behavior.
 */
#[CoversClass( ConnectionType::class )]
final class ConnectionTypeTest extends TestCase {
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
	 * Verifies all connection args are returned without filtering.
	 */
	public function test_get_connection_args(): void {
		$args = ConcreteConnectionTypeTestDouble::get_connection_args( null );

		$this->assertArrayHasKey( 'first', $args );
		$this->assertArrayHasKey( 'after', $args );

		// Test they can be filtered.
		$args = ConcreteConnectionTypeTestDouble::get_connection_args( [ 'first' ] );

		$this->assertSame( [ 'first' ], array_keys( $args ) );
	}

	/**
	 * Verifies a connection is registered in the schema with its args.
	 */
	public function test_connection_is_registered_in_schema(): void {
		$instance = new RegisteringConnectionTypeTestDouble();
		$instance->init();
		\WPGraphQL::clear_schema();

		$query = '
			{
				__type(name: "RootQuery") {
					fields {
						name
						args {
							name
							type {
								name
								kind
								inputFields {
									name
								}
							}
						}
					}
				}
			}
		';

		$actual = graphql( [ 'query' => $query ] );

		$this->assertArrayNotHasKey( 'errors', $actual, 'GraphQL response should not contain errors.' );
		$this->assertArrayHasKey( 'data', $actual );
		$this->assertArrayHasKey( '__type', $actual['data'] );

		$fields           = $actual['data']['__type']['fields'];
		$connection_field = null;

		foreach ( $fields as $field ) {
			if ( 'testConnection' === $field['name'] ) {
				$connection_field = $field;
				break;
			}
		}

		$this->assertNotNull( $connection_field, 'testConnection field should exist on RootQuery.' );

		// WPGraphQL wraps custom connection args inside a 'where' input field.
		$where_arg = null;
		foreach ( $connection_field['args'] as $arg ) {
			if ( 'where' === $arg['name'] ) {
				$where_arg = $arg;
				break;
			}
		}

		$this->assertNotNull( $where_arg, 'testConnection should have a where argument.' );
		$this->assertSame( 'INPUT_OBJECT', $where_arg['type']['kind'] );

		$where_field_names = array_column( $where_arg['type']['inputFields'], 'name' );
		$this->assertContains( 'testArg', $where_field_names );
	}
}

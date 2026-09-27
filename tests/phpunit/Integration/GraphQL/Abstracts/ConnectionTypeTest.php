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
 * Connection type test double that registers a connection with a filtered subset of its args.
 */
final class ConcreteConnectionTypeTestDouble extends ConnectionType {
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
			'includedArg' => [
				'type'        => 'String',
				'description' => static fn (): string => 'An included arg',
			],
			'excludedArg' => [
				'type'        => 'String',
				'description' => static fn (): string => 'An excluded arg',
			],
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		register_graphql_connection(
			self::get_connection_config(
				[
					'fromType'       => 'RootQuery',
					'fromFieldName'  => 'testConnection',
					'resolve'        => static fn () => null,
					'connectionArgs' => self::get_connection_args( [ 'includedArg' ] ),
				]
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
	 * Verifies the connection defaults to the type name as `toType` and only exposes the filtered args.
	 */
	public function test_connection_is_registered_with_filtered_args(): void {
		$instance = new ConcreteConnectionTypeTestDouble();
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
								inputFields {
									name
								}
							}
						}
					}
				}
				edge: __type(name: "RootQueryToTestConnectionConnectionEdge") {
					fields {
						name
						type {
							ofType {
								name
							}
						}
					}
				}
			}
		';

		$actual = graphql( [ 'query' => $query ] );

		$this->assertArrayNotHasKey( 'errors', $actual, 'GraphQL response should not contain errors.' );

		$fields           = array_column( $actual['data']['__type']['fields'], null, 'name' );
		$connection_field = $fields['testConnection'] ?? null;

		$this->assertNotNull( $connection_field, 'testConnection field should exist on RootQuery.' );

		$edge_fields = array_column( $actual['data']['edge']['fields'], null, 'name' );
		$this->assertSame( 'Post', $edge_fields['node']['type']['ofType']['name'] );

		// WPGraphQL wraps custom connection args inside a 'where' input field.
		$args      = array_column( $connection_field['args'], null, 'name' );
		$where_arg = $args['where'] ?? null;

		$this->assertNotNull( $where_arg, 'testConnection should have a where argument.' );

		$where_field_names = array_column( $where_arg['type']['inputFields'], 'name' );
		$this->assertContains( 'includedArg', $where_field_names );
		$this->assertNotContains( 'excludedArg', $where_field_names );
	}
}

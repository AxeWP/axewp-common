<?php
/**
 * TypeTest file.
 *
 * @package AxeWP\Common\Tests\Integration\GraphQL\Abstracts
 */

declare( strict_types = 1 );

namespace AxeWP\Common\Tests\Integration\GraphQL\Abstracts;

use AxeWP\Common\Core\Config;
use AxeWP\Common\GraphQL\Abstracts\Type;
use AxeWP\Common\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Concrete test double for Type.
 */
final class ConcreteTypeTestDouble extends Type {
	/**
	 * {@inheritDoc}
	 */
	protected static function type_name(): string {
		return 'TestType';
	}

	/**
	 * {@inheritDoc}
	 */
	protected static function get_description(): string {
		return 'Test description';
	}

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		// Noop for testing.
	}
}

/**
 * Class - TypeTest
 */
#[CoversClass( Type::class )]
class TypeTest extends TestCase {
	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();

		Config::set_hook_prefix( 'test_graphql' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		Config::$hook_prefix = '';

		parent::tearDown();
	}

	/**
	 * Test that register_hooks adds the graphql_register_types action.
	 */
	public function test_register_hooks_adds_graphql_register_types_action(): void {
		$instance = new ConcreteTypeTestDouble();

		$instance->register_hooks();

		$this->assertNotFalse( has_action( 'graphql_register_types', [ $instance, 'register' ] ) );

		remove_action( 'graphql_register_types', [ $instance, 'register' ] );
	}

	/**
	 * Test that init adds the graphql_register_types action.
	 */
	public function test_init_adds_graphql_register_types_action(): void {
		$instance = new ConcreteTypeTestDouble();

		$instance->init();

		$this->assertNotFalse( has_action( 'graphql_register_types', [ $instance, 'register' ] ) );

		remove_action( 'graphql_register_types', [ $instance, 'register' ] );
	}
}

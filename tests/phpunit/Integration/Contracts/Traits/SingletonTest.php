<?php
/**
 * Unit tests for Singleton trait.
 *
 * @package AxeWP\Common\Tests\Integration\Contracts\Traits
 */

declare( strict_types = 1 );

namespace AxeWP\Common\Tests\Integration\Contracts\Traits;

use AxeWP\Common\Contracts\Traits\Singleton;
use AxeWP\Common\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Test double for our trait.
 */
final class SingletonTestDouble {
	use Singleton;

	// Intentionally left blank.
}

/**
 * Class - SingletonTest
 */
#[CoversClass( Singleton::class )]
class SingletonTest extends TestCase {
	/**
	 * Tests that get_instance() returns the same instance each time.
	 */
	public function test_get_instance_returns_same_instance(): void {
		$a = SingletonTestDouble::get_instance();
		$b = SingletonTestDouble::get_instance();

		$this->assertInstanceOf( SingletonTestDouble::class, $a );
		$this->assertSame( $a, $b );
	}

	/**
	 * Tests that cloning throws a logic exception.
	 */
	public function test_clone_throws_logic_exception(): void {
		$this->setExpectedIncorrectUsage( '__clone' );

		$this->expectException( \LogicException::class );
		$this->expectExceptionMessage( 'cannot be cloned' );

		clone SingletonTestDouble::get_instance();
	}

	/**
	 * Tests that unserializing throws a logic exception via __wakeup().
	 */
	public function test_wakeup_throws_logic_exception(): void {
		$this->setExpectedIncorrectUsage( '__wakeup' );

		$this->expectException( \LogicException::class );
		$this->expectExceptionMessage( 'cannot be deserialized' );

		$class = SingletonTestDouble::class;
		@unserialize( sprintf( 'O:%d:"%s":0:{}', strlen( $class ), $class ) );
	}
}

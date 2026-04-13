<?php
/**
 * ConfigTest file.
 *
 * @package AxeWP\Common\Tests\Integration\Core
 */

declare( strict_types = 1 );

namespace AxeWP\Common\Tests\Integration\Core;

use AxeWP\Common\Core\Config;
use AxeWP\Common\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class - ConfigTest
 */
#[CoversClass( Config::class )]
class ConfigTest extends TestCase {
	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		Config::$hook_prefix = '';

		parent::tearDown();
	}

	/**
	 * Test that the hook prefix roundtrips.
	 */
	public function test_set_hook_prefix_and_hook_prefix_roundtrip(): void {
		Config::set_hook_prefix( 'my_plugin' );

		$this->assertSame( 'my_plugin', Config::hook_prefix() );
	}

	/**
	 * Test that an empty hook prefix logs incorrect usage.
	 */
	public function test_hook_prefix_logs_doing_it_wrong_when_empty(): void {
		Config::$hook_prefix = '';
		$this->setExpectedIncorrectUsage( Config::class . '::hook_prefix' );

		$this->assertSame( '', Config::hook_prefix() );

		// Ensure it can be overridden.
		Config::set_hook_prefix( 'overridden_prefix' );

		$this->assertSame( 'overridden_prefix', Config::hook_prefix() );
	}
}

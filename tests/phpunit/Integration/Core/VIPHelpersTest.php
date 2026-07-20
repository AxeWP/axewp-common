<?php
/**
 * VIPHelpersTest file.
 *
 * @package AxeWP\Common\Tests\Integration\Core
 */

declare( strict_types = 1 );

namespace AxeWP\Common\Tests\Integration\Core;

use AxeWP\Common\Core\VIPHelpers;
use AxeWP\Common\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class - VIPHelpersTest
 */
#[CoversClass( VIPHelpers::class )]
class VIPHelpersTest extends TestCase {
	/**
	 * Tests that do_file_get_contents falls back to file_get_contents off VIP.
	 *
	 * The `wpcom_vip_file_get_contents()` branch is unreachable here — the function
	 * only exists on VIP Go — so this covers the fallback the wrapper exists for.
	 */
	public function test_do_file_get_contents_falls_back_off_vip(): void {
		$this->assertFalse(
			function_exists( 'wpcom_vip_file_get_contents' ),
			'This test asserts the fallback branch; the VIP function should not exist in wp-env.'
		);

		$path = sys_get_temp_dir() . '/' . uniqid( 'vip-helper-', true ) . '.txt';
		file_put_contents( $path, 'contents' );

		$this->assertSame( 'contents', VIPHelpers::do_file_get_contents( $path ) );

		unlink( $path );
	}
}

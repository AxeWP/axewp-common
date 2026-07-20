<?php
/**
 * AutoloaderTraitTest file.
 *
 * @package AxeWP\Common\Tests\Integration\Core
 */

declare( strict_types = 1 );

namespace AxeWP\Common\Tests\Integration\Core;

use AxeWP\Common\Core\AutoloaderTrait;
use AxeWP\Common\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use function do_action;
use function has_action;
use function remove_all_actions;

/**
 * Test double for AutoloaderTrait.
 */
final class AutoloaderTraitTestDouble {
	use AutoloaderTrait;

	/**
	 * {@inheritDoc}
	 */
	protected static function get_autoloader_error_message(): string {
		return 'Trait test autoloader message';
	}

	/**
	 * Calls require_autoloader().
	 *
	 * @param string $autoloader_file The autoloader file path.
	 */
	public static function call_require_autoloader( string $autoloader_file ): bool {
		return self::require_autoloader( $autoloader_file );
	}
}

/**
 * Class - AutoloaderTraitTest
 */
#[CoversClass( AutoloaderTrait::class )]
class AutoloaderTraitTest extends TestCase {
	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'network_admin_notices' );

		parent::tearDown();
	}

	/**
	 * Tests `require_autoloader()` returns true on success.
	 */
	public function test_require_autoloader_returns_true_for_readable_autoloader_file(): void {
		$autoloader_file = tempnam( sys_get_temp_dir(), 'autoload-trait-' );
		file_put_contents( $autoloader_file, '<?php return true;' );

		$this->assertTrue( AutoloaderTraitTestDouble::call_require_autoloader( $autoloader_file ) );
		unlink( $autoloader_file );
		$this->assertTrue( AutoloaderTraitTestDouble::call_require_autoloader( $autoloader_file ) );
	}

	/**
	 * Tests `require_autoloader()` returns false and registers notices when the file is missing.
	 */
	public function test_require_autoloader_returns_false_and_registers_notices_when_file_is_missing(): void {
		$this->assertFalse( AutoloaderTraitTestDouble::call_require_autoloader( '/tmp/does-not-exist/autoload.php' ) );
		$this->assertNotFalse( has_action( 'admin_notices' ) );
		$this->assertNotFalse( has_action( 'network_admin_notices' ) );

		$this->setExpectedIncorrectUsage( AutoloaderTraitTestDouble::class );
		$this->expectOutputRegex( '/Trait test autoloader message/' );
		do_action( 'admin_notices' );
	}
}

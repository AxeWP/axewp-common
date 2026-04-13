<?php
/**
 * AssetLoaderTraitTest file.
 *
 * @package AxeWP\Common\Tests\Integration\Core
 */

declare( strict_types = 1 );

namespace AxeWP\Common\Tests\Integration\Core;

use AxeWP\Common\Core\AssetLoaderTrait;
use AxeWP\Common\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Test double for AssetLoaderTrait.
 */
final class AssetLoaderTraitTestDouble {
	use AssetLoaderTrait;

	/**
	 * Constructs the test double.
	 */
	public function __construct() {
		$this->plugin_dir = sys_get_temp_dir() . '/';
		$this->plugin_url = 'http://example.com/plugin/';
		$this->assets_dir = 'build';
	}

	/**
	 * Calls register_script().
	 *
	 * @param string            $handle     The script handle.
	 * @param string            $filename   The asset filename.
	 * @param array<int,string> $deps       The script dependencies.
	 * @param ?string           $ver        The asset version.
	 * @param bool              $in_footer  Whether to print in the footer.
	 */
	public function call_register_script( string $handle, string $filename, array $deps = [], ?string $ver = null, bool $in_footer = true ): bool {
		return $this->register_script( $handle, $filename, $deps, $ver, $in_footer );
	}

	/**
	 * Calls register_style().
	 *
	 * @param string            $handle   The style handle.
	 * @param string            $filename The asset filename.
	 * @param array<int,string> $deps     The style dependencies.
	 * @param ?string           $ver      The asset version.
	 * @param string            $media    The media target.
	 */
	public function call_register_style( string $handle, string $filename, array $deps = [], ?string $ver = null, string $media = 'all' ): bool {
		return $this->register_style( $handle, $filename, $deps, $ver, $media );
	}

	/**
	 * Calls get_asset_file().
	 *
	 * @param string $filename The asset filename.
	 *
	 * @return ?array{version:string, ...}
	 */
	public function call_get_asset_file( string $filename ): ?array {
		return $this->get_asset_file( $filename );
	}
}

/**
 * Class - AssetLoaderTraitTest
 */
#[CoversClass( AssetLoaderTrait::class )]
class AssetLoaderTraitTest extends TestCase {
	/**
	 * Tests that get_asset_file returns null and logs an incorrect usage when the asset file is missing.
	 */
	public function test_get_asset_file_returns_null_and_logs_when_missing(): void {
		$loader = new AssetLoaderTraitTestDouble();
		$this->setExpectedIncorrectUsage( $loader::class );

		$calls = [];
		add_action(
			'doing_it_wrong_run',
			static function ( string $_called_function, string $_message, string $_version ) use ( &$calls ): void {
				$calls[] = [
					'called_function' => $_called_function,
					'message'         => $_message,
					'version'         => $_version,
				];
			},
			10,
			3
		);

		$this->assertNull( $loader->call_get_asset_file( 'non-existent-asset' ) );
		$this->assertStringContainsString( 'missing', strtolower( $calls[0]['message'] ) );
	}

	/**
	 * Tests that get_asset_file returns null and logs an incorrect usage when the asset file is invalid.
	 */
	public function test_get_asset_file_returns_null_for_invalid_asset_payload(): void {
		$loader = new AssetLoaderTraitTestDouble();
		$this->setExpectedIncorrectUsage( $loader::class );

		$calls = [];
		add_action(
			'doing_it_wrong_run',
			static function ( string $_called_function, string $_message, string $_version ) use ( &$calls ): void {
				$calls[] = [
					'called_function' => $_called_function,
					'message'         => $_message,
					'version'         => $_version,
				];
			},
			10,
			3
		);

		$asset_dir = sys_get_temp_dir() . '/build';
		if ( ! is_dir( $asset_dir ) ) {
			mkdir( $asset_dir );
		}

		$filename   = uniqid( 'invalid-asset-', true );
		$asset_path = $asset_dir . '/' . $filename . '.asset.php';
		file_put_contents( $asset_path, "<?php return 'not-an-array';" );

		$loader->call_get_asset_file( $filename );

		$this->assertStringContainsString( 'invalid', strtolower( $calls[0]['message'] ) );
		unlink( $asset_path );
		@rmdir( $asset_dir );
	}

	/**
	 * Tests that get_asset_file returns the expected array when the asset file is valid.
	 */
	public function test_get_asset_file_falls_back_to_filemtime_version(): void {
		$loader    = new AssetLoaderTraitTestDouble();
		$asset_dir = sys_get_temp_dir() . '/build';
		if ( ! is_dir( $asset_dir ) ) {
			mkdir( $asset_dir );
		}

		$filename   = uniqid( 'asset-file-', true );
		$asset_path = $asset_dir . '/' . $filename . '.asset.php';
		file_put_contents( $asset_path, '<?php return ["dependencies" => ["wp-element"]];' );

		$result = $loader->call_get_asset_file( $filename );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'version', $result );
		$this->assertSame( (string) filemtime( $asset_path ), $result['version'] );

		unlink( $asset_path );
		@rmdir( $asset_dir );
	}

	/**
	 * Test register_script fails with no file.
	 */
	public function test_register_script_fails_without_asset_file(): void {
		$loader = new AssetLoaderTraitTestDouble();
		$this->setExpectedIncorrectUsage( $loader::class );
		$this->assertFalse( $loader->call_register_script( 'test-script', 'non-existent-asset' ) );
	}

	/**
	 * Tests that register_script uses dependencies and version from the asset file.
	 */
	public function test_register_script_uses_asset_dependencies_and_version(): void {
		$loader    = new AssetLoaderTraitTestDouble();
		$asset_dir = sys_get_temp_dir() . '/build';
		if ( ! is_dir( $asset_dir ) ) {
			mkdir( $asset_dir );
		}

		$asset_path = $asset_dir . '/frontend.asset.php';
		file_put_contents( $asset_path, "<?php return ['version' => '1.2.3', 'dependencies' => ['wp-i18n']];" );

		$this->assertTrue( $loader->call_register_script( 'test-frontend', 'frontend' ) );

		$registered = wp_scripts()->registered['test-frontend'] ?? null;
		$this->assertNotNull( $registered );
		$this->assertSame( [ 'wp-i18n' ], $registered->deps );
		$this->assertSame( '1.2.3', $registered->ver );
		$this->assertStringContainsString( '/build/frontend.js', $registered->src );

		unlink( $asset_path );
		@rmdir( $asset_dir );
	}

	/**
	 * Tests the scripts can have their deps overloaded.
	 */
	public function test_register_script_allows_overriding_dependencies_and_version(): void {
		$loader    = new AssetLoaderTraitTestDouble();
		$asset_dir = sys_get_temp_dir() . '/build';
		if ( ! is_dir( $asset_dir ) ) {
			mkdir( $asset_dir );
		}

		$asset_path = $asset_dir . '/editor.asset.php';
		file_put_contents( $asset_path, "<?php return ['version' => '1.2.3', 'dependencies' => ['wp-i18n']];" );

		$this->assertTrue( $loader->call_register_script( 'test-editor', 'editor', [ 'wp-data' ], '9.9.9' ) );

		$registered = wp_scripts()->registered['test-editor'] ?? null;
		$this->assertNotNull( $registered );
		$this->assertSame( [ 'wp-data' ], $registered->deps );
		$this->assertSame( '9.9.9', $registered->ver );

		unlink( $asset_path );
		@rmdir( $asset_dir );
	}

	/**
	 * Tests that register_style fails with no file.
	 */
	public function test_register_style_fails_without_asset_file(): void {
		$loader = new AssetLoaderTraitTestDouble();
		$this->setExpectedIncorrectUsage( $loader::class );
		$this->assertFalse( $loader->call_register_style( 'test-style', 'non-existent-asset' ) );
	}

	/**
	 * Tests that register_style uses dependencies and version from the asset file.
	 */
	public function test_register_style_uses_asset_dependencies_and_version(): void {
		$loader    = new AssetLoaderTraitTestDouble();
		$asset_dir = sys_get_temp_dir() . '/build';
		if ( ! is_dir( $asset_dir ) ) {
			mkdir( $asset_dir );
		}

		$asset_path = $asset_dir . '/global-styles.asset.php';
		file_put_contents( $asset_path, "<?php return ['version' => '2.1.0', 'dependencies' => ['wp-components']];" );

		$this->assertTrue( $loader->call_register_style( 'test-global-styles', 'global-styles' ) );

		$registered = wp_styles()->registered['test-global-styles'] ?? null;
		$this->assertNotNull( $registered );
		$this->assertSame( [], $registered->deps );
		$this->assertSame( '2.1.0', $registered->ver );
		$this->assertSame( 'all', $registered->args );
		$this->assertStringContainsString( '/build/global-styles.css', $registered->src );

		unlink( $asset_path );
		@rmdir( $asset_dir );
	}

	/**
	 * Tests that register style allows overriding dependencies and version.
	 */
	public function test_register_style_allows_overriding_dependencies_and_version(): void {
		$loader    = new AssetLoaderTraitTestDouble();
		$asset_dir = sys_get_temp_dir() . '/build';
		if ( ! is_dir( $asset_dir ) ) {
			mkdir( $asset_dir );
		}

		$asset_path = $asset_dir . '/editor-styles.asset.php';
		file_put_contents( $asset_path, "<?php return ['version' => '2.1.0', 'dependencies' => ['wp-components']];" );

		$this->assertTrue( $loader->call_register_style( 'test-editor-styles', 'editor-styles', [ 'wp-edit-blocks' ], '4.5.6', 'screen' ) );

		$registered = wp_styles()->registered['test-editor-styles'] ?? null;
		$this->assertNotNull( $registered );
		$this->assertSame( [ 'wp-edit-blocks' ], $registered->deps );
		$this->assertSame( '4.5.6', $registered->ver );
		$this->assertSame( 'screen', $registered->args );

		unlink( $asset_path );
		@rmdir( $asset_dir );
	}
}

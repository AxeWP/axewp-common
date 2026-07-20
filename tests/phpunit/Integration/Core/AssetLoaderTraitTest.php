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
	 * Calls register_script_module().
	 *
	 * @param string                                                $handle   The module handle.
	 * @param string                                                $filename The asset filename.
	 * @param array<int, string|array{id: string, import?: string}> $deps     The module dependencies.
	 * @param ?string                                               $ver      The asset version.
	 */
	public function call_register_script_module( string $handle, string $filename, array $deps = [], ?string $ver = null ): bool {
		return $this->register_script_module( $handle, $filename, $deps, $ver );
	}

	/**
	 * Calls get_asset_file().
	 *
	 * @param string $filename  The asset filename.
	 * @param string $extension The asset file extension.
	 *
	 * @return ?array{version:string, ...}
	 */
	public function call_get_asset_file( string $filename, string $extension = 'js' ): ?array {
		return $this->get_asset_file( $filename, $extension );
	}
}

/**
 * Class - AssetLoaderTraitTest
 */
#[CoversClass( AssetLoaderTrait::class )]
class AssetLoaderTraitTest extends TestCase {
	/**
	 * Absolute paths to the files created by a test, removed on teardown.
	 *
	 * @var string[]
	 */
	private array $created_files = [];

	/**
	 * Removes any files created during the test.
	 */
	public function tearDown(): void {
		foreach ( $this->created_files as $file ) {
			if ( file_exists( $file ) ) {
				unlink( $file );
			}
		}

		$this->created_files = [];

		@rmdir( sys_get_temp_dir() . '/build' );

		parent::tearDown();
	}

	/**
	 * Creates a file inside the test assets directory, registering it for cleanup.
	 *
	 * @param string $relative_path Path relative to the assets directory, including the extension.
	 * @param string $contents      The file contents.
	 *
	 * @return string The absolute path to the created file.
	 */
	private function create_asset( string $relative_path, string $contents = '' ): string {
		$asset_dir = sys_get_temp_dir() . '/build';

		if ( ! is_dir( $asset_dir ) ) {
			mkdir( $asset_dir, 0777, true );
		}

		$path = $asset_dir . '/' . $relative_path;
		file_put_contents( $path, $contents );

		$this->created_files[] = $path;

		return $path;
	}

	/**
	 * Collects doing_it_wrong_run notices raised during the test.
	 *
	 * @return array<int, array{called_function:string, message:string, version:string}> The collected notices, by reference.
	 */
	private function &collect_doing_it_wrong(): array {
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

		return $calls;
	}

	/**
	 * Tests that get_asset_file returns null and logs an incorrect usage when the asset file is missing.
	 */
	public function test_get_asset_file_returns_null_and_logs_when_asset_missing(): void {
		$loader = new AssetLoaderTraitTestDouble();
		$this->setExpectedIncorrectUsage( $loader::class );

		$calls = &$this->collect_doing_it_wrong();

		$this->assertNull( $loader->call_get_asset_file( 'non-existent-asset' ) );
		$this->assertStringContainsString( 'missing', strtolower( $calls[0]['message'] ) );
	}

	/**
	 * Tests that a filename containing a parent-directory traversal is rejected outright.
	 *
	 * The rejection is silent — an escaping path is a programming error, not a
	 * misconfigured build, so it must not leak the attempted path into a notice.
	 */
	public function test_get_asset_file_rejects_path_traversal(): void {
		$loader = new AssetLoaderTraitTestDouble();

		$calls = &$this->collect_doing_it_wrong();

		// Create the file the traversal would reach, so the null is the guard's doing
		// and not just a missing file.
		$this->create_asset( 'escape.js', '' );

		$this->assertNull( $loader->call_get_asset_file( 'nested/../escape' ) );
		$this->assertSame( [], $calls );
	}

	/**
	 * Tests that get_asset_file refuses to register when the asset file is invalid.
	 *
	 * An invalid asset file means the declared dependencies are unavailable, and
	 * registering without them defers the failure to the browser.
	 */
	public function test_get_asset_file_refuses_when_asset_file_invalid(): void {
		$loader = new AssetLoaderTraitTestDouble();
		$this->setExpectedIncorrectUsage( $loader::class );

		$calls = &$this->collect_doing_it_wrong();

		$filename = uniqid( 'invalid-asset-', true );
		$this->create_asset( $filename . '.js', '// asset' );
		$this->create_asset( $filename . '.asset.php', "<?php return 'not-an-array';" );

		$this->assertNull( $loader->call_get_asset_file( $filename ) );
		$this->assertStringContainsString( 'invalid', strtolower( $calls[0]['message'] ) );
	}

	/**
	 * Tests that the manifest is optional — an asset with no `.asset.php` still resolves.
	 */
	public function test_get_asset_file_treats_asset_file_as_optional(): void {
		$loader = new AssetLoaderTraitTestDouble();

		$filename   = uniqid( 'manifestless-', true );
		$asset_path = $this->create_asset( $filename . '.css', 'body{}' );

		$result = $loader->call_get_asset_file( $filename, 'css' );

		$this->assertIsArray( $result );
		$this->assertSame( [], $result['dependencies'] );
		$this->assertSame( (string) filemtime( $asset_path ), $result['version'] );
	}

	/**
	 * Tests that a manifest without a version falls back to the mtime of the asset itself, not the manifest.
	 */
	public function test_get_asset_file_version_falls_back_to_asset_filemtime(): void {
		$loader = new AssetLoaderTraitTestDouble();

		$filename    = uniqid( 'asset-file-', true );
		$asset_path  = $this->create_asset( $filename . '.js', '// asset' );
		$manifest    = $this->create_asset( $filename . '.asset.php', '<?php return ["dependencies" => ["wp-element"]];' );
		$manifest_ts = filemtime( $asset_path ) - 3600;

		// Age the manifest so the two mtimes are distinguishable.
		touch( $manifest, $manifest_ts );
		clearstatcache( true, $manifest );

		$result = $loader->call_get_asset_file( $filename );

		$this->assertIsArray( $result );
		$this->assertSame( [ 'wp-element' ], $result['dependencies'] );
		$this->assertSame( (string) filemtime( $asset_path ), $result['version'] );
		$this->assertNotSame( (string) $manifest_ts, $result['version'] );
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
	 * Tests that register_script uses dependencies and version from the asset manifest.
	 */
	public function test_register_script_uses_asset_dependencies_and_version(): void {
		$loader = new AssetLoaderTraitTestDouble();

		$this->create_asset( 'frontend.js', '// frontend' );
		$this->create_asset( 'frontend.asset.php', "<?php return ['version' => '1.2.3', 'dependencies' => ['wp-i18n']];" );

		$this->assertTrue( $loader->call_register_script( 'test-frontend', 'frontend' ) );

		$registered = wp_scripts()->registered['test-frontend'] ?? null;
		$this->assertNotNull( $registered );
		$this->assertSame( [ 'wp-i18n' ], $registered->deps );
		$this->assertSame( '1.2.3', $registered->ver );
		$this->assertStringContainsString( '/build/frontend.js', $registered->src );
	}

	/**
	 * Tests the scripts can have their deps overloaded.
	 */
	public function test_register_script_allows_overriding_dependencies_and_version(): void {
		$loader = new AssetLoaderTraitTestDouble();

		$this->create_asset( 'editor.js', '// editor' );
		$this->create_asset( 'editor.asset.php', "<?php return ['version' => '1.2.3', 'dependencies' => ['wp-i18n']];" );

		$this->assertTrue( $loader->call_register_script( 'test-editor', 'editor', [ 'wp-data' ], '9.9.9' ) );

		$registered = wp_scripts()->registered['test-editor'] ?? null;
		$this->assertNotNull( $registered );
		$this->assertSame( [ 'wp-data' ], $registered->deps );
		$this->assertSame( '9.9.9', $registered->ver );
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
	 * Tests that register_style uses the version from the asset manifest.
	 */
	public function test_register_style_uses_asset_dependencies_and_version(): void {
		$loader = new AssetLoaderTraitTestDouble();

		$this->create_asset( 'global-styles.css', 'body{}' );
		$this->create_asset( 'global-styles.asset.php', "<?php return ['version' => '2.1.0', 'dependencies' => ['wp-components']];" );

		$this->assertTrue( $loader->call_register_style( 'test-global-styles', 'global-styles' ) );

		$registered = wp_styles()->registered['test-global-styles'] ?? null;
		$this->assertNotNull( $registered );
		$this->assertSame( [], $registered->deps );
		$this->assertSame( '2.1.0', $registered->ver );
		$this->assertSame( 'all', $registered->args );
		$this->assertStringContainsString( '/build/global-styles.css', $registered->src );
	}

	/**
	 * Tests that register style allows overriding dependencies and version.
	 */
	public function test_register_style_allows_overriding_dependencies_and_version(): void {
		$loader = new AssetLoaderTraitTestDouble();

		$this->create_asset( 'editor-styles.css', 'body{}' );
		$this->create_asset( 'editor-styles.asset.php', "<?php return ['version' => '2.1.0', 'dependencies' => ['wp-components']];" );

		$this->assertTrue( $loader->call_register_style( 'test-editor-styles', 'editor-styles', [ 'wp-edit-blocks' ], '4.5.6', 'screen' ) );

		$registered = wp_styles()->registered['test-editor-styles'] ?? null;
		$this->assertNotNull( $registered );
		$this->assertSame( [ 'wp-edit-blocks' ], $registered->deps );
		$this->assertSame( '4.5.6', $registered->ver );
		$this->assertSame( 'screen', $registered->args );
	}

	/**
	 * Tests that register_script_module fails with no file.
	 */
	public function test_register_script_module_fails_without_asset_file(): void {
		$loader = new AssetLoaderTraitTestDouble();
		$this->setExpectedIncorrectUsage( $loader::class );
		$this->assertFalse( $loader->call_register_script_module( 'test-module', 'non-existent-asset' ) );
	}

	/**
	 * Tests that register_script_module normalises plain string dependencies into id/import form.
	 */
	public function test_register_script_module_normalises_string_dependencies(): void {
		$loader = new AssetLoaderTraitTestDouble();

		$this->create_asset( 'view.js', '// view' );
		$this->create_asset( 'view.asset.php', "<?php return ['version' => '3.0.0', 'dependencies' => ['@wordpress/interactivity']];" );

		$this->assertTrue( $loader->call_register_script_module( 'test-view', 'view' ) );

		$registered = $this->get_registered_script_modules();

		$this->assertArrayHasKey( 'test-view', $registered );
		$this->assertSame( '3.0.0', $registered['test-view']['version'] );
		// WordPress fills in the default 'static' import type once the dependency is in id form.
		$this->assertSame(
			[
				[
					'id'     => '@wordpress/interactivity',
					'import' => 'static',
				],
			],
			$registered['test-view']['dependencies']
		);
	}

	/**
	 * Reads the private registry out of WP_Script_Modules.
	 *
	 * There is no public accessor for registered (as opposed to enqueued) modules,
	 * and asserting on the printed import map would test WordPress rather than the
	 * dependency normalisation this covers.
	 *
	 * @return array<string, array{version:string|false, dependencies:array<int, array{id:string, import?:string}>}> The registered modules.
	 */
	private function get_registered_script_modules(): array {
		$modules    = wp_script_modules();
		$reflection = new \ReflectionProperty( $modules, 'registered' );

		/**
		 * The registry keyed by module ID.
		 *
		 * @var array<string, array{version:string|false, dependencies:array<int, array{id:string, import?:string}>}> $registered
		 */
		$registered = $reflection->getValue( $modules );

		return $registered;
	}

	/**
	 * Tests that register_script_module accepts dependencies already in id/import form.
	 */
	public function test_register_script_module_accepts_array_dependencies(): void {
		$loader = new AssetLoaderTraitTestDouble();

		$this->create_asset( 'interactive.js', '// interactive' );

		$this->assertTrue(
			$loader->call_register_script_module(
				'test-interactive',
				'interactive',
				[
					'@wordpress/interactivity',
					[
						'id'     => 'some-module',
						'import' => 'dynamic',
					],
				],
				'1.0.0'
			)
		);
	}
}

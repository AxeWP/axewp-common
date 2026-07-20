<?php
/**
 * Trait for WordPress asset loading.
 *
 * @package AxeWP\Common\Core
 */

declare( strict_types = 1 );

namespace AxeWP\Common\Core;

// Bail if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! trait_exists( '\\AxeWP\\Common\\Core\\AssetLoaderTrait' ) ) {
	/**
	 * Trait - AssetLoaderTrait
	 */
	trait AssetLoaderTrait {
		/**
		 * The path to the built assets directory, relative to the plugin directory.
		 * No preceding or trailing slashes.
		 *
		 * @var string
		 */
		private string $assets_dir;

		/**
		 * Plugin directory path.
		 *
		 * @var string
		 */
		private string $plugin_dir;

		/**
		 * Plugin URL.
		 *
		 * @var string
		 */
		private string $plugin_url;

		/**
		 * Register a script.
		 *
		 * @param non-empty-string $handle    Name of the script. Should be unique.
		 * @param string           $filename  Path of the script relative to js directory, excluding the .js extension.
		 * @param string[]         $deps      Optional. An array of registered script handles this script depends on. If not set, the dependencies will be inherited from the asset file.
		 * @param ?string          $ver       Optional. String specifying script version number, if not set, the version will be inherited from the asset file.
		 * @param bool             $in_footer Optional. Whether to enqueue the script before </body> instead of in the <head>.
		 */
		private function register_script( string $handle, string $filename, array $deps = [], ?string $ver = null, bool $in_footer = true ): bool {
			$asset = $this->get_asset_file( $filename, 'js' );
			// Bail if the script or its asset file does not exist or is invalid.

			if ( ! $asset ) {
				return false;
			}

			$asset_src = sprintf( '%s/%s.js', $this->plugin_url . untrailingslashit( $this->assets_dir ), $filename );
			$deps      = $deps ?: ( $asset['dependencies'] ?? [] );
			$version   = $ver ?? $asset['version'];

			return wp_register_script(
				$handle,
				$asset_src,
				$deps,
				$version ?: false,
				$in_footer
			);
		}

		/**
		 * Register a CSS stylesheet.
		 *
		 * @param string   $handle   Name of the stylesheet. Should be unique.
		 * @param string   $filename Path of the stylesheet relative to the css directory, excluding the .css extension.
		 * @param string[] $deps     Optional. An array of registered stylesheet handles this stylesheet depends on, if not set, the version will be inherited from the asset file.
		 * @param ?string  $ver      Optional. String specifying style version number, if not set, the version will be inherited from the asset file.
		 * @param string   $media    Optional. The media for which this stylesheet has been defined.
		 *                           Default 'all'. Accepts media types like 'all', 'print' and 'screen', or media queries like
		 *                           '(orientation: portrait)' and '(max-width: 640px)'.
		 */
		private function register_style( string $handle, string $filename, array $deps = [], ?string $ver = null, string $media = 'all' ): bool {
			$asset = $this->get_asset_file( $filename, 'css' );
			// Bail if the stylesheet or its asset file does not exist or is invalid.

			if ( ! $asset ) {
				return false;
			}

			$asset_src = sprintf( '%s/%s.css', $this->plugin_url . untrailingslashit( $this->assets_dir ), $filename );
			$deps      = $deps ?: [];
			$version   = $ver ?? $asset['version'];

			// Register as a style.
			return wp_register_style(
				$handle,
				$asset_src,
				$deps,
				$version ?: false,
				$media
			);
		}

		/**
		 * Register a script module.
		 *
		 * @param non-empty-string                                      $handle   Name of the script module. Should be unique.
		 * @param string                                                $filename Path of the module relative to the js directory, excluding the .js extension.
		 * @param array<int, string|array{id: string, import?: string}> $deps     Optional. Module dependencies — each a module-ID string or an [id, import] array. If not set, the dependencies will be inherited from the asset file.
		 * @param ?string                                               $ver      Optional. String specifying module version number, if not set, the version will be inherited from the asset file.
		 *
		 * @return bool False if the module or its asset file does not exist or is invalid; otherwise true. (wp_register_script_module()
		 *              returns void, so success past that point cannot be reported.)
		 */
		private function register_script_module( string $handle, string $filename, array $deps = [], ?string $ver = null ): bool {
			$asset = $this->get_asset_file( $filename, 'js' );

			if ( ! $asset ) {
				return false;
			}

			$asset_src = sprintf( '%s/%s.js', $this->plugin_url . untrailingslashit( $this->assets_dir ), $filename );
			$version   = $ver ?? $asset['version'];

			$deps = array_map(
				static fn ( string|array $dep ): array => is_array( $dep ) ? $dep : [ 'id' => $dep ],
				$deps ?: ( $asset['dependencies'] ?? [] )
			);

			wp_register_script_module( $handle, $asset_src, $deps, $version ?: false );

			return true;
		}

		/**
		 * Get the asset metadata for a registerable asset.
		 *
		 * The asset itself is required — without it there is nothing worth registering.
		 * Its `.asset.php` file is optional, and the asset's own modification time is
		 * used as the version when there isn't one.
		 *
		 * @param string $filename  Path of the asset relative to the assets directory, excluding the file extension.
		 * @param string $extension The asset's file extension, excluding the dot. E.g. `js`, `css`.
		 *
		 * @return ?array{version:string, ...} The asset file array, or null if the asset does not exist or its asset file is invalid.
		 */
		private function get_asset_file( string $filename, string $extension ): ?array {
			// $filename reaches a require() below, so refuse to walk out of the assets directory.
			if ( str_contains( $filename, '..' ) ) {
				return null;
			}

			$base       = $this->plugin_dir . untrailingslashit( $this->assets_dir );
			$asset_path = sprintf( '%s/%s.%s', $base, $filename, $extension );

			// Bail if the asset itself does not exist.
			if ( ! file_exists( $asset_path ) ) {
				_doing_it_wrong(
					static::class,
					sprintf(
						/* translators: 1: The asset filename. 2: The asset file extension. */
						esc_html__( 'Asset "%1$s.%2$s" is missing. It will not be registered.', 'axewp' ),
						esc_html( $filename ),
						esc_html( $extension )
					),
					'0.1.0'
				);

				return null;
			}

			$asset_file = sprintf( '%s/%s.asset.php', $base, $filename );

			// The asset file is optional: an unbuilt asset still has an mtime to version it by.
			if ( ! file_exists( $asset_file ) ) {
				return [
					'dependencies' => [],
					'version'      => (string) filemtime( $asset_path ),
				];
			}

			// phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable -- The file is checked for existence above.
			$asset = require $asset_file;

			if ( ! is_array( $asset ) ) {
				_doing_it_wrong(
					static::class,
					sprintf(
						/* translators: %s: The asset filename. */
						esc_html__( 'Asset file for "%s" is invalid. It will not be registered.', 'axewp' ),
						esc_html( $filename )
					),
					'0.1.0'
				);

				return null;
			}

			// Fallback to the asset's own filemtime if no version is set in the asset file.
			if ( ! isset( $asset['version'] ) ) {
				$asset['version'] = (string) filemtime( $asset_path );
			}

			return $asset;
		}
	}
}

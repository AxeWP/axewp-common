<?php
/**
 * Wrappers for VIP helper functions that are not available in all environments.
 *
 * @package AxeWP\Common\Core
 */

declare( strict_types = 1 );

namespace AxeWP\Common\Core;

// Bail if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

if ( ! class_exists( '\\AxeWP\\Common\\Core\\VIPHelpers' ) ) { // @codeCoverageIgnore
	/**
	 * Class - VIPHelpers
	 */
	final class VIPHelpers {
		/**
		 * Wrapper for `wpcom_vip_file_get_contents()`.
		 *
		 * If it doesn't exist, calls `file_get_contents()` instead.
		 *
		 * @see https://github.com/svn2github/wordpress-vip-plugins/blob/4d6f59f9839167d1c11f550610012493c7380dfe/vip-helper.php#L140
		 *
		 * @param string                                                                        $filename   URL to fetch.
		 * @param int                                                                           $timeout    Optional. The timeout limit in seconds; valid values are 1-10. Defaults to 3.
		 * @param int                                                                           $cache_time Optional. The minimum cache time in seconds. Valid values are >= 60. Defaults to 900.
		 * @param array{obey_cache_control_header?: bool, http_api_args?: array<string, mixed>} $extra_args Optional. Advanced arguments: "obey_cache_control_header" and "http_api_args".
		 *
		 * @return string|false The remote file's contents (cached on VIP). False in case of failure.
		 *
		 * @phpstan-param int<1,10> $timeout
		 * @phpstan-param int<60, max> $cache_time
		 */
		public static function do_file_get_contents( string $filename, int $timeout = 3, int $cache_time = 900, array $extra_args = [] ): string|false {
			if ( function_exists( 'wpcom_vip_file_get_contents' ) ) { // @codeCoverageIgnoreStart
				return wpcom_vip_file_get_contents( $filename, $timeout, $cache_time, $extra_args );
			} // @codeCoverageIgnoreEnd

			// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown -- The VIP-cached variant is used above where available.
			return file_get_contents( $filename );
		}
	}
}

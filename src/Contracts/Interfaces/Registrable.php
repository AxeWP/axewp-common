<?php
/**
 * Interface for Registrable classes.
 *
 * Registrable classes are those that register hooks (actions/filters) with WordPress.
 *
 * @package AxeWP\Common\Contracts\Interfaces
 */

declare( strict_types = 1 );

namespace AxeWP\Common\Contracts\Interfaces;

// Bail if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

if ( ! interface_exists( '\\AxeWP\\Common\\Interfaces\\Registrable' ) ) { // @codeCoverageIgnore
	/**
	 * Interface - Registrable
	 */
	interface Registrable {
		/**
		 * Registers class methods to WordPress.
		 *
		 * WordPress actions/filters should be included here.
		 */
		public function register_hooks(): void;
	}
}

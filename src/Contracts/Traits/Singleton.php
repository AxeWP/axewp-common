<?php
/**
 * Singleton trait.
 *
 * Singletons are an ANTI-PATTERN. Use with caution and only when necessary.
 * In most cases, it's better to use dependency injection.
 *
 * @package AxeWP\Common\Contracts\Traits
 */

declare( strict_types = 1 );

namespace AxeWP\Common\Contracts\Traits;

// Bail if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! trait_exists( '\\AxeWP\\Common\\Traits\\Singleton' ) ) {
	/**
	 * Singleton trait.
	 */
	trait Singleton {
		/**
		 * Instance of the class.
		 *
		 * @var ?static
		 */
		protected static $instance;

		/**
		 * The single constructor.
		 *
		 * It's protected to prevent direct instantiation.
		 */
		protected function __construct() {
			// To be implemented by the class using the trait.
		}

		/**
		 * Get the instance of the class.
		 */
		public static function get_instance(): static {
			if ( ! isset( static::$instance ) ) {
				static::$instance = new static();
			}

			return static::$instance;
		}

		/**
		 * Prevent the class from being cloned.
		 *
		 * @throws \LogicException Always. Singletons cannot be cloned.
		 */
		final public function __clone() {
			_doing_it_wrong(
				__FUNCTION__,
				sprintf(
					// translators: %s: Class name.
					esc_html__( 'The %s class should not be cloned.', 'axewp' ),
					esc_html( static::class ),
				),
				'0.1.0'
			);

			throw new \LogicException( sprintf( 'Singleton %s cannot be cloned.', static::class ) );
		}

		/**
		 * Prevent the class from being deserialized.
		 *
		 * @throws \LogicException Always. Singletons cannot be deserialized.
		 */
		final public function __wakeup() {
			_doing_it_wrong(
				__FUNCTION__,
				sprintf(
					// translators: %s: Class name.
					esc_html__( 'De-serializing instances of %s is not allowed.', 'axewp' ),
					esc_html( static::class ),
				),
				'0.1.0'
			);

			throw new \LogicException( sprintf( 'Singleton %s cannot be deserialized.', static::class ) );
		}
	}
}

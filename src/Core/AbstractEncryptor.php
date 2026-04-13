<?php
/**
 * Abstract class for implementing WordPress-safe encryption.
 *
 * Useful for encrypting sensitive data before storing it in the database, with a fallback to return raw values if OpenSSL is unavailable.
 *
 * @package AxeWP\Common\Core
 */

declare( strict_types = 1 );

namespace AxeWP\Common\Core;

// Bail if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\\AxeWP\\Common\\Core\\AbstractEncryptor' ) ) {
	/**
	 * Class - AbstractEncryptor
	 */
	abstract class AbstractEncryptor {
		/**
		 * The OpenSSL encryption method.
		 */
		protected const METHOD = 'aes-256-gcm';

		/**
		 * The GCM authentication tag length in bytes.
		 */
		protected const TAG_LENGTH = 16;

		/**
		 * The IV length for GCM mode.
		 */
		protected const IV_LENGTH = 12;

		/**
		 * Gets the constant name to use for the encryption key.
		 *
		 * @return non-empty-string
		 */
		abstract protected static function get_constant_key();

		/**
		 * Encrypts a value using AES-256-GCM authenticated encryption.
		 *
		 * @param string $raw_value The value to encrypt.
		 *
		 * @return string|false The encrypted value, or false on failure.
		 */
		final public static function encrypt( string $raw_value ): string|false {
			if ( ! extension_loaded( 'openssl' ) ) { // @codeCoverageIgnoreStart
				_doing_it_wrong(
					__METHOD__,
					'OpenSSL extension is not loaded. Returning unencrypted value.',
					'0.1.0',
				);

				return $raw_value;
			} // @codeCoverageIgnoreEnd

			$iv  = random_bytes( self::IV_LENGTH );
			$tag = '';

			$value = openssl_encrypt(
				$raw_value,
				self::METHOD,
				static::get_key(),
				OPENSSL_RAW_DATA,
				$iv,
				$tag,
				'',
				self::TAG_LENGTH
			);

			return false !== $value ? base64_encode( $iv . $tag . $value ) : false;
		}

		/**
		 * Decrypts a value encrypted with AES-256-GCM.
		 *
		 * @param string $raw_value The encrypted value.
		 *
		 * @return string|false The decrypted value, or false on failure/tampering.
		 */
		final public static function decrypt( string $raw_value ): string|false {
			if ( ! extension_loaded( 'openssl' ) ) { // @codeCoverageIgnoreStart
				_doing_it_wrong(
					__METHOD__,
					'OpenSSL extension is not loaded. Returning unencrypted value.',
					'0.1.0',
				);

				return $raw_value;
			} // @codeCoverageIgnoreEnd

			$decoded_value = base64_decode( $raw_value, true );

			if ( false === $decoded_value ) {
				// Don't leak potentially sensitive data, e.g. an unencrypted value that was accidentally passed in.
				_doing_it_wrong(
					__METHOD__,
					'Invalid input: not a valid base64-encoded string.',
					'0.1.0',
				);

				return false;
			}

			// Extract IV, tag, and ciphertext.
			$iv         = substr( $decoded_value, 0, self::IV_LENGTH );
			$tag        = substr( $decoded_value, self::IV_LENGTH, self::TAG_LENGTH );
			$ciphertext = substr( $decoded_value, self::IV_LENGTH + self::TAG_LENGTH );

			return openssl_decrypt(
				$ciphertext,
				self::METHOD,
				static::get_key(),
				OPENSSL_RAW_DATA,
				$iv,
				$tag
			);
		}

		/**
		 * Gets the encryption key.
		 *
		 * Uses the constant defined by the implementing class, or falls back to LOGGED_IN_KEY for backward compatibility, but with a warning.
		 *
		 * @return string The encryption key.
		 * @throws \LogicException If no valid key is defined.
		 */
		protected static function get_key(): string {
			$constant_key = static::get_constant_key();
			if ( defined( $constant_key ) && '' !== constant( $constant_key ) ) {
				return (string) constant( $constant_key );
			}

			if ( defined( 'LOGGED_IN_KEY' ) && '' !== constant( 'LOGGED_IN_KEY' ) ) {
				_doing_it_wrong(
					__METHOD__,
					sprintf(
						'Using %s for encryption key is not recommended. Define %s in wp-config.php for better security.',
						esc_html( 'LOGGED_IN_KEY' ),
						esc_html( $constant_key ),
					),
					'0.1.0',
				);
				return (string) constant( 'LOGGED_IN_KEY' );
			}

			// If you're here, you're either not on a live site or have a serious security issue.
			throw new \LogicException( // @codeCoverageIgnoreStart
				sprintf(
					'No encryption key defined. Please define %s or LOGGED_IN_KEY in wp-config.php.',
					esc_html( $constant_key ),
				)
			); // @codeCoverageIgnoreEnd
		}
	}
}

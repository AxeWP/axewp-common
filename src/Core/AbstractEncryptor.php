<?php
/**
 * Abstract class for implementing WordPress-safe encryption.
 *
 * Useful for encrypting sensitive data before storing it in the database.
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
		 * The OpenSSL cipher method.
		 *
		 * @var non-empty-string
		 */
		protected static string $method = 'aes-256-gcm';

		/**
		 * The authentication tag length in bytes.
		 */
		protected static int $tag_length = 16;

		/**
		 * Marks a value as encrypted, and versions the format.
		 *
		 * @var non-empty-string
		 */
		protected static string $prefix = 'enc:v1:';

		/**
		 * Gets the constant name to use for the encryption key.
		 *
		 * Also used to derive the key, so changing it makes existing values undecryptable.
		 *
		 * @return non-empty-string
		 */
		abstract protected static function get_constant_key(): string;

		/**
		 * Whether a value looks like the output of encrypt().
		 *
		 * Only checks the prefix. Use decrypt() to verify the value itself.
		 *
		 * @param string $value The value to check.
		 */
		public static function is_encrypted( string $value ): bool {
			return str_starts_with( $value, static::$prefix );
		}

		/**
		 * Encrypts a value.
		 *
		 * @param string $raw_value The value to encrypt.
		 *
		 * @return string|\WP_Error The encrypted value, or an error if it can't be encrypted.
		 */
		public static function encrypt( string $raw_value ): string|\WP_Error {
			$keys = static::get_keys();

			if ( is_wp_error( $keys ) ) {
				return $keys;
			}

			$iv_length = (int) openssl_cipher_iv_length( static::$method );
			$iv        = $iv_length > 0 ? random_bytes( $iv_length ) : '';
			$tag       = '';

			$value = openssl_encrypt(
				$raw_value,
				static::$method,
				$keys[0], // Always use the intended key for encryption.
				OPENSSL_RAW_DATA,
				$iv,
				$tag,
				'',
				static::$tag_length
			);

			// Only authenticated methods set a tag.
			if ( null === $tag ) {
				return new \WP_Error( 'invalid_encryption_method', sprintf( '%s is not an authenticated encryption method.', static::$method ) );
			}

			if ( false === $value ) {
				return new \WP_Error( 'encryption_failed', 'The value could not be encrypted.' ); // @codeCoverageIgnore
			}

			return static::$prefix . base64_encode( $iv . $tag . $value );
		}

		/**
		 * Decrypts a value.
		 *
		 * @param string $raw_value The encrypted value.
		 *
		 * @return string|\WP_Error The decrypted value, or an error if it isn't encrypted, was tampered with, or can't be decrypted.
		 */
		public static function decrypt( string $raw_value ): string|\WP_Error {
			if ( ! static::is_encrypted( $raw_value ) ) {
				return new \WP_Error( 'not_encrypted', 'The value is not encrypted.' );
			}

			$keys = static::get_keys();

			if ( is_wp_error( $keys ) ) {
				return $keys;
			}

			$decoded_value = base64_decode( substr( $raw_value, strlen( static::$prefix ) ), true );
			$iv_length     = (int) openssl_cipher_iv_length( static::$method );

			if ( false === $decoded_value || strlen( $decoded_value ) < $iv_length + static::$tag_length ) {
				return new \WP_Error( 'decryption_failed', 'The value could not be decrypted.' );
			}

			// Extract IV, tag, and ciphertext.
			$iv         = substr( $decoded_value, 0, $iv_length );
			$tag        = substr( $decoded_value, $iv_length, static::$tag_length );
			$ciphertext = substr( $decoded_value, $iv_length + static::$tag_length );

			// Fallback to other keys if decryption with the primary key fails.
			foreach ( $keys as $key ) {
				$value = openssl_decrypt(
					$ciphertext,
					static::$method,
					$key,
					OPENSSL_RAW_DATA,
					$iv,
					$tag
				);

				if ( false !== $value ) {
					return $value;
				}
			}

			return new \WP_Error( 'decryption_failed', 'The value could not be decrypted.' );
		}

		/**
		 * Gets the encryption keys, primary first.
		 *
		 * The primary key comes from the implementing class's constant, or from LOGGED_IN_KEY if that isn't defined.
		 * Once the constant is defined, LOGGED_IN_KEY stays as a fallback so values encrypted before then still decrypt.
		 *
		 * @return non-empty-list<string>|\WP_Error The derived keys, or an error if OpenSSL, a key, or a valid $method isn't available.
		 *
		 * @throws \LogicException If no encryption key is defined.
		 */
		protected static function get_keys(): array|\WP_Error {
			if ( ! extension_loaded( 'openssl' ) ) { // @codeCoverageIgnoreStart
				$message = 'OpenSSL extension is not loaded. Encryption cannot proceed.';

				_doing_it_wrong( __METHOD__, esc_html( $message ), '0.1.0' );

				return new \WP_Error( 'openssl_missing', $message );
			} // @codeCoverageIgnoreEnd

			$key_length = (int) openssl_cipher_key_length( static::$method );

			if ( $key_length < 1 ) {
				return new \WP_Error( 'invalid_encryption_method', sprintf( '%s is not an OpenSSL cipher method.', static::$method ) ); // @codeCoverageIgnore
			}

			$constant_key = static::get_constant_key();
			$primary      = defined( $constant_key ) ? (string) constant( $constant_key ) : '';

			// Log if the primary key is not set, indicating that the fallback key will be used.
			if ( empty( $primary ) ) {
				_doing_it_wrong(
					__METHOD__,
					sprintf(
						'Using %s for encryption key is not recommended. Define %s in wp-config.php for better security.',
						esc_html( 'LOGGED_IN_KEY' ),
						esc_html( $constant_key ),
					),
					'0.1.0'
				);
			}

			$fallback = defined( 'LOGGED_IN_KEY' ) ? (string) constant( 'LOGGED_IN_KEY' ) : '';

			$keys = [];

			foreach ( [ $primary, $fallback ] as $secret ) {
				if ( '' !== $secret ) {
					$keys[] = hash_hkdf( 'sha256', $secret, $key_length, $constant_key . '|' . static::$method );
				}
			}

			// If you're here, you're either not on a live site or have a serious security issue.
			if ( [] === $keys ) { // @codeCoverageIgnoreStart
				throw new \LogicException(
					sprintf(
						'No encryption key defined. Please define %s or LOGGED_IN_KEY in wp-config.php.',
						esc_html( $constant_key ),
					)
				);
			} // @codeCoverageIgnoreEnd

			return $keys;
		}
	}
}

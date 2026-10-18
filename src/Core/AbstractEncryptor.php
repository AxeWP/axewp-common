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
		 * The sample value shipped in wp-config-sample.php. Treated as undefined.
		 */
		private const PLACEHOLDER_SECRET = 'put your unique phrase here';

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
		 * Whether the implementing class's constant is undefined, empty, or the sample placeholder, so the fallback key (if any) is used.
		 */
		public static function is_using_fallback_key(): bool {
			return '' === static::get_secret( static::get_constant_key() );
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
			$decrypted = self::decrypt_with_any_key( $raw_value );

			return is_wp_error( $decrypted ) ? $decrypted : $decrypted['value'];
		}

		/**
		 * Whether a value only decrypts with LOGGED_IN_KEY, though the implementing class's constant is now defined, so it should be re-encrypted.
		 *
		 * Returns false for values that don't decrypt, since they can't be re-encrypted.
		 *
		 * @param string $encrypted_value The encrypted value.
		 */
		public static function needs_reencryption( string $encrypted_value ): bool {
			$decrypted = self::decrypt_with_any_key( $encrypted_value );

			return ! is_wp_error( $decrypted ) && $decrypted['key_index'] > 0;
		}

		/**
		 * Decrypts a value with the first key that works.
		 *
		 * @param string $raw_value The encrypted value.
		 *
		 * @return array{value:string,key_index:int}|\WP_Error The decrypted value and the index of the key that decrypted it, or an error.
		 */
		private static function decrypt_with_any_key( string $raw_value ): array|\WP_Error {
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
			foreach ( $keys as $key_index => $key ) {
				$value = openssl_decrypt(
					$ciphertext,
					static::$method,
					$key,
					OPENSSL_RAW_DATA,
					$iv,
					$tag
				);

				if ( false !== $value ) {
					return [
						'value'     => $value,
						'key_index' => $key_index,
					];
				}
			}

			return new \WP_Error( 'decryption_failed', 'The value could not be decrypted.' );
		}

		/**
		 * Gets the value of a secret constant.
		 *
		 * @param string $constant_name The constant name.
		 *
		 * @return string The value, or an empty string if the constant is undefined, empty, or WordPress's sample placeholder.
		 */
		protected static function get_secret( string $constant_name ): string {
			$secret = defined( $constant_name ) ? constant( $constant_name ) : '';

			return is_string( $secret ) && self::PLACEHOLDER_SECRET !== $secret ? $secret : '';
		}

		/**
		 * Gets the encryption keys, primary first.
		 *
		 * The primary key comes from the implementing class's constant, or from LOGGED_IN_KEY if that isn't defined.
		 * Once the constant is defined, LOGGED_IN_KEY stays as a fallback so values encrypted before then still decrypt.
		 *
		 * @return non-empty-list<string>|\WP_Error The derived keys, or an error if OpenSSL, a key, or a valid $method isn't available.
		 */
		protected static function get_keys(): array|\WP_Error {
			if ( ! extension_loaded( 'openssl' ) ) { // @codeCoverageIgnoreStart
				return new \WP_Error( 'openssl_missing', 'OpenSSL extension is not loaded. Encryption cannot proceed.' );
			} // @codeCoverageIgnoreEnd

			$key_length = (int) openssl_cipher_key_length( static::$method );

			if ( $key_length < 1 ) {
				return new \WP_Error( 'invalid_encryption_method', sprintf( '%s is not an OpenSSL cipher method.', static::$method ) ); // @codeCoverageIgnore
			}

			$constant_key = static::get_constant_key();
			$keys         = [];

			foreach ( [ $constant_key, 'LOGGED_IN_KEY' ] as $secret_constant ) {
				$secret = static::get_secret( $secret_constant );

				if ( '' !== $secret ) {
					$keys[] = hash_hkdf( 'sha256', $secret, $key_length, $constant_key . '|' . static::$method );
				}
			}

			// If you're here, you're either not on a live site or have a serious security issue.
			if ( [] === $keys ) {
				return new \WP_Error(
					'no_encryption_key',
					sprintf( 'No encryption key defined. Please define %s or LOGGED_IN_KEY in wp-config.php.', $constant_key )
				);
			}

			return $keys;
		}
	}
}

<?php
/**
 * Integration tests for AbstractEncryptor.
 *
 * @package AxeWP\Common\Tests\Integration\Core
 */

declare( strict_types = 1 );

namespace AxeWP\Common\Tests\Integration\Core;

use AxeWP\Common\Core\AbstractEncryptor;
use AxeWP\Common\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Test double using the constant defined in set_up().
 */
class ConstantKeyEncryptorTestDouble extends AbstractEncryptor {
	/**
	 * {@inheritDoc}
	 */
	protected static function get_constant_key(): string {
		return 'AXEWP_COMMON_ENCRYPTION_TEST_KEY';
	}
}

/**
 * Test double using AES-128-GCM.
 */
final class Aes128EncryptorTestDouble extends ConstantKeyEncryptorTestDouble {
	/**
	 * {@inheritDoc}
	 */
	protected static string $method = 'aes-128-gcm';
}

/**
 * Test double using ChaCha20-Poly1305.
 */
final class ChaCha20EncryptorTestDouble extends ConstantKeyEncryptorTestDouble {
	/**
	 * {@inheritDoc}
	 */
	protected static string $method = 'chacha20-poly1305';
}

/**
 * Test double using a 12-byte tag.
 */
final class ShortTagEncryptorTestDouble extends ConstantKeyEncryptorTestDouble {
	/**
	 * {@inheritDoc}
	 */
	protected static int $tag_length = 12;
}

/**
 * Test double using an unauthenticated method.
 */
final class UnauthenticatedEncryptorTestDouble extends ConstantKeyEncryptorTestDouble {
	/**
	 * {@inheritDoc}
	 */
	protected static string $method = 'aes-256-cbc';
}

/**
 * Test double using a custom prefix.
 */
final class CustomPrefixEncryptorTestDouble extends ConstantKeyEncryptorTestDouble {
	/**
	 * {@inheritDoc}
	 */
	protected static string $prefix = 'custom:v2:';
}

/**
 * Test double whose constant is never defined, so it falls back to LOGGED_IN_KEY.
 */
final class FallbackKeyEncryptorTestDouble extends AbstractEncryptor {
	/**
	 * {@inheritDoc}
	 */
	protected static function get_constant_key(): string {
		return 'AXEWP_COMMON_ENCRYPTION_KEY';
	}
}

/**
 * Test double whose constant is never defined, under a different name.
 */
final class OtherFallbackKeyEncryptorTestDouble extends AbstractEncryptor {
	/**
	 * {@inheritDoc}
	 */
	protected static function get_constant_key(): string {
		return 'AXEWP_COMMON_OTHER_ENCRYPTION_KEY';
	}
}

/**
 * Test double whose constant is defined partway through a test.
 */
final class LateConstantKeyEncryptorTestDouble extends AbstractEncryptor {
	/**
	 * {@inheritDoc}
	 */
	protected static function get_constant_key(): string {
		return 'AXEWP_COMMON_LATE_ENCRYPTION_KEY';
	}
}

/**
 * Class - AbstractEncryptorTest
 */
#[CoversClass( AbstractEncryptor::class )]
class AbstractEncryptorTest extends TestCase {
	private const PREFIX = 'enc:v1:';

	/**
	 * {@inheritDoc}
	 */
	public function set_up(): void {
		parent::set_up();

		if ( ! defined( 'AXEWP_COMMON_ENCRYPTION_TEST_KEY' ) ) {
			define( 'AXEWP_COMMON_ENCRYPTION_TEST_KEY', 'test-constant-key-value' );
		}
	}

	/**
	 * Data provider for round-trip values.
	 *
	 * @return array<string, array{0:string}>
	 */
	public static function round_trip_values(): array {
		return [
			'ascii'   => [ 'Sensitive data' ],
			'unicode' => [ '日本語テスト 🎉 àccënts' ],
			'empty'   => [ '' ],
		];
	}

	/**
	 * Test that a value round-trips through encrypt() and decrypt().
	 *
	 * @param string $raw The value to encrypt.
	 */
	#[DataProvider( 'round_trip_values' )]
	public function test_encrypt_decrypt_round_trip( string $raw ): void {
		$encrypted = ConstantKeyEncryptorTestDouble::encrypt( $raw );

		$this->assertIsString( $encrypted );
		$this->assertStringStartsWith( self::PREFIX, $encrypted );
		$this->assertTrue( ConstantKeyEncryptorTestDouble::is_encrypted( $encrypted ) );
		$this->assertSame( $raw, ConstantKeyEncryptorTestDouble::decrypt( $encrypted ) );
	}

	/**
	 * Data provider for authenticated methods.
	 *
	 * @return array<string, array{0:class-string<\AxeWP\Common\Core\AbstractEncryptor>, 1:string, 2:int}>
	 */
	public static function authenticated_methods(): array {
		return [
			'aes-128-gcm'              => [ Aes128EncryptorTestDouble::class, 'aes-128-gcm', 16 ],
			'aes-256-gcm'              => [ ConstantKeyEncryptorTestDouble::class, 'aes-256-gcm', 16 ],
			'aes-256-gcm, 12-byte tag' => [ ShortTagEncryptorTestDouble::class, 'aes-256-gcm', 12 ],
			'chacha20-poly1305'        => [ ChaCha20EncryptorTestDouble::class, 'chacha20-poly1305', 16 ],
		];
	}

	/**
	 * Test that the output is `prefix . base64( iv . tag . ciphertext )`, using an HKDF-derived key.
	 *
	 * @param class-string<\AxeWP\Common\Core\AbstractEncryptor> $encryptor  The test double.
	 * @param string                                             $method     Its cipher method.
	 * @param int                                                $tag_length Its tag length.
	 */
	#[DataProvider( 'authenticated_methods' )]
	public function test_encrypted_format( string $encryptor, string $method, int $tag_length ): void {
		$encrypted = $encryptor::encrypt( 'secret' );

		$this->assertIsString( $encrypted );
		$this->assertSame( 'secret', $encryptor::decrypt( $encrypted ) );

		$decoded   = (string) base64_decode( substr( $encrypted, strlen( self::PREFIX ) ), true );
		$iv_length = (int) openssl_cipher_iv_length( $method );
		$key       = hash_hkdf(
			'sha256',
			AXEWP_COMMON_ENCRYPTION_TEST_KEY,
			(int) openssl_cipher_key_length( $method ),
			'AXEWP_COMMON_ENCRYPTION_TEST_KEY|' . $method
		);

		$this->assertSame(
			'secret',
			openssl_decrypt(
				substr( $decoded, $iv_length + $tag_length ),
				$method,
				$key,
				OPENSSL_RAW_DATA,
				substr( $decoded, 0, $iv_length ),
				substr( $decoded, $iv_length, $tag_length )
			)
		);
	}

	/**
	 * Test that an unauthenticated method is rejected.
	 */
	public function test_encrypt_rejects_unauthenticated_method(): void {
		$this->assert_error_code( 'invalid_encryption_method', UnauthenticatedEncryptorTestDouble::encrypt( 'secret' ) );
	}

	/**
	 * Test that a class only recognizes its own prefix.
	 */
	public function test_prefix_is_per_class(): void {
		$default = ConstantKeyEncryptorTestDouble::encrypt( 'secret' );
		$custom  = CustomPrefixEncryptorTestDouble::encrypt( 'secret' );

		$this->assertIsString( $default );
		$this->assertIsString( $custom );
		$this->assertStringStartsWith( 'custom:v2:', $custom );
		$this->assertSame( 'secret', CustomPrefixEncryptorTestDouble::decrypt( $custom ) );
		$this->assertFalse( CustomPrefixEncryptorTestDouble::is_encrypted( $default ) );
		$this->assertFalse( ConstantKeyEncryptorTestDouble::is_encrypted( $custom ) );
	}

	/**
	 * Data provider for plaintext values.
	 *
	 * @return array<string, array{0:string}>
	 */
	public static function plaintext_values(): array {
		return [
			'empty'  => [ '' ],
			'text'   => [ 'Sensitive data' ],
			'base64' => [ base64_encode( str_repeat( 'x', 48 ) ) ],
		];
	}

	/**
	 * Test that plaintext isn't treated as encrypted.
	 *
	 * @param string $value The plaintext value.
	 */
	#[DataProvider( 'plaintext_values' )]
	public function test_plaintext_is_not_encrypted( string $value ): void {
		$this->assertFalse( ConstantKeyEncryptorTestDouble::is_encrypted( $value ) );
		$this->assert_error_code( 'not_encrypted', ConstantKeyEncryptorTestDouble::decrypt( $value ) );
	}

	/**
	 * Test that a valid payload isn't decrypted without its prefix.
	 */
	public function test_decrypt_requires_prefix(): void {
		$payload = substr( (string) ConstantKeyEncryptorTestDouble::encrypt( 'secret' ), strlen( self::PREFIX ) );

		$this->assert_error_code( 'not_encrypted', ConstantKeyEncryptorTestDouble::decrypt( $payload ) );
	}

	/**
	 * Data provider for malformed payloads.
	 *
	 * @return array<string, array{0:string}>
	 */
	public static function malformed_payloads(): array {
		return [
			'invalid base64' => [ '!!!not-valid-base64!!!' ],
			'empty'          => [ '' ],
			'truncated'      => [ base64_encode( 'too short' ) ],
		];
	}

	/**
	 * Test that a malformed payload after a valid prefix is rejected.
	 *
	 * @param string $payload The payload following the prefix.
	 */
	#[DataProvider( 'malformed_payloads' )]
	public function test_decrypt_rejects_malformed_payload( string $payload ): void {
		$this->assert_error_code( 'decryption_failed', ConstantKeyEncryptorTestDouble::decrypt( self::PREFIX . $payload ) );
	}

	/**
	 * Data provider for byte offsets into the default `iv (12) . tag (16) . ciphertext` payload.
	 *
	 * @return array<string, array{0:int}>
	 */
	public static function tamper_offsets(): array {
		return [
			'iv'         => [ 0 ],
			'tag'        => [ 12 ],
			'ciphertext' => [ 28 ],
		];
	}

	/**
	 * Test that a tampered payload fails authentication.
	 *
	 * @param int $offset The byte to flip.
	 */
	#[DataProvider( 'tamper_offsets' )]
	public function test_decrypt_rejects_tampered_payload( int $offset ): void {
		$encrypted = (string) ConstantKeyEncryptorTestDouble::encrypt( 'secret' );
		$decoded   = (string) base64_decode( substr( $encrypted, strlen( self::PREFIX ) ), true );

		$decoded[ $offset ] = $decoded[ $offset ] ^ "\x01";

		$this->assert_error_code( 'decryption_failed', ConstantKeyEncryptorTestDouble::decrypt( self::PREFIX . base64_encode( $decoded ) ) );
	}

	/**
	 * Test that classes sharing the LOGGED_IN_KEY fallback can't decrypt each other's values.
	 */
	public function test_fallback_key_is_scoped_per_class(): void {
		$this->setExpectedIncorrectUsage( AbstractEncryptor::class . '::get_keys' );

		$first  = FallbackKeyEncryptorTestDouble::encrypt( 'secret' );
		$second = OtherFallbackKeyEncryptorTestDouble::encrypt( 'secret' );

		$this->assertIsString( $first );
		$this->assertIsString( $second );
		$this->assertSame( 'secret', FallbackKeyEncryptorTestDouble::decrypt( $first ) );
		$this->assertSame( 'secret', OtherFallbackKeyEncryptorTestDouble::decrypt( $second ) );
		$this->assert_error_code( 'decryption_failed', FallbackKeyEncryptorTestDouble::decrypt( $second ) );
		$this->assert_error_code( 'decryption_failed', OtherFallbackKeyEncryptorTestDouble::decrypt( $first ) );
	}

	/**
	 * Test that values encrypted with the LOGGED_IN_KEY fallback still decrypt once the constant is defined.
	 */
	public function test_fallback_key_still_decrypts_after_constant_is_defined(): void {
		$this->setExpectedIncorrectUsage( AbstractEncryptor::class . '::get_keys' );
		$this->assertFalse( defined( 'AXEWP_COMMON_LATE_ENCRYPTION_KEY' ) );

		$encrypted = LateConstantKeyEncryptorTestDouble::encrypt( 'secret' );
		$this->assertIsString( $encrypted );

		define( 'AXEWP_COMMON_LATE_ENCRYPTION_KEY', 'late-constant-key-value' );

		$this->assertSame( 'secret', LateConstantKeyEncryptorTestDouble::decrypt( $encrypted ) );
	}

	/**
	 * Asserts that a result is a WP_Error with the given code.
	 *
	 * @param string $code   The expected error code.
	 * @param mixed  $result The result to check.
	 */
	private function assert_error_code( string $code, $result ): void {
		$this->assertWPError( $result );
		$this->assertSame( $code, $result->get_error_code() );
	}
}

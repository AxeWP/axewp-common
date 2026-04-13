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
 * Concrete test double using the LOGGED_IN_KEY fallback.
 */
final class AbstractEncryptorTestDouble extends AbstractEncryptor {
	/**
	 * {@inheritDoc}
	 */
	protected static function get_constant_key() {
		return 'AXEWP_COMMON_ENCRYPTION_KEY';
	}
}

/**
 * Test double using a dedicated constant key.
 */
final class ConstantKeyEncryptorTestDouble extends AbstractEncryptor {
	/**
	 * {@inheritDoc}
	 */
	protected static function get_constant_key() {
		return 'AXEWP_COMMON_ENCRYPTION_TEST_KEY';
	}
}

/**
 * Class - AbstractEncryptorTest
 */
#[CoversClass( AbstractEncryptor::class )]
class AbstractEncryptorTest extends TestCase {
	/**
	 * Data provider for round-trip encryption values.
	 *
	 * @return array<string, array{0:string}>
	 */
	public static function round_trip_values(): array {
		return [
			'ascii'   => [ 'Sensitive data: ' . uniqid( '', true ) ],
			'unicode' => [ '日本語テスト 🎉 émoji and àccënts' ],
			'empty'   => [ '' ],
		];
	}

	/**
	 * Test that encrypting then decrypting returns the original value.
	 *
	 * Uses the LOGGED_IN_KEY fallback path via the test double.
	 *
	 * @param string $raw The value to round-trip through encrypt/decrypt.
	 */
	#[DataProvider( 'round_trip_values' )]
	public function test_encrypt_decrypt_round_trip( string $raw ): void {
		$this->setExpectedIncorrectUsage( AbstractEncryptor::class . '::get_key' );

		$encrypted = AbstractEncryptorTestDouble::encrypt( $raw );

		$this->assertNotFalse( $encrypted );
		$this->assertNotSame( $raw, $encrypted );
		$this->assertSame( $raw, AbstractEncryptorTestDouble::decrypt( $encrypted ) );
	}

	/**
	 * Test encryption using a dedicated constant key instead of the LOGGED_IN_KEY fallback.
	 */
	public function test_encrypt_decrypt_with_defined_constant_key(): void {
		if ( ! defined( 'AXEWP_COMMON_ENCRYPTION_TEST_KEY' ) ) {
			define( 'AXEWP_COMMON_ENCRYPTION_TEST_KEY', 'test-constant-key-value-here-32chars!!' );
		}

		$raw       = 'Constant-backed data: ' . uniqid( '', true );
		$encrypted = ConstantKeyEncryptorTestDouble::encrypt( $raw );

		$this->assertNotFalse( $encrypted );
		$this->assertNotSame( $raw, $encrypted );
		$this->assertSame( $raw, ConstantKeyEncryptorTestDouble::decrypt( $encrypted ) );
	}

	/**
	 * Test that decrypting invalid base64 data returns false.
	 */
	public function test_decrypt_returns_false_on_invalid_base64(): void {
		$this->setExpectedIncorrectUsage( AbstractEncryptor::class . '::decrypt' );
		$this->assertFalse( AbstractEncryptorTestDouble::decrypt( '!!!not-valid-base64!!!' ) );
	}

	/**
	 * Test that GCM authentication fails when data is tampered.
	 *
	 * @param string $tamper_type Which component to tamper with.
	 */
	#[DataProvider( 'tamper_data_provider' )]
	public function test_decrypt_returns_false_on_tampered_data( string $tamper_type ): void {
		$this->setExpectedIncorrectUsage( AbstractEncryptor::class . '::get_key' );

		$encrypted = AbstractEncryptorTestDouble::encrypt( 'test data' );
		$this->assertIsString( $encrypted );

		$decoded    = base64_decode( $encrypted, true );
		$iv_length  = 12;
		$tag_length = 16;
		$iv         = substr( $decoded, 0, $iv_length );
		$tag        = substr( $decoded, $iv_length, $tag_length );
		$ciphertext = substr( $decoded, $iv_length + $tag_length );

		switch ( $tamper_type ) {
			case 'ciphertext':
				$ciphertext = $ciphertext ^ str_repeat( "\xff", strlen( $ciphertext ) );
				break;
			case 'iv':
				$iv = $iv ^ str_repeat( "\xaa", strlen( $iv ) );
				break;
			case 'tag':
				$tag = $tag ^ str_repeat( "\x01", strlen( $tag ) );
				break;
		}

		$this->assertFalse( AbstractEncryptorTestDouble::decrypt( base64_encode( $iv . $tag . $ciphertext ) ) );
	}

	/**
	 * Data provider for tampering tests.
	 *
	 * @return array<string, array{0:string}>
	 */
	public static function tamper_data_provider(): array {
		return [
			'ciphertext' => [ 'ciphertext' ],
			'iv'         => [ 'iv' ],
			'tag'        => [ 'tag' ],
		];
	}
}

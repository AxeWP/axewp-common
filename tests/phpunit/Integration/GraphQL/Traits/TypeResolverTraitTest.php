<?php
/**
 * TypeResolverTraitTest file.
 *
 * @package AxeWP\Common\Tests\Integration\GraphQL\Traits
 */

declare( strict_types = 1 );

namespace AxeWP\Common\Tests\Integration\GraphQL\Traits;

use AxeWP\Common\GraphQL\Traits\TypeResolverTrait;
use AxeWP\Common\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use WPGraphQL;

/**
 * Test double for the type resolver trait.
 */
final class TypeResolverTraitTestDouble {
	use TypeResolverTrait;

	/**
	 * {@inheritDoc}
	 */
	public static function get_resolved_type_name( $value ): ?string {
		return is_array( $value ) ? ( $value['type'] ?? null ) : null;
	}

	/**
	 * Expose resolve_type for testing.
	 *
	 * @param mixed $value The value to resolve.
	 * @return mixed The resolved type definition.
	 */
	public static function call_resolve_type( $value ) {
		return self::resolve_type( $value );
	}
}

/**
 * Class - TypeResolverTraitTest
 */
#[CoversClass( TypeResolverTrait::class )]
class TypeResolverTraitTest extends TestCase {
	/**
	 * Test that resolve_type resolves a type from the registry.
	 */
	public function test_resolve_type_resolves_type_from_registry(): void {
		// Force the type registry to initialize by building the schema.
		WPGraphQL::get_schema();

		$resolved = TypeResolverTraitTestDouble::call_resolve_type( [ 'type' => 'String' ] );

		$this->assertIsObject( $resolved );
		$this->assertSame( 'String', $resolved->name );
	}

	/**
	 * Test that resolve_type throws when type resolution fails.
	 */
	public function test_resolve_type_throws_when_resolution_fails(): void {
		try {
			TypeResolverTraitTestDouble::call_resolve_type( [] );
			$this->fail( 'Expected error was not thrown.' );
		} catch ( \Throwable $throwable ) {
			$this->assertStringContainsString( 'failed to resolve', $throwable->getMessage() );
		}
	}
}

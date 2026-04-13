<?php
/**
 * TypeNameTraitTest file.
 *
 * @package AxeWP\Common\Tests\Integration\GraphQL\Traits
 */

declare( strict_types = 1 );

namespace AxeWP\Common\Tests\Integration\GraphQL\Traits;

use AxeWP\Common\Core\Config;
use AxeWP\Common\GraphQL\Traits\TypeNameTrait;
use AxeWP\Common\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use function add_filter;
use function remove_all_filters;

/**
 * Test double for the type name trait.
 */
final class TypeNameTraitTestDouble {
	use TypeNameTrait;

	/**
	 * {@inheritDoc}
	 */
	protected static function type_name(): string {
		return 'ExampleType';
	}
}

/**
 * Class - TypeNameTraitTest
 */
#[CoversClass( TypeNameTrait::class )]
class TypeNameTraitTest extends TestCase {
	/**
	 * {@inheritDoc}
	 */
	protected function setUp(): void {
		parent::setUp();

		Config::set_hook_prefix( 'test_graphql' );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function tearDown(): void {
		remove_all_filters( 'test_graphql_type_prefix' );
		Config::$hook_prefix = '';

		parent::tearDown();
	}

	/**
	 * Test that get_type_name returns the expected format.
	 */
	public function test_get_type_name_returns_expected_format(): void {
		$this->assertSame( 'ExampleType', TypeNameTraitTestDouble::get_type_name() );
	}

	/**
	 * Test that get_type_name applies the configured type prefix filter.
	 */
	public function test_get_type_name_applies_filter_prefix(): void {
		add_filter( 'test_graphql_type_prefix', static fn (): string => 'Prefixed' );
		$this->assertSame( 'PrefixedExampleType', TypeNameTraitTestDouble::get_type_name() );
	}
}

<?php
/**
 * PHPStan analyzer class for the Singleton trait.
 *
 * PHPStan only analyses traits when they are used by a class.
 * This file provides a consumer class so PHPStan can analyze the trait.
 *
 * @package AxeWP\Common\PHPStan
 */

declare( strict_types = 1 );

namespace AxeWP\Common\PHPStan;

use AxeWP\Common\Contracts\Traits\Singleton;

/**
 * Analyzer class for the Singleton trait.
 */
final class SingletonTraitAnalyzer {
	use Singleton;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// this is a stub.
	}
}

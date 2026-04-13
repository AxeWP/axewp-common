<?php
/**
 * PHPStan analyzer class for the AssetLoaderTrait.
 *
 * PHPStan only analyses traits when they are used by a class.
 * This file provides a consumer class so PHPStan can analyze the trait.
 *
 * @package AxeWP\Common\PHPStan
 */

declare( strict_types = 1 );

namespace AxeWP\Common\PHPStan;

use AxeWP\Common\Core\AssetLoaderTrait;

/**
 * Analyzer class for the AssetLoaderTrait.
 */
final class AssetLoaderTraitAnalyzer {
	use AssetLoaderTrait;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// this is a stub.
	}
}

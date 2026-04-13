<?php
/**
 * PHPStan analyzer class for the AutoloaderTrait.
 *
 * PHPStan only analyses traits when they are used by a class.
 * This file provides a consumer class so PHPStan can analyze the trait.
 *
 * @package AxeWP\Common\PHPStan
 */

declare( strict_types = 1 );

namespace AxeWP\Common\PHPStan;

use AxeWP\Common\Core\AutoloaderTrait;

/**
 * Analyzer class for the AutoloaderTrait.
 */
final class AutoloaderTraitAnalyzer {
	use AutoloaderTrait;

	/**
	 * {@inheritDoc}
	 */
	protected static function get_autoloader_error_message(): string {
		return 'Autoloader is missing.';
	}
}

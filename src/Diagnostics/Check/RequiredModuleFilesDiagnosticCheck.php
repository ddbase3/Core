<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of BASE3 Framework.
 *
 * BASE3 Framework is a lightweight, modular PHP framework for scalable
 * and maintainable web applications. Built for extensibility,
 * performance, and modern development, it can run standalone or
 * integrate as a subsystem within a host system.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 * https://github.com/ddbase3/Base3Framework
 **********************************************************************/

namespace Base3\Core\Diagnostics\Check;

use Base3\CoreFoundation\Diagnostics\Base\AbstractSourceDiagnosticCheck;
use Base3\CoreFoundation\Diagnostics\Dto\DiagnosticCheckResult;
use Base3\Core\Diagnostics\Scope\InstallationDiagnosticScope;

/** Checks required repository-root documents for all inspectable BASE3 modules. */
final class RequiredModuleFilesDiagnosticCheck extends AbstractSourceDiagnosticCheck {

	private const REQUIRED_FILES = ['README.md', 'PRIVACY.md', 'LICENSE'];

	public static function getName(): string {
		return 'requiredmodulefilesdiagnosticcheck';
	}

	public function getScope(): string {
		return InstallationDiagnosticScope::getName();
	}

	public function run(): DiagnosticCheckResult {
		$inventory = $this->inventory();
		$roots = $inventory->roots();
		$failures = $roots['errors'];
		$warnings = $roots['coverage'] ? [] : ['Module discovery is incomplete.'];
		$inspected = 0;

		foreach($roots['roots'] as $root) {
			$read = $inventory->manifest($root);
			if($read['errors'] || $read['manifest'] === null) {
				$failures[] = basename($root) . ': cannot inspect required files without a valid base3.json';
				continue;
			}
			$inspected++;
			foreach(self::REQUIRED_FILES as $filename) {
				$file = $root . '/' . $filename;
				if(!is_file($file) || is_link($file)) {
					$failures[] = basename($root) . ': missing ' . $filename;
				} elseif(!is_readable($file)) {
					$failures[] = basename($root) . ': unreadable ' . $filename;
				}
			}
		}

		return $this->report($failures, $warnings, $inspected);
	}

}

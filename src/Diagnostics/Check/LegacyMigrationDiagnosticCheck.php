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
use Base3\CoreFoundation\Diagnostics\Inspection\ModuleSourceInventory;
use Base3\CoreFoundation\Diagnostics\Inspection\PhpSourceInspector;
use Base3\Core\Diagnostics\Scope\InstallationDiagnosticScope;

/** Counts staged legacy checksum exemptions separately from down() readiness. */
final class LegacyMigrationDiagnosticCheck extends AbstractSourceDiagnosticCheck {

	public static function getName(): string {
		return 'legacymigrationdiagnosticcheck';
	}

	public function getScope(): string {
		return InstallationDiagnosticScope::getName();
	}

	public function run(): DiagnosticCheckResult {
		$inventory = $this->inventory();
		$roots = $inventory->roots();
		$failures = $roots['errors'];
		$warnings = $roots['coverage'] ? [] : ['Module discovery is incomplete.'];
		$inspector = new PhpSourceInspector();
		$inspected = 0;
		$legacy = [];

		foreach($roots['roots'] as $root) {
			$manifest = $inventory->manifest($root);
			if($manifest['errors']) {
				$failures[] = basename($root) . ': cannot inspect legacy markers without a valid base3.json';
				continue;
			}
			foreach($inventory->sourceFiles($root) as $file) {
				$source = $inspector->inspect($file);
				$location = ModuleSourceInventory::relativeFile($root, $file);
				if($source['error'] !== null) {
					$failures[] = $location . ': ' . $source['error'];
					continue;
				}
				if(!$source['migration']) continue;
				$inspected++;
				if(!$source['migrationCertain']) {
					$warnings[] = $location . ': migration identity cannot be verified statically';
				}
				if($source['legacyMarker']) $legacy[] = $location;
			}
		}

		if($inspected === 0 && !$failures && !$warnings) {
			return new DiagnosticCheckResult(DiagnosticCheckResult::NOT_APPLICABLE, 'not_applicable', ['inspected' => 0]);
		}
		$legacyCount = count($legacy);
		$sample = implode('; ', array_slice($legacy, 0, 4));
		if($legacyCount > 4) $sample .= '; ... (' . ($legacyCount - 4) . ' more)';
		if($failures) {
			$status = DiagnosticCheckResult::FAILED;
		} elseif($warnings || $legacyCount > 0) {
			$status = DiagnosticCheckResult::WARNING;
		} else {
			$status = DiagnosticCheckResult::PASSED;
		}
		if($failures) $sample = implode('; ', array_slice($failures, 0, 3)) . ($sample === '' ? '' : '; ' . $sample);
		if($warnings) $sample .= ($sample === '' ? '' : '; ') . implode('; ', array_slice($warnings, 0, 2));
		$sample = (string)preg_replace('/[<>&"\x00-\x1F\x7F]/u', '?', $sample);
		return new DiagnosticCheckResult(
			$status,
			$status,
			['inspected' => $inspected, 'legacy' => $legacyCount, 'failed' => count($failures), 'warning' => count($warnings), 'unverifiable' => count($warnings), 'sample' => substr($sample, 0, 850)],
			$status === DiagnosticCheckResult::PASSED ? '' : 'remediation',
			['inspected' => $inspected, 'legacy' => $legacyCount, 'failed' => count($failures), 'unverifiable' => count($warnings)]
		);
	}

}

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

/** Reports migration down() readiness without loading or executing plugin code. */
final class MigrationReadinessDiagnosticCheck extends AbstractSourceDiagnosticCheck {

	public static function getName(): string {
		return 'migrationreadinessdiagnosticcheck';
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

		foreach($roots['roots'] as $root) {
			$manifest = $inventory->manifest($root);
			if($manifest['errors']) {
				$failures[] = 'Cannot inspect migration sources in ' . basename($root) . ': invalid or missing manifest';
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
				if(!$source['downDeclared']) {
					$warnings[] = $location . ': down() is not yet declared';
				} elseif(!$source['downSignature']) {
					$warnings[] = $location . ': down() must be public function down(): void';
				}
			}
		}

		if($inspected === 0 && !$failures && !$warnings) {
			return new DiagnosticCheckResult(DiagnosticCheckResult::NOT_APPLICABLE, 'not_applicable', ['inspected' => 0]);
		}
		return $this->report($failures, $warnings, $inspected);
	}

}

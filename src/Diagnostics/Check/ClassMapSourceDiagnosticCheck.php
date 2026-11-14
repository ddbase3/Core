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

/** Inspects classmap source preconditions without generating or loading a class map. */
final class ClassMapSourceDiagnosticCheck extends AbstractSourceDiagnosticCheck {

	public static function getName(): string {
		return 'classmapsourcediagnosticcheck';
	}

	public function getScope(): string {
		return InstallationDiagnosticScope::getName();
	}

	public function run(): DiagnosticCheckResult {
		$inventory = $this->inventory();
		$roots = $inventory->roots();
		$failures = $roots['errors'];
		$warnings = $roots['coverage'] ? [] : ['Module discovery is incomplete.'];
		$parser = new PhpSourceInspector();
		$names = [];
		$classes = 0;
		foreach($roots['roots'] as $root) {
			$read = $inventory->manifest($root);
			if($read['errors'] || $read['manifest'] === null) {
				$failures[] = 'Cannot inspect classmap sources in ' . basename($root) . ': invalid or missing manifest';
				continue;
			}
			foreach($inventory->sourceFiles($root) as $file) {
				$source = $parser->inspect($file);
				$location = ModuleSourceInventory::relativeFile($root, $file);
				if($source['error'] !== null) {
					$failures[] = $location . ': ' . $source['error'];
					continue;
				}
				if(!$source['types']) continue;
				if(count($source['types']) !== 1 || $source['types'][0] !== pathinfo($file, PATHINFO_FILENAME)) {
					$failures[] = $location . ': expected exactly one named PHP type matching its filename';
					continue;
				}
				$classes++;
				if($source['staticName'] !== null) {
					$technicalName = $source['staticName'];
					if($technicalName !== strtolower($source['types'][0])) {
						$failures[] = $location . ': getName() does not match lowercase class name';
					}
					if(isset($names[$technicalName])) {
						$failures[] = $location . ': duplicate getName() with ' . $names[$technicalName];
					} else {
						$names[$technicalName] = $location;
					}
				} elseif($source['dynamicName'] && !$source['abstractType'] && $source['classType']) {
					$warnings[] = $location . ': dynamic getName() not statically verifiable';
				}
			}
		}
		return $this->report($failures, $warnings, $classes);
	}

}

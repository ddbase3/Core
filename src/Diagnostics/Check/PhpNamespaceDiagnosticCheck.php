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

use Base3\CoreFoundation\Api\IModuleRegistry;
use Base3\CoreFoundation\Diagnostics\Base\AbstractSourceDiagnosticCheck;
use Base3\CoreFoundation\Diagnostics\Dto\DiagnosticCheckResult;
use Base3\CoreFoundation\Diagnostics\Inspection\ModuleSourceInventory;
use Base3\CoreFoundation\Diagnostics\Inspection\PhpSourceInspector;
use Base3\Core\Diagnostics\Scope\InstallationDiagnosticScope;

/** Read-only namespace verification of all first-party PHP sources, tests and entry files. */
final class PhpNamespaceDiagnosticCheck extends AbstractSourceDiagnosticCheck {

	private readonly ?string $testRoot;
	private readonly ?string $projectRoot;

	public function __construct(
		IModuleRegistry $modules,
		?string $frameworkRoot = null,
		?string $pluginsRoot = null,
		?string $testRoot = null
	) {
		parent::__construct($modules, $frameworkRoot, $pluginsRoot);
		$this->projectRoot = $frameworkRoot ?? (defined('DIR_ROOT') ? (string)DIR_ROOT : null);
		$this->testRoot = $testRoot ?? (defined('DIR_TEST') ? (string)DIR_TEST : null);
	}

	public static function getName(): string {
		return 'phpnamespacediagnosticcheck';
	}

	public function getScope(): string {
		return InstallationDiagnosticScope::getName();
	}

	public function run(): DiagnosticCheckResult {
		$inventory = $this->inventory();
		$roots = $inventory->roots();
		$failures = $roots['errors'];
		$warnings = $roots['coverage'] ? [] : ['Module discovery is incomplete.'];
		$modules = [];

		foreach($roots['roots'] as $root) {
			$read = $inventory->manifest($root);
			if($read['errors'] || $read['manifest'] === null) {
				$failures[] = basename($root) . ': cannot inspect PHP namespaces without a valid base3.json';
				continue;
			}
			$manifest = $read['manifest'];
			$modules[$manifest['name']] = ['root' => $root, 'namespace' => $manifest['namespace']];
		}

		$parser = new PhpSourceInspector();
		$inspected = 0;
		foreach($modules as $name => $module) {
			$root = $module['root'];
			$prefix = $module['namespace'];
			foreach($this->phpFiles($root, $failures, $warnings) as $file) {
				$inspected++;
				$relative = ltrim(substr($file, strlen(rtrim($root, '/\\'))), '/\\');
				$location = ModuleSourceInventory::relativeFile($root, $file);
				$source = $parser->inspect($file);
				$this->checkFile($source, $relative, $location, $prefix, $modules, $failures, $warnings);
			}
		}

		// Framework entry files and root-level tests are not modules, but are first-party code.
		if($this->projectRoot !== null && is_dir($this->projectRoot)) {
			$project = rtrim((string)realpath($this->projectRoot), '/\\');
			foreach(['index.php', 'bootstrap/Autoloader.php'] as $relative) {
				$file = $project . '/' . $relative;
				if(!is_file($file)) continue;
				$inspected++;
				$this->checkFile($parser->inspect($file), $relative, $relative, 'Base3', $modules, $failures, $warnings, false);
			}
		}
		if($this->testRoot !== null && is_dir($this->testRoot)) {
			$testRoot = rtrim((string)realpath($this->testRoot), '/\\');
			foreach($this->phpFiles($testRoot, $failures, $warnings) as $file) {
				$inspected++;
				$relative = ltrim(substr($file, strlen($testRoot)), '/\\');
				$this->checkFile($parser->inspect($file), $relative, 'test/' . $relative, 'Base3', $modules, $failures, $warnings, false);
			}
		}
		return $this->report($failures, $warnings, $inspected);
	}

	/**
	 * @param array<string,mixed> $source
	 * @param array<string,array{root:string,namespace:string}> $modules
	 * @param list<string> $failures
	 * @param list<string> $warnings
	 */
	private function checkFile(array $source, string $relative, string $location, string $prefix, array $modules, array &$failures, array &$warnings, bool $moduleFile = true): void {
		if($source['error'] !== null) {
			$failures[] = $location . ': ' . $source['error'];
			return;
		}
		$namespaces = $source['namespaces'];
		$namespaces = array_values(array_unique($namespaces));
		if(!$namespaces && $source['types']) {
			$failures[] = $location . ': named PHP type has no namespace';
		}
		$parts = explode('/', str_replace('\\', '/', $relative));
		$area = $parts[0];
		$sourceFile = $moduleFile && $area === 'src';
		$testFile = $moduleFile && $area === 'test';
		$subdirs = array_slice($parts, 1, -1);
		$expected = $prefix . ($subdirs ? '\\' . implode('\\', $subdirs) : '');
		if($testFile) $expected = $prefix . '\\Test' . ($subdirs ? '\\' . implode('\\', $subdirs) : '');

		foreach($namespaces as $namespace) {
			if($sourceFile) {
				if($namespace !== $expected) {
					$failures[] = $location . ': namespace ' . $namespace . ', expected ' . $expected;
				}
			} elseif($testFile && str_starts_with($namespace, $prefix . '\\Test')) {
				if($namespace !== $expected) {
					$failures[] = $location . ': test namespace ' . $namespace . ', expected ' . $expected;
				}
			} elseif($namespace !== $prefix && !str_starts_with($namespace, $prefix . '\\')) {
				if($moduleFile) $failures[] = $location . ': namespace ' . $namespace . ' is outside ' . $prefix;
				else $warnings[] = $location . ': external test/entry namespace ' . $namespace . ' is outside ' . $prefix;
			}
		}

		$references = array_unique(array_merge(array_values($source['imports']), $source['references']));
		foreach($references as $reference) {
			$reference = ltrim($reference, '\\');
			$segments = explode('\\', $reference);
			if(count($segments) < 2) continue;
			if($segments[0] !== 'Base3' && isset($modules[$segments[0]])) {
				$failures[] = $location . ': legacy module reference ' . $reference;
			} elseif($segments[0] === 'Base3' && !in_array($segments[1], ['Bootstrap', 'Test'], true) && !isset($modules[$segments[1]])) {
				$failures[] = $location . ': unknown BASE3 namespace ' . $reference;
			}
		}
	}


}

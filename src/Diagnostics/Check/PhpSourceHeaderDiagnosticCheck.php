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

/** Checks module-specific headers of every first-party PHP file with a BASE3 namespace. */
final class PhpSourceHeaderDiagnosticCheck extends AbstractSourceDiagnosticCheck {

	private readonly ?string $projectRoot;
	private readonly ?string $testRoot;

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
		return 'phpsourceheaderdiagnosticcheck';
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
		$parser = new PhpSourceInspector();

		foreach($roots['roots'] as $root) {
			$read = $inventory->manifest($root);
			if($read['errors'] || $read['manifest'] === null) {
				$failures[] = basename($root) . ': cannot inspect PHP headers without a valid base3.json';
				continue;
			}
			$name = $read['manifest']['name'];
			foreach($this->phpFiles($root, $failures, $warnings) as $file) {
				$inspected++;
				$this->checkFile($parser, $file, $name, ModuleSourceInventory::relativeFile($root, $file), $failures);
			}
		}

		// These are first-party framework sources but are not inside a module.
		if($this->projectRoot !== null && is_dir($this->projectRoot)) {
			$project = rtrim((string)realpath($this->projectRoot), '/\\');
			foreach(['index.php', 'bootstrap/Autoloader.php'] as $relative) {
				$file = $project . '/' . $relative;
				if(!is_file($file)) continue;
				$inspected++;
				$this->checkFile($parser, $file, 'Core', $relative, $failures);
			}
		}
		if($this->testRoot !== null && is_dir($this->testRoot)) {
			$testRoot = rtrim((string)realpath($this->testRoot), '/\\');
			foreach($this->phpFiles($testRoot, $failures, $warnings) as $file) {
				$inspected++;
				$relative = ltrim(substr($file, strlen($testRoot)), '/\\');
				$this->checkFile($parser, $file, 'Core', 'test/' . $relative, $failures);
			}
		}

		return $this->report($failures, $warnings, $inspected);
	}

	/**
	 * @param list<string> $failures
	 */
	private function checkFile(PhpSourceInspector $parser, string $file, string $module, string $location, array &$failures): void {
		$source = $parser->inspect($file);
		if($source['error'] !== null) {
			$failures[] = $location . ': ' . $source['error'];
			return;
		}
		$hasBase3Namespace = false;
		foreach($source['namespaces'] as $namespace) {
			if($namespace === 'Base3' || str_starts_with($namespace, 'Base3\\')) {
				$hasBase3Namespace = true;
				break;
			}
		}
		if(!$hasBase3Namespace) return;

		$sourceText = file_get_contents($file);
		if($sourceText === false) {
			$failures[] = $location . ': cannot read PHP header';
			return;
		}
		// Only a banner between the PHP opener and the namespace declaration counts.
		$namespaceAt = null;
		$offset = 0;
		foreach(token_get_all($sourceText) as $token) {
			if(is_array($token) && $token[0] === T_NAMESPACE) {
				$namespaceAt = $offset;
				break;
			}
			$offset += strlen(is_array($token) ? $token[1] : $token);
		}
		if($namespaceAt === null) {
			$failures[] = $location . ': cannot locate PHP namespace declaration';
			return;
		}
		$opening = substr($sourceText, 0, $namespaceAt);
		if(!preg_match('~/\*{6,}(.*?)\*{6,}/~s', $opening, $match)) {
			$failures[] = $location . ': missing BASE3 file header';
			return;
		}
		$header = $match[1];
		$owner = $module === 'Core'
			? 'This file is part of BASE3 Framework.'
			: 'This file is part of ' . $module . ' for BASE3 Framework.';
		if(!str_contains($header, $owner)) {
			$failures[] = $location . ': header does not belong to ' . $module;
			return;
		}
		foreach(['Developed by Daniel Dahme', 'Licensed under GPL-3.0', 'https://www.gnu.org/licenses/gpl-3.0.en.html'] as $required) {
			if(!str_contains($header, $required)) {
				$failures[] = $location . ': incomplete BASE3 file header (' . $required . ')';
				return;
			}
		}
		$description = explode('Developed by Daniel Dahme', explode($owner, $header, 2)[1], 2)[0];
		if(($module === 'Core' && !str_contains($description, 'BASE3 Framework'))
			|| ($module !== 'Core' && !str_contains($description, $module))
			|| preg_match_all('/[A-Za-z][A-Za-z0-9-]+/', $description) < 9) {
			$failures[] = $location . ': missing module-specific description for ' . $module;
		}
	}

}

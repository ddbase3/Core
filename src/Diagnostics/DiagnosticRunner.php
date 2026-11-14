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

namespace Base3\Core\Diagnostics;

use Base3\CoreFoundation\Api\IClassMap;
use Base3\CoreFoundation\Diagnostics\Api\IDiagnosticCheck;
use Base3\CoreFoundation\Diagnostics\Api\IDiagnosticRunner;
use Base3\CoreFoundation\Diagnostics\Api\IDiagnosticScope;
use Base3\CoreFoundation\Diagnostics\Dto\DiagnosticCheckResult;
use InvalidArgumentException;
use Throwable;

/** Classmap owner of diagnostic discovery and single-check execution. */
final class DiagnosticRunner implements IDiagnosticRunner {

	public function __construct(private readonly IClassMap $classmap) {}

	public function getScopes(): array {
		$scopes = array_values(array_filter(
			$this->classmap->getInstancesByInterface(IDiagnosticScope::class),
			static fn($scope): bool => $scope instanceof IDiagnosticScope
		));
		usort($scopes, static fn(IDiagnosticScope $a, IDiagnosticScope $b): int =>
			($a->getPriority() <=> $b->getPriority()) ?: strcmp($a::getName(), $b::getName())
		);
		return $scopes;
	}

	public function getChecks(string $scope): array {
		$this->assertScope($scope);
		$checks = array_values(array_filter(
			$this->classmap->getInstancesByInterface(IDiagnosticCheck::class),
			static fn($check): bool => $check instanceof IDiagnosticCheck && $check->getScope() === $scope
		));
		usort($checks, static fn(IDiagnosticCheck $a, IDiagnosticCheck $b): int =>
			strcmp($a::getName(), $b::getName())
		);
		return $checks;
	}

	public function run(string $scope, string $name): DiagnosticCheckResult {
		$this->assertScope($scope);
		if($name === '') {
			throw new InvalidArgumentException('A diagnostic check name is required.');
		}
		$check = $this->classmap->getInstanceByInterfaceName(IDiagnosticCheck::class, $name);
		if(!$check instanceof IDiagnosticCheck || $check::getName() !== $name || $check->getScope() !== $scope) {
			throw new InvalidArgumentException('Unknown diagnostic check for this scope.');
		}
		try {
			return $check->run();
		} catch(Throwable $exception) {
			// Never expose potentially sensitive exception contents in a report.
			return new DiagnosticCheckResult(DiagnosticCheckResult::ERROR, 'error');
		}
	}

	private function assertScope(string $name): void {
		$scope = $this->classmap->getInstanceByInterfaceName(IDiagnosticScope::class, $name);
		if(!$scope instanceof IDiagnosticScope) {
			throw new InvalidArgumentException('Unknown diagnostic scope.');
		}
	}

}

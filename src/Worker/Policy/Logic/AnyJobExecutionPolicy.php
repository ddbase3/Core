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

namespace Base3\Core\Worker\Policy\Logic;

use Base3\CoreFoundation\Worker\Policy\Logic\AbstractLogicalJobExecutionPolicy;

use Base3\CoreFoundation\Worker\Api\IJobExecutionPolicy;

final class AnyJobExecutionPolicy extends AbstractLogicalJobExecutionPolicy {

	private ?IJobExecutionPolicy $matchedPolicy = null;

	public static function getName(): string {
		return 'anyjobexecutionpolicy';
	}

	public function shouldRun(string $jobName) {
		$this->matchedPolicy = null;
		$reasons = [];

		foreach ($this->policies as $policy) {
			if ($policy->shouldRun($jobName)) {
				$this->matchedPolicy = $policy;
				return true;
			}

			$reason = trim((string)$policy->getReason());
			if ($reason !== '') {
				$reasons[] = $reason;
			}
		}

		$this->setReason($reasons !== [] ? implode(' / ', $reasons) : 'Skip (no policy allowed execution)');
		return false;
	}

	public function markRun(string $jobName) {
		if ($this->matchedPolicy !== null) {
			$this->matchedPolicy->markRun($jobName);
		}
	}

	public function getSchema(): array {
		return [
			'type' => 'array',
			'items' => [
				'type' => 'object',
				'required' => ['policy'],
				'properties' => [
					'policy' => [
						'type' => 'string',
						'description' => 'Policy name.'
					],
					'data' => [
						'type' => 'object',
						'description' => 'Policy configuration data.'
					]
				]
			]
		];
	}
}

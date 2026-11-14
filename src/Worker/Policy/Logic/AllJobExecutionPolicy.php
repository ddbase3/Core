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

final class AllJobExecutionPolicy extends AbstractLogicalJobExecutionPolicy {

	public static function getName(): string {
		return 'alljobexecutionpolicy';
	}

	public function shouldRun(string $jobName) {
		foreach ($this->policies as $policy) {
			if (!$policy->shouldRun($jobName)) {
				$this->setReason($policy->getReason());
				return false;
			}
		}

		return true;
	}

	public function markRun(string $jobName) {
		foreach ($this->policies as $policy) {
			$policy->markRun($jobName);
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

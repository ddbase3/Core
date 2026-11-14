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

final class NotJobExecutionPolicy extends AbstractLogicalJobExecutionPolicy {

	private ?IJobExecutionPolicy $policy = null;

	public static function getName(): string {
		return 'notjobexecutionpolicy';
	}

	public function setData(array $data) {
		parent::setData($data);

		$definition = $data['item'] ?? $data;
		$this->policy = is_array($definition) ? $this->createPolicy($definition) : null;
	}

	public function shouldRun(string $jobName) {
		if ($this->policy === null) {
			return true;
		}

		if ($this->policy->shouldRun($jobName)) {
			$this->setReason('Skip (negated policy allowed execution)');
			return false;
		}

		return true;
	}

	public function markRun(string $jobName) {}

	public function getSchema(): array {
		return [
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
		];
	}
}

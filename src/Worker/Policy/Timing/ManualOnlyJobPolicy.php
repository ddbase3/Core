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

namespace Base3\Core\Worker\Policy\Timing;

use Base3\CoreFoundation\State\Api\IStateStore;
use Base3\CoreFoundation\Worker\Policy\AbstractJobExecutionPolicy;

final class ManualOnlyJobPolicy extends AbstractJobExecutionPolicy {

	private const POLICY_TYPE = 'manual_only';

	public function __construct(
		private readonly IStateStore $state
	) {}

	public static function getName(): string {
		return 'manualonlyjobpolicy';
	}

	public function shouldRun(string $jobName) {
		$requested = (int)$this->state->get(
			$this->stateKey($jobName, self::POLICY_TYPE, 'requested', $this->getPolicyId()),
			0
		);

		if ($requested !== 1) {
			$this->setReason('Skip (manual run not requested)');
			return false;
		}

		return true;
	}

	public function markRun(string $jobName) {
		$this->state->set(
			$this->stateKey($jobName, self::POLICY_TYPE, 'requested', $this->getPolicyId()),
			0
		);

		$this->state->set(
			$this->stateKey($jobName, self::POLICY_TYPE, 'last_run_at', $this->getPolicyId()),
			$this->nowSqlString()
		);
	}

	public function getSchema(): array {
		return [
			'type' => 'object',
			'properties' => [
				'id' => [
					'type' => 'string',
					'description' => 'Optional policy instance id.'
				]
			]
		];
	}
}

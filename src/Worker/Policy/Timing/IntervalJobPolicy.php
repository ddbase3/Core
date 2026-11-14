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

final class IntervalJobPolicy extends AbstractJobExecutionPolicy {

	private const POLICY_TYPE = 'interval';
	private const DEFAULT_LAST_RUN_AT = '1970-01-01 00:00:00';

	public function __construct(
		private readonly IStateStore $state
	) {}

	public static function getName(): string {
		return 'intervaljobpolicy';
	}

	public function shouldRun(string $jobName) {
		$seconds = $this->getInt('seconds', 0);

		if ($seconds <= 0) {
			return true;
		}

		$lastRunAt = (string)$this->state->get(
			$this->stateKey($jobName, self::POLICY_TYPE, 'last_run_at', $this->getPolicyId()),
			self::DEFAULT_LAST_RUN_AT
		);

		$lastRunTs = strtotime($lastRunAt);
		if ($lastRunTs === false) {
			return true;
		}

		if ((time() - $lastRunTs) < $seconds) {
			$this->setReason('Skip (interval not reached)');
			return false;
		}

		return true;
	}

	public function markRun(string $jobName) {
		$this->state->set(
			$this->stateKey($jobName, self::POLICY_TYPE, 'last_run_at', $this->getPolicyId()),
			$this->nowSqlString()
		);
	}

	public function getSchema(): array {
		return [
			'type' => 'object',
			'required' => ['seconds'],
			'properties' => [
				'seconds' => [
					'type' => 'integer',
					'description' => 'Minimum number of seconds between two successful runs.'
				],
				'id' => [
					'type' => 'string',
					'description' => 'Optional policy instance id.'
				]
			]
		];
	}
}

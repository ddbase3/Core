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

final class RandomIntervalJobPolicy extends AbstractJobExecutionPolicy {

	private const POLICY_TYPE = 'random_interval';
	private const DEFAULT_NEXT_RUN_AT = '1970-01-01 00:00:00';

	public function __construct(
		private readonly IStateStore $state
	) {}

	public static function getName(): string {
		return 'randomintervaljobpolicy';
	}

	public function shouldRun(string $jobName) {
		$nextRunAt = (string)$this->state->get(
			$this->stateKey($jobName, self::POLICY_TYPE, 'next_run_at', $this->getPolicyId()),
			self::DEFAULT_NEXT_RUN_AT
		);

		$nextRunTs = strtotime($nextRunAt);
		if ($nextRunTs === false || $nextRunAt === self::DEFAULT_NEXT_RUN_AT) {
			return true;
		}

		if (time() < $nextRunTs) {
			$this->setReason('Skip (random interval not reached)');
			return false;
		}

		return true;
	}

	public function markRun(string $jobName) {
		$min = max(0, $this->getInt('min_seconds', 0));
		$max = max($min, $this->getInt('max_seconds', $min));
		$offset = $max > $min ? random_int($min, $max) : $min;

		$this->state->set(
			$this->stateKey($jobName, self::POLICY_TYPE, 'last_run_at', $this->getPolicyId()),
			$this->nowSqlString()
		);

		$this->state->set(
			$this->stateKey($jobName, self::POLICY_TYPE, 'next_run_at', $this->getPolicyId()),
			date('Y-m-d H:i:s', time() + $offset)
		);
	}

	public function getSchema(): array {
		return [
			'type' => 'object',
			'required' => ['min_seconds', 'max_seconds'],
			'properties' => [
				'min_seconds' => [
					'type' => 'integer',
					'description' => 'Minimum number of seconds before the next run.'
				],
				'max_seconds' => [
					'type' => 'integer',
					'description' => 'Maximum number of seconds before the next run.'
				],
				'id' => [
					'type' => 'string',
					'description' => 'Optional policy instance id.'
				]
			]
		];
	}
}

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

final class DailyWindowJobPolicy extends AbstractJobExecutionPolicy {

	private const POLICY_TYPE = 'daily_window';
	private const DEFAULT_LAST_RUN_AT = '1970-01-01 00:00:00';

	public function __construct(
		private readonly IStateStore $state
	) {}

	public static function getName(): string {
		return 'dailywindowjobpolicy';
	}

	public function shouldRun(string $jobName) {
		$from = $this->getString('from', '00:00');
		$to = $this->getString('to', '23:59');

		if (!$this->isTimeInWindow(date('H:i'), $from, $to)) {
			$this->setReason('Skip (not in daily window)');
			return false;
		}

		$lastRunAt = (string)$this->state->get(
			$this->stateKey($jobName, self::POLICY_TYPE, 'last_run_at', $this->getPolicyId()),
			self::DEFAULT_LAST_RUN_AT
		);

		if ($this->isSameDay($lastRunAt)) {
			$this->setReason('Skip (already ran today)');
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
			'required' => ['from', 'to'],
			'properties' => [
				'from' => [
					'type' => 'string',
					'description' => 'Start time, inclusive, format HH:MM.'
				],
				'to' => [
					'type' => 'string',
					'description' => 'End time, exclusive, format HH:MM.'
				],
				'id' => [
					'type' => 'string',
					'description' => 'Optional policy instance id.'
				]
			]
		];
	}
}

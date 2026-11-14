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

namespace Base3\Core\Worker\Policy\Domain;

use Base3\CoreFoundation\Configuration\Api\IConfiguration;
use Base3\CoreFoundation\Worker\Policy\AbstractJobExecutionPolicy;

final class ConfigEnabledJobPolicy extends AbstractJobExecutionPolicy {

	public function __construct(
		private readonly IConfiguration $configuration
	) {}

	public static function getName(): string {
		return 'configenabledjobpolicy';
	}

	public function shouldRun(string $jobName) {
		$section = $this->getString('section', 'job');
		$key = $this->getString('key', $jobName . '.active');
		$expected = $this->getInt('expected', 1);

		$config = (array)$this->configuration->get($section);

		if ((int)($config[$key] ?? 0) !== $expected) {
			$this->setReason('Skip (disabled by configuration)');
			return false;
		}

		return true;
	}

	public function getSchema(): array {
		return [
			'type' => 'object',
			'properties' => [
				'section' => [
					'type' => 'string',
					'description' => 'Configuration section. Default: job.'
				],
				'key' => [
					'type' => 'string',
					'description' => 'Configuration key. Default: <jobName>.active.'
				],
				'expected' => [
					'type' => 'integer',
					'description' => 'Expected integer value. Default: 1.'
				]
			]
		];
	}
}

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

namespace Base3\Core\Usermanager\Criterion;

use Base3\CoreFoundation\Usermanager\Api\IUserCriterion;
use Base3\CoreFoundation\Usermanager\User;
use InvalidArgumentException;

/** Matches a configured domain against the host-provided user email; does not verify ownership. */
final class Base3EmailDomainCriterion implements IUserCriterion {
	public static function getName(): string { return 'base3emaildomaincriterion'; }
	public function getSchema(): array {
		return ['type' => 'object', 'required' => ['domain'], 'properties' => [
			'domain' => ['type' => 'string', 'minLength' => 1, 'description' => 'Domain without leading @']
		], 'additionalProperties' => false];
	}
	public function matches(?User $user, array $config): bool {
		$domain = $config['domain'] ?? null;
		if (!is_string($domain) || trim($domain) === '' || str_contains($domain, '@')) {
			throw new InvalidArgumentException('domain must be a non-empty DNS domain without @.');
		}
		if ($user === null) return false;
		$email = strtolower(trim((string)($user->email ?? '')));
		$parts = explode('@', $email);
		return count($parts) === 2 && $parts[0] !== '' && $parts[1] === strtolower(trim($domain));
	}
}

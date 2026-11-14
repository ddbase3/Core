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

/** Matches explicitly listed native user IDs, without creating a parallel user registry. */
final class Base3UserIdCriterion implements IUserCriterion {
	public static function getName(): string { return 'base3useridcriterion'; }
	public function getSchema(): array {
		return ['type' => 'object', 'required' => ['user_ids'], 'properties' => [
			'user_ids' => ['type' => 'array', 'items' => ['type' => ['integer', 'string']], 'uniqueItems' => true]
		], 'additionalProperties' => false];
	}
	public function matches(?User $user, array $config): bool {
		$ids = $config['user_ids'] ?? null;
		if (!is_array($ids) || !array_is_list($ids)) throw new InvalidArgumentException('user_ids must be a list.');
		if ($user === null) return false;
		return in_array((string)$user->id, array_map('strval', $ids), true);
	}
}

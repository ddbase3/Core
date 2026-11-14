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

/** Matches any authenticated user resolved by the active host IUsermanager. */
final class Base3AuthenticatedCriterion implements IUserCriterion {
	public static function getName(): string { return 'base3authenticatedcriterion'; }
	public function getSchema(): array { return ['type' => 'object', 'properties' => [], 'additionalProperties' => false]; }
	public function matches(?User $user, array $config): bool { return $config === [] && $user !== null; }
}

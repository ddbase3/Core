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

namespace Base3\Core\Authorization\Base3System;

use Base3\CoreFoundation\Authorization\Api\IAuthorizationService;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationDecision;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationRequest;
use Base3\CoreFoundation\Usermanager\Api\IUsermanager;
use InvalidArgumentException;

/** Evaluates existing standalone BASE3 RBAC permissions, without owning a second RBAC store. */
final class Base3SystemAuthorizationService implements IAuthorizationService {

	public function __construct(private readonly IUsermanager $usermanager, private readonly string $authority = 'base3') {}

	public function getDefaultAuthority(): string { return $this->authority; }

	public function decide(AuthorizationRequest $request): AuthorizationDecision {
		if ($request->authority !== $this->authority) return AuthorizationDecision::indeterminate('unknown_authority');
		if ($request->subject !== null || $request->onBehalfOf !== null) {
			return AuthorizationDecision::deny('unsupported_principal');
		}
		if ($request->resource !== null) {
			return AuthorizationDecision::deny('resource_authorization_not_supported');
		}
		$permission = $request->permission;
		foreach ($this->usermanager->getPermissions() as $available) {
			if ($available->scope === 'system' && $available->permission === 'admin') {
				return AuthorizationDecision::allow('system_administrator');
			}
			if ($available->scope !== $permission->scope) continue;
			if ($available->permission === $permission->permission || $available->permission === 'admin') {
				return AuthorizationDecision::allow('role_permission');
			}
		}
		return AuthorizationDecision::deny('no_permission');
	}

	public function decideMany(array $requests): array {
		$results = [];
		foreach ($requests as $key => $request) {
			if (!$request instanceof AuthorizationRequest) throw new InvalidArgumentException('Expected AuthorizationRequest.');
			$results[$key] = $this->decide($request);
		}
		return $results;
	}
}

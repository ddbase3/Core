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

namespace Base3\Core\Authorization\No;

use Base3\CoreFoundation\Authorization\Api\IAuthorizationService;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationDecision;
use Base3\CoreFoundation\Authorization\Dto\AuthorizationRequest;
use InvalidArgumentException;

/** Deliberately denies access when no native authority is configured. */
final class NoAuthorizationService implements IAuthorizationService {

	public function getDefaultAuthority(): string { return 'unconfigured'; }

	public function decide(AuthorizationRequest $request): AuthorizationDecision {
		return AuthorizationDecision::deny('no_authority_configured');
	}

	public function decideMany(array $requests): array {
		$result = [];
		foreach ($requests as $key => $request) {
			if (!$request instanceof AuthorizationRequest) {
				throw new InvalidArgumentException('Expected AuthorizationRequest.');
			}
			$result[$key] = $this->decide($request);
		}
		return $result;
	}
}

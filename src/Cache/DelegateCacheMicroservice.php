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

namespace Base3\Core\Cache;

use Base3\Core\ServiceLocator;
use Base3\CoreFoundation\Cache\Api\ICache;
use Base3\CoreFoundation\Microservice\AbstractMicroservice;

class DelegateCacheMicroservice extends AbstractMicroservice implements ICache {

	private $servicelocator;
	private $cache;

	public function __construct() {
		$this->servicelocator = ServiceLocator::getInstance();
		$this->cache = $this->servicelocator->get('cache');
	}

	// Implementation of ICache

	public function getCacheUrl($url, $refresh = false) {
		return $this->cache->getCacheUrl($url, $refresh);
	}

	public function getCacheUrls($urls, $refresh = false) {
		return $this->cache->getCacheUrl($urls, $refresh);
	}

}

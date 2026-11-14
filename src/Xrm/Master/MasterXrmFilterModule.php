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

namespace Base3\Core\Xrm\Master;

use Base3\Core\ServiceLocator;
use Base3\CoreFoundation\Xrm\AbstractXrmFilterModule;

class MasterXrmFilterModule extends AbstractXrmFilterModule {

	private $servicelocator;
	private $xrms;
	private $logger;

	private $filterlist;

	public function __construct() {
		$this->servicelocator = ServiceLocator::getInstance();
		$this->xrms = $this->getXrms();
		$this->logger = $this->servicelocator->get('logger');

		$this->filterlist = array("archive", "name", "owner", "created", "changed", "tag");
	}

	// Implementation of IBase

	public static function getName(): string {
		return "masterxrmfiltermodule";
	}

	// Implementation of IXrmFilterModule

	public function match($xrm, $filter) {
		return in_array($filter->attr, $this->filterlist)
			&& get_class($xrm) == \Base3\Core\Xrm\Master\MasterXrm::class ? 2 : 0;
	}

	public function getEntries($xrm, $filter, $idsonly = false) {
		$entries = array();

		if (in_array($filter->attr, $this->filterlist)) {

			$ids = array();
			$this->xrms = $this->getXrms();
			foreach ($this->xrms as $name => $xrmclosure) {
				$x = $xrmclosure();
				$f = new \Base3\CoreFoundation\Xrm\XrmFilter($filter->attr, $filter->op, $filter->val);
				$es = $x->getEntriesIntern($f, true);

				if ($es != null && sizeof($es)) $ids = array_merge($ids, $es);
			}
/*
			$ids = $this->sliceList($ids, $filter->offset, $filter->limit);
			$entries = $idsonly ? $ids : $xrm->getEntries($ids);
*/
			$entries = $idsonly
				? $ids
				: $this->sliceList($xrm->getEntries($ids), $filter->offset, $filter->limit);
		}

		return $entries;
	}

	// Private functions

	private function getXrms() {
		// funktioniert nicht anders bei Closures
		return $this->servicelocator->get('xrms');
	}

}

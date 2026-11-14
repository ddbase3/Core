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

namespace Base3\Core\Xrm;

use Base3\CoreFoundation\Xrm\XrmFilter;

use Base3\Core\ServiceLocator;
use Base3\CoreFoundation\Xrm\AbstractXrmFilterModule;

class AllocXrmFilterModule extends AbstractXrmFilterModule {

	protected $servicelocator;
	protected $logger;

	public function __construct() {
		$this->servicelocator = ServiceLocator::getInstance();
		$this->logger = $this->servicelocator->get('logger');
	}

	// Implementation of IBase

	public static function getName(): string {
		return "allocxrmfiltermodule";
	}

	// Implementation of IXrmFilterModule

	public function match($xrm, $filter) {
		return $filter->attr == "alloc" ? 1 : 0;
	}

	public function getEntries($xrm, $filter, $idsonly = false) {
		$entries = array();

		if ($filter->attr == "alloc" && $filter->op == "with") {

			$id = is_object($filter->val) ? $filter->val->id : ( is_array($filter->val) ? $filter->val["id"] : $filter->val );
			$ids = $xrm->getAllocIds($id);
			if ($ids != null && sizeof($ids)) {
/*
				$ids = $this->sliceList($ids, $filter->offset, $filter->limit);
				$entries = $idsonly ? $ids : $xrm->getEntries($ids);
*/
				$entries = $idsonly
					? $ids
					: $this->sliceList($xrm->getEntries($ids), $filter->offset, $filter->limit);
			}

		} else if ($filter->attr == "alloc" && $filter->op == "without") {

			$all = $xrm->getAllEntryIds();
			$f = new XrmFilter("alloc", "with", $filter->val);
			$es = $this->getEntries($xrm, $f, true);
			$ids = array_diff($all, $es);

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

}

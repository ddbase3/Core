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

use Base3\Core\ServiceLocator;
use Base3\CoreFoundation\Xrm\AbstractXrmFilterModule;

class AssignXrmFilterModule extends AbstractXrmFilterModule {

	protected $servicelocator;

	public function __construct() {
		$this->servicelocator = ServiceLocator::getInstance();
	}

	// Implementation of IBase

	public static function getName(): string {
		return "assignxrmfiltermodule";
	}

	// Implementation of IXrmFilterModule

	public function match($xrm, $filter) {
		return $filter->attr == "assigned" ? 1 : 0;
	}

	public function getEntries($xrm, $filter, $idsonly = false) {
		$entries = array();

		if ($filter->attr == "assigned" && $filter->op == "to" && $filter->val == "user") {

			$id = $xrm->getUserEntryId();
			if ($id != null) {
				$ids = $xrm->getAllocIds($id);
				if ($ids != null && sizeof($ids)) {
					$entries = $idsonly
						? $ids
						: $this->sliceList($xrm->getEntries($ids), $filter->offset, $filter->limit);
				}
			}

		}

		return $entries;
	}

}

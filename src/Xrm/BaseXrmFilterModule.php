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

use Base3\CoreFoundation\Xrm\Api\IXrmFilterModule;

class BaseXrmFilterModule implements IXrmFilterModule {

	// Implementation of IBase

	public static function getName(): string {
		return "basexrmfiltermodule";
	}

	// Implementation of IXrmFilterModule

	public function match($xrm, $filter) {
		return $filter == null || in_array($filter->attr, array("ids", "app", "xrm")) ? 1 : 0;
	}

	public function getEntries($xrm, $filter, $idsonly = false) {
// TODO LIMIT !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
		$entries = array();

		if ($filter == null) {

			$ids = $xrm->getAllEntryIds();
			$entries = $idsonly ? $ids : $xrm->getEntries($ids);

		} else if ($filter->attr == "ids" && $filter->op == "in") {  // $filter->val ist Array mit IDs

			$entries = $idsonly ? $filter->val : $xrm->getEntries($filter->val);

		} else if ($filter->attr == "ids" && $filter->op == "notin") {  // $filter->val ist Array mit IDs

			$all = $xrm->getAllEntryIds();
			$ids = array_diff($all, $filter->val);
			$entries = $idsonly ? $ids : $xrm->getEntries($ids);

		} else if ($filter->attr == "app" && $filter->op == "eq") {  // $filter->val ist String

			$f = new \Base3\CoreFoundation\Xrm\XrmFilter("alloc", "with", "xxap" . strtolower($filter->val));
			$entries = $xrm->getEntriesIntern($f, $idsonly);

		} else if ($filter->attr == "app" && $filter->op == "neq") {  // $filter->val ist String

			$all = $xrm->getAllEntryIds();
			$f = new \Base3\CoreFoundation\Xrm\XrmFilter("app", "eq", $filter->val);
			$es = $this->getEntries($xrm, $f, true);
			$ids = array_diff($all, $es);
			$entries = $idsonly ? $ids : $xrm->getEntries($ids);

		} else if ($filter->attr == "xrm" && $filter->op == "eq") {  // $filter->val ist String

			$ids = $xrm->getXrmEntryIds($filter->val);
			$entries = $idsonly ? $ids : $xrm->getEntries($ids);

		} else if ($filter->attr == "xrm" && $filter->op == "neq") {  // $filter->val ist String

			$ids = $xrm->getXrmEntryIds($filter->val, true);
			$entries = $idsonly ? $ids : $xrm->getEntries($ids);

		}

		return $entries;
	}

}

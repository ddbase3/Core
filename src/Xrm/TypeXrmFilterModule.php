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

class TypeXrmFilterModule implements IXrmFilterModule {

	// Implementation of IBase

	public static function getName(): string {
		return "typexrmfiltermodule";
	}

	// Implementation of IXrmFilterModule

	public function match($xrm, $filter) {
		return $filter->attr == "type" ? 1 : 0;
	}

	public function getEntries($xrm, $filter, $idsonly = false) {
		$entries = array();

		if ($filter->attr == "type" && $filter->op == "eq") {  // $filter->val ist String

			$f = new \Base3\CoreFoundation\Xrm\XrmFilter("alloc", "with", "xxty" . strtolower($filter->val), $filter->offset, $filter->limit);
			$entries = $xrm->getEntriesIntern($f, $idsonly);

		} else if ($filter->attr == "type" && $filter->op == "neq") {  // $filter->val ist String

			$all = $xrm->getAllEntryIds();
			$f = new \Base3\CoreFoundation\Xrm\XrmFilter("type", "eq", $filter->val, $filter->offset, $filter->limit);
			$es = $this->getEntries($xrm, $f, true);
			$ids = array_diff($all, $es);
			$entries = $idsonly ? $ids : $xrm->getEntries($ids);

		}

		return $entries;
	}

}

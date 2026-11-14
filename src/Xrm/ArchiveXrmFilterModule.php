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

use Base3\CoreFoundation\Xrm\AbstractXrmFilterModule;

class ArchiveXrmFilterModule extends AbstractXrmFilterModule {

	// Implementation of IBase

	public static function getName(): string {
		return "archivexrmfiltermodule";
	}

	// Implementation of IXrmFilterModule

	public function match($xrm, $filter) {
		return $filter->attr == "archive" ? 1 : 0;
	}

	// TODO
	// Hilfs-DB (Datei-basiert) im Master (oder Cache?) fragen, welche Entry-IDs so gesetzt sind (File-XRM und Master-XRM nutzt dies, BASE3-XRM nicht)

	public function getEntries($xrm, $filter, $idsonly = false) {
		$entries = array();

		if ($filter->attr == "archive" && (
				$filter->op == "eq" && $filter->val == "undef"
				|| $filter->op == "neq" && $filter->val != "undef")) {
			$ids = $xrm->getAllEntryIds();
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

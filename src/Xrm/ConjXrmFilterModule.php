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

class ConjXrmFilterModule extends AbstractXrmFilterModule {

	protected $servicelocator;
	protected $logger;

	public function __construct() {
		$this->servicelocator = ServiceLocator::getInstance();
		$this->logger = $this->servicelocator->get('logger');
	}

	// Implementation of IBase

	public static function getName(): string {
		return "conjxrmfiltermodule";
	}

	// Implementation of IXrmFilterModule

	public function match($xrm, $filter) {
		return $filter->attr == "conj" ? 1 : 0;
	}

	public function getEntries($xrm, $filter, $idsonly = false) {
		$entries = array();

		if ($filter->attr == "conj" && in_array($filter->op, array("or", "and", "not"))) {

			$ids = array();

			if ($filter->op == "or") {  // $filter->val ist Array von Filtern

				$idlists = array();
				foreach ($filter->val as $f) {
					$es = $xrm->getEntriesIntern($f, true);
					$idlists[] = $es != null && sizeof($es) ? $es : array();
				}
				$ids = $this->getUnion($idlists);

			} else if ($filter->op == "and") {  // $filter->val ist Array von Filtern

				$idlists = array();
				foreach ($filter->val as $f) {
					$es = $xrm->getEntriesIntern($f, true);
					$idlists[] = $es != null && sizeof($es) ? $es : array();
				}
				$ids = $this->getIntersection($idlists);

			} else if ($filter->op == "not") {  // $filter->val ist Filter

				$all = $xrm->getAllEntryIds();
				$es = $xrm->getEntriesIntern($filter->val, true);
				$ids = array_diff($all, $es);

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

	// Private methods

	private function getIntersection($as) {
		if (sizeof($as) < 2) return $as[0];
		return array_values(array_intersect(... $as));
	}

	private function getUnion($as) {
		if (sizeof($as) < 2) return $as[0];
		return array_unique(array_merge(... $as));
	}

}

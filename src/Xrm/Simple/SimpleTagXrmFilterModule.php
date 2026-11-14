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

namespace Base3\Core\Xrm\Simple;

use Base3\Core\ServiceLocator;
use Base3\CoreFoundation\Xrm\Api\IXrmFilterModule;

class SimpleTagXrmFilterModule implements IXrmFilterModule {

	private $servicelocator;
	private $database;

	public function __construct() {
		$this->servicelocator = ServiceLocator::getInstance();
		$this->database = $this->servicelocator->get('database');
	}

	// Implementation of IBase

	public static function getName(): string {
		return "simpletagxrmfiltermodule";
	}

	// Implementation of IXrmFilterModule

	public function match($xrm, $filter) {
		return $filter->attr == "tag" && get_class($xrm) == \Base3\Core\Xrm\Simple\SimpleXrm::class ? 2 : 0;
	}

	public function getEntries($xrm, $filter, $idsonly = false) {
		$entries = array();

		if ($filter->attr == "tag" && ($filter->op == "conn" || $filter->op == "notconn")) {

			$ids = array();

			$this->database->connect();

			$sql = "";
			$limit = $this->getLimitString($filter->offset, $filter->limit);
			if ($filter->op == "conn") {
				$sql = "SELECT LOWER(HEX(`id`)) AS `uuid` FROM `tag` WHERE `tag` = '" . $this->database->escape($filter->val) . "'" . $limit;
			} else if ($filter->op == "notconn") {
				$sql = "SELECT LOWER(HEX(e.`id`)) AS `uuid`
					FROM `entry` e
					LEFT JOIN `tag` t ON e.`id` = t.`id` AND t.`tag` = '" . $this->database->escape($filter->val) . "'
					WHERE t.`id` IS NULL"
					. $limit;
			}
			$sysentries = $this->database->multiQuery($sql);
			foreach ($sysentries as $sysentry) $ids[] = $sysentry["uuid"];

			$entries = $idsonly ? $ids : $xrm->getEntries($ids);
		}

		return $entries;
	}

	// Private methods

	private function getLimitString($offset, $limit) {
		if ($offset == null && $limit != null) return " LIMIT " . intval($limit);
		else if ($offset != null && $limit == null) return " LIMIT " . intval($offset) . ", 18446744073709551615";
		else if ($offset != null && $limit != null) return " LIMIT " . intval($offset) . ", " . intval($limit);
		else return "";
	}

}

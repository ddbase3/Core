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

namespace Base3\Core\Xrm\Cache;

use Base3\CoreFoundation\Api\ICheck;
use Base3\CoreFoundation\Xrm\AbstractXrm;

class CacheXrm extends AbstractXrm implements ICheck {

	private $database;

	private $xrmname;

	public function __construct($xrmname) {
		parent::__construct(\Base3\Core\ServiceLocator::getInstance());
		$this->database = $this->servicelocator->get('database');
		$this->xrmname = $xrmname;
	}

	// Implementation of IXrm

	public function getXrmName() {
		return $this->xrmname;
	}

	public function delEntry($id, $moveonly = false) {
		return true;
	}

	public function setEntry($entry) {
		return null;
	}

	public function getEntry($id) {
		return null;
	}

	public function getEntries($ids) {
		return array();
	}

	public function getAllocIds($id) {
		if (substr($id, 0, 4) != "xxty") return array();

		// TODO App
		// TODO Tag

		$groups = array();
		$user = (object) $this->usermanager->getUser();
		if ($user->role != "admin") $groups = $this->usermanager->getGroups();

		$this->database->connect();

		$sql = "";
		if ($user->role == "admin") {
			$sql = "SELECT DISTINCT LOWER(HEX(`uuid`)) AS `uuid` FROM `xrmentry` WHERE `type` = '" . $this->database->escape(substr($id, 4)) . "'";
		} else {

			$gs = array();
			foreach ($groups as $group) {
				$g = (object) $group;
				$gs[] = $this->database->escape($g->id);
			}

			$sql = "SELECT DISTINCT LOWER(HEX(e.`uuid`)) AS `uuid`
				FROM `xrmentry` e
				LEFT JOIN `xrmaccess` a1 ON e.`uuid` = a1.`uuid` AND a1.`usergroup` = 0 AND a1.`id` = '" . $this->database->escape($user->id) . "'
				LEFT JOIN `xrmaccess` a2 ON e.`uuid` = a2.`uuid` AND a2.`usergroup` = 1 AND a2.`id` IN ('" . implode("', '", $gs) . "')
				WHERE (a1.`uuid` IS NOT NULL OR a2.`uuid` IS NOT NULL) AND `type` = '" . $this->database->escape(substr($id, 4)) . "'";
		}
		$entryids = $this->database->listQuery($sql);

		return $entryids;
	}

	public function getAllEntryIds() {
		return $this->getAllEntryIdsIntern();
	}

	public function getXrmEntryIds($xrmname, $invert = false) {
		return $this->getAllEntryIdsIntern($xrmname, $invert);
	}

	public function addTag($id, $tag) {
		return true;
	}

	public function removeTag($id, $tag) {
		return true;
	}

	public function addApp($id, $app) {
		return true;
	}

	public function removeApp($id, $app) {
		return true;
	}

	public function addAlloc($id1, $id2) {
		return true;
	}

	public function removeAlloc($id1, $id2) {
		return true;
	}

	// Implementation of ICheck

	public function checkDependencies() {
		return array(
			"depending_services" => $this->database == null ? "Fail" : "Ok"
		);
	}

	// Private methods

	private function getAllEntryIdsIntern($xrmname = null, $invert = null) {

		$entryids = array();

		$groups = array();
		$user = (object) $this->usermanager->getUser();
		if ($user->role != "admin") $groups = $this->usermanager->getGroups();

		$this->database->connect();

		$sql = "";
		if ($user->role == "admin") {
			$sql = "SELECT DISTINCT LOWER(HEX(`uuid`)) AS `uuid` FROM `xrmentry`";
			if ($xrmname) $sql .= " WHERE `xrm` " . ($invert ? "!" : "") . "= '" . $this->database->escape($xrmname) . "'";
		} else {

			$gs = array();
			foreach ($groups as $group) {
				$g = (object) $group;
				$gs[] = $this->database->escape($g->id);
			}

			$sql = "SELECT DISTINCT LOWER(HEX(e.`uuid`)) AS `uuid`
				FROM `xrmentry` e
				LEFT JOIN `xrmaccess` a1 ON e.`uuid` = a1.`uuid` AND a1.`usergroup` = 0 AND a1.`id` = '" . $this->database->escape($user->id) . "'
				LEFT JOIN `xrmaccess` a2 ON e.`uuid` = a2.`uuid` AND a2.`usergroup` = 1 AND a2.`id` IN ('" . implode("', '", $gs) . "')
				WHERE (a1.`uuid` IS NOT NULL OR a2.`uuid` IS NOT NULL)";
			if ($xrmname) $sql .= " AND e.`xrm` " . ($invert ? "!" : "") . "= '" . $this->database->escape($xrmname) . "'";
		}
		$entryids = $this->database->listQuery($sql);

		return $entryids;
	}

}

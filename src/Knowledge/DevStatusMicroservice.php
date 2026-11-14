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

namespace Base3\Core\Knowledge;

use Base3\CoreFoundation\Knowledge\Api\IKnowledge;
use Base3\CoreFoundation\Microservice\AbstractMicroservice;

class DevStatusMicroservice extends AbstractMicroservice implements IKnowledge {

	private $data;

	public function __construct() {
		$file = DIR_LOCAL . "devstatus.json";
		if (file_exists($file)) {
			$content = file_get_contents($file);
			$this->data = json_decode($content, true);
		} else {
			$this->data = array();
		}
	}

	// Implementation of IKnowledge

	public function getScopes() {
		return array_keys($this->data);
	}

	public function getFields($scope) {
		if (!isset($this->data[$scope]) || !isset($this->data[$scope][0])) return null;
		return array_keys($this->data[$scope][0]);
	}

	public function getData($scope, $fields = null) {
		if (!isset($this->data[$scope])) return null;
		if ($fields == null) return $this->data[$scope];
		$result = array();
		foreach ($this->data[$scope] as $data) {
			$d = array();
			foreach ($fields as $field) $d[$field] = $data[$field];
			$result[] = $d;
		}
		return $result;
	}

}

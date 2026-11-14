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

namespace Base3\Core\Cache;

use Base3\Core\ServiceLocator;
use Base3\CoreFoundation\Cache\Api\ICache;

class FileCache implements ICache {

	private $database;
	private $cnf;

	private $timeout;

	public function __construct($cnf = null) {
		$servicelocator = ServiceLocator::getInstance();
		$this->database = $servicelocator->get('database');
		$configuration = $servicelocator->get('configuration');
		$this->cnf = $configuration == null ? array("url" => "") : $configuration->get('base');

		$this->timeout = 60 * 60 * 24 * 30;  // 1 month
	}

	// Implementation of ICache

	public function getCacheUrl($url, $refresh = false) {
		return $this->getCacheUrls(array($url), $refresh);
	}

	public function getCacheUrls($urls, $refresh = false) {

		$destdir = "userfiles/cache/";

		$this->database->connect();

		$timeout = time() + $this->timeout;

		$result = array();

		foreach ($urls as $url) {

			$hash = sha1($url);

			if ($refresh) {

				$sql = "DELETE FROM `cache` WHERE `hash` = '" . $hash . "'";
				$this->database->nonQuery($sql);

			} else {

				// check if exists
				$sql = "SELECT `value`, `extension` FROM `cache` WHERE `hash` = '" . $hash . "'";
				$row = $this->database->singleQuery($sql);
				if ($row != null) {

					$sql = "UPDATE `cache` SET `timeout` = " . $timeout . " WHERE `hash` = '" . $hash . "'";
					$this->database->nonQuery($sql);

					$newfile = $row["value"] . "." . $row["extension"];
					$dir = substr($newfile, 0, 2) . "/" . substr($newfile, 2, 2) . "/";
					$newfile = $dir . $newfile;

					$result[$url] = $this->cnf["url"] . $destdir . $newfile;

					continue;
				}

			}

			$value = $this->generateToken();
			$qm = strpos($url, "?");
			$cleanurl = $qm === false ? $url : substr($url, 0, $qm);
			$extension = substr($url, strrpos($url, ".") + 1);
			if (strpos($extension, "/") !== false) $extension = "";

			$newfile = $value . "." . $extension;
			$dir = substr($newfile, 0, 2) . "/" . substr($newfile, 2, 2) . "/";
			$newfile = $dir . $newfile;

			$folder = $destdir . $dir;
			if (!is_dir($folder)) mkdir($folder, 0777, true);

			$content = file_get_contents($url);

			$fp = fopen($destdir . $newfile, "wb");
			fwrite($fp, $content);
			fclose($fp);
		
			$sql = "INSERT INTO `cache` (`hash`, `value`, `extension`, `timeout`) VALUES ('" . $hash . "', '" . $value . "', '" . $extension . "', " . $timeout . ")";
			$this->database->nonQuery($sql);

			$result[$url] = $this->cnf["url"] . $destdir . $newfile;

		}

		return $result;
	}

	// Private functions

	private function generateToken($length = 32) {
		$characters = "0123456789abcdefghijklmnopqrstuvwxyz";
		$random_string = "";
		for ($p = 0; $p < $length; $p++) $random_string .= $characters[mt_rand(0, strlen($characters)-1)];
		return $random_string;
	}

}

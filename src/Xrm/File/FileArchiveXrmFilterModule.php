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

namespace Base3\Core\Xrm\File;

use Base3\CoreFoundation\Xrm\Api\IXrmFilterModule;

class FileArchiveXrmFilterModule implements IXrmFilterModule {

	// Implementation of IBase

	public static function getName(): string {
		return "filearchivexrmfiltermodule";
	}

	// Implementation of IXrmFilterModule

	public function match($xrm, $filter) {
		return $filter->attr == "archive" && get_class($xrm) == \Base3\Core\Xrm\File\FileXrm::class ? 2 : 0;
	}

	public function getEntries($xrm, $filter, $idsonly = false) {
		// FileXrm nutzt den Cache, daher müssen hier keine Einträge geliefert werden
		$entries = array();
		return $entries;
	}

}

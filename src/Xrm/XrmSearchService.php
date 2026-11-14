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
use Base3\CoreFoundation\Api\IOutput;

class XrmSearchService implements IOutput {

	private $servicelocator;

	public function __construct() {
		$this->servicelocator = ServiceLocator::getInstance();
	}

	// Implementation of IBase

	public static function getName(): string {
		return "xrmsearchservice";
	}

	// Implementation of IOutput

	public function getOutput(string $out = 'html', bool $final = false): string {

		if (!isset($_REQUEST["q"])) return null;

		$scope = isset($_REQUEST["x"]) && $_REQUEST["x"] == "global" ? 'xrmglobal' : 'xrm';
		$xrm = $this->servicelocator->get($scope);

		$filter = new \Base3\CoreFoundation\Xrm\XrmFilter;
		$filter->fromJson($_REQUEST["q"]);

		$format = isset($_REQUEST["fo"]) ? $_REQUEST["fo"] : "ids";  // ids | data | teaser | display
		switch ($format) {

			case "ids":
				$entries = $xrm->getEntriesIntern($filter, 1);
				return json_encode($entries);

			case "data":
				$entries = $xrm->getFilteredEntries($filter);
				return json_encode($entries);

			case "teaser":
			case "display":
				$entries = $xrm->getFilteredEntries($filter);
				$entrydisplays = array();
				foreach ($entries as $entry) {
					$display = new \Base3\Custom\Display\XrmEntryDisplay;
					$display->setData(array("xrmentry" => $entry, "teaser" => $format != "display"));
					$entrydisplays[] = $display->getOutput();
				}
				return '<div>' . implode("\n", $entrydisplays) . '</div>';

		}

		return null;
	}

	public function getHelp(): string {
		return 'Help of XrmSearchService' . "\n";
	}
}

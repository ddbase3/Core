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

namespace Base3\Core\Util\MailParser;

use Base3\CoreFoundation\Util\MailParser\MailPart;

class MailParser {

	private $content;
	private $parts;

	public function __construct($filename = null) {
		if (!isset($filename)) return;
		$this->content = file_get_contents($filename);
		$this->parts = $this->parse($this->content);
	}

	public function fromString($content) {
		$this->content = $content;
		$this->parts = $this->parse($this->content);
	}

	private function parse($content) {
		$matches = array();
		// preg_match('#Content-Type: multipart\/[^;]+;\s*boundary="([^"]+)"#i', $content, $matches);
		preg_match('#Content-Type: multipart\/.+;\s*boundary="([^"]+)"#i', $content, $matches);
		// list($x, $boundary) = $matches;
		$boundary = array_pop($matches);
		if (isset($boundary) && !empty($boundary)) {
			$end = strrpos($content, '--' . $boundary . '--');
			if ($end === false) return new MailPart($content);
			$strippedcontent = substr($content, 0, $end);
			$segments = explode('--' . $boundary, $strippedcontent);
			if (sizeof($segments) == 1) return new MailPart($content);
			$parts = array();
			foreach ($segments as $segment) {
				$part = $this->parse($segment);
				if ($part != null) $parts[] = $part;
			}
			return $parts;
		} else {
			return new MailPart($content);
		}
	}

	public function getParts() {
		return $this->parts;
	}

	public function toString($part = null, $depth = 0) {
		if ($part == null) $part = $this->parts;
		if (is_array($part)) {
			$str = str_replace(" ", "&nbsp;", str_pad("", $depth * 20, " ")) . "[--" . sizeof($part) . "--]\n";
			foreach ($part as $segment) $str .= $this->toString($segment, $depth + 1);
			return $str;
		} else {
			return
				str_replace(" ", "&nbsp;", str_pad("", $depth * 20, " ")) . " " . $part->toString() . "\n"
				. str_replace(" ", "&nbsp;", str_pad("", $depth * 20, " ")) . json_encode($part->getHeaders()) . "\n\n";
		}
	}

}

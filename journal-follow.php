<?php

class Journal implements Iterator {
#lass Journal {
	private $proc;
	private $stdout;
	private $entry;
	private $cursor;

	static function _join_argv($argv) {
		return implode(" ",
			array_map(function($a) {
				return strlen($a) ? escapeshellarg($a) : "''";
			}, $argv));
	}

	function __construct($filter=[]) {
		$this->_open_journal($filter);
	}

	function _close_journal() {
		if ($this->stdout) {
			fclose($this->stdout);
			$this->stdout = null;
		}
		if ($this->proc) {
			proc_close($this->proc);
			$this->proc = null;
		}
		$this->entry = null;
		$this->cursor = null;
	}

	function _open_journal($filter=[]) {
		if ($this->proc)
			$this->_close_journal();
		$cmd = array_merge(["journalctl", "-f", "-ojson"], $filter);
		$cmd = self::_join_argv($cmd);
		$fdspec = [
			0 => ["file", "/dev/null", "r"],
			1 => ["pipe", "w"],
			2 => ["file", "/dev/null", "w"],
		];
		$this->proc = proc_open($cmd, $fdspec, $fds);
		if (!$this->proc)
			return false;
		$this->stdout = $fds[1];
	}

	function follow() {
		while (($line = fgets($this->stdout)) !== false) {
			$dict = json_decode($line);
			print_r($dict);
		}
	}

	function rewind() {
		return;
	}

	function current() {
		return $this->entry;
	}

	function key() {
		return $this->cursor;
	}

	function next() {
		$line = fgets($this->stdout);
		if ($line === false)
			return null;
		$this->entry = json_decode($line);
		$this->cursor = $this->entry->__CURSOR;
		return $this->entry;
	}

	function valid() {
		return true;
	}
}

$a = new Journal();

foreach ($a as $cursor => $item) {
	var_dump($cursor);
	print_r($item);
}

<?php

/* Iterator extends Traversable {
	mixed   current()
	scalar  key()
	void    next()
	void    rewind()
	boolean valid()
}
calls:	rewind, valid==true, current, key
	next, valid==true, current, key
	next, valid==false
*/

class Journal implements Iterator {
	private $filter;
	private $startpos;
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

	function __construct($filter=[], $cursor=null) {
		$this->filter = $filter;
		$this->startpos = $cursor;
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

	function _open_journal($filter=[], $cursor=null) {
		if ($this->proc)
			$this->_close_journal();

		$this->filter = $filter;
		$this->startpos = $cursor;

		$cmd = ["journalctl", "-f", "-o", "json"];
		if ($cursor) {
			$cmd[] = "-c";
			$cmd[] = $cursor;
		}
		$cmd = array_merge($cmd, $filter);
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

		/* current() must return the first entry after rewinding */
		$this->next();
	}

	function seek($cursor) {
		$this->_open_journal($this->filter, $cursor);
	}

	function rewind() {
		$this->seek($this->startpos);
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
		/* callers retrieve the entry using current() */
	}

	function valid() {
		return true;
	}
}

$a = new Journal();

foreach ($a as $cursor => $item) {
	echo "================\n";
	var_dump($cursor);
	//print_r($item);
	if ($item)
		var_dump($item->MESSAGE);
}

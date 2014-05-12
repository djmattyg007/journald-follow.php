<?php
/* © 2013 Mantas Mikulėnas <grawity@gmail.com>
 * Released under WTFPL v2 <http://sam.zoy.org/wtfpl/>
 */

class Journal implements Iterator
{
	protected $filter;
	protected $startpos;
	protected $proc;
	protected $stdout;
	protected $entry;

	public static function _join_argv($argv)
	{
		return implode(" ",
			array_map(function($a) {
				return strlen($a) ? escapeshellarg($a) : "''";
			}, $argv));
	}

	public function __construct($filter = [], $cursor = null)
	{
		$this->filter = $filter;
		$this->startpos = $cursor;
	}

	protected function _close_journal()
	{
		if ($this->stdout) {
			fclose($this->stdout);
			$this->stdout = null;
		}
		if ($this->proc) {
			proc_close($this->proc);
			$this->proc = null;
		}
		$this->entry = null;
	}

	protected function _open_journal($filter = [], $cursor = null)
	{
		if ($this->proc) {
			$this->_close_journal();
		}

		$this->filter = $filter;
		$this->startpos = $cursor;

		$cmd = ["journalctl", "-f", "-o", "json"];
		if ($cursor) {
			$cmd[] = "-c";
			$cmd[] = $cursor;
		}
		$cmd = array_merge($cmd, $filter);
		$cmd = self::_join_argv($cmd);

		$fdspec = array(
			0 => array("file", "/dev/null", "r"),
			1 => array("pipe", "w"),
			2 => array("file", "/dev/null", "w"),
		);

		$this->proc = proc_open($cmd, $fdspec, $fds);
		if (!$this->proc) {
			return false;
		}
		$this->stdout = $fds[1];
	}

	public function seek($cursor)
	{
		$this->_open_journal($this->filter, $cursor);
	}

	public function rewind()
	{
		$this->seek($this->startpos);
	}

	public function next()
	{
		$line = fgets($this->stdout);
		if ($line === false) {
			$this->entry = false;
		} else {
			$this->entry = json_decode($line);
		}
	}

	function valid()
	{
		/* null is valid, it just means next() hasn't been called yet */
		return ($this->entry !== false);
	}

	function current()
	{
		if (!$this->entry) {
			$this->next();
		}
		return $this->entry;
	}

	function key()
	{
		if (!$this->entry) {
			$this->next();
		}
		return $this->entry->__CURSOR;
	}
}

$a = new Journal();

foreach ($a as $cursor => $item) {
	echo "================\n";
	var_dump($cursor);
	//print_r($item);
	if ($item) {
		var_dump($item->MESSAGE);
	}
}


This is a simple PHP class to read the contents of the journald syslog.
The original code can be found here: https://gist.github.com/grawity/6536586
I've made only minor modifications (code style, method visibility, etc).
I've also removed the example from the PHP file.

Usage:

require("/path/to/journal-follow.php");

$a = new Journal();

foreach ($a as $cursor => $item) {
    echo "================\n";
    var_dump($cursor);
    if ($item) {
        var_dump($item);
    }
}


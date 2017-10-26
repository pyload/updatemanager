<?php
require_once('lib/updatemanager.inc.php');
require_once('lib/database.inc.php');

class UpdateManagerTest extends PHPUnit\Framework\TestCase {
    public function testSetupDB() {
        $l = new \Logger();
        $um = new UpdateManager($l);
        $um->update(true);

        $content = file_get_contents(PLUGINLIST_FILE);
        $this->assertNotEquals(false, $content);

        $content = explode(PHP_EOL, $content);
        if (end($content) == '')
            $content = array_slice($content, 0, -1);
        $bl = array_search('BLACKLIST', $content);
        if ($bl != false) {
            $plugins = array_slice($content, 3, $bl-3);
            $blacklist = array_slice($content, $bl + 1);
        }
        else {
            $plugins = array_slice($content, 3);
            $blacklist = array();
        }

        foreach ($plugins as $line) {
            $this->assertEquals(1, preg_match('~^(\w+?)\|(\w+?\.py)\|(\w+?)\|(\d+\.\d+)$~', $line));
        }
        foreach ($blacklist as $line) {
            $this->assertEquals(1, preg_match('~^(\w+?)\|(\w+?\.py)$~', $line));
        }
    }
}
?>

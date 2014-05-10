<?php
require_once('lib/database.inc.php');

class DatabaseTest extends PHPUnit_Framework_TestCase {

    public function testSetupDB() {
        $db = new umSQLite3('tests/testSetupDB.db');
        $q = "SELECT * FROM plugins";
        $r = $db->query($q);
        $this->assertEmpty($r->fetchArray());
        $this->assertEquals(5, $r->numColumns());
        $this->assertEquals('name', $r->columnName(0));
        $this->assertEquals('type', $r->columnName(1));
        $this->assertEquals('sha', $r->columnName(2));
        $this->assertEquals('version', $r->columnName(3));
        $this->assertEquals('del', $r->columnName(4));

        $q = "INSERT INTO plugins (name, type, sha, version, del) VALUES ('test.py', 'hooks', 'abcdefg', '0.15', 0)";
        $db->exec($q);

        $db->close();
        $db = new umSQLite3('tests/testSetupDB.db');

        // Now there should be one row and del should be set to 1
        $q = "SELECT * FROM plugins";
        $r = $db->query($q);

        $r = $r->fetchArray(SQLITE3_ASSOC);
        $this->assertEquals('test.py', $r['name']);
        $this->assertEquals('hooks', $r['type']);
        $this->assertEquals('abcdefg', $r['sha']);
        $this->assertEquals('0.15', $r['version']);
        $this->assertEquals(1, $r['del']);

        $db->close();
    }

    public function testPluginExists() {
        $db = new umSQLite3(':memory:');

        $exists = $db->plugin_exists('hooks', 'test.py', '0.15');
        $this->assertEquals($exists, 0);

        $q = "INSERT INTO plugins (name, type, sha, version, del) VALUES ('test.py', 'hooks', 'abcdefg', '0.15', 0)";
        $db->exec($q);

        $exists = $db->plugin_exists('hooks', 'test.py', '0.15');
        $this->assertEquals($exists, 2);
        $exists = $db->plugin_exists('hooks', 'test.py', '0.11');
        $this->assertEquals($exists, 1);

        $db->close();
    }

    public function testInsertPlugin() {
        $db = new umSQLite3(':memory:');
        $db->insert_plugin('hooks', 'test.py', 'abcdefg', '0.15');
        $this->assertEquals(1, $db->changes());

        $q = "SELECT * FROM plugins";
        $r = $db->query($q);

        $r = $r->fetchArray(SQLITE3_ASSOC);
        $this->assertEquals('test.py', $r['name']);
        $this->assertEquals('hooks', $r['type']);
        $this->assertEquals('abcdefg', $r['sha']);
        $this->assertEquals('0.15', $r['version']);
        $this->assertEquals(0, $r['del']);

        $db->close();
    }
}
?>

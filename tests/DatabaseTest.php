<?php
require_once('lib/database.inc.php');

class DatabaseTest extends PHPUnit_Framework_TestCase {

    public function testSetupDB() {
        $db = new umSQLite3('tests/testSetupDB.db');
        $q = 'SELECT * FROM plugins';
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
        $r = $db->query('SELECT * FROM plugins');

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

        $r = $db->query('SELECT * FROM plugins');

        $r = $r->fetchArray(SQLITE3_ASSOC);
        $this->assertEquals('test.py', $r['name']);
        $this->assertEquals('hooks', $r['type']);
        $this->assertEquals('abcdefg', $r['sha']);
        $this->assertEquals('0.15', $r['version']);
        $this->assertEquals(0, $r['del']);

        $db->close();
    }

    public function testUpdatePlugin() {
        $db = new umSQLite3(':memory:');
        $q = "INSERT INTO plugins (name, type, sha, version, del) VALUES
              ('test.py', 'hooks', 'abcdefg', '0.15', 1)";
        $db->exec($q);
        $q = "INSERT INTO plugins (name, type, sha, version, del) VALUES
              ('test2.py', 'hoster', 'hijklmn', '0.22', 1)";
        $db->exec($q);

        // 2 entry already in the database
        // One is not changed so only del must be set to 0
        // the other has also increased the version number
        $db->update_plugin('hooks', 'test.py');
        $db->update_plugin('hoster', 'test2.py', 'opqrstu', '0.23');

        // Checking if the database has been updated correctly
        $r = $db->query('SELECT * FROM plugins ORDER BY name');

        $exp = array(
            0 => array(
                    'name'    => 'test.py',
                    'type'    => 'hooks',
                    'sha'     => 'abcdefg',
                    'version' => '0.15',
                    'del'     => 0
            ),
            1 => array(
                    'name'    => 'test2.py',
                    'type'    => 'hoster',
                    'sha'     => 'opqrstu',
                    'version' => '0.23',
                    'del'     => 0
            )
        );

        $i = 0;
        while($row = $r->fetchArray(SQLITE3_ASSOC)) {
            foreach(array('name', 'type', 'sha', 'version', 'del') as $par)
                $this->assertEquals($exp[$i][$par], $row[$par]);
            $i++;
        }
        $this->assertEquals(2, $i);

        $db->close();
    }

    public function testDeletePlugin() {
        $db = new umSQLite3(':memory:');
        $q = "INSERT INTO plugins (name, type, sha, version, del) VALUES
              ('test.py', 'hooks', 'abcdefg', '0.15', 1)";
        $db->exec($q);
        $q = "INSERT INTO plugins (name, type, sha, version, del) VALUES
              ('test2.py', 'hoster', 'hijklmn', '0.22', 1)";
        $db->exec($q);

        // 2 plugins in the DB marked for removal.
        $nd = $db->delete_plugins();
        $this->assertEquals(2, $nd);

        // The database must be empty
        $r = $db->query('SELECT * FROM plugins');
        $this->assertFalse($r->fetchArray());

        $db->close();
    }

    public function testGetRows() {
        $db = new umSQLite3(':memory:');
        $q = "INSERT INTO plugins (name, type, sha, version, del) VALUES
              ('test.py', 'hooks', 'abcdefg', '0.15', 0)";
        $db->exec($q);
        $q = "INSERT INTO plugins (name, type, sha, version, del) VALUES
              ('test2.py', 'accounts', 'hijklmn', '0.22', 0)";
        $db->exec($q);

        // Testing the entry in the database correctly sorted
        $exp = array(
            0 => array(
                    'name'    => 'test2.py',
                    'type'    => 'accounts',
                    'sha'     => 'hijklmn',
                    'version' => '0.22',
                    'del'     => 0
            ),
            1 => array(
                    'name'    => 'test.py',
                    'type'    => 'hooks',
                    'sha'     => 'abcdefg',
                    'version' => '0.15',
                    'del'     => 0
            )
        );
        $r = $db->get_rows();
        $i = 0;
        while($row = $r->fetchArray(SQLITE3_ASSOC)) {
            foreach(array('name', 'type', 'sha', 'version', 'del') as $par)
                $this->assertEquals($exp[$i][$par], $row[$par]);
            $i++;
        }
        $this->assertEquals(2, $i);

        $db->close();
    }
}
?>

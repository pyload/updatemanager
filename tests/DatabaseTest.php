<?php
require_once('lib/database.inc.php');

class DatabaseTest extends PHPUnit\Framework\TestCase {

    public function testSetupDB() {
        $db = new umSQLite3('tests/testSetupDB.db');
        $q = 'SELECT * FROM plugins';
        $r = $db->query($q);
        $this->assertEmpty($r->fetchArray());
        $this->assertEquals(4, $r->numColumns());
        $this->assertEquals('name', $r->columnName(0));
        $this->assertEquals('type', $r->columnName(1));
        $this->assertEquals('sha', $r->columnName(2));
        $this->assertEquals('version', $r->columnName(3));

        $q = "INSERT INTO plugins(name, type, sha, version)
              VALUES ('test.py', 'hooks', 'abcdefg', '0.15')";
        $db->exec($q);

        $db->close();
        $db = new umSQLite3('tests/testSetupDB.db');

        // Now there should be one row
        $r = $db->query('SELECT * FROM plugins');

        $r = $r->fetchArray(SQLITE3_ASSOC);
        $this->assertEquals('test.py', $r['name']);
        $this->assertEquals('hooks', $r['type']);
        $this->assertEquals('abcdefg', $r['sha']);
        $this->assertEquals('0.15', $r['version']);

        $db->close();

        $db = new umSQLite3('tests/testSetupDB.db');
        $q = 'SELECT * FROM blacklist';
        $r = $db->query($q);
        $this->assertEmpty($r->fetchArray());
        $this->assertEquals(2, $r->numColumns());
        $this->assertEquals('name', $r->columnName(0));
        $this->assertEquals('type', $r->columnName(1));

        $q = "INSERT INTO blacklist(name, type)
              VALUES ('test.py', 'hooks')";
        $db->exec($q);

        $db->close();
        $db = new umSQLite3('tests/testSetupDB.db');

        // Now there should be one row
        $r = $db->query('SELECT * FROM blacklist');

        $r = $r->fetchArray(SQLITE3_ASSOC);
        $this->assertEquals('test.py', $r['name']);
        $this->assertEquals('hooks', $r['type']);

        $db->close();
    }

    public function testPluginExists() {
        $db = new umSQLite3(':memory:');

        $exists = $db->plugin_exists('hooks', 'test.py', '0.15');
        $this->assertEquals($exists, 0);

        $q = "INSERT INTO plugins(name, type, sha, version)
              VALUES ('test.py', 'hooks', 'abcdefg', '0.15')";
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

        $db->insert_plugin('hooks', 'test.py', 'abcdefg', '0.12');
        $this->assertEquals(1, $db->changes());

        $r = $db->query('SELECT * FROM plugins');

        $row = $r->fetchArray(SQLITE3_ASSOC);
        $this->assertEquals('test.py', $row['name']);
        $this->assertEquals('hooks', $row['type']);
        $this->assertEquals('abcdefg', $row['sha']);
        $this->assertEquals('0.12', $row['version']);
        $this->assertFalse($r->fetchArray());

        $db->close();
    }

    public function testUpdatePlugin() {
        $db = new umSQLite3(':memory:');
        $q = "INSERT INTO plugins(name, type, sha, version)
              VALUES ('test.py', 'hoster', 'hijklmn', '0.22')";
        $db->exec($q);

        $db->update_plugin('hoster', 'test.py', 'opqrstu', '0.23');

        // Checking if the database has been updated correctly
        $r = $db->query('SELECT * FROM plugins
                         ORDER BY name');

        $r = $r->fetchArray(SQLITE3_ASSOC);
        $this->assertEquals('test.py', $r['name']);
        $this->assertEquals('hoster', $r['type']);
        $this->assertEquals('opqrstu', $r['sha']);
        $this->assertEquals('0.23', $r['version']);

        $db->close();
    }

    public function testRemovePlugin()
    {
        $db = new umSQLite3(':memory:');
        $q = "INSERT INTO plugins(name, type, sha, version)
              VALUES ('test.py', 'hooks', 'abcdefg', '0.15')";
        $db->exec($q);
        $q = "INSERT INTO plugins(name, type, sha, version)
              VALUES ('test2.py', 'hoster', 'hijklmn', '0.22')";
        $db->exec($q);

        $db->remove_plugin('hoster', 'test2.py');
        $this->assertEquals(1, $db->changes());

        $db->remove_plugin('hoster', 'test2.py');
        $this->assertEquals(0, $db->changes());

        $r = $db->query('SELECT * FROM plugins');
        $row = $r->fetchArray(SQLITE3_ASSOC);
        $this->assertEquals('test.py', $row['name']);
        $this->assertEquals('hooks', $row['type']);
        $this->assertEquals('abcdefg', $row['sha']);
        $this->assertEquals('0.15', $row['version']);
        $this->assertFalse($r->fetchArray());

        $r = $db->query('SELECT * FROM blacklist');
        $row = $r->fetchArray(SQLITE3_ASSOC);
        $this->assertEquals('test2.py', $row['name']);
        $this->assertEquals('hoster', $row['type']);
        $this->assertFalse($r->fetchArray());

        $db->close();
    }

    public function testGetPluginRows() {
        $db = new umSQLite3(':memory:');
        $q = "INSERT INTO plugins(name, type, sha, version)
              VALUES('test.py', 'hooks', 'abcdefg', '0.15')";
        $db->exec($q);
        $q = "INSERT INTO plugins(name, type, sha, version)
              VALUES('test2.py', 'accounts', 'hijklmn', '0.22')";
        $db->exec($q);

        // Testing the entry in the database correctly sorted
        $exp = array(
            0 => array(
                    'name'    => 'test2.py',
                    'type'    => 'accounts',
                    'sha'     => 'hijklmn',
                    'version' => '0.22',
            ),
            1 => array(
                    'name'    => 'test.py',
                    'type'    => 'hooks',
                    'sha'     => 'abcdefg',
                    'version' => '0.15',
            )
        );
        $r = $db->get_plugin_rows();
        $i = 0;
        while($row = $r->fetchArray(SQLITE3_ASSOC)) {
            $this->assertLessThan(count($exp), $i);
            foreach(array('name', 'type', 'sha', 'version') as $par)
                $this->assertEquals($exp[$i][$par], $row[$par]);
            $i++;
        }
        $this->assertEquals(2, $i);

        $db->close();
    }

    public function testGetBlacklistRows() {
        $db = new umSQLite3(':memory:');
        $q = "INSERT INTO blacklist(name, type)
              VALUES('test.py', 'hooks')";
        $db->exec($q);
        $q = "INSERT INTO blacklist(name, type)
              VALUES('test2.py', 'accounts')";
        $db->exec($q);

        // Testing the entry in the database correctly sorted
        $exp = array(
            0 => array(
                'name'    => 'test2.py',
                'type'    => 'accounts',
            ),
            1 => array(
                'name'    => 'test.py',
                'type'    => 'hooks',
            )
        );
        $r = $db->get_blacklist_rows();
        $i = 0;
        while($row = $r->fetchArray(SQLITE3_ASSOC)) {
            $this->assertLessThan(count($exp), $i);
            foreach(array('name', 'type') as $par)
                $this->assertEquals($exp[$i][$par], $row[$par]);
            $i++;
        }
        $this->assertEquals(2, $i);

        $db->close();
    }
    public function testSetPrevCommit() {
        $db = new umSQLite3(':memory:');

        $db->set_prev_commit('abcdefg');
        $this->assertEquals(1, $db->changes());

        $db->set_prev_commit('hijklmn');
        $this->assertEquals(1, $db->changes());

        $r = $db->query("SELECT * FROM status");
        $row = $r->fetchArray(SQLITE3_ASSOC);
        $this->assertEquals(0, $row['id']);
        $this->assertEquals('hijklmn', $row['prev_commit']);
        $this->assertFalse($r->fetchArray());

        $db->close();
    }

    public function testGetPrevCommit() {
        $db = new umSQLite3(':memory:');
        $q = "INSERT INTO status(id, prev_commit)
              VALUES(0, 'abcdefg')";
        $db->exec($q);

        $this->assertEquals('abcdefg', $db->get_prev_commit());

        $db->close();
    }
}
?>

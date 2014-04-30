<?php
require_once('lib/database.inc.php');

class DatabaseTest extends PHPUnit_Framework_TestCase {
    
    protected static $db;
    
    public static function setUpBeforeClass() {
        self::$db = new umSQLite3('tests/test.db');
    }
    
    public static function tearDownAfterClass() {
        self::$db->close();
    }
    
    public function testPluginInsertAndExists() {
        $exists = self::$db->plugin_exists('hooks', 'test.py', '0.15');
        $this->assertEquals($exists, 0);
        
        self::$db->insert_plugin('hooks', 'test.py', 'abcdefg', '0.15');
        
        $exists = self::$db->plugin_exists('hooks', 'test.py', '0.15');
        $this->assertEquals($exists, 2);
        $exists = self::$db->plugin_exists('hooks', 'test.py', '0.11');
        $this->assertEquals($exists, 1);
    }
}
?>

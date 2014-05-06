<?php
class umSQLite3 extends SQLite3 {

    function __construct($filename) {
        parent::__construct($filename);
        $this->setup();
    }

    private function setup() {
        $this->exec('CREATE TABLE IF NOT EXISTS plugins
            (name TEXT, type TEXT, sha VARCHAR(40), version TEXT, del BOOLEAN)');
        $this->exec('UPDATE plugins SET del=1');
    }

    public function plugin_exists($type, $name, $version) {
        /* Returns:
         * 0: The plugin doesn't exists;
         * 1: The plugin exists but the version number is different;
         * 2: The plugin exists and the version number is the same.
         */
        $q = $this->prepare('SELECT version FROM plugins WHERE name=:name AND type=:type');
        $q->bindValue(':name', $name, SQLITE3_TEXT);
        $q->bindValue(':type', $type, SQLITE3_TEXT);
        $r = $q->execute();
        $r = $r->fetchArray(SQLITE3_NUM);
        if(! $r)
            return 0;
        elseif($r[0] != $version)
            return 1;
        else
            return 2;
    }

    public function insert_plugin($type, $name, $sha, $version) {
        $q = $this->prepare('INSERT INTO plugins VALUES (:name, :type, :sha, :v, 0)');
        $q->bindValue(':name', $name, SQLITE3_TEXT);
        $q->bindValue(':type', $type, SQLITE3_TEXT);
        $q->bindValue(':sha', $sha, SQLITE3_TEXT);
        $q->bindValue(':v', $version, SQLITE3_TEXT);
        $q->execute();
    }

    public function update_plugin($type, $name, $sha=null, $version=null) {
        if($sha && $version) {
            $q = $this->prepare('UPDATE plugins SET sha=:sha, version=:v WHERE name=:name AND type=:type AND version!=:v');
            $q->bindValue(':name', $name, SQLITE3_TEXT);
            $q->bindValue(':type', $type, SQLITE3_TEXT);
            $q->bindValue(':sha', $sha, SQLITE3_TEXT);
            $q->bindValue(':v', $version, SQLITE3_TEXT);
            $q->execute();
        }
        $q = $this->prepare('UPDATE plugins SET del=0 WHERE name=:name AND type=:type');
        $q->bindValue(':name', $name, SQLITE3_TEXT);
        $q->bindValue(':type', $type, SQLITE3_TEXT);
        $q->execute();
    }

    function delete_plugins() {
        $this->exec('DELETE FROM plugins WHERE del=1');
        return $this->changes();
    }

    public function get_rows() {
        $r = $this->query('SELECT * FROM plugins ORDER BY type, name ASC');
        return $r;
    }
}
?>

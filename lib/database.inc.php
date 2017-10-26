<?php
class umSQLite3 extends SQLite3 {

    function __construct($filename) {
        parent::__construct($filename);
        $this->setup();
    }

    private function setup() {
        $this->exec('CREATE TABLE IF NOT EXISTS plugins(name TEXT, type TEXT, sha VARCHAR(40), version TEXT,
                     UNIQUE(name, type))');
        $this->exec('CREATE TABLE IF NOT EXISTS blacklist(name TEXT, type TEXT,
                     UNIQUE(name, type))');
        $this->exec('CREATE TABLE IF NOT EXISTS status(id INTEGER , prev_commit TEXT,
                     UNIQUE(id))');
    }

    public function plugin_exists($type, $name, $version) {
        /* Returns:
         * 0: The plugin doesn't exists;
         * 1: The plugin exists but the version number is different;
         * 2: The plugin exists and the version number is the same.
         */
        $q = $this->prepare('SELECT version FROM plugins
                             WHERE name=:name AND type=:type');
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
        $q = $this->prepare('INSERT OR REPLACE INTO plugins
                             VALUES (:name, :type, :sha, :v)');
        $q->bindValue(':name', $name, SQLITE3_TEXT);
        $q->bindValue(':type', $type, SQLITE3_TEXT);
        $q->bindValue(':sha', $sha, SQLITE3_TEXT);
        $q->bindValue(':v', $version, SQLITE3_TEXT);
        $q->execute();
    }

    public function update_plugin($type, $name, $sha, $version) {
        $q = $this->prepare('UPDATE plugins
                             SET sha=:sha, version=:v
                             WHERE name=:name AND type=:type AND version!=:v');
        $q->bindValue(':name', $name, SQLITE3_TEXT);
        $q->bindValue(':type', $type, SQLITE3_TEXT);
        $q->bindValue(':sha', $sha, SQLITE3_TEXT);
        $q->bindValue(':v', $version, SQLITE3_TEXT);
        $q->execute();
    }

    public function remove_plugin($type, $name) {
        $q = $this->prepare('INSERT OR REPLACE INTO blacklist(name, type)
                             VALUES (:name, :type)');
        $q->bindValue(':name', $name, SQLITE3_TEXT);
        $q->bindValue(':type', $type, SQLITE3_TEXT);
        $q->execute();

        $q = $this->prepare('DELETE FROM plugins
                             WHERE name=:name AND type=:type');
        $q->bindValue(':name', $name, SQLITE3_TEXT);
        $q->bindValue(':type', $type, SQLITE3_TEXT);
        $q->execute();
    }

    function get_prev_commit() {
        $q = $this->prepare('SELECT prev_commit FROM status
                             WHERE id=0');
        $r = $q->execute();
        $r = $r->fetchArray(SQLITE3_NUM);
        if(! $r)
            return null;
        else
            return $r[0];
    }

    function set_prev_commit($prev_commit) {
        $q = $this->prepare('INSERT OR REPLACE INTO status(id, prev_commit)
                             VALUES (0, :prev_commit)');
        $q->bindValue(':prev_commit', $prev_commit, SQLITE3_TEXT);
        $q->execute();
    }

    public function get_plugin_rows() {
        $r = $this->query('SELECT * FROM plugins
                           ORDER BY type, name ASC');
        return $r;
    }

    public function get_blacklist_rows() {
        $r = $this->query('SELECT * FROM blacklist
                           ORDER BY type, UPPER(name) ASC');
        return $r;
    }
}
?>

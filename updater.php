<?php
define('PLUGIN_LIST', getenv('OPENSHIFT_DATA_DIR') . 'plugins.txt');
define('SQLITEDB', getenv('OPENSHIFT_DATA_DIR') . 'plugins.sqlite');
define('BLACKLIST_PATH', getenv('OPENSHIFT_REPO_DIR') . 'blacklist.txt');
define('REPO_PATH', getenv('OPENSHIFT_DATA_DIR') . 'pyload-repo/');
define('REPO_PLUGINS_PATH', REPO_PATH . '/module/plugins/');
define('LOGDIR', getenv('OPENSHIFT_LOG_DIR'));

/* Constants for local test env
define('PLUGIN_LIST', 'plugins.txt');
define('SQLITEDB', 'plugins.sqlite');
define('BLACKLIST_PATH', 'blacklist.txt');
define('REPO_PATH', 'pyload-repo');
define('REPO_PLUGINS_PATH', REPO_PATH . '/module/plugins/');
define('LOGDIR', 'logs');
*/

define('BRANCH', 'stable');

require 'vendor/autoload.php';
require_once('lib/database.inc.php');
require_once('lib/git.inc.php');

class UpdateManager {

    private $git;
    private $db;
    private $l;

    public $last_commit;
    public $EXCLUDE = array('.', '..', '__init__.py');

    function __construct($l) {
        $this->l = $l;
        $this->git = new GitCMD($l, REPO_PATH, BRANCH);
        $this->last_commit = $this->git->last_commit();
        $this->l->info("Last commit: $this->last_commit");
        $this->db = new umSQLite3(SQLITEDB);
    }

    function __destruct() {
        $this->db->close();
    }

    private function get_version($type, $name) {
        $path = REPO_PLUGINS_PATH . $type . "/" . $name;
        $f = fopen($path, 'r');
        $content = fread($f, filesize($path));
        fclose($f);
        $status = preg_match('/__version__\s*=\s*[\'"]([^\'"]+)[\'"]/i', $content, $m);
        if(! isset($m[1])) {
            $this->l->error("Unable to detect version for $type/$name");
            return null;
        }
        else {
            return $m[1];
        }
    }

    private function fill_blacklist() {
        $f = fopen(BLACKLIST_PATH, 'r');
        $content = fread($f, filesize(BLACKLIST_PATH));
        fclose($f);
        return $content;
    }

    public function update_db() {
        foreach (array('accounts', 'container', 'crypter', 'hooks', 'hoster', 'internal') as $subfolder) {
            $files = scandir(REPO_PLUGINS_PATH . $subfolder);
            foreach($files as $file) {
                if(in_array($file, $this->EXCLUDE)) {
                    // This plugin is in the EXCLUDE list! Skipping.
                    $this->l->info("Skipping $subfolder/$file");
                    continue;
                }
                $file_version = $this->get_version($subfolder, $file);
                switch($this->db->plugin_exists($subfolder, $file, $file_version)) {
                    case 0:
                        // The plugin is not in the DB. Create a new entry!
                        $this->l->info("New plugin $subfolder/$file! Adding to the database");
                        $this->db->insert_plugin($subfolder, $file, $this->last_commit, $file_version);
                        break;
                    case 1:
                        // The plugin is in the database but may be outdated!
                        // If version number has changed update both version and sha
                        $this->l->info("$subfolder/$file updated to $file_version");
                        $this->db->update_plugin($subfolder, $file, $this->last_commit, $file_version);
                        break;
                    case 2:
                        // The plugin is in the database and it's updated!
                        // Just mark to avoid removal.
                        $this->db->update_plugin($subfolder, $file);
                        break;
                }
            }
        }
    }

    public function remove_deleted() {
        $n_removed = $this->db->delete_plugins();
        $this->l->info("$n_removed removed plugins deleted");
    }

    public function write_static() {
        $f = fopen(PLUGIN_LIST, 'w');
        fwrite($f, "None\nhttps://raw.github.com/pyload/pyload/%(changeset)s/module/plugins/%(type)s/%(name)s\ntype|name|changeset|version");
        $db_rows = $this->db->get_rows();
        while($row = $db_rows->fetchArray(SQLITE3_ASSOC)) {
            fwrite($f, sprintf("\n%s|%s|%s|%s", $row['type'], $row['name'], $row['sha'], $row['version']));
        }
        fwrite($f, "\nBLACKLIST\n");
        fwrite($f, $this->fill_blacklist());

        fclose($f);
    }
}

if(php_sapi_name() != 'cli' && ((! isset($_GET['key'])) || (sha1(trim($_GET['key'])) != 'b7739cb242fe4e9506a6488d96de0b187fb170ae'))) {
    $l->warning('Invalid key');
    exit('Invalid key. Exiting.');
}

$l = new Katzgrau\KLogger\Logger(LOGDIR);
$l->info('Update process started');

$um = new UpdateManager($l);
$um->update_db();
$um->remove_deleted();
$l->info('DB update completed');

// The DB is now updated! Let's create the static file.
$um->write_static();
$l->info('Plugin list created');
?>

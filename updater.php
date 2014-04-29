<?php
define('PLUGIN_LIST', getenv('OPENSHIFT_DATA_DIR') . 'plugins.txt');
define('SQLITEDB', getenv('OPENSHIFT_DATA_DIR') . 'plugins.sqlite');
define('BLACKLIST_PATH', getenv('OPENSHIFT_REPO_DIR') . 'blacklist.txt');
define('REPO_PATH', getenv('OPENSHIFT_DATA_DIR') . 'pyload-repo/');
define('REPO_PLUGINS_PATH', REPO_PATH . '/module/plugins/');

/* Constants for local test env
define('PLUGIN_LIST', 'plugins.txt');
define('SQLITEDB', 'plugins.sqlite');
define('BLACKLIST_PATH', 'blacklist.txt');
define('REPO_PATH', 'pyload-repo');
define('REPO_PLUGINS_PATH', REPO_PATH . '/module/plugins/');
*/

define('BRANCH', 'stable');

require_once('lib/database.inc.php');
require_once('lib/git.inc.php');

class UpdateManager {

    private $git;
    private $db;

    public $last_commit;
    public $EXCLUDE = array('.', '..', '__init__.py', 'AbstractExtractor.py');

    function __construct() {
        $this->git = new GitCMD(REPO_PATH, BRANCH);
        $this->last_commit = $this->git->last_commit();
        print "Last commit: $this->last_commit\n";
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
            printf("WARNING: Unable to detect version for %s/%s\n", $type, $name);
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
        foreach (array('accounts', 'crypter', 'hooks', 'hoster', 'internal') as $subfolder) {
            $files = scandir(REPO_PLUGINS_PATH . $subfolder);
            foreach($files as $file) {
                if(in_array($file, $this->EXCLUDE)) {
                    // This plugin is in the EXCLUDE list! Skipping.
                    print "Skipping $subfolder/$file\n";
                    continue;
                }
                $file_version = $this->get_version($subfolder, $file);
                switch($this->db->plugin_exists($subfolder, $file, $file_version)) {
                    case 0:
                        // The plugin is not in the DB. Create a new entry!
                        print "New plugin $subfolder/$file! Adding to the database\n";
                        $this->db->insert_plugin($subfolder, $file, $this->last_commit, $file_version);
                        break;
                    case 1:
                        // The plugin is in the database but may be outdated!
                        // If version number has changed update both version and sha
                        print "$subfolder/$file updated to $file_version\n";
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
        print 'Deleting removed plugins... ';
        $n_removed = $this->db->delete_plugins();
        print "$n_removed deleted!\n";
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

if((! isset($_GET['key'])) || (sha1(trim($_GET['key'])) != 'b7739cb242fe4e9506a6488d96de0b187fb170ae'))
    exit('Invalid key. Exiting.');

$um = new UpdateManager;
$um->update_db();
$um->remove_deleted();
print "DB update completed!\n";

// The DB is now updated! Let's create the static file.
$um->write_static();
print "Plugin list created!\n";
?>

<?php
require('vendor/autoload.php');
require_once('lib/updatemanager.inc.php');
require_once('lib/log.inc.php');

set_time_limit(0);

$starttime = time();

$l = new \Logger();

if (php_sapi_name() != 'cli') {
    header('Content-Type: text/plain');

    $remoteaddr = $_SERVER['HTTP_X_APPENGINE_USER_IP'] ?? null;
    $country = $_SERVER['HTTP_X_APPENGINE_COUNTRY'] ?? null;
    $city = $_SERVER['HTTP_X_APPENGINE_CITY'] ?? null;
    if ($remoteaddr)
        $l->debug("Got request from $remoteaddr ($city, $country)");
    $get = $_GET;
    if (isset($get['key']))
        $get['key'] = "********";
    $qs = array();
    foreach($get as $key => $value) {
        array_push($qs, "$key=$value");
    }
    $qs = implode("&", $qs);
    $l->debug("Request query string: $qs");

    if (isset($_SERVER['HTTP_USER_AGENT']) && substr($_SERVER['HTTP_USER_AGENT'], 0, 16) == 'GitHub-Hookshot/') {
        if (!isset($_SERVER['HTTP_X_GITHUB_EVENT']) || $_SERVER['HTTP_X_GITHUB_EVENT'] != 'push') {
            $l->info('Not a push event.');
            exit(0);
        }
        if (!isset($_POST['payload'])) {
            $l->error("No payload. Exiting.");
            header('HTTP/1.0 400 Bad Request');
            exit(1);
        }
        $payload = $_POST['payload'];
    }
    else
        $payload = null;

    $key = $_GET['key'] ?? null;
    if ($key) {
        if (hash("sha256", trim($key)) != '5b80c6b93b3d760d3889079814d28cb41cbfacae2d31620f99c602150986304c') {
            $l->error("Invalid key. Exiting.");
            header('HTTP/1.0 403 Forbidden');
            exit(1);
        }
    }

    if ($payload) {
        if (!$key) {
            list($algo, $hmac) = explode('=', $_SERVER['HTTP_X_HUB_SIGNATURE'], 2) + array('', '');
            if (!in_array($algo, hash_algos(), TRUE)) {
                $l->error("Hash algorithm '$algo' is not supported. Exiting.");
                header('HTTP/1.0 500 Internal server error');
                exit(2);
            }
            $raw_post_data = file_get_contents('php://input');
            if (hash_hmac($algo, $raw_post_data, getenv('WEBHOOK_SECRET')) != $hmac) {
                $l->error("Webhook secret does not match. Exiting.");
                header('HTTP/1.0 403 Forbidden');
                exit(1);
            }
        }

        $json = json_decode($payload, true);

        // Ignore commits from ourself to avoid infinite webhook calls
        if ($json['pusher']['name'] == getenv('GIT_USER')) {
            $l->info('Ignoring commit from ourself.');
            exit(0);
        }

        if ($json['repository']['clone_url'] == PYLOAD_REPO_URL) {
            if (!isset($json['ref']) || $json['ref'] != 'refs/heads/' . PYLOAD_BRANCH) {
                $l->info('Not our branch.');
                exit(0);
            }
        }
        elseif ($json['repository']['clone_url'] == SERVER_REPO_URL) {
            if (!isset($json['ref']) || $json['ref'] != 'refs/heads/master') {
                $l->info('Not our branch.');
                exit(0);
            }
        }
        else {
            $l->error("Unknown repository. Exiting.");
            header('HTTP/1.0 403 Forbidden');
            exit(1);
        }
    }
    elseif (!$key) {
        $l->error("Missing webhook secret. Exiting.");
        header('HTTP/1.0 403 Forbidden');
        exit(1);
    }
}

$l->info('Update process started.');

$dry_run = isset($_GET['dry_run']) && trim($_GET['dry_run']) == '1' || isset($argc) && $argc > 1 && $argv[1] == '--dry-run';
if ($dry_run) {
    $l->info("Dry run specified.");
}

$um = new UpdateManager($l);
$um->update($dry_run);

$l->info('Update process finished.');

$seconds = time() - $starttime;
$mins = floor(intval($seconds / 60) % 60);
$secs = floor($seconds % 60);
$l->info("Elapsed time: $mins minutes and $secs seconds.");

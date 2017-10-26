<?php
require('vendor/autoload.php');

define('LOGDIR', 'logs');
define('RSYSLOG_SERVER', getenv('RSYSLOG_SERVER'));
define('RSYSLOG_PORT', getenv('RSYSLOG_PORT'));


class Logger
{
    private $klogger;
    private $cli;
    private $rsyslog;

    function __construct()
    {
        $this->cli = php_sapi_name() == 'cli';
        $this->rsyslog = getenv('RSYSLOG_SERVER') != false && getenv('RSYSLOG_PORT') != false;
        $this->klogger = !$this->rsyslog  && !$this->cli ? new Katzgrau\KLogger\Logger(LOGDIR) : null;
    }

    function send_remote_syslog($message, $component = "updatemanager", $program="pyload") {
        $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        foreach(explode(PHP_EOL, $message) as $line) {
            list($usec, $sec) = explode(" ", microtime());
            $date = date('M d H:i:s', $sec) . str_replace("0.", ".", $usec);
            $syslog_message = "<22>" . $date . ' ' . $program . ' ' . $component . ': ' . $line;
            socket_sendto($sock, $syslog_message, strlen($syslog_message), 0, RSYSLOG_SERVER, RSYSLOG_PORT);
        }
        socket_close($sock);
    }

    public function debug($msg) {
        print($msg . PHP_EOL);
        if ($this->rsyslog)
            $this->send_remote_syslog("[DEBUG] " . $msg);
        if (!is_null($this->klogger))
                $this->klogger->debug($msg);
    }

    public function info($msg) {
        print($msg . PHP_EOL);
        if ($this->rsyslog)
            $this->send_remote_syslog("[INFO] " . $msg);
        if (!is_null($this->klogger))
                $this->klogger->info($msg);
    }

    public function warning($msg) {
        print($msg . PHP_EOL);
        if ($this->rsyslog)
            $this->send_remote_syslog("[WARNING] " . $msg);
        if (!is_null($this->klogger))
                $this->klogger->warning($msg);
    }

    public function error($msg) {
        print($msg . PHP_EOL);
        if ($this->rsyslog)
            $this->send_remote_syslog("[ERROR] " . $msg);
        if (!is_null($this->klogger))
                $this->klogger->error($msg);
    }
}
?>
<?php
class GitCMD {

    private $git_path;
    private $branch;
    private $l;

    public function __construct($l, $directory, $branch='master') {
        $this->l = $l;
        $this->git_path = $directory;
        $this->branch = $branch;
        if(!file_exists($directory . '/.git')) {
            // There is no Git repo here! Cloning.
            $this->gitclone();
        }
        else {
            $this->checkout();
            $this->pull();
        }
    }

    public function gitclone() {
        $output = shell_exec("git clone -b $this->branch https://github.com/pyload/pyload.git $this->git_path");
        if(is_null($output)) {
            $this->l->error('An error occurred cloning the git repo. Exiting.');
            exit("An error occurred cloning the git repo. Exiting.\n");
        }
    }

    public function checkout() {
        exec("cd $this->git_path; git checkout $this->branch");
    }

    public function pull() {
        $output = shell_exec("cd $this->git_path; git pull -s recursive -X theirs");
        if(is_null($output)) {
            $this->l->error('An error occurred fetching the git repo. Exiting.');
            exit("An error occurred fetching the git repo. Exiting.\n");
        }
    }

    public function last_commit() {
        $output = shell_exec("cd $this->git_path; git log -n 1 --pretty=format:%h");
        return $output;
    }
}
?>

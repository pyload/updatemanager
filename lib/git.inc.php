<?php
class GitCMD {

    private $git_path = null;
    private $branch = null;

    public function __construct($directory, $branch='master') {
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
            exit("An error occurred cloning the git repo. Exiting. Details:\n" . $output);
        }
    }

    public function checkout() {
        exec("cd $this->git_path; git checkout $this->branch");
    }

    public function pull() {
        $output = shell_exec("cd $this->git_path; git pull");
        if(is_null($output)) {
            exit("An error occurred fetching the git repo. Exiting. Details:\n" . $output);
        }
    }

    public function last_commit() {
        $output = shell_exec("cd $this->git_path; git log -n 1 --pretty=format:%h");
        return $output;
    }
}
?>

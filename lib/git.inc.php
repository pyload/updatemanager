<?php
class GitCMD {

    private $git_path;
    private $branch;
    private $l;

    public function __construct($l, $repo, $directory, $branch='master', $shallow=false) {
        $this->l = $l;
        $this->git_path = $directory;
        $this->repo = $repo;
        $this->branch = $branch;
        if(!file_exists($directory . '/.git')) {
            // There is no Git repo here! Cloning.
            if ($shallow)
                $this->shallow_clone();
            else
                $this->gitclone();
        }
        else {
            $this->checkout();
            $this->pull();
        }
    }

    public function gitclone() {
        exec("git clone -b $this->branch $this->repo $this->git_path", $output, $status);
        if($status) {
            $this->l->error("An error occurred cloning the git repo. Exiting.");
            exit(3);
        }
    }

    public function shallow_clone($depth=50) {
        exec("git clone -n --depth=$depth -b $this->branch $this->repo $this->git_path", $output, $status);
        if($status) {
            $this->l->error("An error occurred cloning the git repo. Exiting.");
            exit(3);
        }
    }

    public function checkout() {
        exec("cd $this->git_path; git checkout $this->branch");
    }

    public function push($remote="origin") {
        exec("cd $this->git_path; git push $remote $this->branch", $output, $status);
        if($status) {
            $this->l->error("An error occurred pushing to the git repo. Exiting.");
            exit(3);
        }
        return true;
    }

    public function pull($remote="origin") {
        exec("cd $this->git_path; git reset --hard $remote/$this->branch; git pull -s recursive -X theirs", $output, $status);
        if($status) {
            $this->l->error("An error occurred fetching the git repo. Exiting.");
            exit(0);
        }
    }

    public function set_ident($name, $email) {
        exec("cd $this->git_path; git config user.name \"$name\"; git config user.email \"$email\"", $output, $status);
        if($status) {
            $this->l->error("An error occurred committing to the git repo. Exiting.");
            exit(3);
        }
    }

    public function commit($msg="update") {
        exec("cd $this->git_path; git add .;git commit -m \"$msg\"", $output, $status);
        switch ($status) {
            case 0:
                return true;
            case 1:
                $this->l->info("No changes to commit.");
                return false;
            default:
                $this->l->error("An error occurred committing to the git repo. Exiting.");
                exit(3);
        }
    }

    public function last_commit() {
        exec("cd $this->git_path; git log -n 1 --pretty=format:%h", $output);
        return $output[0];
    }

    public function ls($commit, $path="") {
        exec("cd $this->git_path; git ls-tree --name-only -r $commit $path", $output);
        return $output;
    }

    public function dirty($remote="origin") {
        exec("cd $this->git_path; git add .; git diff --quiet --exit-code $remote/$this->branch", $output, $status);
        return $status == 1;
    }

    public function diff($commit1, $commit2) {
        $res = array();
        exec("cd $this->git_path; git diff-tree --no-commit-id --name-status -r $commit1 $commit2", $output);
        foreach($output as $file) {
            $e = explode("\t", $file);
            $res[$e[1]] = $e[0];
        }
        return $res;
    }
}
?>

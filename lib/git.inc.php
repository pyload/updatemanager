<?php
class GitCMD {

    private bool $windows_os;
    private string $git_path;
    private string $branch;
    private string $repo;
    private Logger $l;

    public function __construct($l, $repo, $directory, $branch='master', $shallow=false) {
        $this->windows_os = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $this->l = $l;
        $this->git_path = $directory;
        $this->repo = $repo;
        $this->branch = $branch;
        if (!$this->is_installed()) {
            $this->l->error("Cannot operate without git installed. Exiting.");
            exit(3);
        }
        $git_dir = $directory . '/.git';
        if (is_dir($directory)) {
            if (is_dir($git_dir)) {
                if ($this->get_upstream_url() != $this->repo) {
                    if ($this->windows_os) {
                        exec("rd /s /q \"$directory\"", $output, $status);
                    } else {
                        exec("rm -rf \"$directory\"", $output, $status);
                    }
                    $this->l->info("Removed local repo due to different upstream url");
                }
            } else {
                if ($this->windows_os) {
                    exec("rd /s /q \"$directory\"", $output, $status);
                } else {
                    exec("rm -rf \"$directory\"", $output, $status);
                }
                $this->l->info("Removed local repo due to nonexistent .git directory");
            }
        }
        clearstatcache(true, $git_dir);
        if (is_dir($git_dir)) {
            $this->checkout();
            $this->pull();
        } else {
            // There is no Git repo here! Cloning.
            if ($shallow)
                $this->shallow_clone();
            else
                $this->gitclone();
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
        if ($this->windows_os) {
            exec("cd $this->git_path & git checkout $this->branch");
        } else {
            exec("cd $this->git_path; git checkout $this->branch");
        }
    }

    public function push($remote="origin") {
        if ($this->windows_os) {
            exec("cd $this->git_path & git push $remote $this->branch", $output, $status);
        } else {
            exec("cd $this->git_path; git push $remote $this->branch", $output, $status);
        }
        if($status) {
            $this->l->error("An error occurred pushing to the git repo. Exiting.");
            exit(3);
        }
        return true;
    }

    public function pull($remote="origin") {
        if ($this->windows_os) {
            exec("cd $this->git_path & git reset --hard $remote/$this->branch & git pull --no-rebase -s recursive -X theirs", $output, $status);
        } else {
            exec("cd $this->git_path; git reset --hard $remote/$this->branch; git pull --no-rebase -s recursive -X theirs", $output, $status);
        }
        if($status) {
            $this->l->error("An error occurred fetching the git repo. Exiting.");
            exit(3);
        }
    }

    public function get_upstream_url($remote="origin"): string
    {
        if ($this->windows_os) {
            exec("cd $this->git_path & git config --get remote.$remote.url", $output, $status);
        } else {
            exec("cd $this->git_path; git config --get remote.$remote.url", $output, $status);
        }

        return $output[0];
    }
    public function is_installed(): bool
    {
        if ($this->windows_os) {
            exec(getenv("SystemRoot") . "\\System32\\where.exe /q git.exe", $output, $status);
        } else {
            exec("which git", $output, $status);
        }
        return $status == 0;
    }

    public function set_ident($name, $email) {
        if ($this->windows_os) {
            exec("cd $this->git_path & git config user.name \"$name\" & git config user.email \"$email\"", $output, $status);
        } else {
            exec("cd $this->git_path; git config user.name \"$name\"; git config user.email \"$email\"", $output, $status);
        }
        if($status) {
            $this->l->error("An error occurred committing to the git repo. Exiting.");
            exit(3);
        }
    }

    public function commit($msg="update") {
        if ($this->windows_os) {
            exec("cd $this->git_path & git add .;git commit -m \"$msg\"", $output, $status);
        } else {
            exec("cd $this->git_path; git add .;git commit -m \"$msg\"", $output, $status);
        }
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
        if ($this->windows_os) {
            exec("cd $this->git_path & git log -n 1 --pretty=format:%h", $output);
        } else {
            exec("cd $this->git_path; git log -n 1 --pretty=format:%h", $output);
        }
        return $output[0];
    }

    public function ls($commit, $path="") {
        if ($this->windows_os) {
            exec("cd $this->git_path & git ls-tree --name-only -r $commit $path", $output);
        } else {
            exec("cd $this->git_path; git ls-tree --name-only -r $commit $path", $output);
        }
        return $output;
    }

    public function dirty($remote="origin"): bool
    {
        if ($this->windows_os) {
            exec("cd $this->git_path & git add . & git diff --quiet --exit-code $remote/$this->branch", $output, $status);
        } else {
            exec("cd $this->git_path; git add .; git diff --quiet --exit-code $remote/$this->branch", $output, $status);
        }
        return $status == 1;
    }

    public function diff($commit1, $commit2): array
    {
        $res = array();
        if ($this->windows_os) {
            exec("cd $this->git_path & git diff-tree --no-commit-id --name-status -r $commit1 $commit2", $output);
        } else {
            exec("cd $this->git_path; git diff-tree --no-commit-id --name-status -r $commit1 $commit2", $output);
        }
        foreach($output as $file) {
            $e = explode("\t", $file);
            $res[$e[1]] = $e[0];
        }
        return $res;
    }
}

#!/bin/sh
rm -f composer.phar README.md plugins.sqlite blacklist.txt .travis.yml manual_release.sh
rm -rf vendor build tests data
git commit -a --amend --no-edit
git push origin master

# pyLoad Update Manager Server
[![Build Status](https://app.travis-ci.com/pyload/updatemanager.svg?branch=master)](https://travis-ci.org/pyload/updatemanager) [![Coverage Status](https://coveralls.io/repos/pyload/updatemanager/badge.svg?branch=master)](https://coveralls.io/r/pyload/updatemanager?branch=master) [![Dependency Status](https://www.versioneye.com/user/projects/537a851114c1583cca00004a/badge.svg)](https://www.versioneye.com/user/projects/537a851114c1583cca00004a)

This is the source code of the pyLoad Update Manager Server, **this software is for project administrators only and not for pyLoad's end users!**

## Blacklist
Every plugin listed in the blacklist will be removed in users installation.
<br>This is very useful when a plugin needs to be revoked after the update manager has already distributed it.
<br>To add a plugin to the blacklist just edit `blacklist.txt` in [pyload/updates](https://github.com/pyload/updates) repository and append the plugin name in format: `type|name.py`.
<br>Once pushed to the repository the CI system will take care of applying the change automatically.

Note: adding plugins to `blacklist.txt` is unnecessary anymore.
<br>This process supposed to be fully automated now from now on:
<br>This because every plugin deletion in the [stable](https://github.com/pyload/pyload/tree/stable) branch will cause the addition of it to the `blacklist.txt` file.


## Build and Deploy
Every commit will trigger a Travis CI build that will test the code.


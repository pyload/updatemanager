# pyLoad Update Manager Server
[![Build Status](https://travis-ci.org/stickell/updatemanager.svg?branch=master)](https://travis-ci.org/stickell/updatemanager) [![Coverage Status](https://coveralls.io/repos/stickell/updatemanager/badge.png?branch=master)](https://coveralls.io/r/stickell/updatemanager?branch=master) [![Dependency Status](https://www.versioneye.com/user/projects/5368f9e314c1582057000045/badge.png)](https://www.versioneye.com/user/projects/5368f9e314c1582057000045)

This is the source code of the pyLoad Update Manager Server, **this software is for project administrators only and not for pyLoad's end users!**

## Blacklist
Every plugin listed in the blacklist will be removed in users installation. This is very useful when a plugin needs to be revoked after the update manager has already distributed it.
To add a plugin to the blacklist just edit `blacklist.txt` and append the plugin name in format: `type|name.py`. Once pushed to the repository the CI system will take care of applying the change automatically.

## Build and Deploy
Every commit will trigger a Travis CI build that will test the code and, if there are no fails, deploy on the OpenShift platform.


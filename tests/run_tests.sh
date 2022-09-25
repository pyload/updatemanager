#!/bin/bash
# Execute from the project home directory
mkdir -p build/logs
export XDEBUG_MODE=coverage
vendor/bin/phpunit --debug -c tests/phpunit.xml
rm tests/*.db

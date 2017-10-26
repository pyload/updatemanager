#!/bin/bash
# Execute from the project home directory
mkdir -p build/logs
vendor/bin/phpunit -c tests/phpunit.xml
rm tests/*.db

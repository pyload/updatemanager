@rem Execute from the project home directory
@echo off
mkdir build\logs 2>nul
set XDEBUG_MODE=coverage
php\php.exe vendor\bin\phpunit --debug -c tests\phpunit.xml
del tests\*.db

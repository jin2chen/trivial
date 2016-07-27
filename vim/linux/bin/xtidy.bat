@echo off

rem ----------------------------------------------------
rem @author     mole<mole1230@gmail.com>
rem @version    $Id: xtidy.bat 287 2012-09-25 09:03:27Z mole1230 $
rem
rem ----------------------------------------------------

setlocal EnableDelayedExpansion
set CURRENT_DIR=%CD%
set ROOT_PATH=%~dp0
set PHP_BIN=php
set PHP_TIDY=%ROOT_PATH%tidy.php
set TIDY_BIN=%ROOT_PATH%etidy
set TIDY_CONFIG=%ROOT_PATH%tidy.conf

%PHP_BIN% -nv 1>NUL 2>NUL && (set PHP_EXIST=TRUE) || (set PHP_EXIST=FALSE)

set charset=%~2
set infile=%~f1
set suffix=%~x1
set filename=%~dpn1
set outfile=%filename%-tidy%suffix%
set logfile=%filename%-log

if "%suffix%" == ".html" (
	"%TIDY_BIN%" -config "%TIDY_CONFIG%" --char-encoding "%charset%"  -o "%outfile%" "%infile%"  2>"%logfile%"
	if "%PHP_EXIST%" == "TRUE" ("%PHP_BIN%" -n "%PHP_TIDY%" "%outfile%") 
)

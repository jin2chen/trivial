@echo off & title JSLint & color 0d

rem ----------------------------------------------------
rem JSLint  is a JavaScript program that looks for problems in JavaScript programs. 
rem It is a code quality tool.
rem
rem @author     mole<mole1230@gmail.com>
rem @version    $Id: i.bat 17 2010-10-20 07:28:42Z mole1230 $
rem @required
rem   jre http://www.oracle.com/technetwork/java/javase/downloads/index.html
rem   jar http://www.mozilla.org/rhino/download.html
rem
rem ----------------------------------------------------

setlocal EnableDelayedExpansion
set CURRENT_DIR=%CD%
set ROOT_PATH=%~dp0
set JAVA_BIN=java
set JAVA_EXIST=FALSE
set JS_JAR=%ROOT_PATH%lib\js.jar
set JS_EXIST=FALSE
set JSLINT=%ROOT_PATH%lib\rhino-jslint.js

cd %ROOT_PATH%
%JAVA_BIN% -version 1>NUL 2>java.tmp && find "1.6" java.tmp 1>NUL 2>NUL && set JAVA_EXIST=TRUE
if exist %JS_JAR% (set JS_EXIST=TRUE)
del /f *.tmp 1>NUL 2>NUL
if "%JAVA_EXIST%" == "FALSE" (
	echo ERROR: 
	echo java.exe is not found or version is less than 1.6
	echo Please install JRE. Downloading from 
	echo http://www.oracle.com/technetwork/java/javase/downloads/index.html
	cd %CURRENT_DIR%
	exit /b
)
if "%JS_EXIST%" == "FALSE" (
	echo ERROR:
	echo compiler.jar is not found
	echo Downloading from 
	echo http://www.mozilla.org/rhino/download.html
	echo unzip rhino*.zip and copy js.jar tolib\js.jar
	cd %CURRENT_DIR%
	exit /b
)
cd %CURRENT_DIR%

if "%1" == "" (echo usage: %~n0 filename & exit /b) 
set infile=%1
%JAVA_BIN% -jar %JS_JAR% %JSLINT% %infile%

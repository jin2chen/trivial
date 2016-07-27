@echo off & title Single JS Compiler & color 0d

rem ----------------------------------------------------
rem @author     mole<mole1230@gmail.com>
rem @version    $Id: s.bat 17 2010-10-20 07:28:42Z mole1230 $
rem @required
rem   jre http://www.oracle.com/technetwork/java/javase/downloads/index.html
rem   complier http://closure-compiler.googlecode.com/files/compiler-latest.zip
rem
rem Attention:  
rem   Charset of all input files is UTF-8
rem
rem Directory layout:
rem 
rem   E:\WWW\CODE\JS
rem   ©À©¤app
rem   ©¦      app.js
rem   ©¦      
rem   ©À©¤jquery
rem   ©¦      jquery-1.4.2.js
rem   ©¦      jquery-1.4.2.min.js
rem   ©¦      jquery.datepick.js
rem   ©¦      jquery.datepick.min.js
rem   ©¦      jquery.embed.js
rem   ©¦      jquery.embed.min.js
rem   ©¦      jquery.jeditable.js
rem   ©¦      jquery.jeditable.min.js
rem   ©¦      jquery.scrollto.js
rem   ©¦      jquery.scrollTo.min.js
rem   ©¦      jquery.select.js
rem   ©¦      jquery.select.min.js
rem   ©¦      jquery.validate.js
rem   ©¦      jquery.validate.min.js
rem   ©¦      
rem   ©À©¤phpjs
rem   ©¦      0.js
rem   ©¦      date.js
rem   ©¦      delcookie.js
rem   ©¦      getcookie.js
rem   ©¦      http_build_query.js
rem   ©¦      json_decode.js
rem   ©¦      json_encode.js
rem   ©¦      json_last_error.js
rem   ©¦      parse_str.js
rem   ©¦      setcookie.js
rem   ©¦      urldecode.js
rem   ©¦      urlencode.js
rem   ©¦      
rem   ©¸©¤sdk
rem       ©¦  c.bat
rem       ©¦  s.bat
rem       ©¦  
rem       ©¸©¤lib
rem               compiler.jar
rem               
rem      
rem ----------------------------------------------------

setlocal EnableDelayedExpansion
set CURRENT_DIR=%CD%
set ROOT_PATH=%~dp0
set JAVA_BIN=java
set JAVA_EXIST=FALSE
set COMPILER=%ROOT_PATH%lib\compiler.jar
set COMPILER_EXIST=FALSE

cd %ROOT_PATH%
%JAVA_BIN% -version 1>NUL 2>java.tmp && find "1.6" java.tmp 1>NUL 2>NUL && set JAVA_EXIST=TRUE
if exist %COMPILER% (set COMPILER_EXIST=TRUE)
del /f *.tmp 1>NUL 2>NUL
if "%JAVA_EXIST%" == "FALSE" (
	echo ERROR: 
	echo java.exe is not found or version is less than 1.6
	echo Please install JRE. Downloading from 
	echo http://www.oracle.com/technetwork/java/javase/downloads/index.html
	cd %CURRENT_DIR%
	exit /b
)
if "%COMPILER_EXIST%" == "FALSE" (
	echo ERROR:
	echo compiler.jar is not found
	echo Downloading from 
	echo http://closure-compiler.googlecode.com/files/compiler-latest.zip
	echo unzip compiler-latest.zip and rename lib\compiler.jar
	cd %CURRENT_DIR%
	exit /b
)
cd %CURRENT_DIR%

if "%1" == "" (echo usage: %~n0 filename & exit /b) 
set infile=%1
set outfile=%infile:.js=.min.js%

cd %ROOT_PATH%..\jquery
%JAVA_BIN% -jar %COMPILER% --compilation_level=SIMPLE_OPTIMIZATIONS --js=%infile% --js_output_file=%outfile%

cd %CURRENT_DIR%
echo SUCCESS
@echo off & title JS Compiler & color 0d

rem ----------------------------------------------------
rem @author     mole<mole1230@gmail.com>
rem @version    $Id: c.bat 9 2010-10-19 15:15:12Z mole1230 $
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

echo cd jquery directory, merge all .min.js files...
cd %ROOT_PATH%..\jquery
copy *.min.js /b ..\0.tmp.js /b > NUL
echo complete

echo cd phpjs directory, merge and compile all .js files...
cd %ROOT_PATH%..\phpjs
set COMMAND=
for %%x in (*.js) do (set COMMAND=!COMMAND! --js=%%x)
if not "%COMMAND%" == "" (
	%JAVA_BIN% -jar %COMPILER% --compilation_level=WHITESPACE_ONLY %COMMAND% --js_output_file=..\1.tmp.js
)
echo complete

echo cd app directory, merge and compile all .js files...
cd %ROOT_PATH%..\app
set COMMAND=
for %%x in (*.js) do (set COMMAND=!COMMAND! --js=%%x)
if not "%COMMAND%" == "" (
	%JAVA_BIN% -jar %COMPILER% --compilation_level=SIMPLE_OPTIMIZATIONS %COMMAND% --js_output_file=..\2.tmp.js
)
echo complete

echo generate lib.js...
cd %ROOT_PATH%..
copy *.tmp.js /b libjj.js /b > NUL
echo complete

echo clean all temp files...
del /f *.tmp.js
echo complete

cd %CURRENT_DIR%
echo SUCCESS
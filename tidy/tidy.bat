@echo off & title Tidy XHTML & color 0d

rem ----------------------------------------------------
rem @author     mole<mole1230@gmail.com>
rem @version    $Id: tidy.bat 79 2010-12-16 14:50:30Z mole1230 $
rem
rem usage:
rem   tidy dir
rem   tidy file.html
rem ----------------------------------------------------

setlocal EnableDelayedExpansion
set CURRENT_DIR=%CD%
set ROOT_PATH=%~dp0
set PHP_BIN=php -n
set PHP_TIDY=%ROOT_PATH%tidy.php
set TIDY_BIN=%ROOT_PATH%etidy
set TIDY_CONFIG=%ROOT_PATH%tidy.conf

%PHP_BIN% -nv 1>NUL 2>NUL && (set PHP_EXIST=TRUE) || (set PHP_EXIST=FALSE)

if "%1" == "" (echo usage: ^<filename or dir^> & exit /b)
set infile=%~f1
(dir /ad/x %infile% 1>NUL 2>NUL) && (set filetype=dir) || (set filetype=file)

if "%filetype%" == "file" (
	for %%x in ("%infile%") do (
		set suffix=%%~xx
		set filename=%%~dpnx
	)

	if "!suffix!" == ".html" (
		set outfile=!filename!-tidy.html
		%TIDY_BIN% -config %TIDY_CONFIG% -o !outfile! %infile%
		if "%PHP_EXIST%" == "TRUE" (%PHP_BIN% %PHP_TIDY% !outfile!)
	) else (
		echo ERROR only accept suffix .html
	)
) else (
	cd %infile%

	for %%x in (%infile%\*.html) do (
		set filename=%%~dpnx
		set tidyfile=!filename:~-5!
		if not "!tidyfile!" == "-tidy" (
			echo %%~fx
			set outfile=%%~dpnx-tidy.html
			%TIDY_BIN% -config %TIDY_CONFIG% -o !outfile! %%x
			if "%PHP_EXIST%" == "TRUE" (%PHP_BIN% %PHP_TIDY% !outfile!)
		)
	)

	cd %CURRENT_DIR%
)

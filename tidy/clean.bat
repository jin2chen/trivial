@echo off & title Clean file-tidy.html & color 0d

rem ----------------------------------------------------
rem @author     mole<mole1230@gmail.com>
rem @version    $Id: clean.bat 2 2010-10-18 16:21:56Z mole1230 $
rem
rem usage:
rem   renhtml dir
rem ----------------------------------------------------

setlocal EnableDelayedExpansion
set CURRENT_DIR=%CD%
set ROOT_PATH=%~dp0

if "%1" == "" (
	set infile=%CURRENT_DIR%
) else (
	set infile=%1
)

cd %infile%

for %%x in (*-tidy.html) do (
	set filename=%%x
	set outfile=!filename:-tidy.html=.html!
	del /f !outfile! 1>NUL 2>NUL
	ren !filename! !outfile!
	del /f !filename! 1>NUL 2>NUL
)

cd %CURRENT_DIR%

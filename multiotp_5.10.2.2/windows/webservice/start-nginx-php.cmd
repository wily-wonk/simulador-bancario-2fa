@ECHO OFF
REM ************************************************************
REM @file  start-nginx-php.cmd
REM @brief Script to run PHP-CGI and Nginx
REM
REM multiOTP - Strong two-factor authentication PHP class package
REM https://www\.multiOTP.net
REM 
REM Windows batch file for Windows Windows 7/8/8.1/10/11/2012(R2)/2016/2019/2022/2025
REM
REM @author    Andre Liechti, SysCo systemes de communication sa, <info@multiotp.net>
REM @version   5.10.2.2
REM @date      2026-04-03
REM @since     2013-08-09
REM @copyright (c) 2013-2026 SysCo systemes de communication sa
REM @copyright GNU Lesser General Public License
REM
REM
REM Description
REM
REM   start-nginx-php.cmd is a small script that will start
REM   PHP-CGI and Nginx.
REM   (http://nginx.org/en/)
REM
REM
REM Licence
REM
REM   Copyright (c) 2013-2026 SysCo systemes de communication sa
REM   SysCo (tm) is a trademark of SysCo systemes de communication sa
REM   (http://www.sysco.ch/)
REM   All rights reserved.
REM
REM   This file is part of the multiOTP project.
REM
REM
REM Change Log
REM
REM   2026-04-03 5.10.2.2 SysCo/al nginx updated to version 1.29.7
REM   (...) nginx regularly updated
REM   2017-01-10 5.0.3.3 SysCo/al Initial release
REM
REM ************************************************************

ECHO multiOTP Web Service started
ECHO.

SET _folder=%~d0%~p0

%~d0
CD %_folder%

IF NOT EXIST "%_folder%logs" MD "%_folder%logs"
IF NOT EXIST "%_folder%temp" MD "%_folder%temp"
IF NOT EXIST "%_folder%temp/client_body_temp" MD "%_folder%temp/client_body_temp"
IF NOT EXIST "%_folder%temp/fastcgi_temp" MD "%_folder%temp/fastcgi_temp"
IF NOT EXIST "%_folder%temp/proxy_temp" MD "%_folder%temp/proxy_temp"
IF NOT EXIST "%_folder%temp/scgi_temp" MD "%_folder%temp/scgi_temp"
IF NOT EXIST "%_folder%temp/uwsgi_temp" MD "%_folder%temp/uwsgi_temp"

START /b CMD /k ""%_folder%php\php-cgi.exe" -b 127.0.0.1:9000 -c "%_folder%php\php.ini""

REM START /b CMD /k "%_folder%nginx.exe"
REM START /b nginx.exe

"%_folder%nginx.exe"

REM taskkill /f /IM nginx.exe >NUL
REM taskkill /f /IM php-cgi.exe >NUL

REM "%_folder%nginx.exe" -s quit

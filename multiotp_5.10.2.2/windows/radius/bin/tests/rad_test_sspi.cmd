@echo off
verify on

SET IP_ADDR=127.0.0.1
SET IP_ADDR_V6=::1
SET RAD_DB=..\..\etc\raddb

echo **** WinRADIUS 2.2.6 Sanity Tests ****
echo.
echo These tests assume WinRADIUS Server is up and running (127.0.0.1:1812)
echo.

pause

..\radclient.exe -x -s -r 1 -d %RAD_DB% -f radclient-sspi.conf %IP_ADDR%:1812 auth testing123
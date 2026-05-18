@echo off
verify on

SET IP_ADDR=127.0.0.1
SET IP_ADDR_V6=::1
SET FR_PORT=1812
SET FR_SECRET=testing123

echo **** WinRADIUS 2.2.6 EAP tests ****
echo.
echo These tests assume WinRADIUS Server is up and running (127.0.0.1:1812)
echo.
echo Please, comment out 'smsotp' from the 'default' file under sites-enabled folder
echo.
echo The following tests will be performed:
echo.
echo      - EAP-MD5
echo.
echo      - EAP-MSCHAPv2
echo.
echo      - EAP-TLS
echo.
echo      - EAP-TTLS/CHAP
echo.
echo      - EAP-TTLS/MSCHAP
echo.
echo      - EAP-TTLS/MSCHAPv2
echo.
echo      - EAP-TTLS/PAP
echo.
echo      - EAP-TTLS/EAP-MD5
echo.
echo      - EAP-TTLS/EAP-GTC
echo.
echo      - EAP-TTLS/EAP-OTP (It may fail)
echo.
echo      - EAP-TTLS/EAP-MSCHAPv2
echo.
echo      - EAP-PEAPv0/MD5
echo.
echo      - EAP-PEAPv0/EAP-MSCHAPv2
echo.
echo      - EAP-PEAPv1/MD5 (It fails)
echo.
echo      - EAP-PEAPv1/EAP-MSCHAPv2 (It fails)
echo.
echo      - EAP2-MD5
echo.
echo      - EAP2-FAST
echo.
echo      - EAP2-IKEv2
echo.
echo      - EAP-GTC (Skipped)
echo.
echo      - EAP2-PWD
echo.
echo      - EAP2-EKE
echo.
echo      - EAP-PAX
echo.
echo      - EAP-SAKE
echo.
echo      - EAP2-GPSK
echo.
echo      - EAP-PSK
echo.
echo      - EAP-SIM
echo.
echo      - EAP-AKA
echo.
echo      - EAP-AKA (Prime)
echo.

echo.

echo Running test: EAP-MD5
pause

..\eapol_test.exe -n -a %IP_ADDR% -p %FR_PORT% -s %FR_SECRET% -c eap-md5.conf

echo.
echo Running test: EAP-MSCHAPv2
pause

..\eapol_test.exe -a %IP_ADDR% -p %FR_PORT% -s %FR_SECRET% -c eap-mschapv2.conf

echo.
echo Running test: EAP-TLS
pause

..\eapol_test.exe -a %IP_ADDR% -p %FR_PORT% -s %FR_SECRET% -c eap-tls.conf

echo.
echo Running test: EAP-GTC (Skipped)
pause

Rem ..\eapol_test.exe -a %IP_ADDR% -p %FR_PORT% -s %FR_SECRET% -c eap-gtc.conf

echo.
echo Running test: EAP-TTLS/CHAP
pause

..\eapol_test.exe -a %IP_ADDR% -p %FR_PORT% -s %FR_SECRET% -c eap-ttls-chap.conf

echo.
echo Running test: EAP-TTLS/MSCHAP
pause

..\eapol_test.exe -a %IP_ADDR% -p %FR_PORT% -s %FR_SECRET% -c eap-ttls-mschap.conf

echo.
echo Running test: EAP-TTLS/MSCHAPv2
pause

..\eapol_test.exe -a %IP_ADDR% -p %FR_PORT% -s %FR_SECRET% -c eap-ttls-mschapv2.conf

echo.
echo Running test: EAP-TTLS/PAP
pause

..\eapol_test.exe -a %IP_ADDR% -p %FR_PORT% -s %FR_SECRET% -c eap-ttls-pap.conf


echo.
echo Running test: EAP-TTLS/EAP-MD5
pause

..\eapol_test.exe -a %IP_ADDR% -p %FR_PORT% -s %FR_SECRET% -c eap-ttls-eap-md5.conf

echo.
echo Running test: EAP-TTLS/EAP-GTC
pause

..\eapol_test.exe -a %IP_ADDR% -p %FR_PORT% -s %FR_SECRET% -c eap-ttls-eap-gtc.conf


echo.
echo Running test: EAP-TTLS/EAP-OTP (It may fail)
pause

..\eapol_test.exe -a %IP_ADDR% -p %FR_PORT% -s %FR_SECRET% -c eap-ttls-eap-otp.conf

echo.
echo Running test: EAP-TTLS/EAP-MSCHAPv2
pause

..\eapol_test.exe -a %IP_ADDR% -p %FR_PORT% -s %FR_SECRET% -c eap-ttls-eap-mschapv2.conf

echo.
echo Running test: EAP-PEAPv0/MD5
pause

..\eapol_test.exe -a %IP_ADDR% -p %FR_PORT% -s %FR_SECRET% -c eap-peapv0-md5.conf


echo.
echo Running test: EAP-PEAPv0/EAP-MSCHAPv2
pause

..\eapol_test.exe -a %IP_ADDR% -p %FR_PORT% -s %FR_SECRET% -c eap-peapv0-eap-mschapv2.conf

echo.
echo Running test: EAP-PEAPv1/MD5
pause

..\eapol_test.exe -a %IP_ADDR% -p %FR_PORT% -s %FR_SECRET% -c eap-peapv1-md5.conf

echo.
echo Running test: EAP-PEAPv1/EAP-MSCHAPv2
pause

..\eapol_test.exe -a %IP_ADDR% -p %FR_PORT% -s %FR_SECRET% -c eap-peapv1-eap-mschapv2.conf

echo.
echo Running test: EAP-IKEv2 (it usually times out - please wait!)
pause

..\eapol_test.exe -a %IP_ADDR% -p %FR_PORT% -s %FR_SECRET% -c eap-ikev2.conf

echo.
echo Running test: EAP2-MD5
pause

..\eapol_test.exe -n -a %IP_ADDR% -p %FR_PORT% -s %FR_SECRET% -c eap2-md5.conf

echo.
echo Running test: EAP2-FAST
pause

..\eapol_test.exe -a %IP_ADDR% -p %FR_PORT% -s %FR_SECRET% -c eap-fast.conf

echo.
echo Running test: EAP2-PWD
pause

..\eapol_test.exe -a %IP_ADDR% -p %FR_PORT% -s %FR_SECRET% -c eap-pwd.conf

echo.
echo Running test: EAP2-GPSK
pause

..\eapol_test.exe -a %IP_ADDR% -p %FR_PORT% -s %FR_SECRET% -c eap-gpsk.conf

echo.
echo Running test: EAP2-IKEv2
pause

..\eapol_test.exe -a %IP_ADDR% -p %FR_PORT% -s %FR_SECRET% -c eap2-ikev2.conf

echo.
echo Running test: EAP2-EKE
pause

..\eapol_test.exe -a %IP_ADDR% -p %FR_PORT% -s %FR_SECRET% -c eap-eke.conf

echo.
echo Running test: EAP-PAX
pause

..\eapol_test.exe -a %IP_ADDR% -p %FR_PORT% -s %FR_SECRET% -c eap-pax.conf

echo.
echo Running test: EAP-SAKE
pause

..\eapol_test.exe -a %IP_ADDR% -p %FR_PORT% -s %FR_SECRET% -c eap-sake.conf

echo.
echo Running test: EAP-PSK
pause

..\eapol_test.exe -a %IP_ADDR% -p %FR_PORT% -s %FR_SECRET% -c eap-psk.conf

echo.
echo Running test: EAP-SIM
pause

..\eapol_test.exe -a %IP_ADDR% -p %FR_PORT% -s %FR_SECRET% -c eap-sim.conf

echo.
echo Running test: EAP-AKA
pause

..\eapol_test.exe -a %IP_ADDR% -p %FR_PORT% -s %FR_SECRET% -c eap-aka.conf

echo.
echo Running test: EAP-AKA (Prime)
pause

..\eapol_test.exe -a %IP_ADDR% -p %FR_PORT% -s %FR_SECRET% -c eap-aka-prime.conf

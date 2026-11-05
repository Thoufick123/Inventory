@echo off
title GRM Windows Local Print Helper Service (TSC TE244)
cd /d "%~dp0"
echo ===================================================
echo  Starting GRM Local Print Helper for TSC TE244...
echo ===================================================
node server.js
pause

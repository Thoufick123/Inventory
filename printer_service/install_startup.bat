@echo off
title Install GRM Print Helper to Windows Startup
set STARTUP_DIR=%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup
set TARGET_BAT=%~dp0start_helper.bat

echo Creating Windows Startup shortcut...
powershell -Command "$wsh = New-Object -ComObject WScript.Shell; $sc = $wsh.CreateShortcut('%STARTUP_DIR%\GRMPrintHelper.lnk'); $sc.TargetPath = '%TARGET_BAT%'; $sc.WorkingDirectory = '%~dp0'; $sc.WindowStyle = 7; $sc.Save()"

echo.
echo =========================================================
echo  SUCCESS! GRM Print Helper shortcut installed to Windows Startup.
echo  It will now start automatically whenever Windows boots.
echo =========================================================
pause

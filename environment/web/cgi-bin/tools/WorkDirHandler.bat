@echo off

if "%~1" == "" (
    echo === Argument type error ===
    goto L_error
)
if "%~2" == "" (
    echo === Argument dir error ===
    goto L_error
)

:: ------------------------------
:: ������
:: ------------------------------
if %1 == 1 (
    mkdir %2
    if errorlevel 1 echo make dir error. & goto L_error
) else if %1 == 2 (
    rd /s /q %2
    if errorlevel 1 echo remove dir error. & goto L_error
)

:: ------------------------------
:: �I������
:: ------------------------------
:: ����I��
:L_success
echo %0 success.
exit /b 0

:: �ُ�I��
:L_error
echo %0 error.
exit /b 1

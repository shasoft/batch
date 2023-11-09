@echo off

rem **********************************************************************************
rem Установить кодировку
rem UTF-8
chcp 65001

rem **********************************************************************************
rem Создать ссылку для запуска тестового сайта в Open Server
set pathSite="%HOME%/domains/shasoft-test.ru"
if defined HOME (
rem Удалить
rd %pathSite%
rem Создать
mklink /D %pathSite% "%~dp0test-site"
)

rem **********************************************************************************
set phpunit=%~dp0../vendor/bin/phpunit
rem Выполнить тесты
php %phpunit%
rem php %~dp0../vendor/bin/phpunit --filter testAllEmpty1
rem php %~dp0../vendor/bin/phpunit --filter priority1
if %errorlevel% neq 0 exit /b %errorlevel%
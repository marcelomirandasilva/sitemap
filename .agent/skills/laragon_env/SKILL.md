# Laragon Environment Configuration

## System Path
Base Directory: C:\laragon

## Git & Shell Configuration
- Git Bin Path: C:\laragon\bin\git\bin (bash.exe, sh.exe)
- Git Cmd Path: C:\laragon\bin\git\cmd (git.exe)
- Bash Executable: C:\laragon\bin\git\bin\bash.exe
- Git Executable: C:\laragon\bin\git\cmd\git.exe

## MySQL Configuration
- Version: MySQL 8.4.3
- Bin Path: C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin
- Executable: C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe
- Dump Executable: C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqldump.exe

## PHP Configuration
- Version: PHP 8.4.2 (NTS x64)
- Bin Path: C:\laragon\bin\php\php-8.4.2-nts-Win32-vs17-x64
- Executable: C:\laragon\bin\php\php-8.4.2-nts-Win32-vs17-x64\php.exe

## Node.js Configuration
- Version: Node v22
- Bin Path: C:\laragon\bin\nodejs\node-v22
- Executable: C:\laragon\bin\nodejs\node-v22\node.exe

## Composer Configuration
- Path: C:\laragon\bin\composer
- Executable: C:\laragon\bin\composer\composer.phar

## Terminal Setup (Copy & Paste)

### Windows CMD:
set PATH=%PATH%;C:\laragon\bin\git\bin;C:\laragon\bin\git\cmd;C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin;C:\laragon\bin\php\php-8.4.2-nts-Win32-vs17-x64;C:\laragon\bin\nodejs\node-v22;C:\laragon\bin\composer

### Windows PowerShell:
$env:Path += ";C:\laragon\bin\git\bin;C:\laragon\bin\git\cmd;C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin;C:\laragon\bin\php\php-8.4.2-nts-Win32-vs17-x64;C:\laragon\bin\nodejs\node-v22;C:\laragon\bin\composer"

### Git Bash:
export PATH=$PATH:/c/laragon/bin/git/bin:/c/laragon/bin/git/cmd:/c/laragon/bin/mysql/mysql-8.4.3-winx64/bin:/c/laragon/bin/php/php-8.4.2-nts-Win32-vs17-x64:/c/laragon/bin/nodejs/node-v22:/c/laragon/bin/composer

## Laravel Integration (config/database.php)
'connections' => [
    'mysql' => [
        // ...
        'dump' => [
           'dump_binary_path' => 'C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin',
        ]
    ],
],
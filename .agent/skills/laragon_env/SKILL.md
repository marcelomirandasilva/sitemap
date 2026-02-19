# Laragon Environment

The user is running Laragon on the **D:** drive (confirmed via file application).

## MySQL Path
**Version:** MySQL 8.4.3
**Bin Path:** `D:\bin\mysql\mysql-8.4.3-winx64\bin`
**Executable:** `D:\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe`
**Dump Executable:** `D:\bin\mysql\mysql-8.4.3-winx64\bin\mysqldump.exe`

## PHP Path
**Version:** PHP 8.4.3
**Bin Path:** `D:\bin\php\php-8.4.3-nts-Win32-vs17-x64`
**Executable:** `D:\bin\php\php-8.4.3-nts-Win32-vs17-x64\php.exe`

## Usage Guidelines
1. **Laravel Config:** Prefer configuring `config/database.php` with `dump_binary_path` instead of relying on system PATH.
2. **Terminal Commands:** If running raw commands, you may need to prepend the path:
   * **CMD:** `set PATH=%PATH%;D:\bin\mysql\mysql-8.4.3-winx64\bin;D:\bin\php\php-8.4.3-nts-Win32-vs17-x64`
   * **Git Bash:** `export PATH=$PATH:/d/bin/mysql/mysql-8.4.3-winx64/bin:/d/bin/php/php-8.4.3-nts-Win32-vs17-x64`


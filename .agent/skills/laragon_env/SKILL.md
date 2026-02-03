---
name: Laragon Environment Configuration
description: Contains specific paths and configurations for the user's Laragon setup on Windows.
---

# Laragon Environment

The user is running Laragon on the **F:** drive.

## MySQL Path
**Version:** MySQL 8.4.3
**Bin Path:** `F:\laragon\bin\mysql\mysql-8.4.3-winx64\bin`
**Executable:** `F:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe`
**Dump Executable:** `F:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqldump.exe`

## Usage Guidelines
1. **Laravel Config:** Prefer configuring `config/database.php` with `dump_binary_path` instead of relying on system PATH.
2. **Terminal Commands:** If running raw commands, you may need to prepend the path:
   * **CMD:** `set PATH=%PATH%;F:\laragon\bin\mysql\mysql-8.4.3-winx64\bin`
   * **PowerShell:** `$env:PATH += ";F:\laragon\bin\mysql\mysql-8.4.3-winx64\bin"`
   * **Git Bash:** `export PATH=$PATH:/f/laragon/bin/mysql/mysql-8.4.3-winx64/bin`

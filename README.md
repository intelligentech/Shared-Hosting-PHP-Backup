# 🔐 Enterprise-Grade PHP Backup Automation

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.4-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Maintenance](https://img.shields.io/badge/Maintained%3F-yes-brightgreen.svg)](https://github.com/intelligentech/Shared-Hosting-PHP-Backup/graphs/commit-activity)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](http://makeapullrequest.com)

> **Production-ready, zero-dependency backup solution for shared hosting environments**

A sophisticated yet simple-to-deploy PHP backup automation system designed specifically for shared hosting environments where shell access is limited or unavailable. Perfect for WordPress, Laravel, custom PHP applications, and any web project requiring reliable automated backups.

## 📋 Table of Contents

- [Features](#-features)
- [Requirements](#-requirements)
- [Quick Start Guide](#-quick-start-guide)
- [Configuration Details](#-configuration-details)
- [How It Works](#-how-it-works)
- [Usage Examples](#-usage-examples)
- [Monitoring & Maintenance](#-monitoring--maintenance)
- [Troubleshooting](#-troubleshooting)
- [Security Best Practices](#-security-best-practices)
- [Advanced Configuration](#-advanced-configuration)
- [Contributing](#-contributing)
- [License](#-license)
- [Support](#-support)

---

## ✨ Features

### Core Capabilities

| Feature | Description |
|---------|-------------|
| 🔍 **Automatic Database Discovery** | No manual database configuration—discovers and backs up all accessible MySQL databases |
| 🗜️ **Intelligent Compression** | Applies gzip to database dumps >20MB; configurable ZIP compression levels |
| 🌐 **Dual Execution Modes** | Run via web browser (token-protected) or cron automation |
| ☁️ **Remote FTP Storage** | Automated upload to remote FTP/FTPS servers with retry logic |
| 🔄 **Automated Retention Management** | Configurable retention policies for both local and remote backups |
| 📧 **Email Notifications** | Detailed HTML/plain-text success and failure reports |
| 🔒 **Concurrent Execution Prevention** | Mutex lock system prevents overlapping backups |
| 💾 **Memory-Efficient Processing** | Streams large files to minimize memory footprint |
| 🎯 **Selective File Exclusions** | Regex-based pattern matching to exclude cache, logs, temp files |
| 📊 **Comprehensive Logging** | Detailed logs with timestamps, levels, and operation tracking |
| ✅ **ZIP Integrity Verification** | Validates archive integrity after creation |
| 🛡️ **NUL Byte Handling** | Safe export of binary data in database fields |

### What Makes This Different?

✅ **No shell access required** - Pure PHP implementation  
✅ **No external dependencies** - Uses only built-in PHP extensions  
✅ **Works on shared hosting** - Tested with cPanel, Plesk, DirectAdmin  
✅ **Set and forget** - Once configured, runs completely automated  
✅ **Production-tested** - Battle-tested in real-world hosting environments  
✅ **Security-first design** - Token authentication, path traversal protection, sanitized inputs

---

## 📦 Requirements

### Minimum Requirements

| Component | Requirement | Notes |
|-----------|-------------|-------|
| **PHP Version** | 7.4+ | PHP 8.0, 8.1, 8.2, 8.3 fully supported |
| **PHP Extensions** | `mysqli`, `zip`, `ftp`, `zlib` | Usually enabled by default on shared hosting |
| **MySQL/MariaDB** | 5.6+ / 10.2+ | Any version supporting `utf8mb4` |
| **FTP Account** | FTP or FTPS | For remote backup storage (required) |
| **Disk Space** | Varies | 2-3x the size of your website + databases |
| **Memory Limit** | 256MB+ | 512MB recommended for large sites |
| **Execution Time** | 300s+ | 900s (15 min) recommended for large backups |
| **File Permissions** | Read/Write | On backup and temp directories |

### Checking Your Environment

Create a `phpinfo.php` file to verify your environment:

```php
<?php
phpinfo();
```

Upload to your server and access via browser. Check for:
- PHP version in the header
- Extensions section (verify `mysqli`, `zip`, `ftp`, `zlib`)
- Core section (check `memory_limit`, `max_execution_time`)

**Remember to delete `phpinfo.php` after checking!**

### Common Hosting Environments

| Provider Type | Typical Configuration | Notes |
|---------------|----------------------|--------|
| **Shared Hosting** | PHP 7.4-8.2, 256-512MB RAM | Perfect for this script |
| **cPanel Hosting** | Full compatibility | Use cPanel cron jobs |
| **Managed WordPress** | May have restrictions | Check with provider |
| **VPS/Cloud** | Full compatibility | Can increase limits as needed |
| **Plesk** | Full compatibility | Use scheduled tasks |

---

## 🚀 Quick Start Guide

### Step 1: Download the Script

**Option A: Clone the Repository**
```bash
git clone https://github.com/intelligentech/Shared-Hosting-PHP-Backup.git
cd Shared-Hosting-PHP-Backup
```

**Option B: Download ZIP**
1. Visit [Releases](https://github.com/intelligentech/Shared-Hosting-PHP-Backup/releases)
2. Download the latest release
3. Extract to your local machine

### Step 2: Upload Files to Server

Upload these files to your server:

```
/home/username/scripts/
├── backup.php      # Main backup script
└── .htaccess       # Security configuration (optional but recommended)
```

**📍 Important Path Recommendations:**

| Location Type | Path | Security Level |
|---------------|------|----------------|
| ✅ **Best** | `/home/username/scripts/` | Outside web root |
| ⚠️ **Acceptable** | `/home/username/private/` | Outside web root |
| ❌ **Avoid** | `/home/username/public_html/` | Inside web root (risky) |

### Step 3: Create Required Directories

**Via SSH:**
```bash
mkdir -p /home/username/backups
mkdir -p /home/username/tmp
chmod 700 /home/username/backups
chmod 700 /home/username/tmp
```

**Via cPanel File Manager:**
1. Navigate to `/home/username/`
2. Click "+ Folder" → Create `backups`
3. Click "+ Folder" → Create `tmp`
4. Right-click each folder → "Change Permissions" → Set to `700`

**Via FTP Client:**
1. Connect to your server
2. Navigate to `/home/username/`
3. Create `backups` and `tmp` folders
4. Set permissions to `700` (drwx------)

### Step 4: Set File Permissions

**Via SSH:**
```bash
chmod 600 /home/username/scripts/backup.php
chmod 644 /home/username/scripts/.htaccess
```

**Via cPanel:**
1. Navigate to `/home/username/scripts/`
2. Right-click `backup.php` → Change Permissions → `600`
3. Right-click `.htaccess` → Change Permissions → `644`

**Permission Breakdown:**
- `600` (rw-------) = Owner can read/write, no one else can access
- `700` (rwx------) = Owner full access, no one else can access
- `644` (rw-r--r--) = Owner read/write, others read-only

### Step 5: Configure the Script

Open `backup.php` in a text editor and locate the configuration section (around line 117).

**🔧 Essential Settings to Change:**

```php
$config = [
    // ========== DATABASE SETTINGS ==========
    'db_host' => 'localhost',                    // Usually 'localhost' or '127.0.0.1'
    'db_user' => 'your_mysql_username',          // ⚠️ REQUIRED: Your MySQL username
    'db_pass' => 'your_mysql_password',          // ⚠️ REQUIRED: Your MySQL password
    
    // ========== FTP SETTINGS ==========
    'ftp_host' => 'backup-server.example.com',   // ⚠️ REQUIRED: Your FTP server
    'ftp_user' => 'ftp_username',                // ⚠️ REQUIRED: Your FTP username
    'ftp_pass' => 'ftp_password',                // ⚠️ REQUIRED: Your FTP password
    'ftp_remote_dir' => '/backups/',             // Remote directory (ensure exists)
    'ftp_use_ssl' => false,                      // true for FTPS (port 990)
    'ftp_passive' => true,                       // Usually true for shared hosting
    'ftp_port' => 21,                            // 21 for FTP, 990 for FTPS
    
    // ========== FILE PATHS (ABSOLUTE) ==========
    'backup_source' => '/home/username/public_html',     // ⚠️ REQUIRED: What to backup
    'local_backup_dir' => '/home/username/backups',      // ⚠️ REQUIRED: Where to store locally
    'temp_dir' => '/home/username/tmp',                  // ⚠️ REQUIRED: Temp directory
    
    // ========== EMAIL NOTIFICATIONS ==========
    'notify_email' => 'your@email.com',          // ⚠️ REQUIRED: Your email
    'email_from' => 'backup@yourdomain.com',     // Sender email address
    'email_on_success' => true,                  // Send email on success
    'email_on_failure' => true,                  // Send email on failure (recommended!)
    
    // ========== SECURITY ==========
    'web_access_token' => 'CHANGE_THIS_TO_RANDOM_32_CHAR_STRING',  // ⚠️ REQUIRED!
];
```

**💡 Pro Tip:** Generate a secure token:
```bash
# On Linux/Mac:
openssl rand -hex 32

# Or use online generator:
# https://www.random.org/strings/
```

### Step 6: Test Manual Execution

**Test via Web Browser:**

1. Navigate to: `https://yourdomain.com/scripts/backup.php?token=YOUR_SECRET_TOKEN`
2. You should see real-time progress updates
3. Wait for "Backup completed successfully" message
4. Check your email for the success notification

**Expected Output:**
```
[2024-11-03 02:00:01] [INFO] Backup started
[2024-11-03 02:00:02] [INFO] Connected to MySQL server successfully
[2024-11-03 02:00:02] [INFO] Discovered 3 database(s): mysite_db, mysite_shop, mysite_blog
[2024-11-03 02:00:05] [INFO] Exporting database: mysite_db
[2024-11-03 02:00:15] [INFO] Creating backup archive...
[2024-11-03 02:02:30] [INFO] Connecting to FTP server: backup.example.com
[2024-11-03 02:03:45] [INFO] FTP upload completed
[2024-11-03 02:03:46] [INFO] Backup completed successfully
```

**Troubleshooting First Run:**
- ❌ **"Invalid token"** → Check your `web_access_token` matches the URL
- ❌ **"Database connection failed"** → Verify MySQL credentials
- ❌ **"FTP connection failed"** → Check FTP settings and firewall
- ❌ **"Permission denied"** → Review directory permissions (Step 3-4)

### Step 7: Setup Automated Execution (Cron)

**Via cPanel:**
1. Log into cPanel
2. Search for "Cron Jobs" → Click it
3. Scroll to "Add New Cron Job"
4. Select schedule:
   - **Common Settings:** Once Per Day (0 2 * * *)
   - Or use "Advanced" for custom schedule
5. **Command:** `/usr/bin/php /home/username/scripts/backup.php`
6. Click "Add New Cron Job"

**Via SSH (crontab):**
```bash
crontab -e
```

Add one of these lines:

```cron
# Daily at 2:00 AM (recommended for most sites)
0 2 * * * /usr/bin/php /home/username/scripts/backup.php >/dev/null 2>&1

# Daily at 3:00 AM with log output
0 3 * * * /usr/bin/php /home/username/scripts/backup.php >> /home/username/cron.log 2>&1

# Every 12 hours (for high-activity sites)
0 */12 * * * /usr/bin/php /home/username/scripts/backup.php >/dev/null 2>&1

# Weekly on Sunday at 3:00 AM
0 3 * * 0 /usr/bin/php /home/username/scripts/backup.php >/dev/null 2>&1

# Twice daily (2 AM and 2 PM)
0 2,14 * * * /usr/bin/php /home/username/scripts/backup.php >/dev/null 2>&1
```

**Cron Schedule Format:**
```
* * * * * command
│ │ │ │ │
│ │ │ │ └─── Day of week (0-7, Sunday = 0 or 7)
│ │ │ └───── Month (1-12)
│ │ └─────── Day of month (1-31)
│ └───────── Hour (0-23)
└─────────── Minute (0-59)
```

**Testing Your Cron Job:**
1. Set a test cron for 2 minutes from now
2. Wait and check your email for notification
3. Verify backup file exists in `/home/username/backups/`
4. Update to your desired schedule

**📚 Recommended Schedules:**

| Site Type | Frequency | Cron Expression | Rationale |
|-----------|-----------|-----------------|------------|
| **Small Blog** | Daily 2 AM | `0 2 * * *` | Low-traffic, daily changes |
| **Business Site** | Daily 3 AM | `0 3 * * *` | Regular updates, customer data |
| **E-commerce** | Every 12h | `0 */12 * * *` | Frequent orders, inventory |
| **High-Traffic** | Every 6h | `0 */6 * * *` | Critical data, frequent updates |
| **Development** | Weekly | `0 3 * * 0` | Testing environment |

✅ **Congratulations!** Your backup system is now fully automated.

---

## ⚙️ Configuration Details

### Database Settings

The script **automatically discovers** all databases accessible to the configured MySQL user. No need to list database names.

```php
'db_host' => 'localhost'  // Usually 'localhost' or '127.0.0.1'
'db_user' => 'username'   // MySQL user with access to all databases
'db_pass' => 'password'   // MySQL password
```

### FTP Settings

```php
'ftp_host' => 'ftp.example.com'  // FTP server hostname/IP
'ftp_user' => 'username'         // FTP username
'ftp_pass' => 'password'         // FTP password
'ftp_remote_dir' => '/backups/'  // Remote directory path
'ftp_use_ssl' => false           // true for FTPS, false for standard FTP
'ftp_passive' => true            // Usually true for shared hosting
'ftp_port' => 21                 // 21 for FTP, 990 for FTPS
```

### Retention Policies

```php
'remote_retention_count' => 14  // Keep 14 most recent backups on FTP
'local_retention_count' => 5    // Keep 5 most recent backups locally
```

### Compression Settings

```php
'compression_level' => 6         // ZIP compression 1-9 (6 = balanced)
'gzip_threshold_mb' => 20        // Gzip DB dumps larger than this
```

**How it works:**
- Database dumps < 20MB → Saved as `.sql`
- Database dumps ≥ 20MB → Saved as `.sql.gz` (gzip compressed)
- Final ZIP uses level 6 compression (fast, good ratio)

### Exclusion Patterns

Files/directories matching these regex patterns are excluded from backup:

```php
'exclude_patterns' => [
    '#/cache/#i',           // Cache directories
    '#/tmp/#i',             // Temp directories
    '#/logs/#i',            // Log files
    '#/sessions/#i',        // Session files
    '#/\.git/#i',           // Git repositories
    '#/node_modules/#i',    // Node modules
    '#/\.svn/#i',           // SVN repositories
    '#/\.DS_Store$#i',      // macOS metadata
    '#/Thumbs\.db$#i',      // Windows thumbnails
    // Add custom patterns:
    '#/uploads/temp/#i',    // Example: exclude temp uploads
    '#\.bak$#i',            // Example: exclude .bak files
    '#/wp-content/cache/#i', // WordPress cache
]
```

**Understanding Regex Patterns:**
- `#` = Pattern delimiter
- `/` = Directory separator
- `$` = End of string
- `i` = Case-insensitive
- `\.` = Literal dot (escaped)
- `\d{4}` = Four digits

**Common Exclusion Examples:**

| Pattern | What It Excludes | Use Case |
|---------|------------------|----------|
| `'#/cache/#i'` | Any path containing `/cache/` | Cache directories |
| `'#\.log$#i'` | Files ending in `.log` | Log files |
| `'#/backup/#i'` | Paths with `/backup/` | Old backup folders |
| `'#\.mp4$#i'` | Video files | Large media files |
| `'#/wp-content/uploads/\d{4}/#i'` | WP uploads by year | Old uploaded files |

### Performance Settings

```php
'max_execution_time' => 900       // 15 minutes (adjust based on site size)
'memory_limit' => '512M'          // 512MB (increase for large sites)
'timeout_warning_seconds' => 840  // Warn at 14 minutes
```

**Sizing Guidelines:**

| Site Size | Files | Databases | Execution Time | Memory Limit |
|-----------|-------|-----------|----------------|---------------|
| **Small** | <1GB | <100MB | 300s (5 min) | 256M |
| **Medium** | 1-5GB | 100-500MB | 600s (10 min) | 512M |
| **Large** | 5-10GB | 500MB-1GB | 900s (15 min) | 1024M |
| **Enterprise** | >10GB | >1GB | 1800s (30 min) | 2048M |

---

## 🔧 How It Works

### Execution Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│  1. INITIALIZATION                                          │
│     • Check for existing lock file                          │
│     • Create new lock file with PID & timestamp             │
│     • Initialize logging                                    │
│     • Validate configuration                                │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  2. DATABASE BACKUP                                         │
│     • Connect to MySQL server                               │
│     • Run SHOW DATABASES (auto-discovery)                   │
│     • Filter out system databases                           │
│     • For each database:                                    │
│       - Export all tables with CREATE and INSERT statements │
│       - Apply gzip if size > threshold                      │
│       - Save to temp directory                              │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  3. FILE SYSTEM BACKUP                                      │
│     • Scan backup_source directory recursively              │
│     • Apply exclusion patterns (regex matching)             │
│     • Create ZIP archive with compression level 6           │
│     • Add database dumps to /databases/ folder in ZIP       │
│     • Add website files to ZIP with directory structure     │
│     • Verify ZIP integrity                                  │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  4. FTP UPLOAD                                              │
│     • Connect to FTP server (with SSL if configured)        │
│     • Set passive mode                                      │
│     • Upload ZIP file in binary mode                        │
│     • Verify upload completed successfully                  │
│     • Upload log file (if enabled)                          │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  5. RETENTION CLEANUP                                       │
│     • List all backup files on FTP server                   │
│     • Sort by modification time (newest first)              │
│     • Delete files beyond retention count                   │
│     • Repeat for local backup directory                     │
└─────────────────────────────────────────────────────────────┘
                          ↓
┌─────────────────────────────────────────────────────────────┐
│  6. FINALIZATION                                            │
│     • Calculate total execution time                        │
│     • Send email notification (success/failure)             │
│     • Clean up temp files                                   │
│     • Remove lock file                                      │
│     • Exit with status code                                 │
└─────────────────────────────────────────────────────────────┘
```

### Key Technical Features

#### 🔍 Automatic Database Discovery

**How it works:**
1. Connects to MySQL server using configured credentials
2. Executes `SHOW DATABASES` query
3. Filters out system databases:
   - `information_schema`
   - `performance_schema`
   - `mysql`
   - `sys`
4. Exports each user database individually
5. Handles special characters in table/column names
6. Exports with `utf8mb4` charset support

**Benefits:**
- ✅ No manual database list maintenance
- ✅ Automatically backs up new databases
- ✅ Works with shared hosting multi-database setups

#### 🗜️ Intelligent Compression Strategy

```
┌──────────────────┬─────────────────────┬──────────────────┐
│  Database Size   │   Storage Format    │  Compression     │
├──────────────────┼─────────────────────┼──────────────────┤
│  < 20MB          │  .sql               │  None (faster)   │
│  ≥ 20MB          │  .sql.gz            │  Gzip level 9    │
│  Final Archive   │  .zip               │  ZIP level 6     │
└──────────────────┴─────────────────────┴──────────────────┘
```

**Why this approach?**
- Small databases: Faster processing, minimal size benefit
- Large databases: Significant space savings (typically 70-90% reduction)
- ZIP level 6: Balance between speed and compression ratio

**Example Compression Results:**
```
Original database: 150MB
Gzipped:           18MB (88% reduction)
Final ZIP:         16MB (additional 11% reduction)
Total reduction:   89.3%
```

#### 🔄 Retention Management

**Remote (FTP) Retention:**
- Default: Keep 14 most recent backups
- Scans remote directory after upload
- Sorts by modification time
- Deletes oldest files beyond retention count
- Logs all deletions for audit trail

**Local Retention:**
- Default: Keep 5 most recent backups
- Executed after FTP upload completes
- Preserves disk space on web server
- Failed uploads kept separately for recovery

**Storage Calculation Example:**
```
Backup size: 500MB
Remote retention (14): 7GB FTP storage needed
Local retention (5):   2.5GB local disk needed
```

#### 🔒 Concurrent Execution Prevention

**Lock File Mechanism:**
```json
{
  "pid": 12345,
  "timestamp": 1698984000,
  "started": "2024-11-03 02:00:00"
}
```

**How it prevents overlaps:**
1. Check if `backup.lock` exists
2. If exists, read timestamp
3. If age < `max_execution_time`, abort (already running)
4. If age ≥ `max_execution_time`, remove stale lock and proceed
5. Create new lock at start
6. Remove lock on completion or error

#### 📧 Email Notification System

**Success Email Contains:**
- ✅ Execution duration
- ✅ Number of databases backed up with sizes
- ✅ Total files archived
- ✅ Compression statistics
- ✅ FTP upload confirmation
- ✅ Retention cleanup summary
- ✅ Next scheduled backup (if cron)

**Failure Email Contains:**
- ❌ Exact error message and stack trace
- ❌ Component that failed (DB/ZIP/FTP)
- ❌ Full log file contents
- ❌ System information (PHP version, memory usage)
- ❌ Troubleshooting suggestions
- ❌ Support contact information

**Email Format:**
- Multipart MIME (HTML + plain text)
- HTML version: Formatted with colors, tables
- Plain text version: Fallback for text-only clients

---

## 💻 Usage Examples

---

## Troubleshooting

### "Maximum execution time exceeded"

**Solutions:**
1. Increase `max_execution_time` in config (default: 900s)
2. Reduce backup scope with exclusion patterns
3. Contact hosting provider to increase PHP time limit

### "Allowed memory size exhausted"

**Solutions:**
1. Increase `memory_limit` in config (default: 512M)
2. Check for extremely large files in backup source
3. Add large files to exclusion patterns
4. Contact hosting to increase PHP memory limit

### "FTP connection failed"

**Check:**
- ✓ FTP credentials are correct
- ✓ FTP server is accessible
- ✓ Hosting firewall allows outbound FTP
- ✓ Try toggling `ftp_use_ssl` setting
- ✓ Verify `ftp_passive` is set to `true`
- ✓ Check FTP port (21 for FTP, 990 for FTPS)

### "Database access denied"

**Check:**
- ✓ MySQL credentials are correct
- ✓ User has SELECT privileges on all databases
- ✓ Host is correct (localhost vs 127.0.0.1)
- ✓ MySQL server is running

### "ZIP integrity check failed"

**Causes:**
- Disk space exhausted during creation
- File system corruption
- Insufficient permissions

**Solutions:**
1. Check available disk space
2. Verify directory permissions
3. Try creating backup manually to test

### "Lock file exists - backup already running"

**Normal:** Another backup is executing
**Stale:** Previous backup crashed

**Solution:**
- Wait 15 minutes for auto-expiration, OR
- Manually delete `/path/to/backups/backup.lock`

---

## Security Best Practices

### 1. File Permissions
```bash
chmod 600 backup.php      # Owner read/write only
chmod 700 backups/        # Owner full access only
chmod 700 tmp/            # Owner full access only
```

### 2. Directory Location
Place script **outside** `public_html`:
```
✓ Good: /home/username/scripts/backup.php
✗ Bad:  /home/username/public_html/backup.php
```

### 3. Web Access Token
Use strong, random token (32+ characters):
```php
'web_access_token' => 'k8Jh3nP9xQ2mF7vL4wR6tY1sN5gD0cB8'
```

Generate with:
```bash
openssl rand -hex 32
```

### 4. .htaccess Protection
The included `.htaccess` file:
- Denies direct file access
- Allows only from localhost
- Protects sensitive files (.lock, .log, .sql)

### 5. FTP Security
Enable FTPS when possible:
```php
'ftp_use_ssl' => true
'ftp_port' => 990
```

### 6. Environment Variables
For enhanced security, use environment variables:
```php
$config['db_pass'] = getenv('DB_PASSWORD') ?: 'fallback_password';
```

### Manual Backup (Web Browser)

Perfect for:
- Testing the configuration
- One-time backups before major changes
- Immediate backup needs

**URL Format:**
```
https://yourdomain.com/path/to/backup.php?token=YOUR_SECRET_TOKEN
```

**Real Examples:**
```
https://example.com/scripts/backup.php?token=k8Jh3nP9xQ2mF7vL4wR6tY1sN5gD0cB8
https://mysite.com/private/backup.php?token=abc123def456ghi789jkl
```

**What You'll See:**
```
[2024-11-03 14:30:01] [INFO] Backup started
[2024-11-03 14:30:01] [INFO] Lock file created
[2024-11-03 14:30:02] [INFO] Connected to MySQL successfully
[2024-11-03 14:30:02] [INFO] Discovered 3 database(s)
[2024-11-03 14:30:15] [INFO] Database export completed
[2024-11-03 14:32:45] [INFO] ZIP archive created: 523MB
[2024-11-03 14:35:20] [INFO] FTP upload completed
[2024-11-03 14:35:21] [INFO] Backup completed successfully in 5 minutes 20 seconds
```

### Automated Backup (Cron)

Perfect for:
- Daily/weekly scheduled backups
- Hands-off operation
- Production environments

**Setup:**
```bash
# Add to crontab
crontab -e

# Daily at 2 AM
0 2 * * * /usr/bin/php /home/username/scripts/backup.php
```

**Monitoring Cron Execution:**
```bash
# View cron logs (cPanel)
tail -f /var/log/cron

# View your cron jobs
crontab -l

# Check if backup ran
ls -lt /home/username/backups/ | head -5

# Check last log file
cat /home/username/backups/backup-log-$(date +%Y-%m-%d)-*.txt
```

### Remote Execution (Webhook/API)

Perfect for:
- Integration with deployment tools
- Triggered backups from external systems
- CI/CD pipelines

**Using curl:**
```bash
curl "https://yourdomain.com/scripts/backup.php?token=YOUR_TOKEN"
```

**Using wget:**
```bash
wget -O - "https://yourdomain.com/scripts/backup.php?token=YOUR_TOKEN"
```

**In a deploy script:**
```bash
#!/bin/bash
echo "Creating backup before deployment..."
curl -s "https://example.com/scripts/backup.php?token=YOUR_TOKEN"
if [ $? -eq 0 ]; then
    echo "Backup completed, proceeding with deployment"
    # Your deployment commands here
else
    echo "Backup failed, aborting deployment"
    exit 1
fi
```

### Testing & Validation

**1. Test Database Connection:**
```php
<?php
// test-db.php - Upload to same directory as backup.php
include 'backup.php';
$conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass']);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Database connection successful!";
$conn->close();
?>
```

**2. Test FTP Connection:**
```php
<?php
// test-ftp.php
include 'backup.php';
$ftp = $config['ftp_use_ssl'] ? ftp_ssl_connect($config['ftp_host'], $config['ftp_port']) 
                               : ftp_connect($config['ftp_host'], $config['ftp_port']);
if (!$ftp) die("FTP connection failed");
if (!ftp_login($ftp, $config['ftp_user'], $config['ftp_pass'])) die("FTP login failed");
echo "FTP connection successful!";
ftp_close($ftp);
?>
```

**3. Validate Backup Archive:**
```bash
# Check ZIP integrity
unzip -t /home/username/backups/backup-2024-11-03-02-00.zip

# List contents without extracting
unzip -l /home/username/backups/backup-2024-11-03-02-00.zip

# Extract to test directory
mkdir /home/username/test-restore
unzip /home/username/backups/backup-2024-11-03-02-00.zip -d /home/username/test-restore
```

**4. Test Database Restore:**
```bash
# Extract database from backup
cd /home/username/test-restore/databases

# If gzipped
gunzip mysite_db.sql.gz

# Import to test database
mysql -u username -p test_database < mysite_db.sql
```

---

## 📊 Monitoring & Maintenance

### Debug Mode

Enable verbose logging:
```php
'debug_mode' => true
```

Output includes:
- Individual file processing
- Detailed database table exports
- Lock file operations
- Memory usage tracking

### Custom Exclusion Patterns

Add specific patterns to exclude:
```php
'exclude_patterns' => [
    // ... default patterns ...
    '#/wp-content/uploads/\d{4}/\d{2}/#i',  // Exclude old uploads
    '#\.mp4$#i',                              // Exclude video files
    '#/backup-old/#i',                        // Exclude old backups dir
]
```

### Upload Logs to FTP

Keep log files on remote server:
```php
'upload_logs_to_ftp' => true
```

Logs will be uploaded alongside backups.

---

## File Structure

After setup, your directory structure:
```
/home/username/
├── scripts/
│   ├── backup.php           # Main backup script
│   └── .htaccess            # Security configuration
├── backups/
│   ├── backup-2024-11-03-02-00.zip
│   ├── backup-2024-11-02-02-00.zip
│   ├── backup-log-2024-11-03-02-00.txt
│   └── backup.lock          # (temporary, removed after completion)
├── tmp/
│   └── (temporary database dumps during execution)
└── public_html/
    └── (your website files - backed up)
```

---

### Daily Monitoring Tasks

#### Check Last Backup
```bash
# List 5 most recent backups
ls -lth /home/username/backups/backup-*.zip | head -5

# Output:
# -rw------- 1 user user 523M Nov  3 02:00 backup-2024-11-03-02-00.zip
# -rw------- 1 user user 521M Nov  2 02:00 backup-2024-11-02-02-00.zip
```

#### View Latest Log File
```bash
# View last 50 lines of most recent log
tail -50 /home/username/backups/backup-log-*.txt | tail -1

# Or view specific log
cat /home/username/backups/backup-log-2024-11-03-02-00.txt

# Search for errors in all logs
grep -i error /home/username/backups/backup-log-*.txt
```

#### Monitor Disk Space
```bash
# Check backup directory size
du -sh /home/username/backups/
# Output: 2.5G    /home/username/backups/

# Check available space
df -h /home/username
# Output: Filesystem   Size  Used Avail Use% Mounted on
#         /dev/sda1    50G   12G   38G  24% /home

# List individual backup sizes
du -h /home/username/backups/backup-*.zip | sort -h
```

### Weekly Monitoring Tasks

#### Verify Backup Integrity
```bash
# Test ZIP integrity (quick)
for file in /home/username/backups/backup-*.zip; do
    echo "Testing: $file"
    unzip -t "$file" >/dev/null 2>&1 && echo "✓ OK" || echo "✗ FAILED"
done
```

#### Check Email Notifications
- Verify you received success emails
- Review for any warnings in emails
- Ensure FTP uploads completed
- Check retention cleanup is working

#### Review FTP Remote Backups
```bash
# List remote backups via FTP command line
ftp backup-server.example.com
# > ls /backups/
# > bye
```

### Monthly Monitoring Tasks

#### Test Full Restore Process

**1. Download Backup:**
```bash
# From FTP server
wget ftp://user:pass@ftp.example.com/backups/backup-2024-11-03-02-00.zip

# Or copy from local
cp /home/username/backups/backup-2024-11-03-02-00.zip ~/test-restore/
```

**2. Extract and Validate:**
```bash
cd ~/test-restore
unzip backup-2024-11-03-02-00.zip

# Check database files exist
ls -lh databases/

# Check website files exist
ls -lh public_html/
```

**3. Test Database Import:**
```bash
cd databases/

# If gzipped, decompress first
gunzip *.sql.gz

# Create test database
mysql -u root -p -e "CREATE DATABASE test_restore;"

# Import backup
mysql -u root -p test_restore < mysite_db.sql

# Verify tables imported
mysql -u root -p test_restore -e "SHOW TABLES;"

# Clean up
mysql -u root -p -e "DROP DATABASE test_restore;"
```

#### Review Log Patterns
```bash
# Count successful backups
grep -c "Backup completed successfully" /home/username/backups/backup-log-*.txt

# Average execution time
grep "Execution time" /home/username/backups/backup-log-*.txt

# Check for warnings
grep WARNING /home/username/backups/backup-log-*.txt
```

### Automated Monitoring Script

Create `/home/username/scripts/check-backup.sh`:

```bash
#!/bin/bash
# Backup Health Check Script

BACKUP_DIR="/home/username/backups"
MAX_AGE_HOURS=26  # Alert if backup older than 26 hours
MIN_SIZE_MB=10    # Alert if backup smaller than 10MB
EMAIL="your@email.com"

# Find most recent backup
LATEST=$(ls -t $BACKUP_DIR/backup-*.zip 2>/dev/null | head -1)

if [ -z "$LATEST" ]; then
    echo "ERROR: No backups found!" | mail -s "Backup Alert: No backups" $EMAIL
    exit 1
fi

# Check age
FILE_TIME=$(stat -c %Y "$LATEST")
CURRENT_TIME=$(date +%s)
AGE_HOURS=$(( ($CURRENT_TIME - $FILE_TIME) / 3600 ))

if [ $AGE_HOURS -gt $MAX_AGE_HOURS ]; then
    echo "WARNING: Latest backup is $AGE_HOURS hours old" | mail -s "Backup Alert: Stale" $EMAIL
fi

# Check size
FILE_SIZE=$(stat -c %s "$LATEST")
SIZE_MB=$(( $FILE_SIZE / 1024 / 1024 ))

if [ $SIZE_MB -lt $MIN_SIZE_MB ]; then
    echo "WARNING: Backup size is only ${SIZE_MB}MB" | mail -s "Backup Alert: Too small" $EMAIL
fi

echo "Backup OK: ${SIZE_MB}MB, ${AGE_HOURS}h old"
```

**Schedule monitoring:**
```cron
# Run health check daily at 8 AM
0 8 * * * /home/username/scripts/check-backup.sh
```

### Performance Metrics to Track

| Metric | How to Check | Healthy Range | Action if Outside |
|--------|--------------|---------------|-------------------|
| **Execution Time** | Check logs | 5-15 minutes | Optimize exclusions |
| **Backup Size** | `du -h backup-*.zip` | Consistent ±10% | Investigate large changes |
| **Database Count** | Check logs | Matches expected | Check DB discovery |
| **FTP Upload Speed** | Check logs | Based on connection | Check network |
| **Memory Usage** | Check logs | <80% of limit | Increase memory_limit |
| **Error Rate** | `grep ERROR logs` | 0 per month | Review failures |

---

## 🔧 Advanced Configuration

### Custom Exclusion Patterns

**WordPress-specific:**
```php
'exclude_patterns' => [
    '#/wp-content/cache/#i',
    '#/wp-content/uploads/\d{4}/\d{2}/#i',  // Old uploads by date
]
```

**Laravel-specific:**
```php
'exclude_patterns' => [
    '#/storage/logs/#i',
    '#/storage/framework/cache/#i',
    '#/vendor/#i',  // Composer packages
]
```

### Performance Tuning

```php
'max_execution_time' => 1800,      // 30 minutes for large sites
'memory_limit' => '1024M',         // 1GB for very large databases
'compression_level' => 1,          // Faster, less compression
'gzip_threshold_mb' => 50,         // Only gzip files > 50MB
```

---

## 🤝 Contributing

Contributions are welcome! Here's how you can help:

### Reporting Issues

Found a bug? [Open an issue](https://github.com/intelligentech/Shared-Hosting-PHP-Backup/issues) with:
- Clear description of the problem
- Steps to reproduce
- Expected vs actual behavior
- PHP version and hosting environment
- Relevant log excerpts

### Submitting Pull Requests

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Make your changes
4. Test thoroughly
5. Commit: `git commit -m 'Add amazing feature'`
6. Push: `git push origin feature/amazing-feature`
7. Open a Pull Request

### Development Guidelines

- Follow existing code style
- Add comments for complex logic
- Update documentation for new features
- Test on multiple PHP versions (7.4, 8.0, 8.1, 8.2)
- Ensure backwards compatibility

---

## 📄 License

```
MIT License

Copyright (c) 2024 Intelligentech

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

**TL;DR:** Free to use for personal and commercial projects. No attribution required (but appreciated!).

---

## 📞 Support

### Documentation

- 📖 **This README** - Comprehensive usage guide
- 💬 **[GitHub Discussions](https://github.com/intelligentech/Shared-Hosting-PHP-Backup/discussions)** - Ask questions, share tips
- 🐛 **[Issue Tracker](https://github.com/intelligentech/Shared-Hosting-PHP-Backup/issues)** - Report bugs, request features

### Community

- ⭐ **Star this repo** if it helped you!
- 🍴 **Fork** and customize for your needs
- 📢 **Share** with others who might benefit

### Professional Support

Need help with:
- Custom features
- Enterprise deployment
- Migration assistance
- Security audits
- Performance optimization

Contact: [Open a discussion](https://github.com/intelligentech/Shared-Hosting-PHP-Backup/discussions)

---

## 📊 Changelog

### Version 1.0.0 (2024-11-03)

**Initial Production Release**

✨ **Features:**
- Automatic database discovery and export
- Intelligent compression (gzip for large databases)
- Dual execution modes (web browser + cron)
- Remote FTP/FTPS storage with automatic upload
- Configurable retention policies (local & remote)
- Email notifications (HTML + plain text)
- Concurrent execution prevention (mutex locks)
- Memory-efficient file processing
- Regex-based file exclusions
- Comprehensive logging system
- ZIP integrity verification
- NUL byte handling in database exports
- UTF-8/UTF8MB4 support
- Timeout warning system

🛡️ **Security:**
- Token-based web access authentication
- Path traversal protection
- Sanitized database/file names
- Secure credential handling
- Environment variable support

📚 **Documentation:**
- Complete installation guide
- Configuration examples
- Troubleshooting section
- Security best practices
- Performance tuning guide

🧪 **Tested On:**
- PHP 7.4, 8.0, 8.1, 8.2, 8.3
- cPanel shared hosting
- Plesk hosting
- DirectAdmin hosting
- WordPress sites
- Laravel applications
- Custom PHP applications

---

## 🎯 Roadmap

### Planned Features

- [ ] Amazon S3 storage support
- [ ] Google Drive integration
- [ ] Dropbox backup option
- [ ] Incremental backups
- [ ] Backup encryption (AES-256)
- [ ] Web-based configuration UI
- [ ] Backup verification tool
- [ ] Automated restoration script
- [ ] Multi-threaded compression
- [ ] Backup comparison tool
- [ ] Mobile app notifications
- [ ] Backup scheduling calendar
- [ ] Cloud storage rotation

**Want a feature?** [Open a feature request](https://github.com/intelligentech/Shared-Hosting-PHP-Backup/issues/new?template=feature_request.md)

---

## 💝 Acknowledgments

Built with care for the shared hosting community. Special thanks to:

- All contributors who improve this project
- Users who report bugs and suggest features
- The PHP community for excellent documentation
- Shared hosting providers who make this possible

---

## ⚠️ Disclaimer

This software is provided "as is" without warranty. Always test backups and verify restoration procedures. The authors are not responsible for data loss. Maintain multiple backup copies and test regularly.

**Remember:** A backup is only good if you can restore from it! 🔄

---

<div align="center">

**🌟 If this project saved you time and headaches, consider starring it! 🌟**

[![GitHub stars](https://img.shields.io/github/stars/intelligentech/Shared-Hosting-PHP-Backup?style=social)](https://github.com/intelligentech/Shared-Hosting-PHP-Backup/stargazers)
[![GitHub forks](https://img.shields.io/github/forks/intelligentech/Shared-Hosting-PHP-Backup?style=social)](https://github.com/intelligentech/Shared-Hosting-PHP-Backup/network/members)

Made with ❤️ by [Intelligentech](https://github.com/intelligentech)

</div>

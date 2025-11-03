<?php
/**
 * ================================================================================
 * ENTERPRISE-GRADE PHP BACKUP AUTOMATION SCRIPT
 * ================================================================================
 * 
 * Production-ready backup solution for shared hosting environments
 * Operates entirely within standard PHP functions (no shell access required)
 * Supports both manual browser execution and automated cron scheduling
 * 
 * @version     1.2.8
 * @author      Enterprise Backup System
 * @license     MIT
 * @requires    PHP 7.4+, mysqli/PDO, zip, ftp, zlib
 * 
 * ================================================================================
 * INSTALLATION INSTRUCTIONS
 * ================================================================================
 * 
 * 1. Upload this file to a secure directory outside public_html (recommended)
 *    Example: /home/username/scripts/backup.php
 * 
 * 2. Configure the settings in the CONFIGURATION section below:
 *    - Database credentials (MySQL user with access to all databases)
 *    - FTP server details for remote backup storage
 *    - File paths (backup source, local backup directory, temp directory)
 *    - Email notification address
 *    - Web access security token
 * 
 * 3. Set file permissions:
 *    chmod 600 backup.php (read/write for owner only)
 *    chmod 700 /path/to/backups (directory execute/read/write for owner)
 * 
 * 4. Create required directories:
 *    mkdir -p /home/username/backups
 *    mkdir -p /home/username/tmp
 * 
 * 5. Protect script location with .htaccess (see included .htaccess file)
 * 
 * ================================================================================
 * MANUAL EXECUTION (Web Browser)
 * ================================================================================
 * 
 * URL Format: https://yourdomain.com/path/to/backup.php?token=YOUR_SECRET_TOKEN
 * 
 * Security: The script requires a GET parameter 'token' matching the configured
 * 'web_access_token' value. This prevents unauthorized execution.
 * 
 * The browser will display real-time progress updates and a summary upon completion.
 * 
 * ================================================================================
 * AUTOMATED EXECUTION (Cron)
 * ================================================================================
 * 
 * Recommended cron schedule (daily at 2:00 AM):
 * 0 2 * * * /usr/bin/php /home/username/scripts/backup.php >/dev/null 2>&1
 * 
 * Alternative with output logging:
 * 0 2 * * * /usr/bin/php /home/username/scripts/backup.php >> /home/username/logs/cron.log 2>&1
 * 
 * The script automatically detects CLI execution and suppresses HTML output.
 * 
 * ================================================================================
 * TROUBLESHOOTING COMMON ISSUES
 * ================================================================================
 * 
 * 1. "Maximum execution time exceeded"
 *    - Increase max_execution_time in config or contact hosting provider
 *    - Reduce backup source size by adding exclusion patterns
 * 
 * 2. "Allowed memory size exhausted"
 *    - Increase memory_limit in config (512M recommended)
 *    - Check for very large files that may cause memory issues
 * 
 * 3. "FTP connection failed"
 *    - Verify FTP credentials and server address
 *    - Check if hosting firewall blocks outbound FTP connections
 *    - Try enabling/disabling FTP SSL (ftp_use_ssl setting)
 * 
 * 4. "Database access denied"
 *    - Verify MySQL username and password
 *    - Ensure MySQL user has SELECT privileges on all databases
 *    - Check if hosting uses localhost or 127.0.0.1
 * 
 * 5. "Lock file exists - backup already running"
 *    - Normal if backup is currently executing
 *    - If backup crashed, manually delete backup.lock file
 *    - Lock auto-expires after 900 seconds (15 minutes)
 * 
 * ================================================================================
 * SECURITY RECOMMENDATIONS
 * ================================================================================
 * 
 * 1. Store this script OUTSIDE public_html directory when possible
 * 2. Use strong, unique web_access_token (minimum 32 characters)
 * 3. Set restrictive file permissions (600 for script, 700 for directories)
 * 4. Protect script directory with .htaccess (deny all except localhost)
 * 5. Use environment variables for sensitive credentials when available
 * 6. Regularly rotate FTP and database passwords
 * 7. Enable FTPS (FTP over SSL) for encrypted transfers
 * 8. Review backup logs for suspicious activity
 * 
 * ================================================================================
 */

// Prevent direct access without proper context
if (!defined('PHP_VERSION_ID')) {
    die('ERROR: PHP_VERSION_ID not defined. This script requires PHP 7.4 or higher.');
}

if (PHP_VERSION_ID < 70400) {
    die('ERROR: This script requires PHP 7.4 or higher. Current version: ' . PHP_VERSION);
}

// ==================== CONFIGURATION ====================

$config = [
    // Database Settings
    'db_host' => 'localhost',  // CHANGE_THIS: Usually 'localhost' or '127.0.0.1'
    'db_user' => 'your_mysql_username',  // CHANGE_THIS: MySQL username
    'db_pass' => 'your_mysql_password',  // CHANGE_THIS: MySQL password
    // Note: Leave db_name empty - script auto-discovers all accessible databases
    
    // FTP Settings
    'ftp_host' => 'backup-server.example.com',  // CHANGE_THIS: FTP server hostname
    'ftp_user' => 'ftp_username',  // CHANGE_THIS: FTP username
    'ftp_pass' => 'ftp_password',  // CHANGE_THIS: FTP password
    'ftp_remote_dir' => '/backups/',  // CHANGE_THIS: Remote backup directory
    'ftp_use_ssl' => false,  // Set true for FTPS (FTP over SSL)
    'ftp_passive' => true,  // Usually true for shared hosting
    'ftp_port' => 21,  // Standard FTP port (990 for FTPS)
    
    // Paths (use absolute paths)
    'backup_source' => '/home/username/public_html',  // CHANGE_THIS: Directory to backup
    'local_backup_dir' => '/home/username/backups',  // CHANGE_THIS: Local backup storage
    'temp_dir' => '/home/username/tmp',  // CHANGE_THIS: Temporary file directory
    
    // Retention Policies
    'remote_retention_count' => 14,  // Keep 14 most recent backups on FTP server
    'local_retention_count' => 5,   // Keep 5 most recent backups locally
    
    // Compression Settings
    'compression_level' => 6,  // ZIP compression: 1-9 (6 is balanced speed/size)
    'gzip_threshold_mb' => 20,  // Apply gzip to database dumps exceeding this size
    
    // Email Notifications
    'notify_email' => 'brendancilia@fastmail.com',  // CHANGE_THIS: Your email
    'email_from' => 'backup@yourdomain.com',  // CHANGE_THIS: Sender email
    'email_from_name' => 'Backup System',
    'email_on_success' => true,  // Send email on successful backup
    'email_on_failure' => true,  // Send email on backup failure (always recommended)
    
    // Security
    'web_access_token' => 'CHANGE_THIS_SECRET_TOKEN_MIN_32_CHARS',  // CHANGE_THIS: Strong random token
    
    // Exclusion Patterns (regex) - Files/directories matching these won't be backed up
    'exclude_patterns' => [
        '#/cache/#i',
        '#/tmp/#i',
        '#/temp/#i',
        '#/logs/#i',
        '#/sessions/#i',
        '#/\.git/#i',
        '#/node_modules/#i',
        '#/\.svn/#i',
        '#/\.DS_Store$#i',
        '#/Thumbs\.db$#i',
        // Add custom patterns here
    ],
    
    // Advanced Options
    'debug_mode' => false,  // Enable verbose debug logging
    'upload_logs_to_ftp' => true,  // Upload log files with backups
    'max_execution_time' => 900,  // 15 minutes (shared hosting limit)
    'memory_limit' => '512M',  // Recommended for large backups
    'timeout_warning_seconds' => 840,  // Warn at 14 minutes
];

// Allow environment variable overrides for sensitive data
$config['db_host'] = getenv('DB_HOST') ?: $config['db_host'];
$config['db_user'] = getenv('DB_USER') ?: $config['db_user'];
$config['db_pass'] = getenv('DB_PASSWORD') ?: $config['db_pass'];
$config['ftp_host'] = getenv('FTP_HOST') ?: $config['ftp_host'];
$config['ftp_user'] = getenv('FTP_USER') ?: $config['ftp_user'];
$config['ftp_pass'] = getenv('FTP_PASSWORD') ?: $config['ftp_pass'];

// ==================== END CONFIGURATION ====================

// ==================== INITIALIZATION ====================

// Set PHP execution parameters with validation
$timeSet = @ini_set('max_execution_time', $config['max_execution_time']);
if ($timeSet === false) {
    error_log("[BACKUP WARNING] Cannot override max_execution_time (ini_set disabled by host). Using default: " . ini_get('max_execution_time') . "s");
} else {
    error_log("[BACKUP INFO] max_execution_time set to " . $config['max_execution_time'] . "s");
}

$memSet = @ini_set('memory_limit', $config['memory_limit']);
if ($memSet === false) {
    error_log("[BACKUP WARNING] Cannot override memory_limit (ini_set disabled by host). Using default: " . ini_get('memory_limit'));
} else {
    error_log("[BACKUP INFO] memory_limit set to " . $config['memory_limit']);
}

@ini_set('display_errors', $config['debug_mode'] ? '1' : '0');
if ($config['debug_mode']) {
    error_reporting(E_ALL);
} else {
    error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);
}

// Detect execution mode (CLI vs Web)
$isCLI = (php_sapi_name() === 'cli' || empty($_SERVER['HTTP_HOST']));

// Global variables
$logFile = '';
$lockFile = $config['local_backup_dir'] . '/backup.lock';
$startTime = microtime(true);
$dbBackups = [];
$totalFileCount = 0;
$totalFileSize = 0;
$backupZipPath = '';

// ==================== HELPER FUNCTIONS ====================

/**
 * Write log entry with timestamp and level
 *
 * @param string $level Log level (INFO, WARNING, ERROR, DEBUG)
 * @param string $message Log message
 * @return void
 */
function writeLog($level, $message) {
    global $logFile, $config, $isCLI;
    
    // Skip debug messages unless debug mode enabled
    if ($level === 'DEBUG' && !$config['debug_mode']) {
        return;
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
    
    // Write to log file with fallback to error_log on failure
    if ($logFile && file_exists(dirname($logFile))) {
        $writeResult = @file_put_contents($logFile, $logEntry, FILE_APPEND);
        if ($writeResult === false) {
            // Fallback to PHP error log if file write fails (disk full, permissions, etc.)
            error_log("[BACKUP LOG FAILURE] $logEntry");
        }
    }
    
    // Output to browser/CLI if appropriate
    if (!$isCLI && $level !== 'DEBUG') {
        echo htmlspecialchars($logEntry) . "<br>";
        @flush();
        @ob_flush();
    } elseif ($isCLI && $config['debug_mode']) {
        echo $logEntry;
    }
}

/**
 * Send email notification with HTML support
 *
 * @param string $subject Email subject line
 * @param string $body Email body content
 * @param bool $isHtml Whether body is HTML formatted
 * @return bool Success status
 */
function sendEmail($subject, $body, $isHtml = true) {
    global $config;
    /** @var array $config */
    
    $to = $config['notify_email'];
    $from = $config['email_from'];
    $fromName = $config['email_from_name'];
    
    // Prepare headers
    $headers = [];
    $headers[] = "From: $fromName <$from>";
    $headers[] = "Reply-To: $from";
    $headers[] = "X-Mailer: PHP/" . phpversion();
    $headers[] = "MIME-Version: 1.0";
    
    if ($isHtml) {
        // Create plain text alternative
        $plainText = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));
        
        // Boundary for multipart message
        $boundary = md5(uniqid(time()));
        
        $headers[] = "Content-Type: multipart/alternative; boundary=\"$boundary\"";
        
        $fullBody = "--$boundary\r\n";
        $fullBody .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $fullBody .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $fullBody .= $plainText . "\r\n\r\n";
        $fullBody .= "--$boundary\r\n";
        $fullBody .= "Content-Type: text/html; charset=UTF-8\r\n";
        $fullBody .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $fullBody .= $body . "\r\n\r\n";
        $fullBody .= "--$boundary--";
    } else {
        $headers[] = "Content-Type: text/plain; charset=UTF-8";
        $fullBody = $body;
    }
    
    $result = @mail($to, $subject, $fullBody, implode("\r\n", $headers));
    
    if (!$result) {
        writeLog('ERROR', 'Failed to send email notification');
        return false;
    }
    
    writeLog('INFO', "Email sent successfully to $to");
    return true;
}

/**
 * Format bytes to human-readable size
 *
 * @param int $bytes File size in bytes
 * @param int $precision Decimal precision
 * @return string Formatted size string
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Output message to browser/CLI appropriately
 *
 * @param string $message Message to output
 * @param string $type Message type (info, success, error, warning)
 * @return void
 */
function outputMessage($message, $type = 'info') {
    global $isCLI;
    
    if ($isCLI) {
        echo $message . PHP_EOL;
    } else {
        $colors = [
            'info' => '#333',
            'success' => '#28a745',
            'error' => '#dc3545',
            'warning' => '#ffc107'
        ];
        $color = $colors[$type] ?? $colors['info'];
        echo "<div style='color: $color; margin: 5px 0;'>$message</div>";
        @flush();
        @ob_flush();
    }
}

/**
 * Create lock file to prevent concurrent executions
 *
 * @return bool Success status
 */
function createLockFile() {
    global $lockFile, $config;
    
    $lockData = [
        'pid' => getmypid(),
        'timestamp' => time(),
        'started' => date('Y-m-d H:i:s')
    ];
    
    $result = @file_put_contents($lockFile, json_encode($lockData));
    
    if ($result === false) {
        writeLog('ERROR', "Failed to create lock file: $lockFile");
        return false;
    }
    
    writeLog('DEBUG', "Lock file created: $lockFile");
    return true;
}

/**
 * Check if backup is already running via lock file
 *
 * @return bool True if safe to proceed, false if locked
 */
function checkLockFile() {
    global $lockFile, $config;
    
    if (!file_exists($lockFile)) {
        return true; // No lock file, safe to proceed
    }
    
    $lockData = @json_decode(file_get_contents($lockFile), true);
    
    if (!$lockData || !isset($lockData['timestamp'])) {
        // Invalid lock file, remove it
        writeLog('WARNING', 'Invalid lock file found, removing');
        @unlink($lockFile);
        return true;
    }
    
    $lockAge = time() - $lockData['timestamp'];
    
    // If lock is older than max execution time, assume stale
    if ($lockAge > $config['max_execution_time']) {
        writeLog('WARNING', "Stale lock file detected (age: {$lockAge}s), removing");
        @unlink($lockFile);
        return true;
    }
    
    // Lock is active
    $startedTime = $lockData['started'] ?? 'unknown';
    writeLog('ERROR', "Backup already running (started: $startedTime)");
    return false;
}

/**
 * Remove lock file
 *
 * @return void
 */
function removeLockFile() {
    global $lockFile;
    
    if (file_exists($lockFile)) {
        @unlink($lockFile);
        writeLog('DEBUG', 'Lock file removed');
    }
}

/**
 * Clean up old files based on retention policy
 *
 * @param string $directory Directory to clean
 * @param string $pattern Filename pattern to match
 * @param int $keepCount Number of files to keep
 * @return array Array of deleted files
 */
function cleanupOldFiles($directory, $pattern, $keepCount) {
    $deletedFiles = [];
    
    if (!is_dir($directory)) {
        writeLog('WARNING', "Directory not found for cleanup: $directory");
        return $deletedFiles;
    }
    
    // Find all matching files
    $files = [];
    $dirHandle = opendir($directory);
    
    if (!$dirHandle) {
        writeLog('ERROR', "Failed to open directory for cleanup: $directory");
        return $deletedFiles;
    }
    
    while (($file = readdir($dirHandle)) !== false) {
        if ($file === '.' || $file === '..') continue;
        
        $filePath = $directory . '/' . $file;
        
        if (is_file($filePath) && preg_match($pattern, $file)) {
            $files[$filePath] = filemtime($filePath);
        }
    }
    closedir($dirHandle);
    
    // Sort by modification time (newest first)
    arsort($files);
    
    // Delete files beyond retention count
    $fileCount = 0;
    foreach ($files as $filePath => $mtime) {
        $fileCount++;
        
        if ($fileCount > $keepCount) {
            $fileSize = filesize($filePath);
            
            if (@unlink($filePath)) {
                $deletedFiles[] = [
                    'name' => basename($filePath),
                    'size' => $fileSize,
                    'date' => date('Y-m-d H:i:s', $mtime)
                ];
                writeLog('INFO', "Deleted old backup: " . basename($filePath) . " (" . formatBytes($fileSize) . ")");
            } else {
                writeLog('WARNING', "Failed to delete: " . basename($filePath));
            }
        }
    }
    
    return $deletedFiles;
}

// ==================== DATABASE FUNCTIONS ====================

/**
 * Discover all accessible databases
 *
 * @return array Array of database names
 */
function discoverDatabases() {
    global $config;
    
    $databases = [];
    $skipDatabases = ['information_schema', 'performance_schema', 'mysql', 'sys'];
    
    try {
        $conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass']);
        
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }
        
        writeLog('INFO', "Connected to MySQL server successfully");
        
        $result = $conn->query("SHOW DATABASES");
        
        if (!$result) {
            throw new Exception("Failed to retrieve database list: " . $conn->error);
        }
        
        while ($row = $result->fetch_array()) {
            $dbName = $row[0];
            
            // Skip system databases
            if (!in_array($dbName, $skipDatabases)) {
                $databases[] = $dbName;
            }
        }
        
        $conn->close();
        
        writeLog('INFO', "Discovered " . count($databases) . " database(s): " . implode(', ', $databases));
        
    } catch (Exception $e) {
        writeLog('ERROR', "Database discovery failed: " . $e->getMessage());
        return [];
    }
    
    return $databases;
}

/**
 * Export single database to SQL file with optional gzip compression
 *
 * @param string $dbName Database name to export
 * @return array Export details (success, file_path, size, compressed)
 */
function exportDatabase($dbName) {
    global $config, $startTime;
    
    $result = [
        'success' => false,
        'database' => $dbName,
        'file_path' => '',
        'size_uncompressed' => 0,
        'size_compressed' => 0,
        'compressed' => false,
        'error' => ''
    ];
    
    // Sanitize database name to prevent path traversal
    $safeDbName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $dbName);
    $timestamp = date('Y-m-d-H-i');
    $tempFile = $config['temp_dir'] . "/{$safeDbName}_{$timestamp}.sql";
    $startTime = microtime(true);
    
    try {
        $conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $dbName);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset('utf8mb4');
        
        writeLog('INFO', "Exporting database: $dbName");
        outputMessage("Exporting database: $dbName", 'info');
        
        // Open temp file for writing
        $handle = fopen($tempFile, 'w');
        if (!$handle) {
            throw new Exception("Failed to create temp file: $tempFile");
        }
        
        // Write header
        fwrite($handle, "-- Database Export: $dbName\n");
        fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
        fwrite($handle, "-- MySQL Version: " . $conn->server_info . "\n\n");
        fwrite($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
        fwrite($handle, "SET time_zone = \"+00:00\";\n\n");
        
        // Get all tables
        $tablesResult = $conn->query("SHOW TABLES");
        if (!$tablesResult) {
            throw new Exception("Failed to get tables: " . $conn->error);
        }
        
        $tableCount = 0;
        $rowCount = 0;
        
        while ($tableRow = $tablesResult->fetch_array()) {
            $tableName = $tableRow[0];
            $tableCount++;
            
            writeLog('DEBUG', "Exporting table: $tableName");
            
            // Drop table statement
            fwrite($handle, "\n-- Table structure for: $tableName\n");
            fwrite($handle, "DROP TABLE IF EXISTS `$tableName`;\n");
            
            // Create table statement
            $createResult = $conn->query("SHOW CREATE TABLE `$tableName`");
            if ($createResult) {
                $createRow = $createResult->fetch_array();
                fwrite($handle, $createRow[1] . ";\n\n");
            }
            
            // Export data with row count verification
            // First, get expected row count for verification
            $countResult = $conn->query("SELECT COUNT(*) as cnt FROM `$tableName`");
            $expectedRows = $countResult ? $countResult->fetch_assoc()['cnt'] : 0;
            $tableRowsExported = 0;
            
            $dataResult = $conn->query("SELECT * FROM `$tableName`");
            if ($dataResult && $dataResult->num_rows > 0) {
                fwrite($handle, "-- Data for table: $tableName (expected rows: $expectedRows)\n");
                
                $fields = $dataResult->fetch_fields();
                $numFields = count($fields);
                
                while ($row = $dataResult->fetch_array(MYSQLI_NUM)) {
                    $rowCount++;
                    $tableRowsExported++;
                    
                    // Progress logging for large tables (every 10k rows)
                    if ($tableRowsExported % 10000 == 0) {
                        writeLog('DEBUG', "Exporting $tableName: $tableRowsExported / $expectedRows rows processed...");
                        
                        // Check for approaching timeout during DB export
                        $elapsed = microtime(true) - $startTime;
                        if ($elapsed > $config['timeout_warning_seconds'] && !isset($dbTimeoutWarned)) {
                            $dbTimeoutWarned = true;
                            $progress = $expectedRows > 0 ? round(($tableRowsExported / $expectedRows) * 100) : 0;
                            writeLog('WARNING', "Database export approaching timeout (" . round($elapsed) . "s / " . 
                                    $config['max_execution_time'] . "s) - Table: $tableName, Progress: {$progress}%");
                        }
                    }
                    
                    // Build values array with NUL byte handling
                    $values = [];
                    for ($i = 0; $i < $numFields; $i++) {
                        if ($row[$i] === null) {
                            $values[] = 'NULL';
                        } elseif (is_string($row[$i]) && strpos($row[$i], "\0") !== false) {
                            // NUL byte detected—use HEX encoding for safe export/import
                            $values[] = "0x" . bin2hex($row[$i]);
                            writeLog('DEBUG', "NUL byte detected in table $tableName, using HEX encoding");
                        } else {
                            $values[] = "'" . $conn->real_escape_string($row[$i]) . "'";
                        }
                    }
                    
                    $fieldNames = array_map(function($f) { return "`{$f->name}`"; }, $fields);
                    fwrite($handle, "INSERT INTO `$tableName` (" . implode(', ', $fieldNames) . ") VALUES (" . implode(', ', $values) . ");\n");
                }
                
                fwrite($handle, "\n");
                
                // Verify row count matches expected
                if ($tableRowsExported != $expectedRows) {
                    writeLog('WARNING', "Row count mismatch in $tableName: exported $tableRowsExported, expected $expectedRows (possible permission issue)");
                }
            } elseif ($expectedRows > 0) {
                // Expected rows but got nothing - permission issue?
                writeLog('WARNING', "Table $tableName has $expectedRows rows but export returned 0 (possible permission issue)");
            }
        }
        
        fclose($handle);
        $conn->close();
        
        $exportTime = round(microtime(true) - $startTime, 2);
        $uncompressedSize = filesize($tempFile);
        $result['size_uncompressed'] = $uncompressedSize;
        
        writeLog('INFO', "Database $dbName exported: $tableCount tables, $rowCount rows, " . formatBytes($uncompressedSize) . " in {$exportTime}s");
        
        // Decide on compression based on size threshold
        $thresholdBytes = $config['gzip_threshold_mb'] * 1024 * 1024;
        
        if ($uncompressedSize > $thresholdBytes) {
            // Apply gzip compression
            writeLog('INFO', "Applying gzip compression to $dbName (size > " . $config['gzip_threshold_mb'] . "MB)");
            
            $gzFile = $tempFile . '.gz';
            $gzHandle = gzopen($gzFile, 'wb9'); // Maximum compression
            
            if (!$gzHandle) {
                throw new Exception("Failed to create gzip file");
            }
            
            $sqlHandle = fopen($tempFile, 'rb');
            if (!$sqlHandle) {
                gzclose($gzHandle);
                @unlink($gzFile);
                throw new Exception("Failed to reopen SQL file for compression");
            }
            
            // Compress with error checking on each write
            $bytesWritten = 0;
            while (!feof($sqlHandle)) {
                $chunk = fread($sqlHandle, 1024 * 512); // 512KB chunks
                if ($chunk === false) {
                    fclose($sqlHandle);
                    gzclose($gzHandle);
                    @unlink($gzFile);
                    throw new Exception("Failed to read SQL file during compression");
                }
                
                $written = gzwrite($gzHandle, $chunk);
                if ($written === false || $written === 0) {
                    fclose($sqlHandle);
                    gzclose($gzHandle);
                    @unlink($gzFile);
                    throw new Exception("Failed to write gzip data (possible disk full)");
                }
                $bytesWritten += $written;
            }
            
            fclose($sqlHandle);
            
            // Verify gzclose succeeds
            if (!gzclose($gzHandle)) {
                @unlink($gzFile);
                throw new Exception("Failed to close gzip file (data may be corrupted)");
            }
            
            // Verify compressed file was created and has content
            if (!file_exists($gzFile) || filesize($gzFile) === 0) {
                @unlink($gzFile);
                throw new Exception("Gzip compression produced empty or missing file");
            }
            
            $compressedSize = filesize($gzFile);
            $compressionRatio = round((1 - $compressedSize / $uncompressedSize) * 100, 1);
            
            // Remove uncompressed file only after successful compression
            if (!@unlink($tempFile)) {
                writeLog('WARNING', "Failed to delete uncompressed SQL file: $tempFile");
            }
            
            $result['file_path'] = $gzFile;
            $result['size_compressed'] = $compressedSize;
            $result['compressed'] = true;
            $result['success'] = true;
            
            writeLog('INFO', "Compression complete: " . formatBytes($compressedSize) . " ({$compressionRatio}% reduction)");
            
        } else {
            // Keep uncompressed
            $result['file_path'] = $tempFile;
            $result['size_compressed'] = $uncompressedSize;
            $result['compressed'] = false;
            $result['success'] = true;
            
            writeLog('INFO', "Database kept uncompressed (size < " . $config['gzip_threshold_mb'] . "MB)");
        }
        
    } catch (Exception $e) {
        writeLog('ERROR', "Failed to export database $dbName: " . $e->getMessage());
        $result['error'] = $e->getMessage();
        
        // Clean up temp file if exists
        if (file_exists($tempFile)) {
            @unlink($tempFile);
        }
    }
    
    return $result;
}

// ==================== FILE SYSTEM ARCHIVAL ====================

/**
 * Create ZIP archive of files and database dumps
 *
 * @param array $dbBackups Array of database backup file details
 * @return array Archive details (success, path, size, file_count)
 */
function createBackupArchive($dbBackups) {
    global $config, $totalFileCount, $totalFileSize;
    
    $result = [
        'success' => false,
        'file_path' => '',
        'size' => 0,
        'file_count' => 0,
        'excluded_count' => 0,
        'error' => ''
    ];
    
    $timestamp = date('Y-m-d-H-i');
    $zipFile = $config['local_backup_dir'] . "/backup-{$timestamp}.zip";
    $startTime = microtime(true);
    
    try {
        writeLog('INFO', "Creating backup archive: $zipFile");
        outputMessage("Creating backup archive...", 'info');
        
        // Create ZIP archive
        $zip = new ZipArchive();
        
        if ($zip->open($zipFile, ZipArchive::CREATE) !== true) {
            throw new Exception("Failed to create ZIP file: $zipFile");
        }
        
        $fileCount = 0;
        $excludedCount = 0;
        $totalSize = 0;
        
        // Add database backup files with verification
        foreach ($dbBackups as $dbBackup) {
            if ($dbBackup['success'] && file_exists($dbBackup['file_path'])) {
                $fileName = basename($dbBackup['file_path']);
                if ($zip->addFile($dbBackup['file_path'], 'databases/' . $fileName)) {
                    $fileCount++;
                    writeLog('DEBUG', "Added database backup to archive: $fileName");
                } else {
                    writeLog('ERROR', "Failed to add database backup to ZIP: $fileName");
                    throw new Exception("Critical: Database backup could not be added to archive");
                }
            }
        }
        
        // Add files from backup source directory
        if (is_dir($config['backup_source'])) {
            writeLog('INFO', "Scanning directory: " . $config['backup_source']);
            
            // Check available disk space before archiving
            $availableDiskSpace = @disk_free_space($config['local_backup_dir']);
            if ($availableDiskSpace === false) {
                writeLog('WARNING', 'Cannot determine available disk space');
                $availableDiskSpace = PHP_INT_MAX; // Continue but don't enforce limit
            } else {
                writeLog('INFO', "Available disk space: " . formatBytes($availableDiskSpace));
            }
            
            // Pre-estimate source directory size to avoid partial ZIP creation
            writeLog('INFO', 'Pre-calculating source directory size...');
            $estimatedSourceSize = 0;
            $sizeIterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($config['backup_source'], RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            foreach ($sizeIterator as $sizeFile) {
                try {
                    if ($sizeFile->isFile() && !$sizeFile->isLink()) {
                        $estimatedSourceSize += $sizeFile->getSize();
                    }
                } catch (Exception $e) {
                    // Skip inaccessible files in size calculation
                    continue;
                }
            }
            
            writeLog('INFO', "Estimated source size: " . formatBytes($estimatedSourceSize));
            
            // Fail early if insufficient space (80% threshold, 20% margin for compression/overhead)
            if ($estimatedSourceSize > $availableDiskSpace * 0.8) {
                throw new Exception("Insufficient disk space for backup. Source: " . formatBytes($estimatedSourceSize) . 
                                   ", Available: " . formatBytes($availableDiskSpace) . " (need 20% margin)");
            }
            
            // Use error-tolerant iterator that skips inaccessible directories
            try {
                $dirIterator = new RecursiveDirectoryIterator(
                    $config['backup_source'],
                    RecursiveDirectoryIterator::SKIP_DOTS // Skip . and ..
                );
                
                $iterator = new RecursiveIteratorIterator(
                    $dirIterator,
                    RecursiveIteratorIterator::SELF_FIRST,
                    RecursiveIteratorIterator::CATCH_GET_CHILD // Continue on permission errors
                );
                
                foreach ($iterator as $file) {
                    try {
                        // Explicitly skip symlinks to prevent infinite loops on shared hosting
                        if ($file->isLink()) {
                            writeLog('DEBUG', "Skipping symlink: " . $file->getPathname());
                            continue;
                        }
                        
                        if ($file->isFile()) {
                            $filePath = $file->getPathname();
                            $relativePath = substr($filePath, strlen($config['backup_source']) + 1);
                            
                            // Check exclusion patterns
                            $excluded = false;
                            foreach ($config['exclude_patterns'] as $pattern) {
                                if (preg_match($pattern, $filePath) || preg_match($pattern, $relativePath)) {
                                    $excluded = true;
                                    $excludedCount++;
                                    writeLog('DEBUG', "Excluded: $relativePath");
                                    break;
                                }
                            }
                            
                            if (!$excluded) {
                                $fileSize = $file->getSize();
                                
                                // Check if adding this file would exceed available disk space
                                if ($totalSize + $fileSize > $availableDiskSpace * 0.9) { // Leave 10% margin
                                    writeLog('ERROR', "Approaching disk space limit. Total size: " . formatBytes($totalSize) . ", Available: " . formatBytes($availableDiskSpace));
                                    throw new Exception("Insufficient disk space to complete backup");
                                }
                                
                                // Attempt to add file and verify it succeeded
                                if (!$zip->addFile($filePath, 'files/' . $relativePath)) {
                                    writeLog('ERROR', "Failed to add file to ZIP: $relativePath (disk full or permission issue)");
                                    throw new Exception("Cannot add file to backup—possible disk space exhaustion");
                                }
                                
                                $fileCount++;
                                $totalSize += $fileSize;
                                
                                if ($fileCount % 100 == 0) {
                                    outputMessage("Archived $fileCount files...", 'info');
                                    
                                    // Check for approaching timeout
                                    $elapsed = microtime(true) - $startTime;
                                    if ($elapsed > $config['timeout_warning_seconds'] && !isset($timeoutWarned)) {
                                        $timeoutWarned = true;
                                        writeLog('WARNING', "Backup approaching timeout (" . round($elapsed) . "s / " . 
                                                $config['max_execution_time'] . "s)");
                                    }
                                }
                            }
                        }
                    } catch (UnexpectedValueException $e) {
                        // Permission denied or file disappeared during iteration
                        writeLog('WARNING', "Skipping inaccessible file: " . $e->getMessage());
                        continue;
                    } catch (RuntimeException $e) {
                        // File read error
                        writeLog('WARNING', "Skipping unreadable file: " . $e->getMessage());
                        continue;
                    }
                }
            } catch (UnexpectedValueException $e) {
                writeLog('ERROR', "Cannot access backup source directory: " . $e->getMessage());
                throw new Exception("Failed to scan backup source: " . $e->getMessage());
            }
        }
        
        // Create manifest
        $manifest = "BACKUP MANIFEST\n" . str_repeat("=", 50) . "\n\n";
        $manifest .= "Backup Date: " . date('Y-m-d H:i:s') . "\n";
        $manifest .= "Timestamp: $timestamp\n\n";
        $manifest .= "DATABASES:\n" . str_repeat("-", 50) . "\n";
        foreach ($dbBackups as $db) {
            if ($db['success']) {
                $manifest .= "- {$db['database']} (" . formatBytes($db['size_compressed']) . ")\n";
            }
        }
        $manifest .= "\nFiles: $fileCount | Excluded: $excludedCount\n";
        
        $zip->addFromString('manifest.txt', $manifest);
        $zip->close();
        
        $zipSize = filesize($zipFile);
        writeLog('INFO', "Archive created: " . formatBytes($zipSize) . " ($fileCount files)");
        
        // Verify integrity
        $zipCheck = new ZipArchive();
        if ($zipCheck->open($zipFile, ZipArchive::CHECKCONS) !== true) {
            throw new Exception("ZIP integrity check failed");
        }
        $zipCheck->close();
        
        $result['success'] = true;
        $result['file_path'] = $zipFile;
        $result['size'] = $zipSize;
        $result['file_count'] = $fileCount;
        $result['excluded_count'] = $excludedCount;
        
        $totalFileCount = $fileCount;
        $totalFileSize = $zipSize;
        
    } catch (Exception $e) {
        writeLog('ERROR', "Archive creation failed: " . $e->getMessage());
        $result['error'] = $e->getMessage();
        if (file_exists($zipFile)) {
            @unlink($zipFile);
        }
    }
    
    return $result;
}

// ==================== FTP FUNCTIONS ====================

/**
 * Upload backup to FTP server with retention management
 *
 * @param string $localFile Local file path to upload
 * @return array Upload details (success, remote_path, deleted_files)
 */
function uploadToFTP($localFile) {
    global $config, $startTime;
    
    $result = [
        'success' => false,
        'remote_path' => '',
        'uploaded_size' => 0,
        'deleted_files' => [],
        'error' => ''
    ];
    
    if (!file_exists($localFile)) {
        $result['error'] = "Local file not found: $localFile";
        writeLog('ERROR', $result['error']);
        return $result;
    }
    
    try {
        writeLog('INFO', "Connecting to FTP server: " . $config['ftp_host']);
        outputMessage("Uploading to FTP server...", 'info');
        
        // Connect to FTP (try SSL first if enabled)
        if ($config['ftp_use_ssl']) {
            $ftpConn = @ftp_ssl_connect($config['ftp_host'], $config['ftp_port']);
        } else {
            $ftpConn = @ftp_connect($config['ftp_host'], $config['ftp_port']);
        }
        
        if (!$ftpConn) {
            throw new Exception("FTP connection failed to " . $config['ftp_host']);
        }
        
        // Login
        if (!@ftp_login($ftpConn, $config['ftp_user'], $config['ftp_pass'])) {
            throw new Exception("FTP login failed for user: " . $config['ftp_user']);
        }
        
        writeLog('INFO', "FTP login successful");
        
        // Set passive mode
        if ($config['ftp_passive']) {
            ftp_pasv($ftpConn, true);
        }
        
        // Change to remote directory with verification
        if (!@ftp_chdir($ftpConn, $config['ftp_remote_dir'])) {
            // Try to create directory if it doesn't exist
            if (!@ftp_mkdir($ftpConn, $config['ftp_remote_dir'])) {
                throw new Exception("Cannot access/create remote directory: " . $config['ftp_remote_dir']);
            }
            // Verify we can change to the newly created directory
            if (!@ftp_chdir($ftpConn, $config['ftp_remote_dir'])) {
                throw new Exception("Created remote directory but cannot chdir to it: " . $config['ftp_remote_dir']);
            }
            writeLog('INFO', "Created remote directory: " . $config['ftp_remote_dir']);
        }
        
        // Upload file with retry logic and chunking support for large files
        $remoteFile = basename($localFile);
        $localSize = filesize($localFile);
        
        writeLog('INFO', "Uploading file: $remoteFile (" . formatBytes($localSize) . ")");
        
        // Retry configuration for transient network errors
        $maxRetries = 3;
        $retryDelay = 2; // seconds
        $uploadSuccess = false;
        $lastError = '';
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            if ($attempt > 1) {
                writeLog('WARNING', "FTP upload attempt $attempt of $maxRetries (retrying after {$retryDelay}s delay)...");
                sleep($retryDelay);
                $retryDelay *= 2; // Exponential backoff
            }
            
            try {
                // Use chunked upload for files >100MB to handle connection drops
                $chunkThreshold = 100 * 1024 * 1024; // 100MB
        
        if ($localSize > $chunkThreshold) {
            // Large file upload - use non-blocking with timeout protection
            writeLog('DEBUG', "Using non-blocking upload for large file (" . formatBytes($localSize) . ")");
            
            // Check if partial upload exists
            $remoteSize = @ftp_size($ftpConn, $remoteFile);
            if ($remoteSize > 0 && $remoteSize < $localSize) {
                // Only delete if clearly corrupted (less than 1% of expected size)
                if ($remoteSize < $localSize * 0.01) {
                    writeLog('WARNING', "Corrupted partial upload detected (" . formatBytes($remoteSize) . "), deleting");
                    @ftp_delete($ftpConn, $remoteFile);
                } else {
                    // File is mostly complete—retain for manual inspection
                    writeLog('INFO', "Substantial partial upload exists (" . formatBytes($remoteSize) . "), keeping for recovery");
                }
            }
            
            // Start non-blocking upload
            $ret = @ftp_nb_put($ftpConn, $remoteFile, $localFile, FTP_BINARY);
            
            if ($ret === false) {
                throw new Exception("Failed to initiate FTP upload for: $remoteFile");
            }
            
            // Continue upload with timeout protection (10 minutes max)
            $uploadSuccess = false;
            $maxAttempts = 600; // 10 minutes with 1-second intervals
            $attempts = 0;
            
            while ($ret == FTP_MOREDATA && $attempts++ < $maxAttempts) {
                $ret = @ftp_nb_continue($ftpConn);
                sleep(1); // Prevent tight loop, allow network breathing room
                
                if ($attempts % 60 == 0) {
                    writeLog('DEBUG', "Upload in progress... (" . round($attempts / 60) . " minutes)");
                    
                    // Check for approaching timeout during FTP upload
                    $elapsed = microtime(true) - $startTime;
                    if ($elapsed > $config['timeout_warning_seconds'] && !isset($ftpTimeoutWarned)) {
                        $ftpTimeoutWarned = true;
                        writeLog('WARNING', "FTP upload approaching timeout (" . round($elapsed) . "s / " . 
                                $config['max_execution_time'] . "s) - File: $remoteFile");
                    }
                }
            }
            
            if ($ret == FTP_FINISHED) {
                $uploadSuccess = true;
                writeLog('INFO', "Large file upload completed in $attempts seconds");
            } elseif ($attempts >= $maxAttempts) {
                throw new Exception("FTP upload timed out after 10 minutes");
            } else {
                throw new Exception("FTP upload failed with status code: $ret");
            }
                } else {
                    // Standard upload for smaller files
                    $uploadSuccess = @ftp_put($ftpConn, $remoteFile, $localFile, FTP_BINARY);
                }
                
                if ($uploadSuccess) {
                    if ($attempt > 1) {
                        writeLog('INFO', "FTP upload succeeded on attempt $attempt");
                    }
                    break; // Success - exit retry loop
                } else {
                    $lastError = "FTP upload failed for: $remoteFile";
                }
            } catch (Exception $e) {
                $lastError = $e->getMessage();
                writeLog('WARNING', "Upload attempt $attempt failed: " . $lastError);
                
                if ($attempt >= $maxRetries) {
                    throw new Exception("FTP upload failed after $maxRetries attempts: $lastError");
                }
            }
        }
        
        if (!$uploadSuccess) {
            throw new Exception("FTP upload failed after $maxRetries attempts: $lastError");
        }
        
        // Verify upload by comparing file sizes with retry logic
        $verifyRetries = 3;
        $remoteSize = -1;
        
        for ($i = 0; $i < $verifyRetries; $i++) {
            $remoteSize = @ftp_size($ftpConn, $remoteFile);
            
            if ($remoteSize === $localSize) {
                break; // Verification successful
            }
            
            if ($i < $verifyRetries - 1) {
                writeLog('DEBUG', "Verification attempt " . ($i + 1) . " failed, retrying...");
                sleep(1); // Brief delay before retry
            }
        }
        
        if ($remoteSize !== $localSize) {
            // Verification failed - leave incomplete upload for retention policy to handle
            // Don't delete here to avoid having no remote backup at all
            throw new Exception("Upload verification failed after $verifyRetries attempts. Local: $localSize bytes, Remote: $remoteSize bytes (incomplete file left for retention cleanup)");
        }
        
        writeLog('INFO', "Upload successful and verified: $remoteFile (" . formatBytes($remoteSize) . ")");
        $result['remote_path'] = $config['ftp_remote_dir'] . '/' . $remoteFile;
        $result['uploaded_size'] = $localSize;
        
        // Manage remote retention - keep only the most recent backups
        writeLog('INFO', "Managing remote retention (keep " . $config['remote_retention_count'] . " backups)");
        
        $deletedFiles = cleanupRemoteFTP($ftpConn, $config['remote_retention_count']);
        $result['deleted_files'] = $deletedFiles;
        
        // Close connection
        ftp_close($ftpConn);
        
        $result['success'] = true;
        
    } catch (Exception $e) {
        writeLog('ERROR', "FTP operation failed: " . $e->getMessage());
        $result['error'] = $e->getMessage();
        
        if (isset($ftpConn) && $ftpConn) {
            @ftp_close($ftpConn);
        }
    }
    
    return $result;
}

/**
 * Clean up old backups on FTP server
 *
 * @param resource $ftpConn Active FTP connection
 * @param int $keepCount Number of backups to retain
 * @return array Array of deleted file information
 */
function cleanupRemoteFTP($ftpConn, $keepCount) {
    $deletedFiles = [];
    
    // Use ftp_rawlist() for reliable cross-server parsing
    /** @var resource|\FTP\Connection $ftpConn */
    $rawList = @ftp_rawlist($ftpConn, '.');
    
    if ($rawList === false || empty($rawList)) {
        writeLog('WARNING', 'Could not retrieve FTP file list for retention cleanup');
        return $deletedFiles;
    }
    
    // Parse raw listing to extract filenames and metadata
    $backupFiles = [];
    foreach ($rawList as $line) {
        // Parse FTP LIST format: "permissions links owner group size month day time filename"
        // Example: "-rw-r--r-- 1 user group 1234567 Nov 03 14:30 backup-2024-11-03-02-00.zip"
        $parts = preg_split('/\s+/', $line, 9);
        
        if (count($parts) < 9) {
            continue; // Skip malformed lines
        }
        
        $filename = basename($parts[8]); // Extract filename, handles path-prefixed results
        
        // Match backup filename pattern
        if (preg_match('/^backup-\d{4}-\d{2}-\d{2}-\d{2}-\d{2}\.zip$/', $filename)) {
            // Get modification time with error checking
            /** @var resource|\FTP\Connection $ftpConn */
            $modTime = @ftp_mdtm($ftpConn, $filename);
            
            if ($modTime === -1) {
                // Fallback: Parse timestamp from rawlist
                writeLog('DEBUG', "ftp_mdtm failed for $filename, parsing from rawlist");
                
                try {
                    // Parse FTP LIST format timestamp
                    // Format: "-rw-r--r-- 1 user group 1234567 Nov 03 14:30 filename"
                    $month = $parts[5] ?? '';
                    $day = $parts[6] ?? '';
                    $timeOrYear = $parts[7] ?? '';
                    
                    // Construct date string
                    if (strpos($timeOrYear, ':') !== false) {
                        // Recent file with time: "Nov 03 14:30"
                        $dateStr = "$month $day " . date('Y') . " $timeOrYear";
                    } else {
                        // Old file with year: "Nov 03 2023"
                        $dateStr = "$month $day $timeOrYear 00:00";
                    }
                    
                    $modTime = strtotime($dateStr);
                    
                    if ($modTime === false || $modTime === -1) {
                        throw new Exception("strtotime failed");
                    }
                    
                    writeLog('DEBUG', "Parsed timestamp from rawlist: " . date('Y-m-d H:i:s', $modTime));
                } catch (Exception $e) {
                    // Treat as ancient if we can't determine age - will be deleted first by retention
                    // This prevents accumulation of unparseable files
                    writeLog('WARNING', "Cannot determine age of $filename, treating as ancient (will delete first)");
                    $modTime = 0; // Oldest possible timestamp - will be deleted first if retention limit exceeded
                }
            }
            
            // Get size before any delete operations to avoid race condition
            /** @var resource|\FTP\Connection $ftpConn */
            $size = @ftp_size($ftpConn, $filename);
            if ($size === -1) {
                $size = 0; // Unknown size
            }
            
            $backupFiles[$filename] = [
                'mtime' => $modTime,
                'size' => $size
            ];
        }
    }
    
    if (empty($backupFiles)) {
        writeLog('DEBUG', 'No backup files found on FTP server');
        return $deletedFiles;
    }
    
    // Sort by modification time (newest first)
    uasort($backupFiles, function($a, $b) {
        return $b['mtime'] - $a['mtime'];
    });
    
    // Delete old backups beyond retention count
    $fileCount = 0;
    foreach ($backupFiles as $filename => $info) {
        $fileCount++;
        
        if ($fileCount > $keepCount) {
            // Attempt deletion
            /** @var resource|\FTP\Connection $ftpConn */
            if (@ftp_delete($ftpConn, $filename)) {
                $deletedFiles[] = [
                    'name' => $filename,
                    'size' => $info['size'],
                    'date' => date('Y-m-d H:i:s', $info['mtime'])
                ];
                writeLog('INFO', "Deleted remote backup: $filename (" . formatBytes($info['size']) . ")");
            } else {
                writeLog('WARNING', "Failed to delete remote backup: $filename");
            }
        }
    }
    
    writeLog('INFO', "FTP retention: kept $keepCount, deleted " . count($deletedFiles) . " old backup(s)");
    return $deletedFiles;
}

// ==================== MAIN EXECUTION FLOW ====================

// Register shutdown handler for cleanup
register_shutdown_function(function() {
    global $lockFile, $logFile;
    
    // Safely access globals that might not exist
    $dbBackups = $GLOBALS['dbBackups'] ?? [];
    
    // Check for abnormal termination
    $error = error_get_last();
    $isFatalError = $error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR]);
    
    if ($isFatalError) {
        // Log to PHP error log if our log file isn't available
        if (!empty($logFile)) {
            writeLog('ERROR', "Script terminated abnormally: {$error['message']} in {$error['file']}:{$error['line']}");
        } else {
            error_log("[BACKUP FATAL] {$error['message']} in {$error['file']}:{$error['line']}");
        }
        
        // Clean up temp database files on crash
        if (is_array($dbBackups)) {
            foreach ($dbBackups as $db) {
                if (!empty($db['file_path']) && file_exists($db['file_path'])) {
                    @unlink($db['file_path']);
                }
            }
        }
    }
    
    removeLockFile();
    
    if (!empty($logFile) && !$isFatalError) {
        writeLog('DEBUG', "Shutdown handler executed");
    }
});

// Web security check
if (!$isCLI) {
    // Check for valid token using timing-attack-safe comparison
    if (!isset($_GET['token']) || !hash_equals($config['web_access_token'], $_GET['token'])) {
        http_response_code(403);
        die('Access denied. Valid token required.');
    }
    
    // Output HTML header
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Backup Progress</title>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-top: 0; }
        .log { background: #f9f9f9; border: 1px solid #ddd; padding: 15px; border-radius: 4px; max-height: 500px; overflow-y: auto; font-family: monospace; font-size: 12px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Backup System</h1>
        <div class="log">';
    @ob_start();
}

// Initialize log file
$timestamp = date('Y-m-d-H-i');
$logFile = $config['local_backup_dir'] . "/backup-log-{$timestamp}.txt";

// Test log file creation immediately
$testWrite = @file_put_contents($logFile, "[TEST]\n", FILE_APPEND);
if ($testWrite === false) {
    http_response_code(500);
    die("FATAL: Cannot write to log file: $logFile\n");
}

writeLog('INFO', "=== BACKUP SCRIPT STARTED ===");

// Pre-flight configuration validation
writeLog('INFO', 'Validating configuration...');

$requiredDirs = [
    'backup_source' => 'Backup source directory',
    'local_backup_dir' => 'Local backup directory',
    'temp_dir' => 'Temporary directory'
];

foreach ($requiredDirs as $configKey => $description) {
    $dir = $config[$configKey];
    
    if (!is_dir($dir)) {
        $error = "$description does not exist: $dir";
        writeLog('ERROR', $error);
        
        $emailBody = "<h2 style='color:#dc3545'>✗ Backup FAILED</h2>";
        $emailBody .= "<p><strong>Error:</strong> " . htmlspecialchars($error) . "</p>";
        $emailBody .= "<p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>";
        
        @sendEmail("✗ Backup FAILED - Configuration Error - " . date('Y-m-d H:i'), $emailBody, true);
        exit(1);
    }
    
    if (!is_writable($dir)) {
        $error = "$description is not writable: $dir (check permissions)";
        writeLog('ERROR', $error);
        
        $emailBody = "<h2 style='color:#dc3545'>✗ Backup FAILED</h2>";
        $emailBody .= "<p><strong>Error:</strong> " . htmlspecialchars($error) . "</p>";
        $emailBody .= "<p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>";
        
        @sendEmail("✗ Backup FAILED - Configuration Error - " . date('Y-m-d H:i'), $emailBody, true);
        exit(1);
    }
}

writeLog('INFO', 'Configuration validation passed');

// Test mail() function availability
writeLog('INFO', 'Testing mail() function...');
if (!function_exists('mail')) {
    $error = "mail() function is disabled by hosting provider - email notifications will not work";
    writeLog('ERROR', $error);
    
    $errorMsg = "<h2 style='color:#dc3545'>✗ Configuration Error</h2>";
    $errorMsg .= "<p>" . htmlspecialchars($error) . "</p>";
    $errorMsg .= "<p>Email notifications are critical for backup monitoring.</p>";
    
    if (!$isCLI) {
        echo $errorMsg;
    }
    exit(1);
}
writeLog('INFO', 'Mail function available');

writeLog('INFO', "Execution mode: " . ($isCLI ? 'CLI' : 'Web'));
writeLog('INFO', "PHP version: " . PHP_VERSION);
writeLog('INFO', "Memory limit: " . ini_get('memory_limit'));

// Check lock file
outputMessage("Checking for concurrent executions...", 'info');
if (!checkLockFile()) {
    writeLog('ERROR', "Another backup process is running");
    outputMessage("ERROR: Another backup is running", 'error');
    exit(1);
}

createLockFile();

// Database backup
outputMessage("=== Database Backup ===", 'info');
$databases = discoverDatabases();
$dbBackups = [];

foreach ($databases as $dbName) {
    $dbBackup = exportDatabase($dbName);
    $dbBackups[] = $dbBackup;
    if ($dbBackup['success']) {
        outputMessage("✓ Database $dbName backed up", 'success');
    }
}

// Create archive
outputMessage("=== Creating Archive ===", 'info');
$archiveResult = createBackupArchive($dbBackups);

if (!$archiveResult['success']) {
    writeLog('ERROR', "Archive creation failed: " . $archiveResult['error']);
    
    // Cleanup temp database files on archive failure
    foreach ($dbBackups as $db) {
        if (isset($db['file_path']) && !empty($db['file_path']) && file_exists($db['file_path'])) {
            @unlink($db['file_path']);
            writeLog('DEBUG', "Cleaned up temp file after archive failure: " . basename($db['file_path']));
        }
    }
    
    $emailBody = "<h2 style='color:#dc3545'>✗ Backup FAILED</h2><p>Error: " . htmlspecialchars($archiveResult['error']) . "</p>";
    
    // Send failure email if configured (always recommended)
    if ($config['email_on_failure']) {
        sendEmail("✗ Backup FAILED - " . date('Y-m-d H:i'), $emailBody, true);
    } else {
        writeLog('WARNING', 'Failure email suppressed by configuration (email_on_failure = false)');
    }
    
    removeLockFile();
    exit(1);
}

$backupZipPath = $archiveResult['file_path'];
outputMessage("✓ Archive created: " . formatBytes($archiveResult['size']), 'success');

// FTP upload
outputMessage("=== FTP Upload ===", 'info');
$ftpResult = uploadToFTP($backupZipPath);

if ($ftpResult['success']) {
    outputMessage("✓ FTP upload successful", 'success');
} else {
    writeLog('WARNING', "FTP upload failed: " . $ftpResult['error']);
    outputMessage("⚠ FTP upload failed", 'warning');
}

// Local retention
outputMessage("=== Local Retention ===", 'info');
$pattern = '/^backup-\d{4}-\d{2}-\d{2}-\d{2}-\d{2}\.zip$/';
$localDeleted = cleanupOldFiles($config['local_backup_dir'], $pattern, $config['local_retention_count']);

// Completion
$totalTime = round(microtime(true) - $startTime, 2);
writeLog('INFO', "=== BACKUP COMPLETED ===");
writeLog('INFO', "Total time: {$totalTime}s");
outputMessage("=== COMPLETED in {$totalTime}s ===", 'success');

// Send success email
$emailBody = "<h2 style='color:#28a745'>✓ Backup Completed</h2>";
$emailBody .= "<p><strong>Time:</strong> {$totalTime}s</p>";
$emailBody .= "<p><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</p>";
$emailBody .= "<h3>Databases:</h3><ul>";
foreach ($dbBackups as $db) {
    if ($db['success']) {
        $emailBody .= "<li>{$db['database']}: " . formatBytes($db['size_compressed']) . "</li>";
    }
}
$emailBody .= "</ul><h3>Archive:</h3><ul>";
$emailBody .= "<li>Files: " . $archiveResult['file_count'] . "</li>";
$emailBody .= "<li>Size: " . formatBytes($archiveResult['size']) . "</li>";
$emailBody .= "</ul>";

if ($ftpResult['success']) {
    $emailBody .= "<h3>FTP:</h3><ul><li>Uploaded: " . formatBytes($ftpResult['uploaded_size']) . "</li>";
    $emailBody .= "<li>Old backups deleted: " . count($ftpResult['deleted_files']) . "</li></ul>";
}

// Send success email only if configured
if ($config['email_on_success']) {
    sendEmail("✓ Backup Completed - " . date('Y-m-d H:i'), $emailBody, true);
} else {
    writeLog('INFO', 'Success email suppressed by configuration (email_on_success = false)');
}

// Cleanup temp files ONLY if FTP upload succeeded or we're keeping local archive
// If FTP failed, keep temp files so we can retry upload without re-exporting databases
if ($ftpResult['success']) {
    writeLog('INFO', 'FTP upload successful, cleaning up temp database files');
    
    foreach ($dbBackups as $db) {
    // Verify file_path is set, non-empty, and exists before attempting deletion
    if (isset($db['file_path']) && !empty($db['file_path']) && is_string($db['file_path'])) {
        if (file_exists($db['file_path'])) {
            if (@unlink($db['file_path'])) {
                writeLog('DEBUG', "Cleaned up temp file: " . basename($db['file_path']));
            } else {
                writeLog('WARNING', "Failed to delete temp file: " . $db['file_path']);
            }
        } else {
            writeLog('DEBUG', "Temp file already removed or never created: " . $db['file_path']);
        }
    } elseif ($db['success']) {
        // Database export succeeded but file_path is invalid - log warning
        writeLog('WARNING', "Database backup succeeded but file_path is invalid: " . json_encode($db, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
} else {
    writeLog('WARNING', 'FTP upload failed - keeping temp database files for retry');
}

cleanupOldFiles($config['local_backup_dir'], '/^backup-log-.*\.txt$/', 30);

removeLockFile();

if (!$isCLI) {
    echo '</div></div></body></html>';
    @ob_end_flush();
}

exit(0);

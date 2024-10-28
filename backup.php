<?php
register_menu("Backup/Restore DB", true, "backup_list", 'SETTINGS', '');
register_hook('cronjob', 'backup_cron');

if ($_SERVER['REQUEST_URI'] === '/plugin/backup_list') {
    backup_list();
} elseif ($_SERVER['REQUEST_URI'] === '/plugin/backup_download') {
    backup_download();
} elseif ($_SERVER['REQUEST_URI'] === '/plugin/backup_delete') {
    backup_delete();
} elseif ($_SERVER['REQUEST_URI'] === '/plugin/backup_add') {
    backup_add();
}
function backup_list()
{
    global $ui;
    _admin();
    $ui->assign('_title', 'Backup/Restore DB');
    $ui->assign('_system_menu', 'settings');
    $admin = Admin::_info();
    $ui->assign('_admin', $admin);

    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        exit;
    }

    $backupDir = "system/uploads/backup";
    if (!file_exists($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    $backupFiles = scandir($backupDir);
    $backupFiles = array_diff($backupFiles, ['..', '.', '']);

    $backupFiles = array_filter($backupFiles, function ($file) {
        return pathinfo($file, PATHINFO_EXTENSION) === 'sql';
    });

    usort($backupFiles, function ($a, $b) use ($backupDir) {
        return filemtime("$backupDir/$b") - filemtime("$backupDir/$a");
    });

    // Calculate the size and creation date of each backup file
    $backupFilesWithInfo = [];
    foreach ($backupFiles as $file) {
        $filePath = "$backupDir/$file";
        $size = backup_getFileSize($filePath);
        $creationDate = date('Y-m-d H:i:s', filemtime($filePath));
        $backupFilesWithInfo[] = [
            'file' => $file,
            'size' => $size,
            'creation_date' => $creationDate
        ];
    }

    $ui->assign('csrf_token', Csrf::generateAndStoreToken());
    $ui->assign('backupFiles', $backupFilesWithInfo);
    $ui->display('backup.tpl');
}

function backup_getFileSize($filePath)
{
    $size = filesize($filePath);

    if ($size === false) {
        return 'Unable to determine file size.';
    }

    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $index = 0;

    while ($size >= 1024 && $index < count($units) - 1) {
        $size /= 1024;
        $index++;
    }

    return round($size, 2) . ' ' . $units[$index];
}

function backup_add($is_CLi = false)
{
    global $UPLOAD_PATH, $root_path;
    include "{$root_path}config.php";
    $backupDir = "$UPLOAD_PATH/backup";
    if (!$is_CLi) {
        _admin();
        $admin = Admin::_info();
        if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
            _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
            exit;
        }

        if (isset($_POST['createBackup'])) {
            $csrf_token = _post('csrf_token');
            if (!Csrf::check($csrf_token)) {
                r2(U . 'plugin/backup_list', 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
            }
            $backupFile = $backupDir . '/backup_' . date('Y-m-d_H-i-s') . '.sql';

            $command = "mysqldump --user={$db_user} --password={$db_pass} --host={$db_host} {$db_name} --result-file={$backupFile} 2>&1";
            $output = shell_exec($command);
            if (file_exists($backupFile)) {
                r2(U . 'plugin/backup_list', 's', Lang::T("Database backup created successfully."));
            } else {
                // Log the error
                _log(Lang::T("Error creating backup: ") . $output);
                sendTelegram(Lang::T("Error creating backup: ") . $output);
                r2(U . 'plugin/backup_list', 'e', Lang::T("Error creating database backup. Check the log for details."));
            }
        } else {
            r2(U . 'plugin/backup_list', 'e', Lang::T("Invalid request method."));
        }
    } else {
        // CLI mode
        $backupFile = $backupDir . '/backup_' . date('Y-m-d_H-i-s') . '.sql';
        $command = "mysqldump --user={$db_user} --password={$db_pass} --host={$db_host} {$db_name} --result-file={$backupFile} 2>&1";
        $output = shell_exec($command);
        if (file_exists($backupFile)) {
            return true;
        } else {
            // Log the error
            _log(Lang::T("Error creating backup: ") . $output);
            sendTelegram(Lang::T("Error creating backup: ") . $output);
            echo "Error creating database backup. Check the log for details.\n\n";
            return false;
        }
    }
}

function backup_download()
{
    global $UPLOAD_PATH;
    _admin();
    $admin = Admin::_info();
    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        exit;
    }
    if (!empty($_GET['file'])) {

        $csrf_token = $_GET['token'];
        if (!Csrf::check($csrf_token)) {
            r2(U . 'plugin/backup_list', 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $fileName = basename($_GET['file']);
        $backupDir = "$UPLOAD_PATH/backup";
        $filePath = "$backupDir/$fileName";

        if (!empty($fileName) && file_exists($filePath)) {

            header("Cache-Control: public");
            header("Content-Description: File Transfer");
            header("Content-Disposition: attachment; filename=$fileName");
            header("Content-Type: application/zip");
            header("Content-Transfer-Encoding: binary");

            readfile($filePath);
            exit;
        } else {
            r2(U . 'plugin/backup_list', 'e', Lang::T("The file does not exist."));
        }
    }
}

function backup_delete()
{
    global $UPLOAD_PATH;
    _admin();
    $admin = Admin::_info();
    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        exit;
    }
    include "config.php";
    $backupDir = "$UPLOAD_PATH/backup";
    if (isset($_GET['file'])) {
        $csrf_token = $_GET['token'];
        if (!Csrf::check($csrf_token)) {
            r2(U . 'plugin/backup_list', 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $fileName = basename($_GET['file']);
        $filePath = "$backupDir/$fileName";

        if (file_exists($filePath) && strpos(realpath($filePath), realpath($backupDir)) === 0) {

            if (unlink($filePath)) {
                r2(U . 'plugin/backup_list', 's', Lang::T("Backup file deleted successfully."));
            } else {
                r2(U . 'plugin/backup_list', 'e', Lang::T("Error deleting backup file. Could not unlink the file."));
            }
        } else {
            r2(U . 'plugin/backup_list', 'e', Lang::T("Backup file does not exist or is not in the backup directory."));
        }
    } else {
        r2(U . 'plugin/backup_list', 'e', Lang::T("No file specified for deletion."));
    }
}
function backup_restore()
{
    global $UPLOAD_PATH;

    $backupDir = "$UPLOAD_PATH/backup";
    _admin();
    $admin = Admin::_info();
    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        exit;
    }
    include "config.php";
    if (isset($_GET['file'])) {
        $csrf_token = $_GET['token'];
        if (!Csrf::check($csrf_token)) {
            r2(U . 'plugin/backup_list', 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        $fileName = $_GET['file'];
        $fileName = basename($fileName);
        $filePath = "$backupDir/$fileName";

        if (!preg_match('/\.sql$/', $fileName)) {
            r2(U . 'plugin/backup_list', 'e', 'Invalid backup file format.');
            return;
        }

        if (file_exists($filePath) && strpos(realpath($filePath), realpath($backupDir)) === 0) {
            // Capture both output and error from shell command
            $command = "mysql --user={$db_user} --password={$db_pass} --host={$db_host} {$db_name} < {$filePath} 2>&1";
            $output = @shell_exec($command);

            if ($output === null) {
                r2(U . 'plugin/backup_list', 's', 'Database restored successfully.');
            } else {
                _log('backup_restore: Error restoring database - ' . htmlspecialchars($output));
                r2(U . 'plugin/backup_list', 'e', 'Error restoring the database: ' . htmlspecialchars($output));
            }
        } else {
            r2(U . 'plugin/backup_list', 'e', 'Backup file not found.');
        }
    } else {
        r2(U . 'plugin/backup_list', 'e', 'No backup file specified.');
    }
}


function backup_settingsPost()
{
    $admin = Admin::_info();
    if (_post('save') == 'save') {
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(U . 'plugin/backup_list', 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }
        if (isset($_POST['backup_retain_count']) && $_POST['backup_clear_old'] == '1') {
            $retainCount = $_POST['backup_retain_count'];

            if (empty($retainCount) || !is_numeric($retainCount) || $retainCount < 1) {
                r2(U . 'plugin/backup_list', 'e', 'Backup Retention Count cannot be empty and must be greater than 0');
                return;
            }
        }
        $settings = [
            'backup_auto' => $_POST['backup_auto'] ? 1 : 0,
            'backup_clear_old' => $_POST['backup_clear_old'] ? 1 : 0,
            'backup_backup_time' => $_POST['backup_backup_time'],
            'backup_retain_count' => $_POST['backup_retain_count']
        ];

        // Update or insert settings in the database
        foreach ($settings as $key => $value) {
            $d = ORM::for_table('tbl_appconfig')->where('setting', $key)->find_one();
            if ($d) {
                $d->value = $value;
                $d->save();
            } else {
                $d = ORM::for_table('tbl_appconfig')->create();
                $d->setting = $key;
                $d->value = $value;
                $d->save();
            }
        }
        _log('[' . $admin['username'] . ']: ' . Lang::T('Settings Saved Successfully'), $admin['user_type']);
        r2(U . 'plugin/backup_list', 's',  Lang::T('Settings Saved Successfully'));
    }
}
function backup_cron()
{
    global $config, $UPLOAD_PATH;

    $backupDir = "$UPLOAD_PATH/backup";
    $lastBackupFile = "$backupDir/last_backup_time.txt";

    if (!isset($config['backup_auto']) || !$config['backup_auto']) {
        return;
    }

    // Ensure backup directory exists and is writable
    if (!is_dir($backupDir)) {
        if (!mkdir($backupDir, 0755, true)) {
            _log(Lang::T("Failed to create backup directory: $backupDir"));
            sendTelegram(Lang::T("Failed to create backup directory: $backupDir"));
            echo Lang::T("Failed to create backup directory: $backupDir\n\n");
            return;
        }
    }

    if (!is_writable($backupDir)) {
        _log(Lang::T("Backup directory is not writable: $backupDir"));
        sendTelegram(Lang::T("Backup directory is not writable: $backupDir"));
        echo Lang::T("Backup directory is not writable: $backupDir\n\n");
        return;
    }

    // Get or create last backup time
    $lastBackupTime = 0;
    if (file_exists($lastBackupFile)) {
        $lastBackupTime = (int) file_get_contents($lastBackupFile);
    }

    $currentTime = time();
    $lastBackupDate = date('Y-m-d', $lastBackupTime ?: 0);
    $currentDate = date('Y-m-d');

    $shouldBackup = false;
    $backupType = '';

    switch ($config['backup_backup_time']) {
        case 'everyday':
            // Check if last backup was not today
            if ($lastBackupDate !== $currentDate) {
                $shouldBackup = true;
                $backupType = 'Daily';
            }
            break;

        case 'everyweek':
            // Check if it's been at least 7 days since last backup
            if ((!$lastBackupTime || ($currentTime - $lastBackupTime) >= 7 * 24 * 3600) && date('w') == 0) {
                $shouldBackup = true;
                $backupType = 'Weekly';
            }
            break;

        case 'everymonth':
            // Check if it's first day of month and no backup was made today
            if (date('j') == 1 && $lastBackupDate !== $currentDate) {
                $shouldBackup = true;
                $backupType = 'Monthly';
            }
            break;
    }

    // Perform backup if conditions are met
    if ($shouldBackup) {
        _log(Lang::T("Initiating $backupType backup"));
        sendTelegram(Lang::T("Initiating $backupType backup"));
        echo Lang::T("Initiating $backupType backup\n\n");

        try {
            if (!backup_add(true)) {
                throw new Exception('Backup failed');
            }
            file_put_contents($lastBackupFile, $currentTime);
            _log(Lang::T("$backupType backup completed successfully"));
            sendTelegram(Lang::T("$backupType backup completed successfully"));
            echo Lang::T("Backup completed successfully\n\n");
        } catch (Exception $e) {
            _log(Lang::T("Backup failed: ") . $e->getMessage());
            sendTelegram(Lang::T("Backup failed: ") . $e->getMessage());
            echo Lang::T("Backup failed: ") . $e->getMessage() . "\n\n";
        }
    } else {
        echo Lang::T("No backup needed at this time. Last backup: $lastBackupDate\n\n");
    }

    // Handle old backup cleanup
    if (!empty($config['backup_clear_old'])) {
        $retainCount = isset($config['backup_retain_count']) ? (int)$config['backup_retain_count'] : 5;
        $files = glob("$backupDir/*.sql");

        if ($files === false) {
            echo Lang::T("Failed to list backup files\n\n");
            return;
        }

        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        if (count($files) > $retainCount) {
            $filesToDelete = array_slice($files, $retainCount);
            foreach ($filesToDelete as $file) {
                if (@unlink($file)) {
                    _log(Lang::T("Deleted old backup file: ") . basename($file));
                    sendTelegram(Lang::T("Deleted old backup file: ") . basename($file));
                } else {
                    _log(Lang::T("Failed to delete old backup file: ") . basename($file));
                }
            }
        }
    }
}


function backup_upload_form()
{
    global $config, $UPLOAD_PATH;
    _admin();
    $admin = Admin::_info();
    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        exit;
    }
    $upload_path = "$UPLOAD_PATH/backup";
    if (isset($_FILES['file'])) {
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            r2(U . 'plugin/backup_list', 'e', Lang::T('Invalid or Expired CSRF Token') . ".");
        }

        // Check for upload errors
        if ($_FILES['file']['error'] != UPLOAD_ERR_OK) {
            r2(U . 'plugin/backup_list', 'e', Lang::T('No file selected'));
            return;
        }
        $file_name = $_FILES['file']['name'];
        $file_size = $_FILES['file']['size'];
        $file_tmp = $_FILES['file']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['sql'];
        $allowed_size = 1024 * 1024 * 50; // 50 MB
        $new_file_name = 'backup_' . date('Y-m-d_H-i-s') . '.' . $file_ext;
        if ($file_size > $allowed_size) {
            r2(U . 'plugin/backup_list', 'e', Lang::T('File size is too large. Maximum allowed size is 50MB'));
            exit;
        } elseif (!in_array($file_ext, $allowed_extensions)) {
            r2(U . 'plugin/backup_list', 'e', Lang::T('Invalid file type. Only SQL files are allowed'));
            exit;
        } else {
            if (move_uploaded_file($file_tmp, "$upload_path/$new_file_name")) {
                r2(U . 'plugin/backup_list', 's', Lang::T('File uploaded successfully'));
            } else {
                r2(U . 'plugin/backup_list', 'e', Lang::T('Failed to upload file'));
            }
        }
    } else {
        _alert(Lang::T('No file selected'), 'danger', "plugin/backup_list");
    }
}

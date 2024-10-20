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

    $backupDir = File::pathFixer("backup");
    if (!file_exists($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    $backupFiles = scandir($backupDir);
    $backupFiles = array_diff($backupFiles, ['..', '.']);

    usort($backupFiles, function ($a, $b) use ($backupDir) {
        return filemtime("$backupDir/$b") - filemtime("$backupDir/$a");
    });

    // Calculate the size and creation date of each backup file
    $backupFilesWithInfo = [];
    foreach ($backupFiles as $file) {
        $filePath = "$backupDir/$file";
        $size = getFileSize($filePath);
        $creationDate = date('Y-m-d H:i:s', filemtime($filePath));
        $backupFilesWithInfo[] = [
            'file' => $file,
            'size' => $size,
            'creation_date' => $creationDate
        ];
    }

    $ui->assign('backupFiles', $backupFilesWithInfo);
    $ui->display('backup.tpl');
}

function getFileSize($filePath)
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

function backup_add()
{
    _admin();
    $admin = Admin::_info();
    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        exit;
    }
    include "config.php";
    $backupDir = File::pathFixer("backup");
    if (isset($_POST['createBackup']) || isset($_GET['auto'])) {
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
    }
}

function backup_download()
{
    _admin();
    $admin = Admin::_info();
    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        exit;
    }
    if (!empty($_GET['file'])) {

        $fileName = basename($_GET['file']);
        $filePath = "backup/$fileName";

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
    _admin();
    $admin = Admin::_info();
    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        exit;
    }
    include "config.php";
    $backupDir = File::pathFixer("backup");

    if (isset($_GET['file'])) {
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
    _admin();
    $admin = Admin::_info();
    if (!in_array($admin['user_type'], ['SuperAdmin', 'Admin'])) {
        _alert(Lang::T('You do not have permission to access this page'), 'danger', "dashboard");
        exit;
    }
    include "config.php";
    if (isset($_GET['file'])) {
        $fileName = $_GET['file'];
        $backupDir = File::pathFixer("backup");
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

        $settings = [
            'backup_auto' => $_POST['backup_auto'] ? 1 : 0,
            'backup_clear_old' => $_POST['backup_clear_old'] ? 1 : 0,
            'backup_backup_time' => $_POST['backup_backup_time']
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
    global $config;

    $backupDir = '../backup';
    if ($config['backup_auto']) {
        if (!is_dir($backupDir)) {
            _log(Lang::T("Backup directory does not exist: $backupDir"));
            sendTelegram(Lang::T("Backup directory does not exist: $backupDir"));
            return;
        }

        if (!is_writable($backupDir)) {
            _log(Lang::T("Backup directory is not writable: $backupDir"));
            sendTelegram(Lang::T("Backup directory is not writable: $backupDir"));
            return;
        }

        // Daily backup
        if ($config['backup_backup_time'] == 'everyday' && (int) date('H') === 0) {
            backup_add();
            _log(Lang::T("backup_cron: Daily backup initiated."));
            sendTelegram(Lang::T("backup_cron: Daily backup initiated."));
        }

        // Weekly backup
        elseif ($config['backup_backup_time'] == 'everyweek' && (int) date('w') === 0 && (int) date('H') === 0) {
            backup_add();
            _log(Lang::T("backup_cron: Weekly backup initiated."));
            sendTelegram(Lang::T("backup_cron: Weekly backup initiated."));
        }

        // Monthly backup
        elseif ($config['backup_backup_time'] == 'everymonth' && (int) date('j') === 1 && (int) date('H') === 0) {
            backup_add();
            _log(Lang::T("backup_cron: Monthly backup initiated."));
            sendTelegram(Lang::T("backup_cron: Monthly backup initiated."));
        }

        _log(Lang::T("backup_cron: Backup cron job executed successfully."));
    }

    if ($config['backup_clear_old']) {
        $retainCount = $config['backup_retain_count'] ?? 5;
        $files = glob("$backupDir/*");

        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        if (count($files) > $retainCount) {
            $filesToDelete = array_slice($files, $retainCount);
            foreach ($filesToDelete as $file) {
                if (@unlink($file)) {
                    _log(Lang::T("backup_cron: Deleted old backup file: $file"));
                    sendTelegram(Lang::T("backup_cron: Deleted old backup file: $file"));
                } else {
                    _log(Lang::T("backup_cron: Failed to delete old backup file: $file"));
                }
            }
        }
        _log(Lang::T("backup_cron: Retained only the latest $retainCount backup files."));
    }

    _log(Lang::T("backup_cron: Backup cron job completed successfully."));
}

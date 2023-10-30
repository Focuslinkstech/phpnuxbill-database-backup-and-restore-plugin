<?php
register_menu("Backup/Restore DB", true, "backup_list", 'SETTINGS', '');

if ($_SERVER['REQUEST_URI'] === '/plugin/backup_list') {
    backup_list();
} elseif ($_SERVER['REQUEST_URI'] === '/plugin/backup_download') {
    //$file = $_GET['file'] ?? '';
    backup_download();
} elseif ($_SERVER['REQUEST_URI'] === '/plugin/backup_delete') {
  //  $file = $_GET['file'] ?? '';
    backup_delete();
}elseif ($_SERVER['REQUEST_URI'] === '/plugin/backup_add') {
  //  $file = $_GET['file'] ?? '';
    backup_add();
}elseif ($_SERVER['REQUEST_URI'] === '/plugin/backup_restore') {
  //  $file = $_GET['file'] ?? '';
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

    $backupDir = File::pathFixer("backup");

    // Check if the backup directory exists, if not, create it
    if (!file_exists($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    // Get a list of backup files in the directory
    $backupFiles = scandir($backupDir);
    $backupFiles = array_diff($backupFiles, array('..', '.')); // Exclude parent and current directory entries

    // Sort the backup files by modification time, in descending order
    usort($backupFiles, function ($a, $b) use ($backupDir) {
        return filemtime($backupDir . '/' . $b) - filemtime($backupDir . '/' . $a);
    });

    // Calculate the size and creation date of each backup file
    $backupFilesWithInfo = [];
    foreach ($backupFiles as $file) {
        $filePath = $backupDir . '/' . $file;
        $size = getFileSize($filePath);
        $creationDate = date('Y-m-d H:i:s', filemtime($filePath));
        $backupFilesWithInfo[] = [
            'file' => $file,
            'size' => $size,
            'creation_date' => $creationDate
        ];
    }

    // Assign variables for Smarty
    $ui->assign('backupFiles', $backupFilesWithInfo);

    // Display the template
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
  require('config.php');
  $backupDir = File::pathFixer("backup");
  // Check if the "Create Backup" button is clicked or it's an automatic backup
  if (isset($_POST['createBackup']) || isset($_GET['auto'])) {
      // Perform the backup query
      $backupFile = $backupDir . '/backup_' . date('Y-m-d_H-i-s') . '.sql';

      // Execute the mysqldump command
      @shell_exec("mysqldump --user={$db_user} --password={$db_password} --host={$db_host} {$db_name} --result-file={$backupFile}");

      if (file_exists($backupFile)) {
        r2(U . 'plugin/backup_list', 's', Lang::T("Database backup created successfully."));
      } else {
        r2(U . 'plugin/backup_list', 'e', Lang::T("Error creating database backup."));
      }
  }
}

function backup_download()
{
  if(!empty($_GET['file'])){
    // Define file name and path
    $fileName = basename($_GET['file']);
    $filePath = 'backup/'.$fileName;

    if(!empty($fileName) && file_exists($filePath)){
        // Define headers
        header("Cache-Control: public");
        header("Content-Description: File Transfer");
        header("Content-Disposition: attachment; filename=$fileName");
        header("Content-Type: application/zip");
        header("Content-Transfer-Encoding: binary");

        // Read the file
        readfile($filePath);
        exit;
    }else{
        r2(U . 'plugin/backup_list', 'e', Lang::T("The file does not exist."));
    }
}

}

function backup_delete()
{
    // Verify that the file exists and is within the backup directory
    $fileName = basename($_GET['file']);
    $filePath = 'backup/'.$fileName;
    if (file_exists($filePath) && strpos(realpath($filePath), realpath($backupDir)) === 0) {
        // Delete the file
        unlink($filePath);
        r2(U . 'plugin/backup_list', 's', Lang::T("Backup file deleted successfully."));
    } else {
        r2(U . 'plugin/backup_list', 'e', Lang::T("Error deleting backup file."));
    }
}

function backup_restore()
{
    require('config.php');
    $backupDir = File::pathFixer("backup");

    // Verify that the file exists and is within the backup directory
    $filePath = $backupDir . '/' . $fileName;
    if (file_exists($filePath) && strpos(realpath($filePath), realpath($backupDir)) === 0) {
        // Execute the mysql command to restore the database
        $command = "mysql --user={$db_user} --password={$db_password} --host={$db_host} {$db_name} < {$filePath}";
        $output = @shell_exec($command);

        if ($output === null) {
            r2(U . 'plugin/backup_list', 's', 'Database restored successfully.');
        } else {
            r2(U . 'plugin/backup_list', 'e', 'Error restoring the database.');
        }
    } else {
        r2(U . 'plugin/backup_list', 'e', 'Backup file not found.');
    }
}

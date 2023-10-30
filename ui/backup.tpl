{include file="sections/header.tpl"}


<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">{Lang::T('Backup Database')}</div>
            <div class="panel-body">
                <div class="md-whiteframe-z1 mb20 text-center" style="padding: 15px">
                    <div class="col-md-8">
                        <form id="site-search" method="post" action="{$_url}plugin/backup_list">
                            <div class="input-group">
                                <input type="text" name="search" value="{$search}" class="form-control"
                                    placeholder="{Lang::T('Search')}...">
                                <div class="input-group-btn">
                                    <button class="btn btn-success" type="submit"><span
                                            class="fa fa-search"></span></button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-4">
                      <form method="POST" action="{$_url}plugin/backup_add">
                          <input class="btn btn-primary btn-block waves-effect" type="submit" name="createBackup" value="Create Backup">
                      </form>
                    </div>&nbsp;
                </div>
                <div class="table-responsive">
                  {if empty($backupFiles)}
                     <p align="center"><b>{Lang::T('Backup not found.')}</b></p>
                     {else}
                    <table class="table table-bordered table-striped table-condensed">
                        <thead>
                            <tr>
                              <th>{Lang::T('Backup File')}</th>
                              <th>{Lang::T('Date')}</th>
                              <th>{Lang::T('Size')}</th>
                              <th>{Lang::T('Action')}</th>
                            </tr>
                        </thead>
                        <tbody>

                        {foreach $backupFiles as $backup}
                                <tr>
                                  <td>{$backup.file}</td>
                                  <td>{$backup.creation_date}</td>
                                  <td>{$backup.size}</td>
                                    <td align="center">
                                            <a href="{$_url}plugin/backup_download&file={$backup.file}" style="margin: 0px;" class="btn btn-success btn-xs">{Lang::T('Download')}</a>
                                            <a href="{$_url}plugin/backup_restore&file={$backup.file}" style="margin: 0px;" onclick="return confirm('{Lang::T('Are you Sure you want to Restore this Database?')}')" class="btn btn-primary btn-xs">{Lang::T('Restore')}</a>
                                            <a href="{$_url}plugin/backup_delete&file={$backup.file}" style="margin: 0px;" onclick="return confirm('{Lang::T('Are you Sure you want to Delete this Database?')}')" class="btn btn-danger btn-xs">{Lang::T('Delete')}</a>
                                    </td>
                                </tr>
                            {/foreach}
                            {/if}

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<pre>
<b>Note:</b>
# Don't forget that i haven't test the automatic backup feature. but i have the plan to add the feature.
# Make sure your server support shell_exec function, else you may get errors while creating database backup.

# To set up the automatic backup, you need to configure a cron job on your server.
# The cron job should execute the backup.php script at the desired interval.
# Here's an example of a cron job entry that runs the script every day at 1 AM:

0 1 * * * php /path/to/backup.php auto >/dev/null 2>&1
</pre>

{include file="sections/footer.tpl"}

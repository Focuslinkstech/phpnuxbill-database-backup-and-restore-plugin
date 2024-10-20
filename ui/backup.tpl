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
                            <input class="btn btn-primary btn-block waves-effect" type="submit" name="createBackup"
                                value="Create Backup">
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
                                    <a href="{$_url}plugin/backup_download&file={$backup.file}" style="margin: 0px;"
                                        class="btn btn-success btn-xs">{Lang::T('Download')}</a>
                                    <a href="{$_url}plugin/backup_restore&file={$backup.file}" style="margin: 0px;"
                                        onclick="return confirm('{Lang::T('Are you Sure you want to Restore this Database?')}')"
                                        class="btn btn-primary btn-xs">{Lang::T('Restore')}</a>
                                    <a href="{$_url}plugin/backup_delete&file={$backup.file}" style="margin: 0px;"
                                        onclick="return confirm('{Lang::T('Are you Sure you want to Delete this Database?')}')"
                                        class="btn btn-danger btn-xs">{Lang::T('Delete')}</a>
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

<form class="form-horizontal" method="post" role="form" action="{$_url}plugin/backup_settingsPost">
    <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="panel panel-primary panel-hovered panel-stacked mb30">
                <div class="panel-heading">{Lang::T('Backup Settings')}</div>
                <div class="panel-body">
                    <div class="form-group col-6">
                        <label class="col-md-3 control-label">{Lang::T('Auto Backup')}</label>
                        <div class="col-md-6">
                            <label class="switch">
                                <input type="checkbox" id="backup_auto" value="1" name="backup_auto" {if
                                    $_c['backup_auto']==1}checked{/if}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group col-6">
                        <label class="col-md-3 control-label">{Lang::T('Auto Clear Old Backup')}</label>
                        <div class="col-md-6">
                            <label class="switch">
                                <input type="checkbox" id="backup_clear_old" value="1" name="backup_clear_old" {if
                                    $_c['backup_clear_old']==1}checked{/if}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group col-6">
                        <label class="col-md-3 control-label">{Lang::T('Choose Backup Frequency')}</label>
                        <div class="col-md-6">
                            <select class="form-control" name="backup_backup_time" id="backup_backup_time">
                                <option value="everyday" {if $_c['backup_backup_time']=='everyday' }selected{/if}>
                                    {Lang::T('Everyday')}</option>
                                <option value="everyweek" {if $_c['backup_backup_time']=='everyweek' }selected{/if}>
                                    {Lang::T('Everyweek')}
                                </option>
                                <option value="everymonth" {if $_c['backup_backup_time']=='everymonth' }selected{/if}>
                                    {Lang::T('Everymonth')}</option>
                            </select>
                            <small class="form-text text-muted">
                                <font color="red"></font> {Lang::T('Backup occurs at 00:00 Hrs')}
                            </small>
                        </div>
                    </div>
                    <div class="form-group col-6">
                        <div class="col-lg-offset-3 col-lg-10">
                            <button class="btn btn-primary waves-effect waves-light" name="save" value="save"
                                type="submit">Save Changes</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<div class="bs-callout bs-callout-info" id="callout-navbar-role">
    <h4><b>Note</b>:</h4>
    <p>
        Make sure your server support shell_exec function, else you may get errors while creating database backup. <br>
    </p>
</div>
{include file="sections/footer.tpl"}
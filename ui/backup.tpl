{include file="sections/header.tpl"}


<div class="row">
    <div class="col-sm-12">
        <div class="panel panel-hovered mb20 panel-primary">
            <div class="panel-heading">{Lang::T('Backup Database')}</div>
            <div class="panel-body">
                <div class="md-whiteframe-z1 mb20 text-center" style="padding: 15px">
                    <div class="col-md-8">
                        <form method="post" action="{$_url}plugin/backup_upload_form" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="{$csrf_token}">
                            <div class="input-group">
                                <input class="form-control" type="file" name="file" accept="application/*.sql">
                                <div class="input-group-btn">
                                    <button class="btn btn-success" type="submit"><span class="fa fa-upload">
                                        </span> {Lang::T('Upload')}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <form method="POST" action="{$_url}plugin/backup_add">
                            <input type="hidden" name="csrf_token" value="{$csrf_token}">
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
                                    <a href="{$_url}plugin/backup_download&file={$backup.file}&token={$csrf_token}"
                                        style="margin: 0px;" class="btn btn-success btn-xs">{Lang::T('Download')}</a>
                                    <a href="{$_url}plugin/backup_restore&file={$backup.file}&token={$csrf_token}"
                                        style="margin: 0px;"
                                        onclick="return confirm('{Lang::T('Are you Sure you want to Restore this Database?')}')"
                                        class="btn btn-primary btn-xs">{Lang::T('Restore')}</a>
                                    <a href="{$_url}plugin/backup_delete&file={$backup.file}&token={$csrf_token}"
                                        style="margin: 0px;"
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
    <input type="hidden" name="csrf_token" value="{$csrf_token}">
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
                                    $_c['backup_auto']==1}checked{/if} onchange="toggleBackupFrequency()">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    <div id="backup_frequency_section" style="display: {if $_c['backup_auto']==1}block{else}none{/if};">
                        <div class="form-group col-6">
                            <label class="col-md-3 control-label">{Lang::T('Choose Backup Frequency')}</label>
                            <div class="col-md-6">
                                <select class="form-control" name="backup_backup_time" id="backup_backup_time">
                                    <option value="everyday" {if $_c['backup_backup_time']=='everyday' }selected{/if}>
                                        {Lang::T('Everyday')}</option>
                                    <option value="everyweek" {if $_c['backup_backup_time']=='everyweek' }selected{/if}>
                                        {Lang::T('Everyweek')}</option>
                                    <option value="everymonth" {if $_c['backup_backup_time']=='everymonth'
                                        }selected{/if}>
                                        {Lang::T('Everymonth')}</option>
                                </select>
                                <small class="form-text text-muted">
                                    <font color="red"></font> {Lang::T('Backup occurs at 00:00 Hrs')}
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group col-6">
                        <label class="col-md-3 control-label">{Lang::T('Auto Clear Old Backup')}</label>
                        <div class="col-md-6">
                            <label class="switch">
                                <input type="checkbox" id="backup_clear_old" value="1" name="backup_clear_old" {if
                                    $_c['backup_clear_old']==1}checked{/if} onchange="toggleRetainCount()">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    <div id="retain_count_section" style="display: {if $_c['backup_clear_old']==1}block{else}none{/if};"
                        class="form-group col-6">
                        <label class="col-md-3 control-label">{Lang::T('Backup Retain Count')}</label>
                        <div class="col-md-6">
                            <input type="number" class="form-control" id="backup_retain_count"
                                name="backup_retain_count" placeholder="5" value="{$_c['backup_retain_count']}">
                            <small class="form-text text-muted">
                                <font color="red"></font> {Lang::T('Retain count must be greater than 0, if you enable
                                auto clear old backup.')}
                            </small>
                        </div>
                    </div>

                    <div class="form-group col-6">
                        <label class="col-md-3 control-label">{Lang::T('Cloud Upload')}</label>
                        <div class="col-md-6">
                            <label class="switch">
                                <input type="checkbox" id="cloud_upload" value="1" name="cloud_upload" {if
                                    $_c['cloud_upload']==1}checked{/if} onchange="toggleCloudFields()">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>

                    <div id="dropbox_fields" style="display: {if $_c['cloud_upload']==1}block{else}none{/if};">
                        <div class="form-group col-6">
                            <label for="backup_dropbox_token" class="col-md-3 control-label">{Lang::T('Dropbox Access
                                Token')}</label>
                            <div class="col-md-6">
                                <input type="password" class="form-control" id="backup_dropbox_token"
                                    name="backup_dropbox_token"
                                    placeholder="XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX"
                                    value="{$_c['backup_dropbox_token']}">
                                <small class="form-text text-muted">
                                    <font color="red"></font> {Lang::T('Your Dropbox Access Token, get it from your
                                    Dropbox App settings.')} <br>
                                    <a href="https://www.dropbox.com/developers/apps" target="_blank">{Lang::T('Get
                                        Dropbox Access Token')}</a>
                                </small>
                            </div>
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
    <p>
    <h4><b>Note</b>:</h4>
    {Lang::T('Make sure your server support shell_exec function, else you may get errors while creating database
    backup.')} <br> {Lang::T('Auto Clear Old Backup will clear your old backups and leave only 5 recent backups.')}
    </p>
    <p>
    <h4><b>Dropbox Cloud Backup</b>:</h4>
    {Lang::T('Visit:')} <a href="https://www.dropbox.com/developers/apps" target="_blank">Get
        Dropbox Access Token</a>
    <br>
    {Lang::T('Create a')} New App <br>
    {Lang::T('Select')} "Full Access" <br>
    {Lang::T('Goto')} "Permission Tab" <br>
    {Lang::T('In')} "Individual Scopes" <br>
    {Lang::T('Under')} "Files and folders"<br>
    {Lang::T('Select')}: "files.content.write" and "files.content.read"<br>
    {Lang::T('Click on')} Submit <br>
    {Lang::T('Goto')} "Settings Tab" <br>
    {Lang::T('Generate')} "new access token" <br>
    {Lang::T('Copy the generated')} "access token" <br>
    {Lang::T('Paste the access token in the input field above and click on "Save Changes"')}
    </p>
</div>

<script>
    function toggleBackupFrequency() {
        const autoBackupCheckbox = document.getElementById('backup_auto');
        const backupFrequencySection = document.getElementById('backup_frequency_section');
        backupFrequencySection.style.display = autoBackupCheckbox.checked ? 'block' : 'none';
    }

    function toggleRetainCount() {
        const autoClearCheckbox = document.getElementById('backup_clear_old');
        const retainCountSection = document.getElementById('retain_count_section');
        retainCountSection.style.display = autoClearCheckbox.checked ? 'block' : 'none';
    }

    function toggleCloudFields() {
        const cloudUploadCheckbox = document.getElementById('cloud_upload');
        const dropBoxFields = document.getElementById('dropbox_fields');
        if (cloudUploadCheckbox.checked) {
            dropBoxFields.style.display = 'block';
        } else {
            dropBoxFields.style.display = 'none';
        }
    }
    toggleBackupFrequency();
    toggleRetainCount();
    toggleCloudFields();
</script>
{include file="sections/footer.tpl"}
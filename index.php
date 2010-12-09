<?php

/**
 * Tiny dropbox (not THAT dropbox ;) script
 * ----------------------------------------
 * Written by Einar Lielmanis.
 * Released under the MIT license,
 * http://www.opensource.org/licenses/mit-license.php
 * Hack away! Bugs, thanks, suggestions: einar@spicausis.lv
 *
 * What is this?
 *
 * This is a small script intended to give my friends/colleagues an easy
 * way to send me their files.
 *
 * Surprisingly, there aren't many ways one can send a bunch of files to
 * other without too much hassle: even simple ftp upload often is out of
 * reach ("first, install the filezilla, ..", yeah right).
 *
 * So, unless the files can be sent by skype or by email, I can put this
 * folder on my webserver and give other parties its address. They will be
 * able to upload their files directly, using just a browser.
 *
 *
 * Requirements
 *
 * If it runs PHP, it will probably run tiny dropbox as well, regardless of
 * the setup: the script is quite flexible.
 *
 *
 * Installation
 *
 * Put the file into any folder of your web hosting provider.
 * By visiting the page the first time, you will automatically be logged in
 * as owner and will be able to customize language, password, etc.
 *
 * If the script is unable to create the storage folder by itself, it will
 * complain; in that case you will need to create the folder "files" and
 * assign sufficient permissions manually.
 *
 *
 * Specific customizations
 *
 * I doubt that you will need taht, but to allow your customizations
 * together with simple upgrades of this script, you can create a file
 * called config.php and store any overrides there.
 * Here is an example of the file:
 *
 * <?php
 * $g_storage_folder = '/var/storage';
 * ?>
 *
 * You can add your own interface languages (via add_language() function)
 * or change the upload folder from the custom.php: everything else can be
 * done from the owner settings page.
 *
 * PHP upload limits
 *
 * These values in php.ini limit how large files you will be able to
 * upload:
 *
 * upload_max_filesize
 * post_max_filesize
 * memory_limit
 *
 **/

 # all uploaded files, as well as configuration, will be stored here.
$g_storage_folder = 'files';

if (file_exists('config.php')) {
    require_once('config.php');
}

#
# /// page actions
#
function process_action($action)
{
    $handlers = array(
        ''                   => 'on_page_index',
        'owner-login'        => 'on_owner_login',
        'owner-logout'       => 'on_owner_logout',

        'upload'             => 'on_upload',
        'flash-upload'       => 'on_flash_upload',
        'delete'             => 'on_delete',
        'download'           => 'on_download',
        'save-edit'          => 'on_save_edit',
        'config'             => 'on_config',

        'default_stylesheet' => 'on_default_stylesheet',
        'background_image'   => 'on_background_image',
    );

    if ( ! isset($handlers[$action])) {
        // 404 would probably be better, but defaulting to index doesn't hurt
        $action = null;
    }

    $is_storage_folder_available = verify_storage_folder();

    if ( ! $is_storage_folder_available && $action != 'default_stylesheet' && $action != 'background_image') {
        // hijack all actions except stylesheet, until the setup is not deemed to be good
        on_setup_required();
    } else {
        call_user_func($handlers[$action]);
    }

}



function on_default_stylesheet()
{
    header('Content-Type: text/css; charset=utf-8');
    etag_last_modified(filemtime(__FILE__));

    echo <<<CSS
* {
    margin: 0;
    padding: 0;
}
body {
    background: #ccb url(?action=background_image) repeat;
}
body, html {
    height: 100%;
    font-family: arial, sans-serif;
}
div#wrapper {
    width: 920px;
    min-height: 100%;
    padding: 0 20px 0 20px;
    height: auto !important;
    height: 100%;
    margin: 0 auto -30px auto;
}
div#footer {
    width: 960px;
    margin: 0 auto;
    font-size: 12px;
    color: #999;
    text-align: right;
}
div.push, div#footer {
    height: 30px;
}
div#wrapper, div#footer {
    border-left: 1px solid #333;
    border-right: 1px solid #333;
    background: white;
}
div#footer p {
    margin: 0 20px;
    padding-top: 8px;
    padding-right: 10px;
    border-top: 1px solid #ccb;
}
div#footer a {
    color: #999;
}
div#footer a.owner {
    color: #545142;
}
div#footer a:hover {
    color: #545142;
}
h1 {
    color: #f03;
    font-weight: normal;
    font-size: 20px;
    letter-spacing: -1px;
    padding: 30px 0 4px 4px;
    border-bottom: 1px solid #f03;
    margin-bottom: 20px;
}
h1 a {
    text-decoration: none;
    color: #f03;
}
div.file {
    border-top: 3px solid #ddc;
    margin: 10px 0;
    padding: 10px 20px 10px 20px;
    background-color: #f4f4ef
}
div.form {
    background-color: #f0f0ec;
    border: 1px solid #ccb;
    border-top: 3px solid #ccb;
    padding-bottom: 20px;
}
div.file h2 {
    font-size: 20px;
    letter-spacing: -1px;
    font-weight: normal;
    margin-bottom: 10px;
    color: #545142;
}
.owner-login label {
    font-size: 20px;
    letter-spacing: -1px;
    font-weight: normal;
    color: #545142;
    padding-right: 10px;
}
.owner-login button {
    width: 220px;
    padding: 10px 0;
}
.owner-login input {
    padding: 2px;
}
div.file h2 em {
    font-size: 12px;
    font-style: normal;
    font-weight: bold;
    color: #bbb;
}
div#introduction,
div.file div.description {
    color: #777;
    font-size: 12px;
    padding: 0 0 10px 0;
    line-height: 150%;
    width: 700px;
}
div#introduction {
    padding-left: 4px;
}
input, select {
    margin-bottom: 4px;
    font-family: arial, sans-serif;
    font-size: 12px;
    padding: 2px;
}
textarea {
    width: 700px;
    height: 100px;
    padding: 2px;
    font-family: arial, sans-serif;
    font-size: 12px;
    margin-bottom: 4px;
}
button {
    padding: 10px 60px;
    font-weight: normal;
    color: #444;
    cursor: pointer;
    font-family: arial, sans-serif;
    font-size: 12px;
    color: #545142;
}
label {
    font-size: 12px;
    color: #545142;
    display: block;
}
p.error {
    font-size: 12px;
    font-weight: normal;
    color: #f03;
    margin-bottom: 8px;
}
.file ul {
    list-style: none;
    float: right;
    font-size: 12px;
}
.file li {
    display: inline;
    padding-left: 4px;
    color: #777;
}
a {
    color: #545142;
}
a.delete {
    color: #f03;
}
p.success {
    font-size: 20px;
    font-weight: normal;
    margin-bottom: 10px;
    color: #65803A;
}
p.success a {
    font-size: 20px;
    font-weight: normal;
    color: #65803A;
}
.config label {
    width: 150px;
    padding-top: 4px;
    float: left;
    clear: left;
}
.config em {
    color: #777;
    font-style: normal;
    font-size: 12px;
}
.config input {
    width: 300px;
}
.config button {
    margin-left: 150px;
    font-weight: bold;
}
.config #i_password {
    color: #777;
    font-style: italic;
}
.config p {
    margin-left: 150px;
    color: #777;
    font-size: 12px;
    margin-bottom: 12px;
}
.config h2 {
    margin: 20px 0 12px 150px;;
}
#p-stock {
    font-size: 11px;
    padding-top: 10px;
}
#div-stock {
    display: none;
}
CSS;
    exit;
}
function on_owner_login()
{
    if (login_owner(get('password'))) {
        redirect('?');
    }

    draw_html_header();
    draw_owner_login();
    draw_html_footer();
}


function on_owner_logout()
{
    if (is_owner_mode()) {

        $setup = get_setup();
        $setup['owner-session'] = null;
        $setup['owner-ip'] = null;
        save_setup($setup);

    }
    redirect('?');
}


function on_page_index()
{
    if ( ! file_exists(get_setup_file_name())) {
        return on_config();
    }

    draw_html_header();

    remove_stale_upload();

    $show_upload_form = get('action') == 'upload' || get('action') == 'show-form' || sizeof(get_visible_uploads()) == 0;

    if ($show_upload_form) {

        if (sizeof(get_visible_uploads()) == 0) {
            draw_introduction();
        }

        draw_upload_form();
    } else {
        if ( ! is_owner_mode()) {
            draw_success_box();
        } else {
            draw_owner_box();
        }
    }

    draw_visible_uploads();

    draw_html_footer();
}

function on_config()
{
    if ( ! is_owner_mode()) {
        redirect('?');
    }

    global $g_languages;

    $setup = get_setup();

    if (get('save')) {

        $redirect_to = '?';

        $password = get('password');
        if ($password) {
            $setup['password'] = $password;
        }

        $title = get('title');
        if ($title) {
            $setup['title'] = $title;
        }

        $introduction = get('introduction');
        $setup['introduction'] = $introduction;

        $storage_limit_mb = get('storage_limit_mb');
        $storage_limit_mb = str_replace(',', '.', $storage_limit_mb);
        $setup['storage-limit-mb'] = $storage_limit_mb > 0 ? (float)$storage_limit_mb : null;

        $css_customizations = get('css_customizations');
        $setup['css-customizations'] = $css_customizations;

        $language = get('language');
        if (isset($g_languages[$language]) && $language != $setup['language']) {
            $setup['language'] = $language;
            $redirect_to = '?action=config';
        }

        save_setup($setup);
        redirect($redirect_to);
    }

    draw_html_header();


    echo '<div class="file form config">';

    echo '<form method="post" action="?">';

    echo '<input type="hidden" name="action" value="config" />';
    echo '<input type="hidden" name="save" value="yes" />';

    printf('<label for="i_password">%s</label><input id="i_password" name="password" value="%s" /><br />',
        t('LABEL_CFG_PASSWORD'), $setup['password'] == 'master' ? 'master' : null);

    $title = $setup['title'];
    if ( ! $title) {
        $title = t('DEFAULT_TITLE');
    }


    $introduction = $setup['introduction'];
    if ($introduction === null) {
        $introduction = t('DEFAULT_INTRODUCTION');
    }

    printf('<label for="i_title">%s</label><input id="i_title" name="title" value="%s" /><br />',
        t('LABEL_CFG_TITLE'),
        htmlspecialchars($title));

    printf('<label for="i_intro">%s</label><textarea id="i_introduction" rows="5" cols="40" name="introduction">%s</textarea><br />',
        t('LABEL_CFG_INTRODUCTION'),
        htmlspecialchars($introduction));

    printf('<label for="i_language">%s</label>', t('LABEL_CFG_LANGUAGE'));
    echo '<select name="language" id="i_language">';
    foreach($g_languages as $id => $language) {
        $parsed = parse_language($language);
        printf('<option value="%s"%s>%s</option>',
            $id,
            $id == get_site_language() ? ' selected="selected"' : '',
            isset($parsed['LANGUAGE']) ? htmlspecialchars($parsed['LANGUAGE']) : $id);
    }
    echo '</select><br />';


    printf('<label for="i_limit">%s</label><input id="i_limit" name="storage_limit_mb" value="%s" style="width: 50px" /><br />',
        t('LABEL_CFG_STORAGE_LIMIT'),
        htmlspecialchars($setup['storage-limit-mb']));
    printf('<p>' . t('HINT_CFG_STORAGE_LIMIT') . '</p>', get_size_of_storage_bytes() / 1000000);

    printf('<label for="i_css_customizations">%s</label><textarea id="i_css_customizations" name="css_customizations">%s</textarea><br />',
        t('LABEL_CFG_CUSTOM_CSS'),
        htmlspecialchars($setup['css-customizations']));
    printf('<p>' . t('HINT_CFG_CUSTOM_CSS') . '</p>', get_size_of_storage_bytes() / 1000000);

    printf('<button type="submit">%s</button>', t('BUTTON_CONFIG_SAVE'));
    echo '</form>';

    echo '</div>';

    js_focus_to('i_password');

    draw_html_footer();

}

function on_setup_required()
{
    $error = get_error();
    draw_html_header();
    printf('<p class="error">%s</p>', $error);
    draw_html_footer();
}


function on_flash_upload()
{
    if (isset($_FILES['Filedata'])) {
        $res = upload( $_FILES['Filedata']);
    }
}


function upload($f)
{
    $filename   = $f['name'];
    $temp_name  = $f['tmp_name'];
    $error      = $f['error'];
    $size       = $f['size'];
    $type       = $f['type'];

    if ( ! $error) {
        $tmp_file = get_tmp_upload_name();
        $move_res = move_uploaded_file($temp_name, $tmp_file);
        if ( ! $move_res) {
            trigger_error(t('ERR_CANNOT_MOVE'), E_USER_ERROR);
        }

        $soft_limit = get_storage_limit_bytes();
        if ($soft_limit && get_size_of_storage_bytes() + $size > $soft_limit) {
            @unlink($tmp_file);
            trigger_error(t('ERR_TOO_BIG'), E_USER_ERROR);
        }

        $entry = array(
            'md5' => md5_file($tmp_file),
            'type' => $type,
            'size' => $size,
            'name' => $filename,
            'description' => get('description'),
        );

        if (is_already_uploaded($entry)) {
            trigger_error(t('ERR_DUPLICATE'), E_USER_ERROR);
        }

        append_to_uploads($entry, $tmp_file);
    }
}

function on_upload()
{

    if ( ! isset($_FILES['file']) || ! $_FILES['file']['name']) {
        draw_index_with_error(t('ERR_NO_UPLOAD'));
    }

    $file = $_FILES['file'];

    if ($file['error']) {
        if ($file['size'] == 0) {
            draw_index_with_error(t('ERR_TOO_BIG'));
        } else {
            draw_index_with_error(t('ERR_BAD_UPLOAD'));
        }
    }

    if ($file['size'] == 0) {
        draw_index_with_error(t('ERR_EMPTY'));
    }

    $tmp_file = get_tmp_upload_name();
    $move_res = @move_uploaded_file($file['tmp_name'], $tmp_file);
    if ( ! $move_res) {
        draw_index_with_error(t('ERR_CANNOT_MOVE', $move_res));
    }

    $soft_limit = get_storage_limit_bytes();
    if ($soft_limit && get_size_of_storage_bytes() + $file['size'] > $soft_limit) {
        @unlink($tmp_file);
        draw_index_with_error(t('ERR_TOO_BIG'));
    }

    $entry = array(
        'md5' => md5_file($tmp_file),
        'type' => $file['type'],
        'size' => $file['size'],
        'name' => $file['name'],
        'description' => get('description'),
    );

    if (is_already_uploaded($entry)) {
        draw_index_with_error(t('ERR_DUPLICATE'));
    }

    append_to_uploads($entry, $tmp_file);

    redirect('?');
}


function on_delete()
{
    $id = get('id');
    $entry = safely_get_file_entry($id);

    $all = get_setup();
    unset($all['uploads'][$id]);
    save_setup($all);
    @unlink(get_storage_folder() . '/' . $entry['name']);
    redirect('?');
}


function on_download()
{

    $entry = safely_get_file_entry(get('id'));
    header('Content-Type: ' . $entry['type']);
    header('Content-Length: ' . $entry['size']);
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $entry['original_name']) . '"');
    readfile(get_storage_folder() . '/' . $entry['name']);
    exit;
}
function on_save_edit()
{
    $id = get('id');
    safely_get_file_entry($id);

    $all = get_setup();
    $all['uploads'][$id]['description'] = get('description');
    save_setup($all);
    redirect('?');
}
function on_background_image()
{
    // you'll hate me for this
    header('Content-type: image/gif');
    etag_last_modified(filemtime(__FILE__));

    echo "GIF89a\xc8\x00\xc8\x00\xf6\x00\x00TQBYVGZWH]ZKa^Nb_PecSifVmjZpn^trbywf|zj\x80\x7fn\x82\x80o\x84\x83r\x88\x87"
        . "v\x89\x88w\x8c\x8bz\x90\x8f~\x92\x90\x7f\xcf\xb1t\xce\xb4{\x95\x93\x82\x98\x97\x85\x9a\x98\x87\x9d\x9c\x8a"
        . "\x8e\x8f\x98\x8f\x90\x98\x93\x93\x9a\x97\x98\x9d\x9a\x9b\x9e\xa0\x9f\x8d\xa1\xa0\x8f\xa5\xa4\x92\xa8\xa7\x95"
        . "\xa9\xa8\x96\xad\xac\x9a\xb0\xaf\x9d\xb2\xb1\x9f\x9e\x9e\xa0\x9f\xa0\xa1\xa3\xa4\xa3\xa7\xa8\xa5\xaa\xab\xa6"
        . "\xae\xae\xa8\xaf\xb0\xa9\xb5\xb5\xa2\xb9\xb9\xa6\xb3\xb3\xab\xb7\xb8\xad\xbb\xbb\xad\xbe\xbe\xb1\xce\xb6\x83"
        . "\xcd\xb8\x87\xcd\xb9\x8b\xcc\xbc\x93\xcc\xbf\x9a\xbf\xc0\xb1\xcb\xc0\x9e\xcb\xc2\xa4\xc1\xc1\xae\xca\xc5\xac"
        . "\xc4\xc4\xb3\xc9\xc7\xb1\xc9\xc9\xb6\xcd\xcd\xb8\xd0\xcf\xba\xd4\xd2\xbc\xd9\xd6\xbf\xdb\xd8\xbf\xdd\xda\xc1"
        . "\xe2\xdd\xc3\xe6\xe1\xc5\xe9\xe3\xc7\xec\xe6\xc9\xef\xe8\xca\xf6\xee\xce\x00\x00\x00\x00\x00\x00\x00\x00\x00"
        . "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"
        . "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"
        . "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"
        . "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"
        . "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"
        . "\x00\x00\x00\x00\x00\x00!\xf9\x04\x00\x00\x00\x00\x00,\x00\x00\x00\x00\xc8\x00\xc8\x00\x00\x07\xfe\x80A\x82"
        . "\x83\x84\x85\x86\x87\x88\x89\x8a\x8b\x8c\x8d\x8e\x8f\x90\x91\x92\x8aG\x93\x96\x97\x98\x99\x9a\x9b\x9c\x92:"
        . ":\x9d\xa1\xa2\xa3\xa4\xa5\xa6\xa7\xa8\xa9\xaa\xab\xac\xad\xae\xaf\xb0\xb1\xb2\xb3\xb4\xb5\xb6\xb7\xb8\xb9\xb0"
        . "?0/%\"\x1a\x18\x13\x17\x19!#&/\xa0\xba\xcb\xcc\x930\x11\x08\x00\xd2\xd3\xd4\xd5\x02\x0c\x1a\xcd\xda\xdb\x87"
        . "\x19\x01\xd2\x02\x0f\x11\x13\x18\x1a\"\xbf\"\x19\x17\x12\x0f\xd1\xd2\t0\xdc\xf2\xcc@ \xd2\x08/\x8f=\x11\xd2"
        . "\x07?\xf3\x00o\xcd\x18\x00\xe0\xc1\xa4\x11\xd2\$\x04\\8\x0b\x03\x00\x02\xff&=\x000\x80\xa1\xc5W\x0c\x00P\xb8"
        . "\xf4BZ\xbe\x8b S\x11<\x81\x89`\x86\x90 i\xb4X\xa1\xe2\x83\x87\x0e\x1c8l\x98\x19\xb3\x83\xcd\x0f*V\xb0\x90\xd1"
        . "H\x1a\x10L\n4\xa2\\\xf8C\xc5\xcc\xa3H\x93*E\x8a\"F\xa2\x1f\x00\x02d\xca\x18a(\xc0\x15IQ\xa4\xc8\xc9\xa2E\x8c"
        . "\x184d|m\xc1\x82e\n\x0fIU \x9a\xf10\x93\x03\x00\xfe\x0e\xac\xcaCq\xb4\xa9\xa5\x18\x1f\x8ev8\xd4\xd1@&~\x0c"
        . "\xe4nsA\xd3\x85&\x16G\xd5\x162\x01\x00A&\t\x00\x14\x08n\xf6C\xe6\x06\xa7\x9bZ\x1c\x9dQ\x08\xa1dL\x0e\x1dO>"
        . "\xa5,\xd1\x91O\x85\x08oP\xcc\x89\xee\xeaB!\"grx`4\xe9J\x91R\xcc\x8c\xc8I\xc6L\x0f\x9d\x01\$\xc8D\xa1\xb1m]"
        . "0\xf7\x8a\xea0\xb3t\x90\x12\x00jc\xe2\xf7\xf9\xb8-\x1a3Ywr\x8dYPG\x02n\x01\x04\xb6N\x8a\x86sF\xd87\xb4\x18"
        . "\x85X=!\x18\x1435\x80K\x9e\x14\xeeG1fv\xef\xc4B&\x0bB=H\x93IP\x10\xd4W\x8bf\x97\x8d\x82\xa0vAH\x13\xcf%\x07"
        . "\x000\x81\x81\xb4\xb4\xc7\x93(\xf9\xbdF\x08A \\\xa2\x834\x17P8\x0bV\x1b\xd00J\x86\x1f\x14\x12\xd4\x02\x97h"
        . " \x8d\t\"\xcaB\xe2y\x9b\xf8\xb6A\x8a\x84\\ \x8d\x08\x93\xe8@\x90\x00?\xc5\x08\x8bQ\x1b\xf0\xd6\x89\x0e3\xe1"
        . "\xfe8\x08|\x14u\x08\t\x0c\x11\xd2'\xe4\x903\x91\xa2\x83L\xc0\x152\xc14\x07\\@\x02X\x87\xd0\x00\xc3\t\x18\x04"
        . "%\xcd\x00=`\x02\x04\x10>\xf0\xe0\xa6\x0fp\x069e\"\xbam`\xe5L\xca\x15\xa2c5\xd2\x04@\x80\x01\x04\x10\xc4'E\$"
        . "E\xc2\xc3\r\x16XP\xc1\xa2\x8c6\xba\xa8\x056\xec\xe0\x83*B0Cdy\xbf!B\x82\x99\x83vz@\x04F6\xe2C\r\x8c\xda`\xea"
        . "\r;\xa4\x9a\xea\r7\x98\xda\xa8\xa4\x90\x08q\xc4\xacH(\xb1\x04\x13\xb8\xe6\xca\xc4\x12\xb6\"1\xeb\x11\x95\xe6"
        . "B\xa4\x89\xa2\xa4\x97e\"\$\x84\xa0\xc1\x05\x13@\xd0\xc0\x02\x0c\x88C\x8e1\xa1:\xe2\xc3\xa3;\xc8)\x88\x0fl\x1a"
        . "\xb2\x83\xa2\x15X\xd0\xc8\x10H0\xd1\xc4\xb9\xe8\xa6\xab\xae\xba\xe62\x01\xec-\xc3\x8eb\xa3\x92\x8ch\xbb\t\x10"
        . "\x8a\xda`/!\xfb\x06\x01\xc4\x0e\x8bf\xab\x08\x12\xe8\xb6\xbb\xab\x12J\x1c\xe1\xeb\xafG\$\x810\xae\xeb\xde7"
        . "\xe2L\xc4\xfe\x86\x92!\n\xb2\xf0\x10\xee\xa4\x91\xaciC\xb8\x8a,Q\xf0\xac\x87\x04\x1b\x84\xc9\x83\xcc\x9a\xae"
        . "\x12\xb5\xb4\xb7\x1f'\x17\xcb\x82C\x058\\rm\x05\xdc\x1eb\x04\xbaHd\xa2\xf2\xb9\x12\xc3\xe22{3\xa5 \x0b\xa9"
        . "<\\\x02D\xc0\x87\x0c\x81n\x11\x9c\x10\xdc\x04\x13(\xbf\x92\xe1z\xa2\xb4\xb7\xc2\"\xbf\"Q\xeb\xad\xb7\xe2\xba"
        . "\x04\xafJxm\x04\x11\x8e(\xda\xef#\x1f\xdfp\x88\xd4,s\"\x84\xb9M\x04m\n\x8dAd\xf8\x9f(D\xeem\xc8\x11J\xac+\xf8"
        . "\xe0M,\x81\xc4\x10\x8a,\x9a\xc9\r\x15\xecp\x88\xc8MT\xed\xf3\xb9q\xa7\x82wz\xf4r\xc2\\\x82\x85\x14\xb1\xee"
        . "\xaeL\x94\xed5\xadI8<\xb6\xe0v\x0f\x022&\x8c\xdb`\x88\x10\xe72\x11\x8a\x0eG\xc4\xbeJ\xeaA\x1c%\n\x923]8\x88"
        . "\xd4\xe7\xfaZ\x9a\xe4:\x03?\xf5!\xd7\x8a\xcbz\x05\xae\x17B\xc4\xb9I\x882\xf7\xd4\xb8\xaf\xe2\x1a\xd6\x9c\xb4"
        . "\xc7\x81\x91N\x07O\xfc#\xfeB\$\x81\xae\xdd\xd7\xd6\xa0&\xc0\xcd\x13\xb2s\x13P\x8b\x12x\xdd\xb3\xb4\xb7\x01"
        . "\xde\x93\xd0`\x19\xc6\x84\xd0\xdd\xf3&\xc0\xa3M\x88\xc6\xe9\x9b\x84\x0f\x00\xa6<BH\xad\x7d\xa1\x10\x9f\xbbf\x91"
        . "\xa1\r\xa0\xa0Z\x91\xf8\xc1\x0fP \x13\x0e`/\x08\x9e;^'\xf4W\x08\x00f\x82\x80\x86\x90\x9a\xffBQ\xbb\xba\x7d\xcf"
        . "zzyY\$2T\x13#I\xadz\x93\xc8`\x13:\xc8\xbc\x0f\xae\x8e\x10\xe2k\x02\xe2DQB\x18\xaa\xe2\x07I\xe1@\nb \x03\x19"
        . "\xe8@\x82\x86\x90 \rf\x10\x83\x16\xa4\xc02G\xf1\x9d \xe86\nt\xa1\xecZ\x01\x94\xc4\xbf\x98g\xaf\xf7\x9d\xf0"
        . "\x12%\xdc\x1f\x037\xb7\x14\xbd\xbc\x04\x8a\x1b\x88\xc9RTx\xae%\x8c\xe2\x7dF D\xf2\xd443\xb7\x15\x02r_\x9c\xc4"
        . "\x0fJ\x18\xbdZ\x14\xa5\x8cAD\xe3Q*\xc8\x01\x14T\x8c\x10\xc1\x1b\xc5\x0b\xf9\xa5\xb8\xe5\xd9\x91\x10\x90\xb3"
        . "\x8f\xb9\xfah\x0b\x1d8\x91\x8cK\x81\xa2&Q\xfe\xc0\x02\x1a@\xb0\x87\x8a\x84^!\x1ay\x89\xb6\x19\"\x92\xa3(\xa1"
        . "\x1b\x97!A\xb14\x91,,\x88e\x0b\xbcBD\xfa\x15\x02\x94\xa2X\x1f%\x05\xb1\xa8\xb59\xe2c\x8e\xbb\xe3\xb9H\xd1\xbd"
        . "Un#\x8f\x8d\xa8\xdd\x02sI\xb9QV i\x96XZ\x05\x1e9\x08*\xa6\xb2\x99\"z!\x041\xf1<\r\x0e\xc2\x94\x96\x00\xd83"
        . "O9\xccT\x9a\xabr(\xf1\xa5 \xa4\xb6\xc3PtSv\x84\xc8\xc1\xa28&\tp\xd1s\x10x\x14\xc5\x1eE\xc9\xca\x12D\xe0Y\t"
        . "@\x00\xa0\xa2\xf2'\x04(`\x01\r\x98@\xa1\x12\xc1\xceQt\x0f\x9e\x83\xb8\x99\x05\xeeY/ |\xac\x86\xe4\x8c\xdc5"
        . "\x9b\xb0\xcb\xa7\xb4\xe2\x02\x02\xa8\xc6\x00\n\x80\x80\x04\$\x00P\xdf\xa0\x06\x01\x9c\xf4\xb6s\xb5\xb3\x13"
        . "\xb0\xf3\xe6 f\xb6(\x1c\xf0@\x9d\xdb\x12\xe7<\x0f\xe1\xc5\x8dv\xb4\x16QZ@\x06`\xb0MA\xcc\x80\x04\x10 \x804"
        . "\xc6\x13\xc2rJ\xcfv\x86\xb8(\xa3\x12\x05\xfe)\x1b\xb4\xcaT\xe0rT0\r\x91\xc3\x11v\xa2\x87\xc8d\x05?\x06P\x82"
        . "\x08.@\x1a,\x1d\x04\xb9\\\xeaPt!\"\x07Yu\x94\\\x1be\x03\x8a\xe2\xf0\\\x08\x94[\x0e\x7d\xd8\n\xb6\x00 \xad\x8f"
        . "\xf8A\x84\x06P-\xa9\x91\xe2\x9d\x8a\xf0A\x0ep\xc0*S\xd5 Q\xa6\xb2*\x0eR\xc5\x08\\&\x90z\xb8\xc8\x80l,\x01\x1d"
        . "\x00|\xc4\x80@\x1bE7\x9b\x80\xd3C\xd8\xab\xb4\x18\xc4f'~0>\\d\$D\x97 \x08lA\x0b?Q\x8c\xb6\xa8\xb6u+\t\xad\x88"
        . "\x8a\xd38\"B\x9f\x9d\xc4Y\x1b\xd0\xd4\xba\xe1v\x12\xaal\x05\xba\xbc\x9a\tYAU\x124\x08K\x0c\\\xe0\x82X\xea\xa4"
        . "\x05\xd4\xad%j\xeaU\x00\x00p\xe6\x12\xf3aj\xcaB\xcb\xc36N\x82M9\x83\x04\xe4 \xaa\x89\xd1\xfe4\x11:\x88A\n0"
        . "YFA\xdeh'\x8b \xc8q\x17A\x9d\xbf\x91w\xb7\x1c\x7d\x04\x0el\x00.\xaa>\xca\x06\xd0\xa4\x04\xcf61=\xfe\xbc6\xa2("
        . "1\x89\xb0e\"\x8c\x82\n{@\x8de\xec\x80\x0c\xfcV\x88\x00H\x05\x13[\x12\r!\xba)\xc6NH\xad\xc4\x8aHT\x0e\xec\n"
        . "\x04\x1e\xcc,\x07\x8cH\x17\x13^\x1a\t!\x14\x81n2\x85\xaf\x84;\xa0\x02\xb0\x1cQ\x11J\x8c\xc1\n\xd0\x82\x14\x15"
        . "B\xa5\"\xa0\x89\x8e!\x8a9\n\xc8\xf15\x08p\xb5k!f&eB\x94\x10]\x86#\x02\x11\xbe(\x84.\x0f\x81\x08I\xc0\xf1\xb9"
        . "\x98\x8b\x88\x0bs\xa0\x03*\x84\x04\r\x88\x9c\xc64\x11\x82-~\xc1\x84=\x90L\x88\x98j\x14\xa6\xe8\x8a##nP\xb3"
        . "E,\r\xc6\\#\\\xec\x0e66\x88\t\xfa\xc9D\xca\xdc%\x88\xa4\xa1A\x9c\xc08\x98\x10\x01\x00\x04\xf0\xb8\xffn\xe2"
        . "\xcaa\xb5@\x82\x15Q\x83>sM\xcc\x82\x0e\xf5\xd4\xc8|\x88+\xc5\xc4\x96\x92\xd8\xdc\xb1\x04\xf1h\xe9\\\"6\x1f"
        . ".\x04\xf0\xc2\xea\x88t\xa5m\xd3\x89\xb0\xea#\x14\x06jQ\x17,\t\xa4FD\x0cb\xb2\xb5N\xfe\x0c\xbb9\x83\x00\xc2"
        . "\x87\xc0\x83\t\xcd\xd2y\xc4\xe8z\xef\$r\xd8\x04\x14'\x02g\x8d0\x95\$\xbeL\x04\x85y\xadt\x08+]\x12\x90\x90\x84"
        . "#h\x99\xd6\x84\xa8\x13\xaa'Q\xa7\x18\xc8)@\x94\xc6\x84\x8e\n\x80\x08\xc8\x15\x8e\xc6\x91 \x82\x98\x83]\x88"
        . "\xa5U\xb9\x10\xadjF\x05\x89\xb6\x01\x0e7\x08\x00\x8f\x81\xb4!F\xdb\xc6\xc3u\x99\x11]\xee\xb6\xbd-\xad\x08\x7f"
        . "7\"\xe0\xcc\x88\x89\xd10\x94\x9dB\x84\xd4\xcd\x96\x98Hu\xfc+\xe8]\x91\xad\xd0\xa1\x966\"\xb0\xcd\x08m/\xc3"
        . "\xd4\xc5\x0e\x85\x8d6>\x08\xa5~\xd7\x12\xe1UD\xf7|\xed\xeb'\x13B\xd3\xd9\xa6&.f@ly\xa5q\xd5A\x88P6 \x04\x00"
        . "\xe2.\x82\x08\xc6\xe3y\xba\xca\x8d\xeeA\x00\x9d\x11\x16\xf0t.\x86\xcd\x01\xc3\x14\xab&\x858\xabA,\xe1\xd7\t"
        . "9\xe2\xcbF\xf0\x9a\xd7\xc6\x86\x84\xb1)A\xdc\xb3*B\xd5\xa3\x9aE\xe4U\x00\xd0\xbapAL\xd2\x9c\t \x9e\xfeYO\x93"
        . "\xbey\$  \x8d\xb26C\x9e\xb8\x8e*\xcb9\x91\xace5\xcb\x01\xd0r\x00\x04\xc8\xa1\x81\x10\x18>\x11z\xe7\x00\xdf"
        . "1\xe1w\x0e\x14\xa2#\xc29H\x9f\xf6;\x8b|\xe5`\x07o\xe2\xc1\x0e\x16\xab(\xa1\x9fW\xd9\rHi\xa7\xf8\x14\x0e\xfa"
        . "e^\x8a\x9d\xd0]!2B\x11\x1e9\xe2\x05\x90\x01\x91)\xd6\xa4\t \xe0 \xae\x8e\xb2\x01\xde\x89\x93R\x04L\xa0\x18"
        . "#(A2tp\x82_\x80\xe0\x02\x0e\x10T\x00|_\x88\xfel@\xf0\xb9\xafR!z (i( Z\x11\xb8\x00\x06@\xa0\x8ef=K\xf6\xd1A"
        . "\xad!r@\x7f\xc6\xb6\xaa\x065\xb0*\x9fU%\n8\xf1\x00N\xa4\xf7\x08\xf6\x00\x00\x0b@#\xc4w\x08\"\xa0T\x01\x10\\"
        . "\x82\xb0\x022qH\xe1g'\x860\x03g5{\xb3\x17\x00\xa0\x12\t> Uq\x95(s\xc5<\xffv\x0b=\x10Rq!\x7f\x83\xe0#\n7\x08"
        . "\x0eX\"\xa3\x00D\xe2w\x080p\x01\n\xfep\x00\x05P~\"e\x00\x08 T\xe0\xd7\x08\xf2\x14.6\xf5\x7f\xf6\xf2\x03l\xa2"
        . "z\x8c\xc34\xda i\x04`\x82\x85\xe0\"\xde\xd5\x7d\x0f8\n\xbc\xe3y\xfa0\x03'\x00\x033\x10\x82\x8e\xd0\x83Z\xd7\x08"
        . "\x16\xb5(\x89\x87\x0b\x13\xf1\x00J\xc8/\xdf\xb0t\x83\x90y\x9bw\t\xe9\x91'\xaf M\xcb\x07\t\x8aR@\x9c A:\x10"
        . "]\x9e\x14\x80\x86\x10\r\xdc7\t\x04\x92\x1a{7\n\xf6\xb3\x01l\xe8\n\x1a#\x87\x86\xb2Sw\xb1\x02\x15F_\x99Ta(\xd0"
        . "\x02\x10x\x08\x06\x00\x00\x0b5\t\x84'^A\x90y^g1X\"34\x83\t\x8b\xf2\x86\x8d@\x03\xae\x01H\xa6\x88\x14\x0c\xc2"
        . "/J\xb5n\x8b\x00\x19,B\x08\x99gp\x9b\x90\x1f\x1c\xa0h\x890\x04\xb8H\x04E\xa0\x8bE\xb0\x8b\xb8\x88o\x8b\x804"
        . "\x98\x00N\x8f\xc0h\x1a'KD\$\x03\xe6\xf1\x03\xd1%\x16\xb3\xc4\x02\x8c\x86'\x91(\x08\xdf\x00r\x93\xa0#\"&\x08"
        . "6\x12s\xfe\x9d\xa0\x1a4\xb7p\x80\xd3k\xa1f8y\x85\x08\xa4\x82\x85\x8b03u\xa7\x08u\xb2\x01\x1e\x90\x86%#\x08"
        . "4 ?,X\x08P\x11k\x96\xa0Y\xd9\x18\x04\xbc\x93\x8a\x9a\xa0\x02EGrRG8JpB\xa42\x86\x85 O\xfa\xe2\x08\x19\x82f\x89"
        . "@k\xa48\x13\xf8C\x08\x1f\x12o\xaf\xd6\x16\x84\xe0w\xb6\x98\t0\xc1\x01\x06'\x04\x13\xc7\x04\xbe\xb2\x8bD\x80"
        . "\x8b&#\x04_6\x04\xbd\xd8v\xeaR\x8e\xaaS\x01j\xa2\x90\x8fp\x14\xf0\xf8\x086Rp\x85\x00\x1f\xf4\x86\t\$0i\x86"
        . "\x10ax\xe8\x08\x96\x81{\xf6\x96\x04sge\xe9Bj\xa4\x14N\xe1\x82Z\\\xf7\x8d\x87!\x91F\xd2\x17\x99\xd0\x93\xf8"
        . "(\x08y\xc19\x9c`?2qK\xbc\xa5\t\xf6f\x08Kc\x88\x92P\x88\x8e\x80\x15\x1c\x80{\x7dw\x14F\x02\x1f\xcf6\t\xb1\xf1\x96"
        . "A@\"\x83\x98\t\x89VgO#7\x0b\xc6Hdy\x880\xd9\x08h!\x85\xa1\xc0\x1d\x14\x19\xfe\x15\x08I\x08.\x12g\x84p\x93\xdc"
        . "\x88\t\r\xb4\x1f\x19\x84N\x9a\xb0>\xec\xe5/7d\t\x1aS\x01\xa8%\x91\xa3@\$\xdc\x08\x15\x00\xc0\x8a\x8a\xa0#\xae"
        . "F\x08d\xb4\x02Ay\x08\xfd\xc1\x1c\x13)\x08]%\n\xadE\x08\x979\t \xc4\x08.\xd8\x98\xb38\x13\x8d\xf9\r;\x08\t\x90"
        . "\xb1\x8f\xda\x88\x14\x1c\xb0\x02\xd3\xb8\x084\xe0\x02Y\x99F\xa1bM\xa1@0\xcb\xf4\x92\x99 O\x7di\x8f\xe9!\x8b\x9a"
        . "plP\xa9T\x0c8x\xe2\x81\x08\$\x92\x14\x1f\x80\x13*\x80\x8cMd]-\xf1\x01\x8cH#\xe6U^\xb5\x05\x9d\x98 O5PZ6rA\xbd"
        . "\xc1\x99\x84\x10!{(\tg\x85\x89\xb0\xc8\x88\xa7h\x8a\xad)\x08\xdd\xa3r`\x94H?W\x01\xa99e\x15`>\x8c\x90!\x9b"
        . "\xd8\tkX\x08\t\x00\x00Uq\t!U\xa1\x88\xf0\x03+\xc0\x1c\xf6\x05H\x98T\x8b\xf4)\x08\$\xb6Q\x92\xa9(\xe8\xa8\x08"
        . "3\xc3\xa0\x8b\x90!5\t]x\xfeR\x08\xfc\x90\x84\x96 i\x00\x90\x9f\x88`I\xd0\x88\x02\x1epFhd\x13\x1d\x80\x13:\xa1"
        . "\x96H\xd9\x9exV8\x85@*\xa2\x08\t\xa4\xa2\xa2\x8a\x80 B\xba\x96\x82\x18\x1c\x14:\t=\xa0T\x00`\x8d\xae\xb0>>"
        . "\x17+\xcf%\x08\x8c\xb3\x85\x90 M`z\x08\x08\xd2\x9b\x9a\xa0{\x840\x89\x05\x81\xa5\x8b`\x02A5\x0b\x96U\xa43\x14"
        . "O\xbdd\t=\xb8U\x8a\xd0\x1e\xc5\x99\th:\x08\x8cq&Bu\x0e/\x00\x03=\x00\x04?0\x03c2\x02! \x01\xee@\x11\xa2iZ\xfe"
        . "\xa7z\xf4\x87z\xff\xe7\x03\xfbUB.\xb9\t\xba\x15Q\x8b\xe2z\\\xc8(\xa8\xa5\xa7\xa4\xd0\xa7\x830\x026h\x81|b\x00"
        . "\xdb\xc9\x089P\x84\x1f(W\x91b\x1a\x14\xc7\t\x9b:\x08\xa4\xb2\xa0\xe8(':\xe5\xa9\xaa9\x13f\xca\xa7/h\x8f2\x88"
        . "\xaa\xd4\x10\x00\x0b\x80\x01e\x99U\xac\x82\x038pznR\x7f\x8cu\xab\x1b\xd3Rw\x06\x9bN\xc5\xa9\xfe\x8d\xc2\xac"
        . "\xcd\x9a\x03\xff\xe7&<P\x7f\xd2\xda\x94\x8e\xe0\xa4-H\xaaI4\x03\xbe\xa0\xa8\x18@\x01\xc4`\x0c\xd2\xf7\xab\x8b"
        . " Q_\x98\x08\xe2d\x01\xfb\"B\xa4P\xab\x11\x15\xae\xaf:W5p\xa2\x82\x80 -\x1a\t\xe9!\x98\xb0@*\xeb\xc8\x087SW"
        . "^I\xa4\x9d\xa0\xaf\xf1\x84(\xfd\xda(Y7\xaf\x987\x13\x0f\xba\x95/\x1a\x0b\xc9s\x98Pf\x84\xe3\xc5>\xf9z\xad\x89"
        . "\x80^\xde\xca\xad\x95\x9a\xa0\x83p5F\xe7\x8e\x9e\xc8\xab\x9f\xfa\x89C\xba\xa5\xe0\x03\xb1\xad\xa07'b\x9f\x97"
        . "\xf0\x0f\x87\xf91I\xfa\x08\x08\xdb\xb04\xdb\x08\xb4c\xb3\xac\x90\x1e\xb8y\x9d:\x8b\x08F\xa00a35\x9f\xb3+\xa5"
        . "cn\x8dp\x8e\xcb\x93Ea\xb4Q\x95\xe9\n.\x18\xa0\x9b@\"\xfe\x18\x04,9\x90\xeb\xb2\x040d\xb5\x97\x00\x9f\xcec\xa0"
        . "\xa1\xc0d\xb2\xe0\x82\x128\x98\xba)\x90Xf8\x0bc\x04gC+\xdfv:\xe9\xb2\x04\xdf\xa36J#\xfe\x9dk\x1b`\xec)\x99"
        . "\xaf\xe0\x1a{j\t\x96\xf12WVm\xc0\xb8\x08B`\x04\x13\xb7r\x7f\x99\xb6\xb3\x19S[\xab\t'6\x0b\x7f\xb4\x1a*\x8b"
        . "\x08\xda\xe3\x1c\xa3em\x92pe\xc6\$\x9b\xd3\xf9\x08\xe2d\x08_\xd9\t\xef3\xb4%{\x17Q\xc4\tW\"\x95\xf9\xc3\xb6"
        . "\x990ZA3\x96\x99\x90\x99\x86@7\xb0\xbb\x08\xb1\xa9\t\xb88\x08x\x08\xb7\x1d\x90\xb8\x8e\x10\x91s;\x08\xa3\xd5"
        . "\x9c\xeb\xc9\xa9\xa9\xeb\x08\xbe+k\xd1\xcb?F\xebQ\xe4R.\x86\xc6.\"\xe95\x8f;\x08\x08\x92\x1d\xf0\nd-\x00E\x1d"
        . "`\$q\xaa\xa9\$;G\x98`\x96\r\xeb\xb0\x97`gs\xbak\x9fc8\xf8{+\xebr8OQ\x8ax\xb2\x02\xb4\xa4\x8cG\xf4\x0f?P\x87"
        . "\xaeD\x16\xfe\xdb;\xc2T\xad\xb4Jq\xb3)\t\xb5Y\x08\xe9\x12\xbc\xc6\x1b\x04;Wm\x90 5\xee\x12\xbeV\x06<\xe1\xdb"
        . "\x03\x1f\xd0\xa1\xffY_ld\xa4\xa2\xb0\xb9\xb2\xa9\x99\xef\xfe\xb9\xa0\x87\xd0\xb8\xcf\t>A\xa00\x7d\x0b\t\xddC\xba"
        . "\x8b0\x04\xed\xb2\x08\xe7;\xc2\xff\x89\x02x\xc3O\x00\x86b\x80k\xb9L\nI\x9fSn\x0csn\xe6\xc60^\x83:\x19\x8c\xc1"
        . "\x93\xb0H\x8b0\x03,\x80\x02\x8c\xa8IA\xe4\x01=\xb6M1e\xc3\xdct\xbdA`\xa2W\x9b\x08\xd4F\xb6\x84s\x04x\xd8=\x98"
        . "`.^\x9c\xa1K4\x03\xae\xf4\x15E\xa4\x8c\x018\xa2\xb9e\xc2\xb6:N\xa54M\x8a@\x04\x13g\xc6\x0b\x16\x94G\xb0+\x98"
        . "\xe0\x9c\xb2\x90A\x14\xdc\xc7\xfa\x8a\xa4\x98\xa0(?{KI\xa0\xbf\xbe&6\xfc[)\x82\x0c\xc5\x96PB\xb2\xb0\xbe\x9b"
        . "\xb0s\t\xf9\xc0\x8fp3\x00[\x08^&+_\xd6e\x0fwi\xf2K\xc3l\xe5\x08\xd5\xd2\x03\xe8\xfa\xa83;\nvFC\x8dc\t\xb7J"
        . "\x19\x04\x93\xc8\x16\xdc\xca\x8c\xa0\x03 \xd0\x00\tp\x00\x81\xc2'#u\x00\t\x80\r\xf0ZB\xfc\x86\t\xfa*M\xb7\x0c"
        . "\t\x1b\xf8(\xfe\xda@0zf\t1\x85L?\xc0\x0f\xd30\x008\x08-\x0f\xc0\x00\x0b\x10P\x06`\x83\x0f\xc0\xa6\x82\xb0>"
        . "\xcd|\ttS5\x99\xb9\xa08\x00'O\xd1&\x12\xeb\x85\xfd\xb7\x03\xcczU|F\x7f\x16{\x08\xe2\xb3\xce\x90;\xab\xf6\xe8"
        . "\x0e\x100\x02\x8d\xd0\x03 `&\x06\xf0Ic6\xb2\x0c,\x08=(W\x89\x82\x7f\xc8\xf7*\xa1\xe0\x03\xe0r*\x8b5`\xa4\x92"
        . "(\xfdl\xc4\x1c\xdc\x08\r\xad\x08\x90!\x00\xab\xba\x08[R\x10\xd4\n\xd0\x92P\xcb\xc8\xe3\xaa\x13\xdb(7\xe0\xb1"
        . "\x88\x80/\x13\xa5\x08< \xc6\x8c 2!\x1dc\xabL\x08e\x18r\x0eBr\x99\xaa\t\xad{\x08\x92\x9a\xcf\xfal\x03\xdb\x8a"
        . "z\xa403\xa5\x85\xd1\t+L=-\xbc?-\x08\x92\xa6\x98\x92\x00\x9af8\x08\xebs\xcdr\xba\r\xbavq\x95\x1b2M\xc0\xd2\x0f"
        . "9\xd2\x88\xc0\x0f\x05r\t\x13\xeat\x84\x80\xc8\xb4\x9c\xbd\xb6\x10\xcd\x8c\x000\xe8H0h\x8d\x081\xd5\xfe\xcc"
        . "\x19a\xa3\xbe\x19z\x85\xa0\xa5\xa3 C\xa5\x10\xce\x08\xf5\x00\x12@\x0c\"\x00\xd8\xd7\xf6\xd1\x83\xa01y\x7d\xd5"
        . "\x8d\x90\xcd\x8a\x10\x14\x97w\x8dJ\xd69@\xdc\t\x88\xc5\t\xeaz\x01\xdc<{\xceW/z\xcc\x08\x1a\x03\xd9\xbb\xfc"
        . "\xc5\x0f]\x08\xd1p\xd2\x8e\xe0\"\xcc\x06m\x84\x1b\n\x19t\xba\x96@\x01\x8d:\r\x83\x8a\xae&0\x02\xe9 \x01\x9c"
        . "b\x00\xf0jq\xa2r\xda\xb2\xca\xcb%\xf4E\x93X\xbe\x8b\x10\x97\xaf\x03\xc6\xaa\xdc\x04\x86\x0b\t3\xa0\xa6\xd7"
        . "p\x01 p\x0e\x8a\xd0\x03\xf3\xb1Y\x88p-\x00{-x\x8a\x08\xb5\x83\xdb\x91\xa0\xc9\x8a@\x10\xb2|\x08\x9d\xb5/tm"
        . "\t9\xd4\xc6\x8c0\xa1\x03\xd0\xd5\x8e\x10|'Q\xd3\xc8\x8d\xd3\xfd\x7d\x08\x19\x84\t\"\x93\xb9@\x7d\x95\x92\xf0h\x00"
        . "P-t\xb3\xd7\xb5&\xd0\xb1=i\x0f\"\t\x93\xf8\x8a\xa6\xf5\xdf\x88\x90\xda\x10\xe7\xe0\x95\xd5\xd9\x87\xe0\x93"
        . "\x98\x00z0`/9D\xa0\x92 C\x0c\xfe\xae\"Ba\t[\x12\x00Ee\xe1H\xbdxf\xadC\x93\xe0d\x8b  \x98\x80\xe04b\xe2r\x83"
        . "c;\xfbq\x97\xd0Y\xb0m\x01\xe5\x9d\x08\xf2t\xa2;\xc7\xbf\xa4\xdc4\xa6\xcb\x08>\xfe\xe3\xd2@#vF5\x9a \x04\xef"
        . "C\xdd\x97\xd0j\x98\x10 \x00\x90\xd9G:\xd5\x00W\xd6\x8b\x10u\xbc2\xb5\xbf\"nU\x8e.=ms\x91f\xe3\\5\xc1\xdc\$"
        . "f\xa9,\t\xd0\xc1\x9f\x91\xb0#\x89\xc08\xe8\x88\xd1E\xfct\x80\\\xdb\x8b0\x89\xb0\xdd\x08.\"\x97\x82\xd0`Xfn"
        . "%\x19\xe7u&\x04ZV\x04g\xae\xe1\x8d iq\x81\t!\x85\xdfr4\xad\x89\x90\xd3w'\t\xdd\x16\xc9\xe285K0\xb5\xe8\x16"
        . "\r0r\t:\xa2\xd5\xa4\xccs('j\xd5\r\t.\x82\xa1\x960\x89\xc8J\xe4\xa5\"Y\xf6\xf71\xf9\x12\nGi\x08A\xe1\xd8\xf9"
        . "\xbd\xd9|]\xc6\x7d\x9eg\x9a\xe0\x10\x1b\x81\t\x93x\xec\x99\x8e\x03\x8f\xe5(\x13\x7d\x03\xfe\x8f,\x0b\x19\xa1\x10"
        . "\x97\x10\x14\xc3\x01\xb9\x80\xd3\xe7\"\t\xd6\x98P\x1c\xb3e\t\xd1\x00\xeb\x8e@\xd3\xac\xf0\x9b;\xfb\r\x93\xee"
        . "\x08\x11W\x04z[:\x0b3+g\xb3\xeb\xfc\xf5W\x99\x10\rmm\x1b\x9d\x15\x02\x96\x10|\x80\xc5\r\x13a\xe9\x91\x10!\xe2"
        . ".\x18>\x10R\x03\xd0\xdeX=\r\x08O\x0b\xfc0\xeb\x97\xa0T\xdf>\x1a\x0e!\r\n\xe5\x08%@x\xd20\xf0\xdcP\x1c\xcan"
        . "\t\x04\xe1\xf0\xc7\xc1)\x93VR\xe0<\x01\xec\xc0\x00\n0\xcc\xd5\x00\x9c\xa2r\xeb\x046UU%Y\x90\x1d\t\x0e\xa1\xf1"
        . "\x8f\x00\x04\xd2\xa0\xef\xf5\x81\x01\xf0G\xac\x7dbv\x90\xa0S\x14\x1bY\x06F\xb1cj\t.2v\x96\xa0\xe5\xbe.\x18?p\xf1"
        . "(\x8f\x00\xc4\x1cR\x04u\x00\x06\x15-\$\x80\x90E\x18)\xe9\x15\xde>p|\x8b\xb2\xe73\xda\x9dW^xsb\x8f\x9c N5\xa3"
        . "\x844u\xf4\x91\x00\x1d,\xff\x08\xc5\x01\x00\x11~\xf6\xa1\xa0(n\xbf\xfe\x08E8\xca\x89\x00z\xe8\xfc[\xf1a\xf7"
        . "\xfd\x07\xca\xe3\x0e\xb2%\x01\x00\xfb-\t{B\xe7\x06\"\xef(\xca\xc7{\xec\xe5\x8f\x90\x11\x03\xe0\xdc\xf68\xda"
        . "\xa5.\$\xeb\xde\xb4\x0c\xd3\xb4\xbd\xb8e\x90\xc08C\x0e\t)\xba\t\x9de\x00\$0\x81\xff\xd0\x03c\xf2\x0b\x1a\xc0"
        . "\x00\xe5\x07\xd7\x06\xe2\xb4\x80\x9ce\x8b\xf013O\xeb\xd3\x0b\t\xdf\r\x00\x06\x10\x01\x1a@\x02\xd2\xe7\xf3\x10"
        . "\xf0\xb9\x01\xd1\xb8\x83\xc3\xb7\xa1\xc6\xed\x84@`\xb9\x7f\xe1\x84\xbf\xd5\x13\xe1\xf3\xd4`\x00\x0f`\x9d\xa3"
        . "A\xbf\x85\x83\xe8\x8a\xbe\xe8\x8c^\x04\xe52u\x88\xf01|o\x08\xd5\xcb\t\x17\xaf\xa6\xd3p\x7d\xc3\xb0\xdd%P\x85\x7f"
        . "\x7f\x1c\xf6F\xdf7<\xff\x870\xc4\x96p3\xe4~\xa3\xbd\x00\xdc\x82?\xa4\x80\xd04\x14DXhx\x88h\x98\xd4\xc4H\x84"
        . "hQ\x91()\xe9SQ\x014\x99\xa9\xb9\xc9\xd9\xe9\xf9\t\xca\xc9\xd8d\x14:\xb9\xc4\xb8\x84hijXY\xe1\xd3*;\xfeK[kz"
        . "\x94j[(4\xbaz9\xfb\x1a\xab;L\\\x1c\x8aKZ\x8c\xda\xe4h\x08\x89)\x1bl<M]]\xb8,T\x8c\xc4\x88t\x08)\xdc\xca\xc3"
        . "jM^^\xcb\xc4hL\xc4\x98tXS\xc13+\x1ei^o\xef\x99[\xbc\x9enh\x03?\x9b\xa3\x82\x85{\x04\x0b\"\xe2\xd5\xa4]1\x84"
        . "L\x0e\xf9\xcb\x01P\xa0\xc1\x89\x05\x11*\$\x86\xb0\xc9\xa1\x1b\x15p\xcc\xe28\x90\xa2Hs\xfb\xba\x19\x1b\x95\xad"
        . "P\xc0\x1a\xb3 \xd9\x18\t\xd3Z\xc9i(\r\xcd\x8bg*`\x05\x881{\x16Cv\x84&\xa3\x94\x85,Y\x80\xf6\xc9\x07\$X>\x9b"
        . "\xea*\xc2(\xa81l\x87tV\xd8\xf1\xc9\xea\r\xa7\\i\x01\x9d\xb6l\x907K\x96l\xd8\xb8\x81#\x87\xda\xb58n\xd8Xj\x14"
        . "i\xd7\xb9\x9f\xa0&3\x86N\x10\" V\xc9\xfa\xfd\xfb\xd7\x86\\\xba\x847\xd9\x95Zl\x94XD>v\xd8\xa8a\x01\xee\xdf"
        . "\xc85l\xe4\x00W8\xb3\xa6\xc3\xd3\xf2\xbf\x12\xe5\x04\xc4\x87\x0f \x835\x9b6\xccMh\x93\xcf\xa7[\x1b\xb3{\x91"
        . "XM\xd7\xb4\xf51Rb,c\xed\xdd\xc3\x10\xaaZ\xd8\x8b\xb7pZ\xf9\x88\xed\xfb=<y\xa8\xe0\xc4\x90!W\x0e\x9dS\xdef\xc3"
        . "\x16%\x8c\x8e\x7d\xd32\x93\xc3Fq\xcf\x0e\xfe\x10\xb2\xd5\xc3\xc6S\x0f\x8f>H\xc6\xe7\xb32jL\x0f?H\xde\x84\xacC"
        . "\xd9M\x1d?\xfd\x90Q\xdc\xcew\x12b\xc46\xa34\x94\x1f|\xe3\xf1\xb7\xc4\x12I \xc1\xa0\x11F\x10a\xc4\x11\x0c\""
        . "\xa1\xc4\x12\xf3\xf1WD\x81\xf1\x19\xc1_\x87\x1e~\xe8!\x13\xfei\x88\x9e\x80 \x9e\xe8\xe1w\$\xe6G\x84\x84\x15"
        . "\xa2\x98\xca\x12J Q\xca\x8a6j\xb2\xd8\x8d:\xee\xc8c\x8f\xb5\x04\x02\x00;";



    exit;

}


#
# /// site specific functions
#
function get_storage_folder()
{
    global $g_storage_folder;
    return rtrim($g_storage_folder, '/ ');
}
function draw_html_header()
{

    $setup = get_setup();
    $title = $setup['title'];

    if ( ! $title) {
        $title = t('DEFAULT_TITLE');
    }

    if (is_owner_mode()) {
        $title .= ', ' . t('OWNER_TITLE');
    }

    header('Content-Type: text/html; charset=utf-8');

    echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
    echo '<html xmlns="http://www.w3.org/1999/xhtml">';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';

    echo <<<JS
<script type="text/javascript">
function show_stock_uploader()
{
    document.getElementById('p-stock').style.display='none';
    document.getElementById('div-stock').style.display='block';
    document.getElementById('uploader').style.display='none';
    return false;
}
</script>
JS;


    printf('<title>%s</title>', strip_tags($title));

    draw_stylesheet();

    echo '</head>';
    echo '<body>';
    echo '<div id="wrapper">';

    printf('<h1><a href="?">%s</a></h1>', $title);

}


function draw_html_footer()
{

    echo '<div class="push"></div>';
    echo '</div>';
    echo '<div id="footer"><p>';
    echo t('FOOTER');
    if (is_owner_mode()) {
        printf(' <a class="owner" href="?action=config">%s</a>', t('CHANGE_SETTINGS'));
        printf(' <a class="owner" href="?action=owner-logout">%s</a>', t('OWNER_LOGOUT'));
    } else {
        printf(' <a class="owner" href="?action=owner-login">%s</a>', t('OWNER_LOGIN'));
    }
    echo '</p></div>';
    echo '</body></html>';
}


function draw_stylesheet()
{
    $setup = get_setup();

    $stylesheet = '?action=default_stylesheet&amp;time=' . date('Y_m_d-H_i', filemtime(__FILE__));

    printf('<link rel="stylesheet" href="%s" media="all" />', $stylesheet);

    $custom_css = $setup['css-customizations'];
    if ($custom_css) {
        printf('<style type="text/css" media="all">%s</style>', $custom_css);
    }

}



function draw_introduction()
{
    $setup = get_setup();
    if ($setup['introduction']) {
        printf('<div id="introduction">%s</div>', nl2br($setup['introduction']));
    }
}
function login_owner($password)
{
    if ($password) {
        $setup = get_setup();

        if ( ! isset($setup['login-counters'])) {
            $setup['login-counters'] = array();
        }
        $counter = 0;
        $blocked_until = null;
        if (isset($setup['login-counters'][$_SERVER['REMOTE_ADDR']])) {

            list($counter, $blocked_until) = $setup['login-counters'][$_SERVER['REMOTE_ADDR']];
            if ($blocked_until > time()) {
                set_error(t('LOGIN_BLOCKED'));
                return;
            }

        }

        if ($setup['password'] == get('password')) {
            $setup['owner-session'] = get_session_id();
            $setup['owner-ip'] = $_SERVER['REMOTE_ADDR'];
            unset($setup['login-counters'][$_SERVER['REMOTE_ADDR']]);

            save_setup($setup);
            return true;

        } else {
            $counter++;
            if ($counter >= 10) {
                $blocked_until = time() + 60; // 1 minute cooldown
                set_error(t('WRONG_PASSWORD'));
                set_error(t('LOGIN_BLOCKED'));
            } else {
                set_error(t('WRONG_PASSWORD'));
            }
            $setup['login-counters'][$_SERVER['REMOTE_ADDR']] = array($counter, $blocked_until);
            save_setup($setup);
        }
    }
}

function is_owner_mode()
{
    if ( ! file_exists(get_setup_file_name())) {
        return true;
    }

    $setup = get_setup();
    $owner_session = isset($setup['owner-session']) ? $setup['owner-session'] : null;
    $owner_ip      = isset($setup['owner-ip']) ? $setup['owner-ip'] : null;
    return $owner_session == get_session_id() && $owner_ip == $setup['owner-ip'];
}

function draw_owner_login()
{
    echo '<div class="form file">';
    echo '<form class="owner-login" method="post" action="?">';
    draw_site_error();
    printf('<label style="float:left" for="password">%s</label>', t('LABEL_PASSWORD'));
    echo '<input type="hidden" name="action" value="owner-login" />';
    echo '<input type="password" name="password" id="password" /><br />';
    echo '<div style="clear:both"></div>';
    printf('<button type="submit">%s</button>', t('BUTTON_LOGIN'));
    echo '</form>';
    echo '</div>';
    js_focus_to('password');
}

function draw_success_box()
{
    draw_site_error();
    echo '<p class="success">';
    if (sizeof(get_visible_uploads()) == 1) {
        printf('<strong>%s</strong>', t('SUCCESS_SINGLE'));
    } else {
        printf('<strong>%s</strong>', t('SUCCESS_MULTIPLE'));
    }
    printf(' <a href="?action=show-form">%s</a>', t('LINK_ADD_MORE'));
    echo '</p>';
}

function draw_owner_box()
{
    draw_site_error();
    echo '<p class="success">';
    printf(' <a href="?action=show-form">%s</a>', t('LINK_OWNER_UPLOAD'));
    echo '</p>';
}

function draw_upload_form()
{
    echo '<div class="file form">';

    $limit_text = null;
    $limit = get_upload_limit();
    if ($limit > 1000000) {
        $limit_text = t('UPLOAD_LIMIT', $limit / 1000000.0);
        $limit_text = " <em>$limit_text</em>";
    }

    $storage_limit = get_storage_limit_bytes();
    $storage_taken = get_size_of_storage_bytes();
    if ($storage_taken > $storage_limit) {
        set_error(t('ERR_STORAGE_EXCEEDED'));
    }

    printf('<h2>%s %s</h2>', t('UPLOAD_YOUR_FILE'), $limit_text);

    draw_site_error();

    if ($storage_taken <= $storage_limit) {
        echo '<form enctype="multipart/form-data" method="post" action="?">';

        echo '<input type="hidden" name="action" value="upload" />';

        require_once('in-a-flash/class.FlashUploader.php');
        IAF_display_js();
        $uploader = new FlashUploader('uploader', 'in-a-flash/uploader', 'http://drop.spicausis.lv/?session_id=' . get_session_id() . '%26action=flash-upload%26');
        // $uploader = new FlashUploader('uploader', 'in-a-flash/uploader', 'http://drop.spicausis.lv/?action=flash-upload#');
        $uploader->set('set_width', '880');
        $uploader->set('valid_extensions', '*.*'); // yes, I know what I'm doing

        $uploader->set('click_text', t('IAF_CLICK'));
        $uploader->set('uploading_text', t('IAF_UPLOADING'));
        $uploader->set('complete_text', t('IAF_COMPLETE'));
        $uploader->set('pending_text', t('IAF_PENDING'));
        $uploader->set('max_text', t('IAF_MAX'));
        $uploader->set('valid_text', t('IAF_VALID'));
        $uploader->set('size_failure_text', t('IAF_SIZE_FAILURE'));
        $uploader->set('progress_text', t('IAF_PROGRESS'));
        $uploader->set('auto_clear', '1');

        $uploader->display();

        printf('<p id="p-stock"><a id="a-stock" href="#" onclick="return show_stock_uploader();">%s</a></p>',
            t('LABEL_STOCK_INPUT'));
        echo '<div id="div-stock">';
        echo '<input name="file" type="file" /><br />';

        printf('<label for="description" id="description">%s</label>', t('LABEL_DESCRIPTION'));
        printf('<textarea rows="5" cols="40" name="description">%s</textarea><br />', htmlspecialchars(get('description')));
        printf('<button type="submit">%s</button>', t('BUTTON_UPLOAD'));
        echo '</div>';

        echo '</form>';
    }

    echo '</div>';
}

function draw_site_error()
{
    $error_text = get_error();
    if ($error_text) {
        printf('<p class="error">%s</p>', $error_text);
    }
}


function draw_visible_uploads()
{
    $all = get_visible_uploads();

    foreach($all as $id=>$entry) {

        echo '<div class="file">';

        echo '<ul>';

        if (is_owner_mode()) {
            printf('<li>%s, %s</li>',
                $entry['request']['REMOTE_ADDR'],
                date('d.m.Y H:i',  $entry['uploaded'])
            );
        }
        if (isset($entry['description']) && $entry['description']) {
            printf('<li><a href="%s">%s</a></li>', '?id=' . $id, t('LINK_EDIT_DESCRIPTION'));
        } else {
            printf('<li><a href="%s">%s</a></li>', '?id=' . $id, t('LINK_ADD_DESCRIPTION'));
        }
        printf('<li><a class="delete" href="%s">%s</a></li>', '?action=delete&amp;id=' . $id, t('LINK_ERASE'));
        echo '</ul>';

        printf('<h2><a href="%s">%s</a> <em>%s</em></h2>',
            '?action=download&amp;id=' . $id,
            htmlspecialchars($entry['original_name']),
            format_size($entry['size'])
        );

        if (get('id') == $id) {
            // draw editable form
            echo '<form method="post" action="?">';
            printf('<input type="hidden" name="action" value="save-edit" />');
            printf('<input type="hidden" name="id" value="%s" />', $id);
            printf('<textarea rows="5" cols="40" name="description" id="upload-description">%s</textarea>', htmlspecialchars($entry['description']));
            printf('<button type="submit">%s</button>', t('BUTTON_SAVE_EDIT'));
            echo '</form>';
            js_focus_to('upload-description');
        } else {
            if ($entry['description']) {
                printf('<div class="description">%s</div>',
                    nl2br(htmlspecialchars($entry['description'])));
            }
        }

        if (is_owner_mode() and ! sizeof($all)) {
            printf('<div class="file"><h2>%s</h2></div>', t('NO_FILES'));
        }

        echo '</div>';

    }
}
function draw_index_with_error($error /*, ...*/)
{
    $args = func_get_args();
    call_user_func_array('set_error', $args);
    on_page_index();
    exit;
}
function verify_storage_folder()
{
    $storage = get_storage_folder();

    if ( ! is_dir($storage)) {
        // attempt to create a storage folder
        @mkdir($storage, 0777, true);
    }

    clearstatcache();
    if ( ! is_dir($storage)) {
        set_error(t('ERR_MISSING_STORAGE', htmlspecialchars($storage)));
        return false;
    }

    if ( ! is_writable($storage)) {
        set_error(t('ERR_READONLY_STORAGE', htmlspecialchars($storage)));
        return false;
    }

    $htaccess_file = $storage . '/.htaccess';
    if ( ! file_exists($htaccess_file)) {
        // create default .htaccess
        $htaccess_contents = <<<HTACCESS
Order deny,allow
Deny from all
HTACCESS;
        @file_put_contents($htaccess_file, $htaccess_contents);
    }

    clearstatcache();
    if ( ! file_exists($htaccess_file)) {
        set_error(t('ERR_WRITE_FAILED', htmlspecialchars($htaccess_file)));
        return false;
    }

    return true;
}

function get_session_id()
{
    global $g_sid;

    if ( ! isset($g_sid)) {
        $symbols = '0123456789abcdefghijklmnopqrstuvwxyz';

        if (get('session_id') && preg_match("/^[$symbols]+$/", get('session_id'))) {
            // cookie is just a temporary random gibberish, so we don't care
            // user may try to spoof it, and he is welcome to to that
            $g_sid = get('session_id');
        } else if (isset($_COOKIE['dropbox_sid']) && preg_match("/^[$symbols]+$/", $_COOKIE['dropbox_sid'])) {
            // cookie is just a temporary random gibberish, so we don't care
            // user may try to spoof it, and he is welcome to to that
            $g_sid = $_COOKIE['dropbox_sid'];
        } else {
            $g_sid = null;
            list($usec, $sec) = explode(' ', microtime());
            mt_srand( (10000000000 * (float)$usec) ^ (float)$sec );
            for($i = 0 ; $i < 10; $i++) {
                $g_sid .= $symbols[mt_rand(0, strlen($symbols) - 1)];
            }
            setcookie('dropbox_sid', $g_sid);
        }
    }
    return $g_sid;
}
function is_already_uploaded($upload_entry)
{
    $all = get_setup();
    foreach($all['uploads'] as $existing) {
        if ($upload_entry['md5'] == $existing['md5']) {
            return true;
        }
    }
    return false;
}

function append_to_uploads($entry, $tmp_file)
{
    // add system information
    $entry['uploaded']      = time();
    $entry['request']       = $_SERVER;
    $entry['original_name'] = $entry['name'];
    $entry['session']       = get_session_id();

    $safe_name = safe_file_name($entry['name']);
    $entry['name'] = $safe_name;
    $n = 1;
    while(file_exists($entry['name'])) {
        // make filename unique
        $entry['name'] = $n . '_' . $safe_name;
        $n++;
    }

    rename($tmp_file, get_storage_folder() . '/' . $entry['name']);

    if (lock(get_storage_folder() . '/.lock', true)) {
        $setup = get_setup();
        $setup['uploads'][ md5(microtime()) ] = $entry;
        save_setup($setup);
        lock(get_storage_folder() . '/.lock', false);
    }

}

function lock( $lock_file, $acquire = true ) {
    static $handlers = array();

    if ($acquire === false) {
        if (isset($handlers[$lock_file])) {
            @fclose($handlers[$lock_file]);
            @unlink($file);
            unset($handlers[$lock_file]);
        } else {
            trigger_error("Lock '$lock_file' is already unlocked", E_USER_WARNING);
        }
    } else {
        if (!isset($handlers[$lock_file])) {
            $handler = false;
            $count = 100;
            do {
                if (!file_exists($lock_file) || @unlink($lock_file)) {
                    $handler = @fopen($lock_file, "x");
                }
                if (false === $handler) {
                    usleep(10000);
                } else {
                    $handlers[$lock_file] = $handler;
                }
            } while ( $handler === false && $count-- > 0);
        } else {
            trigger_error("Lock '$lock_file' is already locked", E_USER_WARNING);
        }
    }

    return isset($handlers[$lock_file]);
}


function get_setup_file_name()
{
    return get_storage_folder() . '/.setup';
}

function get_setup()
{
    $ser = @file_get_contents(get_setup_file_name());
    $setup = @unserialize($ser);
    if ($setup === false) {
        $setup = array();
    }
    array_set_default($setup, 'title', null);
    array_set_default($setup, 'password', 'master');
    array_set_default($setup, 'custom-stylesheet', null);
    array_set_default($setup, 'css-customizations', null);
    array_set_default($setup, 'introduction', null);
    array_set_default($setup, 'uploads', array());
    array_set_default($setup, 'storage-limit-mb', null);
    array_set_default($setup, 'language', 'en');
    array_set_default($setup, 'owner-session', get_session_id());
    array_set_default($setup, 'owner-ip', $_SERVER['REMOTE_ADDR']);

    return $setup;
}


function save_setup($all)
{
    return @file_put_contents(get_setup_file_name(), serialize($all));
}

function get_tmp_upload_name()
{
    $session_id = get_session_id();
    $storage = get_storage_folder();
    return $storage . '/' . $session_id . '_' . time() . '.tmp';
}
function remove_stale_upload()
{
    $file_name = get_tmp_upload_name();
    @unlink($file_name);
}
function get_all_uploads()
{
    $setup = get_setup();
    $uploads = $setup['uploads'];
    uasort($uploads, 'name_sort');
    return $uploads;
}

function name_sort($a, $b)
{
    if ($a['name'] > $b['name']) {
        return 1;
    } else {
        return $a['name'] == $b['name'] ? 0 : -1;
    }
}


function get_visible_uploads()
{
    $all = get_all_uploads();
    if (is_owner_mode()) {
        return $all;
    } else {
        $out = array();
        foreach($all as $id=>$upload) {
            if ($upload['session'] == get_session_id()) {
                $out[$id] = $upload;
            }
        }
        return $out;
    }
}


function init_session()
{
    // get_session_id will generated sid and set a cookie, if needed
    get_session_id();
}

function safely_get_file_entry($id)
{
    $visible = get_visible_uploads();
    if ( ! isset($visible[$id])) {
        draw_index_with_error(t('ERR_NO_FILE'));
    }
    return $visible[$id];
}
function get_storage_limit_bytes()
{
    $setup = get_setup();
    $limit_mb = isset($setup['storage-limit-mb']) && $setup['storage-limit-mb'] ? $setup['storage-limit-mb'] : null;
    if ($limit_mb) {
        return $limit_mb * 1000000;
    }
}

function get_size_of_storage_bytes()
{
    // nonrecursive. the subfolders
    $uploads = get_all_uploads();
    $total_bytes = 0;
    foreach($uploads as $upload) {
        $total_bytes += @filesize(get_storage_folder() . '/' . $upload['name']);
    }
    return $total_bytes;
}
function get_upload_limit()
{
    if (get_storage_limit_bytes()) {
        $soft_limit = get_storage_limit_bytes() - get_size_of_storage_bytes();
        if ($soft_limit > 0) {
            return min($soft_limit, get_php_upload_limit());
        } else {
            return 0; // space exceeded probably
        }
    }

    return get_php_upload_limit();
}
#
# /// languages and internationalization support
#
function get_site_language()
{
    $setup = get_setup();
    return $setup['language'];
}

function init_default_languages()
{
    $lv = <<<LANG
LANGUAGE                  Latviešu
FOOTER                    Veidojis <a href="http://spicausis.lv/">Einārs Lielmanis</a>, failu ielāde: <a href="http://inaflashuploader.com">in-a-flash</a>, krāsu gamma un grafiskie elementi: <a href="http://www.colourlovers.com/lover/doc%20w">doc w</a>.
CHANGE_SETTINGS           Mainīt iestatījumus
OWNER_TITLE               pārvaldīšana
OWNER_LOGIN               Saimnieka skats
OWNER_LOGOUT              Beigt darbu
LOGIN_BLOCKED             Pārāk daudz nepareizu minējumu. Autorizācija īslaicīgi bloķēta.
WRONG_PASSWORD            Parole nav pareiza.
LABEL_PASSWORD            Parole:
BUTTON_LOGIN              Autorizēties
SUCCESS_SINGLE            Paldies, fails ir saņemts.
SUCCESS_MULTIPLE          Paldies, faili ir saņemti.
LINK_ADD_MORE             Vai vēlies nosūtīt vēl kādu failu?
LINK_OWNER_UPLOAD         Vai vēlies pievienot kādu failu?
UPLOAD_LIMIT              %d MB ierobežojums
UPLOAD_YOUR_FILE          <strong>Pievieno</strong> savu failu:
LABEL_DESCRIPTION         Vieta nelielam aprakstam:
BUTTON_UPLOAD             <strong>Ielādē</strong> un nosūti failu
NO_FILES                  Nekas nav atsūtīts.
LINK_EDIT_DESCRIPTION     pielabot aprakstu
LINK_ADD_DESCRIPTION      pievienot aprakstu
LINK_ERASE                izdzēst
BUTTON_SAVE_EDIT          Saglabāt aprakstu
ERR_MISSING_STORAGE       Nav mapītes, kur glabāt failu, un nevaru to izveidot. Lūdzu, izveido mapīti <strong>%s</strong> un piešķir tai rakstīšanas tiesības.
ERR_READONLY_STORAGE      Nevaru neko ierakstīt mapītē <strong>%s</strong>. Lūdzu, piešķir tai rakstīšanas tiesības.
ERR_WRITE_FAILED          Neizdevās izveidot failu <strong>%s</strong>. Lūdzu, pārliecinies, ka mapītei ir rakstīšanas tiesības.
ERR_NO_FILE               <strong>Nav</strong> šāda faila.
DEFAULT_TITLE             failu <strong>pastkastīte</strong>
DEFAULT_INTRODUCTION      Izmantojot šo lapu, vari nosūtīt man savus failus.
LABEL_CFG_PASSWORD        Jaunā parole:
LABEL_CFG_TITLE           Lapas virsraksts:
LABEL_CFG_INTRODUCTION    Lapas ievadteksts:
LABEL_CFG_CUSTOM_CSS      Manuāli CSS pielāgojumi:
LABEL_CFG_LANGUAGE        Lapas valoda:
LABEL_CFG_STORAGE_LIMIT   Failiem atvēlētā vieta, MB
HINT_CFG_STORAGE_LIMIT    Cik vietas, megabaitos, atvēlēt failu krātuvei.<br />Šobrīd faili aizņem <strong>%.1f MB</strong>.
HINT_CFG_CUSTOM_CSS       CSS ir tehnoloģija, ar ko ir iespējams izmainīt lapas izskatu.<br />Piemēram, "body { background: red }" visai lapai piešķirs sarkanu fona krāsu.
BUTTON_CONFIG_SAVE        Saglabāt izmaiņas
LABEL_STOCK_INPUT         Pārslēgties uz vienkāršo faila ielādētāju — ja kaut kas nestrādā
ERR_NO_UPLOAD             Lūdzu, pievieno pašu failu.
ERR_TOO_BIG               Fails saņemts <strong>kļūdaini</strong>. Iespējams, ka tas ir <strong>par lielu?</strong>
ERR_BAD_UPLOAD            Fails saņemts <strong>kļūdaini</strong>.
ERR_CANNOT_MOVE           Nevaru pārvietot ielādēto failu uz <strong>%s</strong>.
ERR_DUPLICATE             Šāds fails te <strong>jau ir ielādēts</strong>, paldies.
ERR_EMPTY                 Ielādētais fails ir tukšs.
ERR_STORAGE_EXCEEDED      Diemžēl, pastkastītei ir beigusies atvēlētā diska vieta.
IAF_CLICK                 Noklikšķini, lai pievienotu failus!
IAF_UPLOADING             ...
IAF_COMPLETE              Ielādēts
IAF_PENDING               Gaida rindā
IAF_MAX                   Vairāk failus vienā reizē ielādēt nevar.
IAF_VALID                 Pieļaujamie faili
IAF_SIZE_FAILURE          Tik lielu failu nevaru ielādēt.
IAF_PROGRESS              Nosūtu (BYTES_LOADED / BYTES_TOTAL)
LANG;

    $en = <<<LANG
LANGUAGE                  English
FOOTER                    Written by <a href="http://bugpipe.org/">Einar Lielmanis</a>, <a href="http://inaflashuploader.com">In-A-Flash</a> upload widget, color scheme and images by <a href="http://www.colourlovers.com/lover/doc%20w">doc w</a>.
CHANGE_SETTINGS           Change settings
OWNER_TITLE               owner mode
OWNER_LOGIN               Owner mode
OWNER_LOGOUT              Logout
LOGIN_BLOCKED             Too many failed attempts: I've temporarily blocked the login form.
WRONG_PASSWORD            The password is incorrect.
LABEL_PASSWORD            Password:
BUTTON_LOGIN              Login
SUCCESS_SINGLE            Thank you, your file is received.
SUCCESS_MULTIPLE          Thank you, your files are received.
LINK_ADD_MORE             Would you like to send another file?
LINK_OWNER_UPLOAD         Would you like to upload a file?
UPLOAD_LIMIT              %d MB limit
UPLOAD_YOUR_FILE          <strong>Upload</strong> your file:
LABEL_DESCRIPTION         You can add a short description:
BUTTON_UPLOAD             <strong>Upload</strong> and send your file
NO_FILES                  Nothing received.
LINK_EDIT_DESCRIPTION     edit description
LINK_ADD_DESCRIPTION      add a description
LINK_ERASE                erase
BUTTON_SAVE_EDIT          Save description
ERR_MISSING_STORAGE       The storage folder is missing, and unable to create it. Create a folder <strong>«%s»</strong> and make it writable.
ERR_READONLY_STORAGE      The storage folder <strong>«%s»</strong> seems to be read-only. Make it writable, please.
ERR_WRITE_FAILED          Writing to a file <strong>«%s»</strong> failed. Please, check the folder permissions.
ERR_NO_FILE               No such file.
DEFAULT_TITLE             tiny <strong>file dropbox</strong>
DEFAULT_INTRODUCTION      Using this page, you can easily send me various files.
LABEL_CFG_PASSWORD        New password:
LABEL_CFG_TITLE           Page title:
LABEL_CFG_INTRODUCTION    Page introduction:
LABEL_CFG_LANGUAGE        Site language:
LABEL_CFG_STORAGE_LIMIT   Storage limit, MB:
LABEL_CFG_CUSTOM_CSS      Custom CSS overrides:
LABEL_STOCK_INPUT         Switch to non-flash file upload
HINT_CFG_CUSTOM_CSS       CSS is a technology which allows to change how the page looks.<br />As an example, specifying "body { background: red }" will make the background of the page turn red.
HINT_CFG_STORAGE_LIMIT    Maximum storage space, in megabytes, that the uploaded files may use.<br />Currently using <strong>%.1f MB</strong>.
BUTTON_CONFIG_SAVE        Saglabāt izmaiņas
BUTTON_CONFIG_SAVE        Save changes
ERR_NO_UPLOAD             You haven't attached any file.
ERR_TOO_BIG               There was a <strong>problem</strong> receiving your file. It is possible that it was <strong>too big</strong> for the server limits.
ERR_BAD_UPLOAD            There was a <strong>problem</strong> receiving your file.
ERR_CANNOT_MOVE           Cannot move the uploaded file to <strong>%s</strong>.
ERR_DUPLICATE             This file is <strong>already uploaded</strong>, thank you.
ERR_EMPTY                 The uploaded file is empty.
ERR_STORAGE_EXCEEDED      Sadly, the storage space is full.
IAF_CLICK                 Click here to upload your files.
IAF_UPLOADING             Uploading
IAF_COMPLETE              Upload complete.
IAF_PENDING               Upload pending...
IAF_MAX                   File maximum reached!
IAF_VALID                 Valid text
IAF_SIZE_FAILURE          Failed: size too big (must be <= MAX_FILE_SIZE).
IAF_PROGRESS              BYTES_LOADED / BYTES_TOTAL
LANG;

    add_language('lv', $lv);
    add_language('en', $en);
}


function add_language($language, $words)
{
    global $g_languages;
    if ( ! isset($g_languages[$language])) {
        $g_languages[$language] = $words;
    }
}


function parse_language($words)
{
    $words = explode("\n", $words);
    $out = array();
    foreach($words as $word) {
        if ($word) {
            list($tag, $word) = explode(' ', $word, 2);
            $word = ltrim($word);
            $out[$tag] = $word;
        }
    }
    return $out;
}


function t($tag /* ... */)
{
    global $g_languages, $g_parsed_language;
    if ( ! isset($g_parsed_language)) {

        $language = get_site_language();
        $g_parsed_language = parse_language( isset($g_languages[$language]) ? $g_languages[$language] : '' );

    }

    $word = isset($g_parsed_language[$tag]) ? $g_parsed_language[$tag] : $tag;
    if (func_num_args() > 1) {
        $args = func_get_args();
        $args[0] = $word;
        return call_user_func_array('sprintf', $args);
    } else {
        return $word;
    }

}

#
# /// global, generic functions
#
function etag_last_modified($last_modified)
{
    $etag = $last_modified;
    header("Etag: \"$etag\"");
    header('Last-Modified: ' . date('r', $last_modified));
    header('Accept-Ranges: bytes');

    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $etag) {
        header('HTTP/1.1 304 Not Modified');
        exit;
    }
}
function format_size($bytes)
{
    if ($bytes < 1000000) {
        return sprintf('%.1f KB', $bytes / 1000);
    }
    return sprintf('%.1f MB', $bytes / 1000000);
}
function get_php_upload_limit()
{
    // more like guessing

    $upload_max_filesize = bytes_from_shorthand(ini_get('upload_max_filesize'));
    $post_max_size = bytes_from_shorthand(ini_get('post_max_size'));
    $upload_max_filesize = min($upload_max_filesize, $post_max_size);

    $memory_limit = bytes_from_shorthand(ini_get('memory_limit'));
    if (function_exists('memory_get_usage')) {
        $memory_limit -= memory_get_usage();
    }
    $limit = $upload_max_filesize;
    if ($memory_limit && $memory_limit < $limit) {
        $limit = $memory_limit;
    }


    return $limit ? $limit : null;
}

function bytes_from_shorthand($ini_shorthand)
{
    $ini_shorthand = trim(strtolower($ini_shorthand));
    $converted = (int)$ini_shorthand;
    if ($ini_shorthand) {
        $prefix = substr($ini_shorthand, -1);
        if ($prefix == 'g') {
            $converted *= 1024 * 1024 * 1024;
        } elseif ($prefix == 'm') {
            $converted *= 1024 * 1024;
        } elseif ($prefix == 'k') {
            $converted *= 1024;
        }
    }
    return $converted;
}
function remove_magic_quotes($force_execution = false)
{
    if (get_magic_quotes_gpc() or $force_execution) {

        function walk_stripslashes(&$what)
        {
            if (is_array($what)) {
                array_walk($what, 'walk_stripslashes');
            } else {
                $what = stripslashes($what);
            }
        }

        if (is_array($_GET)) {
            array_walk($_GET, 'walk_stripslashes');
        }
        if (is_array($_POST)) {
            array_walk($_POST, 'walk_stripslashes');
        }
        if (is_array($_COOKIE)) {
            array_walk($_COOKIE, 'walk_stripslashes');
        }

    }
}

function get($name)
{
    $value = isset($_GET[$name]) ? $_GET[$name] : null;
    $value = ($value === Null and isset($_POST[$name])) ? $_POST[$name] : $value;
    return $value === Null ? Null : trim($value);
}

function get_int($name)
{
    $var = get($name);
    return $var === null ? null : (int)$var;
}
function set_error($message /*, ... */)
{
    global $g_error;

    $args = func_get_args();
    $message = call_user_func_array('sprintf', $args);

    $g_error[] = $message;
}

function get_error()
{
    global $g_error;
    if ($g_error) {
        return implode("\n<br />", $g_error);
    }
}
function array_set_default(&$array, $key, $default = null)
{
    if ( ! array_key_exists($key, $array)) {
        $array[$key] = $default;
    }
}


function js_focus_to($object)
{
    echo <<<JS
<script type="text/javascript">document.getElementById('$object').focus();</script>
JS;
}
# build a printable name from (latvian) text
# undecoded symbols are replaced with underscore
# utf-8 safe
function safe_name($text, $use_visual_mode = false)
{

    if ( ! $text) return '';

    $text = strtolower_utf($text);
    $translation_table = array(
        'ā' => 'a',
        'č' => 'c',
        'ē' => 'e',
        'ģ' => 'g',
        'ī' => 'i',
        'ķ' => 'k',
        'ļ' => 'l',
        'ņ' => 'n',
        'ō' => 'o',
        'š' => 's',
        'ū' => 'u',
        'ž' => 'z',
        'а' => 'a',
        'б' => 'b',
        'в' => $use_visual_mode ? 'b' : 'v',
        'г' => 'g',
        'д' => $use_visual_mode ? 'g' : 'd',
        'е' => 'e',
        'ё' => 'e',
        'ж' => 'z',
        'з' => $use_visual_mode ? '3' : 'z',
        'и' => $use_visual_mode ? 'u' : 'i',
        'й' => 'j',
        'к' => 'k',
        'л' => 'l',
        'м' => 'm',
        'н' => $use_visual_mode ? 'h' : 'n',
        'о' => 'o',
        'п' => 'p',
        'р' => $use_visual_mode ? 'p' : 'r',
        'с' => $use_visual_mode ? 'c' : 's',
        'т' => 't',
        'у' => $use_visual_mode ? 'y' : 'u',
        'ф' => 'f',
        'х' => $use_visual_mode ? 'x' : 'h',
        'ц' => 'c',
        'ч' => 'c',
        'ш' => 's',
        'щ' => 's',
        'ъ' => '',
        'ы' => 'i',
        'ь' => '',
        'э' => 'e',
        'ю' => 'u',
        'я' => 'j',

        // ukrainian support
        'ґ' => 'g',
        'і' => 'i',
        'ї' => 'i',
        'є' => 'e',
    );
    $text = strtr($text, $translation_table);
    $allowed_chars = 'abcdefghijklmnopqrstuvwxyz01234567890_';
    $out = '';
    for($i = 0 ; $i < strlen_utf($text) ; $i++) {
        $char = substr_utf($text, $i, 1);
        if (strlen($char) != 1) {
            $out .= '_';
        } else {
            if (strpos($allowed_chars, $char) !== FALSE) {
                $out .= $char;
            } else {
                $out .= '_';
            }
        }
    }

    $out = trim($out, '_');
    $out = preg_replace('/__+/u', '_', $out);
    $out = preg_replace('/--+/u', '-', $out);
    if ($out == '') $out = '_';

    return $out;
}

function safe_file_name($file_name, $force_extension = null)
{
    if (strpos($file_name, '.') === false) {
        $file_name .= '.';
    }
    if (($slash_pos = strrpos($file_name, '/')) !== false) {
        $file_name = substr($file_name, $slash_pos + 1);
    }
    if ($force_extension === null) {
        $force_extension = '.' . safe_name(substr($file_name, strrpos($file_name, '.') + 1));
    } else {
        if ($force_extension != '') {
            $force_extension = '.' . $force_extension;
        }
    }
    $f = substr($file_name, 0, strrpos($file_name, '.'));
    $out = rtrim(safe_name($f) . $force_extension, '.');
    return $out ? $out : Null;
}

function redirect($url = Null)
{
    if ($url and $url[0] == '?') {
        // attempt to correct the '?...' urls
        if (isset($_SERVER['SCRIPT_URI'])) {
            $url = $_SERVER['SCRIPT_URI'] . $url;
        } elseif (isset($_SERVER['HTTP_HOST']) and isset($_SERVER['REQUEST_URI'])) {
            // some servers don't set SCRIPT_URI
            if (strpos($_SERVER['REQUEST_URI'], '?') === FALSE) {
                $url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . $url;
            } else {
                $url = 'http://' . $_SERVER['HTTP_HOST'] . substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '?')) . $url;
            }

        }
    }

    @ob_get_contents();

    header("Location: $url");
    exit;
}
if ( ! function_exists('file_put_contents'))
{
    function file_put_contents($file, $data) {
        $f = @fopen($file, 'w');
        if ( ! $f) return false;

        fwrite($f, $data);
        fclose($f);
        return true;
    }
}
//
// !!! utf-8 support
//
if (function_exists('mb_convert_case')) {
    function strtoupper_utf($str) {
        return mb_convert_case($str, MB_CASE_UPPER, 'UTF-8');
    }
    function strtolower_utf($str) {
        return mb_convert_case($str, MB_CASE_LOWER, 'UTF-8');
    }
    function strlen_utf($str) {
        return mb_strlen($str, 'UTF-8');
    }
    function substr_utf($str, $from, $to) {
        return mb_substr($str, $from, $to, 'UTF-8');
    }

} else {
    function strtoupper_utf($str) {
        return strtoupper($str);
    }
    function strtolower_utf($str) {
        return strtolower($str);
    }
    function strlen_utf($str) {
        return strlen($str);
    }
    function substr_utf($str, $from, $to) {
        return substr($str, $from, $to);
    }
}

///
///
/// main
///
///

error_reporting(E_ALL);
ini_set('display_errors', 'on');
remove_magic_quotes();
init_session();
init_default_languages();
process_action(get('action'));



# phpFolding plugin _most_ recommended, http://www.vim.org/scripts/script.php?script_id=1623
# vim: set tw=120 ts=4 sts=4 sw=4 et : #


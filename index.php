<?php

/**
 * 
 * Tiny dropbox script
 * -------------------
 * Written by Einar Lielmanis, http://dropbox.bugpipe.org/
 * Bugs, thanks, suggestions: einar@spicausis.lv
 *
 * to allow your customizations together with simple upgrades of this script,
 * you can create a file called config.php and put overrides there.
 * Here is an example of the file:

     <?php
     $g_storage_folder = '/var/storage';
     $g_language = 'en';
     set_translation('DELETE', 'Erase completely');
     ?>

 * You can specify your own CSS file (or just some minor overrides) in owner's interface.
 * The default password for the interface is "master".
 *
 **/

 # all uploaded files, as well as configuration, will be stored here.
$g_storage_folder = 'files';
$g_language = 'lv';

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

        'default_stylesheet' => 'on_default_stylesheet',
        'upload'             => 'on_upload',
        'delete'             => 'on_delete',
        'download'           => 'on_download',
        'save-edit'          => 'on_save_edit',
        'config'             => 'on_config',
    );

    if ( ! isset($handlers[$action])) {
        // 404 would probably be better, but defaulting to index doesn't hurt
        $action = null;
    }

    $is_storage_folder_available = verify_storage_folder();

    if ( ! $is_storage_folder_available && $action != 'default_stylesheet') {
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
    background: #ccb url(images/pattern.gif) repeat;
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
input {
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
    float: left;
    clear: left;
}
.config input {
    width: 300px;
}
.config button {
    margin-left: 150px;
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
    draw_html_header();

    remove_stale_upload();

    if ( ! is_owner_mode()) {
        if (get('action') == 'upload' || get('action') == 'show-form' || sizeof(get_visible_uploads()) == 0) {

            if (sizeof(get_visible_uploads()) == 0) {
                draw_introduction();
            }

            draw_upload_form();
        } else {
            draw_success_box();
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

    $setup = get_setup();

    if (get('save')) {
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

        $css = get('custom_stylesheet');
        $setup['custom_stylesheet'] = $css;

        save_setup($setup);
        redirect('?');
    }

    draw_html_header();


    echo '<div class="file form config">';

    echo '<form method="post" action="?">';

    echo '<input type="hidden" name="action" value="config" />';
    echo '<input type="hidden" name="save" value="yes" />';
    printf('<label for="i_password">%s</label><input id="i_password" name="password" /><br />', 
        t('LABEL_CONFIG_PASSWORD'));

    printf('<label for="i_title">%s</label><input id="i_title" name="title" value="%s" /><br />',
        t('LABEL_CONFIG_TITLE'),
        htmlspecialchars($setup['title']));

    printf('<label for="i_intro">%s</label><textarea id="i_introduction" name="introduction">%s</textarea><br />',
        t('LABEL_CONFIG_INTRODUCTION'),
        htmlspecialchars($setup['introduction']));


    printf('<label for="i_css">%s</label><input id="i_css" name="custom_stylesheet" value="%s" /><br />',
        t('LABEL_CONFIG_CSS'),
        htmlspecialchars($setup['custom_stylesheet']));

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

    $tmp_file = get_tmp_upload_name();

    $move_res = @move_uploaded_file($file['tmp_name'], $tmp_file);
    if ( ! $move_res) {
        draw_index_with_error(t('ERR_CANNOT_MOVE', $move_res));
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
    $id = get_int('id');
    $entry = safely_get_file_entry($id);

    $all = get_setup();
    unset($all['uploads'][$id]);
    save_setup($all);
    @unlink(get_storage_folder() . '/' . $entry['name']);
    redirect('?');
}


function on_download()
{

    $entry = safely_get_file_entry(get_int('id'));
    header('Content-Type: ' . $entry['type']);
    header('Content-Length: ' . $entry['size']);
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $entry['original_name']) . '"');
    readfile(get_storage_folder() . '/' . $entry['name']);
    exit;
}
function on_save_edit()
{
    $id = get_int('id');
    safely_get_file_entry($id);

    $all = get_setup();
    $all['uploads'][$id]['description'] = get('description');
    save_setup($all);
    redirect('?');
}
#
# /// site specific functions
#
function get_storage_folder()
{
    global $g_storage_folder;
    return rtrim($g_storage_folder);
}
function draw_html_header()
{

    $setup = get_setup();
    $title = $setup['title'];

    if (is_owner_mode()) {
        $title .= ', pārvaldīšana';
    }

    header('Content-Type: text/html; charset=utf-8');

    echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
    echo '<html xmlns="http://www.w3.org/1999/xhtml">';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';


    printf('<title>%s</title>', strip_tags($title));

    draw_stylesheets();

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
        printf(' <a class="owner" href="?action=owner-logout">%s</a>', t('OWNER_LOGIN'));
    }
    echo '</p></div>';
    echo '</body></html>';
}


function draw_stylesheets()
{
    $stylesheet = '?action=default_stylesheet&amp;time=' . date('Y_m_d-H_i', filemtime(__FILE__));

    $setup = get_setup();
    if ($setup['custom_stylesheet']) {
        $stylesheet = $setup['custom_stylesheet'];
    }

    if ($stylesheet) {
        printf('<link rel="stylesheet" href="%s" media="all" />', $stylesheet);
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
    printf('<label style="float:left" for="password">%s:</label>', t('LABEL_PASSWORD'));
    echo '<input type="hidden" name="action" value="owner-login" />';
    echo '<input type="password" name="password" id="password" /><br />';
    echo '<div style="clear:both"></div>';
    printf('<button type="submit">Autorizēties</button>', t('BUTTON_LOGIN'));
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

function draw_upload_form()
{
    echo '<div class="file form">';

    $limit_text = null;
    $limit = get_upload_limit();
    if ($limit > 1000000) {
        $limit_text = t('UPLOAD_LIMIT', $limit / 1000000.0);
        $limit_text = " <em>$limit_text</em>";
    }
    printf('<h2>%s %s</h2>', t('UPLOAD_YOUR_FILE'), $limit_text);

    draw_site_error();

    echo '<form enctype="multipart/form-data" method="post" action="?">';

    echo '<input type="hidden" name="action" value="upload" />';
    echo '<input name="file" type="file" /><br />';
    printf('<label for="description" id="description">%s</label>', t('LABEL_DESCRIPTION'));
    printf('<textarea name="description">%s</textarea><br />', htmlspecialchars(get('description')));
    printf('<button type="submit">%s</button>', t('BUTTON_UPLOAD'));

    echo '</form>';

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
    if (is_owner_mode()) {
        foreach($all as $id=>$entry) {

            echo '<div class="file">';

            echo '<ul>';
            printf('<li>%s, %s</li>', 
                $entry['request']['REMOTE_ADDR'],
                date('d.m.Y H:i', $entry['uploaded'])
            );
            printf('<li><a class="delete" href="%s">%s</a></li>', '?action=delete&amp;id=' . $id, t('LINK_ERASE'));
            echo '</ul>';

            printf('<h2><a href="%s">%s</a> <em>%s</em></h2>',
                '?action=download&amp;id=' . $id,
                htmlspecialchars($entry['original_name']),
                format_size($entry['size'])
            );
            if ($entry['description']) {
                echo '<div class="description">';
                echo nl2br(htmlspecialchars($entry['description']));
                echo '</div>';
            }

            echo '</div>';
        }

        if ( ! sizeof($all)) {

            printf('<div class="file"><h2>%s</h2></div>', t('NO_FILES'));

        }

    } else {
        foreach($all as $id=>$entry) {

            echo '<div class="file">';

            echo '<ul>';
            if ($entry['description']) {
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

            if (get_int('id') == $id) {
                // draw editable form
                echo '<form method="post" action="?">';
                printf('<input type="hidden" name="action" value="save-edit" />');
                printf('<input type="hidden" name="id" value="%s" />', $id);
                printf('<textarea name="description" id="upload-description">%s</textarea>', htmlspecialchars($entry['description']));
                printf('<button type="submit">%s</button>', t('BUTTON_SAVE_EDIT'));
                echo '</form>';
                js_focus_to('upload-description');
            } else {
                if ($entry['description']) {
                    printf('<div class="description">%s</div>',
                        nl2br(htmlspecialchars($entry['description'])));
                }
            }

            echo '</div>';

        }
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

        if (isset($_COOKIE['dropbox_sid']) && preg_match("/^[$symbols]+$/", $_COOKIE['dropbox_sid'])) {
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

    $setup = get_setup();
    $setup['uploads'][ $entry['uploaded'] ] = $entry;
    save_setup($setup);

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
        $setup = get_default_setup();
    }
    return $setup;
}

function get_default_setup()
{
    return array(
        'title' => t('DEFAULT_TITLE'),
        'password' => 'master',
        'custom_stylesheet' => null,
        'introduction' => t('DEFAULT_INTRODUCTION'),
        'uploads' => array(),
    );
}

function save_setup($all)
{
    return @file_put_contents(get_setup_file_name(), serialize($all));
}

function get_tmp_upload_name()
{
    $session_id = get_session_id();
    $storage = get_storage_folder();
    return $storage . '/' . $session_id . '.tmp';
}
function remove_stale_upload()
{
    $file_name = get_tmp_upload_name();
    @unlink($file_name);
}
function get_visible_uploads()
{
    $setup = get_setup();
    $all = $setup['uploads'];
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
#
# /// languages and internationalization support
#
function init_default_languages()
{
    $lv = <<<LANG
LANGUAGE                  latviešu
FOOTER                    Veidojis <a href="http://spicausis.lv/">Einārs Lielmanis</a>, krāsu gamma un grafiskie elementi: <a href="http://www.colourlovers.com/lover/doc%20w">doc w</a>.
CHANGE_SETTINGS           Mainīt iestatījumus
OWNER_LOGIN               Saimnieka skats
OWNER_LOGOUT              Beigt darbu
LOGIN_BLOCKED             Pārāk daudz nepareizu minējumu. Autorizācija īslaicīgi bloķēta.
WRONG_PASSWORD            Parole nav pareiza.
LABEL_PASSWORD            Parole:
BUTTON_LOGIN              Autorizēties
SUCCESS_SINGLE            Paldies, fails ir saņemts.
SUCCESS_MULTIPLE          Paldies, faili ir saņemti.
LINK_ADD_MORE             Vai vēlies nosūtīt vēl kādu failu?
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
LABEL_CONFIG_PASSWORD     Jaunā parole:
LABEL_CONFIG_TITLE        Lapas virsraksts:
LABEL_CONFIG_INTRODUCTION Lapas ievadteksts:
LABEL_CONFIG_CSS          Ārējā css saite:
BUTTON_CONFIG_SAVE        Saglabāt izmaiņas
ERR_NO_UPLOAD             Lūdzu, pievieno pašu failu.
ERR_TOO_BIG               Fails saņemts <strong>kļūdaini</strong>. Iespējams, ka tas ir <strong>par lielu?</strong>
ERR_BAD_UPLOAD            Fails saņemts <strong>kļūdaini</strong>.
ERR_CANNOT_MOVE           Nevaru pārvietot ielādēto failu uz <strong>%s</strong>.
ERR_DUPLICATE             Šāds fails te <strong>jau ir ielādēts</strong>, paldies.
LANG;
    add_language('lv', $lv);
}


function add_language($language, $words)
{
    global $g_languages;
    $g_languages[$language] = $words;
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
    global $g_language, $g_languages, $g_parsed_language;
    if ( ! isset($g_parsed_language)) {

        $g_parsed_language = parse_language( isset($g_languages[$g_language]) ? $g_languages[$g_language] : '' );

    }

    $word = isset($g_parsed_language[$tag]) ? $g_parsed_language[$tag] : $tag;
    if (func_num_args() > 1) {
        $args = func_get_args();
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
    header('Etag: ' . $etag);
    header('Last-Modified: ' . date('r', $last_modified));

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
function get_upload_limit()
{
    // more like guessing

    $upload_max_filesize = bytes_from_shorthand(ini_get('upload_max_filesize'));
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
init_session();
init_default_languages();
process_action(get('action'));




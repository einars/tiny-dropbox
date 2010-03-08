<?php

/**
 * 
 * Tiny dropbox script
 * -------------------
 * Written by Einar Lielmanis, http://spicausis.lv
 * Bugs, thanks, suggestions: einar@spicausis.lv
 * 
 **/

error_reporting(E_ALL);
ini_set('display_errors', 'on');

$settings = array(

    'custom_stylesheet' => null,
    'storage_folder'    => 'files',
    'title'             => 'Ingas failu <strong>pastkastīte</strong>',

    );



process_action(get('action'));



#
# /// page actions
# 
function process_action($action)
{
    $site = array(
        ''                   => 'on_page_index',
        'default_stylesheet' => 'on_default_stylesheet',
        'upload'             => 'on_upload',
    );

    if ( ! isset($site[$action])) {
        // 404 would be better, but defaulting to index doesn't hurt
        $action = null;
    }

    $is_setup_good = verify_installation();

    if ( ! $is_setup_good && $action != 'default_stylesheet') {
        // hijack all actions except stylesheet, until the setup is not deemed to be good
        on_setup_required();
    } else {
        call_user_func($site[$action]);
    }

}



function on_default_stylesheet()
{
    header('Content-type: text/css; charset=utf-8');
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
div.file {
    border-top: 3px solid #ccb;
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
div.file h2 em {
    font-size: 12px;
    font-style: normal;
    font-weight: bold;
    color: #bbb;
}
div.file div.description {
    color: #777;
    font-size: 12px;
    padding: 0 0 10px 0;
    line-height: 150%;
    width: 700px;
}
input {
    margin-bottom: 4px;
}
textarea {
    width: 700px;
    height: 100px;
    padding: 2px;
    font-family: arial, sans-serif;
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
}
a {
    color: #545142;
}
a.delete {
    color: #f03;
}
CSS;
    exit;
}
function on_page_index()
{
    draw_html_header();

    draw_upload_form();

    draw_html_footer();
}

function on_setup_required()
{
    $error = get_site_error();
    draw_html_header();
    printf('<p class="error">%s</p>', $error);
    draw_html_footer();
}
function on_upload()
{
    set_site_error('Not yet <strong>implemented.</strong>');
    on_page_index();
}
#
# /// site specific functions
#
function get_storage_folder()
{
    $folder = get_setting('storage_folder', 'storage');
    $folder = rtrim($folder, '/');
    return $folder;
}
function draw_html_header()
{

    $title = get_setting('title', 'Tiny <strong>dropbox</strong>');

    echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
HTML;

    printf('<title>%s</title>', strip_tags($title));

    draw_stylesheets();

    echo '</head>';
    echo '<body>';
    echo '<div id="wrapper">';

    printf('<h1>%s</h1>', $title);

}


function draw_html_footer()
{

    echo '<div class="push"></div>';
    echo '</div>';
    printf('<div id="footer"><p>%s</p></div>',
        'Veidojis <a href="http://spicausis.lv/">Einārs Lielmanis</a>, krāsu gamma un grafiskie elementi: <a href="http://www.colourlovers.com/lover/doc%20w">doc w</a>.'
        );
    echo '</body></html>';
}


function draw_stylesheets()
{
    $default_stylesheet = '?action=default_stylesheet&amp;time=' . date('Y_m_d-H_i', filemtime(__FILE__));
    $stylesheet = get_setting('custom_stylesheet', $default_stylesheet);
    if ($stylesheet) {
        printf('<link rel="stylesheet" href="%s" media="all" />', $stylesheet);
    }

}



function draw_upload_form()
{
    $error_text = get_site_error();
    echo '<div class="file form">';

    $limit_text = null;
    $limit = get_upload_limit();
    if ($limit > 1000000) {
        $limit_text = sprintf(' <em>%d MB ierobežojums</em>', $limit / 1000000.0);
    }
    printf('<h2><strong>Pievieno</strong> savu failu: %s</h2>', $limit_text);

    if ($error_text) {
        printf('<p class="error">%s</p>', $error_text);
    }

    echo '<form enctype="multipart/form-data" method="post" action="?">';

    echo '<input type="hidden" name="action" value="upload" />';
    echo '<input name="file" type="file" /><br />';
    echo '<label for="description" id="description">Vieta nelielam aprakstam:</label>';
    printf('<textarea name="description">%s</textarea><br />', htmlspecialchars(get('description')));
    echo '<button type="submit"><strong>Ielādē</strong> un nosūti failu</button>';

    echo '</form>';

    echo '</div>';
}



function verify_installation()
{
    $storage = get_storage_folder();

    if ( ! is_dir($storage)) {
        // attempt to create a storage folder
        @mkdir($storage, 0777, true);
    }

    clearstatcache();
    if ( ! is_dir($storage)) {
        set_site_error('Missing folder where to store files. Please create a folder <strong>%s</strong> and make it writable.', 
            htmlspecialchars($storage));
        return false;
    }

    if ( ! is_writable($storage)) {
        set_site_error('Cannot write to folder <strong>%s</strong>. Make it writable, please.', 
            htmlspecialchars($storage));
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
        set_site_error('Creating file <strong>%s</strong> failed. The folder probably is not writable?', 
            htmlspecialchars($htaccess_file));
        return false;
    }

    return true;
}

#
# /// global, generic functions
#
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
function get_setting($name, $default = null)
{
    global $settings;
    if ( ! isset($settings[$name]) or ! $settings[$name]) {
        return $default;
    }
    return $settings[$name];
}


function get($name)
{
    $value = isset($_GET[$name]) ? $_GET[$name] : null;
    $value = ($value === Null and isset($_POST[$name])) ? $_POST[$name] : $value;
    return $value === Null ? Null : trim($value);
}


function set_site_error($message /*, ... */)
{
    global $g_error;

    $args = func_get_args();
    $message = call_user_func_array('sprintf', $args);

    $g_error[] = $message;
}

function get_site_error()
{
    global $g_error;
    if ($g_error) {
        return implode("\n<br />", $g_error);
    }
}


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

$g_storage_folder = 'files';

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

    remove_stale_upload();

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

    if ( ! isset($_FILES['file']) || ! $_FILES['file']['name']) {
        set_site_error('Lūdzu, <strong>pievieno</strong> savu failu.');
        return on_page_index();
    }

    $file = $_FILES['file'];

    if ($file['error']) {
        if ($file['size'] == 0) {
            set_site_error('Fails saņemts <strong>kļūdaini.</strong> Iespējams, ka tas ir <strong>par lielu?</strong>');
        } else {
            set_site_error('Fails saņemts <strong>kļūdaini.</strong>');
        }
        return on_page_index();
    }

    $tmp_file = get_tmp_upload_name();

    $move_res = @move_uploaded_file($file['tmp_name'], $tmp_file);
    if ( ! $move_res) {
        set_site_error('Nevaru pārvietot ielādēto failu uz <strong>%s</strong>.', $move_res);
        return on_page_index();
    }

    $entry = array(
        'md5' => md5($tmp_file),
        'type' => $file['type'],
        'size' => $file['size'],
        'name' => $file['name'],
        );

    if (is_already_uploaded($entry)) {
        set_site_error('Šāds fails te <strong>jau ir ielādēts,</strong> paldies.');
        return on_page_index();
    }

    append_to_uploads($entry, $tmp_file);

    set_site_error('bleh');
    on_page_index();
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
    $stylesheet = '?action=default_stylesheet&amp;time=' . date('Y_m_d-H_i', filemtime(__FILE__));

    $setup = get_setup();
    if ($setup['custom_stylesheet']) {
        $stylesheet = $setup['custom_stylesheet'];
    }

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
    return get_storage_folder() . '/setup.serialized';
}

function get_setup()
{
    $ser = @file_get_contents(get_setup_file_name());
    return $ser ? unserialize($ser) : get_default_setup();
}

function get_default_setup()
{
    return array(
        'title' => 'tiny file <strong>dropbox</strong>',
        'password' => 'master',
        'custom_stylesheet' => null,
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

get_session_id();
process_action(get('action'));




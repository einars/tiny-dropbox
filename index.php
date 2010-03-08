<?php

/*

Tiny dropbox script
-------------------

Written by Einar Lielmanis, http://spicausis.lv
Bugs, thanks, suggestions: einar@spicausis.lv

 */

$settings = array(

    'custom_stylesheet' => null,

    );


process_action(get('action'));








function process_action($action)
{
    $site = array(
        ''                   => 'draw_page_index',
        'default_stylesheet' => 'draw_default_stylesheet',
    );
    if ( ! isset($site[$action])) {
        // 404 would be better, but default to index now
        $action = null;
    }
    call_user_func($site[$action]);
}




function draw_html_header()
{

    echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Tiny dropbox</title>
HTML;

    draw_stylesheets();

    echo '</head>';
    echo '<body>';
    echo '<div id="wrapper">';

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


function draw_default_stylesheet()
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
function draw_page_index()
{
    draw_html_header();
    draw_html_footer();
}

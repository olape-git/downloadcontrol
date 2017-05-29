<?php

/**
 * Back-end of Downloadcontrol_XH
 * Copyright 2016 by svasti@svasti.de
 *
 * Last change: 10.08.2016 02:02:51
 *
 */

if (!defined('CMSIMPLE_XH_VERSION')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

/*
 * Register the plugin menu items.
 */
if (function_exists('XH_registerStandardPluginMenuItems')) {
    XH_registerStandardPluginMenuItems(true);
}

if (function_exists('XH_wantsPluginAdministration') 
        && XH_wantsPluginAdministration('downloadcontrol')
        || isset($downloadcontrol) && $downloadcontrol == 'true')
{
    Downloadcontrol_checkLogFiles();
    if(!isset($plugin_cf['downloadcontrol']['version'])
        || $plugin_cf['downloadcontrol']['version'] != DOWNLOADCONTROL_VERSION) {
        if($o .= Downloadcontrol_createConfig()) include $pth['folder']['plugins'] . 'downloadcontrol/config/config.php';
        $o .= Downloadcontrol_migrateLog();
        }
    $o .= print_plugin_admin('on');
    if (!$admin || $admin == 'plugin_main') {

        $o .= '<form method="post" style="display:inline-block;" action="'. $sn . '?downloadcontrol&admin=editcounter">'
            . '<button type="submit">'
            . $plugin_tx['downloadcontrol']['backend_edit_counter_and_analyse_log']
            . '</button></form>';
        $o .= '<form method="post" style="display:inline-block;" action="'. $sn . '?downloadcontrol&admin=editcolors">'
            . '<button type="submit">'
            . $plugin_tx['downloadcontrol']['backend_edit_colors']
            . '</button></form>';
        if (is_dir($pth['folder']['plugins'] . 'dlcounter/'))
            $o .= Downloadcontrol_dlcounterSync();
        $o .= '<div class="downloadcontrol_spacer"></div>';

        $o .= '<h1>Downloadcontrol_XH ' . DOWNLOADCONTROL_VERSION . '</h1>';
        if ($plugin_cf['downloadcontrol']['backend_number_of_shortcuts']) $o .= Downloadcontrol_shortcuts();
        $o .= Downloadcontrol_checkHtaccess() . '<div class="downloadcontrol_spacer"></div>'
            . Downloadcontrol_showPluginCalls() . '<div class="downloadcontrol_spacer"></div>'
            . Downloadcontrol_showGraph() . '<div class="downloadcontrol_spacer"></div>'
            . Downloadcontrol_showFileCount() . '<div class="downloadcontrol_spacer"></div>'
            . Downloadcontrol_readLog();

    } elseif ($admin == 'editcounter') {
        $o .= '<h1>Downloadcontrol_XH ' . DOWNLOADCONTROL_VERSION . '</h1>';
        $o .= Downloadcontrol_editCounter() . '<div class="downloadcontrol_spacer"></div>'
            . Downloadcontrol_analyseLog();
    } elseif ($admin == 'editcolors') {
        $o .= '<h1>Downloadcontrol_XH ' . DOWNLOADCONTROL_VERSION . '</h1>';
        $o .= Downloadcontrol_editColors() . '<div class="downloadcontrol_spacer"></div>'
            . Downloadcontrol_showColors();
    } else $o .= plugin_admin_common($action, $admin, $plugin);
}



function Downloadcontrol_dlcounterSync()
{
	global $pth, $plugin_cf, $plugin_tx;
    $o = '';
    $my_array = $my_short_array = $dl_array = $filecount = $newfilecount = $addcount = $addgraph = array();

    $o .= '<form method="post" style="display:inline-block;">'
        . '<button type="submit" name="dlcountersync">'
        . $plugin_tx['downloadcontrol']['backend_dlcountersync']
        . '</button></form>';

    if (isset($_POST['dlcountersync'])) {

        $dl_log_pth = empty($plugin_cf['dlcounter']['folder_data'])
            ? $pth['folder']['plugins'] . 'dlcounter/data/downloads.dat'
            : $pth['folder']['base'] . rtrim($plugin_cf['dlcounter']['folder_data'],'/') . '/data/downloads.dat';

        if (!is_file($dl_log_pth)) return $o . '<p class="xh_fail">'
                . $plugin_tx['downloadcontrol']['backend_dlcounter_notfound']
                . '</p>';

        $dl_log_lines = file($dl_log_pth, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($dl_log_lines as $key=>$value) {
            list($newkey,$newvalue) = explode("\t", $value, 2);
            // in case 2 entries have the same second, the 2nd will be
            // put 1 second later, otherwise it would get overwritten in the array
            while (isset($my_array[$newkey])) $newkey++;
        	$dl_array[$newkey] = $newvalue;
        }
        $my_log_pth = $pth['folder']['userfiles'] . 'plugins/downloadcontrol/log.txt';


        $my_log_lines = file($my_log_pth, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (count($my_log_lines)) {
            foreach ($my_log_lines as $key=>$value) {
                list($newkey,$newvalue) = explode("\t", $value, 2);
                while (isset($my_array[$newkey])) $newkey++;
            	$my_array[$newkey] = $newvalue;
                // 2nd array without the data of the downloaders
                list( , $file) = explode("\t", $value);
            	$my_short_array[$newkey] = $file;

                // count the download counts registered in my log
                if(isset($filecount[$file])) $filecount[$file]++;
                else $filecount[$file] = 1;
            }
            $forcounting = $my_short_array + $dl_array;
        } else {
            $forcounting = $dl_array;
            $my_short_array = array();
        }


        // find out which files get additional download counts in the combined log

        foreach ($forcounting as $key=>$file) {
            $addgraph[] = $key;
            if(isset($newfilecount[$file])) $newfilecount[$file]++;
            else $newfilecount[$file] = 1;
        }
        foreach ($newfilecount as $key=>$value) {
        	if (!isset($filecount[$key])) $addcount[$key] = $value;
            elseif ($filecount[$key] < $value) $addcount[$key] = $value - $filecount[$key];
        }
        if (!count($addcount)) return $o . '<p class="xh_info">'
                . $plugin_tx['downloadcontrol']['backend_dlcounter_unnecessary']
                . '</p>';


        // add to the file counter
       	Downloadcontrol_addToFilesCount($addcount);

        // generate a new graph counter
        Downloadcontrol_makeNewGraph($forcounting);


        // merge the logs, sort and save
        $my_newarray = $my_array + $dl_array;
        krsort($my_newarray);
        $my_newlog = '';
        foreach ($my_newarray as $key=>$value) {
            $my_newlog .= $key . "\t" . $value . PHP_EOL ;
        }
        if (!file_put_contents($my_log_pth, $my_newlog, LOCK_EX)) {
            e('cntwriteto','file',$my_log_pth);
            return $o;
        } 

        if (!$plugin_cf['downloadcontrol']['sync_dont_change_dlcounter_log']) {
            $dl_newarray = $dl_array + $my_short_array;
            ksort($dl_newarray);
            $dl_newlog = '';
            foreach ($dl_newarray as $key=>$value) {
                $dl_newlog .= $key . "\t" . $value . PHP_EOL ;
            }
            if (!file_put_contents($dl_log_pth, $dl_newlog, LOCK_EX)) e('cntwriteto','file',$dl_log_pth);
        }

        $o .= '<p class="xh_success">'
            . $plugin_tx['downloadcontrol']['backend_dlcountersync_ok']
            . '</p>';
    }

    return $o;
}


function Downloadcontrol_addToFilesCount($newcountaray)
{
    global $pth, $plugin_cf;

    $counter = $pth['folder']['userfiles'] . 'plugins/downloadcontrol/countfiles.txt';
    $counts = parse_ini_file($counter);

    foreach ($newcountaray as $file=>$count) {

        if(!mb_detect_encoding($file, 'UTF-8', true)) $file = utf8_encode($file);
        $file = str_replace(array('?','{','}','|','&','~','!','(',')','[',']','^','"','#','=',';'),'_',$file);

        if(isset($counts[$file])) $counts[$file] = $counts[$file] + $count;
            else $counts[$file] = $count;

        if ($plugin_cf['downloadcontrol']['separator_before_version_endings']) {
            if ($x = strpos($file,$plugin_cf['downloadcontrol']['separator_before_version_endings'])) {
                $total = substr($file,0,$x) . '_total';
                if(isset($counts[$total])) $counts[$total] = $counts[$total] + $count;
                    else $counts[$total] = $count;
            }
        }
    }

    ksort($counts);
    $newfile = "[Download Counts Per File] \n";
    foreach ($counts as $key => $value) {
        $newfile .= $key . ' = ' . $value . "\n";
    }

    file_put_contents($counter,$newfile,LOCK_EX);
}


function Downloadcontrol_makeNewGraph($log)
{
    global $pth;

    file_put_contents($pth['folder']['userfiles']
               . 'plugins/downloadcontrol/countforgraph.php','');

    foreach ($log as $key=>$value) {
        if ($key > strtotime("-35 day")) Downloadcontrol_countForGraph($key);
    }

}



function Downloadcontrol_shortcuts()
{
    global $pth, $plugin_cf, $plugin_tx, $tx;
    $ptx = $plugin_tx['downloadcontrol'];
    $o = '';

    $select = isset($_POST['dlcontrolshortcuts'])
        ? $_POST['dlcontrolshortcuts']
        : (isset($_COOKIE['dlcontrolshortcuts'])
        ? $_COOKIE['dlcontrolshortcuts']
        : 0);
    setcookie('dlcontrolshortcuts', $select);

    $o .= '<' . $plugin_cf['downloadcontrol']['backend_headlines'] . '>'
        . $plugin_tx['downloadcontrol']['headline_shortcuts']
        . '</' . $plugin_cf['downloadcontrol']['backend_headlines'] . '>';

    $o .= '<form method="post">';
    for ($i = 1; $i <= $plugin_cf['downloadcontrol']['backend_number_of_shortcuts'] ; $i++) {
        $class = $i == $select ? ' class="downloadcontrol_on"' : '';
        $o .= '<button type="submit" ' . $class . ' name="dlcontrolshortcuts" value="'
            . $i . '">' . $i . '</button>';
    }
    $o .= $select
        ? '<button type="submit" name="dlcontrolshortcuts" value="0">'
        . $plugin_tx['downloadcontrol']['backend_close'] . '</button>'
        : '';
    $o .= '</form>';

    if ($select) {
        if (isset($_POST['shortcut'])) {
            $newshortcut = $_POST['shortcut'];
            foreach ($newshortcut as $key=>$value) {
            	if (!$value) unset($newshortcut[$key]);
            }
            file_put_contents($pth['folder']['userfiles'] . 'plugins/downloadcontrol/shortcut'
                . $select . '.php',json_encode($newshortcut));
        }
        $shortcut = json_decode(file_get_contents($pth['folder']['userfiles']
            . 'plugins/downloadcontrol/shortcut' . $select . '.php'),true);

        $basefolder = trim($pth['folder']['downloads']
                    . $plugin_cf['downloadcontrol']['downloadcontrol_base_folder'], '/');

        $o .= '<form method="post" class="downloadcontrol_shortcut">'
            . '<input type="submit" value="' . utf8_ucfirst($tx['action']['save']) . '">';
        $o .= '<div>{{{control ' . $select . '}}}</div>';

        // Start+end dates?
        if (!isset($shortcut['startDate'])) $shortcut['startDate'] = '';
        $o .= '<input type="text" style="margin-top:.5em;" name="shortcut[startDate]" value="' . $shortcut['startDate'] . '"> <label>'
            . $plugin_tx['downloadcontrol']['backend_startdate'] . '</label><br>';
        if (!isset($shortcut['endDate'])) $shortcut['endDate'] = '';
        $o .= '<input type="text" name="shortcut[endDate]" value="' . $shortcut['endDate'] . '"> <label>'
            . $plugin_tx['downloadcontrol']['backend_enddate'] . '</label><br>';

        //password or login?
        if (!isset($shortcut['password'])) $shortcut['password'] = '';
        $password = $shortcut['password'] == 1 ? '' : $shortcut['password'];
        $o .= '<input type="text" name="shortcut[password]" value="' . $password . '"> <label>'
            . $plugin_tx['downloadcontrol']['backend_password'] . '</label> ';
        $login = $shortcut['password'] == 1 ? ' checked' : '';
        $o .= '<input type="checkbox" name="shortcut[password]"' . $login . ' value="1"> <label>'
            . $plugin_tx['downloadcontrol']['backend_login'] . '</label><br>';

        //button style?
        if (!isset($shortcut['style'])) $shortcut['style'] = '';
        $o .= '<select name="shortcut[style]">';
        foreach (array($ptx['backend_as_config'], 'table','button','button2','smallbutton','inline') as $value) {
            $select = $value == $shortcut['style'] ? ' selected' : '';
            $t = $value == $ptx['backend_as_config'] ? '' : $value ;
            $o .= '<option' . $select . ' value="' . $t . '">' . $value . '</option>';
        }
        $o .= '</select> <label>'
            . $plugin_tx['downloadcontrol']['backend_askstyle'] . '</label><br>';

        //ask for downloader's name?
        $o .= Downloadcontrol_radioButtons('askname',$ptx['backend_askname'],$shortcut) . '<br>';

        //show date of last file change?
        $o .= Downloadcontrol_radioButtons('date',$ptx['file_date'],$shortcut) . '<br>';

         //show count?
        $o .= Downloadcontrol_radioButtons('count',$ptx['file_downloads'] . ' ('
            . $ptx['file_this_version'] . ')',$shortcut) . '<br>';
        $o .= Downloadcontrol_radioButtons('countall',$ptx['file_downloads'] . ' ('
            . $ptx['file_all_versions'] . ')',$shortcut);

        //list all files in the downloadcontrol folder
        $o .= Downloadcontrol_listAllFiles($basefolder, 0, $shortcut);
        $o .= '<input type="submit" value="' . utf8_ucfirst($tx['action']['save']) . '">'. '</form>';
    }
	
    return $o;
}

function Downloadcontrol_radioButtons($name, $label, $shortcut)
{
    global $plugin_tx;
    $ptx = $plugin_tx['downloadcontrol'];
    $o = '';

        $o .= '<input type="radio" name="shortcut[' . $name . ']" ';
        if (!isset($shortcut[$name]) || ($shortcut[$name] !== '1' && $shortcut[$name] !== '0')) $o .= 'checked ';
        $o .= 'value="">'. $ptx['backend_as_config']  . ' ';
        $o .= '<input type="radio" name="shortcut[' . $name . ']" ';
        if (isset($shortcut[$name]) && $shortcut[$name] === '1') $o .= 'checked ';
        $o .= 'value="1">'. $label . ' ' ;
        $o .= '<input type="radio" name="shortcut[' . $name . ']" ';
        if (isset($shortcut[$name]) && $shortcut[$name] === '0') $o .= 'checked ';
        $o .= 'value="0">'. $ptx['backend_off'];

    return $o;
}




function Downloadcontrol_listAllFiles($folder, $i = 0, $shortcut, $subfolder = '', $warnarray = NULL)
{
    global $plugin_tx;

    $o = $warning = '';
    $dirarray = array();

    $subfolder = $subfolder ? $subfolder . '/' : '';
    $o .= '<p style="margin-left:' . $i . 'em;"><b>'
        . basename($folder) . '</b><br>';
    $scan = scandir($folder);
    static $allfiles = array();
    static $warnarray = array();
    $allfiles = array_merge($allfiles,$scan);
    foreach (array_count_values($allfiles) as $key=>$value) {
         if ($value > 1 && strpos($key,'.') !== 0 && !in_array($key, $warnarray)) {
            $warnarray[] = $key;
            $warning .= $warning ? '<br>' : '';
            $displayvalue = !mb_detect_encoding($key, 'UTF-8', true)
            ? utf8_encode($key)
            : $key;
            $warning .= sprintf($plugin_tx['downloadcontrol']['error_filename_not_unique'], $displayvalue, $value);
         }
    }

    foreach ($scan as $value) {
        if($value == '.' || $value == '..') continue;
        if (is_dir($folder . '/' . $value)) { 
            $dirarray[] = $folder . '/' . $value;
            $subfolderarray [] = $subfolder . $value;
        }
        else {
            $displayvalue = !mb_detect_encoding($value, 'UTF-8', true)
                ? utf8_encode($value)
                : $value;
            $checked = isset($shortcut[$subfolder . urlencode($value)]) && $shortcut[$subfolder . urlencode($value)]
                ? ' checked'
                : '';
            $o .= '<input type="hidden" name="shortcut['
               . $subfolder . urlencode($value) . ']" ' . $checked . ' value=0>'
               . '<input type="checkbox" name="shortcut['
               . $subfolder . urlencode($value) . ']" ' . $checked . ' value=1><label> '
               . $displayvalue . '</label><br>';
        }
    }
    if ($dirarray) $i++;
    foreach ($dirarray as $key=>$value) {
        $o .= Downloadcontrol_listAllFiles( $value , $i, $shortcut, $subfolderarray[$key], $warnarray);
    }
            
    return $o . '</p>' . ($warning ? '<p class="xh_warning">' . $warning . '</p>' : '');
}



function Downloadcontrol_showPluginCalls()
{
	global $plugin_tx;

    return '<button type="button" onClick="
if(document.getElementById(\'pluginCalls\').style.display == \'none\') {
    document.getElementById(\'pluginCalls\').style.display = \'inline\';
} else {
    document.getElementById(\'pluginCalls\').style.display = \'none\';
}">'
           . $plugin_tx['downloadcontrol']['backend_show_plugincall_info']
           . '</button><div id="pluginCalls" style="display:none">'
           . $plugin_tx['downloadcontrol']['backend_plugincall_info']
           . '</div>';
}



function Downloadcontrol_checkHtaccess()
{
	global $plugin_cf, $plugin_tx, $pth;
    $o = $ok = $t = '';
    $basefolder = $pth['folder']['downloads']
                . trim($plugin_cf['downloadcontrol']['downloadcontrol_base_folder'], '/');

    $o .= '<' . $plugin_cf['downloadcontrol']['backend_headlines'] . '>'
        . $plugin_tx['downloadcontrol']['headline_protection']
        . '</' . $plugin_cf['downloadcontrol']['backend_headlines'] . '>';

    if (is_file($basefolder . '/.htaccess')) {
        $ok .= '<img src="' . $pth['folder']['plugin_css']
            . 'success.png" style="height:16px;width:16px;vertical-align:baseline;margin:0"> ';
        $t .= $plugin_tx['downloadcontrol']['check-htaccess_base_folder_ok'];
    } else {
        $t .= ' ' . $plugin_tx['downloadcontrol']['check-htaccess_no_htaccess'];

        $scan = scandir($basefolder);
        foreach ($scan as $key => $value) {
            if($value == '.' || $value == '..') continue;
            if (is_dir($basefolder . '/' . $value)) {
                if (is_file($basefolder . '/' . $value . '/.htaccess')) {
                    $t .= '<br><img src="' . $pth['folder']['plugin_css']
                        . 'success.png" style="height:16px;width:16px;vertical-align:baseline;margin:0"> '
                        . sprintf($plugin_tx['downloadcontrol']['check-htaccess_subfolder_ok'], $value);
                }
            }
        }
    }

    $o .= '<p>' . $ok . $plugin_tx['downloadcontrol']['backend_base_folder_info']
        . ' ' . $basefolder . ' ' . $t . '.';

    if (is_file($pth['folder']['userfiles'] . 'plugins/downloadcontrol/.htaccess')) {
        $o .= '<br><img src="' . $pth['folder']['plugin_css']
            . 'success.png" style="height:16px;width:16px;vertical-align:baseline;margin:0"> '
            . $plugin_tx['downloadcontrol']['check-htaccess_log_ok'] . '.';
    } else {
        $o .= '<br><img src="' . $pth['folder']['plugin_css']
            . 'warning.png" style="height:16px;width:16px;vertical-align:baseline;margin:0"> '
            . $plugin_tx['downloadcontrol']['check-htaccess_log_failed'] . '.';
    }

    return '<p>' . $o . '</p>';
}



function Downloadcontrol_checkLogFiles()
{
    global $pth, $plugin_cf;

    $logfolder = $pth['folder']['userfiles'] . 'plugins/downloadcontrol';
    if (!is_dir($logfolder)) mkdir($logfolder, 0777, true);
    if (!is_writable($logfolder)) e('cntwriteto', 'folder', $logfolder);

    for ($i = -2; $i <= $plugin_cf['downloadcontrol']['backend_number_of_shortcuts'] ; $i++) {
        $check = $i == -2
            ? $logfolder . '/log.txt'
            : ($i == -1
            ? $logfolder . '/countfiles.txt'
            : ($i == 0
            ? $logfolder . '/countforgraph.php'
            : $logfolder . '/shortcut' . $i . '.php'));
        if (!is_file($check)) file_put_contents($check,'');
        if (!is_writable($check)) e('cntwriteto', 'file', $check);
    }
}



function Downloadcontrol_migrateLog()
{
    global $pth, $plugin_tx;
    $o = '';

    $logfolder = $pth['folder']['userfiles'] . 'plugins/downloadcontrol';
    $oldlogfolder = $pth['folder']['plugins'] . 'downloadcontrol/log';

    if (is_file($oldlogfolder . '/log.txt') && filesize($logfolder . '/log.txt') == false) {
        $oldlog = file_get_contents($oldlogfolder . '/log.txt');
        file_put_contents($logfolder . '/log.txt', $oldlog);
        $o .= 'log.txt, ';
    }
    if (is_file($oldlogfolder . '/countfiles.txt') && filesize($logfolder . '/countfiles.txt') == false) {
        $oldcounter = file_get_contents($oldlogfolder . '/countfiles.txt');
        file_put_contents($logfolder . '/countfiles.txt', $oldcounter);
        $o .= 'countfiles.txt, ';
    } 
    if (is_file($oldlogfolder . '/countforgraph.php') && filesize($logfolder . '/countforgraph.php') == false) {
        $oldgraph = file_get_contents($oldlogfolder . '/countforgraph.php');
        file_put_contents($logfolder . '/countforgraph.php', $oldgraph);
        $o .= 'countforgraph.php ';
    } 

    return $o? '<p class="xh_info">' . $o . $plugin_tx['downloadcontrol']['update_log_transfer'] . '</p>' : '';
}



function Downloadcontrol_readLog()
{
    global $pth,$plugin_tx,$plugin_cf;
    $o = '';
    $log = $pth['folder']['userfiles'] . 'plugins/downloadcontrol/log.txt';

    If(isset($_POST['editfilecounter']) || isset($_POST['savefilecounter'])) {
        $o .= Downloadcontrol_analyseLog();
    } else {

        $fd = fopen ($log, "r");
        $lines = array();
        $i = 0;
        while (!feof ($fd) && $i < $plugin_cf['downloadcontrol']['backend_number_of_log_entries_shown'])
        {
           $buffer = fgets($fd, 4096);
           if ($buffer) $lines[] = $buffer;
           $i++;
        }
        fclose ($fd);
        $o .= '<' . $plugin_cf['downloadcontrol']['backend_headlines'] . '>'
            .  $plugin_tx['downloadcontrol']['headline_recent_log']
            . '</' . $plugin_cf['downloadcontrol']['backend_headlines'] . '>';
    
        $o .=  '<p>';
        foreach ($lines as $line) {
            list($when,$what) = explode("\t", $line, 2);
        	$o .= date($plugin_cf['downloadcontrol']['backend_dateformat'], intval($when)) . ' — ' . $what . '<br>';
        }
        $o .= '</p>';
    }

    return $o;
}



function Downloadcontrol_count($all = false)
{
	global $pth, $plugin_cf;
    $counts = array();

    foreach (Downloadcontrol_logToArray() as $key=>$value) {
        if(!mb_detect_encoding($value, 'UTF-8', true)) $value = utf8_encode($value);
        $value = str_replace(array('?','{','}','|','&','~','!','(',')','[',']','^','"','#','=',';'),'_',$value);
        if ($x = strpos($value, $plugin_cf['downloadcontrol']['separator_before_version_endings'])) {
            $total = substr($value,0,$x) . '_total';
            if(isset($counts[$total])) $counts[$total]++;
                else $counts[$total] = 1;
        }
        if ($all) {
            if(isset($counts[$value])) $counts[$value]++;
                else $counts[$value] = 1;
        }
    }

    $countfile = $pth['folder']['userfiles'] . 'plugins/downloadcontrol/countfiles.txt';
    $oldcounts = parse_ini_file($countfile);

    $newcounts = $all
        ? $counts
        : $counts + $oldcounts;

    ksort($newcounts);
    $newfile = "[Download Counts Per File] \n";
    foreach ($newcounts as $key => $value) {
        $newfile .= $key . ' = ' . $value . "\n";
    }

    file_put_contents($countfile,$newfile,LOCK_EX);
}



function Downloadcontrol_editCounter()
{
	global $pth, $plugin_tx, $plugin_cf, $tx, $sn;
    $o = '';

    $counter = $pth['folder']['userfiles'] . 'plugins/downloadcontrol/countfiles.txt';

    if (isset($_POST['savefilecounter'])
        && isset($_POST['filecounter'])
        && !isset($_POST['dontsavefilecounter']))  {
        $new = rtrim($_POST['filecounter']) . PHP_EOL;
        $new = str_replace(array('?','{','}','|','&','~','!','(',')','^','"','#',';'),'',$new);
        file_put_contents($counter, $new, LOCK_EX);
    }
    if (isset($_POST['counttotals'])) Downloadcontrol_count();
    if (isset($_POST['newcounter'])) Downloadcontrol_count(true);

    $filecounts = file_get_contents($counter);

    $o .= '<form method="POST">' . "\n"
       .  '<button type="submit" name="savefilecounter">'
       .  utf8_ucfirst($tx['action']['save'])
       .  '</button>'
       .  '<button type="submit" name="dontsavefilecounter">'
       .  utf8_ucfirst($tx['action']['cancel'])
       .  '</button>'
       .  '<button type="submit" name="newcounter">'
       .  $plugin_tx['downloadcontrol']['backend_generate_count']
       .  '</button>'
       .  '<button type="submit" name="counttotals">'
       .  $plugin_tx['downloadcontrol']['backend_count_all_versions']
       .  '</button>'
       .  '<textarea  class="membp_log" name="filecounter" id="filecounter">'
       .  $filecounts
       .  '</textarea>'
       .  '</form>' . "\n";

    return $o;
}




function Downloadcontrol_logToArray()
{
	global $pth;
    $new_array = array();

	$log_pth = $pth['folder']['userfiles'] . 'plugins/downloadcontrol/log.txt';

    $log_lines = file($log_pth, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($log_lines as $key=>$value) {
        list($newkey,$newvalue) = explode("\t", $value);
        while (isset($new_array[$newkey])) $newkey++;
        $new_array[$newkey] = $newvalue;
    }
    return $new_array;
}



function Downloadcontrol_showFileCount()
{
	global $pth, $plugin_tx, $plugin_cf, $tx, $sn;
    $pcf = $plugin_cf['downloadcontrol'];
    $ptx = $plugin_tx['downloadcontrol'];

    $o = '';

    $o .= '<' . $pcf['backend_headlines'] . '>'
       . $ptx['headline_count_per_file']
       . '</' . $pcf['backend_headlines'] . '>';

    $counter = $pth['folder']['userfiles'] . 'plugins/downloadcontrol/countfiles.txt';

    $counts = parse_ini_file($counter);
    if ($pcf['backend_counter_order'] == 'alphabet') {
        ksort($counts);
    } else arsort($counts);

    $o .= '<table class="downloadcontrol_filecount"><tr><th colspan="2">' . $ptx['file_name']
        . '<span style="float:right;">'
        . $ptx['file_downloads'] . '</span></th></tr>';
    foreach ($counts as $key=>$value) {
        $o .= '<tr><td>' . $key . '</td><td style="text-align:right">' . $value . '</td></tr>';
    }
    $o .= '</table>';

    return $o;
}



function Downloadcontrol_showGraph()
{
	global $pth, $plugin_tx, $plugin_cf;
    $o = '';

    $gcounter = $pth['folder']['userfiles'] . 'plugins/downloadcontrol/countforgraph.php';

    $downloads = json_decode(file_get_contents($gcounter),true);
    $today = date('y') * 365 + date('z');
    $weekdays = explode(',', $plugin_tx['downloadcontrol']['weekdays']);

    $newdownloads = array();
    $stats = array(array());
    // Generate values for the last 30 days
    for ($i = 0; $i < 30; $i++) {
        $j = $newdownloads[$today - $i] = isset($downloads[$today - $i])
            ? $downloads[$today - $i]
            : 0;

        $stats['d'][$i] = date('j',strtotime("-$i days"));
        $stats['w'][$i] = str_replace(
                            array(0,1,2,3,4,5,6),
                            $weekdays,
                            date('w',strtotime("-$i days")));
        $stats['dl'][$i] = $j;
    }

    // write back values for the last 30 days, erasing older entries, so that the
    // list doesn't get too long
    file_put_contents($gcounter,json_encode($newdownloads));

    $max_dl = max($stats['dl']);
    if($max_dl < 10) $max_dl = 10;

    $height = 200;
    $ratio = $height / $max_dl;

    $o .= '<' . $plugin_cf['downloadcontrol']['backend_headlines'] . '>'
        . $plugin_tx['downloadcontrol']['headline_this_month']
        . '</' . $plugin_cf['downloadcontrol']['backend_headlines'] . '>'
        . '<div style="border:solid 1px #999; background-color:#fafafa; height:'
        . ($height + 36) . 'px; padding-top:10px; padding-right:5px;width:cal(100% - 5px);'
        . ' font:9px/1.6 Verdana,sans-serif;color:black;text-align:center;overflow:hidden;">';

    foreach ($stats['dl'] as $key=>$value) {
    	$o .= '<div style="margin-top:'
            . round($height - $value * $ratio) . 'px; border-top:solid ' . (round($value * $ratio) + 1)
            . 'px #5a7; width:3.15%; margin-right:1px; overflow:hidden; float:right; box-sizing:border-box"'
            . ' title="Downloads: ' . $value . '">'
            . $stats['w'][$key]
            . '<br>'
            . $stats['d'][$key]
            . '</div>';
    }
    $o .= '</div>';

    return $o;
}



function Downloadcontrol_analyseLog()
{
    global $pth, $plugin_tx, $plugin_cf;
    $pcf = $plugin_cf['downloadcontrol'];
    $ptx = $plugin_tx['downloadcontrol'];
    $o = '';

    $log = file($pth['folder']['userfiles'] . 'plugins/downloadcontrol/log.txt',FILE_IGNORE_NEW_LINES);

    $timecount = $filecount = array();

    foreach ($log as $key=>$value) {
        $x = explode("\t",$value);

        $time = date('Y-m', intval($x[0]));
        $file = $x[1];

        if(isset($timecount[$time])) $timecount[$time]++;
        else $timecount[$time] = 1;

        if(isset($filecount[$file])) $filecount[$file]++;
        else $filecount[$file] = 1;
    }

    ksort($filecount);
    $o .= '<' . $pcf['backend_headlines'] . '>'
        . $ptx['headline_log_analysis'] . ': '
        . $ptx['headline_count_per_file']
        . '</' . $pcf['backend_headlines'] . '>';

    $o .= '<table class="downloadcontrol_filecount"><tr><th colspan="2">' . $ptx['file_name']
        . '<span style="float:right;">'
        . $ptx['file_downloads'] . '</span></th></tr>';
    foreach ($filecount as $key=>$value) {
        $o .= '<tr><td>' . $key . '</td><td style="text-align:right">' . $value . '</td></tr>';
    }
    $o .= '</table>';


    $max_dl = $timecount ? max($timecount) : 0;
    if($max_dl < 10) $max_dl = 10;
    $ratio = 15 / $max_dl;

    ksort($timecount);
    $o .= '<' . $pcf['backend_headlines'] . '>'
        . $ptx['headline_log_analysis'] . ': '
        . $ptx['headline_downloads_per_month']
        . '</' . $pcf['backend_headlines'] . '>'
        . '<table class="downloadcontrol_stats">';
    foreach ($timecount as $key=>$value) {
    	$o .= '<tr><td>' . $key . '</td>'
            . '<td> : &nbsp; </td>'
            . '<td style="text-align:right;">'
            . $value . '</td>'
            . '<td><div style="height:1.2em;margin-left:1.5em;width:'
            . round($value * $ratio) . 'em;background:#5a7;"></div></td></tr>';
    }
    $o .= '</table>';

    return $o;
}



/**
 * Prepares the creation of config items with default values or pre-existing ones
 */
function Downloadcontrol_createConfig()
{
	global $pth ,$plugin_tx;

    // make sure that the plugin css really gets put into the generated plugincss
    touch($pth['folder']['plugins'] . 'downloadcontrol/css/stylesheet.css');

    $text = '<?php' . "\n\n"
          . Downloadcontrol_findConfigValue(array(
              'downloadcontrol_base_folder;;download_folder',
              'email_send',
              'email_to',
              'email_from',
              'alternative_file_name_encoding;Windows-1252',
              'backend_number_of_shortcuts;9',
              'backend_number_of_log_entries_shown;50',
              'backend_dateformat;d.m.Y, H:i',
              'backend_headlines;h5',
              'backend_counter_order;alphabet',
              'single-link_show_file_size;true;frontend_show_file_size',
              'single-link_show_1_version_downloads;true',
              'single-link_show_all_version_downloads',
              'single-link_style;button;frontend_style',
              'single-link_ask_login;;frontend_only_for_members',
              'single-link_ask_password',
              'single-link_ask_for_name;;frontend_ask_for_name',
              'multiple-links_show_date;true',
              'multiple-links_dateformat;d.m.Y',
              'multiple-links_show_1_version_downloads;true',
              'multiple-links_show_all_version_downloads',
              'multiple-links_ask_login',
              'multiple-links_ask_password',
              'multiple-links_ask_for_name',
              'password',
              'separator_before_version_endings;_',
              'sync_dont_change_dlcounter_log'))
          . '$plugin_cf[\'downloadcontrol\'][\'version\']="'
          . DOWNLOADCONTROL_VERSION . '";' . "\n"
          . "\n" . '?>' . "\n";

    $config = $pth['folder']['plugins'] . 'downloadcontrol/config/config.php';

    if (!file_put_contents($config, $text)) {
        e('cntwriteto', 'file', $config);
        return false;
    } else {
      // give out notice that updating was successful
      return '<div style="display:block; width:100%; border:1px solid red;'
             . 'margin:2em 0;">'
             . '<h4 style="text-align:center; margin:0; padding:.5em;"> '
             . sprintf($plugin_tx['downloadcontrol']['update_config'], DOWNLOADCONTROL_VERSION)
             . '</h4></div>';
    }
}



/**
 * Checks if old config value exists and creates new config values
 */
function Downloadcontrol_findConfigValue($itemArray)
{
	global $plugin_cf;
    $o = '';

    foreach ($itemArray as $value) {
        list($item, $default, $oldname) = array_pad(explode(';',$value), 3, '');
        $name = $oldname ? $oldname : $item;
        $value = isset($plugin_cf['downloadcontrol'][$name])
            ? $plugin_cf['downloadcontrol'][$name]
            : (isset($plugin_cf['downloadcontrol'][$item])
              ? $plugin_cf['downloadcontrol'][$item]
              : $default);

        $o .= '$plugin_cf[\'downloadcontrol\'][\'' . $item . '\']="'
                . $value . '";' . "\n";
    }
    return $o;
}


function Downloadcontrol_editColors()
{
	global $pth,$hjs,$plugin_tx;
    $o = '<p>' . $plugin_tx['downloadcontrol']['backend_color_change'] . '<p>';

    if (isset($_POST['dlc_colorchange']) && isset($_POST['dlc_color'])) {
        $css = file_get_contents($pth['file']['plugin_stylesheet']);

        switch ($_POST['dlc_color']) {
            case 'red':
                $col      = 'white';
                $startbg  = '#f99';
                $endbg    = '#600';
                $hovercol = 'black';
                $hoverbg  = '#fcc';
                $tablebg  = '#920';
                break;
            case 'blue':
                $col      = 'white';
                $startbg  = '#9af';
                $endbg    = '#016';
                $hovercol = 'black';
                $hoverbg  = '#cdf';
                $tablebg  = '#05c';
                break;
            case 'green':
                $col      = 'white';
                $startbg  = '#8c9';
                $endbg    = '#241';
                $hovercol = 'black';
                $hoverbg  = '#cfd';
                $tablebg  = '#465';
                break;
            case 'black':
                $col      = 'white';
                $startbg  = '#bbb';
                $endbg    = '#000';
                $hovercol = 'black';
                $hoverbg  = '#def';
                $tablebg  = '#000';
                break;
            default:
                $col      = 'black';
                $startbg  = '#fff';
                $endbg    = '#bbb';
                $hovercol = 'black';
                $hoverbg  = '#cef';
                $tablebg  = '#eee';
        }

        if ($_POST['dlc_colorchange'] == 'button2') { 
            $pattern1 = '!\.downloadcontrol\.alt\s*button\s{'
                      . '\s*color:\s*(\S*)'
                      . '\s*background:\s*(\S*)'
                      . '\s*background-image:(.*)!';
            $pattern2 = '!\.downloadcontrol\.alt\s*button:hover\s*\{'
                      . '\s*color:\s*(\S*)'
                      . '\s*background:\s*(\S*)!';

            $pattern = array($pattern1,$pattern2);

            $replace1 = ".downloadcontrol.alt button {\n"
                      . "    color:$col;\n"
                      . "    background:$tablebg;\n"
                      . "    background-image: linear-gradient(to bottom, $startbg 0%, $endbg 100%);";
            $replace2 = ".downloadcontrol.alt button:hover {\n"
                      . "    color:$hovercol;\n"
                      . "    background:$hoverbg;";

            $replace = array($replace1,$replace2);

        } elseif ($_POST['dlc_colorchange'] == 'table') {
            $pattern = '!\.downloadcontrol_table\s*\.dlc_table\s*\.dlc_th,'
                      . '\s*\.downloadcontrol_filecount\s*th\s*\{'
                      . '\s*color:\s*(\S*)'
                      . '\s*background:\s*(\S*)!';

            $replace = ".downloadcontrol_table .dlc_table .dlc_th,\n.downloadcontrol_filecount th {\n"
                      . "    color:$col;\n"
                      . "    background:$tablebg;";


        } else {
            $pattern1 = '!\.downloadcontrol\s*button,'
                      . '\s*\.downloadcontrol\s*div\s*\{'
                      . '\s*color:\s*(\S*)'
                      . '\s*background:\s*(\S*)'
                      . '\s*background-image:(.*)!';
            $pattern2 = '!\.downloadcontrol\s*button:hover,'
                      . '\s*\.downloadcontrol_small\s*button:hover\s*\{'
                      . '\s*color:\s*(\S*)'
                      . '\s*background:\s*(\S*)!';

            $pattern = array($pattern1,$pattern2);

            $replace1 = ".downloadcontrol button,\n.downloadcontrol div {\n"
                      . "    color:$col;\n"
                      . "    background:$tablebg;\n"
                      . "    background-image: linear-gradient(to bottom, $startbg 0%, $endbg 100%);";
            $replace2 = ".downloadcontrol button:hover,\n.downloadcontrol_small button:hover {\n"
                      . "    color:$hovercol;\n"
                      . "    background:$hoverbg;";

            $replace = array($replace1,$replace2);
        }

        $css = preg_replace($pattern, $replace, $css);
        file_put_contents($pth['file']['plugin_stylesheet'], $css);
        $hjs .= '<style>' . $css . '</style>';
    }

    $o .= '<form method="post">'
        . '<input type="radio" name="dlc_color" value="standard">Standard '
        . '<input type="radio" name="dlc_color" value="red">Red '
        . '<input type="radio" name="dlc_color" value="blue">Blue '
        . '<input type="radio" name="dlc_color" value="green">Green '
        . '<input type="radio" name="dlc_color" value="black">Black '
        . '<button type="submit" name="dlc_colorchange"  value="standard">button</button>'
        . '<button type="submit" name="dlc_colorchange"  value="table">table</button>'
        . '<button type="submit" name="dlc_colorchange"  value="button2">button2</button>'
        . '</form>';

    return $o;
}

function Downloadcontrol_showColors()
{
    global $plugin_cf;
	$o = '';
    $plugin_cf['downloadcontrol']['downloadcontrol_base_folder'] = '../images/flags/';

    $o .= '<hr><br>';
    $o .= 'button:<br>{{{control "de.gif","","","","button"}}}' . '<br>';
    $o .= 'button2:<br>{{{control "de.gif","","","","button2"}}}' . '<br>';
    $o .= 'small:<br>{{{control "de.gif","","","","small"}}}' . '<br>';
    $o .= 'inline:<br>{{{control "de.gif","","","","inline"}}}' . '<br>';
    $o .= 'password:<br>{{{control "de.gif","","","test"}}}' . '<br>';
    $o .= 'table:<br>{{{control "de.gif,da.gif,en.gif"}}}';
    return evaluate_scripting($o);
}
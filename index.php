<?php
/**
 * Frontend of downloadcontrol
 * (c) 2016 by svasti
 * last edit: 10.08.2016 02:02:36
 *
 */

define('DOWNLOADCONTROL_VERSION','1.7.1');
if (!isset($plugin_cf['downloadcontrol']['version'])
    || $plugin_cf['downloadcontrol']['version'] != DOWNLOADCONTROL_VERSION) {
    include_once $pth['folder']['plugins'] . 'downloadcontrol/config/defaultconfig.php';
}



/**
 * Function to be called by user
 */
function control($calledfile ='', $startDate = '', $endDate = '', $password = '', $style = '', $askname = '', $count = '', $countall = '') {

    global $plugin_cf, $plugin_tx, $pth, $tx, $downloadcontrol_base_folder;
    $pcf = $plugin_cf['downloadcontrol'];
    $ptx = $plugin_tx['downloadcontrol'];

    $o = $size = $error = '';
    $table = $member = $multifile = false;

    if (is_numeric($calledfile)) {
        $shortcutfile = file_get_contents($pth['folder']['userfiles']
                . 'plugins/downloadcontrol/shortcut' . $calledfile . '.php');
        if (strlen($shortcutfile) < 4) {
            return '<p class="xh_warning">' . sprintf($ptx['error_shortcut'], $calledfile) . '</p>';
        } 
        $shortcut = json_decode($shortcutfile,true);
        $startDate = isset($shortcut['startDate']) ? $shortcut['startDate'] : '';
        $endDate   = isset($shortcut['endDate'])   ? $shortcut['endDate']   : '';
        $password  = isset($shortcut['password'])  ? $shortcut['password']  : '';
        $style     = isset($shortcut['style'])     ? $shortcut['style']     : '';
        $askname   = isset($shortcut['askname'])   ? $shortcut['askname']   : '';
        $count     = isset($shortcut['count'])     ? $shortcut['count']     : '';
        $countall  = isset($shortcut['countall'])  ? $shortcut['countall']  : '';
        unset($shortcut['startDate'],$shortcut['endDate'],$shortcut['password'],
            $shortcut['style'],$shortcut['askname'],$shortcut['count'],$shortcut['countall']);
        $calledfile = '';
        foreach ($shortcut as $key=>$value) {
            if ($value) $calledfile .= $calledfile? ',' . urldecode($key) :  urldecode($key);
        }
    }
    $date = isset($shortcut['date']) && $shortcut['date']!== ''
        ? $shortcut['date']
        : $pcf['multiple-links_show_date'];

    switch(TRUE) {
        case $style == 'inline':
            $style = '_inline';
            break;
        case $style == 'small':
        case $style == 'smallbutton':
            $style = '_small';
            break;
        case $style == 'button':
            $style = '';
            break;
        case $style == 'button2':
            $style = ' alt';
            break;
        case $style == 'table':
            $style = '';
            $table = $multifile = true;
            break;
        case ($pcf['single-link_style'] == 'inline'):
            $style = '_inline';
            break;
        case ($pcf['single-link_style'] == 'smallbutton'):
            $style = '_small';
            break;
        case ($pcf['single-link_style'] == 'table'):
            $style = '';
            $table = $multifile = true;
            break;
        default:
            $style = '';
    } 

    if ($startDate || $endDate) {

        $start = $startDate
            ? strtotime($startDate)
            : 1420066800 ;
        $end = $endDate
            ? strtotime($endDate)
            : 2147483647;
        $enabled = (time() >= $start) && (time() <= $end)
            ? true
            : false;

    } else $enabled = true;


    $fileencoding = $pcf['alternative_file_name_encoding'];

    $dlfolder = isset($downloadcontrol_base_folder)
        ? $downloadcontrol_base_folder
        : ($pcf['downloadcontrol_base_folder']
            ? trim($pcf['downloadcontrol_base_folder'],'/') .'/'
            : '');

    // differentiation between $calledfile, which may have windows
    // encoding and $realfile, the resulting filename on the server
    $realfile = $calledfile;

    // check if a folder or a list of files is called
    // 1st case list of files
    if (strpos($realfile,',')) {
        $table = true;
        $multifile = true; 
    // 2nd folder
    } elseif (is_dir($pth['folder']['downloads'] . $dlfolder . $realfile)) {
        $table = true;
    // 3rd single file. If not found try converting the encoding of the file name and try again
    } elseif (!$table && !is_readable($pth['folder']['downloads'] . $dlfolder . $calledfile)) {
        // if file cannot be found, try alternative code file
        $realfile = mb_convert_encoding($calledfile, $fileencoding, "UTF-8");
        if (!is_readable($pth['folder']['downloads'] . $dlfolder . $realfile)) {
            e('notreadable', 'file', $pth['folder']['downloads'] . $dlfolder . $calledfile);
            return;
        }
    }

    // check if giving downloads count is wanted
    $count = $count !== ''
        ? $count
        : ($table
        ? $pcf['multiple-links_show_1_version_downloads']
        : $pcf['single-link_show_1_version_downloads']);
    $countall = $countall !== ''
        ? $countall
        : ($table
        ? $pcf['multiple-links_show_all_version_downloads']
        : $pcf['single-link_show_all_version_downloads']);

    // check if member's log-in is required,
    // and if this is set in config for folder or single file download
    if ($password == '1'
        || $pcf['single-link_ask_login'] && !$table
        || $pcf['multiple-links_ask_login'] && $table ) {
        $member = true;
        $password = '';
    }
    // check if password is set in config or plugin call
    $password = $password !== '' && $password != '1'
        ? $password
        : ($pcf['single-link_ask_password'] && !$table
        || $pcf['multiple-links_ask_password'] && $table
        ? $pcf['password']
        : '');

    // check if asking for downloader's name is wanted
    // plugin call, and config folder and single file setting gets checked
    $askname = $askname !== ''
        ? $askname
        : (!$table &&  $pcf['single-link_ask_for_name'] || $table && $pcf['multiple-links_ask_for_name']
        ? true
        : false);


    // for single file downloads, see if file size and nr of downloads are wanted
    if(!$table) {
        if ($pcf['single-link_show_file_size']) $size .= Downloadcontrol_showFileSize($realfile);
        if ($count && !$countall) {
            $size .= $size ? ', ' : '';
            $size =  controlcount(basename($calledfile)) . ' ' . $ptx['file_downloads'];
        }
        if ($countall) {
            $size .= $size ? ', ' : '';
            $one = controlcount(basename($calledfile));
            $t = strpos(basename($calledfile), $pcf['separator_before_version_endings']);
            $all = controlcount(substr(basename($calledfile), 0, $t) . '_total');
            if (!$count) {
                $size .=  $all? $all : $one;
                $size .=  ' ' . $ptx['file_downloads'];
            } else {
                $size .=  $all > $one
                    ? $ptx['file_downloads'] . ': ' . $one . ' '
                    . $ptx['file_this_version'] . ', ' . $all . ' ' . $ptx['file_all_versions']
                    : $one . ' ' . $ptx['file_downloads'];
            }
        }
        $size = ' <span class="downloadcontrol_size">(' . $size .')</span>';
    }


    // file_id gives each plugin call an 8 cifer id used for $_POST names and anchors
    $file_id = abs(crc32($calledfile)); 

    // $enabled means no objection from date checking feature
    if ($enabled) {
        // if $_POST is there, check if download should start
        if (isset($_POST['downloadcontrol' . $file_id])) {

                $name = isset($_SESSION['fullname']) && $_SESSION['fullname'] != ''
                     ? $_SESSION['fullname']
		     : 'IP ' . ($pcf['get_anonym_Ip']== 'true' ? preg_replace('/[0-9]+\z/', '0', $_SERVER['REMOTE_ADDR']) : $_SERVER['REMOTE_ADDR']);

            if ($password && $_POST['dlcontrolpw' . $file_id] != trim($password)) {
                $error = $ptx['error_password'];
            }

            if ($askname && strlen($_POST['dlcontrolname' . $file_id]) >= 6) {
                $name = $_POST['dlcontrolname' . $file_id];
                if(!mb_detect_encoding($name, 'UTF-8', true)) $name = utf8_encode($name);
                $name = htmlspecialchars($name);
            } elseif ($askname && strlen($_POST['dlcontrolname' . $file_id]) < 6) $error = '? ? ?';


            if(!$error) {

                if ($table) {
                    // if a folder is displayed, $_POST is sending the file name urlencoded
                    $realfile = urldecode($_POST['downloadcontrol' . $file_id]);
                    $calledfile = !mb_detect_encoding($realfile, 'UTF-8', true)
                        ? utf8_encode($realfile)
                        : $realfile;
                } 

                if ($pcf['email_send']) downloadcontrol_SendMail($calledfile,$name);


                Downloadcontrol_log($calledfile,$name);
                Downloadcontrol_countForGraph();
                Downloadcontrol_countFiles($calledfile);

                // send desired file
                // actually there seems to be no clear way to know if the user is really downloading the file, as the
                // download starts right away as soon as the link is clicked. If the user then abandons the download,
                // the downloaded part is simply not stored while the server continues sending data until
                // connection_aborted() is detected. Short files are usually downloaded before a user decides to abandon.
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $realfile . '"');
                header('Content-Transfer-Encoding: binary');
                header('Content-Length: ' . filesize($pth['folder']['downloads'] . $dlfolder . $realfile));
                ob_clean();
                readfile($pth['folder']['downloads'] . $dlfolder . $realfile);
                exit();
            }
        }

        // Here the HTML display starts
        //=============================
        if ($password && !$table) {

            $o .= '<a class="downloadcontrol_anchor"  id="anchor' . $file_id . '"></a>'
                . '<form class="downloadcontrol" method="post" action="#anchor'
                . $file_id . '">'
                . '<div>'
                . '<span class="downloadcontrol_dl">'
                . basename($calledfile) . $size
                . '</span>'

                . '<span class="downloadcontrol_pw"><br>'
                . $ptx['password']
                . ' <input type="text" name="dlcontrolpw' . $file_id
                . '" size="8" value="' . $error .'"> '
                . '<button type="submit" name="downloadcontrol' . $file_id
                . '" onclick="
                       setTimeout(
                        function() {
                        document.getElementsByName(\'dlcontrolpw' . $file_id . '\')[0].value = \'\';
                        }
                        , 1000)
                    " >'
                . $tx['action']['ok']
                . '</button>'
                . '</span>'
                . '</div>'

                . '</form>'
                . "\n";

        } elseif ($member && !isset($_SESSION['username']) && !$table) {

            $o .= '<div class="downloadcontrol">'
                . '<div>'
                . '<span class="downloadcontrol_dl">'
                . basename($calledfile)
                . '</span>'

                . '<span class="downloadcontrol_pw"><br>'
                . $ptx['login_first']
                . '</span>'
                . '</div>'

                . '</div>'
                . "\n";

        } elseif ($table) {

            $filearray = $multifile
                ? explode(',',$realfile)
                : scandir($pth['folder']['downloads'] . $dlfolder . $realfile);
            $o .= '<a class="downloadcontrol_anchor"  id="anchor' . $file_id . '"></a>';
            $o .=  '<form class="downloadcontrol_table" action="#anchor' . $file_id . '" method="post">';
            if ($member && !isset($_SESSION['username'])) $o .= '<p>' . $ptx['login_first'] . '</p>';
            if ($askname) $o .= '<p>' . $ptx['askname_folder_links']
                . ' <input type="text" name="dlcontrolname' . $file_id
                . '" size="30" maxlength="50" value="' . $error .'"></p>';
            if ($password) $o .=  '<p>' . $ptx['password']
                . ' <input type="text" name="dlcontrolpw' . $file_id . '" size="8" value="' . $error .'"></p>';
            $o .=  '<div class="dlc_table"><div class="dlc_tr"><div class="dlc_th">'
                . $ptx['file_name'] . '</div><div class="dlc_th">' . $ptx['file_size'] . '</div>';
            if ($date) $o .= '<div class="dlc_th">' . $ptx['file_date'] . '</div>';
            if ($count) $o .= '<div class="dlc_th">' . $ptx['file_downloads'] . '</div>';
            if ($countall) {
                $o .= $count
                    ? '<div class="dlc_th">' . $ptx['file_all_versions'] . '</div>'
                    : '<div class="dlc_th">' . $ptx['file_downloads'] . '</div>';
            } 
            $o .=  '</div>';
            $folder = $realfile? $realfile . '/' : '';
            foreach ($filearray as $key => $value) {
                if ($multifile || ($value != '.' && $value != '..' && $value != '.htaccess'
                    && !is_dir($pth['folder']['downloads'] . $dlfolder . $folder .  $value))) {

                    $calledvalue = !mb_detect_encoding($value, 'UTF-8', true)
                        ? utf8_encode($value)
                        : $value;
                    if ($multifile) {
                        $calledvalue = basename($value);
                        $folder = '';
                        if (!is_readable($pth['folder']['downloads'] . $dlfolder . $value)) {
                            e('notreadable', 'file', $pth['folder']['downloads'] . $dlfolder . $value);
                            return;
                        }
                    }
                    $o .= '<div class="dlc_tr"><div class="dlc_td">';
                    $o .= $member && !isset($_SESSION['username'])
                        ? $calledvalue
                        : '<button type="submit" name="downloadcontrol' . $file_id . '" value="'
                        . urlencode($folder . $value) . '">'
                        . $calledvalue . '</button>';
                    $o .= '</div><div class="dlc_td">'
                        .  Downloadcontrol_showFileSize($folder . $value)
                        . '</div>';
                    if ($date) $o .= '<div class="dlc_td">'
                        . date($pcf['multiple-links_dateformat'], filemtime($pth['folder']['downloads']
                        . $dlfolder . $folder .  $value)) . '</div>';
                    $one = controlcount($calledvalue);
                    if ($count) $o .= '<div class="dlc_td">' . $one . '</div>';
                    if ($countall) {
                        $t = strpos($calledvalue, $pcf['separator_before_version_endings']);
                        $all = controlcount(substr($calledvalue, 0, $t) . '_total');
                        $o .= $all > $one
                            ? '<div class="dlc_td">' . $all . '</div>'
                            : ($count
                            ? '<div class="dlc_td">–</div>'
                            : '<div class="dlc_td">' . $one . '</div>');
                    } 
                    $o .=  '</div>';
                }
            }
            $o .= '</div></form>';

        } elseif ($askname && !$table) {

            $o .= '<a class="downloadcontrol_anchor"  id="anchor' . $file_id . '"></a>'
                . '<form class="downloadcontrol" method="post" action="#anchor'
                . $file_id . '" id="' . $file_id . '">'
                . '<div>'
                . '<span class="downloadcontrol_dl">'
                . basename($calledfile) . $size
                . '</span>'

                . '<span class="downloadcontrol_pw"><br>'
                . $ptx['askname_single_links']
                . '<br>'
                . '<input type="text" name="dlcontrolname' . $file_id
                . '" size="30" maxlength="50" value="' . $error .'">'
                . '<button type="submit" name="downloadcontrol' . $file_id
                . '" onclick="
                       setTimeout(
                        function() {
                        document.getElementsByName(\'dlcontrolname' . $file_id . '\')[0].value = \'\';
                        }
                        , 1000)
                    " >'
                . $tx['action']['ok']
                . '</button>'
                . '</span>'
                . '</div>'

                . '</form>'
                . "\n";

        } else {

            $o .= '<form class="downloadcontrol' . $style . '" method="post">'
                . '<button type="submit" name="downloadcontrol' . $file_id . '">'
                . '<span class="downloadcontrol_dl' . $style . '">'
                . basename($calledfile) . '</span>' . $size
                . '</button>'
                . '</form>'
                . "\n";
        }


    }
    return $o;
}



/**
 * Calculate the file size
 */
function Downloadcontrol_showFileSize($file)
{
    global $plugin_cf, $pth, $downloadcontrol_base_folder;

    $dlfolder = isset($downloadcontrol_base_folder)
        ? $downloadcontrol_base_folder
        : ($plugin_cf['downloadcontrol']['downloadcontrol_base_folder']
            ? trim($plugin_cf['downloadcontrol']['downloadcontrol_base_folder'],'/') .'/'
            : '');

    $size = filesize($pth['folder']['downloads'] . $dlfolder . $file);
    switch ($size) {
        case $size < 1000: $size = $size . ' B';
        	break;

        case $size < 1000000: $size = round($size, -3)/1000 . ' kB';
        	break;

        default: $size = round($size/1000000, 1) . ' MB';
        	break;
    }

    return $size;
}



/**
 * Send email to admin on initiating a download
 */
function Downloadcontrol_sendMail($file,$name)
{
     global $plugin_cf, $plugin_tx;

    $to = $plugin_cf['downloadcontrol']['email_to'];
    $from = $plugin_cf['downloadcontrol']['email_from'];
    $subject = $plugin_tx['downloadcontrol']['text_email_subject'];

    //Mail-Nachricht erstellen und versenden
    $headers = 'From: ' . $from . "\r\n"
        . 'Reply-To: ' . $from . "\r\n"
        . 'Content-Type: text/plain;charset=utf-8' . "\r\n"
        . 'X-Mailer: PHP/' . phpversion();
    $message = sprintf($plugin_tx['downloadcontrol']['text_email'], date('d.m.Y, H:i'), $file, $name);
    mail($to, $subject, $message, $headers);
}



/**
 * Write download information into a log file
 */
function Downloadcontrol_log($file,$name)
{
    global $pth;

    if(!mb_detect_encoding($file, 'UTF-8', true)) $file = utf8_encode($file);
    if(!mb_detect_encoding($name, 'UTF-8', true)) $name = utf8_encode($name);

    $log_new = time() . "\t" . basename($file) . "\t". $name. "\n";

    $file = $pth['folder']['userfiles'] . 'plugins/downloadcontrol/log.txt';

    $handle = fopen($file, "r+b");
    flock($handle, LOCK_EX);
    $len = strlen($log_new);
    $final_len = filesize($file) + $len;
    $log_old = fread($handle, $len);
    rewind($handle);
    $i = 1;
    while (ftell($handle) < $final_len) {
        fwrite($handle, $log_new);
        $log_new = $log_old;
        $log_old = fread($handle, $len);
        fseek($handle, $i * $len);
        $i++;
    }
    flock($handle, LOCK_UN);
    fclose($handle);
}



function Downloadcontrol_countForGraph($date = '')
{
    global $pth;

    $datecounter = $date
        ? date('y',$date) * 365 + date('z',$date)
        : date('y') * 365 + date('z');

    $downloads = json_decode(file_get_contents($pth['folder']['userfiles']
               . 'plugins/downloadcontrol/countforgraph.php'),true);
    if(isset($downloads[$datecounter])) $downloads[$datecounter]++;
        else $downloads[$datecounter] = 1;

    file_put_contents($pth['folder']['userfiles']
        . 'plugins/downloadcontrol/countforgraph.php',json_encode($downloads));
}



function Downloadcontrol_countFiles($file, $fullcount = true)
{
    global $pth, $plugin_cf;

    $counter = $pth['folder']['userfiles'] . 'plugins/downloadcontrol/countfiles.txt';
    $counts = parse_ini_file($counter);
    if(!mb_detect_encoding($file, 'UTF-8', true)) $file = utf8_encode($file);

    $file = str_replace(array('?','{','}','|','&','~','!','(',')','[',']','^','"','#','=',';'),'_',basename($file));

    if ($fullcount) {
        if(isset($counts[$file])) $counts[$file]++;
            else $counts[$file] = 1;
    }

    if ($plugin_cf['downloadcontrol']['separator_before_version_endings']) {
        if ($x = strpos($file,$plugin_cf['downloadcontrol']['separator_before_version_endings'])) {
            $total = substr($file,0,$x) . '_total';
            if(isset($counts[$total])) $counts[$total]++;
                else $counts[$total] = 1;
        }
    }

    ksort($counts);
    $newfile = "[Download Counts Per File] \n";
    foreach ($counts as $key => $value) {
        $newfile .= $key . ' = ' . $value . "\n";
    }

    file_put_contents($counter,$newfile,LOCK_EX);
}



function controlcount($file)
{
    global $pth;

    $counter = $pth['folder']['userfiles'] . 'plugins/downloadcontrol/countfiles.txt';
    $counts = parse_ini_file($counter);
    $file = str_replace(array('?','{','}','|','&','~','!','(',')','[',']','^','"','#','=',';'),'_',$file);

    return isset($counts[$file])? $counts[$file] : 0;
}

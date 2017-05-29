<?php

$plugin_tx['downloadcontrol']['askname_folder_links']="Before downloading enter your name and location";
$plugin_tx['downloadcontrol']['askname_single_links']="Please give your name and<br>location for our information";
$plugin_tx['downloadcontrol']['backend_as_config']="as config";
$plugin_tx['downloadcontrol']['backend_askname']="Ask for downloader's name/location";
$plugin_tx['downloadcontrol']['backend_askstyle']="Link style (works only in single file downloads)";
$plugin_tx['downloadcontrol']['backend_base_folder_info']="Downloadcontrol base folder is";
$plugin_tx['downloadcontrol']['backend_color_change']="Color changes done here may be visible on other pages only after page reload.";
$plugin_tx['downloadcontrol']['backend_close']="Close";
$plugin_tx['downloadcontrol']['backend_count_all_versions']="Generate totals for combined versions only";
$plugin_tx['downloadcontrol']['backend_dlcounter_notfound']="Dlcounter_XH log not found";
$plugin_tx['downloadcontrol']['backend_dlcounter_unnecessary']="Dlcounter_XH log already in sync, update not necessary";
$plugin_tx['downloadcontrol']['backend_dlcountersync']="Log sync with Dlcounter_XH";
$plugin_tx['downloadcontrol']['backend_dlcountersync_ok']="Sync with Dlcounter_XH log completed and download file counter updated.";
$plugin_tx['downloadcontrol']['backend_edit_counter_and_analyse_log']="Log analysis + file counter editing";
$plugin_tx['downloadcontrol']['backend_edit_colors']="Colors";
$plugin_tx['downloadcontrol']['backend_enddate']="End date";
$plugin_tx['downloadcontrol']['backend_generate_count']="Delete and generate new count";
$plugin_tx['downloadcontrol']['backend_login']="Login";
$plugin_tx['downloadcontrol']['backend_off']="off";
$plugin_tx['downloadcontrol']['backend_password']="Requires password or";
$plugin_tx['downloadcontrol']['backend_plugincall_info']="<h5>Plugin Calls</h5><p><b>{{{control</b> ['filename(s)/folder' , 'startdate' , 'enddate' , 'password' , 'linktype' , 'askname/location'] <b>}}}</b><br><b>{{{controlcount 'filename' }}}</b> <i>returns the number of  downloads of the file.</i></p><p>Arguments in [] are optional.<br><b>filename:</b> when empty, all files of Downloadcontrol basic folder will be shown.<br><b>startdate, enddate</b> period when the link is shown. (Empty field = always.) Format: <b>day.month.year</b> (e.g. 1.5.2020) or <b>year-month-day</b> (e.g. 2020-05-01)<br><b>password:</b> can be a free combination of chars. If entered, the download of the file requires the password. If you enter <b>'1'</b> a log-in via Memberpages/Register_XH will be requested.<br><b>linktype:</b> overturns the config setting, allowed values: 'button','button2','small','inline','table'.</p>";
$plugin_tx['downloadcontrol']['backend_show_plugincall_info']="Plugin call info";
$plugin_tx['downloadcontrol']['backend_startdate']="Start date";
$plugin_tx['downloadcontrol']['cf_alternative_file_name_encoding']="Encoding which is used when files are not found because their names are not UTF-8 encoded.<br>This happens, when you upload files with accents in the file name directly from Windows via ftp. For western Europe enter here \"Windows-1252\".";
$plugin_tx['downloadcontrol']['cf_backend_headlines']="Depending on your template you can choose a headline style for the Downloadcontrol backend.";
$plugin_tx['downloadcontrol']['cf_backend_number_of_shortcuts']="Without entry the shortcuts will be disabled";
$plugin_tx['downloadcontrol']['cf_sync_dont_change_dlcounter_log']="If you have Dlcounter installed and click \"Log-Sync with Dlcounter_XH\", Downloadcountrol will compare both logs, and add to both logs the new entries of the other log. If however you do not want the Dlcounter log to be messed with, mark the checkbox.";
$plugin_tx['downloadcontrol']['cf_downloadcontrol_base_folder']="Path from the standard downloads folder to the base folder for Downloadcontrol. If empty, the standard downloads will be taken by Downloadcontrol as base folder.";
$plugin_tx['downloadcontrol']['cf_email_from']="The (fake-) sender's email address of the notification email";
$plugin_tx['downloadcontrol']['cf_email_send']="Whether a notification email will be send for every download.";
$plugin_tx['downloadcontrol']['cf_email_subject']="The subject of the notification email.";
$plugin_tx['downloadcontrol']['cf_email_to']="Email address to which a notification email is send when a download is initiated.";
$plugin_tx['downloadcontrol']['cf_multiple-links_show_all_version_downloads']="Requires \"Separato before version endings\" to be set. <br><br>Opens a column in the downloads table, where totals of different versions of a downloads are shown, if they are higher than the simple download counts. <br><br>Otherwise – in case \"Show 1 version downloads\" is checked – a hyphen is shown or – in case \"Show 1 version downloads\" is not checked – the standard download count.";
$plugin_tx['downloadcontrol']['cf_multiple-links_show_date']="Shows the date of the last change to the downloadable file. That last change is normally the time when the file was uploaded on the server. It may not have much meaning.";
$plugin_tx['downloadcontrol']['cf_password']="This password will be asked from downloaders, if 'Single-link Ask password' or 'Folder-links Ask password' are checked and the plugin call doesn't have its own password settings. (Plugin call entries take precedence over config entries.)";
$plugin_tx['downloadcontrol']['cf_separator_before_version_endings']="Character that separates a file name from its version number, usually \"_\".";
$plugin_tx['downloadcontrol']['cf_single-link_ask_for_name']="If checked, the created links are displayed as buttons where  downloaders are asked for their name and location before accessing the download.";
$plugin_tx['downloadcontrol']['cf_single-link_ask_login']="If checked, all downloadcontrol link buttons display a notice that log-in is required. Only after log-in via memberpages_XH or register_XH the links will work.";
$plugin_tx['downloadcontrol']['cf_single-link_ask_password']="If a password has been entered below, single file download links will be styled as big buttons with password restriction (Settings 'Style', 'Only for members' and 'Ask for name' will be ignored). If you want to differentiate your downloads, it may be more practical to set the password directly with the plugin call.";
$plugin_tx['downloadcontrol']['cf_single-link_show_1_version_downloads']="This displays the number of downloads of a file as counted in the counter file";
$plugin_tx['downloadcontrol']['cf_single-link_show_all_version_downloads']="Works only if you have entered a char at \"Separator before version endings\". If you had different versions of downloadable files, the total of all versions is also counted in the counter file. If the present option is checked, the total will be displayed, if found in the counter file and if it is larger than the normal count. Otherwise the standard download number will be displayed.";
$plugin_tx['downloadcontrol']['cf_single-link_style']="Type of link used for single file download links. However, if table is selected, the settings for multiple links apply.";
$plugin_tx['downloadcontrol']['check-htaccess_base_folder_ok']="with protecting .htaccess file, i.e. from this folder only controlled (counted) downloads possible.";
$plugin_tx['downloadcontrol']['check-htaccess_log_failed']="Didn't find .htaccess file in log/counter folder";
$plugin_tx['downloadcontrol']['check-htaccess_log_ok']=".htaccess protects log/counter folder";
$plugin_tx['downloadcontrol']['check-htaccess_no_htaccess']="without .htaccess file, i.e. usual (uncounted) downloads remain possible.";
$plugin_tx['downloadcontrol']['check-htaccess_subfolder_ok']=".htaccess file found in subfolder <b>%s</b>, i.e. only controlled downloads possible from this folder";
$plugin_tx['downloadcontrol']['error_shortcut']="Short cut call {{{control %s}}} without contents";
$plugin_tx['downloadcontrol']['error_filename_not_unique']="Filename <b>%s</b> not unique";
$plugin_tx['downloadcontrol']['file_all_versions']="all versions";
$plugin_tx['downloadcontrol']['file_date']="Last Change";
$plugin_tx['downloadcontrol']['file_downloads']="Down&shy;loads";
$plugin_tx['downloadcontrol']['file_name']="File name";
$plugin_tx['downloadcontrol']['file_size']="Size";
$plugin_tx['downloadcontrol']['file_this_version']="this version";
$plugin_tx['downloadcontrol']['headline_count_per_file']="Downloads per File";
$plugin_tx['downloadcontrol']['headline_downloads_per_month']="Downloads per Month";
$plugin_tx['downloadcontrol']['headline_log_analysis']="Log Analysis";
$plugin_tx['downloadcontrol']['headline_protection']="Folder Protection";
$plugin_tx['downloadcontrol']['headline_recent_log']="Recent Log Entries";
$plugin_tx['downloadcontrol']['headline_shortcuts']="Plugin call shortcuts";
$plugin_tx['downloadcontrol']['headline_this_month']="Downloads of the Last 30 Days";
$plugin_tx['downloadcontrol']['login_first']="Please log in before downloading";
$plugin_tx['downloadcontrol']['menu_main']="Stats";
$plugin_tx['downloadcontrol']['password']="Password";
$plugin_tx['downloadcontrol']['text_email']="Download notice\r\n\r\nDate/Time: %s\r\n\r\nFile: %s\r\n\r\nUser: %s";
$plugin_tx['downloadcontrol']['text_email_subject']="Download notice";
$plugin_tx['downloadcontrol']['update_config']="Downloadcontrol config updated to version %s.";
$plugin_tx['downloadcontrol']['update_log_transfer']="moved to zu userfiles/plugins/downloadcontrol.";
$plugin_tx['downloadcontrol']['weekdays']="Su,Mo,Tu,We,Th,Fr,Sa";

?>

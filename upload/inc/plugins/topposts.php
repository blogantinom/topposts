<?php
/*
 _______________________________________________________
 |                           |
 | Plugin TopPosts 0.1                 |
 | (c) 2013 Eco
 | On top of work of SaeedGh (ProStats)    |
 | Website: http://community.mybb.com/user-14229.html  |
 | Last edit: May 17th, 2013             |
 |_______________________________________________________|

 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program. If not, see <http://www.gnu.org/licenses/>.

 */

if (!defined("IN_MYBB"))
{
  die("Direct initialization of this file is not allowed.");
}


$plugins->add_hook('global_start', 'topposts_run_global');
$plugins->add_hook('pre_output_page', 'topposts_run_pre_output');
$plugins->add_hook('index_start', 'topposts_run_index');
$plugins->add_hook('portal_start', 'topposts_run_portal');

function topposts_info()
{
  global $mybb, $db;

  $settings_link = '';

  $query = $db->simple_select('settinggroups', '*', "name='topposts'");

  if (count($db->fetch_array($query)))
  {
    $settings_link = '(<a href="index.php?module=config&action=change&search=topposts" style="color:#FF1493;">Settings</a>)';
  }

  return array(
    'name'      =>  'TopPosts',
    'title'     =>  'TopPosts',
    'description' =>  'Top Posts for MyBB. ' . $settings_link,
    'website'   =>  'http://community.mybb.com/thread-139455.html',
    'author'    =>  'Blog Anti-NOM',
    'authorsite'  =>  'mailto:blogantinom@gmail.com',
    'version'   =>  '1.0',
    'guid'      =>  '93cbf2d9f08ea161a3697ddb3b2225c2',
    'compatibility' =>  '14*,16*'
    );
}

function topposts_is_installed()
{
  global $db;

  $query = $db->simple_select('settinggroups', '*', "name='topposts'");
  echo ("count: ".$db->num_rows($query)>0);

  if (mysql_fetch_array($query, MYSQL_NUM))
  {
    //echo("está instalado");
    return true;
  } else {
    //echo("não está instalado");
    return false;
  }


}

function topposts_activate()
{
  global $db, $cache;

  require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
  find_replace_templatesets('index', '#{\$header}(\r?)\n#', "{\$header}\n{\$tp_header_index}\n");
  find_replace_templatesets('index', '#{\$forums}(\r?)\n#', "{\$forums}\n{\$tp_footer_index}\n");
  find_replace_templatesets('portal', '#{\$header}(\r?)\n#', "{\$header}\n{\$tp_header_portal}\n");
  find_replace_templatesets('portal', '#{\$footer}(\r?)\n#', "{\$tp_footer_portal}\n{\$footer}\n");
  $db->update_query('tasks', array('enabled' => 1), 'file = \'topposts\'');
  tp_UpdateContent();
}


function topposts_deactivate()
{
  global $db, $cache;
  require_once MYBB_ROOT."/inc/adminfunctions_templates.php";
  find_replace_templatesets("index", '#{\$tp_header_index}(\r?)\n#', "", 0);
  find_replace_templatesets("index", '#{\$tp_footer_index}(\r?)\n#', "", 0);
  find_replace_templatesets("portal", '#{\$tp_header_portal}(\r?)\n#', "", 0);
  find_replace_templatesets("portal", '#{\$tp_footer_portal}(\r?)\n#', "", 0);


  $db->update_query('tasks', array('enabled' => 0), 'file = \'topposts\'');
  $cache->update('topposts_table','');
}

function topposts_install()
{
  global $mybb, $db, $lang, $PL, $plugins, $cache;

  $lang->load("topposts");

  $templatearray = array(
    "title" => "topposts",
    "template" => "
     <script language=JAVASCRIPT>
      function fadein(div) {
        var zero = \"0.\";
        div = document.getElementById(div);
        div.style.opacity = 0;
        div.style.display = \'block\';
        for (counter = 0; counter < 100; counter++) {
          div.style.opacity = zero + counter;
        }
      }
      function fadeout(div) {
        var zero = \"0.\";
        div = document.getElementById(div);
        for (counter = 99; counter > -1; counter--) {
          div.style.opacity = zero + counter;
        }
      }
  </script>
    <div id=\"topposts_table\">
    <table width=\"100%\" border=\"0\" cellspacing=\"{\$theme[borderwidth]}\" cellpadding=\"0\" class=\"tborder\">
    <thead>
    <tr><td colspan=\"{\$num_columns}\">
      <table border=\"0\" cellspacing=\"0\" cellpadding=\"{\$theme[tablespace]}\" width=\"100%\">
      <tr class=\"thead\">
      <td><strong>{\$lang->topposts_topposts}</strong></td>
      <td style=\"text-align:{\$tp_ralign};\"></td>
      </tr>
      </table>
    </td>
    </tr>
    </thead>
    <tbody>
    {\$trow_message_top}
    <tr valign=\"top\">
    {\$topposts_content}
    </tr>
    {\$trow_message_down}
    </tbody>
    </table>
    <br />
    </div>",
    "sid" => "-1",
  );
  $db->insert_query("templates", $templatearray);

  $templatearray = array(
    "title" => "topposts_readstate_icon",
    "template" => "<img src=\"{\$mybb->settings[\'bburl\']}/images/tp_mini{\$lightbulb[\'folder\']}.gif\" style=\"vertical-align:middle;\" alt=\"\" />&nbsp;",
    "sid" => "-1",
  );
  $db->insert_query("templates", $templatearray);

  $templatearray = array(
    "title" => "topposts_newestposts",
    "template" => "<td class=\"{\$trow}\"><table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"{\$theme[tablespace]}\">
    <tr class=\"tcat smalltext\">
    <td colspan=\"{\$colspan}\"><b>{\$title_template}</b></td>
    </tr>
    <tr>
    <td colspan=\"{\$colspan}\">
<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">
    {\$newestposts_row}
</table></td>
    </tr>
    </table></td>",
    "sid" => "-1",
  );
  $db->insert_query("templates", $templatearray);

  $templatearray = array(
    "title" => "topposts_newestposts_row",
    "template" => "<tr class=\"{\$trow} smalltext\">
    {\$newestposts_cols}
    </tr>",
    "sid" => "-1",
  );
  $db->insert_query("templates", $templatearray);

  $templatearray = array(
    "title" => "topposts_newestposts_specialchar",
    "template" => "<a href=\"{\$threadlink}\" style=\"text-decoration: none;\"><font face=\"arial\" style=\"line-height:10px;\">â–¼</font></a>",
    "sid" => "-1",
  );
  $db->insert_query("templates", $templatearray);


  $tp_group = array(
    "name"      => "topposts",
    "title"     => "TopPosts Plugin Configuration",
    "description" => "Top Posts for MyBB.",
    "disporder"   => "1",
    "isdefault"   => "1",
  );

  $db->insert_query("settinggroups", $tp_group);
  $gid = $db->insert_id();

  $ps[]= array(
    "name"      => "tp_active",
    "title"     => "Activate",
    "description" => "Do you want to activate the plugin?",
    "optionscode" => "yesno",
    "value"     => '1',
    "disporder"   => '1',
    "gid"     => intval($gid),
  );

  $ps[]= array(
    "name"      => "tp_ignoreforums",
    "title"     => "Ignore list",
    "description" => "Forums not to be shown on topposts. Seperate with comma. (e.g. 1,3,12)",
    "optionscode" => "text",
    "value"     => '',
    "disporder"   => '3',
    "gid"     => intval($gid),
  );

  $ps[]= array(
    "name"      => "tp_index",
    "title"     => "Show in index",
    "description" => "Show the topposts table in the index page.",
    "optionscode" => "yesno",
    "value"     => '1',
    "disporder"   => '4',
    "gid"     => intval($gid),
  );

  $ps[]= array(
    "name"      => "tp_portal",
    "title"     => "Show in portal",
    "description" => "Show the topposts table in the portal page.",
    "optionscode" => "yesno",
    "value"     => '0',
    "disporder"   => '5',
    "gid"     => intval($gid),
  );

  $ps[]= array(
    "name"      => "tp_position",
    "title"     => "Table position",
    "description" => "Position of stats in your board.",
    "optionscode" => "select\n0=Top (Header)\n1=Bottom (Footer)",
    "value"     => '0',
    "disporder"   => '10',
    "gid"     => intval($gid),
  );

  $ps[]= array(
    "name"      => "tp_format_name",
    "title"     => "Style usernames",
    "description" => "Style the username in true color, font, etc.",
    "optionscode" => "yesno",
    "value"     => '1',
    "disporder"   => '20',
    "gid"     => intval($gid),
  );

  $ps[]= array(
    "name"      => "tp_subject_length",
    "title"     => "Subject length",
    "description" => "Maximum length of topic/post subjects. (Input 0 to remove the limitation)",
    "optionscode" => "text",
    "value"     => '0',
    "disporder"   => '30',
    "gid"     => intval($gid),
  );

  $ps[]= array(
    "name"      => "tp_num_rows",
    "title"     => "Number of rows",
    "description" => "How much items must be shown? (Input an odd number greater than or equal to 3)",
    "optionscode" => "text",
    "value"     => '4',
    "disporder"   => '41',
    "gid"     => intval($gid),
  );
  
    $ps[]= array(
    "name"      => "tp_thumbnail_height",
    "title"     => "Height of thumbnail",
    "description" => "The height of the thumbnail generated)",
    "optionscode" => "text",
    "value"     => '111',
    "disporder"   => '42',
    "gid"     => intval($gid),
  );
    
    $ps[]= array(
    "name"      => "tp_thumbnail_width",
    "title"     => "Width of thumbnail",
    "description" => "The width of the thumbnail generated)",
    "optionscode" => "text",
    "value"     => '160',
    "disporder"   => '42',
    "gid"     => intval($gid),
  );  
  
  $ps[]= array(
    "name"      => "tp_date_format",
    "title"     => "Date and Time format",
    "description" => "The format of Date and Time which would be used in stats. [<a href=\"http://php.net/manual/en/function.date.php\" target=\"_blank\">More Information</a>]",
    "optionscode" => "text",
    "value"     => 'm-d, H:i',
    "disporder"   => '42',
    "gid"     => intval($gid),
  );

  $ps[]= array(
    "name"      => "tp_date_format_ty",
    "title"     => "Replace format",
    "description" => "A part of Date and Time format that must be replaced with \"Yesterday\" or \"Today\".",
    "optionscode" => "text",
    "value"     => 'm-d',
    "disporder"   => '43',
    "gid"     => intval($gid),
  );

  $ps[]= array(
    "name"      => "tp_newest_posts_cells",
    "title"     => "Stats of newest posts",
    "description" => "What type of stats you want to be shown for newest posts?<br />Your choices are: <strong>Newest_posts, Date, Starter, Last_sender, Forum</strong><br />Separate them by comma (\",\").",
    "optionscode" => "text",
    "value"     => 'Newest_posts',
    "disporder"   => '55',
    "gid"     => intval($gid),
  );

  $ps[]= array(
    "name"      => "tp_hidefrombots",
    "title"     => "Hide from search bots",
    "description" => "Using this option you can hide stats from all search bots you\'ve defined them in <strong><a href=\"index.php?module=config-spiders\" target=\"_blank\">Spiders/Bots</a></strong> page. This will save bandwidth and decrease server load.",
    "optionscode" => "yesno",
    "value"     => '1',
    "disporder"   => '74',
    "gid"     => intval($gid),
  );

  $ps[]= array(
    "name"      => "tp_global_tag",
    "title"     => "Active global tag",
    "description" => "So you can edit themes and insert &lt;topposts&gt; tag wherever you want to show the stats",
    "optionscode" => "yesno",
    "value"     => '0',
    "disporder"   => '76',
    "gid"     => intval($gid),
  );


  foreach ($ps as $p)
  {
    $db->insert_query("settings", $p);
  }

  rebuild_settings();

  //Create task that updates the topposts html table creating the thumbnail images
  $taskExists = $db->simple_select('tasks', 'tid', 'file = \'topposts\'', array('limit' => '1'));
  if ($db->num_rows($taskExists) == 0) {
    require_once MYBB_ROOT.'/inc/functions_task.php';

    $myTask = array(
            'title'       => $lang->topposts_task_title,
            'file'        => 'topposts',
            'description' => $lang->topposts_task_description,
            'minute'      => 0,
            'hour'        => '*',
            'day'         => '*',
            'weekday'     => '*',
            'month'       => '*',
            'nextrun'     => TIME_NOW + 3600,
            'lastrun'     => 0,
            'enabled'     => 1,
            'logging'     => 1,
            'locked'      => 0,
    );

    $task_id = $db->insert_query('tasks', $myTask);
    $theTask = $db->fetch_array($db->simple_select('tasks', '*', 'tid = '.(int) $task_id, 1));
    $nextrun = fetch_next_run($theTask);
    $db->update_query('tasks', 'nextrun = '.$nextrun, 'tid = '.(int) $task_id);
    $plugins->run_hooks('admin_tools_tasks_add_commit');
    $cache->update_tasks();
  } else {
    require_once MYBB_ROOT.'/inc/functions_task.php';
    $theTask = $db->fetch_array($db->simple_select('tasks', '*', 'file = \'topposts\'', 1));
    $db->update_query('tasks', array('enabled' => 1, 'nextrun' => fetch_next_run($theTask)), 'file = \'topposts\'');
    $cache->update_tasks();
  }

}

function topposts_uninstall()
{
  global $db;
  topposts_deactivate();
  $db->delete_query('tasks', 'file = \'topposts\'');
  $db->query("DELETE FROM ".TABLE_PREFIX."templates WHERE title='topposts'");
  $db->query("DELETE FROM ".TABLE_PREFIX."templates WHERE title LIKE 'topposts_%'");

  $db->delete_query("settings","name IN ('tp_active','tp_ignoreforums','tp_index','tp_portal','tp_position','tp_format_name','tp_subject_length','tp_num_rows','tp_date_format','tp_date_format_ty','tp_newest_posts_cells','tp_hidefrombots','tp_global_tag','tp_thumbnail_height','tp_thumbnail_width')");
  $db->delete_query("settinggroups","name='topposts'");

  
    
  rebuild_settings();
  echo('uninstall finish');
}


function topposts_run_global()
{
  global $mybb, $session;

  if (isset($GLOBALS['templatelist']))
  {
    if ($mybb->settings['tp_active'] && defined('THIS_SCRIPT'))
    {
      if (!$mybb->settings['tp_hidefrombots'] || empty($session->is_spider))
      {
        if (($mybb->settings['tp_index'] && THIS_SCRIPT == 'index.php')
        || ($mybb->settings['tp_portal'] && THIS_SCRIPT == 'portal.php')
        || $mybb->settings['tp_global_tag'])
        {
          $GLOBALS['templatelist'] .= ",topposts,topposts_readstate_icon,topposts_newestposts,topposts_newestposts_row,topposts_newestposts_specialchar";
        }
      }
    }
  }
}


function topposts_run_index($force = false)
{
  global $mybb, $parser, $session, $topposts_tbl, $tp_header_index, $tp_footer_index, $tp_header_portal, $tp_footer_portal;


  if (!$mybb->settings['tp_active']) {return false;}

  if ($mybb->settings['tp_hidefrombots'] && !empty($session->is_spider)) {return false;}

  if (!is_object($parser))
  {
    require_once MYBB_ROOT.'inc/class_parser.php';
    $parser = new postParser;
  }

  if (ceil($mybb->settings['tp_num_rows']) != $mybb->settings['tp_num_rows'] || ceil($mybb->settings['tp_subject_length']) != $mybb->settings['tp_subject_length']){return false;}
  //if (intval($mybb->settings['tp_num_rows']) < 3) {return false;}


  if (!$mybb->settings['tp_index'] && !$force) {return false;}

  $numofrows = $mybb->settings['tp_num_rows'];
  $topposts_tbl = "";

  $topposts_tbl = tp_MakeTable();


  if ($mybb->settings['tp_position'] == 0)
  {
    $tp_header_index = $topposts_tbl;
  }
  else if ($mybb->settings['tp_position'] == 1)
  {
    $tp_footer_index = $topposts_tbl;
  }

}


function topposts_run_portal()
{
  global $mybb, $parser, $session, $tp_header_index, $tp_footer_index, $tp_header_portal, $tp_footer_portal;

  if (!$mybb->settings['tp_active']) {return false;}

  if ($mybb->settings['tp_hidefrombots'] && !empty($session->is_spider)) {return false;}

  if (!is_object($parser))
  {
    require_once MYBB_ROOT.'inc/class_parser.php';
    $parser = new postParser;
  }

  if (ceil($mybb->settings['tp_num_rows']) != $mybb->settings['tp_num_rows'] || ceil($mybb->settings['tp_subject_length']) != $mybb->settings['tp_subject_length']){return false;}

  if (!$mybb->settings['tp_portal']) {return false;}
  //if (intval($mybb->settings['tp_num_rows']) < 3) {return false;}

  $numofrows = $mybb->settings['tp_num_rows'];
  $topposts_tbl = "";

  $topposts_tbl = tp_MakeTable();

  if ($mybb->settings['tp_position'] == 0)
  {
    $tp_header_portal = $topposts_tbl;
  }
  else if ($mybb->settings['tp_position'] == 1)
  {
    $tp_footer_portal = $topposts_tbl;
  }
}


function topposts_run_pre_output($contents)
{
  global $mybb, $parser, $session, $topposts_tbl, $tp_header_index, $tp_footer_index, $tp_header_portal, $tp_footer_portal;

  if (!$mybb->settings['tp_active']) {return false;}

  if ($mybb->settings['tp_hidefrombots'] && !empty($session->is_spider)) {return false;}

  if (!is_object($parser))
  {
    require_once MYBB_ROOT.'inc/class_parser.php';
    $parser = new postParser;
  }

  if (ceil($mybb->settings['tp_num_rows']) != $mybb->settings['tp_num_rows'] || ceil($mybb->settings['tp_subject_length']) != $mybb->settings['tp_subject_length']){return false;}
  if (intval($mybb->settings['tp_num_rows']) < 3) {return false;}

  if (!$mybb->settings['tp_global_tag']){
    $contents = str_replace('<topposts>', '', $contents);
    return false;
  }

  $numofrows = $mybb->settings['tp_num_rows'];
  $topposts_tbl = "";
  echo("tp_MakeTable");
  $topposts_tbl = tp_MakeTable();


  $contents = str_replace('<topposts>', $topposts_tbl, $contents);
}


function tp_GetNewestPosts($NumOfRows, $days, $title)
{
  global $mybb, $db, $templates, $theme, $lang, $unviewwhere, $parser, $lightbulb, $trow, $newestposts_cols_name, $newestposts_cols, $colspan, $title_template, $image_url;

  $title_template=$title;
  if (!is_object($parser))
  {
    require_once MYBB_ROOT.'inc/class_parser.php';
    $parser = new postParser;
  }
  $g33kthanks=thankyouplugin_is_installed();
  $sql="
    SELECT t.subject,t.username,t.uid,t.tid,t.fid,t.lastpost,t.lastposter,t.lastposteruid,t.replies, t.totalratings, ".($g33kthanks ? "tyl_tnumtyls" : "").", t.views,tr.uid AS truid,tr.dateline,f.name ,
    TRUNCATE(t.totalratings/t.numratings*2+t.numratings/8".($g33kthanks ? "+tyl_tnumtyls" : "")."+t.replies+t.views/".$days.",2) ranking
    FROM ".TABLE_PREFIX."threads t 
    LEFT JOIN ".TABLE_PREFIX."forums f ON (f.fid = t.fid) 
    WHERE t.visible='1' 
    ".tp_GetUnviewable("t")."
    AND t.closed NOT LIKE 'moved|%'         
    AND t.dateline >=UNIX_TIMESTAMP()-(86400*".$days.") ORDER BY ranking DESC
    LIMIT 0,".$NumOfRows;

  echo($sql);

  $query = $db->query ($sql);

  $newestposts_cols_name = "";
  $newestposts_cols = "";
  $colspan = 0;
  $active_cells = "";

  $newest_posts_cells_arr = escaped_explode(",", htmlspecialchars_uni($mybb->settings['tp_newest_posts_cells']),20);

  // Cabeçalho tabela
  foreach($newest_posts_cells_arr as $newest_posts_cell)
  {
    ++$colspan;

    switch($newest_posts_cell)
    {
      case "Newest_posts" :
        $active_cells['Newest_posts']=1;
        $newestposts_cols_name .= "<td >".$lang->topposts_topic."</td>";
        $cell_order[$colspan]='Newest_posts';
        break;
      case "Date" :
        $active_cells['Date']=1;
        $newestposts_cols_name .= "<td>".$lang->topposts_datetime."&nbsp;</td>";
        $cell_order[$colspan]='Date';
        break;
      case "Starter" :
        $active_cells['Starter']=1;
        $newestposts_cols_name .= "<td>".$lang->topposts_author."</td>";
        $cell_order[$colspan]='Starter';
        break;
      case "Last_sender" :
        $active_cells['Last_sender']=1;
        $newestposts_cols_name .= "<td>".$lang->topposts_last_sender."</td>";
        $cell_order[$colspan]='Last_sender';
        break;
      case "Forum" :
        $active_cells['Forum']=1;
        $newestposts_cols_name .= "<td>".$lang->topposts_forum."</td>";
        $cell_order[$colspan]='Forum';
        break;
      default: --$colspan;
    }
  }

  $trow = "trow1";

  $loop_counter = 0;

  //caches the DB results in an array
  $results = array();
  while($newest_threads = $db->fetch_array($query))
  {
    $results[] = $newest_threads;
  }

  //$results has all that you need
  echo($results);

  foreach ($results as $newest_threads)
  {
    $tid = $newest_threads['tid'];

    $image_url= $mybb->settings['bburl'].'/images/topposts/thumbnails/'.tp_GetThreadImage($tid);

    $fuid = $newest_threads['uid'];
    $fid = $newest_threads['fid'];
    
    $replies=$newest_threads['replies'];
    $views=$newest_threads['views'];
    $ratings=$newest_threads['totalratings'];
    if ($g33kthanks) {
      $tyl_tnumtyls=$newest_threads['tyl_tnumtyls'];
    }
    $newestposts_cols = "";

    $dateformat = $mybb->settings['tp_date_format'];

    if ($active_cells['Date'])
    {

      $datetime = $newest_threads['ranking'];

    }

    if ($active_cells['Newest_posts'])
    {
      $parsed_subject = $parser->parse_badwords($newest_threads['subject']);
      $subject = htmlspecialchars_uni(tp_SubjectLength($parsed_subject));
      $subject_long = htmlspecialchars_uni($parsed_subject);
      $threadlink = get_thread_link($tid,NULL,"lastpost");
      eval("\$newestposts_specialchar = \"".$templates->get("topposts_newestposts_specialchar")."\";");
    }

    if ($active_cells['Starter'])
    {
      $username = tp_FormatNameDb($fuid, htmlspecialchars_uni($newest_threads['username']));
      $profilelink = get_profile_link($fuid);
    }

    if ($active_cells['Last_sender'])
    {
      $lastposter_uname = tp_FormatNameDb($newest_threads['lastposteruid'], htmlspecialchars_uni($newest_threads['lastposter']));
      $lastposter_profile = get_profile_link($newest_threads['lastposteruid']);
    }

    if ($active_cells['Forum'])
    {
      $forumlink = get_forum_link($fid);
      $forumname_long = $parser->parse_badwords(strip_tags($newest_threads['name']));
      $forumname = htmlspecialchars_uni(tp_SubjectLength($forumname_long, NULL, true));
    }

    for($i=1;$i<=$colspan;++$i)
    {
      switch($cell_order[$i])
      {
        //most of the presentation html is concentrated on this first case below.
        //The other cases are not used. Planning in doing a refactoring here.
  
        case "Newest_posts" :
          $newestposts_cols .= "<td width='".$mybb->settings['tp_thumbnail_width']."' height='".$mybb->settings['tp_thumbnail_height']."' border='1' valign='bottom'  style=\"background: url(".$image_url."); background-repeat: no-repeat\" onmouseout=\"fadeout('thread".$tid.$days."');\" onmouseover=\"fadein('thread".$tid.$days."');\">  <span  >".
          "<span id='thread".$tid.$days."' style='display:none;width: 100%;vertical-align:top;max-height: 30px;background:rgba(255,255,255,0.60); font-color: red;font-weight:bold;font-size: 0.8em;float: right;bottom:0;left:0; a, a:hover, a:active, a:visited { color: white; }'>"
          ."<img src=\"".$mybb->settings['bburl']."/images/topposts/Chat-icon.png\">".$replies." <img src=\"".$mybb->settings['bburl']."/images/topposts/eye.png\"/>".$views.($g33kthanks ? " "." <img src=\"".$mybb->settings['bburl']."/images/topposts/Heart-icon.png\"/>".$tyl_tnumtyls : "")." <img src=\"".$mybb->settings['bburl']."/images/topposts/star.gif\"/>".$ratings."</span>".
             "<span onmouseover=\"document.getElementById('thread".$tid."').style.display='block'\" style='display:block;width: 100%;vertical-align:middle;min-height: 30px;background:rgba(0,0,0,0.60); font-color: red;font-weight:bold;font-size: 0.8em;float: right;bottom:0;left:0; a, a:hover, a:active, a:visited { color: white; }'>".
          "<span style='margin-left:3px;margin-right:3px;margin-botton:4px;margin-top:4px;display:inline-block; vertical-align:middle'>"
          ."<a href=\"".$threadlink."\" style=\"float: left;color:white;\" title=\"".$subject_long."\">".$subject."</a></span></span></span></td>";
          break;
        case "Date" :
          $newestposts_cols .= "<td>".$newestposts_specialchar.$datetime."</td>";
          break;
        case "Starter" :
          $newestposts_cols .= "<td><a href=\"".$profilelink."\">".$username."</a></td>";
          break;
        case "Last_sender" :
          $newestposts_cols .= "<td><a href=\"".$lastposter_profile."\">".$lastposter_uname."</a></td>";
          break;
        case "Forum" :
          $newestposts_cols.= "<td><a href=\"".$forumlink."\" title=\"".$forumname_long."\">".$forumname."</a></td>";
          break;
        default: NULL;
      }
    }

    eval("\$newestposts_row .= \"".$templates->get("topposts_newestposts_row")."\";");


    ++$loop_counter;
  }

  eval("\$newestposts = \"".$templates->get("topposts_newestposts")."\";");

  //TODO: if no updates for the period to show an empty cell (the commented code below doesn't work)
  //   if ($loop_counter==0) {
  //   	$newestposts="<tr><td width='".$mybb->settings['tp_thumbnail_width']."' height='".$mybb->settings['tp_thumbnail_height']."</td></tr>";
  //   }
  return $newestposts;
}

/*
 * returns the cached table
 */
function tp_MakeTable()
{
  global $cache, $mybb, $theme, $lang, $templates, $parser, $lightbulb, $unread_forums, $tp_align;
  $topposts_cached = $cache->read('topposts_table');
  return $topposts_cached;
}

/*
 * Updates the topposts table
 */
function tp_UpdateContent()
{
  global $cache, $mybb, $theme, $lang, $templates, $parser, $lightbulb, $unread_forums, $tp_align;

  $numofrows = $mybb->settings['tp_num_rows'];

  $lang->load("topposts");

  $col1 = $col2 = $col3 = $col4 = $col5 = "";
  $num_columns = 0;

  $tp_align = $lang->settings['rtl'] ? "right" : "left";
  $tp_ralign = $lang->settings['rtl'] ? "left" : "right";


  $num_columns = 5; //used to make the header column occupy the whole line

  //top topics of the day
  $col1 = tp_GetNewestPosts($mybb->settings['tp_num_rows'],1,$lang->topposts_day);
  //top topics of the week
  $col2= tp_GetNewestPosts($mybb->settings['tp_num_rows'],7,$lang->topposts_week);
  //top topics of the month
  $col3= tp_GetNewestPosts($mybb->settings['tp_num_rows'],30,$lang->topposts_month);
  //top topics of the year
  $col4= tp_GetNewestPosts($mybb->settings['tp_num_rows'],360,$lang->topposts_year);
  //top topics ever
  $col5= tp_GetNewestPosts($mybb->settings['tp_num_rows'],3600,$lang->topposts_ever);

  $topposts_content = $col1.$col2.$col3.$col4.$col5;

  eval("\$topposts = \"".$templates->get("topposts")."\";");

  $cache->update('topposts_table', $topposts);
  return true;
}

/*
 * Checks if the forum of the thread is set to not appear in the topposts
 */
function tp_GetUnviewable($name="")
{
  global $mybb;
  $unviewwhere = $comma = '';
  $name ? $name .= '.' : NULL;
  $unviewable = get_unviewable_forums();

  if ($mybb->settings['tp_ignoreforums'])
  {
    $ignoreforums = explode(',', $mybb->settings['tp_ignoreforums']);

    if (count($ignoreforums))
    {
      $unviewable ? $unviewable .= ',' : NULL;

      foreach($ignoreforums as $fid)
      {
        $unviewable .= $comma."'".intval($fid)."'";
        $comma = ',';
      }
    }
  }

  if ($unviewable)
  {
    $unviewwhere = "AND ".$name."fid NOT IN (".$unviewable.")";
  }

  return $unviewwhere;
}

/*
 * Format name (not used)
 */
function tp_FormatName($username, $usergroup, $displaygroup)
{
  global $mybb;

  if ($mybb->settings['tp_format_name'] == '1')
  {
    $username = format_name($username, $usergroup, $displaygroup);
  }
  return $username;
}

/*
 * Format name (not used)
 */
function tp_FormatNameDb($uid, $username="")
{
  global $mybb, $db;

  if ($mybb->settings['tp_format_name'] == "1")
  {
    $query = $db->query("SELECT username,usergroup,displaygroup FROM ".TABLE_PREFIX."users WHERE uid = '".$uid."'");
    $query_array = $db->fetch_array($query);
    $username = format_name($query_array['username'], $query_array['usergroup'], $query_array['displaygroup']);
  }
  else if ($username=="")
  {
    $query = $db->query("SELECT username FROM ".TABLE_PREFIX."users WHERE uid = '".$uid."'");
    $query_array = $db->fetch_array($query);
    $username = $query_array['username'];
  }

  return $username;
}

/*
 * Returns the subject of the thread truncated (if not 0)
 */
function tp_SubjectLength($subject, $length="", $half=false)
{
  global $mybb;
  $length = $length ? intval($length) : intval($mybb->settings['tp_subject_length']);
  $half ? $length = ceil($length/2) : NULL;
  if ($length != 0)
  {
    if (my_strlen($subject) > $length)
    {
      $subject = my_substr($subject,0,$length) . '...';
    }
  }
  return $subject;
}

/*
 * Returns the thread URL image based on the thread ID
 */
function tp_GetThreadImage($tid)
{

  $thumb_file_name="thumb_".$tid.'.jpg';
  $thumb_folder=MYBB_ROOT.'images/topposts/thumbnails/';
  if (!file_exists($thumb_folder.$thumb_file_name)) {
    $thread_image_url=tp_GetThreadImageUrl($tid);
    tp_createThumb($thread_image_url, $thumb_folder.$thumb_file_name, $thumb_file_name);
  }
  return $thumb_file_name;
}

function tp_GetThreadImageUrl($tid){
  global $mybb, $db;

  $query = $db->query ("
  SELECT t.tid, p.subject, p.message  FROM mybb_threads t, mybb_posts p 
  where t.tid=".$tid." and t.firstpost=p.pid");
  $post = $db->fetch_array($query);
  $message=$post["message"];

  //try to locate the first image on the post text
  preg_match_all("#(?P<wholestring>\[img\](\r\n?|\n?)(?P<url>https?://([^<>\"']+?))\[/img\])#ise", $message, $matches);

  // No match? Let's skip this loop around
  if (empty($matches) OR !is_array($matches) OR !isset($matches['wholestring']) OR !isset($matches['url'])) {
    //if it doesn't find it returns default image
   return;
  }

  foreach ($matches['url'] as $match) {
    return $match;
  }
}

/*
 * This function uses PhpThumb ( http://phpthumb.sourceforge.net/ ) library to resize and crop the images
 */
function tp_createThumb($source_url,$target_file, $thumb_file_name){
	
  global $mybb;
	
  require_once MYBB_ROOT.'inc/plugins/topposts/phpthumb.class.php';
  require_once MYBB_ROOT.'inc/plugins/topposts/phpThumb.config.php';

  // create phpThumb object
  $phpThumb = new phpthumb();

  $temp_file_full_path=str_replace('\\', '/', MYBB_ROOT.'inc\\plugins\\topposts\temp\\'.$thumb_file_name);

  //returns using the default image if url is empty or it doesn't manage to download the file or if the file is invalid image
  $downloadSucessful=downloadFile($source_url, $temp_file_full_path);
  if (empty($source_url) or !$downloadSucessful ) {
    echo('Using default image: $source_url:'.$source_url.' - $temp_file_full_path:'.$temp_file_full_path.'  - $downloadSucessful:'.$downloadSucessful.'   ---- ');
    copy(MYBB_ROOT.'inc/plugins/topposts/defaultImage.png', $target_file);
   return;
  }

  $phpThumb->setSourceData(file_get_contents(MYBB_ROOT.'inc/plugins/topposts/temp/'.$thumb_file_name));

  // PLEASE NOTE:
  // You must set any relevant config settings here. The phpThumb
  // object mode does NOT pull any settings from phpThumb.config.php

  // set parameters (see URL Parameters in phpthumb.readme.txt)
  $phpThumb->setParameter('nohotlink_enabled', 'false');
  $phpThumb->setParameter('config_output_format', 'jpeg');
  $phpThumb->setParameter('zc', "C");
  $phpThumb->setParameter('config_allow_src_above_docroot', true);
  $phpThumb->setParameter('w', $mybb->settings['tp_thumbnail_width']);
  $phpThumb->setParameter('h', $mybb->settings['tp_thumbnail_height']);
  $phpThumb->setParameter('config_cache_directory','./topposts/temp/');
  $phpThumb->setParameter('config_temp_directory', './topposts/temp/');
  $phpThumb->setParameter('config_cache_disable_warning', true);
  $phpThumb->setParameter('config_cache_disable_warning', true);
  $phpThumb->setParameter('config_imagemagick_path', null);
  $phpThumb->setParameter('config_prefer_imagemagick', false);



  // generate & output thumbnail
  if ($phpThumb->GenerateThumbnail()) { // this line is VERY important, do not remove it!
    if ($phpThumb->RenderToFile($target_file)) {
      // do something on success
      echo 'Successfully rendered to "'.$target_file.'"';
      unlink($temp_file_full_path);
    } else {
      // do something with debug/error messages
      echo 'Failed <pre>'.implode("\n\n", $phpThumb->debugmessages).'</pre>';
    }
    $phpThumb->purgeTempFiles();
  } else {
    // do something with debug/error messages
    echo '<form><textarea rows="55" cols="120" wrap="off">'.htmlentities(implode("\n* ", $phpThumb->debugmessages)).'</textarea></form><hr>';
  }
  $phpThumb->purgeTempFiles();
}

/*
 * This funtion downloads the image to the plugin/topposts/temp folder
 */
function downloadFile ($url, $path) {
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $con = curl_exec($ch);
  curl_close($ch);
  file_put_contents($path, $con);
  return true;
}

/*
 * This function is called upon activation of the plugin and also by the hourly task that updates the thumbnails
 */
function updateTopposts(){
  global $cache;

  tp_UpdateContent();
  return true;
}

/*
 * Check if thankyouplugin is installed
 */
function thankyouplugin_is_installed()
{
  global $mybb;

  // MyAlerts Extension obviously adds some settings. Just check a random one, if not present then the plugin isn't installed
  if($mybb->settings['g33k_thankyoulike_enabled'])
  {
    return true;
  }
}
?>
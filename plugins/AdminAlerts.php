<?php
/**!info**
{
  "Plugin Name"  : "Administrators' Alerts",
  "Plugin URI"   : "http://enanocms.org/plugin/admin-alerts",
  "Description"  : "Provides a sidebar block with information on unapproved comments, inactive users, and pages with deletion votes",
  "Author"       : "Dan Fuhry",
  "Version"      : "0.1",
  "Author URI"   : "http://enanocms.org/"
}
**!*/

/*
 * Administrators' Alerts plugin for Enano CMS
 * Version 0.1
 * Copyright (C) 2006-2008 Dan Fuhry
 *
 * This program is Free Software; you can redistribute and/or modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for details.
 */
 
global $db, $session, $paths, $template, $plugins; // Common objects

$plugins->attachHook('common_post', 'adminalerts_setup();');

function adminalerts_setup()
{
  global $db, $session, $paths, $template, $plugins; // Common objects
  
  // restrict this block to administrators
  $content = '{restrict auth_admin}';
  
  if ( $session->user_level >= USER_LEVEL_ADMIN )
  {
    
    $content .= '<p style="margin: 0; padding: 0;"><b>Unapproved comments:</b><br />';
    
    // unapproved comments
    $q = $db->sql_query('SELECT comment_id, page_id, namespace, user_id, name, comment_data, subject, time FROM '.table_prefix.'comments WHERE approved=0 ORDER BY time ASC;');
    if ( !$q )
      $db->_die();
    
    if ( $db->numrows() < 1 )
    {
      $content .= 'No unapproved comments.';
    }
    else
    {
      $content .= '<div class="tblholder" style="max-height: 100px; clip: rect(0px,auto,auto,0px); overflow: auto;">
                   <table border="0" cellspacing="1" cellpadding="2">';
      $class = 'row3';
      while ( $row = $db->fetchrow() )
      {
        $class = ( $class == 'row1' ) ? 'row3' : 'row1';
        $preview = substr($row['comment_data'], 0, 100);
        $preview = htmlspecialchars($preview);
        $subj = substr($row['subject'], 0, 20);
        if ( $subj != $row['subject'] )
          $subj .= '...';
        $subj = htmlspecialchars($subj);
        if ( $row['user_id'] == 1 )
        {
          $name_link = htmlspecialchars($row['name']) . ' [G]';
        }
        else
        {
          $memberlist_link = makeUrlNS('Special', 'Memberlist', 'finduser=' . urlencode($row['name']), true);
          $name = urlencode($row['name']);
          $name_link = "<a href=\"$memberlist_link\">$name</a>";
        }
        
        $page_url = makeUrlNS($row['namespace'], sanitize_page_id($row['page_id']));
        $title    = get_page_title_ns($row['page_id'], $row['namespace']);
        $page_link = "<a href=\"$page_url#do:comments\">$title</a>";
        $timestamp = date('n/j H:i', intval($row['time']));
        
        $content .= '<tr><td title="' . $preview . '" class="' . $class . '">';
        
        $content .= '<b>' . $subj . '</b> by ' . $name_link . '<br />';
        $content .= "$page_link, $timestamp";
        
        $content .= '</td></tr>';
      }
      $content .= '</table></div>';
    }
    $db->free_result();
    
    $content .= '</p>';
    
    // Inactive users
    
    $content .= '<p style="margin: 3px 0 0 0; padding: 0;"><b>Inactive user accounts:</b><br />';
    
    $q = $db->sql_query('SELECT username,reg_time FROM '.table_prefix.'users WHERE account_active=0 AND user_id > 1;');
    if ( !$q )
      $db->_die();
    
    if ( $db->numrows() < 1 )
    {
      $content .= 'No inactive users.';
    }
    else
    {
      $users = array();
      while ( $row = $db->fetchrow() )
      {
        $url  = makeUrlNS('Special', 'Administration', 'module=' . $paths->nslist['Admin'] . 'UserManager&src=get&username=' . urlencode($row['username']), true);
        $uname= htmlspecialchars($row['username']);
        $uname_js = addslashes($row['username']);
        $link = "<a href=\"$url\" onclick=\"ajaxAdminUser('$uname_js'); return false;\">$uname</a>";
        $users[] = $link;
      }
      $content .= implode(', ', $users);
    }
    $db->free_result();
    
    $content .= '</p>';
    
    // Pages with deletion requests
    
    $content .= '<p style="margin: 3px 0 0 0; padding: 0;"><b>Pages voted for deletion:</b><br />';
    
    $q = $db->sql_query('SELECT name, urlname, namespace, delvotes FROM '.table_prefix.'pages WHERE delvotes > 0 ORDER BY delvotes DESC;');
    if ( !$q )
      $db->_die();
    
    if ( $db->numrows() < 1 )
    {
      $content .= 'No pages nominated for deletion.';
    }
    else
    {
      $pages = array();
      while ( $row = $db->fetchrow() )
      {
        $url = makeUrlNS($row['namespace'], sanitize_page_id($row['urlname']), false, true);
        $name = htmlspecialchars($row['name']);
        $link = "<a href=\"$url\">$name</a> ({$row['delvotes']})";
        $pages[] = $link;
      }
      $content .= implode("<br />\n      ", $pages);
    }
    $db->free_result();
    
    $content .= '</p>';
  }
  
  $template->sidebar_widget('Administrator alerts', '<div style="padding: 5px; font-size: smaller;">' . $content . '</div>');
}

?>

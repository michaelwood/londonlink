<?php

$m = new Mustache_Engine(array(
  'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__) . '/views'),
));

function update_event () {

  llg_db_connection ();

  /* Check if the event already exists and update it
   * otherwise insert a new one.
   * Generates a query like:
   * INSERT INTO event (...) VALUES(...) ON DUPLICATE KEY UPDATE key=value
   */

  $sql  = "INSERT INTO event ";
  foreach ($_POST as $key => $value) {
    if ($key == "llg_post_action" || $key == 'wp_page_id')
      continue;

    $keys .= $key;
    $keys .= ',';
    $esc_val = mysql_real_escape_string ($value);

    /* Don't allow password updating, the password may have
     * been used to encrypt data already.
     */
    if ($key != 'password') {
      $update_sql .= mysql_real_escape_string ($key);
      $update_sql .= '=\'';
      $update_sql .= $esc_val;
      $update_sql .= '\',';
    }

    if ($key == 'password') {
      $insert_sql .= "PASSWORD (\"$esc_val\"),";
      continue;
    }


    $insert_sql .= '\'';
    $insert_sql .= $esc_val;
    $insert_sql .= '\',';
  }

  $update_sql = substr ($update_sql, 0, -1);
  $insert_sql = substr ($insert_sql, 0, -1);
  $keys = substr ($keys, 0, -1);


  $sql .= "($keys) VALUES(";
  $sql .= $insert_sql;
  $sql .= ") ON DUPLICATE KEY UPDATE ";
  $sql .= $update_sql;

//  echo $sql;

  $result = mysql_query($sql) or die(mysql_error());

  /* Update the form's own page */
  $event_name = mysql_real_escape_string($_POST['name']);

  $res = mysql_query ('SELECT wp_page_id FROM event WHERE name="'.$event_name.'"');
  $wp_page_id = mysql_result ($res, 0);

  $new_page = array(
    'post_title'    => $_POST['name'],
    'post_content'  => '[londonlinkbookingform event="'.$_POST['name'].'"]',
    'post_status'   => 'publish',
    'post_author'   => 1,
    'post_parent' => 498,
    'post_type'     => 'page',
    'post_name' => $_POST['name'],
    'ID' => $wp_page_id,
  );

  /* Insert the post into the database */
  $new_wp_post_id = wp_insert_post($new_page);

  /* Blindly update this */
  $sql = 'UPDATE `event` SET `wp_page_id`='.$new_wp_post_id.' WHERE name="'.$event_name.'"';

  mysql_query($sql) or die(mysql_error());
}

function toggle_event_status(){

  if (!isset($_POST['event_id']) ||
      !isset($_POST['event_state']))
      return;

  if ($_POST['event_state'] == 'Close')
    $enabled = 0;
  elseif ($_POST['event_state'] == 'Open')
    $enabled = 1;

  if (!isset($enabled))
    return;

  llg_db_connection ();

  $event_id = mysql_real_escape_string ($_POST['event_id']);

  $sql = 'UPDATE `event` SET `enabled`='.$enabled.' WHERE id='.$event_id;
  $result = mysql_query($sql) or die(mysql_error());
}


function main_page ()
{
  llg_db_connection ();

  $result = mysql_query ('SELECT * FROM event ORDER BY id DESC') or die (mysql_error ());

  $i = 0;

  $events = array();
  
  while ($current_values = mysql_fetch_assoc ($result)) {
    $current_values['page_link'] = get_permalink($current_values['wp_page_id']);

    $bookings = mysql_query('SELECT COUNT(id) FROM bookings WHERE event_name="'.$current_values['name'].'"');

    $current_values['num_bookings'] = mysql_result($bookings, 0);

    $events[] = $current_values;
  }

  $context = array(
    'events' => $events,
    'this_page' => $_GET['page'],
  );

  global $m;
  echo $m->render("event-settings", $context);
}

function add_event_page(){
  /* Nothing to see here yet */
  global $m;
  $context = array(
  );

  echo $m->render("add-event", $context);
}

?>

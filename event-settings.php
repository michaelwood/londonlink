<?php

$m = new Mustache_Engine(array(
  'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__) . '/views'),
));

function update_event () {
  $db = llg_db_connection();
  /* Check if the event already exists and update it
   * otherwise insert a new one.
   * Generates a query like:
   * INSERT INTO event (...) VALUES(...) ON DUPLICATE KEY UPDATE key=value
   */

  $sql  = "INSERT INTO events ";
  foreach ($_POST as $key => $value) {
    if ($key == "llg_post_action" || $key == 'wp_page_id' ||
      $key == 'parent_page')
      continue;

    $keys .= $key;
    $keys .= ',';
    $esc_val = mysqli_real_escape_string($db, $value);

    /* Don't allow password updating, the password may have
     * been used to encrypt data already.
     */
    if ($key != 'password') {
      $update_sql .= mysqli_real_escape_string($db, $key);
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
  $result = mysqli_query($db, $sql) or die("E2422: ".mysqli_error($db));

  /* Update the form's own page */
  $event_name = mysqli_real_escape_string($db, $_POST['name']);

  $res = mysqli_query($db, 'SELECT wp_page_id FROM events WHERE name="'.$event_name.'"');
  $wp_page_id = mysqli_fetch_array($res)[0];

  $new_page = array(
    'post_title'    => $_POST['name'],
    'post_content'  => '[qform event="'.$_POST['name'].'"]',
    'post_status'   => 'publish',
    'post_author'   => 1,
    'post_parent' => $_POST['parent_page'],
    'post_type'     => 'page',
    'post_name' => $_POST['name'],
    'ID' => $wp_page_id,
  );

  /* Insert the post into the database */
  $new_wp_post_id = wp_insert_post($new_page);

  /* Blindly update this */
  $sql = 'UPDATE `events` SET `wp_page_id`='.$new_wp_post_id.' WHERE name="'.$event_name.'"';

  mysqli_query($db, $sql) or die("E9432: ".mysqli_error($db));
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

  $db = llg_db_connection ();

  $event_id = mysqli_real_escape_string($db, $_POST['event_id']);

  $sql = 'UPDATE `events` SET `enabled`='.$enabled.' WHERE id='.$event_id;
  $result = mysqli_query($db, $sql) or die(mysqli_error());
}


function main_page()
{
  $db = llg_db_connection();

  $result = mysqli_query($db, 'SELECT * FROM events ORDER BY id DESC') or die (mysqli_error ());

  $i = 0;

  $events = array();

  while ($current_values = mysqli_fetch_assoc($result)) {
    $current_values['page_link'] = get_permalink($current_values['wp_page_id']);

    $bookings_res = mysqli_query($db, 'SELECT COUNT(id) FROM bookings WHERE event_id='.$current_values['id'].'');

    //echo $bookings_res;
    $current_values['num_bookings'] = mysqli_fetch_array($bookings_res)[0];

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
    'pages' => get_pages(),
    'org_name' => config()['org_name'],
  );

  echo $m->render("add-event", $context);
}

?>

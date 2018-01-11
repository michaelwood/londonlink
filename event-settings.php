<?php

$m = new Mustache_Engine(array(
  'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__) . '/views'),
));


function update_event (){
  $db = llg_db_connection();

  $update_sql = "UPDATE `events` SET ";
  foreach ($_POST as $key => $value) {
    /* Don't allow updating of these fields as they could impact
     * existing bookings made.
     */
    if ($key == "llg_post_action" ||
      $key == 'wp_page_id' ||
      $key == 'name' ||
      $key == 'llg_event_dash_csrf' ||
      $key == '_wp_http_referer' ||
      $key == 'password' ||
      $key == 'event_id'){
      continue;
    }

    $esc_val = mysqli_real_escape_string($db, $value);
    $update_sql .= mysqli_real_escape_string($db, $key);
    $update_sql .= '=\'';
    $update_sql .= $esc_val;
    $update_sql .= '\',';
  }

  /* remove trailing comma */
  $update_sql = substr ($update_sql, 0, -1);

  $update_sql .= ' WHERE id='.mysqli_real_escape_string($db,
    $_POST['event_id']);

  $res = mysqli_query($db, $update_sql) or die(mysqli_error($db));
}

function insert_event () {
  /* wp_parent_page is the page in which the new form page will be parented to
   * otherwise it is orphaned :(
   */
  $db = llg_db_connection();

  $sql  = "INSERT INTO events ";

  foreach ($_POST as $key => $value) {
    if ($key == "llg_post_action" ||
      $key == 'llg_event_dash_csrf' ||
      $key == 'wp_parent_page' ||
      $key == '_wp_http_referer'){
      continue;
    }

    $keys .= $key;
    $keys .= ',';
    $esc_val = mysqli_real_escape_string($db, $value);

    if ($key == 'password') {
      $insert_sql .= "PASSWORD (\"$esc_val\"),";
      continue;
    }

    $insert_sql .= '\'';
    $insert_sql .= $esc_val;
    $insert_sql .= '\',';
  }

  $insert_sql = substr ($insert_sql, 0, -1);
  $keys = substr ($keys, 0, -1);


  $sql .= "($keys) VALUES(";
  $sql .= $insert_sql;
  $sql .= ')';

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
    'post_parent' => $_POST['wp_parent_page'],
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

    $current_values['num_bookings'] = mysqli_fetch_array($bookings_res)[0];

    $events[] = $current_values;
  }


  $context = array(
    'events' => $events,
    'csrf' => wp_nonce_field("llg_event_dash", "llg_event_dash_csrf"),
    'forms' => find_available_forms(),
    'this_page' => $_GET['page'],
  );

  global $m;
  echo $m->render("event-settings", $context);
}

function find_available_forms(){
  $forms_dir = dirname(__FILE__) . '/forms/';

  $forms = array();

  foreach (scandir($forms_dir) as $key => $val){
    /* Remove the unix dir entries of .. and ../ */
    if ($val == '.' || $val == '..'){
      continue;
    }

    $forms[] = array(
      'name' => $val,
      'in_use' => function($compare, Mustache_LambdaHelper $helper){
        /* Cheeky bit of logic */
        $compare = $helper->render($compare);
        $comparison = explode(":", trim($compare), 2);

        if (strcmp($comparison[0], $comparison[1]) == 0){
          return $helper->render("selected=selected");
        }
        return;
      }
    );
  }

  return $forms;
}

function add_event_page(){
  global $m;
  $context = array(
    'pages' => get_pages(),
    'csrf' => wp_nonce_field("llg_event_dash", "llg_event_dash_csrf"),
    'org_name' => config()['org_name'],
    'forms' => find_available_forms(),
  );

  echo $m->render("add-event", $context);
}

function forms_page(){
  global $m;
  $form_dir = dirname(__FILE__) . '/forms/';

  $context = array(
    'csrf' => wp_nonce_field("llg_event_dash", "llg_event_dash_csrf"),
    'org_name' => config()['org_name'],
    'forms' => find_available_forms(),
    'forms_dir' => $form_dir,
    'selected_form' => $_POST['form_template'],
  );

  echo $m->render("view-forms", $context);

  if (!isset($_POST['form_template'])){
    return;
  }

  $fm = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader($form_dir),
  ));

  $form_dummy_context = array(
    'event' => array(
      'cost' => '23423',
      'booking_person_name' => 'BOOKING PERSON NAME',
      'enabled' => True,
      'event_end_date' => '11/22/33',
      'event_start_date' => '22/44/55',
      'name' => 'EVENT NAME',
    ),
    'img_url' => plugins_url('/img/', __FILE__),
  );

  echo '<div class="wrap llg-form-preview">';
  echo $fm->render($_POST['form_template'], $form_dummy_context);
  echo '</div>';
}

?>

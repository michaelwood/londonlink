<?php

function delete_data(){
  $db = llg_db_connection();

  if (!isset ($_POST['event_id'])) {
    echo 'E898: No event id';
    exit();
  }

  $event_id = mysqli_real_escape_string($db, $_POST['event_id']);

  $res = mysqli_query($db, 'SELECT wp_page_id FROM `events` WHERE id="'.$event_id.'"') or die (mysqli_error($db));

  $wp_page_id = mysqli_fetch_array($res)[0];

  wp_delete_post($wp_page_id, false);


  $sql = 'DELETE FROM bookings WHERE event_id="'.$event_id.'"';

  mysqli_query($db, $sql) or die (mysqli_error($db));

  $sql = 'DELETE FROM events WHERE id="'.$event_id.'"';

  mysqli_query($db, $sql) or die (mysqli_error($db));
}

?>

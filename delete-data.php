<?php

function delete_data ()
{
  llg_db_connection ();

  $event_name = mysql_real_escape_string($_POST['event_name_selected']);

  if (!isset ($event_name)) {
    return;
  }


  $res = mysql_query('SELECT wp_page_id FROM event WHERE name="'.$event_name.'"') or die (mysql_error());

  $wp_page_id = mysql_result ($res, 0);

  wp_delete_post($wp_page_id, false);


  $sql = 'DELETE FROM bookings WHERE event_name="'.$event_name.'"';

  mysql_query ($sql) or die (mysql_error ());

  $sql = 'DELETE FROM event WHERE name="'.$event_name.'"';

  mysql_query ($sql) or die (mysql_error ());


}

?>

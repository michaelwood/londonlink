<?php

function delete_data ()
{
  llg_db_connection ();

  $event = $_POST['event_name_selected'];

  if (!isset ($event)) {
    return;
  }


  $sql = 'DELETE FROM bookings WHERE event_name="'.mysql_real_escape_string ($event).'"';

  mysql_query ($sql) or die (mysql_error ());

  $sql = 'DELETE FROM event WHERE name="'.mysql_real_escape_string ($event).'"';

  mysql_query ($sql) or die (mysql_error ());


}

?>

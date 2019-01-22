<?php


function update_admin_notes(){

  if (!isset($_POST['event_id']) || !isset($_POST['notes']) || !isset($_POST['booking_id'])){
      exit_with_error("E101");
  }

  $db = llg_db_connection();
  $config = config();

  $event_id = mysqli_real_escape_string($db, $_POST['event_id']);
  $admin_notes = mysqli_real_escape_string($db, $_POST['notes']);
  $booking_id = mysqli_real_escape_string($db, $_POST['booking_id']);

  $select_booking_det = 'SELECT `password` FROM `events` WHERE id='.$event_id.' LIMIT 1';

  $res = mysqli_query($db, $select_booking_det) or exit_with_error("E105", mysqli_error($db) . $select_booking_det);
  $event_details = mysqli_fetch_assoc($res);

  $pw = $event_details['password'];
  $salt = file_get_contents($config['saltfile'], FILE_USE_INCLUDE_PATH);

  if ($salt === false){
    exit_with_error("E103");
  }

  $pw .= $salt;

  $update_admin_notes = 'UPDATE bookings SET admin_notes=AES_ENCRYPT("'.$admin_notes.'", "'.$pw.'") WHERE id='.$booking_id.' LIMIT 1';

  mysqli_query($db, $update_admin_notes) or exit_with_error("E104", mysqli_error($db) . $update_admin_notes);

  exit();
}

?>
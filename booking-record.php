<?php

include_once ('config.php');

function exit_with_error($err_msg, $details){
  if (config()['debug'] == true){
    $err_msg .= ' '.$details;
  }

  header("X-llg-booking:" . $err_msg);
  http_response_code(500);
  exit();
}

/* save_booking:
 * Requires POST _wpnonce, event_id, form_data
 */
function save_booking(){
  $config = config ();

  if (!isset($_POST['_wpnonce']) ||
    !isset($_POST['event_id']) ||
    !isset($_POST['form_data'])){
    exit_with_error("E99");
  }

  /* CSRF */
  if (!wp_verify_nonce($_POST['_wpnonce'])){
    exit_with_error("E100");
  }

  $form_data = json_decode(stripslashes($_POST['form_data']), true);

  if (!$form_data){
    exit_with_error("E101");
  }


  /* Test the anti spam answer */
  if (strtolower($form_data['anti_spam']) != strtolower($config['antispam'])){
    exit_with_error("E102");
  }

  $db = llg_db_connection();

  $event_id = mysqli_real_escape_string($db, $_POST['event_id']);

  $select_booking_det = 'SELECT `name`, `booking_person_email`, `password` FROM `events` WHERE id='.$event_id.' LIMIT 1';

  $res = mysqli_query($db, $select_booking_det) or exit_with_error("E105", mysqli_error($db) . $select_booking_det);
  $event_details = mysqli_fetch_assoc($res);

  $pw = $event_details['password'];
  $salt = file_get_contents($config['saltfile'], FILE_USE_INCLUDE_PATH);

  if ($salt === false){
    exit_with_error("E103");
  }

  $pw .= $salt;

  $json_string_booking = json_encode($form_data);
  $json_string_booking = mysqli_real_escape_string($db, $json_string_booking);

  $insert_booking = 'INSERT INTO bookings (`event_id`, `data`) VALUES('.$event_id.',
    AES_ENCRYPT("'.$json_string_booking.'", "'.$pw.'"))';

  mysqli_query($db, $insert_booking) or exit_with_error("E104");

  $booking_person_email = $event_details['booking_person_email'];

  $mail_to = filter_var($_POST['parent_guardian_email'], FILTER_SANITIZE_EMAIL);
  $subject = 'Booking received for: '.$event_details['name'];
  $participant_email = filter_var($_POST['participant_email'], FILTER_SANITIZE_EMAIL);

  if (isset ($participant_email)) {
    $mail_cc = $participant_email.','.$booking_person_email;
  } else {
    $mail_cc = $booking_person_email;
  }

  $mail_body = 'Hello,'."\n\n";
  $mail_body .= 'We have received your booking for '.filter_var($_POST['full_name'], FILTER_SANITIZE_STRING).'.';
  $mail_body .= "\n";
  $mail_body .= 'If there any problems please don\'t hesitate to contact the bookings person for this event (CC d)';
  $mail_body .= "\n\n";
  $mail_body .= 'Thank you';
  $mail_body .= "\n\n";
  $mail_body .= '---';
  $mail_body .= "\n";
  $mail_body .= 'http://'.$config['domain'].'';

  $headers = 'From: '.$config['from'].''."\r\n";
  $headers .= 'Cc:'.$mail_cc."\r\n";
  $headers .= 'bcc: '.$config['admin_email']."\r\n";
  $headers .= "Content-type: text/plain; charset=iso-8859-1\r\n";
  $headers .= 'Reply-To:'.$booking_person_email;

  mail ($mail_to, $subject, $mail_body, $headers, '-f '.$config['from']);
  if ($add_to_mailing_list) {
    $emails_to_add = array ($mail_to, $participant_email);
    add_to_mailing_list ($emails_to_add);
  }
}

?>

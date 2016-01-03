<?php

include_once ('config.php');

function simple_pw_gen ()
{
  $possibles = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
  $string = "";

  for ($i=0; $i < 7; $i++) {
    if ($i == rand (0,7))
      $string .= rand (0,9);
    else
      $string .= $possibles{rand (0, 51)};
  }
  return $string;
}

function already_subscribed ($email)
{
  $mailman_pw = config ()['mailman'];

  $url = "http://lists.londonlinkgroup.org.uk/cgi-bin/mailman/admin/llg/members?adminpw=".$mailman_pw."&findmember=".$email;

  $ret = file_get_contents ($url);

  if (strpos ($ret, '1 members total') > 0)
    return true;

  return false;
}

function add_to_mailing_list ($emails)
{
  $url = 'http://lists.londonlinkgroup.org.uk/cgi-bin/mailman/subscribe/llg?';

  foreach ($emails as $email) {
    if (already_subscribed ($email) == true)
      continue;

    $pw = simple_pw_gen ();

    $data = 'fullname=&email='.$email.'&pw='.$pw.'&pw-conf='.$pw.'&language=en&email-button=Subscribe';
    /* Send http get request to mailing list server to subscribe
     * persons to receive email updates.
     */
    file_get_contents ($url.$data, false);
  }
}

function save_booking ()
{
  llg_db_connection ();

  $add_to_mailing_list = false;
  $array = $_POST;

  $sql = 'SELECT booking_person_email, password FROM event WHERE name=\''.mysql_real_escape_string ($_POST['event_name']).'\' LIMIT 1';

  $res = mysql_query ($sql) or die ("Problem");
  $details = mysql_fetch_assoc ($res);

  $pw = $details['password'];
  $booking_person_email = $details['booking_person_email'];

  $values ="";
  $keys ="";

  /* AES_ENCRYPT ('value', 'key') */

  foreach ($array as $key => $val)
  {
    if ($key == 'llg_post_action' || $key == 'anti_spam')
      continue;

    $keys .= mysql_real_escape_string ($key).',';

    /* Don't encrypt event name */
    if ($key == 'event_name') {
      $values .= '\''.mysql_real_escape_string ($val).'\',';
      continue;
    }

    if ($key == 'use_contact_dets' && $value == 'yes')
      $add_to_mailing_list = true;

    $values .= 'AES_ENCRYPT (\'';
    $values .= mysql_real_escape_string ($val);
    $values .= '\',\'';
    $values .= $pw;
    $values .= '\'),';
  }

  /* remove the trailing comma */
  $values = substr ($values, 0, -1);
  $keys = substr ($keys, 0, -1);

  $sql  = "INSERT INTO bookings";
  $sql .= " (".$keys.")";
  $sql .= " VALUES (".$values.")";

  $result = mysql_query($sql) or die(http_response_code(500));

  $mail_to = $_POST['parent_guardian_email'];
  $subject = 'Booking received for: '.$_POST['event_name'];
  $participant_email = $_POST['participant_email'];

  if (isset ($participant_email)) {
    $mail_cc = $participant_email.','.$booking_person_email;
  } else {
    $mail_cc = $booking_person_email;
  }

  $admin_email = 'internet@michaelwood.me.uk';
  $mail_body = 'Hello,'."\n\n";
  $mail_body .= 'We have received your booking for '.$_POST['full_name'].'.';
  $mail_body .= "\n";
  $mail_body .= 'If there any any problems please don\'t hesitate to contact the bookings person for this event (CC d)';
  $mail_body .= "\n\n";
  $mail_body .= 'Thank you';
  $mail_body .= "\n\n";
  $mail_body .= '---';
  $mail_body .= "\n";
  $mail_body .= 'http://londonlinkgroup.org.uk';

  $headers = 'From: noreply@londonlinkgroup.org.uk'."\r\n";
  $headers .= 'Cc:'.$mail_cc."\r\n";
  $headers .= 'bcc: '.$admin_email."\r\n";
  $headers .= 'Reply-To:'.$booking_person_email;

  mail ($mail_to, $subject, $mail_body, $headers);
  if ($add_to_mailing_list) {
    $emails_to_add = array ($mail_to, $participant_email);
    add_to_mailing_list ($emails_to_add);
  }
}

?>

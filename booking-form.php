<?php

function get_event_data ($event_name)
{
  llg_db_connection ();

  /* If we don't have an event name set then just get the latest one */
  if (!isset($event_name))
    $sql = 'SELECT `name`, `booking_person_name`, `event_start_date`, `event_end_date` , `enabled`, `cost` FROM event ORDER BY id DESC LIMIT 1 ';
  else
    $sql = 'SELECT `name`, `booking_person_name`, `event_start_date`, `event_end_date`, `enabled`, `cost` FROM event  WHERE name="'.$event_name.'" ORDER BY id DESC LIMIT 1 ';

  $result = mysql_query($sql) or die(mysql_error());
  if (mysql_num_rows ($result) > 0)
    $event_data = mysql_fetch_assoc ($result);

  return $event_data;
}

function booking_form_get_string ($event_name)
{
  $ret = "";

  /* If we're not already using SSL don't allow continue, redirect instead */
  if (empty ($_SERVER['HTTPS']))
  {
    $correct_url = 'https://';
    $correct_url .= $_SERVER['HTTP_HOST'];
    $correct_url .= $_SERVER['REQUEST_URI'];

    $ret = '<p><a href="'.$correct_url.'" title="Switch to SSL version">Please view this page using the secure server</a> Redirecting in 3 seconds..</p><script type="text/javascript">setTimeout("redirect()",3000); function redirect() { location.href = "'.$correct_url.'"; } </script>';
    return $ret;
  }

  $event_data = get_event_data ($event_name);
  if ($event_data['enabled'] == 0)
    return '<p>Sorry bookings are now closed. <a href="/contact">Contact for further enquieries</a></p>';

  $form_top = file_get_contents ("form-top.html", FILE_USE_INCLUDE_PATH);
  $form_bottom = file_get_contents ("form-bottom.html", FILE_USE_INCLUDE_PATH);

  if (!$form_top || !$form_bottom)
    return '<p>Something went wrong :/ E12</p>';

  $ret .= $form_top;

  if (isset ($event_data) == 0) {
    $ret .= "No event found E13";
    return $ret;
  }

  if ($event_data['cost'] == 0){
    $cost_string = 'FREE';
    $ret .= '<style>#payment-selection { display: None }</style>';
  } else {
    $cost_string = $event_data['cost'];
  }

  /* Add event data info */
  $ret .= '
    <ul><li>Booking for: <b>'.$event_data['name'].'</b></li><li>Date '.$event_data['event_start_date'].' to '.$event_data['event_end_date'].'</li><li>Contact person: '.$event_data['booking_person_name'].'<li>Cost: £'.$cost_string.'</ul>
    <span id="booking-area">
    <form id="llg-check-form"  method="POST">
    <input type="hidden" name="event_name" value="'.$event_data['name'].'" />';

  $ret .= $form_bottom;
  return $ret;
}

?>

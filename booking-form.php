<?php

function get_event_data($event_name){
  $db = llg_db_connection();

  /* If we don't have an event name set then just get the latest one */
  if (!isset($event_name))
    $sql = 'SELECT * FROM events ORDER BY id DESC LIMIT 1 ';
  else
    $sql = 'SELECT * FROM events  WHERE name="'.$event_name.'" ORDER BY id DESC LIMIT 1 ';

  $result = mysqli_query($db, $sql) or die(mysqli_error($db));
  if (mysqli_num_rows ($result) > 0)
    $event_data = mysqli_fetch_assoc ($result);

  return $event_data;
}

function booking_form_get_string ($event_name){
  $m = new Mustache_Engine(array(
    'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__) . '/views'),
  ));

  /* If we're not already using SSL don't allow continue, redirect instead */
  if (empty($_SERVER['HTTPS']))
  {
    $correct_url = 'https://';
    $correct_url .= $_SERVER['HTTP_HOST'];
    $correct_url .= $_SERVER['REQUEST_URI'];

    return '<p>&#x1f512; <a href="'.$correct_url.'"><span id="https-redirect-notice">Redirecting to secure server</a></p>';
  }

  $event_data = get_event_data($event_name);
  if ($event_data['enabled'] == 0)
    return '<p>Sorry bookings are now closed. <a href="/contact">Contact for further enquieries</a></p>';

  if (isset ($event_data) == 0) {
    $ret .= "No event found E13";
    return $ret;
  }

  $context = array(
    'event' => $event_data,
    'img_url' => plugins_url('/img/', __FILE__),
  );

  $ret = '
  <script>
    var llgCSRF = "'.wp_create_nonce().'";
    var llgEventId = '.$event_data['id'].';
  </script>';

  $form_template = 'default-form';

  if ($event_data['form_template']){
    $form_template = $event_data['form_template'];
  }

  $ret .= $m->render($form_template, $context);

  return $ret;
}

?>

<?php

$ADD_NEW_EVENT = 0;

function update_event () {

  llg_db_connection ();

  /* Check if the event already exists and update it
   * otherwise insert a new one.
   * Generates a query like:
   * INSERT INTO event (...) VALUES(...) ON DUPLICATE KEY UPDATE key=value
   */

  $sql  = "INSERT INTO event ";
  foreach ($_POST as $key => $value) {
    if ($key == "llg_post_action")
      continue;

    $keys .= $key;
    $keys .= ',';
    $esc_val = mysql_real_escape_string ($value);

    /* Don't allow password updating, the password may have
     * been used to encrypt data already.
     */
    if ($key != 'password') {
      $update_sql .= mysql_real_escape_string ($key);
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

  $result = mysql_query($sql) or die(mysql_error());
}

function toggle_event_status () {

  if (!isset($_POST['event_id']) ||
      !isset($_POST['event_state']))
      return;

  if ($_POST['event_state'] == 'Close')
    $enabled = 0;
  elseif ($_POST['event_state'] == 'Open')
    $enabled = 1;

  if (!isset($enabled))
    return;

  llg_db_connection ();

  $event_id = mysql_real_escape_string ($_POST['event_id']);

  $sql = 'UPDATE `event` SET `enabled`='.$enabled.' WHERE id='.$event_id;
  $result = mysql_query($sql) or die(mysql_error());
}

/* TODO refactor */
function bookings_settings_form ($current_values, $i) {

  $res = mysql_query ('SELECT COUNT(id) FROM bookings WHERE event_name="'.$current_values['name'].'"');
  $event_stat .= mysql_result ($res, 0);

  $event_toggle_button = "";
  if ($current_values['enabled'] == 1)
    $event_toggle_button = '<input type="submit" name="event_state" value="Close" />';
  else
    $event_toggle_button = '<input type="submit" name="event_state" value="Open" />';

  $ret ='
    <tr>
      <td style="vertical-align: bottom">
        <span class="llg-arrow"></span>
        <a href="#" style="padding: 3px" onClick="open_form (\''.$i.'\')" >'.$current_values['name'].'</a>
        <span class="bookingdetails" style="display:none" id="'.$i.'">
        <form id="llg-check-form" method="post" action="?page='.$_GET['page'].'">

        <h3>Edit event</h3>
        <table>
          <tr>
          <td><label for="name">Event name</label></td>
          <td><input type="text" id="name" name="name" value="'.$current_values['name'].'" /></td>
          </tr>';

  if ($i != $ADD_NEW_EVENT) {
    $ret .='
      <tr>
      <td title="Insert into any page to embed online form">Short code</td>
      <td>[londonlinkbookingform event="'.$current_values['name'].'"]</td>
      </tr>';
  }

          $ret .= '<tr>
          <td><label for="event_start_date">Event start date (dd/mm/yyyy)</label></td>
          <td><input type="text" id="event_start_date" name="event_start_date" value="'.$current_values['event_start_date'].'" required/></td>
          </tr>

          <tr>
          <td><label for="event_end_date">Event end date (dd/mm/yyyy)</label></td>
          <td><input type="text" id="event_end_date" name="event_end_date" value="'.$current_values['event_end_date'].'" required/></td>
          </tr>

          <tr>
          <td><label for="booking_person_name">Bookings person name</label></td>
          <td><input type="text" id="booking_person_name" name="booking_person_name" value="'.$current_values['booking_person_name'].'" required/></td>
          </tr>

          <tr>
          <td><label for="booking_person_email">Bookings person email</label></td>
          <td><input type="text" id="booking_person_email" name="booking_person_email" value="'.$current_values['booking_person_email'].'" required/></td>
          </tr>';

          if (!isset ($current_values['password'])) {
              $ret .= '
          <tr>
          <td><label for="password">Bookings data password<br /><b>DO NOT LOSE THIS PASSWORD</b><small> The data will be encrypted with this password there is no recovery once data has been encryped.</small></label></td>
          <td><input type="text" id="password" name="password" /></td>
          </tr>';
          }

          $ret .= '
          <tr><td></td><td>
          <input type="button" value="Save" onClick="admin_check_form ('.$i.')" />
          </td>
          </tr>
          </table>
        <input type="hidden" name="llg_post_action" value="update_event" />
        </form>
        </span>
    </td>
    <!-- event actions -->
    ';
    if ($i != $ADD_NEW_EVENT) {
      $ret .= '
    <td style="vertical-align: bottom;">
    <span class="bookingdetails" style="display:none" id="actions-'.$i.'">

      <h3>Bookings data</h3>
      <form method="post" action="?page='.$_GET['page'].'">
      <table class="stats">
      <tr>
      <td>Set bookings status</td>
      <td>'.$event_toggle_button.'
      <input type="hidden" name="llg_post_action" value="toggle_event_status" />
      <input type="hidden" name="event_id" value="'.$current_values['id'].'" />
      </form>
      <form method="post" action="?page='.$_GET['page'].'">
      </td>
       </tr>
        <th><strong>Download current bookings data</strong></th>
        <tr>
         <td><label for="password">Password: </label></td>
         <td><input type="password" id="password" name="password" /></td>
        </tr>
        <tr>
          <td />
          <td>
            <input type="radio" name="output_type" value="csv" checked> Speadsheet<br/>
            <input type="radio" name="output_type" value="pdf"> PDF <br />
            <input type="submit" value="Download" />
          </td>
        </tr>
        <input type="hidden" name="event_name_selected" value="'.$current_values['name'].'" />
        <input type="hidden" name="llg_post_action" value="download_data" />
      </form>
      <form id="'.$i.'-form-delete" method="post" action="?page='.$_GET['page'].'">
      <input type="hidden" id="'.$i.'-event-name" name="event_name_selected" value="'.$current_values['name'].'" />
      <input type="hidden" name="llg_post_action"  value="delete_data" />
      <tr>
        <td>Delete event and data</td>
        <td><input type="button" value="Delete" onClick=\'really_delete ('.$i.')\'></td>
      </tr>
      </table>
      </form>
    </span>
    </td>
    <td style="text-align: center; "><strong>'.$event_stat.'</strong></td>
    ';
    } else {
      $ret .= '<td></td><td></td>';
    }

    $ret .= '</tr>';

  return $ret;
}

function main_page ()
{
  llg_db_connection ();

  echo '
    <style>
    .bookingdetails table {
      background-color: #fffbe4;
    }

    .llg-arrow {
      float: left;
      height: 15px;
      width: 15px;
        border-color: #ccc;
        border-radius: 10px;
        -webkit-border-radius: 10px;
      background-image: transparent url(./images/arrows.png) no-repeat 6px 7px;
      background-position: 0 -108px;
        background-size: 15px 123px;
        background-image: url(./images/arrows.png), -webkit-gradient(linear, left bottom, left top, from(#dfdfdf), to(#fff));
        background-image: -url(./images/arrows.png), webkit-linear-gradient(bottom, #dfdfdf, #fff);
        background-image:  url(./images/arrows.png),   -moz-linear-gradient(bottom, #dfdfdf, #fff);
        background-image: url(./images/arrows.png),      -o-linear-gradient(bottom, #dfdfdf, #fff);
        background-image: url(./images/arrows.png), linear-gradient(to top, #dfdfdf, #fff);
    }
    .llg-arrow.open {
      background-position: 0 0px;
    }


    </style>
    <h2>London Link Bookings</h2>
    <table class="wp-list-table widefat fixed events">
    <colgroup>
      <col style="width: 50%" />
      <col style="width: 45%" />
      <col style="width: 10%" />
    </colgroup>
    <thead>
      <th>Event details</th>
      <th>Actions</th>
      <th>Bookings</th>
    </thead>';

  $result = mysql_query ('SELECT * FROM event ORDER BY id DESC') or die (mysql_error ());

  $i = 0;
  $new_event_form = bookings_settings_form (array (name => "Add new event"), $i);
  echo $new_event_form;
  while ($current_values = mysql_fetch_assoc ($result)) {
    $form = bookings_settings_form ($current_values, ++$i);
    echo $form;
  }

  echo '</table>
    <script type="text/javascript">

function really_delete (i) {
  var deleteForm = jQuery("#"+i+"-form-delete");
  var eventNameToDelete = jQuery("#"+i+"-event-name").val ();

  var ret = confirm ("Really delete event "+eventNameToDelete+" and all it\'s data?!");
  if (ret == true) { deleteForm.submit (); }
}

function open_form (id) {
  var eventDiv = jQuery ("#"+id);
  var eventActions = jQuery ("#actions-"+id);

  if (eventDiv.css ("display") != "inline") {
    jQuery (".bookingdetails").css ("display", "none");
    jQuery (".llg-arrow").toggleClass ("open", false);
    eventDiv.css ("display", "inline");
    eventActions.css ("display", "inline");
    jQuery (".llg-arrow").eq(id).toggleClass ("open", true);
}
else {
  eventDiv.css ("display", "none");
  eventActions.css ("display", "none");
  jQuery (".llg-arrow").eq(id).toggleClass ("open", false);
}

}

/* I is form instance number */
function admin_check_form (i) {
  var form = jQuery("#"+i+" #llg-check-form");
  /* TODO this needs to get the right form instance */
  var failed = llg_check_form (form);
  if (!failed) {
    form.submit ();
} else {
  alert ("Please fill in all fields");
}
}
</script>';

}

?>

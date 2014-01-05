<?php

function update_event () {

  llg_db_connection ();

  $sql  = "UPDATE event SET ";
  foreach ($_POST as $key => $value) {
    if ($key == "llg_post_action")
      continue;
    $sql .= mysql_real_escape_string ($key);
    $sql .= '=\'';
    $sql .= mysql_real_escape_string ($value);
    $sql .= '\',';
  }

  $sql = substr ($sql, 0, -1);

  $result = mysql_query($sql) or die(mysql_error());
}

function main_page ()
{
  llg_db_connection ();
  /* TODO support simulataneous multiple events update? */
  $result = mysql_query ('SELECT * FROM event ORDER BY id DESC LIMIT 1') or die (mysql_error ());
  $current_values = mysql_fetch_assoc ($result);


  echo '
    <h2>London Link Bookings</h2>
    <h3>Current event details</h3>

    <form id="llg-check-form" method="post" action="?page='.$_GET['page'].'">
    <table>
    <tr>
    <td><label for="name">Event name</label></td>
    <td><input type="text" id="name" name="name" value="'.$current_values['name'].'" /></td>
    </tr>

    <tr>
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
    </tr>
    <tr><td></td><td>
    <input type="button" value="Save" onClick="admin_check_form ()" />
    </td>
    </tr>
    </table>
    <input type="hidden" name="llg_post_action" value="update_event" />
    </form>
    <script type="text/javascript">
function admin_check_form () {

  var failed = llg_check_form ();
  if (!failed) {
    jQuery ("#llg-check-form").submit ();
} else {
  alert ("Please fill in all fields");
}
}
</script>';

}

function sub_page ()
{
  llg_db_connection ();
  $result = mysql_query ('SELECT name FROM event') or die (mysql_error ());
  $current_values = mysql_fetch_assoc ($result);

  $options = '<select name="event_name_selected" id="select_event_name" >';
  foreach ($current_values as $val) {
    $event_stat .='<tr><td>'.$val.'</td><td>';
    $res = mysql_query ('Select id FROM bookings WHERE event_name="'.$val.'"');
    $event_stat .= mysql_num_rows ($res);
    $event_stat .= '</td></tr>';

    $options .= '<option value="'.$val.'">'.$val.'</option>';
  }
  $options .= '</select>';


  echo '
    <style>
    label { margin-left: 3px; }
    label:after { color: red; content: "*"; }
    .not-required:after { content: none; }
    table { border-collapse: collapse; padding: 1px; }
    table tr:nth-child(even)  {background-color:#ffffff;}
    th { padding-top: 30px; text-align: left; }
    input[type=radio] {float: left; }
    input, textarea, select { width: 260px }
    </style>

    <h2>Booking Data</h2>
    <p>Download, Delete and View bookings information</p>
    <table class="stats">
    <th>Event</th><th>Num bookings</th>
    '.$event_stat.'

    <th>Download current bookings data</th>

    <form method="post" action="?page='.$_GET['page'].'">

    <tr>
      <td><label for="select_event_name">Event: </label></td>
      <td>'.$options.'</td>
    </tr>

    <tr>
      <td><label for="password">Password: </label></td>
      <td><input type="password" id="password" name="password" /></td>
    </tr>

    <tr>
      <td />
      <td><input type="submit" value="Download" /></td>
    </tr>

      <input type="hidden" name="llg_post_action" value="download_data" />
    </form>


    <th>Delete bookings data</th>

    <form id="form_delete" method="post" action="?page='.$_GET['page'].'">
    <tr>
     <td><label for="select_event_name">Event: </label></td>
     <td>'.$options.'</td>
    </tr>

    <tr>
    <td />
    <td><input type="button" value="Delete" onClick=\'really_delete ()\'></td>
    </tr>
    <input type="hidden" name="llg_post_action" value="delete_data" />
    </table>
    </form>
    <script type="text/javascript">
      function really_delete () {
        var ret = confirm (\'Really delete?!\');
        if (ret == true) { jQuery (\'#form_delete\').submit (); }
      }
    </script>
    ';
}
?>

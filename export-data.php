<?php

include_once('config.php');

function non_encryped_fields ($field)
{
  /* Skip these fields they aren't encrypted */
  if ($field == 'id' ||
      $field == 'submit_timestamp' ||
      $field == 'event_name') {
        return true;
      }

  return false;
}


function export_data ()
{
  /* If we're not already using SSL don't allow continue, redirect instead */
  if (empty ($_SERVER['HTTPS'])) {
    $correct_url = 'https://';
    $correct_url .= $_SERVER['HTTP_HOST'];
    $correct_url .= $_SERVER['REQUEST_URI'];

    echo '<a href='.$correct_url.'>Please go to secure server first</a>';
    exit;
  }

  llg_db_connection ();

  $config = config();

  $pass = $_POST['password'];
  $event_name = $_POST['event_name_selected'];

  if (!isset ($pass))
    return;

  $res = mysql_query ('SELECT COUNT(password) FROM event WHERE name="'.mysql_real_escape_string ($event_name).'" AND password=PASSWORD("'.mysql_real_escape_string ($pass).'") LIMIT 1') or die (mysql_error ());
  $db_key = mysql_result ($res, 0);

  if ($db_key != 1) {
    echo '
    <script type="text/javascript">
      alert ("Sorry that\'s not the correct password");
      location.reload();
    </script>';
    exit;
  }

  header ('Content-type:text/csv',true);
  header ('Content-Disposition: attachment; filename="LLG-'.$event_name.'-'.date("d-m-y").'.csv"', true);

  $col_headers = "";
  $decrypt_fields = "";
  $csv = "";

  $salt = file_get_contents($config['saltfile'], FILE_USE_INCLUDE_PATH);
  if ($salt === false){
    http_response_code(500);
    exit();
  }

  /* value appended to colname of decrypted field */
  $decrypt_identifier = "_decrypt";
  $decrypt_identifier_len = strlen ($decrypt_identifier);
  $res = mysql_query ("SHOW COLUMNS FROM bookings") or die (mysql_error ());

  /* Make a "AES_DECRYPT ('field', 'key') as field_decrypt" string
   * for all the fields apart from the non_encryped_fields
   */
  while ($row = mysql_fetch_assoc ($res)) {
    $field = $row['Field'];
    $col_headers .= $field;
    $col_headers .= ",";
    if (non_encryped_fields ($field))
      continue;

    $decrypt_fields .= 'AES_DECRYPT ('.$field.', CONCAT(PASSWORD("'.$pass.'"), "'.$salt.'")) as '.$field.$decrypt_identifier.',';
  }

  $decrypt_fields = substr ($decrypt_fields, 0, -1);

  $sql = 'SELECT *, '.$decrypt_fields.' FROM bookings WHERE event_name="'.mysql_real_escape_string ($event_name).'"';

  $res = mysql_query ($sql) or die (mysql_error ());


  while ($row_arr = mysql_fetch_assoc ($res)) {
    /* skip the encryped restuls i.e. medical_info ,
     * in favour of medical_info_decrypt version
     */
    foreach ($row_arr as $key => $value) {
      if (non_encryped_fields ($key) == true) {
        $csv .= $value;
        $csv .= ',';
        continue;
      }

      if (substr ($key, -$decrypt_identifier_len) == $decrypt_identifier) {
        $csv .= '"';
        $csv .= $value;
        $csv .= '"';
        $csv .= ',';
      }
    }
    /* Remove trailing comma */
    $csv = substr ($csv, 0, -1);
    $csv .= "\n";
  }
  echo $col_headers;
  echo "\n";
  echo $csv;
  exit;
}
?>

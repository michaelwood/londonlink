<?php

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

  header ('Content-type:text/csv',true);
  header ('Content-Disposition: attachment; filename="LLG_data_'.date("d-m-y").'.csv"', true);

  llg_db_connection ();

  $key = md5 ($_POST['password']);

  /* TODO compare $key with db value before continue */
  if (!isset ($key))
    return;

  $col_headers = "";
  $decrypt_fields = "";
  $csv = "";
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

    $decrypt_fields .= 'AES_DECRYPT ('.$field.',"'.$key.'") as '.$field.$decrypt_identifier.',';
  }

  $decrypt_fields = substr ($decrypt_fields, 0, -1);

  $sql = 'SELECT *, '.$decrypt_fields.' FROM bookings WHERE event_name="'.mysql_real_escape_string ($_POST['event_name_selected']).'"';

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

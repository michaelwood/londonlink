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
    echo "Sorry that is not the correct password <a href=\"";
    echo $_SERVER["REQUEST_URI"];
    echo "\">back</a>";
    exit;
  }


  $col_headers = "";
  $decrypt_fields = "";

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


  switch ($_POST['output_type']){
    case 'csv':
      output_as_csv($res, $event_name, $col_headers);
      break;
    case 'pdf':
      output_as_pdf($res, $event_name);
      break;
    default:
      echo "unknown output type";
      exit;
  }
}

function output_as_csv($res, $event_name, $col_headers){

  header ('Content-type:text/csv',true);
  header ('Content-Disposition: attachment; filename="LLG-'.$event_name.'-'.date("d-m-y").'.csv"', true);

  $csv = "";
  /* value appended to colname of decrypted field */
  $decrypt_identifier = "_decrypt";
  $decrypt_identifier_len = strlen ($decrypt_identifier);
 
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

/* format the text a bit nicer */
function write_kv($pdf, $key, $value){
  $decrypt_identifier = "_decrypt";
  $key = str_replace($decrypt_identifier, "", $key);
  $key = str_replace("_", " " ,$key);
  $pdf->Write(5, $key.": ".$value);
  $pdf->SetY($pdf->GetY() + 5);
}

function output_as_pdf($res, $event_name){

  require('fpdf.php');


  class PDF extends FPDF {
    function Footer(){
      // Position at 1.5 cm from bottom
      $this->SetY(-15);
      // Arial italic 8
      $this->SetFont('Arial','I',8);
      // Page number
      $this->Cell(0,10,'LLG Bookings CONFIDENTIAL - Destroy after use - Page '.$this->PageNo().'/{nb}',0,0,'C');
    }
  }

  $pdf = new PDF('P', 'mm', 'A4');
  $pdf->AliasNbPages();
  $pdf->SetTitle($event_name);
  $pdf->SetAutoPageBreak(True, 20);
  $pdf->SetLineWidth(2);
  $pdf->SetDrawColor(255,255,255);
  $pdf->AddPage();

  $pdf->SetFont('Arial','',15);
  $pdf->Write(5, "London Link Bookings: ".$event_name);

  $pdf->SetY($pdf->GetY() + 10);

  $pdf->Line(0, 100, 10, 100);
  $pdf->SetFont('Arial','',12);

  /* value appended to colname of decrypted field */
  $decrypt_identifier = "_decrypt";
  $decrypt_identifier_len = strlen ($decrypt_identifier);

  while ($row_arr = mysql_fetch_assoc ($res)) {
    /* skip the encryped restuls i.e. medical_info ,
     * in favour of medical_info_decrypt version
     */
    foreach ($row_arr as $key => $value) {
      if (non_encryped_fields ($key) == true) {
        write_kv($pdf, $key, $value);
        continue;
      }

      if (substr ($key, -$decrypt_identifier_len) == $decrypt_identifier) {
        write_kv($pdf, $key, $value);
      }
    }

    $pdf->SetY($pdf->GetY() + 10);
  }

  $pdf->Output();
}
?>

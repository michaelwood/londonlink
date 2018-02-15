<?php

include_once('config.php');

function export_data(){

  /* If we're not already using SSL don't allow continue, redirect instead */
  if (empty ($_SERVER['HTTPS'])) {
    $correct_url = 'https://';
    $correct_url .= $_SERVER['HTTP_HOST'];
    $correct_url .= $_SERVER['REQUEST_URI'];

    echo '<a href='.$correct_url.'>Please go to secure server first</a>';
    exit();
  }

  $db = llg_db_connection();
  $config = config();

  $pass = $_POST['password'];
  $event_id = mysqli_real_escape_string($db, $_POST['event_id']);
  $event_name = mysqli_real_escape_string($db, $_POST['event_name']);

  if (!isset ($pass))
    return;

  $select_password = 'SELECT COUNT(password) FROM events WHERE id="'.$event_id.'" AND password=PASSWORD("'.mysqli_real_escape_string($db, $pass).'") LIMIT 1';
  $pass_res = mysqli_query($db, $select_password) or die (mysqli_error($db));

  if (mysqli_fetch_assoc($pass_res)['COUNT(password)'] != 1){
    echo "Sorry that is not the correct password <a href=\"";
    echo $_SERVER["REQUEST_URI"];
    echo "\">back</a>";
    exit();
  }


  $salt = file_get_contents($config['saltfile'], FILE_USE_INCLUDE_PATH);
  if ($salt === false){
    http_response_code(500);
    exit();
  }

  $select_booking_data = '
    SELECT bookings.id, bookings.submit_timestamp,
     AES_DECRYPT(data, CONCAT(PASSWORD("'.$pass.'"), "'.$salt.'")) AS data
     FROM bookings WHERE bookings.event_id='.$event_id.' ORDER BY id ASC';

/*   Possible join for getting event information
 *   RIGHT JOIN events ON bookings.event_id=events.id
 *   WHERE bookings.event_id='.$event_id.'';
 */
  $res = mysqli_query($db, $select_booking_data) or die (mysqli_error ($db));

  switch ($_POST['output_type']){
    case 'csv':
      output_as_csv($res, $event_name);
      break;
    case 'pdf':
      output_as_pdf($res, $event_name);
      break;
    case 'html':
      output_as_html($res, $event_name);
      break;
    default:
      echo "Err Unknown output type";
      exit();
  }
}

function skip_keys($key){
  return in_array($key, ['anti_spam', 'llg_post_action', 'event_id']);
}

function output_as_html($res, $event_name){
	$csv = output_as_csv($res, $event_name, false);
	$table = '';

  foreach (str_getcsv($csv, "\n") as $index => $row){

    if ($index == 0){
      $table .='<thead><tr>';
      foreach (str_getcsv($row, ',') as $cell){
        $table .="<th>$cell</th>";
      }
      $table .= '</tr></thead><tbody>';
      continue;
    }


    $table .='<tr>';
    foreach (str_getcsv($row, ',') as $cell){
      $table .="<td>$cell</td>";
    }
		$table .='</tr>';
  }
  $table .='</tbody>';

  $context = array(
    'table' => $table,
		'event_name' => $event_name,
    'org_name' => config()['org_name'],
    'js_dir' => plugins_url('/js/', __FILE__),
  );

	$m = new Mustache_Engine(array(
		'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__) . '/views'),
	));

  echo $m->render("html-data-output", $context);

  exit();
}

/* We want to avoid breaking the csv document so if the string contains a
 * new line or comma or a quote then we process this to give either the output
 * csv a fighting chance of being parsed correctly or to make the html output
 * safe.
 */
function clean_up_str($string, $file_output){

  if (!$file_output){
    $string = filter_var($string, FILTER_SANITIZE_STRING,
      FILTER_FLAG_NO_ENCODE_QUOTES);
    $string = str_replace("\n", "<br /> ", $string);
  } else {
    $string = str_replace("\"", "'", $string);
  }

  if (strpos($string, "\n") !== false ||
    strpos($string, ",") !== false){
    return "\"".$string."\"";
  }

  return $string;
}

function output_as_csv($res, $event_name, $file_output=true){
  $config = config();

  if ($file_output == true){
    header ('Content-type:text/csv',true);
    header ('Content-Disposition: attachment; filename="'.$config['org_name'].'-'.$event_name.'-'.date("d-m-y").'.csv"', true);
  }

  $csv_out = "";
  $col_headers_previous = '';

  while ($row_arr = mysqli_fetch_assoc($res)) {

    $csv_row = "";
    $col_headers = "";

    foreach ($row_arr as $key => $value) {


      if ($key == 'event_name'){
        $event_name = $value;
        continue;
      }

      if (skip_keys($key)){
        continue;
      }

      if ($key == 'data'){
        $json_data = json_decode($value, true);
        foreach ($json_data as $key => $value){

          if (skip_keys($key)){
            continue;
          }

          /* first row so get the keys for the column header from the json */
          $col_headers .= clean_up_str($key, $file_output) . ',';
          $csv_row .= clean_up_str($value, $file_output) . ',';
        }
      } else {
          $col_headers .= clean_up_str($key, $file_output) . ',';
          $csv_row .= clean_up_str($value, $file_output) . ',';
      }
    }

    /* Remove trailing comma */
    $csv_row = substr ($csv_row, 0, -1);
    $col_headers = substr($col_headers, 0, -1);

    /* If the previous column headers didn't match then output them again
     * this should avoid values appearing under the wrong column header in the
     * case that someone changes the form half way through collecting the data
     */
    if ($col_headers != $col_headers_previous){
      $csv_out .= $col_headers . "\n";
    }

    $col_headers_previous = $col_headers;

    $csv_out .= $csv_row . "\n";
  }

  if ($file_output == true){
    echo $csv_out;
    exit();
  }

  return $csv_out;
}

/* format the text a bit nicer */
function write_kv($pdf, $key, $value){
  if ($value == ''){
    $value = " - ";
  }
  $pdf->SetFont('Arial', '', 12);
  $pdf->Write(5, $key.': ');
  $pdf->SetFont('Arial', 'B', 12);
  $pdf->Write(5, $value);
  $pdf->SetY($pdf->GetY() + 5);
}

function output_as_pdf($res, $event_name){
  require_once('fpdf.php');
  $config = config();

  class PDF extends FPDF {
    function Footer(){
      $config = config();
      // Position at 1.5 cm from bottom
      $this->SetY(-15);
      // Arial italic 8
      $this->SetFont('Arial','I',8);
      // Page number
      $this->Cell(0,10, $config['org_name'].' CONFIDENTIAL - Destroy after use - Page '.$this->PageNo().'/{nb}',0,0,'C');
    }
  }

  $pdf = new PDF('P', 'mm', 'A4');
  $pdf->AliasNbPages();
  $pdf->SetTitle($config['org_name'].' '.$event_name);
  $pdf->SetAutoPageBreak(True, 20);
  $pdf->SetLineWidth(2);
  $pdf->SetDrawColor(255,255,255);
  $pdf->AddPage();

  $pdf->SetFont('Arial','',15);
  $pdf->Write(5, $config['org_name'].": ".$event_name);

  $pdf->SetY($pdf->GetY() + 10);

  $pdf->Line(0, 100, 10, 100);
  $pdf->SetFont('Arial','',12);

  while ($row_arr = mysqli_fetch_assoc($res)) {
    foreach ($row_arr as $key => $value) {

      if ($key == 'data'){
        $json_data = json_decode($value, true);
        foreach ($json_data as $key => $value){

          if (skip_keys($key)){
            continue;
          }
          write_kv($pdf, $key, $value);
        }
      } else {

        write_kv($pdf, $key, $value);
      }
    }

    $pdf->SetY($pdf->GetY() + 10);
  }

  $pdf->Output();
  exit();
}

?>

<?php
/*
Plugin Name: QEventBookings
Plugin URI: http://michaelwood.me.uk
Description: Plugin to manage simple booking forms
Author: Michael Wood
Version: 2.0
Author URI: http://michaelwood.me.uk
*/


include_once ('mustache/mustache.php');


include_once ('config.php');
include_once ('export-data.php');
include_once ('event-settings.php');
include_once ('booking-record.php');
include_once ('booking-form.php');
include_once ('delete-data.php');
include_once ('widget.php');

function llg_db_connection (){
  $config = config ();

  $llg_db_connection = new mysqli($config['host'],
    $config['user'],
    $config['pass'],
    $config['database']);

  if ($llg_db_connection->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    exit ();
  }
  return $llg_db_connection;
}

function llg_db_query($query){
  return mysqli_query(llg_db_connection(), $query);
}

function verify_domain() {
  $config = config ();

  if ($_SERVER['HTTP_HOST'] == $config['domain'])
    return true;

  deactivate_plugins( plugin_basename( __FILE__ ) );
  wp_redirect(admin_url('plugins.php?deactivate=true&plugin_status=all&paged=1'));
  exit();
}

function llg_admin_page(){
  main_page ();
}

function llg_admin_add_event(){
  add_event_page();
}

function llg_admin_forms(){
  forms_page();
}

function llg_register_admin_page(){
  add_menu_page ('Qevent Bookings', 'QEvents', 'manage_options', 'llg_booking_admin', 'llg_admin_page');
  add_submenu_page ('llg_booking_admin', 'Add an event', 'Add an event', 'manage_options', 'llg_new_event', 'llg_admin_add_event');
  add_submenu_page ('llg_booking_admin', 'Forms', 'Forms', 'manage_options', 'llg_forms', 'llg_admin_forms');
}

function llg_form_shortcode_handler ($attr, $content, $tag){
  if ($attr && array_key_exists ('event', $attr))
    $form = booking_form_get_string ($attr['event']);
  else
    $form = booking_form_get_string (null);

  return $form;
}


function llg_next_events_shortcode_handler($attr, $content, $tag){
  $widget = new NextEventWidget();
  $widget->widget();
}

function llg_can_do_this(){
  return (check_admin_referer('llg_event_dash', 'llg_event_dash_csrf') &&
    current_user_can('publish_posts'));
}

function llg_process_post ()
{
  /* N.b These posts requests come from anywhere */
  switch ($_POST['llg_post_action']) {

  case 'save_booking':
    save_booking ();
    break;

  case 'download_data':
    if (llg_can_do_this()) {
      export_data ();
    }
    break;

  case 'update_event':
    if (llg_can_do_this()) {
      update_event ();
    }
    break;

  case 'insert_event':
    if (llg_can_do_this()) {
      insert_event();
    }
    break;

  case 'delete_data':
    if (llg_can_do_this()) {
      delete_data ();
    }
    break;

  case 'toggle_event_status':
    if (llg_can_do_this()) {
      toggle_event_status();
    }
    break;
  }
}

function llg_enqueue_scripts(){
  wp_register_script('llg-custom-script', plugins_url('/js/llg-form-utils.js', __FILE__), array('jquery'));
  wp_register_style('llg-custom-style', plugins_url('/css/llg-style.css', __FILE__));

  wp_enqueue_script('llg-custom-script');
  wp_enqueue_style('llg-custom-style');

  wp_register_script('EasyAutocomplete-script', plugins_url('/js/EasyAutocomplete-1.3.5/jquery.easy-autocomplete.js', __FILE__), array('jquery'));
  wp_register_style('EasyAutocomplete-style', plugins_url('/js/EasyAutocomplete-1.3.5/easy-autocomplete.min.css', __FILE__));

  wp_enqueue_script('EasyAutocomplete-script');
  wp_enqueue_style('EasyAutocomplete-style');
}

function llg_enqueue_admin_scripts(){
  wp_register_script('llg-admin-scripts', plugins_url('/js/llg-admin-page.js', __FILE__), array('jquery'));
  wp_enqueue_script('llg-admin-scripts');
}

function llg_register_widgets(){
  register_widget ('NextEventWidget');
}

function llg_admin_init(){
  verify_domain ();
}

/* Adds [londonlinkbookingform] */
add_shortcode ('qform', 'llg_form_shortcode_handler');
add_shortcode ('qformnextevents', 'llg_next_events_shortcode_handler');

add_action('admin_menu', 'llg_register_admin_page');
add_action('wp_enqueue_scripts', 'llg_enqueue_scripts');
add_action('init', 'llg_process_post');
add_action('admin_enqueue_scripts', 'llg_enqueue_scripts');
add_action('admin_enqueue_scripts', 'llg_enqueue_admin_scripts');
add_action('admin_init', 'llg_admin_init');
add_action('widgets_init', 'llg_register_widgets');

?>

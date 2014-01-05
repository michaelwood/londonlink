<?php
/*
Plugin Name: London Link bookings
Plugin URI: http://michaelwood.me.uk
Description: Plugin to manage simple booking forms
Author: Michael Wood
Version: 1.0
Author URI: http://michaelwood.me.uk
*/


/* The event table
 * $sql = "CREATE TABLE `llg_bookings`.`event` (`id` INT NOT NULL AUTO_INCREMENT, `name` TEXT NOT NULL, `booking_person_name` TEXT NOT NULL, `booking_person_email` TEXT NOT NULL, `event_start_date` TEXT NOT NULL, `event_end_date` TEXT NOT NULL, PRIMARY KEY (`id`)) ENGINE = MyISAM;";
 */


/* TODO merge some of these into logical groups */
include_once ('config.php');
include_once ('export-data.php');
include_once ('event-settings.php');
include_once ('booking-record.php');
include_once ('booking-form.php');
include_once ('delete-data.php');

$llg_db_connection;

function llg_db_connection ()
{
  $llg_db_connection = mysql_connect($host,$user,$pass) or die ('Could not connect: ' . mysql_error ());

  if (!$llg_db_connection)
  {
    echo mysql_error ();
    echo 'bye';
    exit ();
  }

  mysql_select_db ($database);
}

function verify_domain ()
{
  if ($_SERVER['HTTP_HOST'] == $domain)
    return true;

  deactivate_plugins ("londonlink/londonlink.php");
  wp_redirect(admin_url('plugins.php?deactivate=true&plugin_status=all&paged=1'));
  exit();
}

function llg_admin_page ()
{
    main_page ();
}

function llg_admin_subpage ()
{
    sub_page ();
}

function llg_register_admin_page ()
{
  add_menu_page ('London Link Bookings', 'London Link Events', 'manage_options', 'llg_booking_admin', 'llg_admin_page');
  add_submenu_page ('llg_booking_admin', 'Bookings Data', 'Bookings Data', 0, 'llg_booking_data', 'llg_admin_subpage');
}

function llg_form_shortcode_handler ($attr, $content, $tag)
{
  $form = booking_form_get_string ();
  return $form;
}

function llg_process_post ()
{
  /* N.b These posts requests come from anywhere */
  switch ($_POST['llg_post_action']) {

  case 'save_booking':
    save_booking ();
    break;

  case 'download_data':
    if (is_admin()) {
      export_data ();
    }
    break;

  case 'update_event':
    if (is_admin ()) {
      update_event ();
    }

  case 'delete_data':
    if (is_admin ()) {
      delete_data ();
    }
    break;
  }
}

function llg_enqueue_scripts ()
{
  wp_enqueue_script(
    'llg-custom-script',
    '/files/llg-form-checker.js',
    array( 'jquery' ),
    false
  );
}

function llg_admin_init ()
{
  verify_domain ();
}

/* Adds [londonlinkbookingform] */
add_shortcode ('londonlinkbookingform', 'llg_form_shortcode_handler');
add_action ('admin_menu', 'llg_register_admin_page');
add_action ('wp_enqueue_scripts', 'llg_enqueue_scripts');
add_action ('init', 'llg_process_post');
add_action ('admin_enqueue_scripts', 'llg_enqueue_scripts');
add_action ('admin_init', 'llg_admin_init');
?>

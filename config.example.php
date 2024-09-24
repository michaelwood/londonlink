<?php

function config () {
  /* We use a separate database from wordpress */
  $config['user'] = '';
  $config['pass'] = '';
  $config['database'] = '';
  $config['host'] = '';

  /* Our current domain */
  $config['domain'] = '';

  /* Admin email */
  $config['admin_email'] = '';
  /* A single string such as "myorganisationforms" */
  $config['shortcode'] = '';
  $config['from'] = '';

  /* Anti spam answer */
  $config['antispam'] = '';

  /* Optional Mailman password to add people to mailing list */
  $config['mailman'] = '';

  /* File path to a text file containing the additional item to the encryption password */
  $config['saltfile'] = '';

  /* Organisation name */
  $config['org_name'] = '';

  /* Debug switched on or off */
  $config['debug'] = false;

  return $config;
}
?>

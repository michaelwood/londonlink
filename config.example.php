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
  $config['shortcode'] = '';
  $config['from'] = '';

  /* Anti spam answer */
  $config['antispam'] = '';

  /* Mailman password to add people to mailing list */
  $config['mailman'] = '';

  /* Salt file for the encryption password */
  $config['saltfile'] = '';

  return $config;
}
?>

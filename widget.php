<?php

class NextEventWidget extends WP_Widget {

  function __construct() {
    // Instantiate the parent object
    parent::__construct( false, 'London link next event' );
  }

  //function can take $args, $instance
  function widget() {
    llg_db_connection ();

     $sql = "SELECT `name`, `wp_page_id`, `event_start_date`, STR_TO_DATE(event_start_date, '%d/%m/%y') as 'realDate'  FROM `event` WHERE `enabled`=1 ORDER BY `realDate` ASC";
    $result = mysql_query($sql) or die(mysql_error());

     if (mysql_num_rows ($result) == 0){
       echo('<!-- no events currently -->');
       return;
     }

     echo('
       <div class="widget">
         <div class="widget-content">
           <h3 class="widget-title">Upcoming events</h3>
           <ul>');

     while($event_data = mysql_fetch_assoc ($result)){
       $page_link = get_permalink($event_data['wp_page_id']);
       echo('<li><a href="'.$page_link.'">');
       echo($event_data['event_start_date'].' - '.$event_data['name']);
       echo('</a></li>');
     }

     echo('</ul>
         </div>
       </div>');


  }
  function update( $new_instance, $old_instance ) {
  // Save widget options
  }
  function form( $instance ) {
  // Output admin widget options form
  }
}

?>

<?php

class NextEventWidget extends WP_Widget {

  function __construct() {
    // Instantiate the parent object
    parent::__construct( false, 'QForm next event' );
  }

  //function can take $args, $instance
  function widget($args, $instance) {

    $today = date_create("now");

    $db = llg_db_connection();

     $sql = "SELECT `name`, `wp_page_id`, `event_start_date`, STR_TO_DATE(event_start_date, '%d/%m/%y') as 'realDate'  FROM `events` WHERE `enabled`=1 ORDER BY `realDate` ASC";
    $result = mysqli_query($db, $sql) or die(mysqli_error($db));

     if (mysqli_num_rows($result) == 0){
       echo('<!-- no events currently -->');
       return;
     }

     echo('
       <div class="widget llg-upcoming-events-widget">
         <div class="widget-content">
           <h3 class="widget-title">Upcoming events</h3>
           <ul>');

     while($event_data = mysqli_fetch_assoc ($result)){
       $event_date = date_create_from_format('d/m/Y', $event_data['event_start_date']);

       $page_link = get_permalink($event_data['wp_page_id']);
       echo('<li><a href="'.$page_link.'" >');
       echo($event_data['event_start_date'].' - '.$event_data['name']);

       if ($event_date){
         $diff = date_diff($today, $event_date);
         echo (' <span style="font-size: 0.8em">(in just '.$diff->format('%a days!').')</span> ');
       }

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

<?php
defined( 'ABSPATH' ) or die( '' ); // Prevents direct file access
/*
Plugin Name: HCS Functions
Description: Functionality plugin, includes: (1) GCalEvents feed widget
Version:     0.2
Author:      Brett Goodrich
Author URI:  brettgoodrich.com
*/
/*
define( 'HCSPlugins_Path', plugin_dir_path( __FILE__ ) );
$elexiopress_settings = get_option('elexiopress_keys');

include( HCSPlugins_Path . 'elexiopress-admin.php' );
include( HCSPlugins_Path . 'elexiopress-functions.php' );
include( HCSPlugins_Path . 'elexiopress-errorhandling.php' );
*/

// Register and load the widget
function hcsplugins_load_widget() {
	register_widget( 'hcsplugins_widget' );
}
add_action( 'widgets_init', 'hcsplugins_load_widget' );

// Creating the widget
class hcsplugins_widget extends WP_Widget {

  function __construct() {
    $widget_ops = array(
      'description' => __( 'Gets events on a day from Google Calendar specified in settings', 'hcsplugins_widget_domain' )
    );
    parent::__construct(
      'hcsplugins_widget', // Base ID of your widget
      __('HCS - GCal Feed', 'hcsplugins_widget_domain'),// Widget name will appear in UI
      $widget_ops// Widget description
    );
  }

  // Creating widget front-end
  public function widget( $args, $instance ) {
    $offset = intval($instance['offset']);
    // before and after widget arguments are defined by themes
    echo $args['before_widget'];
    // This is where you run the code and display the output
    HCSPlugins_getGCalEvents($offset);
  }

  // Widget Backend
  public function form( $instance ) {
    if ( isset( $instance[ 'offset' ] ) ) {
    $offset = $instance[ 'offset' ];
    }
    else {
    $offset = __( '0', 'hcsplugins_widget_domain' );
    }
    // Widget admin form
    ?>
    <p>
      <label for="<?php echo $this->get_field_id( 'offset' ); ?>"><?php _e( 'Offset:' ); ?></label>
      <select id="<?php echo $this->get_field_id( 'offset' ); ?>" name="<?php echo $this->get_field_name( 'offset' ); ?>">
        <option value="0" <?php if ($offset == 0) echo 'selected';?>>0 Days</option>
        <option value="1" <?php if ($offset == 1) echo 'selected';?>>1 Day</option>
        <option value="2" <?php if ($offset == 2) echo 'selected';?>>2 Days</option>
      </select>
      <?php /** ?><input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /><?php /**/ ?>
    </p>
    <?php
  }

  // Updating widget replacing old instances with new
  public function update( $new_instance, $old_instance ) {
    $instance = array();
    $instance['offset'] = ( ! empty( $new_instance['offset'] ) ) ? strip_tags( $new_instance['offset'] ) : 0;
    return $instance;
  }
} // Class hcsplugins_widget ends here

function HCSPlugins_getGCalEvents($offset = 0) {

  /* Base code provided by Sarah Bailey. From https://spunmonkey.com/display-contents-google-calendar-php/ */
  //TO DEBUG UNCOMMENT THESE LINES
  //error_reporting(E_ALL);
  //ini_set("display_errors", 1);

	//Don't ask me why, but it works without this.
  //include(get_theme_root_uri() . '/divischool/files/google-api-php-client-2.2.0/src/Google/autoload.php');

  //TELL GOOGLE WHAT WE'RE DOING
  $client = new Google_Client();
  $client->setApplicationName("My Calendar");
  $client->setDeveloperKey('AIzaSyCSFSklKTNKfpXlc_6AFUo-e9oezU21nGY');
  $cal = new Google_Service_Calendar($client);
  $calendarId = 'hvk8si8d9j9ql4k4ik6826qnt4@group.calendar.google.com';
  //TELL GOOGLE HOW WE WANT THE EVENTS
  $params = array(
      //CAN'T USE TIME MIN WITHOUT SINGLEEVENTS TURNED ON,
      //IT SAYS TO TREAT RECURRING EVENTS AS SINGLE EVENTS
      'singleEvents' => true,
      'orderBy' => 'startTime',
      'timeMin' => date(DateTime::ATOM, time()-2700), //GETS EVENTS STARTING 45 MINUTES AGO
      'maxResults' => 12
  );
  //THIS IS WHERE WE ACTUALLY PUT THE RESULTS INTO A VAR
  $events = $cal->events->listEvents($calendarId, $params);
  $calTimeZone = $events->timeZone; //GET THE TZ OF THE CALENDAR

  //SET THE DEFAULT TIMEZONE SO PHP DOESN'T COMPLAIN. SET TO YOUR LOCAL TIME ZONE.
  date_default_timezone_set($calTimeZone);

  $eventlist = array();
  foreach ($events->getItems() as $event) {

        //Convert date to month and day
				$isAllDayEvent = false;
        $eventDateStr = $event->start->dateTime;
        if(empty($eventDateStr)) {
					 $isAllDayEvent = true;
           $eventDateStr = $event->start->date; // it's an all day event
        }
        //THIS OVERRIDES THE CALENDAR TIMEZONE IF THE EVENT HAS A SPECIAL TZ
        $temp_timezone = $event->start->timeZone;
        if (!empty($temp_timezone)) {
         $timezone = new DateTimeZone($temp_timezone); //GET THE TIME ZONE
        } else {
         $timezone = new DateTimeZone($calTimeZone); //Set your default timezone in case your events don't have one
        }

				// ATTEMPT TO DISCERN CORRECT EVENT ICON
				$eventicon = 'apple';
				$icontextcheck = $event->description.' '.$event->summary;
				if (
						stripos($icontextcheck, 'volleyball') !== false ||
						stripos($icontextcheck, 'vball') !== false
						) {
  						$eventicon = 'volleyball';
				} elseif (
						stripos($icontextcheck, 'basketball') !== false ||
						stripos($icontextcheck, 'bball') !== false
						) {
							$eventicon = 'basketball';
				} elseif (
						stripos($icontextcheck, 'soccer') !== false
						) {
							$eventicon = 'soccer';
				} elseif (
						stripos($icontextcheck, 'runner') !== false ||
						stripos($icontextcheck, 'xc') !== false ||
						stripos($icontextcheck, 'cross country') !== false ||
						stripos($icontextcheck, 'athletic') !== false
						) {
							$eventicon = 'runner';
				}
				switch ($eventicon) {
					case 'apple':
					$eventicon = 'http://brettgoodrich.com/school/wp-content/uploads/sites/2/2017/07/icon-apple-white.png';
					break;
					case 'volleyball':
					$eventicon = 'http://brettgoodrich.com/school/wp-content/uploads/sites/2/2016/02/icon-volleyball-white.png';
					break;
					case 'basketball':
					$eventicon = 'http://brettgoodrich.com/school/wp-content/uploads/sites/2/2016/02/icon-basketball-white.png';
					break;
					case 'soccer':
					$eventicon = 'http://brettgoodrich.com/school/wp-content/uploads/sites/2/2016/02/icon-soccer-white.png';
					break;
					case 'runner':
					$eventicon = 'http://brettgoodrich.com/school/wp-content/uploads/sites/2/2016/02/icon-runner-white.png';
					break;
				}

				// WRITING VARIOUS VARIABLES
        $eventdate = new DateTime($eventDateStr,$timezone);
        $link = $event->htmlLink;
        $TZlink = $link . "&ctz=" . $calTimeZone; //ADD TZ TO EVENT LINK
        $eventarrayposition = $eventdate->format("Ymj");
				$location = (strpos($event->location, 'google') !== false // If it's a Google Maps link...
						? '<a href="'.$event->location.'" class="hcs-events-table-mapicon buttonstyle notextx" target="_blank" onClick="ga(\'send\', \'event\', \'Button\', \'Click\', \'Map\');"><span>Map</span></a>' // ...add the "Maps" button.
						: $event->location
				);
        $thisevent = array(
          'title' => $event->summary,
          'date' => $eventdate,
					'time' => ($isAllDayEvent ? 'All Day' : $eventdate->format("g").(true/*$eventdate->format("i") != '00'*/ ? $eventdate->format(":i") : '').'<span class="ampm">'.$eventdate->format("a").'</span>'),
					'icon' => $eventicon,
          //'eventlink' => $link . "&ctz=" . $calTimeZone//,
          'eventObject' => $event,
					'location' => $location
        );

        //PUT EVENTS INTO AN ARRAY BY DAY
        $eventlist[$eventarrayposition][] = $thisevent;

        /** ?>
        <div class="event-container">
          <div class="eventDate">
            <span class="month">
              <?php echo $newmonth; ?>
            </span>
            <br />
            <span class="day">
              <?php echo $newday; ?>
            </span>
            <span class="dayTrail"></span>
          </div>
          <div class="eventBody">
              <a href="<?php echo $TZlink; //ECHO DIRECT LINK TO EVENT
              ?>">
                <?php echo $event->summary; //SUMMARY = TITLE
                ?>
              </a>
          </div>
        </div>
    <?php /**/
    } // END FOREACH EVENT
    $eventlist = array_values($eventlist); //Resets keys to start at 0
    $daylist = $eventlist[$offset]; //Gets just the events from the day we want
    ?>
    <div class="et_pb_text et_pb_module et_pb_bg_layout_light et_pb_text_align_left et_pb_text_0" style="padding-right:0 !important;">
      <div class="et_pb_text_inner">
				<?php /** ?>

          <span class="lil-label"><?php echo $daylist[0]['date']->format('l, M j'); ?></span>
					<table class="hcs-events-table"><tbody><?php
          foreach ($daylist as $singleevent) {
            ?>
							<tr>
								<td class="hcs-events-table-icon"><img src="<?php echo $singleevent['icon'];?>" alt=""/></td>
								<td><?php echo $singleevent['title'];?></td>
								<td class="hcs-events-table-time"><?php echo $singleevent['time'];?></td>
            	</tr><?php
          }
          ?>
        </tbody></table>
				<?php /**/ ?>

          <span class="lil-label"><?php echo $daylist[0]['date']->format('l, M j'); ?></span>
					<table class="hcs-events-table"><tbody><?php
          foreach ($daylist as $singleevent) {
            ?>
							<tr>
								<td class="hcs-events-table-icon"><img src="<?php echo $singleevent['icon'];?>" alt=""/></td>
								<td><?php echo $singleevent['title'];?><br/><span class="hcs-events-table-time"><?php echo $singleevent['time'].($singleevent['location'] ? '<span class="separator">&bull;</span>'.$singleevent['location'] : '');?></span></td>
								<?php /** echo '<!--'; print_r($singleevent['eventObject']); echo '-->'; /**/ ?>
            	</tr><?php
          }
          ?>
        </tbody></table>
				<?php /**/ ?>
      </div>
    </div>
    <style type="text/css">
    .home-servicetimesbar {
        color: white;
        font-size: 1.2em;
        font-family: Montserrat;
        font-weight: 500;
    }
    .et_pb_widget_area {
          padding: 0 25px;
    }
    </style>
  </div> <?php //I have no idea why this </div> is necessary, but it definitely is ?>
    <?php
} // END function HCSPlugins_getGCalEvents()

?>

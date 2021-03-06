<?php
/**
 * Event Calendar Base
 *
 * @package    BE-Events-Calendar
 * @since      1.0.0
 * @link       https://github.com/billerickson/BE-Events-Calendar
 * @author     Bill Erickson <bill@billerickson.net>
 * @copyright  Copyright (c) 2014, Bill Erickson
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

class BE_Events_Calendar {

	/**
	 * Primary class constructor
	 *
	 * @since 1.0.0
	 */
	function __construct() {

		// Fire on activation
		register_activation_hook( BE_EVENTS_CALENDAR_FILE, array( $this, 'activation' ) );

		// Load the plugin base
		add_action( 'plugins_loaded', array( $this, 'init' ) );

		// Load Text Domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
	}

	/**
	 * Flush the WordPress permalink rewrite rules on activation
	 *
	 * @since 1.0.0
	 */
	function activation() {

		$this->post_type();
		flush_rewrite_rules();
	}

	/**
	 * Loads the plugin base into WordPress
	 *
	 * @since 1.0.0
	 */
	function init() {

		// Create Post Type
		add_action( 'init', array( $this, 'post_type' ) );

		// Post Type columns
		add_filter( 'manage_edit-event_columns', array( $this, 'edit_event_columns' ) );
		add_action( 'manage_event_posts_custom_column', array( $this, 'manage_event_columns' ), 10, 2 );

		// Post Type sorting
		add_filter( 'manage_edit-event_sortable_columns', array( $this, 'event_sortable_columns' ) );
		add_action( 'load-edit.php', array( $this, 'edit_event_load' ) );

		// Post Type title placeholder
		add_filter( 'enter_title_here', array( $this, 'title_placeholder' ) );

		// Create Taxonomy
		add_action( 'init', array( $this, 'taxonomies' ) );

		// Add term fields
		add_action( 'event_location_add_form_fields', array( $this, 'event_location_add_address_field' ) );
		add_action( 'event_location_add_form_fields', array( $this, 'event_location_add_website_field' ) );
		add_action( 'event_location_add_form_fields', array( $this, 'event_location_add_map_field' ) );
		add_action( 'event_location_edit_form_fields', array( $this, 'event_location_edit_address_field' ) );
		add_action( 'event_location_edit_form_fields', array( $this, 'event_location_edit_website_field' ) );
		add_action( 'event_location_edit_form_fields', array( $this, 'event_location_edit_map_field' ) );
		add_action( 'edit_event_location', array( $this, 'event_location_save_address_field' ) );
		add_action( 'edit_event_location', array( $this, 'event_location_save_website_field' ) );
		add_action( 'edit_event_location', array( $this, 'event_location_save_map_field' ) );
		add_action( 'create_event_location', array( $this, 'event_location_save_address_field' ) );
		add_action( 'create_event_location', array( $this, 'event_location_save_website_field' ) );
		add_action( 'create_event_location', array( $this, 'event_location_save_map_field' ) );

		// Create Metabox
		$metabox = apply_filters( 'be_events_manager_metabox_override', false );
		if ( false === $metabox ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'metabox_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'metabox_scripts' ) );
			add_action( 'add_meta_boxes', array( $this, 'metabox_register' ) );
			add_action( 'save_post', array( $this, 'metabox_save' ), 1, 2 );
		}

		// Modify Event Listings query
		add_action( 'pre_get_posts', array( $this, 'event_query' ) );
	}

	/**
	 * Register Post Type
	 *
	 * @since 1.0.0
	 */
	function post_type() {

		$labels = array(
			'name'                     => _x( 'Events', 'post type general name' , 'be-events-calendar' ),
			'singular_name'            => _x( 'Event', 'post type singular name', 'be-events-calendar' ),
			'add_new'                  => __( 'Add New', 'be-events-calendar' ),
			'add_new_item'             => __( 'Add New Event', 'be-events-calendar' ),
			'edit_item'                => __( 'Edit Event', 'be-events-calendar' ),
			'new_item'                 => __( 'New Event', 'be-events-calendar' ),
			'view_item'                => __( 'View Event', 'be-events-calendar' ),
			'view_items'               => __( 'View Events', 'be-events-calendar' ),
			'search_items'             => __( 'Search Events', 'be-events-calendar' ),
			'not_found'                => __( 'No events found', 'be-events-calendar' ),
			'not_found_in_trash'       => __( 'No events found in trash', 'be-events-calendar' ),
			'parent_item_colon'        => __( 'Parent Event:', 'be-events-calendar' ),
			'all_items'                => __( 'All Events', 'be-events-calendar' ),
			'archives'                 => __( 'Event Archives', 'be-events-calendar' ),
			'attributes'               => __( 'Event Attributes', 'be-events-calendar' ),
			'menu_name'                => _x( 'Events', 'admin menu', 'be-events-calendar' ),
			'filter_items_list'        => __( 'Filter posts list', 'be-events-calendar' ),
			'items_list_navigation'    => __( 'Events list navigation', 'be-events-calendar' ),
			'items_list'               => __( 'Events list', 'be-events-calendar' ),
			'item_published'           => __( 'Event published.', 'be-events-calendar' ),
			'item_published_privately' => __( 'Event published privately.', 'be-events-calendar' ),
			'item_reverted_to_draft'   => __( 'Event reverted to draft.', 'be-events-calendar' ),
			'item_scheduled'           => __( 'Event scheduled.', 'be-events-calendar' ),
			'item_updated'             => __( 'Event updated.', 'be-events-calendar' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'events', 'with_front' => false ),
			'capability_type'    => 'post',
			'taxonomies'         => self::get_theme_supported_taxonomies(),
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'editor', 'thumbnail' ),
			'menu_icon'          => 'dashicons-calendar',
		);

		$args = apply_filters( 'be_events_manager_post_type_args', $args );
		register_post_type( 'event', $args );
	}

	/**
	 * Edit Column Titles
	 *
	 * @since 1.0.0
	 *
	 * @link http://justintadlock.com/archives/2011/06/27/custom-columns-for-custom-post-types
	 *
	 * @param array $columns
	 * @return array
	 */
	function edit_event_columns( $columns ) {

		// Change Titles
		$columns['title'] = esc_html__( 'Event', 'be-events-calendar' );
		$columns['date']  = esc_html__( 'Published Date', 'be-events-calendar' );

		// New Columns
		$new_columns = array(
			'event_start' => esc_html__( 'Starts', 'be-events-calendar' ),
			'event_end'   => esc_html__( 'Ends', 'be-events-calendar' ),
		);

		// Add new columns after title column
		$column_end   = array_splice( $columns, 2 );
		$column_start = array_splice( $columns, 0, 2 );
		$columns      = array_merge( $column_start, $new_columns, $column_end );

		return $columns;
	}

	/**
	 * Edit Column Content
	 *
	 * @since 1.0.0
	 *
	 * @link http://justintadlock.com/archives/2011/06/27/custom-columns-for-custom-post-types
	 *
	 * @param string $column
	 * @param int $post_id
	 */
	function manage_event_columns( $column, $post_id ) {

		$set_dmy_format = apply_filters( 'be_event_set_dmy_format', false );
		$date_format    = apply_filters( 'be_event_columns_date_format', $set_dmy_format ? 'j M Y' : 'M j, Y' );

		$set_24_hour_clock = apply_filters( 'be_event_set_24_hour_clock', false );
		$date_time_format  = apply_filters( 'be_event_columns_time_format', $set_24_hour_clock ? $date_format . ' (G:i)' : $date_format . ' (g:i A)' );

		switch ( $column ) {

			/* If displaying the 'Starts' column. */
			case 'event_start' :

				/* Get the post meta. */
				$allday      = get_post_meta( $post_id, 'be_event_allday', true );
				$date_format = $allday ? $date_format : $date_time_format;
				$start       = get_post_meta( $post_id, 'be_event_start', true );

				/* If no duration is found, output a default message. */
				if ( empty( $start ) ) {
					esc_html_e( 'Unknown', 'be-events-calendar' );
				} /* If there is a duration, append 'minutes' to the text string. */
				else {
					echo esc_attr( date( $date_format, $start ) );
				}

				break;

			/* If displaying the 'Ends' column. */
			case 'event_end' :

				/* Get the post meta. */
				$allday      = get_post_meta( $post_id, 'be_event_allday', true );
				$date_format = $allday ? $date_format : $date_time_format;
				$end         = get_post_meta( $post_id, 'be_event_end', true );

				/* If no duration is found, output a default message. */
				if ( empty( $end ) ) {
					esc_html_e( 'Unknown', 'be-events-calendar' );
				} /* If there is a duration, append 'minutes' to the text string. */
				else {
					echo esc_attr( date( $date_format, $end ) );
				}

				break;

			/* Just break out of the switch statement for everything else. */
			default :
				break;
		}
	}

	/**
	 * Make Columns Sortable
	 *
	 * @since 1.0.
	 *
	 * @link http://justintadlock.com/archives/2011/06/27/custom-columns-for-custom-post-types
	 *
	 * @param array $columns
	 * @return array
	 */
	function event_sortable_columns( $columns ) {

		$columns['event_start'] = 'event_start';
		$columns['event_end']   = 'event_end';

		return $columns;
	}

	/**
	 * Check for load request
	 *
	 * @since 1.0.0
	 */
	function edit_event_load() {

		add_filter( 'request', array( $this, 'sort_events' ) );
	}

	/**
	 * Sort events on load request
	 *
	 * @since 1.0.0
	 * @param array $vars
	 * @return array
	 */
	function sort_events( $vars ) {

		// Check if we're viewing the 'event' post type.
		if ( isset( $vars['post_type'] ) && 'event' == $vars['post_type'] ) {

			// Check if 'orderby' is set to 'start_date'.
			if ( isset( $vars['orderby'] ) && 'event_start' == $vars['orderby'] ) {

				// Merge the query vars with our custom variables.
				$vars = array_merge(
					$vars,
					array(
						'meta_key' => 'be_event_start',
						'orderby'  => 'meta_value_num',
					)
				);
			}

			// Check if 'orderby' is set to 'end_date'.
			if ( isset( $vars['orderby'] ) && 'event_end' == $vars['orderby'] ) {

				// Merge the query vars with our custom variables.
				$vars = array_merge(
					$vars,
					array(
						'meta_key' => 'be_event_end',
						'orderby'  => 'meta_value_num',
					)
				);
			}

		}

		return $vars;
	}

	/**
	 * Change the default title placeholder text
	 *
	 * @since 1.0.0
	 *
	 * @param string $title
	 * @return string Customized translation for title
	 */
	function title_placeholder( $title ) {

		$screen = get_current_screen();

		if ( 'event' === $screen->post_type ) {
			$title = __( 'Enter Event Name Here', 'be-events-calendar' );
		}

		return $title;
	}

	/**
	 * Create Taxonomies
	 *
	 * @since 1.0.0
	 */
	function taxonomies() {

		$supports = get_theme_support( 'be-events-calendar' );
		if ( ! is_array( $supports ) ) {
			return;
		}

		$post_types = in_array( 'recurring-events', $supports[0] ) ? array( 'event', 'recurring_event', ) : array( 'event' );
		$taxonomies = self::get_theme_supported_taxonomies();

		foreach ( $taxonomies as $taxonomy ) {
			$method_name = 'register_' . $taxonomy;

			if ( method_exists( $this, $method_name ) ) {
				call_user_func( array( $this, $method_name ), $post_types );
			}

			if ( 'event_location' === $taxonomy ) {
				$this->register_event_location_meta();
			}
		}
	}

	/**
	 * Return the theme supported taxonomies
	 *
	 * @return array
	 */
	static function get_theme_supported_taxonomies() {
		$taxonomies = array();
		$supports   = get_theme_support( 'be-events-calendar' );

		if ( ! is_array( $supports ) ) {
			return $taxonomies;
		}

		if ( in_array( 'event-location', $supports[0] ) ) {
			$taxonomies[] = 'event_location';
		}

		if ( in_array( 'event-category', $supports[0] ) ) {
			$taxonomies[] = 'event_category';
		}

		return $taxonomies;
	}

	/**
	 * Register an `event_category` taxonomy
	 *
	 * @since 1.5.0
	 *
	 * @param $post_types
	 */
	function register_event_category( $post_types ) {
		$labels = array(
			'name'              => __( 'Categories', 'be-events-calendar' ),
			'singular_name'     => __( 'Category', 'be-events-calendar' ),
			'search_items'      => __( 'Search Categories', 'be-events-calendar' ),
			'all_items'         => __( 'All Categories', 'be-events-calendar' ),
			'parent_item'       => __( 'Parent Category', 'be-events-calendar' ),
			'parent_item_colon' => __( 'Parent Category:', 'be-events-calendar' ),
			'edit_item'         => __( 'Edit Category', 'be-events-calendar' ),
			'update_item'       => __( 'Update Category', 'be-events-calendar' ),
			'add_new_item'      => __( 'Add New Category', 'be-events-calendar' ),
			'new_item_name'     => __( 'New Category Name', 'be-events-calendar' ),
			'menu_name'         => __( 'Categories', 'be-events-calendar' ),
		);

		register_taxonomy( 'event_category', $post_types, array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'event-category', 'with_front' => false ),
		) );
	}

	/**
	 * Register an `event_location` taxonomy
	 *
	 * @since 1.5.0
	 *
	 * @param $post_types
	 */
	function register_event_location( $post_types ) {
		$labels = array(
			'name'                       => __( 'Locations', 'be-events-calendar' ),
			'singular_name'              => __( 'Location', 'be-events-calendar' ),
			'search_items'               => __( 'Search Locations', 'be-events-calendar' ),
			'popular_items'              => __( 'Popular Locations', 'be-events-calendar' ),
			'all_items'                  => __( 'All Locations', 'be-events-calendar' ),
			'edit_item'                  => __( 'Edit Location', 'be-events-calendar' ),
			'view_item'                  => __( 'View Location', 'be-events-calendar' ),
			'update_item'                => __( 'Update Location', 'be-events-calendar' ),
			'add_new_item'               => __( 'Add New Location', 'be-events-calendar' ),
			'new_item_name'              => __( 'New Location Name', 'be-events-calendar' ),
			'separate_items_with_commas' => __( 'Separate locations with commas', 'be-events-calendar' ),
			'add_or_remove_items'        => __( 'Add or remove locations', 'be-events-calendar' ),
			'choose_from_most_used'      => __( 'Choose from the most used locations', 'be-events-calendar' ),
			'no_terms'                   => __( 'No locations', 'be-events-calendar' ),
			'menu_name'                  => __( 'Locations', 'be-events-calendar' ),
			'back_to_items'              => __( 'Back to Locations', 'be-events-calendar' ),
		);

		register_taxonomy( 'event_location', $post_types, array(
			'hierarchical'      => false,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'event-location' ),
		) );
	}

	/**
	 * Loads styles for metaboxes
	 *
	 * @since 1.0.0
	 */
	function metabox_styles() {

		if ( isset( get_current_screen()->base ) && 'post' !== get_current_screen()->base ) {
			return;
		}

		if ( isset( get_current_screen()->post_type ) && 'event' != get_current_screen()->post_type ) {
			return;
		}

		// Load styles
		wp_register_style( 'be-events-calendar', BE_EVENTS_CALENDAR_URL . 'css/events-admin.css', array(), BE_EVENTS_CALENDAR_VERSION );
		wp_enqueue_style( 'be-events-calendar' );
	}

	/**
	 * Loads scripts for metaboxes.
	 *
	 * @since 1.0.0
	 */
	function metabox_scripts() {

		if ( isset( get_current_screen()->base ) && 'post' !== get_current_screen()->base ) {
			return;
		}

		if ( isset( get_current_screen()->post_type ) && 'event' != get_current_screen()->post_type ) {
			return;
		}

		// Load scripts.
		wp_register_script( 'be-events-calendar', BE_EVENTS_CALENDAR_URL . 'js/events-admin.js', array(
			'jquery',
			'jquery-ui-core',
			'jquery-ui-datepicker',
		), BE_EVENTS_CALENDAR_VERSION, true );
		wp_enqueue_script( 'be-events-calendar' );

		$l18n_data['dateFormat'] = apply_filters( 'be_event_set_dmy_format', false ) ? 'dd/mm/yy' : 'mm/dd/yy';

		wp_localize_script( 'be-events-calendar', 'beEventsCalendar', $l18n_data );
	}

	/**
 * Initialize the metabox
 *
 * @since 1.0.0
 */
	function metabox_register() {

		add_meta_box( 'be-events-calendar-date-time', esc_html__( 'Date and Time Details', 'be-events-calendar' ), array( $this, 'render_metabox' ), 'event', 'normal', 'high' );
	}

	/**
	 * Render the metabox
	 *
	 * @since 1.0.0
	 */
	function render_metabox() {

		$set_dmy_format = apply_filters( 'be_event_set_dmy_format', false );
		$date_format    = $set_dmy_format ? 'd/m/Y' : 'm/d/Y';

		$set_24_hour_clock = apply_filters( 'be_event_set_24_hour_clock', false );
		$time_format       = $set_24_hour_clock ? 'G:i' : 'g:ia';

		$start  = get_post_meta( get_the_ID(), 'be_event_start', true );
		$end    = get_post_meta( get_the_ID(), 'be_event_end', true );
		$allday = get_post_meta( get_the_ID(), 'be_event_allday', true );

		if ( ! empty( $start ) && ! empty( $end ) ) {
			$start_date = date( $date_format, $start );
			$start_time = date( $time_format, $start );
			$end_date   = date( $date_format, $end );
			$end_time   = date( $time_format, $end );
		}

		wp_nonce_field( 'be_events_calendar_date_time', 'be_events_calendar_date_time_nonce' );
		?>

		<div class="section" style="min-height:0;">
			<label for="be-events-calendar-allday"><?php esc_html_e( 'All Day event?', 'be-events-calendar' ); ?></label>
			<input name="be-events-calendar-allday" type="checkbox" id="be-events-calendar-allday"
			       value="1" <?php checked( '1', $allday ); ?>>
		</div>
		<div class="section">
			<label for="be-events-calendar-start"><?php esc_html_e( 'Start date and time:', 'be-events-calendar' ); ?></label>
			<input name="be-events-calendar-start" type="text"  id="be-events-calendar-start" class="be-events-calendar-date" value="<?php echo !empty( $start_date ) ? $start_date : ''; ?>" placeholder="<?php esc_html_e( 'Date', 'be-events-calendar' ); ?>">
			<input name="be-events-calendar-start-time" type="text" id="be-events-calendar-start-time"
			       class="be-events-calendar-time" value="<?php echo ! empty( $start_time ) ? $start_time : ''; ?>"
			       placeholder="<?php esc_html_e( 'Time', 'be-events-calendar' ); ?>">
		</div>
		<div class="section">
			<label for="be-events-calendar-end"><?php esc_html_e( 'End date and time:', 'be-events-calendar' ); ?></label>
			<input name="be-events-calendar-end" type="text"  id="be-events-calendar-end" class="be-events-calendar-date" value="<?php echo !empty( $end_date ) ? $end_date : ''; ?>" placeholder="<?php esc_html_e( 'Date', 'be-events-calendar' ); ?>">
			<input name="be-events-calendar-end-time" type="text" id="be-events-calendar-end-time"
			       class="be-events-calendar-time" value="<?php echo ! empty( $end_time ) ? $end_time : ''; ?>"
			       placeholder="<?php esc_html_e( 'Time', 'be-events-calendar' ); ?>">
		</div>
		<p class="desc">
			<?php printf( esc_html__( 'Date format should be %s.', 'be-events-calendar'), '<strong>' . ( $set_dmy_format ? 'DD/MM/YYYY' : 'MM/DD/YYYY' ) . '</strong>' ); ?>
			<?php printf( esc_html__( 'Time format should be %s.', 'be-events-calendar' ), '<strong>' . ( $set_24_hour_clock ? 'H:MM' : 'H:MM' ) . '</strong>' ); ?>
			<br><?php printf( esc_html__( 'Example: %s.', 'be-events-calendar' ), ( $set_dmy_format ? '21/05/2015' : '05/21/2015' ) . ' ' . ( $set_24_hour_clock ? '18:00' : '6:00pm' ) ); ?>
		</p>
		<?php
	}

	/**
	 * Save metabox contents
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id
	 * @param array $post
	 */
	function metabox_save( $post_id, $post ) {

		// Security check
		if ( ! isset( $_POST['be_events_calendar_date_time_nonce'] ) || ! wp_verify_nonce( $_POST['be_events_calendar_date_time_nonce'], 'be_events_calendar_date_time' ) ) {
			return;
		}

		// Bail out if running an autosave, ajax, cron, or revision.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Bail out if the user doesn't have the correct permissions to update the slider.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Make sure the event start/end dates were not left blank before we run the save
		if ( isset( $_POST['be-events-calendar-start'] ) && isset( $_POST['be-events-calendar-end'] ) && ! empty( $_POST['be-events-calendar-start'] ) && ! empty( $_POST['be-events-calendar-end'] ) ) {
			$set_dmy_format    = apply_filters( 'be_event_set_dmy_format', false );

			$start      = $_POST['be-events-calendar-start'] . ' ' . $_POST['be-events-calendar-start-time'];
			$start_unix = strtotime( $set_dmy_format ? str_replace('/', '-', $start ) : $start );
			$end        = $_POST['be-events-calendar-end'] . ' ' . $_POST['be-events-calendar-end-time'];
			$end_unix   = strtotime( $set_dmy_format ? str_replace('/', '-', $end ) : $end );
			$allday     = ( isset( $_POST['be-events-calendar-allday'] ) ? '1' : '0' );

			update_post_meta( $post_id, 'be_event_start', $start_unix );
			update_post_meta( $post_id, 'be_event_end', $end_unix );
			update_post_meta( $post_id, 'be_event_allday', $allday );
		}
	}

	/**
	 * Register metas for locations
	 */
	function register_event_location_meta() {
		register_meta( 'term', 'address', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_textarea_field',
		) );

		register_meta( 'term', 'website', array(
			'type'              => 'string',
			'sanitize_callback' => 'esc_url',
		) );

		register_meta( 'term', 'map', array(
			'type'              => 'string',
			'sanitize_callback' => 'esc_url',
		) );
	}

	/**
	 * Display an address field when adding an event location
	 */
	function event_location_add_address_field() {
		wp_nonce_field( basename( __FILE__ ), 'event_location_address_nonce' );
		?>
		<div class="form-field">
			<label for="be-event-location-address"><?php esc_html_e( 'Address', 'be-events-calendar' ); ?></label>
			<textarea name="be_event_location_address" class="be-event-location-address" id="be-event-location-address" rows="4"></textarea>
		</div>
		<?php
	}

	/**
	 * Display an address field when editing an event location
	 *
	 * @param $term
	 */
	function event_location_edit_address_field( $term ) {
		$address = get_term_meta( $term->term_id, 'address', true );
		?>
		<tr class="form-field be-event-location-address-wrap">
			<th scope="row"><label for="be-event-location-address"><?php esc_html_e( 'Address', 'be-events-calendar' ); ?></label></th>
			<td>
				<?php wp_nonce_field( basename( __FILE__ ), 'event_location_address_nonce' ); ?>
				<textarea name="be_event_location_address" class="be-event-location-address" id="be-event-location-address" rows="4"><?php echo esc_html( $address ); ?></textarea>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save the address field
	 *
	 * @param $term_id
	 */
	function event_location_save_address_field( $term_id ) {
		if ( ! isset( $_POST['event_location_address_nonce'] ) || ! wp_verify_nonce( $_POST['event_location_address_nonce'], basename( __FILE__ ) ) ) {
			return;
		}

		$old_value = get_term_meta( $term_id, 'address', true );
		$new_value = isset( $_POST['be_event_location_address'] ) ? sanitize_textarea_field( $_POST['be_event_location_address'] ) : '';

		if ( $old_value && '' === $new_value ) {
			delete_term_meta( $term_id, 'address' );
		} else if ( $old_value !== $new_value ) {
			update_term_meta( $term_id, 'address', $new_value );
		}
	}

	/**
	 * Display a website field when adding an event location
	 */
	function event_location_add_website_field() {
		wp_nonce_field( basename( __FILE__ ), 'event_location_website_nonce' );
		?>
		<div class="form-field">
			<label for="be-event-location-website"><?php esc_html_e( 'Website', 'be-events-calendar' ); ?></label>
			<input type="url" name="be_event_location_website" class="be-event-location-website" id="be-event-location-website" value="" />
		</div>
		<?php
	}

	/**
	 * Display a website field when editing an event location
	 *
	 * @param $term
	 */
	function event_location_edit_website_field( $term ) {
		$url = get_term_meta( $term->term_id, 'website', true );
		?>
		<tr class="form-field be-event-location-website-wrap">
			<th scope="row"><label for="be-event-location-website"><?php esc_html_e( 'Website', 'be-events-calendar' ); ?></label></th>
			<td>
				<?php wp_nonce_field( basename( __FILE__ ), 'event_location_website_nonce' ); ?>
				<input type="url" name="be_event_location_website" class="be-event-location-website" id="be-event-location-website" value="<?php echo esc_attr( $url ); ?>" />
			</td>
		</tr>
		<?php
	}

	/**
	 * Save the website field
	 *
	 * @param $term_id
	 */
	function event_location_save_website_field( $term_id ) {
		if ( ! isset( $_POST['event_location_website_nonce'] ) || ! wp_verify_nonce( $_POST['event_location_website_nonce'], basename( __FILE__ ) ) ) {
			return;
		}

		$old_value = get_term_meta( $term_id, 'website', true );
		$new_value = isset( $_POST['be_event_location_website'] ) ? sanitize_text_field( $_POST['be_event_location_website'] ) : '';

		if ( $old_value && '' === $new_value ) {
			delete_term_meta( $term_id, 'website' );
		} else if ( $old_value !== $new_value ) {
			update_term_meta( $term_id, 'website', $new_value );
		}
	}

	/**
	 * Display a map field when adding an event location
	 */
	function event_location_add_map_field() {
		wp_nonce_field( basename( __FILE__ ), 'event_location_map_nonce' );
		?>
		<div class="form-field">
			<label for="be-event-location-map"><?php esc_html_e( 'Map', 'be-events-calendar' ); ?></label>
			<input type="text" name="be_event_location_map" class="be-event-location-map" id="be-event-location-map" value="" />
		</div>
		<?php
	}

	/**
	 * Display a map field when editing an event location
	 *
	 * @param $term
	 */
	function event_location_edit_map_field( $term ) {
		$url = get_term_meta( $term->term_id, 'map', true );
		?>
		<tr class="form-field be-event-location-map-wrap">
			<th scope="row"><label for="be-event-location-map"><?php esc_html_e( 'Map', 'be-events-calendar' ); ?></label></th>
			<td>
				<?php wp_nonce_field( basename( __FILE__ ), 'event_location_map_nonce' ); ?>
				<input type="url" name="be_event_location_map" class="be-event-location-map" id="be-event-location-map" value="<?php echo esc_attr( $url ); ?>" />
			</td>
		</tr>
		<?php
	}

	/**
	 * Save the map field
	 *
	 * @param $term_id
	 */
	function event_location_save_map_field( $term_id ) {
		if ( ! isset( $_POST['event_location_map_nonce'] ) || ! wp_verify_nonce( $_POST['event_location_map_nonce'], basename( __FILE__ ) ) ) {
			return;
		}

		$old_value = get_term_meta( $term_id, 'map', true );
		$new_value = isset( $_POST['be_event_location_map'] ) ? sanitize_text_field( $_POST['be_event_location_map'] ) : '';

		if ( $old_value && '' === $new_value ) {
			delete_term_meta( $term_id, 'map' );
		} else if ( $old_value !== $new_value ) {
			update_term_meta( $term_id, 'map', $new_value );
		}
	}

	/**
	 * Modify WordPress query where needed for event listings
	 *
	 * @since 1.0.0
	 *
	 * @param object $query
	 */
	function event_query( $query ) {

		// If you don't want the plugin to mess with the query, use this filter to override it
		$override = apply_filters( 'be_events_manager_query_override', false );
		if ( $override ) {
			return;
		}

		if ( $query->is_main_query() && ! is_admin() && ( is_post_type_archive( 'event' ) || is_tax( 'event_category' ) ) ) {
			$meta_query = array(
				array(
					'key'     => 'be_event_end',
					'value'   => (int) current_time( 'timestamp' ),
					'compare' => '>',
				),
			);
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'order', 'ASC' );
			$query->set( 'meta_query', $meta_query );
			$query->set( 'meta_key', 'be_event_start' );
		}
	}

	/**
	 * Load Text Domain
	 *
	 * @since 1.4.0
	 */
	function load_plugin_textdomain() {
		load_plugin_textdomain( 'be-events-calendar', FALSE, basename( plugin_basename( BE_EVENTS_CALENDAR_DIR ) ) . '/languages/' );
	}

}

new BE_Events_Calendar;

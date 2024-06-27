<?php
/*
Description: Events Filter Shortcode
Author: Ryner S. Galaus

*/

if ( !defined( 'ABSPATH' ) ) exit;


// Register the shortcode
add_shortcode('event_filters', function() {
	ob_start();

    $event_types = get_terms(array( 'taxonomy' => 'category', 'hide_empty' => false ));
	$all_events = new WP_Query(array( 'post_type' => 'events', 'posts_per_page' => -1));
	$dates = [];
	if ($all_events->have_posts()) {
		while ($all_events->have_posts()) {
			$all_events->the_post();
			$post_id = get_the_ID();
			$date_field = get_field('event_date', $post_id);
			array_push($dates, $date_field);
		}
		wp_reset_postdata();
	}	
	
	// Convert dates to months
    $months = [];
    foreach ($dates as $date) {
        $month = date('F Y', strtotime($date));
        if (!in_array($month, $months)) { $months[] = $month; }
    }
	
	?>
	<form id="event-filters">
        <select name="event_type" id="event_type">
            <option value=""><?php _e('By Event Type', 'textdomain'); ?></option>
            <?php foreach ($event_types as $event_type) : ?>
                <option value="<?php echo esc_attr($event_type->slug); ?>"><?php echo esc_html($event_type->name); ?></option>
            <?php endforeach; ?>
        </select>

        <select name="event_month" id="event_month">
            <option value=""><?php _e('By Month', 'textdomain'); ?></option>
            <?php foreach ($months as $month) : ?>
                <option value="<?php echo esc_attr($month); ?>"><?php echo esc_html($month); ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <div id="event-results"></div>
	
 	<script>
    (function($) {
        $('#event-filters').on('change', function() {
            var eventType = $('#event_type').val();
            var eventMonth = $('#event_month').val();

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'filter_events',
                    event_type: eventType,
                    event_month: eventMonth
                },
                success: function(response) {
                    $('#event-results').html(response);
                }
            });
        });
    })(jQuery);
    </script>

	<?php 
	return ob_get_clean();
});


// Handle the AJAX request
add_action('wp_ajax_filter_events', 'filter_events');
add_action('wp_ajax_nopriv_filter_events', 'filter_events');

function filter_events() {
    
	$event_type = $_POST['event_type'];
    $event_month = $_POST['event_month'];
	$args = array('post_type' => 'event', 'posts_per_page' => -1 );

    if ($event_type) {
		$args['tax_query'] = array( array( 'taxonomy' => 'category', 'field' => 'slug', 'terms' => $event_type, ), );
    }

    if ($event_month) {
        $args['meta_query'] = array(
            'relation' => 'AND',
            array(
                'key' => 'field_6674f79616139',
                'value' => $event_month,
                'compare' => 'LIKE',
            ),
        );
    }

    $events = new WP_Query($args);

    if ($events->have_posts()) :
        while ($events->have_posts()) : $events->the_post();
            ?>
            <div class="event">
                <h2><?php the_title(); ?></h2>
                <p><?php the_content(); ?></p>
            </div>
            <?php
        endwhile;
        wp_reset_postdata();
    else :
        echo '<p>No events found</p>';
    endif;

    die();
}
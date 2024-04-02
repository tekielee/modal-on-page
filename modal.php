<?php
/** Plugin Name: Modal On Page
** Author: Cuong Le
** Version: 1.0
** Description: Create a modal custom post type where authors can add content. Authors will associate the modal to a specific URL. The Modal will show the first time an user land in the page and won’t show again after assertive action by user. If the user declines the Modal, it will redirect to blackstone.com home page.
*/

register_activation_hook( __FILE__, 'was_visited_setup_table' );

function was_visited_setup_table() {

    global $wpdb;

    $table_name = $wpdb->prefix . 'was_visited';

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        ip varchar(100) NOT NULL,
        browser_version mediumtext NOT NULL,
        post_id bigint(20) NOT NULL,
        PRIMARY KEY  (id)
    )";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    dbDelta( $sql );

}

add_action('init', 'modal_custom_post_type');

if(!function_exists('modal_custom_post_type')) {

    function modal_custom_post_type() {

        register_post_type(

            'modal',

            array(

                'labels'      => array(

                    'name'          => __('Modal Posts', 'author-modal'),

                    'singular_name' => __('modal', 'author-modal'),

                ),

                'public'      => true,

                'has_archive' => true,

                'supports'    => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),  

                'show_in_rest' => true,

            )

        );

    }

}

add_action('wp_enqueue_scripts', 'modal_enqueue_scripts');

function modal_enqueue_scripts() {

    wp_enqueue_script('modal', plugin_dir_url(__FILE__) . 'modal.js', array('jquery'), null, true);

    wp_enqueue_style('modal', plugin_dir_url(__FILE__) . 'modal.css');

}

add_action( 'wp_ajax_was_visited', 'ajax_post_was_visited_handler' );

add_action( 'wp_ajax_nopriv_was_visited', 'ajax_post_was_visited_handler' );

if ( !function_exists ( 'ajax_post_was_visited_handler' ) ) {

    function ajax_post_was_visited_handler () {

        global $wpdb;

        $table_name = $wpdb->prefix . 'was_visited';

        $ip = $_SERVER['REMOTE_ADDR'];

        $browser_version = $_POST['browser_version'];

        $post_id = $_POST['post_id'];

        $total_query = "SELECT COUNT(*) FROM {$table_name} 

        where ip = '{$ip}' and browser_version = '{$browser_version}'and post_id = '{$post_id}'";

        $total = $wpdb->get_var( $total_query );

        if ( is_null($total) || (int)$total === 0 ) {
            
            $wpdb->insert( $table_name,

                array( 

                    'ip' => $ip, 

                    'browser_version' => $browser_version,

                    'post_id' => $post_id 

                ),

                array(

                    '%s',

                    '%s',

                    '%d',

                )

            );

        }

        wp_die();

    }

}

add_action( 'rest_api_init', 'was_visited_routes' );

function was_visited_routes() {
    
    register_rest_route(

        'modal-api/v1',

        '/was-visited/',

        array(

            'methods'  => 'GET',

            'callback' => 'was_visited_callback',

            'permission_callback' => '__return_true'

        )

    );

    register_rest_route(

        'modal-api/v1',

        '/modal-content/',

        array(

            'methods'  => 'GET',

            'callback' => 'modal_content_callback',

            'permission_callback' => '__return_true'

        )

    );

}

if (!function_exists('was_visited_callback')) {

    function was_visited_callback() {

        global $wpdb;

        $table_name = $wpdb->prefix . 'was_visited';

        $ip = $_SERVER['REMOTE_ADDR'];

        $browser_version = $_GET['browser_version'];

        $post_id = $_GET['post_id'];

        $total_query = "SELECT COUNT(*) FROM {$table_name} where ip = '{$ip}' and browser_version = '{$browser_version}' and post_id = '{$post_id}'";

        $total = $wpdb->get_var( $total_query );

        echo json_encode(array('count' => $total));
    
    }

}

if (!function_exists('modal_content_callback')) {

    function modal_content_callback() {

        global $wpdb;

        $table_name = $wpdb->prefix . 'postmeta';

        $post_id = $_GET['post_id'];

        $results = $wpdb->get_results( "SELECT * FROM {$table_name} where post_id = '{$post_id}' and meta_key = 'content' or meta_key = 'url'" );

        foreach($results as $key => $result) {

            $results[$result->meta_key] = $result->meta_value;

            unset($results[$key]);

        }

        echo json_encode( $results, true);
    
    }

}

?>
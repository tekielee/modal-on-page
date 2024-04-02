<?php
/** Plugin Name: Modal On Page
** Author: Cuong Le
** Version: 1.0
** Description: Create a modal custom post type where authors can add content. Authors will associate the modal to a specific URL. The Modal will show the first time an user land in the page and won’t show again after assertive action by user. If the user declines the Modal, it will redirect to blackstone.com home page.
*/

register_activation_hook( __FILE__, 'author_modal_setup_table' );

function author_modal_setup_table() {

    global $wpdb;

    $table_name = $wpdb->prefix . 'author_modal';
    $table_name_2 = $wpdb->prefix . 'author_modal_browser_fingerprint';

    $sql = "CREATE TABLE $table_name (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      content text NOT NULL,
      associate_url mediumtext NOT NULL,
      display tinyint(1) NOT NULL DEFAULT 0,
      PRIMARY KEY  (id)
    )";

    $sql_2 = "CREATE TABLE $table_name_2 (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        ip varchar(100) NOT NULL,
        browser_version mediumtext NOT NULL,
        PRIMARY KEY  (id)
    )";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
    dbDelta( $sql_2 );

}

add_action('init', 'modal_custom_post_type');

if(!function_exists('modal_custom_post_type')) {
    function modal_custom_post_type() {
        register_post_type(
            'author_modal',
            array(
                'labels'      => array(
                    'name'          => __('Content Modal', 'author-modal'),
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

add_action ( 'admin_enqueue_scripts', 'author_modal_scripts' );

if ( ! function_exists ( 'author_modal_scripts' ) ) {

    function author_modal_scripts ( $hook ) {

        if ( $hook != 'toplevel_page_custompage' ) {

            return;

        }

        wp_enqueue_script('modal', plugin_dir_url(__FILE__) . 'modal-admin.js', array('jquery'), null, true);

    }

}

function modal_enqueue_scripts() {
    wp_enqueue_script('modal', plugin_dir_url(__FILE__) . 'modal.js', array('jquery'), null, true);
    wp_enqueue_style('modal', plugin_dir_url(__FILE__) . 'modal.css');
}

add_action('wp_enqueue_scripts', 'modal_enqueue_scripts');

if ( ! function_exists ( 'author_modal_content_menu' ) ) {

    function author_modal_content_menu () {

    	add_menu_page (

    		__( 'Author Modal', 'author-mdodal' ),

    		'Author Modal',

    		'manage_options',

    		'custompage',

            'author_modal_content_menu_page'

    	);

    }

}

if ( ! function_exists ( 'get_associate_urls_list' ) ) {

    function get_associate_urls_list($id) {

        global $wpdb;

        $table_name = $wpdb->prefix . 'author_modal';

        $associate_urls = $wpdb->get_results( "SELECT id, associate_url FROM {$table_name}" );

        $selected = '';

        $select = '<select id="associate-url-list" name="associate-url-list">';

        foreach ( $associate_urls as $associate_url ) {

            if((int)$associate_url->id === (int)$id)

                $selected = 'selected=selected';
                
            else

                $selected ='';

            $select .= '<option value="' . $associate_url->id . '" ' . $selected . '>' . $associate_url->associate_url . '</option>';

        }

        $select .= '</select>';

        return $select;

    }

}

add_action ( 'admin_menu', 'author_modal_content_menu' );

if ( ! function_exists ( 'author_modal_content_menu_page' ) ) {

    function author_modal_content_menu_page () {

        global $wpdb;
    
        $table_name = $wpdb->prefix . 'author_modal';

    
        $results = $wpdb->get_results( "SELECT * FROM {$table_name} where display = 1" );

        //print_r($results);

        if (empty($results)) {
                
                $content = '';

                $associate_url = '';

                $display = 'unchecked';

                $id = 0;

        } else {

            $content = wp_unslash($results[0]->content);

            $associate_url = $results[0]->associate_url;

            if((int)$results[0]->display === 1)  {

                $display = 'checked';

            } else {

                $display = 'unchecked';

            }

            $id = $results[0]->id;

        }
        
        $author = '

            <div>

                <label>Select Associate URL</label>

            ' . get_associate_urls_list($id) . ' 

                <br/>
        
                <label>Content</label>

                <textarea id="author-content" name="author-content" cols="100" rows="10">'. $content .'</textarea>

                <br/>

                <label>Associate Url</label>

                <input type="text" id="associate-url" name="associate-url" value="'. $associate_url .'" style="width:100%;" />

                <br/>

                <label>Display Modal</label>

                <input type="checkbox" id="display" name="display" ' . $display . '/>

                <br/>

                <button id="save-author-content" class="submit success button">Save</button>

                <br/>

                <div id="author-content-message"></div>

            </div>
        
        
        ';

        echo $author;
    }

}

add_action( 'wp_ajax_save_author_content', 'ajax_post_save_author_content_handler' );

if ( !function_exists ( 'ajax_post_save_author_content_handler' ) ) {

    function ajax_post_save_author_content_handler () {

        global $wpdb;

        $table_name = $wpdb->prefix . 'author_modal';

        $content = $_POST['content'];

        $associate_url = $_POST['associate_url'];

        $display = $_POST['display'];

        $result = $wpdb->get_results( "SELECT id FROM {$table_name} where associate_url = '{$associate_url}'" );

        if ( (int)count($result) === 0 ) {

            $wpdb->insert( $table_name, 

            array(
                'content' => $content, 
                'associate_url' => $associate_url,
                'display' => $display
            ),
            array(
                '%s',
                '%s',
                '%d',
            ) );

        } else {

            $wpdb->query(
                "UPDATE {$table_name} SET display = 0"
            );
                
            $wpdb->update( $table_name, 

            array(
                'content' => $content, 
                'associate_url' => $associate_url,
                'display' => $display
            ),
            array(
                'id' => $result[0]->id,
            ),
            array(
                '%s',
                '%s',
                '%d',
            ),
            array(
                '%d',
            ) );
        }

        echo 'Content saved';

    }

}

add_action( 'wp_ajax_save_browser_fingerprint', 'ajax_post_save_browser_fingerprint_handler' );

add_action( 'wp_ajax_nopriv_save_browser_fingerprint', 'ajax_post_save_browser_fingerprint_handler' );

if ( !function_exists ( 'ajax_post_save_browser_fingerprint_handler' ) ) {

    function ajax_post_save_browser_fingerprint_handler () {

        global $wpdb;

        $table_name = $wpdb->prefix . 'author_modal_browser_fingerprint';

        $ip = $_SERVER['REMOTE_ADDR'];

        $browser_version = $_POST['app_version'];

        $total_query = "SELECT COUNT(*) FROM {$table_name} where ip = '{$ip}' and browser_version = '{$browser_version}'";

        $total = $wpdb->get_var( $total_query );

        if ( (int)$total === 0 ) {

            $wpdb->insert( $table_name, 
                array( 
                    'ip' => $ip, 
                    'browser_version' => $browser_version 
                ),
                array(
                    '%s',
                    '%s',
                )
            );

        }

        echo $total;

        wp_die();

    }

}

add_action( 'rest_api_init', 'modal_author_routes' );

function modal_author_routes() {
    // Register the routes
    register_rest_route(
        'modal-api/v1',
        '/browser-inf/',
        array(
            'methods'  => 'GET',
            'callback' => 'browser_inf_callback',
            'permission_callback' => '__return_true'
        )
    );

    register_rest_route(
        'modal-api/v1',
        '/author-content/',
        array(
            'methods'  => 'GET',
            'callback' => 'author_content_callback',
            'permission_callback' => '__return_true'
        )
    );

    register_rest_route(
        'modal-api/v1',
        '/modal/',
        array(
            'methods'  => 'GET',
            'callback' => 'modal_callback',
            'permission_callback' => '__return_true'
        )
    );
}

if (!function_exists('browser_inf_callback')) {

    function browser_inf_callback( $data ) {

        global $wpdb;

        $table_name = $wpdb->prefix . 'author_modal_browser_fingerprint';

        $ip = $_SERVER['REMOTE_ADDR'];

        $browser_version = $_GET['app_version'];

        $total_query = "SELECT COUNT(*) FROM {$table_name} where ip = '{$ip}' and browser_version = '{$browser_version}'";

        $total = $wpdb->get_var( $total_query );

        echo json_encode(array('count' => $total));
    
    }

}

if (!function_exists('author_content_callback')) {

    function author_content_callback( $data ) {

        global $wpdb;

        $table_name = $wpdb->prefix . 'author_modal';

        $id = $_GET['id'];

        $results = $wpdb->get_results( "SELECT * FROM {$table_name} where id = '{$id}'" );

        echo json_encode( wp_unslash($results[0]), true);
    
    }


    if (!function_exists('modal_callback')) {

        function modal_callback( $data ) {
    
            global $wpdb;
    
            $table_name = $wpdb->prefix . 'author_modal';

    
            $results = $wpdb->get_results( "SELECT * FROM {$table_name} where display = 1" )[0];
    
            echo json_encode( wp_unslash($results), true);
        
        }
    
    }
}
?>
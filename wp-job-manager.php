<?php
/**
 * Plugin Name: WP Job Manager
 * Plugin URI: https://wpjobmanager.com/
 * Description: Manage job listings from the WordPress admin panel, and allow users to post jobs directly to your site.
 * Version: 100
 * Author: Automattic
 * Author URI: https://wpjobmanager.com/
 * Requires at least: 6.0
 * Tested up to: 6.2
 * Requires PHP: 7.4
 * Text Domain: wp-job-manager
 * Domain Path: /languages/
 * License: GPL2+
 * @package wp-job-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define constants.
define( 'JOB_MANAGER_VERSION', '1.41.0-dev' );
define( 'JOB_MANAGER_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'JOB_MANAGER_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
define( 'JOB_MANAGER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once dirname( __FILE__ ) . '/includes/class-wp-job-manager-dependency-checker.php';
if ( ! WP_Job_Manager_Dependency_Checker::check_dependencies() ) {
	return;
}

require_once dirname( __FILE__ ) . '/includes/class-wp-job-manager.php';

/**
 * Main instance of WP Job Manager.
 *
 * Returns the main instance of WP Job Manager to prevent the need to use globals.
 *
 * @since  1.26
 * @return WP_Job_Manager
 */
function WPJM() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName
	return WP_Job_Manager::instance();
}

$GLOBALS['job_manager'] = WPJM();

// Activation - works with symlinks.
register_activation_hook( basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ), [ WPJM(), 'activate' ] );

// Cleanup on deactivation.
register_deactivation_hook( __FILE__, [ WPJM(), 'unschedule_cron_jobs' ] );
register_deactivation_hook( __FILE__, [ WPJM(), 'usage_tracking_cleanup' ] );

// Enqueue the JavaScript file
function enqueue_plugin_script() {
    wp_enqueue_style('erp-job-style', plugin_dir_url(__FILE__) . 'erp-job-style.css', array(), '1.0.0', 'all');
    wp_enqueue_script('erp-job-script', plugin_dir_url(__FILE__) . 'erp-job-script.js', array('jquery'), '1.0.0', true);
    wp_localize_script('erp-job-script', 'ERPJOB', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'enqueue_plugin_script');
add_action('admin_enqueue_scripts', 'enqueue_plugin_script');

function activate_my_plugin() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aa_erp_job_list';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        message text NOT NULL,
        cv_url text NOT NULL,
        company_id mediumint(9) NOT NULL,
        contract_id mediumint(9) NOT NULL,
        job_id mediumint(9) NOT NULL,
        status text NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'activate_my_plugin');

add_action('wp_ajax_cxc_upload_file_data', 'cxc_upload_file_data');
add_action('wp_ajax_nopriv_cxc_upload_file_data', 'cxc_upload_file_data');

function cxc_upload_file_data(){

    global $wpdb;
    $table_name     = $wpdb->prefix . 'aa_erp_job_list';
    $full_name      = sanitize_text_field( $_POST['full_name'] );
    $email          = sanitize_email( $_POST['email'] );
    $message        = sanitize_text_field( $_POST['message'] );
    $post_id        = sanitize_text_field( $_POST['post_id'] );
    $company_id     = get_post_meta( $post_id, '_erp_company_id', true );
    $contact_id     = 0;
    $cxc_upload_dir = wp_upload_dir();
    $cxc_success    = false;
    $cxc_messages   = '';
    $applied        = $wpdb->get_var( 
        $wpdb->prepare( 
            "SELECT COUNT(*) FROM $table_name WHERE email = %s AND company_id = %s", $email, $company_id  ) );
    if ( $applied ) {
        $cxc_messages = array( 
            'success' => $cxc_success,
            'message' => __('You have already applied', 'wp-job-manager' )
        );
        wp_send_json( $cxc_messages );
    }
	
    if ( ! empty( $cxc_upload_dir['basedir'] ) ) {

        $cxc_user_dirname = $cxc_upload_dir['basedir'].'/erp-jobs-pdfs/';
        $cxc_user_baseurl = $cxc_upload_dir['baseurl'].'/erp-jobs-pdfs/';

        if ( ! file_exists( $cxc_user_dirname ) ) {
            wp_mkdir_p( $cxc_user_dirname );
        }

        $cxc_filename   = wp_unique_filename( $cxc_user_dirname, $_FILES['file']['name'] );
        $cxc_success    = move_uploaded_file( $_FILES['file']['tmp_name'], $cxc_user_dirname .''. $cxc_filename );
        $cxc_image_url  = $cxc_user_baseurl .''. $cxc_filename;

        if( !empty( $cxc_success ) ) {

            $data = [
                'type'          => 'contact',
                'first_name'    => $full_name,
                'email'         => $email,
            ];
            if ( function_exists('erp_insert_people') ) {
                $contact_id = erp_insert_people( $data );
            }

            $wpdb->insert(
                $table_name,
                array(
                    'name'          => $full_name,
                    'email'         => $email,
                    'message'       => $message,
                    'cv_url'        => $cxc_image_url,
                    'company_id'    => $company_id,
                    'contract_id'   => $contact_id,
                    'job_id'        => $post_id,
                    'status'        => 'applied',
                )
            );
            $admin_email    = get_option('admin_email');
            $subject        = 'New Job Application Submitted';
            $message        = "A new job application has been submitted with {$email}. Please check the admin panel for details.";

            wp_mail( $admin_email, $subject, $message );

            $cxc_success = true;
            $cxc_messages = array( 
                'success' => $cxc_success,
                'message' => __( 'Successfully Applied', 'wp-job-manager' )

            );
        }
        else{
            $cxc_success = false;
            $cxc_messages = array( 
                'success' => $cxc_success,
                'message' => __( 'Apply Failed', 'wp-job-manager' )
            );
        }
        wp_send_json( $cxc_messages );
    }
}

function job_applications_table_shortcode() {

    global $wpdb;
    $current_user_id    = get_current_user_id();
    $table_name         = $wpdb->prefix . 'erp_peoples';
    $company_id         = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_name WHERE user_id = %d", $current_user_id) );
    $status_options     = ['applied', 'hired', 'closed' ,'interview', 'interviewed' ];
    $table_name2        = $wpdb->prefix . 'aa_erp_job_list';

    if ( ! $company_id ) return;

    $job_applications   = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $table_name2 WHERE company_id = %d", $company_id ),
        ARRAY_A
    );

    if ( $job_applications ) {
        $table_html = '<table>
            <tr>
                <th>'. __( "Name", "wp-job-manager" ) .'</th>
                <th>'. __( "Email", "wp-job-manager" ) .'</th>
                <th>'. __( "Message", "wp-job-manager" ) .'</th>
                <th>'. __( "CV", "wp-job-manager" ) .'</th>
                <th>'. __( "Status", "wp-job-manager" ) .'</th>
                <th>'. __( "Action", "wp-job-manager" ) .'</th>
            </tr>';
    }
    else{
       _e('No one has applied or you don’t have permission.', 'wp-job-manager');
    }

    if ( ! $job_applications ) return;

    foreach ( $job_applications as $application ) {
        if ( $application['status'] != 'applied' ) {
            $table_html .= '<tr>
                <td data-id="'. $application['id'] .'" >' . $application['name'] . '</td>
                <td>' . $application['email'] . '</td>
                <td>' . $application['message'] . '</td>
                <td><a href="' . $application['cv_url'] . '" target="_blank">'. __( "Download CV", "wp-job-manager" ) .'</a></td>
                <td>
                    <select name="status">
                        ';

                foreach ( $status_options as $option ) {
                    $selected = ($option == $application['status']) ? 'selected' : '';
                    $table_html .= '<option value="' . $option . '" ' . $selected . '>' . ucfirst( $option ). '</option>';
                }

                $table_html .= '
                        </select>
                    </td>
                    <td>
                        <button id="erp-job-status" >'. __( "Apply", "wp-job-manager" ) .'</button>
                    </td>
                </tr>';
        }

    }
    
    $table_html .= '</table>';
    
    return $table_html;
}
add_shortcode('job_applications_table', 'job_applications_table_shortcode');
function update_status_callback() {
    global $wpdb;

    $status     = sanitize_text_field( $_POST['status'] );
    $id         = sanitize_text_field( $_POST['id'] );
    $table_name = $wpdb->prefix . 'aa_erp_job_list';
    $sql        = $wpdb->prepare("UPDATE $table_name SET status = %s WHERE id = %s", $status, $id );

    $wpdb->query( $sql );

    $cxc_success    = true;
    $cxc_messages   = array( 
        'success' => $cxc_success,
        'message' => __('Status changed to ', 'wp-job-manager') . $status,
    );

    wp_send_json( $cxc_messages );
}

add_action('wp_ajax_update_status', 'update_status_callback');
add_action('wp_ajax_nopriv_update_status', 'update_status_callback');

add_action( 'wp_footer', 'modal' );
add_action( 'admin_footer', 'modal' );

function modal() {
    ?>
    <div class="erp-job-modal">
      <div class="erp-job-modal-content">
        <span class="erp-job-modal-close">&times;</span>
        <p></p>
      </div>
    </div>
    <?php
}
function custom_redirect_for_user_role( $user_login, $user ) {

    if (in_array('employer', $user->roles)) {
        $redirect_url = get_permalink(8379);
        wp_redirect($redirect_url);
        exit();
    }
}
add_action('wp_login', 'custom_redirect_for_user_role', 10, 2);

function custom_shortcode_function() {

    $category_slugs = array('Management', 'Inactive Ads' );

    $args_posts = array(
        'post_type' => 'post',
        'category_name' => implode(',', $category_slugs ), 
        'posts_per_page' => -1,
    );

    $posts_query = new WP_Query($args_posts);

    $args = array(
        'post_type' => 'job_listing',
        'posts_per_page' => -1,
    );
    
    $jobs_query     = new WP_Query($args);
    $combined_posts = array_merge($posts_query->posts, $jobs_query->posts);

// Shuffle the combined array to get a random order
    shuffle( $combined_posts );

    // Custom array to store post data
    $custom_posts = array();
    foreach ( $combined_posts as $post ) {
        setup_postdata( $post );
    
        if ( $post->post_type === 'post' ) {
            $terms          = get_the_category( $post->ID );
            $category_name  = !empty( $terms ) ? $terms[0]->name : '';
        } elseif ($post->post_type === 'job_listing') {
            $taxonomy       = 'job_listing_type'; 
            $terms          = get_the_terms( $post->ID, $taxonomy );
            $category_name  = ! empty( $terms ) ? $terms[0]->name : '';
        }
        
        $custom_post = array(
            'id'            => $post->ID,
            'post_content'  => get_the_title( $post->ID ),
            'category_name' => $category_name,
        );
    
        $custom_posts[] = $custom_post;
    }
    

    $output = '<div class="erp-all-jobs" >';


    foreach ( $custom_posts as $custom_post ) {

        $post_content = $custom_post['post_content'];

        $output .= '<div class="job-listing">';

        // Display post content
        $output .= '<div class="post-content">' . substr( $post_content, 0, 100 ) . '...</div>';

        // Get associated taxonomy terms from the custom array
        $terms = isset( $custom_post['category_name'] ) ? [$custom_post['category_name']] : null;

        if ( $terms && !is_wp_error( $terms ) ) {
            $output .= '<div class="taxonomy-terms">';

            foreach ( $terms as $term ) {
                $output .= '<span class="term">' . esc_html( $term ) . '</span>';
            }

            $output .= '</div>';
        }

        // Add a button with the post link
        $output .= '<a href="' .get_permalink( $custom_post['id'] ) . '" class="button">' . esc_html('Apply Now', 'wp-job-manager') . '</a>';

        $output .= '</div>';
    }

    $output .= '</div>';

    return $output;
}

add_shortcode( 'erp_jobs', 'custom_shortcode_function' );

<?php
/**
 * Plugin Name: WP Job Manager
 * Plugin URI: https://wpjobmanager.com/
 * Description: Manage job listings from the WordPress admin panel, and allow users to post jobs directly to your site.
 * Version: 1.42.0
 * Author: Automattic
 * Author URI: https://wpjobmanager.com/
 * Requires at least: 6.0
 * Tested up to: 6.2
 * Requires PHP: 7.4
 * Text Domain: wp-job-manager
 * Domain Path: /languages/
 * License: GPL2+
 *
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

add_action('wp_head', 'css' );

function css() {

}
function activate_my_plugin() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'aa_erp_job_list';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        message text NOT NULL,
        cv_id mediumint(9) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'activate_my_plugin');

// add_action('wp_ajax_epr_job_submit', 'epr_job_submit');
// add_action('wp_ajax_nopriv_epr_job_submit', 'epr_job_submit');

// function epr_job_submit() {
//         // parse_str($_POST['formData'], $formData);

//     // if (isset($formData['full_name']) && isset($formData['email']) && isset($formData['message'])) {
//     //     $full_name = sanitize_text_field($formData['full_name']);
//     //     $email = sanitize_email($formData['email']);
//     //     $message = sanitize_text_field($formData['message']);

//     //     if (isset($_FILES['cv'])) {
//     //         $file = $_FILES['cv'];
//     //         update_option( 'fwqffq', "fweqffwqffwq" );
//     //         update_option( 'file', $file );
//     //         $upload_overrides = array('test_form' => false);
//     //         $uploaded_file = wp_handle_upload($file, $upload_overrides);

//     //         if ($uploaded_file && !isset($uploaded_file['error'])) {
//     //             $file_path = $uploaded_file['file'];
//     //             $file_name = basename($file_path);

//     //             $attachment = array(
//     //                 'post_mime_type' => $file['type'],
//     //                 'post_title' => sanitize_file_name($file_name),
//     //                 'post_content' => '',
//     //                 'post_status' => 'inherit',
//     //             );

//     //             $attachment_id = wp_insert_attachment($attachment, $file_path);

//     //             if (!is_wp_error($attachment_id)) {
//     //                 require_once(ABSPATH . 'wp-admin/includes/image.php');
//     //                 $attachment_data = wp_generate_attachment_metadata($attachment_id, $file_path);
//     //                 wp_update_attachment_metadata($attachment_id, $attachment_data);
//     //             }
//     //         }
//     //     } else {
//     //         $attachment_id = 0;
//     //     }

//     //     // Now, you can use $full_name, $email, $message, and $attachment_id to perform any further actions or database operations as needed.

//     //     // For example, you can insert the data into a custom table.
//     //     global $wpdb;
//     //     $table_name = $wpdb->prefix . 'aa_erp_job_list';
//     //     $wpdb->insert(
//     //         $table_name,
//     //         array(
//     //             'name' => $full_name,
//     //             'email' => $email,
//     //             'message' => $message,
//     //             'cv_id' => $attachment_id, // Use the attachment ID
//     //         )
//     //     );

//     //     // Return a response (e.g., success message)
//     //     $response = array(
//     //         'status' => 'success',
//     //         'message' => $attachment_id,
//     //     );

//     //     wp_send_json($response);
//     // } else {
//         // Handle missing or invalid data
//         $response = array(
//             'status' => 'error',
//             'message' => 111,
//         );

//         wp_send_json($response);
//     // }
// }


function epr_job_submit_callback() {
    // Check if the action is set and matches the expected value
        update_option( 'fqqfqwfqwff', 'fwqfwqfwq' );
        if (isset($_FILES['file'])) {
            $file = $_FILES['file'];
       update_option( '_FILES_FILES', 'fwqfwqfwq' );
            if ($file['error'] == 0) {
                // Process and save the uploaded file
                $upload_dir = wp_upload_dir();
                $file_name = sanitize_file_name($file['name']);
                $file_path = $upload_dir['path'] . '/' . $file_name;

                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    // File uploaded successfully
                    $caption = sanitize_text_field($_POST['caption']);
                    
                    // You can now do something with the uploaded file and caption, e.g., save them to a database.

                    // Prepare the JSON response
                    $response = array(
                        'status' => 'success',
                        'message' => 'File uploaded successfully'
                    );

                    wp_send_json($response);
                } else {
                    // Error moving the uploaded file
                    $response = array(
                        'status' => 'error',
                        'message' => 'Error uploading the file'
                    );

                    wp_send_json($response);
                }
            } else {
                // File upload error
                $response = array(
                    'status' => 'error',
                    'message' => 'File upload error: ' . $file['error']
                );

                wp_send_json($response);
            }
        } else {
            // No file received in the POST request
            $response = array(
                'status' => 'error',
                'message' => 'No file received'
            );

            wp_send_json($response);
        }
    
        $response = array(
        'status' => 'error',
        'message' => 'Invalid action'
    );

    wp_send_json($response);
}

add_action('wp_ajax_erp_job_submit', 'epr_job_submit_callback');
add_action('wp_ajax_nopriv_erp_job_submit', 'epr_job_submit_callback');

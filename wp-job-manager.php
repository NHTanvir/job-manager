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
        cv_url text NOT NULL,
        company_id mediumint(9) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'activate_my_plugin');

add_action('wp_ajax_cxc_upload_file_data', 'cxc_upload_file_data');
add_action('wp_ajax_nopriv_cxc_upload_file_data', 'cxc_upload_file_data');

function cxc_upload_file_data(){
    update_option( 'effwef', $_POST );
    $cxc_upload_dir = wp_upload_dir();
    $cxc_success = false;
    $cxc_messages = '';

    if ( ! empty( $cxc_upload_dir['basedir'] ) ) {

        $cxc_user_dirname = $cxc_upload_dir['basedir'].'/erp-jobs-pdfs/';
        $cxc_user_baseurl = $cxc_upload_dir['baseurl'].'/erp-jobs-pdfs/';

        if ( ! file_exists( $cxc_user_dirname ) ) {
            wp_mkdir_p( $cxc_user_dirname );
        }

        $cxc_filename   = wp_unique_filename( $cxc_user_dirname, $_FILES['file']['name'] );
        $cxc_success    = move_uploaded_file( $_FILES['file']['tmp_name'], $cxc_user_dirname .''. $cxc_filename );
        $cxc_image_url  = $cxc_user_baseurl .''. $cxc_filename;
        $full_name      = sanitize_text_field( $_POST['full_name'] );
        $email          = sanitize_email( $_POST['email'] );
        $message        = sanitize_text_field( $_POST['message'] );
        $post_id        = sanitize_text_field( $_POST['post_id'] );
        $company_id     = get_post_meta( $post_id, '_erp_company_id', true );
        if( !empty( $cxc_success ) ) {
            global $wpdb;
            $table_name     = $wpdb->prefix . 'aa_erp_job_list';
            // $email_exists   = $wpdb->get_var(
            //     $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE email = %s", $email)
            // );

            $wpdb->insert(
                $table_name,
                array(
                    'name'          => $full_name,
                    'email'         => $email,
                    'message'       => $message,
                    'cv_url'        => $cxc_image_url,
                    'company_id'    => $company_id,
                )
            );
            $cxc_success = true;
        }
        else{
            $cxc_success = false;
        }

        $cxc_messages = array( 'success' => $cxc_success, 'cxc_image_url' => $cxc_image_url );
        wp_send_json( $cxc_messages );
    }
}
<?php
/**
 * Show job application when viewing a single job listing.
 *
 * This template can be overridden by copying it to yourtheme/job_manager/job-application.php.
 *
 * @see         https://wpjobmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     wp-job-manager
 * @category    Template
 * @version     1.31.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<?php if ( $apply = get_the_job_application_method() ) :
	wp_enqueue_script( 'wp-job-manager-job-application' );
	?>
	<div class="job_application application">
		<?php do_action( 'job_application_start', $apply ); ?>
		<?php if( is_user_logged_in() ) : ?>
	    <div class="erp-job-container">
	        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" enctype="multipart/form-data">
	            <label for="full_name">Full Name:</label>
	            <input type="text" id="full_name" name="full_name" required>

	            <label for="email">Email:</label>
	            <input type="email" id="email" name="email" required>

	            <label for="message">Message:</label>
	            <textarea id="message" name="message" required></textarea>

	            <label for="cv">Upload CV:</label>
	            <input type="file" id="cv" name = "files[]"  accept=".pdf, .doc, .docx">

	            <input type="submit" value="Apply" class="btn erp-job-submit-btn">
	        </form>
	    </div>
	    <?php else: ?>
	    <?php _e( 'Log In to apply', 'wp-job-manager' ) ?>
	    <?php endif; ?>
		<?php do_action( 'job_application_end', $apply ); ?>
	</div>
<?php endif; ?>

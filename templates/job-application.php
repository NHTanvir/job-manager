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
	?>
	<div class="job_application application">
		<?php do_action( 'job_application_start', $apply ); ?>

	    <div class="erp-job-container">
	        <form method="post" class="cxc_upload_form" enctype="multipart/form-data">
	            <label for="full_name"></label><?php esc_html_e( 'Full Name:', 'wp-job-manager' ); ?></label>
	            <input type="text" id="full_name" name="full_name" required>

	            <label for="email"><?php esc_html_e( 'Email:', 'wp-job-manager' ); ?></label>
	            <input type="email" id="email" name="email" required>

	            <label for="message"><?php esc_html_e( 'Message:', 'wp-job-manager' ); ?></label>
	            <textarea id="message" name="message" required></textarea>

				<div class="cxc_image_url"></div>
				<label for="cv"><?php esc_html_e( 'Upload CV:', 'wp-job-manager' ); ?></label>
				<input type="file" name="file"  accept=".pdf, .doc, .doc" required />
				<input type="hidden" name="post_id" id="post_id" value="<?php echo get_the_id() ?>" />
				<div class="erp-job-success"></div>
	            <input type="submit" value="<?php esc_attr_e( 'Apply', 'wp-job-manager' ); ?>" class="btn erp-job-submit-btn">
	        </form>
	    </div>

		<?php do_action( 'job_application_end', $apply ); ?>
	</div>
<?php endif; ?>
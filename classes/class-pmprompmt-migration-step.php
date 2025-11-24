<?php
abstract class PMProMPMT_Migration_Step {
	/**
	 * Get the step slug.
	 *
	 * @return string The step slug.
	 */
	abstract static public function get_step_slug();

	/**
	 * Get the step name. This will be displayed in the header of the step.
	 *
	 * @return string The step name.
	 */
	abstract static public function get_step_name();

	/**
	 * Get the status of the step.
	 *
	 * @return string The status of the step. Possible values: 'not_started', 'in_progress', 'completed'.
	 */
	abstract static public function get_step_status();

	/**
	 * Whether the step should default to being expanded.
	 *
	 * @return bool True if the step should default to being expanded, false otherwise.
	 */
	abstract static public function should_be_expanded();

	/**
	 * Display the body content of the step.
	 */
	abstract static public function display_step_body();

	/**
	 * Process the step.
	 */
	abstract static public function process_step();

	/**
	 * Process the step if this form was submitted and the nonce is valid.
	 */
	final static public function maybe_process_step() {
		// Check if this step's form was submitted.
		if ( ! isset( $_POST['pmprompmt_action'] ) || sanitize_text_field( wp_unslash( $_POST['pmprompmt_action'] ) ) !== static::get_step_slug() ) {
			return;
		}

		// Verify nonce.
		if ( ! isset( $_POST['pmprompmt_migration_step_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pmprompmt_migration_step_nonce'] ) ), 'pmprompmt_migration_step_' . static::get_step_slug() ) ) {
			wp_die( esc_html__( 'Nonce verification failed. Please try again.', 'pmpro-memberpress-migration-toolkit' ) );
		}

		// Process the step after nonce verification.
		static::process_step();
	}

	/**
	 * Display the entire step, including header and body.
	 */
	final static public function display_step() {
		$should_be_expanded = static::should_be_expanded();
		$status = static::get_step_status();
		?>
		<div class="pmpro_section" data-visibility="<?php echo $should_be_expanded ? 'shown' : 'hidden'; ?>" data-activated="<?php echo $should_be_expanded ? 'true' : 'false'; ?>">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="<?php echo $should_be_expanded ? 'true' : 'false'; ?>">
					<span class="dashicons dashicons-arrow-<?php echo $should_be_expanded ? 'up' : 'down'; ?>-alt2"></span>
					<?php
					echo esc_html( static::get_step_name() );
					if ( 'completed' === $status ) {
						// If the status is completed, show a checkmark emoji.
						echo ' ' . esc_html( '✅' );
					} elseif ( 'in_progress' === $status ) {
						// If the status is in progress, show a hourglass emoji.
						echo ' ' . esc_html( '⏳' );
					}
					?>

				</button>
			</div>
			<div class="pmpro_section_inside" style="<?php echo $should_be_expanded ? '' : 'display: none;'; ?>">
				<form method="post" action="">
					<input type="hidden" name="pmprompmt_action" value="<?php echo esc_attr( static::get_step_slug() ); ?>" />
					<?php
					wp_nonce_field( 'pmprompmt_migration_step_' . static::get_step_slug(), 'pmprompmt_migration_step_nonce' );
					static::display_step_body();
					?>
				</form>
			</div>
		</div>
		<?php
	}
}
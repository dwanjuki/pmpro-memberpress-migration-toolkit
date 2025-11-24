<?php
class PMProMPMT_Migration_Step_Final extends PMProMPMT_Migration_Step {
	/**
	 * Get the step slug.
	 *
	 * @return string The step slug.
	 */
	static public function get_step_slug() {
		return 'final';
	}

	/**
	 * Get the step name. This will be displayed in the header of the step.
	 *
	 * @return string The step name.
	 */
	static public function get_step_name() {
		return esc_html__( 'Final Steps', 'pmpro-memberpress-migration-toolkit' );
	}

	/**
	 * Get the status of the step.
	 *
	 * @return string The status of the step. Possible values: 'not_started', 'in_progress', 'completed'.
	 */
	static public function get_step_status() {
		return 'not_started';
	}

	/**
	 * Whether the step should default to being expanded.
	 *
	 * @return bool True if the step should default to being expanded, false otherwise.
	 */
	static public function should_be_expanded() {
		return true;
	}

	/**
	 * Display the body content of the step.
	 */
	static public function display_step_body() {
		?>
		<p><?php esc_html_e( 'After completing the migration, please review the following items to ensure everything is set up correctly:', 'pmpro-memberpress-migration-toolkit' ); ?></p>
		<ul>
			<li><?php esc_html_e( 'Review and adjust membership levels as needed.', 'pmpro-memberpress-migration-toolkit' ); ?></li>
			<li><?php esc_html_e( 'Verify that all user memberships and orders have been migrated correctly.', 'pmpro-memberpress-migration-toolkit' ); ?></li>
			<li><?php esc_html_e( 'Test the checkout process to ensure payments are processed correctly.', 'pmpro-memberpress-migration-toolkit' ); ?></li>
			<li><?php esc_html_e( 'Set up any additional Add Ons or integrations as needed.', 'pmpro-memberpress-migration-toolkit' ); ?></li>
		</ul>
		<?php
	}

	/**
	 * Process the step.
	 */
	static public function process_step() {
		// No processing needed for the final step.
	}
}
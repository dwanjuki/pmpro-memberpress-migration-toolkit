<?php
class PMProMPMT_Migration_Step_License extends PMProMPMT_Migration_Step {
	/**
	 * Get the step slug.
	 *
	 * @return string The step slug.
	 */
	static public function get_step_slug() {
		return 'license';
	}

	/**
	 * Get the step name. This will be displayed in the header of the step.
	 *
	 * @return string The step name.
	 */
	static public function get_step_name() {
		return esc_html__( 'Activate License Key', 'pmpro-memberpress-migration-toolkit' );
	}

	/**
	 * Get the status of the step.
	 *
	 * @return string The status of the step. Possible values: 'not_started', 'in_progress', 'completed'.
	 */
	static public function get_step_status() {
		return pmpro_license_isValid() ? 'completed' : 'not_started';
	}

	/**
	 * Whether the step should default to being expanded.
	 *
	 * @return bool True if the step should default to being expanded, false otherwise.
	 */
	static public function should_be_expanded() {
		return 'not_started' === static::get_step_status();
	}

	/**
	 * Display the body content of the step.
	 */
	static public function display_step_body() {
		?>
		<p><?php esc_html_e( 'To install premium Add Ons and receive support for the MemberPress Migration Toolkit, please enter your Paid Memberships Pro license key below.', 'pmpro-memberpress-migration-toolkit' ); ?></p>
		<input type="text" name="pmpro_memberpress_migration_toolkit_license_key" value="<?php echo esc_attr( get_option( 'pmpro_license_key', '' ) ); ?>" class="pmpro-wizard__field-block" />
		<button class="button button-primary" type="submit"><?php esc_html_e( 'Activate License', 'pmpro-memberpress-migration-toolkit' ); ?></button>
		<?php
	}

	/**
	 * Process the step.
	 */
	static public function process_step() {
		// Get the license key from the form submission.
		$license_key = '';
		if ( isset( $_POST['pmpro_memberpress_migration_toolkit_license_key'] ) ) {
			$license_key = sanitize_text_field( wp_unslash( $_POST['pmpro_memberpress_migration_toolkit_license_key'] ) );
		}

		// Update the license key option.
		update_option( 'pmpro_license_key', $license_key );

		// Check the license key with the PMPro server.
		$license_valid = pmpro_license_isValid( $license_key, null, true );

		// Set a message based on whether the license key is valid.
		if ( $license_valid ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'License key activated successfully.', 'pmpro-memberpress-migration-toolkit' ) . '</p></div>';
		} else {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Invalid license key. Please try again.', 'pmpro-memberpress-migration-toolkit' ) . '</p></div>';
		}
	}
}
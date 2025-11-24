<?php
class PMProMPMT_Migration_Step_Content_Restrictions extends PMProMPMT_Migration_Step {
	/**
	 * Get the step slug.
	 *
	 * @return string The step slug.
	 */
	static public function get_step_slug() {
		return 'content_restrictions';
	}

	/**
	 * Get the step name. This will be displayed in the header of the step.
	 *
	 * @return string The step name.
	 */
	static public function get_step_name() {
		return esc_html__( 'Migrate Content Restrictions', 'pmpro-memberpress-migration-toolkit' );
	}

	/**
	 * Get the status of the step.
	 *
	 * @return string The status of the step. Possible values: 'not_started', 'in_progress', 'completed'.
	 */
	static public function get_step_status() {
		global $wpdb;
		$queue_content_restriction_migrations_query_args = array(
			'hook'   => 'pmprompmt_queue_content_restriction_migrations',
			'status' => ActionScheduler_Store::STATUS_PENDING,
		);
		$migrate_content_restriction_migrations_query_args = array(
			'hook'   => 'pmprompmt_migrate_content_restriction',
			'status' => ActionScheduler_Store::STATUS_PENDING,
		);

		// Check if a pmprompmt_queue_content_restriction_migrations or pmprompmt_migrate_content_restriction task is queued.
		if ( ! empty( as_get_scheduled_actions( $queue_content_restriction_migrations_query_args ) ) || ! empty( as_get_scheduled_actions( $migrate_content_restriction_migrations_query_args ) ) ) {
			return 'in_progress';
		}

		// Check if there are any content restrictions. If there are, we assume content restrictions have been migrated.
		global $wpdb;
		$pmpro_has_content_restrictions = ! empty( $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->pmpro_memberships_pages LIMIT 1" ) );
		if ( $pmpro_has_content_restrictions ) {
			return 'completed';
		}

		return 'not_started';
	}

	/**
	 * Whether the step should default to being expanded.
	 *
	 * @return bool True if the step should default to being expanded, false otherwise.
	 */
	static public function should_be_expanded() {
		return 'not_started' === static::get_step_status() && ! empty( get_option( 'pmprompmt_level_map' ) );
	}

	/**
	 * Display the body content of the step.
	 */
	static public function display_step_body() {
		?>
		<p><?php esc_html_e( 'Only membership-based content restrictions can be migrated from MemberPress to Paid Memberships Pro. Content restrictions based on other criteria (such as roles, capabilities, or specific users) will need to be set up manually after the migration.', 'pmpro-memberpress-migration-toolkit' ); ?></p>
		<button class="button button-primary" type="submit"><?php esc_html_e( 'Queue Content Restriction Migrations', 'pmpro-memberpress-migration-toolkit' ); ?></button>
		<?php
	}

	/**
	 * Process the step.
	 */
	static public function process_step() {
		// Queue up all content restriction migrations.
		PMPro_Action_Scheduler::instance()->maybe_add_task(
			'pmprompmt_queue_content_restriction_migrations',
			array(),
			'pmpro_async_tasks'
		);
	}
}
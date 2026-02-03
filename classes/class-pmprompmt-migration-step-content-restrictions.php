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
		<h4><?php esc_html_e( 'What Will Be Migrated', 'pmpro-memberpress-migration-toolkit' ); ?></h4>
		<ul>
			<li><?php esc_html_e( 'Single post and page restrictions (membership-based)', 'pmpro-memberpress-migration-toolkit' ); ?></li>
			<li><?php esc_html_e( 'Category-based restrictions', 'pmpro-memberpress-migration-toolkit' ); ?></li>
			<li><?php esc_html_e( 'Parent page restrictions (including child pages)', 'pmpro-memberpress-migration-toolkit' ); ?></li>
			<li><?php esc_html_e( 'All posts/pages restriction rules', 'pmpro-memberpress-migration-toolkit' ); ?></li>
		</ul>

		<h4><?php esc_html_e( 'What Will NOT Be Migrated', 'pmpro-memberpress-migration-toolkit' ); ?></h4>
		<ul>
			<li>
				<strong><?php esc_html_e( 'Custom Post Type Restrictions:', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
				<?php
				printf(
					/* translators: %s: Link to Custom Post Type Membership Access Add On */
					esc_html__( 'Use the %s or specific PMPro integrations to restrict custom post types.', 'pmpro-memberpress-migration-toolkit' ),
					'<a href="https://www.paidmembershipspro.com/add-ons/custom-post-type-membership-access/" target="_blank">' . esc_html__( 'Custom Post Type Membership Access Add On', 'pmpro-memberpress-migration-toolkit' ) . '</a>'
				);
				?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Drip Content (Time-Based Content Release):', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
				<?php
				printf(
					/* translators: %s: Link to Series Add On */
					esc_html__( 'Use the %s to recreate drip content schedules.', 'pmpro-memberpress-migration-toolkit' ),
					'<a href="https://www.paidmembershipspro.com/add-ons/pmpro-series-for-drip-feed-content/" target="_blank">' . esc_html__( 'Series: Drip-Feed Content Add On', 'pmpro-memberpress-migration-toolkit' ) . '</a>'
				);
				?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Partial Content Restrictions Within a Post:', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
				<?php esc_html_e( 'PMPro restricts entire posts by default. Use the [membership] shortcode or Content Visibility block to wrap restricted sections within a post for partial content protection.', 'pmpro-memberpress-migration-toolkit' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Content Expiration Rules:', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
				<?php esc_html_e( 'Time-limited content access based on membership start date is not migrated. Configure using custom code.', 'pmpro-memberpress-migration-toolkit' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Role/Capability-Based Restrictions:', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
				<?php esc_html_e( 'Role/capability-based restrictions are not migrated. Configure using custom code.', 'pmpro-memberpress-migration-toolkit' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'User-Specific Restrictions:', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
				<?php esc_html_e( 'Individual user access rules must be set up manually or via custom code.', 'pmpro-memberpress-migration-toolkit' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'MemberPress Product Category Restrictions:', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
				<?php esc_html_e( 'Product category rules are not migrated. Set up category restrictions in PMPro under Memberships > Settings > Advanced.', 'pmpro-memberpress-migration-toolkit' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Level Group-Based Restrictions:', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
				<?php esc_html_e( 'MemberPress group-based rules must be recreated using PMPro level assignments.', 'pmpro-memberpress-migration-toolkit' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Paywall/Metered Content:', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
				<?php
				printf(
					/* translators: %s: Link to Limit Post Views Add On */
					esc_html__( 'Use the %s for metered paywall functionality.', 'pmpro-memberpress-migration-toolkit' ),
					'<a href="https://www.paidmembershipspro.com/add-ons/pmpro-limit-post-views/" target="_blank">' . esc_html__( 'Limit Post Views Add On', 'pmpro-memberpress-migration-toolkit' ) . '</a>'
				);
				?>
			</li>
		</ul>

		<h4><?php esc_html_e( 'After Migration', 'pmpro-memberpress-migration-toolkit' ); ?></h4>
		<p><?php esc_html_e( 'Review your content restrictions by visiting protected pages and verifying access.', 'pmpro-memberpress-migration-toolkit' ); ?></p>

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
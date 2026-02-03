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
		<p><?php esc_html_e( 'Congratulations! Your migration from MemberPress to Paid Memberships Pro is complete. Please review the following checklist to ensure everything is configured correctly.', 'pmpro-memberpress-migration-toolkit' ); ?></p>

		<h4><?php esc_html_e( 'Post-Migration Checklist', 'pmpro-memberpress-migration-toolkit' ); ?></h4>

		<h5><?php esc_html_e( '1. Verify Membership Levels', 'pmpro-memberpress-migration-toolkit' ); ?></h5>
		<ul>
			<li>
				<?php
				printf(
					/* translators: %s: Link to PMPro membership levels admin page */
					esc_html__( 'Review levels at %s', 'pmpro-memberpress-migration-toolkit' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=pmpro-membershiplevels' ) ) . '">' . esc_html__( 'Memberships > Settings > Levels', 'pmpro-memberpress-migration-toolkit' ) . '</a>'
				);
				?>
			</li>
			<li><?php esc_html_e( 'Configure expiration dates, trials, and billing limits that were not migrated', 'pmpro-memberpress-migration-toolkit' ); ?></li>
			<li><?php esc_html_e( 'Verify pricing and billing cycles are correct', 'pmpro-memberpress-migration-toolkit' ); ?></li>
		</ul>

		<h5><?php esc_html_e( '2. Verify User Memberships and Orders', 'pmpro-memberpress-migration-toolkit' ); ?></h5>
		<ul>
			<li>
				<?php
				printf(
					/* translators: %s: Link to PMPro members admin page */
					esc_html__( 'Check member list at %s', 'pmpro-memberpress-migration-toolkit' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=pmpro-memberslist' ) ) . '">' . esc_html__( 'Memberships > Members', 'pmpro-memberpress-migration-toolkit' ) . '</a>'
				);
				?>
			</li>
			<li>
				<?php
				printf(
					/* translators: %s: Link to PMPro orders admin page */
					esc_html__( 'Review orders at %s', 'pmpro-memberpress-migration-toolkit' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=pmpro-orders' ) ) . '">' . esc_html__( 'Memberships > Orders', 'pmpro-memberpress-migration-toolkit' ) . '</a>'
				);
				?>
			</li>
			<li><?php esc_html_e( 'Spot-check a few members to verify their membership status and order history', 'pmpro-memberpress-migration-toolkit' ); ?></li>
		</ul>

		<h5><?php esc_html_e( '3. Test the Checkout Process', 'pmpro-memberpress-migration-toolkit' ); ?></h5>
		<ul>
			<li><?php esc_html_e( 'Create a test checkout with a small amount or test mode', 'pmpro-memberpress-migration-toolkit' ); ?></li>
			<li><?php esc_html_e( 'Verify payment gateway is processing correctly', 'pmpro-memberpress-migration-toolkit' ); ?></li>
			<li><?php esc_html_e( 'Test the member account page and member-only content access', 'pmpro-memberpress-migration-toolkit' ); ?></li>
		</ul>

		<h5><?php esc_html_e( '4. Configure Email Templates', 'pmpro-memberpress-migration-toolkit' ); ?></h5>
		<ul>
			<li>
				<?php
				printf(
					/* translators: %s: Link to PMPro email templates admin page */
					esc_html__( 'Set up email templates at %s', 'pmpro-memberpress-migration-toolkit' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=pmpro-emailtemplates' ) ) . '">' . esc_html__( 'Memberships > Settings > Email Templates', 'pmpro-memberpress-migration-toolkit' ) . '</a>'
				);
				?>
			</li>
			<li><?php esc_html_e( 'Customize welcome, cancellation, and receipt emails', 'pmpro-memberpress-migration-toolkit' ); ?></li>
			<li><?php esc_html_e( 'Test sending emails to verify delivery', 'pmpro-memberpress-migration-toolkit' ); ?></li>
		</ul>

		<h5><?php esc_html_e( '5. Install Recommended Add Ons', 'pmpro-memberpress-migration-toolkit' ); ?></h5>
		<p><?php esc_html_e( 'Based on common MemberPress features, you may want to install these PMPro Add Ons:', 'pmpro-memberpress-migration-toolkit' ); ?></p>
		<ul>
			<li>
				<a href="https://www.paidmembershipspro.com/add-ons/pmpro-series-for-drip-feed-content/" target="_blank"><?php esc_html_e( 'Series: Drip-Feed Content', 'pmpro-memberpress-migration-toolkit' ); ?></a>
				<?php esc_html_e( '- For time-released content', 'pmpro-memberpress-migration-toolkit' ); ?>
			</li>
			<li>
				<a href="https://www.paidmembershipspro.com/add-ons/group-accounts/" target="_blank"><?php esc_html_e( 'Group Accounts', 'pmpro-memberpress-migration-toolkit' ); ?></a>
				<?php esc_html_e( '- For corporate/team memberships', 'pmpro-memberpress-migration-toolkit' ); ?>
			</li>
			<li>
				<a href="https://www.paidmembershipspro.com/add-ons/pmpro-courses-lms-integration/" target="_blank"><?php esc_html_e( 'Courses for Membership', 'pmpro-memberpress-migration-toolkit' ); ?></a>
				<?php esc_html_e( '- For course/lesson content', 'pmpro-memberpress-migration-toolkit' ); ?>
			</li>
			<li>
				<a href="https://www.paidmembershipspro.com/add-ons/extra-expiration-warning-emails-add-on/" target="_blank"><?php esc_html_e( 'Extra Expiration Warning Emails', 'pmpro-memberpress-migration-toolkit' ); ?></a>
				<?php esc_html_e( '- For membership reminder emails', 'pmpro-memberpress-migration-toolkit' ); ?>
			</li>
		</ul>
		<p>
			<?php
			printf(
				/* translators: %1$s: Link to PMPro add-ons website, %2$s: Link to PMPro add-ons admin page */
				esc_html__( 'Browse all Add Ons at %1$s or in the %2$s.', 'pmpro-memberpress-migration-toolkit' ),
				'<a href="https://www.paidmembershipspro.com/add-ons/" target="_blank">' . esc_html__( 'paidmembershipspro.com/add-ons', 'pmpro-memberpress-migration-toolkit' ) . '</a>',
				'<a href="' . esc_url( admin_url( 'admin.php?page=pmpro-addons' ) ) . '">' . esc_html__( 'PMPro Add Ons dashboard', 'pmpro-memberpress-migration-toolkit' ) . '</a>'
			);
			?>
		</p>

		<h5><?php esc_html_e( '6. Update Site Links and Navigation', 'pmpro-memberpress-migration-toolkit' ); ?></h5>
		<ul>
			<li><?php esc_html_e( 'Update menu links to point to new PMPro pages (account, checkout, etc.)', 'pmpro-memberpress-migration-toolkit' ); ?></li>
			<li><?php esc_html_e( 'Update any MemberPress shortcodes to PMPro equivalents', 'pmpro-memberpress-migration-toolkit' ); ?></li>
			<li><?php esc_html_e( 'Set up redirects from old MemberPress URLs if needed', 'pmpro-memberpress-migration-toolkit' ); ?></li>
		</ul>

		<h5><?php esc_html_e( '7. Deactivate MemberPress (When Ready)', 'pmpro-memberpress-migration-toolkit' ); ?></h5>
		<ul>
			<li><?php esc_html_e( 'Once you have verified everything is working, you can deactivate the MemberPress plugin', 'pmpro-memberpress-migration-toolkit' ); ?></li>
			<li><?php esc_html_e( 'Keep a backup of your database before deactivating, in case you need to reference MemberPress data', 'pmpro-memberpress-migration-toolkit' ); ?></li>
			<li><?php esc_html_e( 'Consider keeping MemberPress data in the database temporarily for reference', 'pmpro-memberpress-migration-toolkit' ); ?></li>
		</ul>

		<h4><?php esc_html_e( 'Need Help?', 'pmpro-memberpress-migration-toolkit' ); ?></h4>
		<ul>
			<li>
				<a href="https://www.paidmembershipspro.com/documentation/" target="_blank"><?php esc_html_e( 'PMPro Documentation', 'pmpro-memberpress-migration-toolkit' ); ?></a>
			</li>
			<li>
				<a href="https://www.paidmembershipspro.com/support/" target="_blank"><?php esc_html_e( 'PMPro Support', 'pmpro-memberpress-migration-toolkit' ); ?></a>
			</li>
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
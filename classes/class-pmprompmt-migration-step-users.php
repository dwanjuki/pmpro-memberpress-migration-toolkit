<?php
class PMProMPMT_Migration_Step_Users extends PMProMPMT_Migration_Step {
	/**
	 * Get the step slug.
	 *
	 * @return string The step slug.
	 */
	static public function get_step_slug() {
		return 'users';
	}

	/**
	 * Get the step name. This will be displayed in the header of the step.
	 *
	 * @return string The step name.
	 */
	static public function get_step_name() {
		return esc_html__( 'Migrate Users', 'pmpro-memberpress-migration-toolkit' );
	}

	/**
	 * Get the status of the step.
	 *
	 * @return string The status of the step. Possible values: 'not_started', 'in_progress', 'completed'.
	 */
	static public function get_step_status() {
		global $wpdb;
		$queue_user_migrations_query_args = array(
			'hook'   => 'pmprompmt_queue_user_migrations',
			'status' => ActionScheduler_Store::STATUS_PENDING,
		);
		$migrate_user_query_args = array(
			'hook'   => 'pmprompmt_migrate_user',
			'status' => ActionScheduler_Store::STATUS_PENDING,
		);

		// Check if a pmprompmt_queue_user_migrations or pmprompmt_migrate_user task is queued.
		if ( ! empty( as_get_scheduled_actions( $queue_user_migrations_query_args ) ) || ! empty( as_get_scheduled_actions( $migrate_user_query_args ) ) ) {
			return 'in_progress';
		}

		// Check if there are any membership records. If there are, we assume users have been migrated.
		global $wpdb;
		$pmpro_has_membership_data = ! empty( $wpdb->get_var( "SELECT COUNT(id) FROM $wpdb->pmpro_memberships_users LIMIT 1" ) );
		if ( $pmpro_has_membership_data ) {
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
		// Check if there is existing membership user data in PMPro.
		global $wpdb;
		$pmpro_has_membership_data = ! empty( $wpdb->get_var( "SELECT COUNT(id) FROM $wpdb->pmpro_memberships_users LIMIT 1" ) );
		if ( $pmpro_has_membership_data ) {
			// Show a warning that existing membership user data exists and migrating may cause issues.
			?>
			<p><?php esc_html_e( 'Warning: Existing membership user data has been detected in Paid Memberships Pro. Migrating user data from MemberPress may cause conflicts or duplicate memberships. Please ensure you have a backup of your database before proceeding.', 'pmpro-memberpress-migration-toolkit' ); ?></p>
			<?php
		}
		?>

		<h4><?php esc_html_e( 'What Will Be Migrated', 'pmpro-memberpress-migration-toolkit' ); ?></h4>
		<ul>
			<li><?php esc_html_e( 'MemberPress transactions converted to PMPro orders', 'pmpro-memberpress-migration-toolkit' ); ?></li>
			<li><?php esc_html_e( 'User membership assignments (based on migrated level mapping)', 'pmpro-memberpress-migration-toolkit' ); ?></li>
			<li><?php esc_html_e( 'Membership start and end dates', 'pmpro-memberpress-migration-toolkit' ); ?></li>
			<li><?php esc_html_e( 'Stripe subscription data (optional, see checkbox below)', 'pmpro-memberpress-migration-toolkit' ); ?></li>
		</ul>

		<h4><?php esc_html_e( 'What Will NOT Be Migrated', 'pmpro-memberpress-migration-toolkit' ); ?></h4>
		<ul>
			<li>
				<strong><?php esc_html_e( 'Non-Stripe Payment Gateway Subscriptions:', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
				<?php esc_html_e( 'PayPal, Authorize.net, and other gateway subscriptions cannot be automatically migrated. Memberships using these gateways will be assigned an expiration date and users will have to check out again to renew their membership. These payment subscriptions should be manually cancelled in your payment gateway.', 'pmpro-memberpress-migration-toolkit' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Member Notes/Communication History:', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
				<?php esc_html_e( 'Any notes attached to MemberPress members are not migrated. Export these separately if needed.', 'pmpro-memberpress-migration-toolkit' ); ?>
			</li>
		</ul>

		<h4><?php esc_html_e( 'Important Notes', 'pmpro-memberpress-migration-toolkit' ); ?></h4>
		<ul>
			<li><?php esc_html_e( 'The migration runs in the background using Action Scheduler. Large sites may take some time to complete.', 'pmpro-memberpress-migration-toolkit' ); ?></li>
		</ul>

		<p><?php esc_html_e( 'Once you are ready, click the button below to queue up all users for migration from MemberPress to Paid Memberships Pro.', 'pmpro-memberpress-migration-toolkit' ); ?></p>
		<hr />
		<div>
			<?php
			$stripe_gateways = array();
			$mp_options = get_option( 'mepr_options', array() );
			foreach ( $mp_options['integrations'] as $gateway ) {
				if ( 'MeprStripeGateway' === $gateway['gateway'] ) {
					$stripe_gateways[] = $gateway;
				}
			}
			if ( 1 === count( $stripe_gateways ) ) {
				?>
				<label for="pmprompmt_migrate_stripe_gateway_id">
					<input type="checkbox" name="pmprompmt_migrate_stripe_gateway_id" id="pmprompmt_migrate_stripe_gateway_id" value="<?php echo esc_attr( $stripe_gateways[0]['id'] ); ?>" />
					<?php esc_html_e( 'Migrate Stripe subscriptions to PMPro', 'pmpro-memberpress-migration-toolkit' ); ?>
				</label>
				<br /><br />
				<?php
			} elseif ( count( $stripe_gateways ) > 1 ) {
				?>
				<label for="pmprompmt_migrate_stripe_gateway_id"><?php esc_html_e( 'Migrate Stripe subscriptions to PMPro?', 'pmpro-memberpress-migration-toolkit' ); ?></label>
				<br />
				<select name="pmprompmt_migrate_stripe_gateway_id" id="pmprompmt_migrate_stripe_gateway_id">
					<option value=""><?php esc_html_e( 'Do not migrate Stripe subscriptions', 'pmpro-memberpress-migration-toolkit' ); ?></option>
					<?php
					foreach ( $stripe_gateways as $gateway ) {
						?>
						<option value="<?php echo esc_attr( $gateway['id'] ); ?>"><?php echo esc_html( 'Migrate from Gateway ID: ' . $gateway['id'] ); ?></option>
						<?php
					}
					?>
				</select>
				<br /><br />
				<?php
			}
			?>
			<div style="display: none;" id="pmpro_memberpress_migration_stripe_keys">
				<p><?php esc_html_e( 'Please provide your Stripe API keys that are associated with the MemberPress Stripe gateway you are migrating from. These keys are necessary to manage subscriptions in Paid Memberships Pro.', 'pmpro-memberpress-migration-toolkit' ); ?></p>
				<p><?php esc_html_e( 'With this setting enabled, customers with Stripe subscriptions will have their subscriptions migrated to Paid Memberships Pro. Customers with subscriptions managed by other gateways will be assigned a membership expiration date and will need to check out again to renew their membership.', 'pmpro-memberpress-migration-toolkit' ); ?></p>
				<?php
				// Allow changing the PMPro gateway environment and Stripe API keys.
				$current_gateway_environment = get_option( 'pmpro_gateway_environment', 'live' );
				?>
				<label for="pmpro_gateway_environment"><?php esc_html_e( 'PMPro Gateway Environment:', 'pmpro-memberpress-migration-toolkit' ); ?></label>
				<select name="pmpro_gateway_environment">
					<option value="live"<?php selected( $current_gateway_environment, 'live' ); ?>><?php esc_html_e( 'Live/Production', 'pmpro-memberpress-migration-toolkit' ); ?></option>
					<option value="sandbox"<?php selected( $current_gateway_environment, 'sandbox' ); ?>><?php esc_html_e( 'Sandbox/Testing', 'pmpro-memberpress-migration-toolkit' ); ?></option>
				</select>
				<br /><br />
				<label for="pmpro_stripe_publishablekey"><?php esc_html_e( 'Stripe Publishable Key:', 'pmpro-memberpress-migration-toolkit' ); ?></label>
				<input type="text" name="pmpro_stripe_publishablekey" value="<?php echo esc_attr( get_option( 'pmpro_stripe_publishablekey', '' ) ); ?>" />
				<br /><br />
				<label for="pmpro_stripe_secretkey"><?php esc_html_e( 'Stripe Secret Key:', 'pmpro-memberpress-migration-toolkit' ); ?></label>
				<input type="text" name="pmpro_stripe_secretkey" value="<?php echo esc_attr( get_option( 'pmpro_stripe_secretkey', '' ) ); ?>" />
			</div>
			<script type="text/javascript">
				jQuery(document).ready(function($) {
					function toggleStripeKeys() {
						// Handle both checkbox and select inputs.
						var gatewayId = $('#pmprompmt_migrate_stripe_gateway_id').is(':checkbox') ? ($('#pmprompmt_migrate_stripe_gateway_id').is(':checked') ? $('#pmprompmt_migrate_stripe_gateway_id').val() : '') : $('#pmprompmt_migrate_stripe_gateway_id').val();
						if (gatewayId) {
							$('#pmpro_memberpress_migration_stripe_keys').show();
						} else {
							$('#pmpro_memberpress_migration_stripe_keys').hide();
						}
					}
					$('#pmprompmt_migrate_stripe_gateway_id').change(function() {
						toggleStripeKeys();
					});
					toggleStripeKeys();
				});
			</script>
		</div>
		<button class="button button-primary" type="submit"><?php esc_html_e( 'Queue User Migrations', 'pmpro-memberpress-migration-toolkit' ); ?></button>
		<?php
	}

	/**
	 * Process the step.
	 */
	static public function process_step() {
		// Check if we need to migrate Stripe API keys.
		$migrate_stripe_gateway_id = empty( $_REQUEST['pmprompmt_migrate_stripe_gateway_id'] ) ? false : sanitize_text_field( wp_unslash( $_REQUEST['pmprompmt_migrate_stripe_gateway_id'] ) );
		if ( ! empty( $migrate_stripe_gateway_id ) ) {
			// Update gateway environment and Stripe API keys.
			update_option( 'pmpro_gateway', 'stripe' );
			update_option( 'pmpro_gateway_environment', sanitize_text_field( wp_unslash( $_REQUEST['pmpro_gateway_environment'] ) ) );
			update_option( 'pmpro_stripe_publishablekey', sanitize_text_field( wp_unslash( $_REQUEST['pmpro_stripe_publishablekey'] ) ) );
			update_option( 'pmpro_stripe_secretkey', sanitize_text_field( wp_unslash( $_REQUEST['pmpro_stripe_secretkey'] ) ) );

			// Set up webhook events as well.
			$stripe = new PMProGateway_stripe();
			$stripe->update_webhook_events();
		}

		// Queue up all users for migration.
		PMPro_Action_Scheduler::instance()->maybe_add_task(
			'pmprompmt_queue_user_migrations',
			array(
				'migrate_stripe_gateway_id' => $migrate_stripe_gateway_id,
			),
			'pmpro_async_tasks'
		);
	}
}
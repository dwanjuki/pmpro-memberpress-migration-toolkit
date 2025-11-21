<?php
/*
Plugin Name: Paid Memberships Pro - MemberPress Migration Toolkit Add On
Plugin URI: https://www.paidmembershipspro.com/add-ons/pmpro-memberpress-migration-toolkit-add-on/
Description: Quickly search Paid Memberships Pro admin pages for members, orders, subscriptions, and more.
Version: 0.1
Author: Paid Memberships Pro
Author URI: https://www.paidmembershipspro.com
Text Domain: pmpro-memberpress-migration-toolkit
Domain Path: /languages
*/

/**
 * Add a new admin page under the "Memberships" menu for the migration toolkit.
 *
 * @since TBD
 */
function pmprompmt_menu() {
	add_submenu_page(
		'pmpro-membershiplevels',
		'MemberPress Migration Toolkit',
		'MemberPress Migration Toolkit',
		'manage_options',
		'pmpro-memberpress-migration-toolkit',
		'pmprompmt_page'
	);
}
add_action( 'admin_menu', 'pmprompmt_menu' );

/**
 * Map MemberPress period types to PMPro cycle periods.
 *
 * @since TBD
 *
 * @param string $mepr_period_type MemberPress period type.
 * @return string PMPro cycle period.
 */
function pmprompmt_convert_period( $mepr_period_type ) {
	switch ( $mepr_period_type ) {
		case 'days':
			return 'Day';
		case 'weeks':
			return 'Week';
		case 'months':
			return 'Month';
		case 'years':
			return 'Year';
		default:
			return '';
	}
}

/**
 * Display the content of the MemberPress Migration Toolkit admin page.
 *
 * @since TBD
 */
function pmprompmt_page() {
	// Get all MemberPress levels mapped from post id to post object.
	$mp_levels = array();
	$mp_levels_query = new WP_Query(
		array(
			'post_type' => 'memberpressproduct',
			'posts_per_page' => -1,
			'orderby' => 'id',
			'order' => 'ASC',
		)
	);
	if ( $mp_levels_query->have_posts() ) {
		while ( $mp_levels_query->have_posts() ) {
			$mp_levels_query->the_post();
			$mp_levels[ get_the_ID() ] = get_post();
		}
		wp_reset_postdata();
	}

	// Get the current level mapping from options. MemberPress level ID => PMPro level ID.
	$level_map = get_option( 'pmprompmt_level_map', array() );

	// Process form submissions.
	if ( ! empty( $_REQUEST['pmprompmt_action'] ) ) {
		$action = sanitize_text_field( wp_unslash( $_REQUEST['pmprompmt_action'] ) );
		if ( 'migrate_levels' === $action ) {
			// Check the nonce.
			check_admin_referer( 'pmpro_memberpress_migration_toolkit_migrate_levels', 'pmpro_memberpress_migration_toolkit_migrate_levels_nonce' );

			// If the level map is not empty, do not run the migration again.
			if ( ! empty( $level_map ) ) {
				wp_die( esc_html__( 'Level migration has already been completed. To re-run the migration, please clear the existing level map first.', 'pmpro-memberpress-migration-toolkit' ) );
			}

			// Migrate all MemberPress levels to PMPro levels.
			foreach ( $mp_levels as $mp_level_id => $mp_level ) {
				$new_pmpro_level = new PMPro_Membership_Level();
				$new_pmpro_level->get_empty_membership_level();
				$new_pmpro_level->name = $mp_level->post_title;
				$new_pmpro_level->description = $mp_level->post_content;
				$new_pmpro_level->allow_signups = 1; // Default to allowing signups.
				$new_pmpro_level->initial_payment = get_post_meta( $mp_level_id, '_mepr_product_price', true );
				
				// Handle recurring payment settings.
				$period = get_post_meta( $mp_level_id, '_mepr_product_period_type', true );
				if ( 'lifetime' !== $period ) {
					$new_pmpro_level->billing_amount = $new_pmpro_level->initial_payment; // Using the same as initial payment as a different amount would be considered a MemberPress "trial".
					$new_pmpro_level->cycle_number = get_post_meta( $mp_level_id, '_mepr_product_period', true );
					$new_pmpro_level->cycle_period = pmprompmt_convert_period( $period );
				}

				// TODO: Consider handling these fields in the future.
				//$new_pmpro_level->billing_limit = ...
				//$new_pmpro_level->trial_amount = ...
				//$new_pmpro_level->trial_limit = ...
				//$new_pmpro_level->expiration_number = ...
				//$new_pmpro_level->expiration_period = ...
				$new_pmpro_level->save();

				// Store the mapping.
				$level_map[ $mp_level_id ] = $new_pmpro_level->id;
			}

			update_option( 'pmprompmt_level_map', $level_map );

			// Migrate MemberPress level groups to PMPro level groups.
			$mp_level_groups = new WP_Query(
				array(
					'post_type' => 'memberpressgroup',
					'posts_per_page' => -1,
					'orderby' => 'id',
					'order' => 'ASC',
				)
			);
			if ( $mp_level_groups->have_posts() ) {
				while ( $mp_level_groups->have_posts() ) {
					// Create the PMPro level group.
					$mp_level_groups->the_post();
					$group_name = get_the_title();
					$allow_multi = get_post_meta( get_the_ID(), '_mepr_group_is_upgrade_path', true ) ? 0 : 1;
					$new_level_group_id = pmpro_create_level_group( $group_name, $allow_multi );

					// Add levels to the PMPro level group.
					// Get all MemberPress levels where _mepr_group_id matches the current group ID.
					$group_id = get_the_ID();
					wp_reset_postdata();
					$group_levels_query = new WP_Query(
						array(
							'post_type' => 'memberpressproduct',
							'posts_per_page' => -1,
							'meta_query' => array(
								array(
									'key' => '_mepr_group_id',
									'value' => $group_id,
									'compare' => '=',
								),
							),
						)
					);
					if ( $group_levels_query->have_posts() ) {
						while ( $group_levels_query->have_posts() ) {
							$group_levels_query->the_post();
							$mp_level_id = get_the_ID();
							// Check if this MemberPress level was migrated to PMPro.
							if ( ! empty( $level_map[ $mp_level_id ] ) ) {
								$pmpro_level_id = $level_map[ $mp_level_id ];
								// Add the PMPro level to the new level group.
								pmpro_add_level_to_group( $pmpro_level_id, $new_level_group_id );
							}
						}
						wp_reset_postdata();
					}
				}
			}

			// Break the PMPro levels cache to reflect new levels.
			pmpro_getAllLevels( true, true, true );
		} elseif ( 'save_level_map' === $action ) {
			// Check the nonce.
			check_admin_referer( 'pmpro_memberpress_migration_toolkit_save_level_map', 'pmpro_memberpress_migration_toolkit_save_level_map_nonce' );

			// Save the manually submitted level mapping.
			$new_level_map = array();
			if ( ! empty( $_REQUEST['pmpro_mp_level_map'] ) && is_array( $_REQUEST['pmpro_mp_level_map'] ) ) {
				foreach ( $_REQUEST['pmpro_mp_level_map'] as $mp_level_id => $pmpro_level_id ) {
					$mp_level_id = intval( $mp_level_id );
					$pmpro_level_id = intval( $pmpro_level_id );
					if ( $mp_level_id > 0 && $pmpro_level_id > 0 ) {
						$new_level_map[ $mp_level_id ] = $pmpro_level_id;
					}
				}
			}
			update_option( 'pmprompmt_level_map', $new_level_map );
			$level_map = $new_level_map;
		} elseif ( 'queue_user_migrations' === $action ) {
			// Check the nonce.
			check_admin_referer( 'pmpro_memberpress_migration_toolkit_queue_user_migrations', 'pmpro_memberpress_migration_toolkit_queue_user_migrations_nonce' );

			// Check if we need to migrate Stripe API keys.
			$migrate_stripe_gateway_id = empty( $_REQUEST['pmprompmt_migrate_stripe_gateway_id'] ) ? false : sanitize_text_field( wp_unslash( $_REQUEST['pmprompmt_migrate_stripe_gateway_id'] ) );
			if ( ! empty( $migrate_stripe_gateway_id ) ) {
				// Migrate Stripe API keys from MemberPress to PMPro.
				$mp_options = get_option( 'mepr_options', array() );
				if (
					! empty( $mp_options['integrations'][$migrate_stripe_gateway_id]['api_keys']['live']['public'] )
					&& ! empty( $mp_options['integrations'][$migrate_stripe_gateway_id]['api_keys']['live']['secret'] )
				) {
					// Save Stripe API keys to PMPro options.
					update_option( 'pmpro_stripe_publishablekey', $mp_options['integrations'][$migrate_stripe_gateway_id]['api_keys']['live']['public'] );
					update_option( 'pmpro_stripe_secretkey', $mp_options['integrations'][$migrate_stripe_gateway_id]['api_keys']['live']['secret'] );
					update_option( 'pmpro_gateway', 'stripe' );
					update_option( 'pmpro_gateway_environment', 'live' );

					// Set up webhook events as well.
					$stripe = new PMProGateway_stripe();
					$stripe->update_webhook_events();
				}
			}

			// Queue up all users for migration.
			PMPro_Action_Scheduler::instance()->maybe_add_task(
				'pmprompmt_queue_user_migrations',
				array(
					'migrate_stripe_gateway_id' => $migrate_stripe_gateway_id,
				),
				'pmpro_async_tasks'
			);
		} elseif ( 'queue_content_restriction_migrations' === $action ) {
			// Check the nonce.
			check_admin_referer( 'pmpro_memberpress_migration_toolkit_migrate_content_restrictions', 'pmpro_memberpress_migration_toolkit_migrate_content_restrictions_nonce' );

			// Queue up all content restriction migrations.
			PMPro_Action_Scheduler::instance()->maybe_add_task(
				'pmprompmt_queue_content_restriction_migrations',
				array(),
				'pmpro_async_tasks'
			);
		} elseif ( 'migrate_other_settings' === $action ) {
			// Check the nonce.
			check_admin_referer( 'pmpro_memberpress_migration_toolkit_migrate_other_settings', 'pmpro_memberpress_migration_toolkit_migrate_other_settings_nonce' );

			// Create membership pages if no pages are assigned.
			global $pmpro_pages;
			if ( empty( $pmpro_pages['account'] ) &&
				 empty( $pmpro_pages['billing'] ) &&
				 empty( $pmpro_pages['cancel'] ) &&
				 empty( $pmpro_pages['checkout'] ) &&
				 empty( $pmpro_pages['confirmation'] ) &&
				 empty( $pmpro_pages['invoice'] ) &&
				 empty( $pmpro_pages['levels'] ) &&
				 empty( $pmpro_pages['member_profile_edit'] ) ) {
				$pages = array();
				$pages['account']             = __( 'Membership Account', 'pmpro-memberpress-migration-toolkit' );
				$pages['billing']             = __( 'Membership Billing', 'pmpro-memberpress-migration-toolkit' );
				$pages['cancel']              = __( 'Membership Cancel', 'pmpro-memberpress-migration-toolkit' );
				$pages['checkout']            = __( 'Membership Checkout', 'pmpro-memberpress-migration-toolkit' );
				$pages['confirmation']        = __( 'Membership Confirmation', 'pmpro-memberpress-migration-toolkit' );
				$pages['invoice']             = __( 'Membership Orders', 'pmpro-memberpress-migration-toolkit' );
				$pages['levels']              = __( 'Membership Levels', 'pmpro-memberpress-migration-toolkit' );
				$pages['login']               = __( 'Log In', 'pmpro-memberpress-migration-toolkit' );
				$pages['member_profile_edit'] = __( 'Your Profile', 'pmpro-memberpress-migration-toolkit' );
				pmpro_generatePages( $pages );
			}

			// Migrate currency.
			$mp_options = get_option( 'mepr_options', array() );
			if ( ! empty( $mp_options['currency_code'] ) ) {
				update_option( 'pmpro_currency', $mp_options['currency_code'] );
			}

			// Migrate business address.
			update_option( 'pmpro_business_address', array(
				'name'    => get_option( 'mepr_biz_name', '' ),
				'street'  => get_option( 'mepr_biz_address1', '' ),
				'street2' => get_option( 'mepr_biz_address2', '' ),
				'city'    => get_option( 'mepr_biz_city', '' ),
				'state'   => get_option( 'mepr_biz_state', '' ),
				'zip'     => get_option( 'mepr_biz_postcode', '' ),
				'country' => get_option( 'mepr_biz_country', '' ),
				'phone'   => ''
			));

			// Migrate custom user fields.
			var_dump($mp_options['custom_fields'] );
			$pmpro_user_field_group = new stdClass();
			$pmpro_user_field_group->name = __( 'More Information', 'pmpro-memberpress-migration-toolkit' );
			$pmpro_user_field_group->checkout = 'yes';
			$pmpro_user_field_group->profile = 'yes';
			$pmpro_user_field_group->description = '';
			$pmpro_user_field_group->levels = array();
			$pmpro_user_field_group->fields = array();
			foreach( $mp_options['custom_fields'] as $cf ) {
				$field = new stdClass();
				$field->name = $cf['field_key'];
				$field->label = $cf['field_name'];
				$field->type = 'text'; // TODO: Map other field types.
				$field->required = empty( $cf['required'] ) ? 'no' : 'yes';
				$field->readonly = 'no';
				$field->profile = empty( $cf['show_in_account'] ) ? 'admins' : 'yes'; // Note: We don't have great control over showing the field at checkout. If we ever do, we can update this.
				$field->wrapper_class = '';
				$field->element_class = '';
				$field->hint = '';
				$field->options = ''; // TODO: Handle options for select, radio, checkbox fields.
				if ( ! empty( $cf['options'] ) ) {
					
				}
				$pmpro_user_field_group->fields[] = $field;
			}
			update_option( 'pmpro_user_fields_settings', array( $pmpro_user_field_group ), false );
		}
	}


	?>
	<div class="wrap pmpro_admin">
		<h1><?php esc_html_e( 'MemberPress Migration Toolkit', 'pmpro-memberpress-migration-toolkit' ); ?></h1>
		<p><?php esc_html_e( 'This toolkit provides scripts and tools to help you migrate your membership data from MemberPress to Paid Memberships Pro.', 'pmpro-memberpress-migration-toolkit' ); ?></p>
		<p><?php printf( esc_html__( 'Please follow the steps outlined in our %s to ensure a smooth transition.', 'pmpro-memberpress-migration-toolkit' ), '<a href="https://www.paidmembershipspro.com/migrate-memberpress-to-paid-memberships-pro/" target="_blank">' . esc_html__( 'migration guide', 'pmpro-memberpress-migration-toolkit' ) . '</a>' ); ?></p>

		<div class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'Activate License Key', 'pmpro-memberpress-migration-toolkit' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<form method="post" action="">
					<p><?php esc_html_e( 'To install premium Add Ons and receive support for the MemberPress Migration Toolkit, please enter your Paid Memberships Pro license key below.', 'pmpro-memberpress-migration-toolkit' ); ?></p>
					<input type="text" name="pmpro_memberpress_migration_toolkit_license_key" value="<?php echo esc_attr( get_option( 'pmpro_memberpress_migration_toolkit_license_key', '' ) ); ?>" class="pmpro-wizard__field-block" />
					<?php wp_nonce_field( 'pmpro_memberpress_migration_toolkit_activate_license', 'pmpro_memberpress_migration_toolkit_activate_license_nonce' ); ?>
					<button class="button button-primary" type="submit"><?php esc_html_e( 'Activate License', 'pmpro-memberpress-migration-toolkit' ); ?></button>
				</form>
			</div>
		</div>

		<div class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'Migrate Membership Levels', 'pmpro-memberpress-migration-toolkit' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<?php
				// If there are MemberPress levels but no mapping yet, show a button to run the full migration.
				if ( ! empty( $mp_levels ) && empty( $level_map ) ) {
					?>
					<form method="post" action="">
						<p><?php esc_html_e( 'The following data will NOT be migrated automatically and will need to be set up manually after the levels have been created:', 'pmpro-memberpress-migration-toolkit' ); ?></p>
						<ul>
							<li><?php esc_html_e( 'Expiration Dates' ); ?></li>
							<li><?php esc_html_e( 'Trials' ); ?></li>
							<li><?php esc_html_e( 'Limited Payment Cycles' ); ?></li>
						</ul>
						<input type="hidden" name="pmprompmt_action" value="migrate_levels" />
						<?php wp_nonce_field( 'pmpro_memberpress_migration_toolkit_migrate_levels', 'pmpro_memberpress_migration_toolkit_migrate_levels_nonce' ); ?>
						<button class="button button-primary" type="submit"><?php esc_html_e( 'Migrate All Levels And Level Groups Now', 'pmpro-memberpress-migration-toolkit' ); ?></button>
					</form>
					<hr />
					<a href="#" id="pmpro_memberpress_migration_show_manual_level_mapping"><?php esc_html_e( 'Or, map levels manually', 'pmpro-memberpress-migration-toolkit' ); ?></a>
					<script type="text/javascript">
						jQuery(document).ready(function($) {
							$('#pmpro_memberpress_migration_show_manual_level_mapping').click(function(e) {
								e.preventDefault();
								$('#pmpro_memberpress_migration_level_mapping_div').show();
								$(this).hide();
							});
						});
					</script>
					<?php
				}

				// Show a manual level mapping form if there are MemberPress levels.
				?>
				<div id='pmpro_memberpress_migration_level_mapping_div' style='<?php echo empty( $mp_levels ) || empty( $level_map ) ? 'display:none;' : ''; ?>'>
					<form method="post" action="">
						<input type="hidden" name="pmprompmt_action" value="save_level_map" />
						<table class="form-table">
							<tr>
								<th scope="row"><?php esc_html_e( 'MemberPress Level', 'pmpro-memberpress-migration-toolkit' ); ?></th>
								<th scope="row"><?php esc_html_e( 'Map to PMPro Level', 'pmpro-memberpress-migration-toolkit' ); ?></th>
							</tr>
							<?php
							// Get all PMPro levels.
							$pmpro_levels = pmpro_getAllLevels( true );

							// Loop through MemberPress levels and show a dropdown to map to PMPro levels.
							if ( ! empty( $mp_levels ) ) {
								foreach ( $mp_levels as $mp_level_id => $mp_level ) {
									$mp_level_name = $mp_level->post_title;
									?>
									<tr>
										<td><?php echo esc_html( $mp_level_name ); ?></td>
										<td>
											<select name="pmpro_mp_level_map[<?php echo esc_attr( $mp_level_id ); ?>]">
												<option value=""><?php esc_html_e( 'Select PMPro Level', 'pmpro-memberpress-migration-toolkit' ); ?></option>
												<?php
												foreach ( $pmpro_levels as $level ) {
													?>
													<option value="<?php echo esc_attr( $level->id ); ?>" <?php selected( isset( $level_map[ $mp_level_id ] ) && $level_map[ $mp_level_id ] == $level->id ); ?>>
														<?php echo esc_html( $level->name ); ?>
													</option>
													<?php
												}
												?>
											</select>
										</td>
									</tr>
									<?php
								}
							} else {
								?>
								<tr>
									<td colspan="2"><?php esc_html_e( 'No MemberPress levels found.', 'pmpro-memberpress-migration-toolkit' ); ?></td>
								</tr>
								<?php
							}
							?>
						</table>
						<?php wp_nonce_field( 'pmpro_memberpress_migration_toolkit_save_level_map', 'pmpro_memberpress_migration_toolkit_save_level_map_nonce' ); ?>
						<button class="button button-primary" type="submit"><?php esc_html_e( 'Save Level Map', 'pmpro-memberpress-migration-toolkit' ); ?></button>
					</form>
				</div>
			</div>
		</div>

		<div class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'Migrate User Data', 'pmpro-memberpress-migration-toolkit' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<?php
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
				<p><?php esc_html_e( 'Once you are ready, click the button below to queue up all users for migration from MemberPress to Paid Memberships Pro. This process will run in the background using Action Scheduler.', 'pmpro-memberpress-migration-toolkit' ); ?></p>
				<form method="post" action="">
					<input type="hidden" name="pmprompmt_action" value="queue_user_migrations" />
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
							<?php esc_html_e( 'Migrate Stripe API keys and subscriptions to PMPro', 'pmpro-memberpress-migration-toolkit' ); ?>
						</label>
						<br /><br />
						<?php
					} elseif ( count( $stripe_gateways ) > 1 ) {
						?>
						<label for="pmprompmt_migrate_stripe_gateway_id"><?php esc_html_e( 'Keep existing Stripe subscriptions active (do not cancel in MemberPress):', 'pmpro-memberpress-migration-toolkit' ); ?></label>
						<br />
						<select name="pmprompmt_migrate_stripe_gateway_id" id="pmprompmt_migrate_stripe_gateway_id">
							<option value=""><?php esc_html_e( 'Do not migrate Stripe API keys or subscriptions', 'pmpro-memberpress-migration-toolkit' ); ?></option>
							<?php
							foreach ( $stripe_gateways as $gateway ) {
								?>
								<option value="<?php echo esc_attr( $gateway['id'] ); ?>"><?php echo esc_html( 'Gateway ID: ' . $gateway['id'] ); ?></option>
								<?php
							}
							?>
						</select>
						<br /><br />
						<?php
					}
					?>
					<?php wp_nonce_field( 'pmpro_memberpress_migration_toolkit_queue_user_migrations', 'pmpro_memberpress_migration_toolkit_queue_user_migrations_nonce' ); ?>
					<button class="button button-primary" type="submit"><?php esc_html_e( 'Queue User Migrations', 'pmpro-memberpress-migration-toolkit' ); ?></button>
				</form>
			</div>
		</div>

		<div class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'Migrate Content Restrictions', 'pmpro-memberpress-migration-toolkit' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<p><?php esc_html_e( 'Only membership-based content restrictions can be migrated from MemberPress to Paid Memberships Pro. Content restrictions based on other criteria (such as roles, capabilities, or specific users) will need to be set up manually after the migration.', 'pmpro-memberpress-migration-toolkit' ); ?></p>
				<form method="post" action="">
					<input type="hidden" name="pmprompmt_action" value="queue_content_restriction_migrations" />
					<?php wp_nonce_field( 'pmpro_memberpress_migration_toolkit_migrate_content_restrictions', 'pmpro_memberpress_migration_toolkit_migrate_content_restrictions_nonce' ); ?>
					<button class="button button-primary" type="submit"><?php esc_html_e( 'Queue Content Restriction Migrations', 'pmpro-memberpress-migration-toolkit' ); ?></button>
				</form>
			</div>
		</div>

		<div class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'Migrate Other Settings', 'pmpro-memberpress-migration-toolkit' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<p><?php esc_html_e( 'The following PMPro settings will be automatically configured:', 'pmpro-memberpress-migration-toolkit' ); ?></p>
				<ul>
					<li><?php esc_html_e( 'Membership Pages', 'pmpro-memberpress-migration-toolkit' ); ?></li>
					<li><?php esc_html_e( 'Payment Currency', 'pmpro-memberpress-migration-toolkit' ); ?></li>
					<li><?php esc_html_e( 'Business Address', 'pmpro-memberpress-migration-toolkit' ); ?></li>
					<li><?php esc_html_e( 'User Fields', 'pmpro-memberpress-migration-toolkit' ); ?></li>
				</ul>
				<form method="post" action="">
					<input type="hidden" name="pmprompmt_action" value="migrate_other_settings" />
					<?php wp_nonce_field( 'pmpro_memberpress_migration_toolkit_migrate_other_settings', 'pmpro_memberpress_migration_toolkit_migrate_other_settings_nonce' ); ?>
					<button class="button button-primary" type="submit"><?php esc_html_e( 'Migrate Other Settings', 'pmpro-memberpress-migration-toolkit' ); ?></button>
				</form>
			</div>
		</div>

		<div class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'Final Steps', 'pmpro-memberpress-migration-toolkit' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<p><?php esc_html_e( 'After completing the migration, please review the following items to ensure everything is set up correctly:', 'pmpro-memberpress-migration-toolkit' ); ?></p>
				<ul>
					<li><?php esc_html_e( 'Review and adjust membership levels as needed.', 'pmpro-memberpress-migration-toolkit' ); ?></li>
					<li><?php esc_html_e( 'Verify that all user memberships and orders have been migrated correctly.', 'pmpro-memberpress-migration-toolkit' ); ?></li>
					<li><?php esc_html_e( 'Test the checkout process to ensure payments are processed correctly.', 'pmpro-memberpress-migration-toolkit' ); ?></li>
					<li><?php esc_html_e( 'Set up any additional Add Ons or integrations as needed.', 'pmpro-memberpress-migration-toolkit' ); ?></li>
				</ul>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Action Scheduler function to queue up all users for migration from MemberPress to PMPro.
 */
function pmprompmt_queue_user_migrations( $migrate_stripe_gateway_id = false ) {
	global $wpdb;

	// Get an array of all user IDs.
	$user_ids = $wpdb->get_col( "SELECT ID FROM $wpdb->users" );

	if ( count( $user_ids ) > 250 ) {
			PMPro_Action_Scheduler::instance()->halt();
		}

	foreach ( $user_ids as $user_id ) {
		PMPro_Action_Scheduler::instance()->maybe_add_task(
			'pmprompmt_migrate_user',
			array(
				'user_id' => $user_id,
				'migrate_stripe_gateway_id' => $migrate_stripe_gateway_id,
			),
			'pmpro_async_tasks'
		);
	}

	// If we paused the Action Scheduler, unpause it now.
	PMPro_Action_Scheduler::instance()->resume();
}
add_action( 'pmprompmt_queue_user_migrations', 'pmprompmt_queue_user_migrations', 10, 1 );

/**
 * Action Scheduler function to migrate a single MemberPress member to PMPro.
 */
function pmprompmt_migrate_user( $user_id, $migrate_stripe_gateway_id = false ) {
	global $wpdb;

	// Validate user ID.
	$user_id = intval( $user_id );
	if ( $user_id <= 0 ) {
		return;
	}

	// All the data that we need is stored in mepr_transactions.
	// We want to:
	// 1. Migrate every transaction to a PMPro order,
	// 2. Keep a record of any level IDs and expiration dates that the user needs to be given memberships for.
	$table_name = $wpdb->prefix . 'mepr_transactions';
	$mp_transactions = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at ASC", $user_id ) );
	if ( ! empty( $mp_transactions ) ) {
		$level_map = get_option( 'pmprompmt_level_map', array() );
		$levels_to_add = array(); // level_id => associative array with level data.
		
		foreach ( $mp_transactions as $transaction ) {
			// Create a PMPro order for this transaction.
			$order = new MemberOrder();
			$order->user_id = $transaction->user_id;
			$order->membership_id = ! empty( $level_map[ $transaction->product_id ] ) ? $level_map[ $transaction->product_id ] : 0;
			$order->payment_transaction_id = $transaction->trans_num;
			$order->timestamp = strtotime( $transaction->created_at );
			$order->total = $transaction->total;
			$order->subtotal = $transaction->amount;
			$order->tax = $transaction->tax_amount;
			$order->notes = 'Migrated from MemberPress Transaction ID ' . $transaction->id;
			switch ( $transaction->status ) {
				case 'complete':
				case 'confirmed':
					$order->status = 'success';
					break;
				case 'failed':
					$order->status = 'error';
					break;
				default:
					$order->status = $transaction->status;
					break;
			}
			if (
				! empty( $migrate_stripe_gateway_id ) &&
				$transaction->gateway == $migrate_stripe_gateway_id &&
				in_array( $transaction->status, array( 'complete', 'confirmed' ), true )
			) {
				// This transaction was made via Stripe and we are migrating Stripe API keys.
				$order->gateway = 'stripe';

				// Check if this transaction is part of a subscription.
				if ( ! empty( $transaction->subscription_id ) ) {
					// Get the subscription transaction ID for this transaction.
					$subscription_id = $wpdb->get_var( $wpdb->prepare( "SELECT subscr_id FROM {$wpdb->prefix}mepr_subscriptions WHERE id = %d AND status = 'active' LIMIT 1", $transaction->subscription_id ) );
					if ( ! empty( $subscription_id ) ) {
						$order->gateway = 'stripe';
						$order->subscription_transaction_id = $subscription_id;

						// Let's also remove the `expires_at` to avoid PMPro auto-expiring the membership.
						$transaction->expires_at = null;
					}
				}
			}
			$order->saveOrder();

			// Maybe add this level to the user.
			if ( ! empty( $level_map[ $transaction->product_id ] ) && in_array( $transaction->status, array( 'complete', 'confirmed' ), true ) ) {
				$pmpro_level_id = $level_map[ $transaction->product_id ];
				if ( empty( $levels_to_add[ $pmpro_level_id ] ) ) {
					$levels_to_add[ $pmpro_level_id ] = array(
						'startdate' => $transaction->created_at,
						'enddate'   => $transaction->expires_at,
					);
				} else {
					// If we already have this level, check if this transaction has a later expiration date.
					if ( empty( $transaction->expires_at ) || strtotime( $transaction->expires_at ) > strtotime( $levels_to_add[ $pmpro_level_id ]['enddate'] ) ) {
						$levels_to_add[ $pmpro_level_id ]['enddate'] = $transaction->expires_at;
					}
					// If this transaction has an earlier start date, update it.
					if ( empty( $levels_to_add[ $pmpro_level_id ]['startdate'] ) || strtotime( $transaction->created_at ) < strtotime( $levels_to_add[ $pmpro_level_id ]['startdate'] ) ) {
						$levels_to_add[ $pmpro_level_id ]['startdate'] = $transaction->created_at;
					}
				}
			}
		}

		// Now give the user any levels that they need.
		foreach ( $levels_to_add as $pmpro_level_id => $level_data ) {
			$custom_level = array(
				'user_id'         => $user_id,
				'membership_id'   => $pmpro_level_id,
				'code_id'         => '',
				'initial_payment' => 0,
				'billing_amount'  => 0,
				'cycle_number'    => 0,
				'cycle_period'    => 'month',
				'billing_limit'   => 0,
				'trial_amount'    => 0,
				'trial_limit'     => 0,
				'startdate'       => $level_data['startdate'],
				'enddate'         => $level_data['enddate']
			);
			pmpro_changeMembershipLevel( $custom_level, $user_id );
		}
	}
}
add_action( 'pmprompmt_migrate_user', 'pmprompmt_migrate_user', 10, 2 );

/**
 * Action Scheduler function to queue up content restriction migrations.
 */
function pmprompmt_queue_content_restriction_migrations() {
	global $wpdb;

	// Since we can only migrate membership-based content restrictions, let's build our list of rules to migrate by querying mepr_rule_access_conditions
	// for all unique rule IDs where access_type is 'membership'.
	$table_name = $wpdb->prefix . 'mepr_rule_access_conditions';
	$rule_ids = $wpdb->get_col( "SELECT DISTINCT rule_id FROM $table_name WHERE access_type = 'membership'" );

	foreach ( $rule_ids as $rule_id ) {
		PMPro_Action_Scheduler::instance()->maybe_add_task(
			'pmprompmt_migrate_content_restriction',
			array(
				'rule_id' => $rule_id,
			),
			'pmpro_async_tasks'
		);
	}
}
add_action( 'pmprompmt_queue_content_restriction_migrations', 'pmprompmt_queue_content_restriction_migrations' );

/**
 * Action Scheduler function to migrate a single content restriction rule from MemberPress to PMPro.
 */
function pmprompmt_migrate_content_restriction( $rule_id ) {
	// First, let's get the MemberPress product IDs that are associated with this rule.
	global $wpdb, $pmpro_pages;
	$table_name = $wpdb->prefix . 'mepr_rule_access_conditions';
	$mp_product_ids = $wpdb->get_col( $wpdb->prepare( "SELECT access_condition FROM $table_name WHERE rule_id = %d AND access_type = 'membership'", $rule_id ) );
	if ( empty( $mp_product_ids ) ) {
		return;
	}

	// Get the level mapping.
	$level_map = get_option( 'pmprompmt_level_map', array() );
	$pmpro_level_ids = array();
	foreach ( $mp_product_ids as $mp_product_id ) {
		if ( ! empty( $level_map[ $mp_product_id ] ) ) {
			$pmpro_level_ids[] = $level_map[ $mp_product_id ];
		}
	}
	if ( empty( $pmpro_level_ids ) ) {
		return;
	}

	// Now get the content that this rule applies to.
	$rule_type = get_post_meta( $rule_id, '_mepr_rules_type', true );
	$rule_content = get_post_meta( $rule_id, '_mepr_rules_content', true );

	switch( $rule_type ) {
		case 'single_page':
		case 'single_post':
			// Get the current PMPro restriction for this post/page.
			foreach( $pmpro_level_ids as $pmpro_level_id ) {
				$wpdb->insert(
					$wpdb->prefix . 'pmpro_memberships_pages',
					array(
						'page_id'        => intval( $rule_content ),
						'membership_id' => intval( $pmpro_level_id ),
					),
					array(
						'%d',
						'%d',
					)
				);
			}
			break;
		case 'all_posts':
			// Run a single query to update all posts.
			foreach( $pmpro_level_ids as $pmpro_level_id ) {
				$wpdb->query(
					$wpdb->prepare(
						"INSERT IGNORE INTO {$wpdb->prefix}pmpro_memberships_pages (page_id, membership_id)
						SELECT ID, %d FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish'",
						intval( $pmpro_level_id )
					)
				);
			}
			break;
		case 'all_pages':
			// Run a single query to update all pages.
			foreach( $pmpro_level_ids as $pmpro_level_id ) {
				$wpdb->query(
					$wpdb->prepare(
						"INSERT IGNORE INTO {$wpdb->prefix}pmpro_memberships_pages (page_id, membership_id)
						SELECT ID, %d FROM {$wpdb->posts} WHERE post_type = 'page' AND post_status = 'publish'",
						intval( $pmpro_level_id )
					)
				);
			}

			// Make sure that no PMPro pages are restricted.
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->prefix}pmpro_memberships_pages
					WHERE page_id IN (%s)",
					implode( ',', array_map( 'intval', $pmpro_pages ) )
				)
			);
			break;
		case 'all':
			// Run a single query to update all posts and pages.
			foreach( $pmpro_level_ids as $pmpro_level_id ) {
				$wpdb->query(
					$wpdb->prepare(
						"INSERT IGNORE INTO {$wpdb->prefix}pmpro_memberships_pages (page_id, membership_id)
						SELECT ID, %d FROM {$wpdb->posts} WHERE (post_type = 'post' OR post_type = 'page') AND post_status = 'publish'",
						intval( $pmpro_level_id )
					)
				);
			}

			// Make sure that no PMPro pages are restricted.
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->prefix}pmpro_memberships_pages
					WHERE page_id IN (%s)",
					implode( ',', array_map( 'intval', $pmpro_pages ) )
				)
			);
			break;
		case 'all_tax_category':
		case 'all_tax_post_tag':
		case 'category':
		case 'tag':
			// For taxonomy restrictions, we're going to instead update the pmpro_memberships_categories table.
			foreach( $pmpro_level_ids as $pmpro_level_id ) {
				$wpdb->insert(
					$wpdb->prefix . 'pmpro_memberships_categories',
					array(
						'membership_id' => intval( $pmpro_level_id ),
						'category_id'   => intval( $rule_content ),
					),
					array(
						'%d',
						'%d',
					)
				);
			}
			break;
		case 'parent_page':
			// Get all child pages of the specified parent page.
			$child_pages = get_pages( array( 'child_of' => intval( $rule_content ), 'post_status' => 'publish' ) );
			if ( ! empty( $child_pages ) ) {
				foreach ( $child_pages as $child_page ) {
					foreach( $pmpro_level_ids as $pmpro_level_id ) {
						$wpdb->insert(
							$wpdb->prefix . 'pmpro_memberships_pages',
							array(
								'page_id'        => intval( $child_page->ID ),
								'membership_id' => intval( $pmpro_level_id ),
							),
							array(
								'%d',
								'%d',
							)
						);
					}
				}
			}
			break;
		// Rules that we don't support:
		case 'all_tax_mepr-product-category':
		case 'all_memberpressgroup':
		case 'single_memberpressgroup':
		case 'parent_memberpressgroup':
		case 'partial':
		case 'custom':
		default:
			break;
	}
}
add_action( 'pmprompmt_migrate_content_restriction', 'pmprompmt_migrate_content_restriction', 10, 1 );
<?php
class PMProMPMT_Migration_Step_Levels extends PMProMPMT_Migration_Step {
	/**
	 * Get the step slug.
	 *
	 * @return string The step slug.
	 */
	static public function get_step_slug() {
		return 'levels';
	}

	/**
	 * Get the step name. This will be displayed in the header of the step.
	 *
	 * @return string The step name.
	 */
	static public function get_step_name() {
		return esc_html__( 'Migrate Membership Levels', 'pmpro-memberpress-migration-toolkit' );
	}

	/**
	 * Get the status of the step.
	 *
	 * @return string The status of the step. Possible values: 'not_started', 'in_progress', 'completed'.
	 */
	static public function get_step_status() {
		return empty( get_option( 'pmprompmt_level_map', array() ) ) ? 'not_started' : 'completed';
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

		// If there are MemberPress levels but no mapping yet, show a button to run the full migration.
		if ( ! empty( $mp_levels ) && empty( $level_map ) ) {
			?>
			<h4><?php esc_html_e( 'What Will Be Migrated', 'pmpro-memberpress-migration-toolkit' ); ?></h4>
			<ul>
				<li><?php esc_html_e( 'Membership level names and descriptions', 'pmpro-memberpress-migration-toolkit' ); ?></li>
				<li><?php esc_html_e( 'Initial payment amounts (price)', 'pmpro-memberpress-migration-toolkit' ); ?></li>
				<li><?php esc_html_e( 'Recurring payment settings (billing amount, cycle number, cycle period)', 'pmpro-memberpress-migration-toolkit' ); ?></li>
				<li><?php esc_html_e( 'MemberPress level groups converted to PMPro level groups', 'pmpro-memberpress-migration-toolkit' ); ?></li>
				<li><?php esc_html_e( 'Upgrade/downgrade path settings', 'pmpro-memberpress-migration-toolkit' ); ?></li>
			</ul>

			<h4><?php esc_html_e( 'What Will NOT Be Migrated', 'pmpro-memberpress-migration-toolkit' ); ?></h4>
			<ul>
				<li>
					<strong><?php esc_html_e( 'Expiration Dates/Membership Duration:', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
					<?php esc_html_e( 'Configure in PMPro level settings under "Membership Expiration".', 'pmpro-memberpress-migration-toolkit' ); ?>
				</li>
				<li>
					<strong><?php esc_html_e( 'Trial Pricing and Trial Periods:', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
					<?php esc_html_e( 'Configure in PMPro level settings under "Recurring Subscription".', 'pmpro-memberpress-migration-toolkit' ); ?>
				</li>
				<li>
					<strong><?php esc_html_e( 'Limited Payment Cycles (Billing Limits):', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
					<?php esc_html_e( 'Set in PMPro level settings under "Billing Cycle Limit".', 'pmpro-memberpress-migration-toolkit' ); ?>
				</li>
				<li>
					<strong><?php esc_html_e( 'Upgrade/Downgrade Paths', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
				</li>
				<li>
					<strong><?php esc_html_e( 'Custom Levels Page CSS/Styling', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
				</li>
			</ul>

			<h4><?php esc_html_e( 'After Migration', 'pmpro-memberpress-migration-toolkit' ); ?></h4>
			<p>
				<?php
				printf(
					/* translators: %s: Link to PMPro membership levels admin page */
					esc_html__( 'Review your levels at %s to configure expiration, trials, and billing limits.', 'pmpro-memberpress-migration-toolkit' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=pmpro-membershiplevels' ) ) . '">' . esc_html__( 'Memberships > Settings > Levels', 'pmpro-memberpress-migration-toolkit' ) . '</a>'
				);
				?>
			</p>

			<?php wp_nonce_field( 'pmpro_memberpress_migration_toolkit_migrate_levels', 'pmpro_memberpress_migration_toolkit_migrate_levels_nonce' ); ?>
			<button class="button button-primary" type="submit" name="level-step-action" value="migrate_levels"><?php esc_html_e( 'Migrate All Levels And Level Groups Now', 'pmpro-memberpress-migration-toolkit' ); ?></button>
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
				<button class="button button-primary" type="submit" name="level-step-action" value="save_level_map"><?php esc_html_e( 'Save Level Map', 'pmpro-memberpress-migration-toolkit' ); ?></button>
			</form>
		</div>
		<?php
	}

	/**
	 * Process the step.
	 */
	static public function process_step() {
		// Check the level step action.
		if ( ! isset( $_POST['level-step-action'] ) ) {
			return;
		}

		if ( 'migrate_levels' === sanitize_text_field( wp_unslash( $_POST['level-step-action'] ) ) ) {
			// If the level map is not empty, do not run the migration again.
			$level_map = get_option( 'pmprompmt_level_map', array() );
			if ( ! empty( $level_map ) ) {
				wp_die( esc_html__( 'Level migration has already been completed. To re-run the migration, please clear the existing level map first.', 'pmpro-memberpress-migration-toolkit' ) );
			}

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

			// Make sure that every migrated level ends up in a level group. The frontend
			// Levels page only displays levels that are in a group, so levels whose
			// MemberPress product was not in a group would otherwise be hidden.
			$ungrouped_level_ids = array();
			foreach ( $level_map as $mp_level_id => $pmpro_level_id ) {
				if ( empty( pmpro_get_group_id_for_level( $pmpro_level_id ) ) ) {
					$ungrouped_level_ids[] = $pmpro_level_id;
				}
			}
			if ( ! empty( $ungrouped_level_ids ) ) {
				// Add ungrouped levels to the first existing level group, or create one.
				$level_groups = pmpro_get_level_groups_in_order();
				if ( empty( $level_groups ) ) {
					$ungrouped_group_id = pmpro_create_level_group( __( 'Membership Levels', 'pmpro-memberpress-migration-toolkit' ), true );
				} else {
					$ungrouped_group_id = reset( $level_groups )->id;
				}
				foreach ( $ungrouped_level_ids as $pmpro_level_id ) {
					pmpro_add_level_to_group( $pmpro_level_id, $ungrouped_group_id );
				}
			}

			// Break the PMPro levels cache to reflect new levels.
			pmpro_getAllLevels( true, true, true );
		} elseif ( 'save_level_map' === sanitize_text_field( wp_unslash( $_POST['level-step-action'] ) ) ) {
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
		}
	}
}
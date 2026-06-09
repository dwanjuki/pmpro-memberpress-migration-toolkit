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
		'pmpro-dashboard',
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
	include_once( dirname( __FILE__ ) . '/classes/class-pmprompmt-migration-step.php' );
	include_once( dirname( __FILE__ ) . '/classes/class-pmprompmt-migration-step-license.php' );
	include_once( dirname( __FILE__ ) . '/classes/class-pmprompmt-migration-step-levels.php' );
	include_once( dirname( __FILE__ ) . '/classes/class-pmprompmt-migration-step-users.php' );
	include_once( dirname( __FILE__ ) . '/classes/class-pmprompmt-migration-step-content-restrictions.php' );
	include_once( dirname( __FILE__ ) . '/classes/class-pmprompmt-migration-step-other.php' );
	include_once( dirname( __FILE__ ) . '/classes/class-pmprompmt-migration-step-final.php' );

	PMProMPMT_Migration_Step_License::maybe_process_step();
	PMProMPMT_Migration_Step_Levels::maybe_process_step();
	PMProMPMT_Migration_Step_Users::maybe_process_step();
	PMProMPMT_Migration_Step_Content_Restrictions::maybe_process_step();
	PMProMPMT_Migration_Step_Other::maybe_process_step();
	PMProMPMT_Migration_Step_Final::maybe_process_step();

	?>
	<div class="wrap pmpro_admin">
		<h1><?php esc_html_e( 'MemberPress Migration Toolkit', 'pmpro-memberpress-migration-toolkit' ); ?></h1>
		<p><?php esc_html_e( 'This toolkit provides scripts and tools to help you migrate your membership data from MemberPress to Paid Memberships Pro.', 'pmpro-memberpress-migration-toolkit' ); ?></p>
		<p><?php printf( esc_html__( 'Please follow the steps outlined in our %s to ensure a smooth transition.', 'pmpro-memberpress-migration-toolkit' ), '<a href="https://www.paidmembershipspro.com/migrate-memberpress-to-paid-memberships-pro/" target="_blank">' . esc_html__( 'migration guide', 'pmpro-memberpress-migration-toolkit' ) . '</a>' ); ?></p>
		<?php
		PMProMPMT_Migration_Step_License::display_step();
		PMProMPMT_Migration_Step_Levels::display_step();
		PMProMPMT_Migration_Step_Users::display_step();
		PMProMPMT_Migration_Step_Content_Restrictions::display_step();
		PMProMPMT_Migration_Step_Other::display_step();
		PMProMPMT_Migration_Step_Final::display_step();
		?>
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
			// These rules restrict all content that has any term in the taxonomy, so restrict every term in the taxonomy.
			$taxonomy = 'all_tax_category' === $rule_type ? 'category' : 'post_tag';
			$term_ids = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'fields'     => 'ids',
				)
			);
			if ( is_wp_error( $term_ids ) ) {
				break;
			}
			foreach ( $term_ids as $term_id ) {
				foreach( $pmpro_level_ids as $pmpro_level_id ) {
					$wpdb->insert(
						$wpdb->prefix . 'pmpro_memberships_categories',
						array(
							'membership_id' => intval( $pmpro_level_id ),
							'category_id'   => intval( $term_id ),
						),
						array(
							'%d',
							'%d',
						)
					);
				}
			}
			break;
		case 'category':
		case 'tag':
			// For taxonomy restrictions, we're going to instead update the pmpro_memberships_categories table.
			// MemberPress stores the term slug in the rule content for these rule types.
			$taxonomy = 'category' === $rule_type ? 'category' : 'post_tag';
			$term = get_term_by( 'slug', $rule_content, $taxonomy );
			if ( empty( $term ) ) {
				break;
			}
			foreach( $pmpro_level_ids as $pmpro_level_id ) {
				$wpdb->insert(
					$wpdb->prefix . 'pmpro_memberships_categories',
					array(
						'membership_id' => intval( $pmpro_level_id ),
						'category_id'   => intval( $term->term_id ),
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
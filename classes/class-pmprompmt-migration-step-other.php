<?php
class PMProMPMT_Migration_Step_Other extends PMProMPMT_Migration_Step {
	/**
	 * Get the step slug.
	 *
	 * @return string The step slug.
	 */
	static public function get_step_slug() {
		return 'other';
	}

	/**
	 * Get the step name. This will be displayed in the header of the step.
	 *
	 * @return string The step name.
	 */
	static public function get_step_name() {
		return esc_html__( 'Migrate Other Settings', 'pmpro-memberpress-migration-toolkit' );
	}

	/**
	 * Get the status of the step.
	 *
	 * @return string The status of the step. Possible values: 'not_started', 'in_progress', 'completed'.
	 */
	static public function get_step_status() {
		// Check if the account page has been explicitly set. If so, we'll assume that other settings have been migrated.
		return empty( get_option( 'pmpro_account_page_id' ) ) ? 'not_started' : 'completed';
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
		<h4><?php esc_html_e( 'What Will Be Migrated', 'pmpro-memberpress-migration-toolkit' ); ?></h4>
		<ul>
			<li>
				<strong><?php esc_html_e( 'Membership Pages:', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
				<?php esc_html_e( 'Account, Billing, Cancel, Checkout, Confirmation, Orders, Levels, Login, and Profile pages will be created.', 'pmpro-memberpress-migration-toolkit' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Payment Currency:', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
				<?php esc_html_e( 'Your MemberPress currency setting will be applied to PMPro.', 'pmpro-memberpress-migration-toolkit' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Business Address:', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
				<?php esc_html_e( 'Business name, address, city, state, postal code, and country.', 'pmpro-memberpress-migration-toolkit' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Custom User Field Configuration:', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
				<?php esc_html_e( 'Field names, labels, types, required settings, and options are migrated. Note: MemberPress and PMPro store data for date, checkboxes, checkbox_grouped, and file fields differently. If you are using these field types, this data will require a separate custom migration.', 'pmpro-memberpress-migration-toolkit' ); ?>
			</li>
		</ul>

		<h4><?php esc_html_e( 'What Will NOT Be Migrated', 'pmpro-memberpress-migration-toolkit' ); ?></h4>
		<ul>
			<li>
				<strong><?php esc_html_e( 'Email Notifications/Templates:', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
				<?php
				printf(
					/* translators: %s: Link to PMPro email templates admin page */
					esc_html__( 'Configure email templates at %s. PMPro includes comprehensive email customization options.', 'pmpro-memberpress-migration-toolkit' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=pmpro-emailtemplates' ) ) . '">' . esc_html__( 'Memberships > Settings > Email Templates', 'pmpro-memberpress-migration-toolkit' ) . '</a>'
				);
				?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Coupon/Discount Codes:', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
				<?php
				printf(
					/* translators: %s: Link to PMPro discount codes admin page */
					esc_html__( 'Recreate discount codes at %s. PMPro supports percentage and fixed discounts, expiration dates, and usage limits.', 'pmpro-memberpress-migration-toolkit' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=pmpro-discountcodes' ) ) . '">' . esc_html__( 'Memberships > Discount Codes', 'pmpro-memberpress-migration-toolkit' ) . '</a>'
				);
				?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Tax/VAT Settings:', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
				<?php
				printf(
					/* translators: %s: Link to VAT Tax Add On */
					esc_html__( 'Use the %s for EU VAT compliance, or configure tax rates in your payment gateway settings.', 'pmpro-memberpress-migration-toolkit' ),
					'<a href="https://www.paidmembershipspro.com/add-ons/pmpro-vat-tax/" target="_blank">' . esc_html__( 'VAT Tax Add On', 'pmpro-memberpress-migration-toolkit' ) . '</a>'
				);
				?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Anti-Fraud/Card Testing Protection:', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
				<?php
				printf(
					/* translators: %s: Link to PMPro advanced settings admin page */
					esc_html__( 'PMPro includes built-in reCAPTCHA support. Configure at %s. For additional protection, use your payment gateway\'s fraud tools.', 'pmpro-memberpress-migration-toolkit' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=pmpro-advancedsettings' ) ) . '">' . esc_html__( 'Memberships > Settings > Advanced', 'pmpro-memberpress-migration-toolkit' ) . '</a>'
				);
				?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Design/Branding Settings:', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
				<?php
				printf(
					/* translators: %s: Link to Member Homepages Add On */
					esc_html__( 'PMPro styling integrates with your theme. Configure at %s or via CSS.', 'pmpro-memberpress-migration-toolkit' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=pmpro-designsettings' ) ) . '">' . esc_html__( 'Settings > Design', 'pmpro-memberpress-migration-toolkit' ) . '</a>'
				);
				?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Corporate/Group Memberships:', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
				<?php
				printf(
					/* translators: %s: Link to Group Accounts Add On */
					esc_html__( 'Use the %s to enable group/corporate membership functionality.', 'pmpro-memberpress-migration-toolkit' ),
					'<a href="https://www.paidmembershipspro.com/add-ons/group-accounts/" target="_blank">' . esc_html__( 'Group Accounts Add On', 'pmpro-memberpress-migration-toolkit' ) . '</a>'
				);
				?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Gift Memberships:', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
				<?php
				printf(
					/* translators: %s: Link to Gift Membership Add On */
					esc_html__( 'Use the %s to enable gifting functionality.', 'pmpro-memberpress-migration-toolkit' ),
					'<a href="https://www.paidmembershipspro.com/add-ons/gift-levels/" target="_blank">' . esc_html__( 'Gift Membership Add On', 'pmpro-memberpress-migration-toolkit' ) . '</a>'
				);
				?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Courses:', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
				<?php
				printf(
					/* translators: %s: Link to Courses for Membership Add On */
					esc_html__( 'Use the %s or integrate with LearnDash, LifterLMS, or Sensei for course content.', 'pmpro-memberpress-migration-toolkit' ),
					'<a href="https://www.paidmembershipspro.com/add-ons/pmpro-courses-lms-integration/" target="_blank">' . esc_html__( 'Courses for Membership Add On', 'pmpro-memberpress-migration-toolkit' ) . '</a>'
				);
				?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Reminder/Automation Emails:', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
				<?php
				printf(
					/* translators: %s: Link to Extra Expiration Warning Emails Add On */
					esc_html__( 'Use the %s for expiration reminders.', 'pmpro-memberpress-migration-toolkit' ),
					'<a href="https://www.paidmembershipspro.com/add-ons/extra-expiration-warning-emails-add-on/" target="_blank">' . esc_html__( 'Extra Expiration Warning Emails Add On', 'pmpro-memberpress-migration-toolkit' ) . '</a>'
				);
				?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Two-Factor Authentication:', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
				<?php esc_html_e( 'Use a dedicated WordPress 2FA plugin such as Wordfence, WP 2FA, or Two-Factor.', 'pmpro-memberpress-migration-toolkit' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Social Login:', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
				<?php
				printf(
					/* translators: %s: Link to Social Login Add On */
					esc_html__( 'Use the %s for social authentication.', 'pmpro-memberpress-migration-toolkit' ),
					'<a href="https://www.paidmembershipspro.com/add-ons/social-login-add-on/" target="_blank">' . esc_html__( 'Social Login Add On', 'pmpro-memberpress-migration-toolkit' ) . '</a>'
				);
				?>
			</li>
		</ul>

		<h4><?php esc_html_e( 'Third-Party Integrations (Not Migrated)', 'pmpro-memberpress-migration-toolkit' ); ?></h4>
		<p><?php esc_html_e( 'If you were using MemberPress integrations with third-party plugins, you will need to set up the equivalent PMPro Add Ons:', 'pmpro-memberpress-migration-toolkit' ); ?></p>
		<ul>
			<li>
				<strong><?php esc_html_e( 'BuddyPress/BuddyBoss:', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
				<a href="https://www.paidmembershipspro.com/add-ons/buddypress-integration/" target="_blank"><?php esc_html_e( 'BuddyPress and BuddyBoss Integration Add On', 'pmpro-memberpress-migration-toolkit' ); ?></a>
			</li>
			<li>
				<strong><?php esc_html_e( 'bbPress:', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
				<a href="https://www.paidmembershipspro.com/add-ons/pmpro-bbpress/" target="_blank"><?php esc_html_e( 'bbPress Integration Add On', 'pmpro-memberpress-migration-toolkit' ); ?></a>
			</li>
			<li>
				<strong><?php esc_html_e( 'Zapier/Automations:', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
				<a href="https://www.paidmembershipspro.com/add-ons/zapier-integration/" target="_blank"><?php esc_html_e( 'Zapier Integration Add On', 'pmpro-memberpress-migration-toolkit' ); ?></a>
			</li>
			<li>
				<strong><?php esc_html_e( 'WooCommerce:', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
				<a href="https://www.paidmembershipspro.com/add-ons/pmpro-woocommerce/" target="_blank"><?php esc_html_e( 'WooCommerce Integration Add On', 'pmpro-memberpress-migration-toolkit' ); ?></a>
			</li>
			<li>
				<strong><?php esc_html_e( 'Email Marketing:', 'pmpro-memberpress-migration-toolkit' ); ?></strong>
				<?php esc_html_e( 'Multiple Add Ons available for', 'pmpro-memberpress-migration-toolkit' ); ?>
				<a href="https://www.paidmembershipspro.com/add-ons/pmpro-mailchimp-integration/" target="_blank"><?php esc_html_e( 'Mailchimp', 'pmpro-memberpress-migration-toolkit' ); ?></a>,
				<a href="https://www.paidmembershipspro.com/add-ons/pmpro-kit-integration/" target="_blank"><?php esc_html_e( 'Kit (ConvertKit)', 'pmpro-memberpress-migration-toolkit' ); ?></a>,
				<a href="https://www.paidmembershipspro.com/add-ons/pmpro-aweber-integration/" target="_blank"><?php esc_html_e( 'AWeber', 'pmpro-memberpress-migration-toolkit' ); ?></a>,
				<?php esc_html_e( 'and more.', 'pmpro-memberpress-migration-toolkit' ); ?>
			</li>
		</ul>

		<button class="button button-primary" type="submit"><?php esc_html_e( 'Migrate Other Settings', 'pmpro-memberpress-migration-toolkit' ); ?></button>
		<?php
	}

	/**
	 * Process the step.
	 */
	static public function process_step() {
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
		// TODO: Values for date, checkboxes, checkbox_grouped, and file fields are not stored the same way in PMPro as in MemberPress. We may need a migration script for that user data later.
		$pmpro_user_field_group = new stdClass();
		$pmpro_user_field_group->name = __( 'More Information', 'pmpro-memberpress-migration-toolkit' );
		$pmpro_user_field_group->checkout = 'yes';
		$pmpro_user_field_group->profile = 'yes';
		$pmpro_user_field_group->description = '';
		$pmpro_user_field_group->levels = array();
		$pmpro_user_field_group->fields = array();
		foreach( $mp_options['custom_fields'] as $cf ) {
			$cf = (array) $cf; // Make sure that we have an array and not an object.

			$field = new stdClass();
			$field->name = $cf['field_key'];
			$field->label = $cf['field_name'];
			switch ( $cf['field_type'] ) {
				case 'date':
					$field->type = 'date';
					break;
				case 'textarea':
					$field->type = 'textarea';
					break;
				case 'dropdown':
					$field->type = 'select';
					break;
				case 'multiselect':
					$field->type = 'select2';
					break;
				case 'checkbox':
					$field->type = 'checkbox';
					break;
				case 'radios':
					$field->type = 'radio';
					break;
				case 'checkboxes':
					$field->type = 'checkbox_grouped';
					break;
				case 'file':
					$field->type = 'file';
					break;
				default:
					$field->type = 'text';
					break;
			}
			$field->required = empty( $cf['required'] ) ? 'no' : 'yes';
			$field->readonly = 'no';
			$field->profile = empty( $cf['show_in_account'] ) ? 'admins' : 'yes'; // Note: We don't have great control over showing the field at checkout. If we ever do, we can update this.
			$field->wrapper_class = '';
			$field->element_class = '';
			$field->hint = '';
			$field->options = '';
			if ( ! empty( $cf['options'] ) ) {
				foreach ( $cf['options'] as $option_arr ) {
					$option_arr = (array) $option_arr; // Make sure that we have an array and not an object.
					$field->options .= $option_arr['option_value'] . ':' . $option_arr['option_name']  . "\n";
				}
				$field->options = trim( $field->options );
			}
			$pmpro_user_field_group->fields[] = $field;
		}
		update_option( 'pmpro_user_fields_settings', array( $pmpro_user_field_group ), false );
	}
}
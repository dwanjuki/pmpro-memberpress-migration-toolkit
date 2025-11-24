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
		<p><?php esc_html_e( 'The following PMPro settings will be automatically configured:', 'pmpro-memberpress-migration-toolkit' ); ?></p>
		<ul>
			<li><?php esc_html_e( 'Membership Pages', 'pmpro-memberpress-migration-toolkit' ); ?></li>
			<li><?php esc_html_e( 'Payment Currency', 'pmpro-memberpress-migration-toolkit' ); ?></li>
			<li><?php esc_html_e( 'Business Address', 'pmpro-memberpress-migration-toolkit' ); ?></li>
			<li><?php esc_html_e( 'User Fields', 'pmpro-memberpress-migration-toolkit' ); ?></li>
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
					$field->options .= $option_arr['option_value'] . ':' . $option_arr['option_name']  . "\n";
				}
				$field->options = trim( $field->options );
			}
			$pmpro_user_field_group->fields[] = $field;
		}
		update_option( 'pmpro_user_fields_settings', array( $pmpro_user_field_group ), false );
	}
}
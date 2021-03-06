<?php
/**
 * Profiles XProfile Classes.
 *
 * @package Profiles
 * @suprofilesackage XProfileClasses
 * @since 2.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Textbox xprofile field type.
 *
 * @since 2.0.0
 */
class Profiles_XProfile_Field_Type_Textbox extends Profiles_XProfile_Field_Type {

	/**
	 * Constructor for the textbox field type.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		parent::__construct();

		$this->category = _x( 'Single Fields', 'xprofile field type category', 'profiles' );
		$this->name     = _x( 'Text Box', 'xprofile field type', 'profiles' );

		$this->set_format( '/^.*$/', 'replace' );

		/**
		 * Fires inside __construct() method for Profiles_XProfile_Field_Type_Textbox class.
		 *
		 * @since 2.0.0
		 *
		 * @param Profiles_XProfile_Field_Type_Textbox $this Current instance of
		 *                                             the field type text box.
		 */
		do_action( 'profiles_xprofile_field_type_textbox', $this );
	}

	/**
	 * Output the edit field HTML for this field type.
	 * Must be used inside the {@link profiles_profile_fields()} template loop.
	 *
	 * @since 2.0.0
	 *
	 * @param array $raw_properties Optional key/value array of
	 *                              {@link http://dev.w3.org/html5/markup/input.text.html permitted attributes}
	 *                              that you want to add.
	 */
	public function edit_field_html( array $raw_properties = array() ) {

		// User_id is a special optional parameter that certain other fields
		// types pass to {@link profiles_the_profile_field_options()}.
		if ( isset( $raw_properties['user_id'] ) ) {
			unset( $raw_properties['user_id'] );
		}

		$r = profiles_parse_args( $raw_properties, array(
			'type'  => 'text',
			'value' => profiles_get_the_profile_field_edit_value(),
		) ); ?>

		<label for="<?php profiles_the_profile_field_input_name(); ?>">
			<?php profiles_the_profile_field_name(); ?>
			<?php profiles_the_profile_field_required_label(); ?>
		</label>

		<?php

		/** This action is documented in profiles-xprofile/profiles-xprofile-classes */
		do_action( profiles_get_the_profile_field_errors_action() ); ?>

		<input <?php echo $this->get_edit_field_html_elements( $r ); ?>>

		<?php
	}

	/**
	 * Output HTML for this field type on the wp-admin Profile Fields screen.
	 *
	 * Must be used inside the {@link profiles_profile_fields()} template loop.
	 *
	 * @since 2.0.0
	 *
	 * @param array $raw_properties Optional key/value array of permitted attributes that you want to add.
	 */
	public function admin_field_html( array $raw_properties = array() ) {

		$r = profiles_parse_args( $raw_properties, array(
			'type' => 'text'
		) ); ?>

		<label for="<?php profiles_the_profile_field_input_name(); ?>" class="screen-reader-text"><?php
			/* translators: accessibility text */
			esc_html_e( 'Textbox', 'profiles' );
		?></label>
		<input <?php echo $this->get_edit_field_html_elements( $r ); ?>>

		<?php
	}

	/**
	 * This method usually outputs HTML for this field type's children options on the wp-admin Profile Fields
	 * "Add Field" and "Edit Field" screens, but for this field type, we don't want it, so it's stubbed out.
	 *
	 * @since 2.0.0
	 *
	 * @param Profiles_XProfile_Field $current_field The current profile field on the add/edit screen.
	 * @param string            $control_type  Optional. HTML input type used to render the
	 *                                         current field's child options.
	 */
	public function admin_new_field_html( Profiles_XProfile_Field $current_field, $control_type = '' ) {}
}

<?php
/**
 * Profiles XProfile Screens.
 *
 * Screen functions are the controllers of Profiles. They will execute when
 * their specific URL is caught. They will first save or manipulate data using
 * business functions, then pass on the user to a template file.
 *
 * @package Profiles
 * @suprofilesackage XProfileScreens
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles the display of the profile page by loading the correct template file.
 *
 * @since 1.0.0
 *
 */
function profiles_xprofile_screen_display_profile() {
	$new = isset( $_GET['new'] ) ? $_GET['new'] : '';

	/**
	 * Fires right before the loading of the XProfile screen template file.
	 *
	 * @since 1.0.0
	 *
	 * @param string $new $_GET parameter holding the "new" parameter.
	 */
	do_action( 'profiles_xprofile_screen_display_profile', $new );

	/**
	 * Filters the template to load for the XProfile screen.
	 *
	 * @since 1.0.0
	 *
	 * @param string $template Path to the XProfile template to load.
	 */
	profiles_core_load_template( apply_filters( 'xprofile_template_display_profile', 'members/single/home' ) );
}

/**
 * Handles the display of the profile edit page by loading the correct template file.
 * Also checks to make sure this can only be accessed for the logged in users profile.
 *
 * @since 1.0.0
 *
 */
function profiles_xprofile_screen_edit_profile() {

	if ( ! profiles_is_my_profile() && ! profiles_current_user_can( 'profiles_moderate' ) ) {
		return false;
	}

	// Make sure a group is set.
	if ( ! profiles_action_variable( 1 ) ) {
		profiles_core_redirect( trailingslashit( profiles_displayed_user_domain() . profiles_get_profile_slug() . '/edit/group/1' ) );
	}

	// Check the field group exists.
	if ( ! profiles_is_action_variable( 'group' ) || ! profiles_profiles_xprofile_get_field_group( profiles_action_variable( 1 ) ) ) {
		profiles_do_404();
		return;
	}

	// No errors.
	$errors = false;

	// Check to see if any new information has been submitted.
	if ( isset( $_POST['field_ids'] ) ) {

		// Check the nonce.
		check_admin_referer( 'profiles_xprofile_edit' );

		// Check we have field ID's.
		if ( empty( $_POST['field_ids'] ) ) {
			profiles_core_redirect( trailingslashit( profiles_displayed_user_domain() . profiles_get_profile_slug() . '/edit/group/' . profiles_action_variable( 1 ) ) );
		}

		// Explode the posted field IDs into an array so we know which
		// fields have been submitted.
		$posted_field_ids = wp_parse_id_list( $_POST['field_ids'] );
		$is_required      = array();

		// Loop through the posted fields formatting any datebox values
		// then validate the field.
		foreach ( (array) $posted_field_ids as $field_id ) {
			if ( !isset( $_POST['field_' . $field_id] ) ) {

				if ( !empty( $_POST['field_' . $field_id . '_day'] ) && !empty( $_POST['field_' . $field_id . '_month'] ) && !empty( $_POST['field_' . $field_id . '_year'] ) ) {
					// Concatenate the values.
					$date_value =   $_POST['field_' . $field_id . '_day'] . ' ' . $_POST['field_' . $field_id . '_month'] . ' ' . $_POST['field_' . $field_id . '_year'];

					// Turn the concatenated value into a timestamp.
					$_POST['field_' . $field_id] = date( 'Y-m-d H:i:s', strtotime( $date_value ) );
				}

			}

			$is_required[ $field_id ] = profiles_xprofile_check_is_required_field( $field_id ) && ! profiles_current_user_can( 'profiles_moderate' );
			if ( $is_required[$field_id] && empty( $_POST['field_' . $field_id] ) ) {
				$errors = true;
			}
		}

		// There are errors.
		if ( !empty( $errors ) ) {
			profiles_core_add_message( __( 'Please make sure you fill in all required fields in this profile field group before saving.', 'profiles' ), 'error' );

		// No errors.
		} else {

			// Reset the errors var.
			$errors = false;

			// Now we've checked for required fields, lets save the values.
			$old_values = $new_values = array();
			foreach ( (array) $posted_field_ids as $field_id ) {

				// Certain types of fields (checkboxes, multiselects) may come through empty. Save them as an empty array so that they don't get overwritten by the default on the next edit.
				$value = isset( $_POST['field_' . $field_id] ) ? $_POST['field_' . $field_id] : '';

				$visibility_level = !empty( $_POST['field_' . $field_id . '_visibility'] ) ? $_POST['field_' . $field_id . '_visibility'] : 'public';

				// Save the old and new values. They will be
				// passed to the filter and used to determine
				// whether an activity item should be posted.
				$old_values[ $field_id ] = array(
					'value'      => profiles_xprofile_get_field_data( $field_id, profiles_displayed_user_id() ),
					'visibility' => profiles_xprofile_get_field_visibility_level( $field_id, profiles_displayed_user_id() ),
				);

				// Update the field data and visibility level.
				profiles_xprofile_set_field_visibility_level( $field_id, profiles_displayed_user_id(), $visibility_level );
				$field_updated = profiles_xprofile_set_field_data( $field_id, profiles_displayed_user_id(), $value, $is_required[ $field_id ] );
				$value         = profiles_xprofile_get_field_data( $field_id, profiles_displayed_user_id() );

				$new_values[ $field_id ] = array(
					'value'      => $value,
					'visibility' => profiles_xprofile_get_field_visibility_level( $field_id, profiles_displayed_user_id() ),
				);

				if ( ! $field_updated ) {
					$errors = true;
				} else {

					/**
					 * Fires on each iteration of an XProfile field being saved with no error.
					 *
					 * @since 1.1.0
					 *
					 * @param int    $field_id ID of the field that was saved.
					 * @param string $value    Value that was saved to the field.
					 */
					do_action( 'xprofile_profile_field_data_updated', $field_id, $value );
				}
			}

			/**
			 * Fires after all XProfile fields have been saved for the current profile.
			 *
			 * @since 1.0.0
			 *
			 * @param int   $value            Displayed user ID.
			 * @param array $posted_field_ids Array of field IDs that were edited.
			 * @param bool  $errors           Whether or not any errors occurred.
			 * @param array $old_values       Array of original values before updated.
			 * @param array $new_values       Array of newly saved values after update.
			 */
			do_action( 'xprofile_updated_profile', profiles_displayed_user_id(), $posted_field_ids, $errors, $old_values, $new_values );

			// Set the feedback messages.
			if ( !empty( $errors ) ) {
				profiles_core_add_message( __( 'There was a problem updating some of your profile information. Please try again.', 'profiles' ), 'error' );
			} else {
				profiles_core_add_message( __( 'Changes saved.', 'profiles' ) );
			}

			// Redirect back to the edit screen to display the updates and message.
			profiles_core_redirect( trailingslashit( profiles_displayed_user_domain() . profiles_get_profile_slug() . '/edit/group/' . profiles_action_variable( 1 ) ) );
		}
	}

	/**
	 * Fires right before the loading of the XProfile edit screen template file.
	 *
	 * @since 1.0.0
	 */
	do_action( 'profiles_xprofile_screen_edit_profile' );

	/**
	 * Filters the template to load for the XProfile edit screen.
	 *
	 * @since 1.0.0
	 *
	 * @param string $template Path to the XProfile edit template to load.
	 */
	profiles_core_load_template( apply_filters( 'xprofile_template_edit_profile', 'members/single/home' ) );
}

/**
 * Handles the uploading and cropping of a user avatar. Displays the change avatar page.
 *
 * @since 1.0.0
 *
 */
function profiles_xprofile_screen_change_avatar() {

	// Bail if not the correct screen.
	if ( ! profiles_is_my_profile() && ! profiles_current_user_can( 'profiles_moderate' ) ) {
		return false;
	}

	// Bail if there are action variables.
	if ( profiles_action_variables() ) {
		profiles_do_404();
		return;
	}

	$profiles = profiles();

	if ( ! isset( $profiles->avatar_admin ) ) {
		$profiles->avatar_admin = new stdClass();
	}

	$profiles->avatar_admin->step = 'upload-image';

	if ( !empty( $_FILES ) ) {

		// Check the nonce.
		check_admin_referer( 'profiles_avatar_upload' );

		// Pass the file to the avatar upload handler.
		if ( profiles_core_avatar_handle_upload( $_FILES, 'profiles_xprofile_avatar_upload_dir' ) ) {
			$profiles->avatar_admin->step = 'crop-image';

			// Make sure we include the jQuery jCrop file for image cropping.
			add_action( 'wp_print_scripts', 'profiles_core_add_jquery_cropper' );
		}
	}

	// If the image cropping is done, crop the image and save a full/thumb version.
	if ( isset( $_POST['avatar-crop-submit'] ) ) {

		// Check the nonce.
		check_admin_referer( 'profiles_avatar_cropstore' );

		$args = array(
			'item_id'       => profiles_displayed_user_id(),
			'original_file' => $_POST['image_src'],
			'crop_x'        => $_POST['x'],
			'crop_y'        => $_POST['y'],
			'crop_w'        => $_POST['w'],
			'crop_h'        => $_POST['h']
		);

		if ( ! profiles_core_avatar_handle_crop( $args ) ) {
			profiles_core_add_message( __( 'There was a problem cropping your profile photo.', 'profiles' ), 'error' );
		} else {

			/**
			 * Fires right before the redirect, after processing a new avatar.
			 *
			 * @since 1.1.0
			 * @since 2.3.4 Add two new parameters to inform about the user id and
			 *              about the way the avatar was set (eg: 'crop' or 'camera').
			 *
			 * @param string $item_id Inform about the user id the avatar was set for.
			 * @param string $value   Inform about the way the avatar was set ('crop').
			 */
			do_action( 'xprofile_avatar_uploaded', (int) $args['item_id'], 'crop' );
			profiles_core_add_message( __( 'Your new profile photo was uploaded successfully.', 'profiles' ) );
			profiles_core_redirect( profiles_displayed_user_domain() );
		}
	}

	/**
	 * Fires right before the loading of the XProfile change avatar screen template file.
	 *
	 * @since 1.0.0
	 */
	do_action( 'profiles_xprofile_screen_change_avatar' );

	/**
	 * Filters the template to load for the XProfile change avatar screen.
	 *
	 * @since 1.0.0
	 *
	 * @param string $template Path to the XProfile change avatar template to load.
	 */
	profiles_core_load_template( apply_filters( 'xprofile_template_change_avatar', 'members/single/home' ) );
}

/**
 * Displays the change cover image page.
 *
 * @since 2.4.0
 */
function profiles_xprofile_screen_change_cover_image() {

	// Bail if not the correct screen.
	if ( ! profiles_is_my_profile() && ! profiles_current_user_can( 'profiles_moderate' ) ) {
		return false;
	}

	/**
	 * Fires right before the loading of the XProfile change cover image screen template file.
	 *
	 * @since 2.4.0
	 */
	do_action( 'profiles_xprofile_screen_change_cover_image' );

	/**
	 * Filters the template to load for the XProfile cover image screen.
	 *
	 * @since 2.4.0
	 *
	 * @param string $template Path to the XProfile cover image template to load.
	 */
	profiles_core_load_template( apply_filters( 'xprofile_template_cover_image', 'members/single/home' ) );
}

/**
 * Show the xprofile settings template.
 *
 * @since 2.0.0
 */
function profiles_xprofile_screen_settings() {

	// Redirect if no privacy settings page is accessible.
	if ( profiles_action_variables() || ! profiles_is_active( 'xprofile' ) ) {
		profiles_do_404();
		return;
	}

	/**
	 * Filters the template to load for the XProfile settings screen.
	 *
	 * @since 2.0.0
	 *
	 * @param string $template Path to the XProfile change avatar template to load.
	 */
	profiles_core_load_template( apply_filters( 'profiles_settings_screen_xprofile', '/members/single/settings/profile' ) );
}

<?php
/**
 * Core Avatars attachment class.
 *
 * @package Profiles
 * @suprofilesackage Core
 * @since 2.3.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BP Attachment Avatar class.
 *
 * Extends BP Attachment to manage the avatar uploads.
 *
 * @since 2.3.0
 */
class Profiles_Attachment_Avatar extends Profiles_Attachment {

	/**
	 * Construct Upload parameters.
	 *
	 * @since 2.3.0
	 *
	 * @see  Profiles_Attachment::__construct() for list of parameters
	 */
	public function __construct() {
		// Allowed avatar types.
		$allowed_types = profiles_core_get_allowed_avatar_types();

		parent::__construct( array(
			'action'                => 'profiles_avatar_upload',
			'file_input'            => 'file',
			'original_max_filesize' => profiles_core_avatar_original_max_filesize(),

			// Specific errors for avatars.
			'upload_error_strings'  => array(
				9  => sprintf( __( 'That photo is too big. Please upload one smaller than %s', 'profiles' ), size_format( profiles_core_avatar_original_max_filesize() ) ),
				10 => sprintf( _n( 'Please upload only this file type: %s.', 'Please upload only these file types: %s.', count( $allowed_types ), 'profiles' ), self::get_avatar_types( $allowed_types ) ),
			),
		) );
	}

	/**
	 * Gets the available avatar types.
	 *
	 * @since 2.3.0
	 *
	 * @param array $allowed_types Array of allowed avatar types.
	 * @return string comma separated list of allowed avatar types.
	 */
	public static function get_avatar_types( $allowed_types = array() ) {
		$types = array_map( 'strtoupper', $allowed_types );
		$comma = _x( ',', 'avatar types separator', 'profiles' );
		return join( $comma . ' ', $types );
	}

	/**
	 * Set Upload Dir data for avatars.
	 *
	 * @since 2.3.0
	 */
	public function set_upload_dir() {
		if ( profiles_core_avatar_upload_path() && profiles_core_avatar_url() ) {
			$this->upload_path = profiles_core_avatar_upload_path();
			$this->url         = profiles_core_avatar_url();
			$this->upload_dir  = profiles_upload_dir();
		} else {
			parent::set_upload_dir();
		}
	}

	/**
	 * Avatar specific rules.
	 *
	 * Adds an error if the avatar size or type don't match Profiles needs.
	 * The error code is the index of $upload_error_strings.
	 *
	 * @since 2.3.0
	 *
	 * @param array $file the temporary file attributes (before it has been moved).
	 * @return array the file with extra errors if needed.
	 */
	public function validate_upload( $file = array() ) {
		// Bail if already an error.
		if ( ! empty( $file['error'] ) ) {
			return $file;
		}

		// File size is too big.
		if ( ! profiles_core_check_avatar_size( array( 'file' => $file ) ) ) {
			$file['error'] = 9;

		// File is of invalid type.
		} elseif ( ! profiles_core_check_avatar_type( array( 'file' => $file ) ) ) {
			$file['error'] = 10;
		}

		// Return with error code attached.
		return $file;
	}

	/**
	 * Maybe shrink the attachment to fit maximum allowed width.
	 *
	 * @since 2.3.0
	 * @since 2.4.0 Add the $ui_available_width parameter, to inform about the Avatar UI width.
	 *
	 * @param string $file               The absolute path to the file.
	 * @param int    $ui_available_width Available width for the UI.
	 * @return mixed
	 */
	public static function shrink( $file = '', $ui_available_width = 0 ) {
		// Get image size.
		$avatar_data = parent::get_image_data( $file );

		// Init the edit args.
		$edit_args = array();

		// Defaults to the Avatar original max width constant.
		$original_max_width = profiles_core_avatar_original_max_width();

		// The ui_available_width is defined and it's smaller than the Avatar original max width.
		if ( ! empty( $ui_available_width ) && $ui_available_width < $original_max_width ) {
			/**
			 * In this case, to make sure the content of the image will be fully displayed
			 * during the cropping step, let's use the Avatar UI Available width.
			 */
			$original_max_width = $ui_available_width;

			// $original_max_width has to be larger than the avatar's full width
			if ( $original_max_width < profiles_core_avatar_full_width() ) {
				$original_max_width = profiles_core_avatar_full_width();
			}
		}

		// Do we need to resize the image?
		if ( isset( $avatar_data['width'] ) && $avatar_data['width'] > $original_max_width ) {
			$edit_args = array(
				'max_w' => $original_max_width,
				'max_h' => $original_max_width,
			);
		}

		// Do we need to rotate the image?
		$angles = array(
			3 => 180,
			6 => -90,
			8 =>  90,
		);

		if ( isset( $avatar_data['meta']['orientation'] ) && isset( $angles[ $avatar_data['meta']['orientation'] ] ) ) {
			$edit_args['rotate'] = $angles[ $avatar_data['meta']['orientation'] ];
		}

		// No need to edit the avatar, original file will be used.
		if ( empty( $edit_args ) ) {
			return false;

		// Add the file to the edit arguments.
		} else {
			$edit_args['file'] = $file;
		}

		return parent::edit_image( 'avatar', $edit_args );
	}

	/**
	 * Check if the image dimensions are smaller than full avatar dimensions.
	 *
	 * @since 2.3.0
	 *
	 *
	 * @param string $file the absolute path to the file.
	 * @return bool
	 */
	public static function is_too_small( $file = '' ) {
		$uploaded_image = @getimagesize( $file );
		$full_width     = profiles_core_avatar_full_width();
		$full_height    = profiles_core_avatar_full_height();

		if ( isset( $uploaded_image[0] ) && $uploaded_image[0] < $full_width || $uploaded_image[1] < $full_height ) {
			return true;
		}

		return false;
	}

	/**
	 * Crop the avatar.
	 *
	 * @since 2.3.0
	 *
	 * @see  Profiles_Attachment::crop for the list of parameters
	 *
	 * @param array $args Array of arguments for the cropping.
	 * @return array The cropped avatars (full and thumb).
	 */
	public function crop( $args = array() ) {
		// Bail if the original file is missing.
		if ( empty( $args['original_file'] ) ) {
			return false;
		}

		/**
		 * Original file is a relative path to the image
		 * eg: /avatars/1/avatar.jpg
		 */
		$relative_path = $args['original_file'];
		$absolute_path = $this->upload_path . $relative_path;

		// Bail if the avatar is not available.
		if ( ! file_exists( $absolute_path ) )  {
			return false;
		}

		if ( empty( $args['item_id'] ) ) {

			/** This filter is documented in profiles-core/profiles-core-avatars.php */
			$avatar_folder_dir = apply_filters( 'profiles_core_avatar_folder_dir', dirname( $absolute_path ), $args['item_id'], $args['object'], $args['avatar_dir'] );
		} else {

			/** This filter is documented in profiles-core/profiles-core-avatars.php */
			$avatar_folder_dir = apply_filters( 'profiles_core_avatar_folder_dir', $this->upload_path . '/' . $args['avatar_dir'] . '/' . $args['item_id'], $args['item_id'], $args['object'], $args['avatar_dir'] );
		}

		// Bail if the avatar folder is missing for this item_id.
		if ( ! file_exists( $avatar_folder_dir ) ) {
			return false;
		}

		// Delete the existing avatar files for the object.
		$existing_avatar = profiles_core_fetch_avatar( array(
			'object'  => $args['object'],
			'item_id' => $args['item_id'],
			'html' => false,
		) );

		/**
		 * Check that the new avatar doesn't have the same name as the
		 * old one before deleting
		 */
		if ( ! empty( $existing_avatar ) && $existing_avatar !== $this->url . $relative_path ) {
			profiles_core_delete_existing_avatar( array( 'object' => $args['object'], 'item_id' => $args['item_id'], 'avatar_path' => $avatar_folder_dir ) );
		}

		// Make sure we at least have minimal data for cropping.
		if ( empty( $args['crop_w'] ) ) {
			$args['crop_w'] = profiles_core_avatar_full_width();
		}

		if ( empty( $args['crop_h'] ) ) {
			$args['crop_h'] = profiles_core_avatar_full_height();
		}

		// Get the file extension.
		$data = @getimagesize( $absolute_path );
		$ext  = $data['mime'] == 'image/png' ? 'png' : 'jpg';

		$args['original_file'] = $absolute_path;
		$args['src_abs']       = false;
		$avatar_types = array( 'full' => '', 'thumb' => '' );

		foreach ( $avatar_types as $key_type => $type ) {
			if ( 'thumb' === $key_type ) {
				$args['dst_w'] = profiles_core_avatar_thumb_width();
				$args['dst_h'] = profiles_core_avatar_thumb_height();
			} else {
				$args['dst_w'] = profiles_core_avatar_full_width();
				$args['dst_h'] = profiles_core_avatar_full_height();
			}

			$filename         = wp_unique_filename( $avatar_folder_dir, uniqid() . "-profiles{$key_type}.{$ext}" );
			$args['dst_file'] = $avatar_folder_dir . '/' . $filename;

			$avatar_types[ $key_type ] = parent::crop( $args );
		}

		// Remove the original.
		@unlink( $absolute_path );

		// Return the full and thumb cropped avatars.
		return $avatar_types;
	}

	/**
	 * Get the user id to set its avatar.
	 *
	 * @since 2.3.0
	 *
	 * @return integer The user ID.
	 */
	private function get_user_id() {
		$profiles = profiles();
		$user_id = 0;

		if ( profiles_is_user() ) {
			$user_id = profiles_displayed_user_id();
		}

		if ( ! empty( $profiles->members->admin->user_id ) ) {
			$user_id = $profiles->members->admin->user_id;
		}

		return $user_id;
	}

	/**
	 * Get the group id to set its avatar.
	 *
	 * @since 2.3.0
	 *
	 * @return integer The group ID.
	 */
	private function get_group_id() {
		$group_id = 0;

		if ( profiles_is_group() ) {
			$group_id = profiles_get_current_group_id();
		}

		return $group_id;
	}

	/**
	 * Build script datas for the Uploader UI.
	 *
	 * @since 2.3.0
	 *
	 * @return array The javascript localization data.
	 */
	public function script_data() {
		// Get default script data.
		$script_data = parent::script_data();

		// Defaults to Avatar Backbone script.
		$js_scripts = array( 'profiles-avatar' );

		// Default object.
		$object = '';

		// Get the possible item ids.
		$user_id  = $this->get_user_id();
		$group_id = $this->get_group_id();

		if ( ! empty( $user_id ) ) {
			// Should we load the the Webcam Avatar javascript file.
			if ( profiles_avatar_use_webcam() ) {
				$js_scripts = array( 'profiles-webcam' );
			}

			$script_data['profiles_params'] = array(
				'object'     => 'user',
				'item_id'    => $user_id,
				'has_avatar' => profiles_get_user_has_avatar( $user_id ),
				'nonces'  => array(
					'set'    => wp_create_nonce( 'profiles_avatar_cropstore' ),
					'remove' => wp_create_nonce( 'profiles_delete_avatar_link' ),
				),
			);

			// Set feedback messages.
			$script_data['feedback_messages'] = array(
				1 => __( 'There was a problem cropping your profile photo.', 'profiles' ),
				2 => __( 'Your new profile photo was uploaded successfully.', 'profiles' ),
				3 => __( 'There was a problem deleting your profile photo. Please try again.', 'profiles' ),
				4 => __( 'Your profile photo was deleted successfully!', 'profiles' ),
			);
		} elseif ( ! empty( $group_id ) ) {
			$script_data['profiles_params'] = array(
				'object'     => 'group',
				'item_id'    => $group_id,
				'has_avatar' => profiles_get_group_has_avatar( $group_id ),
				'nonces'     => array(
					'set'    => wp_create_nonce( 'profiles_avatar_cropstore' ),
					'remove' => wp_create_nonce( 'profiles_group_avatar_delete' ),
				),
			);

			// Set feedback messages.
			$script_data['feedback_messages'] = array(
				1 => __( 'There was a problem cropping the group profile photo.', 'profiles' ),
				2 => __( 'The group profile photo was uploaded successfully.', 'profiles' ),
				3 => __( 'There was a problem deleting the group profile photo. Please try again.', 'profiles' ),
				4 => __( 'The group profile photo was deleted successfully!', 'profiles' ),
			);
		} else {

			/**
			 * Use this filter to include specific Profiles params for your object.
			 * e.g. Blavatar.
			 *
			 * @since 2.3.0
			 *
			 * @param array $value The avatar specific Profiles parameters.
			 */
			$script_data['profiles_params'] = apply_filters( 'profiles_attachment_avatar_params', array() );
		}

		// Include the specific css.
		$script_data['extra_css'] = array( 'profiles-avatar' );

		// Include the specific css.
		$script_data['extra_js']  = $js_scripts;

		// Set the object to contextualize the filter.
		if ( isset( $script_data['profiles_params']['object'] ) ) {
			$object = $script_data['profiles_params']['object'];
		}

		/**
		 * Use this filter to override/extend the avatar script data.
		 *
		 * @since 2.3.0
		 *
		 * @param array  $script_data The avatar script data.
		 * @param string $object      The object the avatar belongs to (eg: user or group).
		 */
		return apply_filters( 'profiles_attachment_avatar_script_data', $script_data, $object );
	}
}

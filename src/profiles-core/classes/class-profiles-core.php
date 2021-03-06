<?php
/**
 * Profiles Core Loader.
 *
 * Core contains the commonly used functions, classes, and APIs.
 *
 * @package Profiles
 * @suprofilesackage Core
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Creates the Core component.
 *
 * @since 1.5.0
 */
class Profiles_Core extends Profiles_Component {

	/**
	 * Start the members component creation process.
	 *
	 * @since 1.5.0
	 *
	 */
	public function __construct() {
		parent::start(
			'core',
			__( 'Profiles Core', 'profiles' ),
			profiles()->plugin_dir
		);

		$this->bootstrap();
	}

	/**
	 * Populate the global data needed before Profiles can continue.
	 *
	 * This involves figuring out the currently required, activated, deactivated,
	 * and optional components.
	 *
	 * @since 1.5.0
	 */
	private function bootstrap() {
		$profiles = profiles();

		/**
		 * Fires before the loading of individual components and after Profiles Core.
		 *
		 * Allows plugins to run code ahead of the other components.
		 *
		 * @since 1.2.0
		 */
		do_action( 'profiles_core_loaded' );

		/** Components *******************************************************
		 */

		/**
		 * Filters the included and optional components.
		 *
		 * @since 1.5.0
		 *
		 * @param array $value Array of included and optional components.
		 */
		$profiles->optional_components = apply_filters( 'profiles_optional_components', array( 'activity', 'blogs', 'forums', 'friends', 'groups', 'messages', 'notifications', 'settings', 'xprofile' ) );

		/**
		 * Filters the required components.
		 *
		 * @since 1.5.0
		 *
		 * @param array $value Array of required components.
		 */
		$profiles->required_components = apply_filters( 'profiles_required_components', array( 'members' ) );

		// Get a list of activated components.
		if ( $active_components = profiles_get_option( 'profiles-active-components' ) ) {

			/** This filter is documented in profiles-core/admin/profiles-core-admin-components.php */
			$profiles->active_components      = apply_filters( 'profiles_active_components', $active_components );

			/**
			 * Filters the deactivated components.
			 *
			 * @since 1.0.0
			 *
			 * @param array $value Array of deactivated components.
			 */
			$profiles->deactivated_components = apply_filters( 'profiles_deactivated_components', array_values( array_diff( array_values( array_merge( $profiles->optional_components, $profiles->required_components ) ), array_keys( $profiles->active_components ) ) ) );

		// Pre 1.5 Backwards compatibility.
		} elseif ( $deactivated_components = profiles_get_option( 'profiles-deactivated-components' ) ) {

			// Trim off namespace and filename.
			foreach ( array_keys( (array) $deactivated_components ) as $component ) {
				$trimmed[] = str_replace( '.php', '', str_replace( 'profiles-', '', $component ) );
			}

			/** This filter is documented in profiles-core/profiles-core-loader.php */
			$profiles->deactivated_components = apply_filters( 'profiles_deactivated_components', $trimmed );

			// Setup the active components.
			$active_components     = array_fill_keys( array_diff( array_values( array_merge( $profiles->optional_components, $profiles->required_components ) ), array_values( $profiles->deactivated_components ) ), '1' );

			/** This filter is documented in profiles-core/admin/profiles-core-admin-components.php */
			$profiles->active_components = apply_filters( 'profiles_active_components', $profiles->active_components );

		// Default to all components active.
		} else {

			// Set globals.
			$profiles->deactivated_components = array();

			// Setup the active components.
			$active_components     = array_fill_keys( array_values( array_merge( $profiles->optional_components, $profiles->required_components ) ), '1' );

			/** This filter is documented in profiles-core/admin/profiles-core-admin-components.php */
			$profiles->active_components = apply_filters( 'profiles_active_components', $profiles->active_components );
		}

		// Loop through optional components.
		foreach( $profiles->optional_components as $component ) {
			if ( profiles_is_active( $component ) && file_exists( $profiles->plugin_dir . '/profiles-' . $component . '/profiles-' . $component . '-loader.php' ) ) {
				include( $profiles->plugin_dir . '/profiles-' . $component . '/profiles-' . $component . '-loader.php' );
			}
		}

		// Loop through required components.
		foreach( $profiles->required_components as $component ) {
			if ( file_exists( $profiles->plugin_dir . '/profiles-' . $component . '/profiles-' . $component . '-loader.php' ) ) {
				include( $profiles->plugin_dir . '/profiles-' . $component . '/profiles-' . $component . '-loader.php' );
			}
		}

		// Add Core to required components.
		$profiles->required_components[] = 'core';

		/**
		 * Fires after the loading of individual components.
		 *
		 * @since 2.0.0
		 */
		do_action( 'profiles_core_components_included' );
	}

	/**
	 * Include profiles-core files.
	 *
	 * @since 1.6.0
	 *
	 * @see Profiles_Component::includes() for description of parameters.
	 *
	 * @param array $includes See {@link Profiles_Component::includes()}.
	 */
	public function includes( $includes = array() ) {

		if ( ! is_admin() ) {
			return;
		}

		$includes = array(
			'admin'
		);

		parent::includes( $includes );
	}

	/**
	 * Set up profiles-core global settings.
	 *
	 * Sets up a majority of the Profiles globals that require a minimal
	 * amount of processing, meaning they cannot be set in the Profiles class.
	 *
	 * @since 1.5.0
	 *
	 * @see Profiles_Component::setup_globals() for description of parameters.
	 *
	 * @param array $args See {@link Profiles_Component::setup_globals()}.
	 */
	public function setup_globals( $args = array() ) {
		$profiles = profiles();

		/** Database *********************************************************
		 */

		// Get the base database prefix.
		if ( empty( $profiles->table_prefix ) ) {
			$profiles->table_prefix = profiles_core_get_table_prefix();
		}

		// The domain for the root of the site where the main blog resides.
		if ( empty( $profiles->root_domain ) ) {
			$profiles->root_domain = profiles_core_get_root_domain();
		}

		// Fetches all of the core Profiles settings in one fell swoop.
		if ( empty( $profiles->site_options ) ) {
			$profiles->site_options = profiles_core_get_root_options();
		}

		// The names of the core WordPress pages used to display Profiles content.
		if ( empty( $profiles->pages ) ) {
			$profiles->pages = profiles_core_get_directory_pages();
		}

		/** Basic current user data ******************************************
		 */

		// Logged in user is the 'current_user'.
		$current_user            = wp_get_current_user();

		// The user ID of the user who is currently logged in.
		$profiles->loggedin_user       = new stdClass;
		$profiles->loggedin_user->id   = isset( $current_user->ID ) ? $current_user->ID : 0;

		/** Avatars **********************************************************
		 */

		// Fetches the default Gravatar image to use if the user/group/blog has no avatar or gravatar.
		$profiles->grav_default        = new stdClass;

		/**
		 * Filters the default user Gravatar.
		 *
		 * @since 1.1.0
		 *
		 * @param string $value Default user Gravatar.
		 */
		$profiles->grav_default->user  = apply_filters( 'profiles_user_gravatar_default',  $profiles->site_options['avatar_default'] );

		/**
		 * Filters the default group Gravatar.
		 *
		 * @since 1.1.0
		 *
		 * @param string $value Default group Gravatar.
		 */
		$profiles->grav_default->group = apply_filters( 'profiles_group_gravatar_default', $profiles->grav_default->user );

		/**
		 * Filters the default blog Gravatar.
		 *
		 * @since 1.1.0
		 *
		 * @param string $value Default blog Gravatar.
		 */
		$profiles->grav_default->blog  = apply_filters( 'profiles_blog_gravatar_default',  $profiles->grav_default->user );

		// Notifications table. Included here for legacy purposes. Use
		// profiles-notifications instead.
		$profiles->core->table_name_notifications = $profiles->table_prefix . 'profiles_notifications';

		// Backward compatibility for plugins modifying the legacy profiles_nav and profiles_options_nav global properties.
		if ( profiles()->do_nav_backcompat ) {
			$profiles->profiles_nav         = new Profiles_Core_Profiles_Nav_BackCompat();
			$profiles->profiles_options_nav = new Profiles_Core_Profiles_Options_Nav_BackCompat();
		}

		/**
		 * Used to determine if user has admin rights on current content. If the
		 * logged in user is viewing their own profile and wants to delete
		 * something, is_item_admin is used. This is a generic variable so it
		 * can be used by other components. It can also be modified, so when
		 * viewing a group 'is_item_admin' would be 'true' if they are a group
		 * admin, and 'false' if they are not.
		 */
		profiles_update_is_item_admin( profiles_user_has_access(), 'core' );

		// Is the logged in user is a mod for the current item?
		profiles_update_is_item_mod( false,                  'core' );

		/**
		 * Fires at the end of the setup of profiles-core globals setting.
		 *
		 * @since 1.1.0
		 */
		do_action( 'profiles_core_setup_globals' );
	}

	/**
	 * Setup cache groups
	 *
	 * @since 2.2.0
	 */
	public function setup_cache_groups() {

		// Global groups.
		wp_cache_add_global_groups( array(
			'profiles'
		) );

		parent::setup_cache_groups();
	}

	/**
	 * Set up post types.
	 *
	 * @since Profiles (2.4.0)
	 */
	public function register_post_types() {

		// Emails
		if ( profiles_is_root_blog() && ! is_network_admin() ) {
			register_post_type(
				profiles_get_email_post_type(),
				apply_filters( 'profiles_register_email_post_type', array(
					'description'       => _x( 'Profiles emails', 'email post type description', 'profiles' ),
					'labels'            => profiles_get_email_post_type_labels(),
					'menu_icon'         => 'dashicons-email',
					'public'            => false,
					'publicly_queryable' => profiles_current_user_can( 'profiles_moderate' ),
					'query_var'         => false,
					'rewrite'           => false,
					'show_in_admin_bar' => false,
					'show_ui'           => profiles_current_user_can( 'profiles_moderate' ),
					'supports'          => profiles_get_email_post_type_supports(),
				) )
			);
		}

		parent::register_post_types();
	}
}

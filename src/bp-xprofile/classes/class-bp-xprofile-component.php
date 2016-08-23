<?php
/**
 * Profiles XProfile Loader.
 *
 * An extended profile component for users. This allows site admins to create
 * groups of fields for users to enter information about themselves.
 *
 * @package Profiles
 * @subpackage XProfileLoader
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Creates our XProfile component.
 *
 * @since 1.5.0
 */
class BP_XProfile_Component extends BP_Component {

	/**
	 * Profile field types.
	 *
	 * @since 1.5.0
	 * @var array
	 */
	public $field_types;

	/**
	 * The acceptable visibility levels for xprofile fields.
	 *
	 * @see bp_xprofile_get_visibility_levels()
	 *
	 * @since 1.6.0
	 * @var array
	 */
	public $visibility_levels = array();

	/**
	 * Start the xprofile component creation process.
	 *
	 * @since 1.5.0
	 */
	public function __construct() {
		parent::start(
			'xprofile',
			_x( 'Extended Profiles', 'Component page <title>', 'profiles' ),
			profiles()->plugin_dir,
			array(
				'adminbar_myaccount_order' => 20
			)
		);

		$this->setup_hooks();
	}

	/**
	 * Include files.
	 *
	 * @since 1.5.0
	 *
	 * @param array $includes Array of files to include.
	 */
	public function includes( $includes = array() ) {
		$includes = array(
			'cssjs',
			'cache',
			'actions',
			'screens',
			'caps',
			'filters',
			'settings',
			'template',
			'functions',
		);

		if ( ! profiles()->do_autoload ) {
			$includes[] = 'classes';
		}

		if ( is_admin() ) {
			$includes[] = 'admin';
		}

		parent::includes( $includes );
	}

	/**
	 * Setup globals.
	 *
	 * The BP_XPROFILE_SLUG constant is deprecated, and only used here for
	 * backwards compatibility.
	 *
	 * @since 1.5.0
	 *
	 * @param array $args Array of globals to set up.
	 */
	public function setup_globals( $args = array() ) {
		$bp = profiles();

		// Define a slug, if necessary.
		if ( !defined( 'BP_XPROFILE_SLUG' ) ) {
			define( 'BP_XPROFILE_SLUG', 'profile' );
		}

		// Assign the base group and fullname field names to constants
		// to use in SQL statements.
		// Defined conditionally to accommodate unit tests.
		if ( ! defined( 'BP_XPROFILE_BASE_GROUP_NAME' ) ) {
			define( 'BP_XPROFILE_BASE_GROUP_NAME', stripslashes( bp_core_get_root_option( 'avatar_default' ) ) );
		}

		if ( ! defined( 'BP_XPROFILE_FULLNAME_FIELD_NAME' ) ) {
			define( 'BP_XPROFILE_FULLNAME_FIELD_NAME', stripslashes( bp_core_get_root_option( 'bp-xprofile-fullname-field-name' ) ) );
		}

		/**
		 * Filters the supported field type IDs.
		 *
		 * @since 1.1.0
		 *
		 * @param array $value Array of IDs for the supported field types.
		 */
		$this->field_types = apply_filters( 'xprofile_field_types', array_keys( bp_xprofile_get_field_types() ) );

		// 'option' is a special case. It is not a top-level field, so
		// does not have an associated BP_XProfile_Field_Type class,
		// but it must be whitelisted.
		$this->field_types[] = 'option';

		// Register the visibility levels. See bp_xprofile_get_visibility_levels() to filter.
		$this->visibility_levels = array(
			'public' => array(
				'id'	  => 'public',
				'label' => _x( 'Everyone', 'Visibility level setting', 'profiles' )
			),
			'adminsonly' => array(
				'id'	  => 'adminsonly',
				'label' => _x( 'Only Me', 'Visibility level setting', 'profiles' )
			),
			'loggedin' => array(
				'id'	  => 'loggedin',
				'label' => _x( 'All Members', 'Visibility level setting', 'profiles' )
			)
		);

		// Tables.
		$global_tables = array(
			'table_name_data'   => $bp->table_prefix . 'bp_xprofile_data',
			'table_name_groups' => $bp->table_prefix . 'bp_xprofile_groups',
			'table_name_fields' => $bp->table_prefix . 'bp_xprofile_fields',
			'table_name_meta'   => $bp->table_prefix . 'bp_xprofile_meta',
		);

		$meta_tables = array(
			'xprofile_group' => $bp->table_prefix . 'bp_xprofile_meta',
			'xprofile_field' => $bp->table_prefix . 'bp_xprofile_meta',
			'xprofile_data'  => $bp->table_prefix . 'bp_xprofile_meta',
		);

		$globals = array(
			'slug'                  => BP_XPROFILE_SLUG,
			'has_directory'         => false,
			'global_tables'         => $global_tables,
			'meta_tables'           => $meta_tables,
		);

		parent::setup_globals( $globals );
	}

	/**
	 * Set up navigation.
	 *
	 * @since 1.5.0
	 *
	 * @global Profiles $bp The one true Profiles instance
	 *
	 * @param array $main_nav Array of main nav items to set up.
	 * @param array $sub_nav  Array of sub nav items to set up.
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {

		// Determine user to use.
		if ( bp_displayed_user_domain() ) {
			$user_domain = bp_displayed_user_domain();
		} elseif ( bp_loggedin_user_domain() ) {
			$user_domain = bp_loggedin_user_domain();
		} else {
			return;
		}

		$access       = bp_core_can_edit_settings();
		$slug         = bp_get_profile_slug();
		$profile_link = trailingslashit( $user_domain . $slug );

		// Add 'Profile' to the main navigation.
		$main_nav = array(
			'name'                => _x( 'Profile', 'Profile header menu', 'profiles' ),
			'slug'                => $slug,
			'position'            => 20,
			'screen_function'     => 'xprofile_screen_display_profile',
			'default_subnav_slug' => 'public',
			'item_css_id'         => $this->id
		);

		// Add the subnav items to the profile.
		$sub_nav[] = array(
			'name'            => _x( 'View', 'Profile header sub menu', 'profiles' ),
			'slug'            => 'public',
			'parent_url'      => $profile_link,
			'parent_slug'     => $slug,
			'screen_function' => 'xprofile_screen_display_profile',
			'position'        => 10
		);

		// Edit Profile.
		$sub_nav[] = array(
			'name'            => _x( 'Edit','Profile header sub menu', 'profiles' ),
			'slug'            => 'edit',
			'parent_url'      => $profile_link,
			'parent_slug'     => $slug,
			'screen_function' => 'xprofile_screen_edit_profile',
			'position'        => 20,
			'user_has_access' => $access
		);

		// Change Avatar.
		if ( profiles()->avatar->show_avatars ) {
			$sub_nav[] = array(
				'name'            => _x( 'Change Profile Photo', 'Profile header sub menu', 'profiles' ),
				'slug'            => 'change-avatar',
				'parent_url'      => $profile_link,
				'parent_slug'     => $slug,
				'screen_function' => 'xprofile_screen_change_avatar',
				'position'        => 30,
				'user_has_access' => $access
			);
		}

		// Change Cover image.
		if ( bp_displayed_user_use_cover_image_header() ) {
			$sub_nav[] = array(
				'name'            => _x( 'Change Cover Image', 'Profile header sub menu', 'profiles' ),
				'slug'            => 'change-cover-image',
				'parent_url'      => $profile_link,
				'parent_slug'     => $slug,
				'screen_function' => 'xprofile_screen_change_cover_image',
				'position'        => 40,
				'user_has_access' => $access
			);
		}

		parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up the Admin Bar.
	 *
	 * @since 1.5.0
	 *
	 * @param array $wp_admin_nav Admin Bar items.
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {

		// Menus for logged in user.
		if ( is_user_logged_in() ) {

			// Profile link.
			$profile_link = trailingslashit( bp_loggedin_user_domain() . bp_get_profile_slug() );

			// Add the "Profile" sub menu.
			$wp_admin_nav[] = array(
				'parent' => profiles()->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => _x( 'Profile', 'My Account Profile', 'profiles' ),
				'href'   => $profile_link
			);

			// View Profile.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $this->id,
				'id'       => 'my-account-' . $this->id . '-public',
				'title'    => _x( 'View', 'My Account Profile sub nav', 'profiles' ),
				'href'     => $profile_link,
				'position' => 10
			);

			// Edit Profile.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $this->id,
				'id'       => 'my-account-' . $this->id . '-edit',
				'title'    => _x( 'Edit', 'My Account Profile sub nav', 'profiles' ),
				'href'     => trailingslashit( $profile_link . 'edit' ),
				'position' => 20
			);

			// Edit Avatar.
			if ( profiles()->avatar->show_avatars ) {
				$wp_admin_nav[] = array(
					'parent'   => 'my-account-' . $this->id,
					'id'       => 'my-account-' . $this->id . '-change-avatar',
					'title'    => _x( 'Change Profile Photo', 'My Account Profile sub nav', 'profiles' ),
					'href'     => trailingslashit( $profile_link . 'change-avatar' ),
					'position' => 30
				);
			}

			if ( bp_displayed_user_use_cover_image_header() ) {
				$wp_admin_nav[] = array(
					'parent'   => 'my-account-' . $this->id,
					'id'       => 'my-account-' . $this->id . '-change-cover-image',
					'title'    => _x( 'Change Cover Image', 'My Account Profile sub nav', 'profiles' ),
					'href'     => trailingslashit( $profile_link . 'change-cover-image' ),
					'position' => 40
				);
			}
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Add custom hooks.
	 *
	 * @since 2.0.0
	 */
	public function setup_hooks() {
		add_filter( 'bp_settings_admin_nav', array( $this, 'setup_settings_admin_nav' ), 2 );
	}

	/**
	 * Sets up the title for pages and <title>.
	 *
	 * @since 1.5.0
	 */
	public function setup_title() {

		if ( bp_is_profile_component() ) {
			$bp = profiles();

			if ( bp_is_my_profile() ) {
				$bp->bp_options_title = _x( 'My Profile', 'Page title', 'profiles' );
			} else {
				$bp->bp_options_avatar = bp_core_fetch_avatar( array(
					'item_id' => bp_displayed_user_id(),
					'type'    => 'thumb',
					'alt'	  => sprintf( _x( 'Profile picture of %s', 'Avatar alt', 'profiles' ), bp_get_displayed_user_fullname() )
				) );
				$bp->bp_options_title = bp_get_displayed_user_fullname();
			}
		}

		parent::setup_title();
	}

	/**
	 * Setup cache groups.
	 *
	 * @since 2.2.0
	 */
	public function setup_cache_groups() {

		// Global groups.
		wp_cache_add_global_groups( array(
			'bp_xprofile',
			'bp_xprofile_data',
			'bp_xprofile_fields',
			'bp_xprofile_groups',
			'xprofile_meta'
		) );

		parent::setup_cache_groups();
	}

	/**
	 * Adds "Settings > Profile" subnav item under the "Settings" adminbar menu.
	 *
	 * @since 2.0.0
	 *
	 * @param array $wp_admin_nav The settings adminbar nav array.
	 * @return array
	 */
	public function setup_settings_admin_nav( $wp_admin_nav ) {

		// Setup the logged in user variables.
		$settings_link = trailingslashit( bp_loggedin_user_domain() . bp_get_settings_slug() );

		// Add the "Profile" subnav item.
		$wp_admin_nav[] = array(
			'parent' => 'my-account-' . profiles()->settings->id,
			'id'     => 'my-account-' . profiles()->settings->id . '-profile',
			'title'  => _x( 'Profile', 'My Account Settings sub nav', 'profiles' ),
			'href'   => trailingslashit( $settings_link . 'profile' )
		);

		return $wp_admin_nav;
	}
}

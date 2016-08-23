<?php
/**
 * The Profiles Plugin.
 *
 * Profiles is built on the solid foundation of the BuddyPress plugin. 
 * We decided to fork this project to provide a more generic Profiles 
 * plugin that could be easily extended for more specific use-cases.
 *
 * @package Profiles
 * @subpackage Main
 * @since 0.0.1
 */

/**
 * Plugin Name: Profiles
 * Plugin URI:  https://wordpress.org/plugins/profiles/
 * Description: Profiles is built on the solid foundation of the Profiles plugin. We decided to fork this project to provide a more generic Profiles plugin that could be easily extended for more specific use-cases.
 * Author:      OpenTute+
 * Author URI:  http://opentuteplus.com/
 * Version:     0.0.1
 * Text Domain: profiles
 * Domain Path: /profiles-languages/
 * License:     GPLv3 or later (license.txt)
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Constants *****************************************************************/

if ( !class_exists( 'Profiles' ) ) :
/**
 * Main Profiles Class.
 *
 * Tap tap tap... Is this thing on?
 *
 * @since 1.6.0
 */
class Profiles {

	/** Magic *****************************************************************/

	/**
	 * Profiles uses many variables, most of which can be filtered to
	 * customize the way that it works. To prevent unauthorized access,
	 * these variables are stored in a private array that is magically
	 * updated using PHP 5.2+ methods. This is to prevent third party
	 * plugins from tampering with essential information indirectly, which
	 * would cause issues later.
	 *
	 * @see Profiles::setup_globals()
	 * @var array
	 */
	private $data;

	/** Not Magic *************************************************************/

	/**
	 * @var array Primary Profiles navigation.
	 */
	public $bp_nav = array();

	/**
	 * @var array Secondary Profiles navigation to $bp_nav.
	 */
	public $bp_options_nav = array();

	/**
	 * @var array The unfiltered URI broken down into chunks.
	 * @see bp_core_set_uri_globals()
	 */
	public $unfiltered_uri = array();

	/**
	 * @var array The canonical URI stack.
	 * @see bp_redirect_canonical()
	 * @see bp_core_new_nav_item()
	 */
	public $canonical_stack = array();

	/**
	 * @var array Additional navigation elements (supplemental).
	 */
	public $action_variables = array();

	/**
	 * @var string Current member directory type.
	 */
	public $current_member_type = '';

	/**
	 * @var array Required components (core, members).
	 */
	public $required_components = array();

	/**
	 * @var array Additional active components.
	 */
	public $loaded_components = array();

	/**
	 * @var array Active components.
	 */
	public $active_components = array();

	/**
	 * Whether autoload is in use.
	 *
	 * @since 2.5.0
	 * @var bool
	 */
	public $do_autoload = false;

	/**
	 * Whether to load backward compatibility classes for navigation globals.
	 *
	 * @since 2.6.0
	 * @var bool
	 */
	public $do_nav_backcompat = false;

	/** Option Overload *******************************************************/

	/**
	 * @var array Optional Overloads default options retrieved from get_option().
	 */
	public $options = array();

	/** Singleton *************************************************************/

	/**
	 * Main Profiles Instance.
	 *
	 * Profiles is great.
	 * Please load it only one time.
	 * For this, we thank you.
	 *
	 * Insures that only one instance of Profiles exists in memory at any
	 * one time. Also prevents needing to define globals all over the place.
	 *
	 * @since 1.7.0
	 *
	 * @static object $instance
	 * @see profiles()
	 *
	 * @return Profiles The one true Profiles.
	 */
	public static function instance() {

		// Store the instance locally to avoid private static replication
		static $instance = null;

		// Only run these methods if they haven't been run previously
		if ( null === $instance ) {
			$instance = new Profiles;
			$instance->constants();
			$instance->setup_globals();
			$instance->legacy_constants();
			$instance->includes();
			$instance->setup_actions();
		}

		// Always return the instance
		return $instance;

		// The last metroid is in captivity. The galaxy is at peace.
	}

	/** Magic Methods *********************************************************/

	/**
	 * A dummy constructor to prevent Profiles from being loaded more than once.
	 *
	 * @since 1.7.0
	 * @see Profiles::instance()
	 * @see profiles()
	 */
	private function __construct() { /* Do nothing here */ }

	/**
	 * A dummy magic method to prevent Profiles from being cloned.
	 *
	 * @since 1.7.0
	 */
	public function __clone() { _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'profiles' ), '1.7' ); }

	/**
	 * A dummy magic method to prevent Profiles from being unserialized.
	 *
	 * @since 1.7.0
	 */
	public function __wakeup() { _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'profiles' ), '1.7' ); }

	/**
	 * Magic method for checking the existence of a certain custom field.
	 *
	 * @since 1.7.0
	 *
	 * @param string $key Key to check the set status for.
	 *
	 * @return bool
	 */
	public function __isset( $key ) { return isset( $this->data[$key] ); }

	/**
	 * Magic method for getting Profiles variables.
	 *
	 * @since 1.7.0
	 *
	 * @param string $key Key to return the value for.
	 *
	 * @return mixed
	 */
	public function __get( $key ) { return isset( $this->data[$key] ) ? $this->data[$key] : null; }

	/**
	 * Magic method for setting Profiles variables.
	 *
	 * @since 1.7.0
	 *
	 * @param string $key   Key to set a value for.
	 * @param mixed  $value Value to set.
	 */
	public function __set( $key, $value ) { $this->data[$key] = $value; }

	/**
	 * Magic method for unsetting Profiles variables.
	 *
	 * @since 1.7.0
	 *
	 * @param string $key Key to unset a value for.
	 */
	public function __unset( $key ) { if ( isset( $this->data[$key] ) ) unset( $this->data[$key] ); }

	/**
	 * Magic method to prevent notices and errors from invalid method calls.
	 *
	 * @since 1.7.0
	 *
	 * @param string $name
	 * @param array  $args
	 *
	 * @return null
	 */
	public function __call( $name = '', $args = array() ) { unset( $name, $args ); return null; }

	/** Private Methods *******************************************************/

	/**
	 * Bootstrap constants.
	 *
	 * @since 1.6.0
	 *
	 */
	private function constants() {

		// Place your custom code (actions/filters) in a file called
		// '/plugins/bp-custom.php' and it will be loaded before anything else.
		if ( file_exists( WP_PLUGIN_DIR . '/bp-custom.php' ) ) {
			require( WP_PLUGIN_DIR . '/bp-custom.php' );
		}

		// Path and URL
		if ( ! defined( 'BP_PLUGIN_DIR' ) ) {
			define( 'BP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}

		if ( ! defined( 'BP_PLUGIN_URL' ) ) {
			define( 'BP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}

		// Only applicable to those running trunk
		if ( ! defined( 'BP_SOURCE_SUBDIRECTORY' ) ) {
			define( 'BP_SOURCE_SUBDIRECTORY', '' );
		}

		// Define on which blog ID Profiles should run
		if ( ! defined( 'BP_ROOT_BLOG' ) ) {

			// Default to use current blog ID
			// Fulfills non-network installs and BP_ENABLE_MULTIBLOG installs
			$root_blog_id = get_current_blog_id();

			// Multisite check
			if ( is_multisite() ) {

				// Multiblog isn't enabled
				if ( ! defined( 'BP_ENABLE_MULTIBLOG' ) || ( defined( 'BP_ENABLE_MULTIBLOG' ) && (int) constant( 'BP_ENABLE_MULTIBLOG' ) === 0 ) ) {
					// Check to see if BP is network-activated
					// We're not using is_plugin_active_for_network() b/c you need to include the
					// /wp-admin/includes/plugin.php file in order to use that function.

					// get network-activated plugins
					$plugins = get_site_option( 'active_sitewide_plugins');

					// basename
					$basename = basename( constant( 'BP_PLUGIN_DIR' ) ) . '/bp-loader.php';

					// plugin is network-activated; use main site ID instead
					if ( isset( $plugins[ $basename ] ) ) {
						$current_site = get_current_site();
						$root_blog_id = $current_site->blog_id;
					}
				}

			}

			define( 'BP_ROOT_BLOG', $root_blog_id );
		}

		// Whether to refrain from loading deprecated functions
		if ( ! defined( 'BP_IGNORE_DEPRECATED' ) ) {
			define( 'BP_IGNORE_DEPRECATED', false );
		}

		// The search slug has to be defined nice and early because of the way
		// search requests are loaded
		//
		// @todo Make this better
		if ( ! defined( 'BP_SEARCH_SLUG' ) ) {
			define( 'BP_SEARCH_SLUG', 'search' );
		}
	}

	/**
	 * Component global variables.
	 *
	 * @since 1.6.0
	 *
	 */
	private function setup_globals() {

		/** Versions **********************************************************/

		$this->version    = '2.7-alpha';
		$this->db_version = 10469;

		/** Loading ***********************************************************/

		/**
		 * Filters the load_deprecated property value.
		 *
		 * @since 2.0.0
		 *
		 * @const constant BP_IGNORE_DEPRECATED Whether or not to ignore deprecated functionality.
		 */
		$this->load_deprecated = ! apply_filters( 'bp_ignore_deprecated', BP_IGNORE_DEPRECATED );

		/** Toolbar ***********************************************************/

		/**
		 * @var string The primary toolbar ID.
		 */
		$this->my_account_menu_id = '';

		/** URIs **************************************************************/

		/**
		 * @var int The current offset of the URI.
		 * @see bp_core_set_uri_globals()
		 */
		$this->unfiltered_uri_offset = 0;

		/**
		 * @var bool Are status headers already sent?
		 */
		$this->no_status_set = false;

		/** Components ********************************************************/

		/**
		 * @var string Name of the current Profiles component (primary).
		 */
		$this->current_component = '';

		/**
		 * @var string Name of the current Profiles item (secondary).
		 */
		$this->current_item = '';

		/**
		 * @var string Name of the current Profiles action (tertiary).
		 */
		$this->current_action = '';

		/**
		 * @var bool Displaying custom 2nd level navigation menu (I.E a group).
		 */
		$this->is_single_item = false;

		/** Root **************************************************************/

		/**
		 * Filters the Profiles Root blog ID.
		 *
		 * @since 1.5.0
		 *
		 * @const constant BP_ROOT_BLOG Profiles Root blog ID.
		 */
		$this->root_blog_id = (int) apply_filters( 'bp_get_root_blog_id', BP_ROOT_BLOG );

		/** Paths**************************************************************/

		// Profiles root directory
		$this->file           = constant( 'BP_PLUGIN_DIR' ) . 'bp-loader.php';
		$this->basename       = basename( constant( 'BP_PLUGIN_DIR' ) ) . '/bp-loader.php';
		$this->plugin_dir     = trailingslashit( constant( 'BP_PLUGIN_DIR' ) . constant( 'BP_SOURCE_SUBDIRECTORY' ) );
		$this->plugin_url     = trailingslashit( constant( 'BP_PLUGIN_URL' ) . constant( 'BP_SOURCE_SUBDIRECTORY' ) );

		// Languages
		$this->lang_dir       = $this->plugin_dir . 'bp-languages';

		// Templates (theme compatibility)
		$this->themes_dir     = $this->plugin_dir . 'bp-templates';
		$this->themes_url     = $this->plugin_url . 'bp-templates';

		// Themes (for bp-default)
		$this->old_themes_dir = $this->plugin_dir . 'bp-themes';
		$this->old_themes_url = $this->plugin_url . 'bp-themes';

		/** Theme Compat ******************************************************/

		$this->theme_compat   = new stdClass(); // Base theme compatibility class
		$this->filters        = new stdClass(); // Used when adding/removing filters

		/** Users *************************************************************/

		$this->current_user   = new stdClass();
		$this->displayed_user = new stdClass();

		/** Post types and taxonomies *****************************************/
		$this->email_post_type     = apply_filters( 'bp_email_post_type', 'bp-email' );
		$this->email_taxonomy_type = apply_filters( 'bp_email_tax_type', 'bp-email-type' );

		/** Navigation backward compatibility *********************************/
		if ( interface_exists( 'ArrayAccess', false ) ) {
			// bp_nav and bp_options_nav compatibility depends on SPL.
			$this->do_nav_backcompat = true;
		}
	}

	/**
	 * Legacy Profiles constants.
	 *
	 * Try to avoid using these. Their values have been moved into variables
	 * in the instance, and have matching functions to get/set their values.
	 *
	 * @since 1.7.0
	 */
	private function legacy_constants() {

		// Define the Profiles version
		if ( ! defined( 'BP_VERSION' ) ) {
			define( 'BP_VERSION', $this->version );
		}

		// Define the database version
		if ( ! defined( 'BP_DB_VERSION' ) ) {
			define( 'BP_DB_VERSION', $this->db_version );
		}
	}

	/**
	 * Include required files.
	 *
	 * @since 1.6.0
	 *
	 */
	private function includes() {
		if ( function_exists( 'spl_autoload_register' ) ) {
			spl_autoload_register( array( $this, 'autoload' ) );
			$this->do_autoload = true;
		}

		// Load the WP abstraction file so Profiles can run on all WordPress setups.
		require( $this->plugin_dir . 'bp-core/bp-core-wpabstraction.php' );

		// Setup the versions (after we include multisite abstraction above)
		$this->versions();

		/** Update/Install ****************************************************/

		// Theme compatibility
		require( $this->plugin_dir . 'bp-core/bp-core-template-loader.php'     );
		require( $this->plugin_dir . 'bp-core/bp-core-theme-compatibility.php' );

		// Require all of the Profiles core libraries
		require( $this->plugin_dir . 'bp-core/bp-core-dependency.php'       );
		require( $this->plugin_dir . 'bp-core/bp-core-actions.php'          );
		require( $this->plugin_dir . 'bp-core/bp-core-caps.php'             );
		require( $this->plugin_dir . 'bp-core/bp-core-cache.php'            );
		require( $this->plugin_dir . 'bp-core/bp-core-cssjs.php'            );
		require( $this->plugin_dir . 'bp-core/bp-core-update.php'           );
		require( $this->plugin_dir . 'bp-core/bp-core-options.php'          );
		require( $this->plugin_dir . 'bp-core/bp-core-taxonomy.php'         );
		require( $this->plugin_dir . 'bp-core/bp-core-filters.php'          );
		require( $this->plugin_dir . 'bp-core/bp-core-attachments.php'      );
		require( $this->plugin_dir . 'bp-core/bp-core-avatars.php'          );
		require( $this->plugin_dir . 'bp-core/bp-core-widgets.php'          );
		require( $this->plugin_dir . 'bp-core/bp-core-template.php'         );
		require( $this->plugin_dir . 'bp-core/bp-core-adminbar.php'         );
		require( $this->plugin_dir . 'bp-core/bp-core-buddybar.php'         );
		require( $this->plugin_dir . 'bp-core/bp-core-catchuri.php'         );
		require( $this->plugin_dir . 'bp-core/bp-core-functions.php'        );
		require( $this->plugin_dir . 'bp-core/bp-core-moderation.php'       );
		require( $this->plugin_dir . 'bp-core/bp-core-loader.php'           );
		require( $this->plugin_dir . 'bp-core/bp-core-customizer-email.php' );

		if ( ! $this->do_autoload ) {
			require( $this->plugin_dir . 'bp-core/bp-core-classes.php' );
		}
	}

	/**
	 * Autoload classes.
	 *
	 * @since 2.5.0
	 *
	 * @param string $class
	 */
	public function autoload( $class ) {
		$class_parts = explode( '_', strtolower( $class ) );

		if ( 'bp' !== $class_parts[0] ) {
			return;
		}

		$components = array(
			'activity',
			'core',
			//'groups',
			'members',
			'xprofile',
		);

		// These classes don't have a name that matches their component.
		$irregular_map = array(
			'BP_Akismet' => 'activity',

			'BP_Admin'                     => 'core',
			'BP_Attachment_Avatar'         => 'core',
			'BP_Attachment_Cover_Image'    => 'core',
			'BP_Attachment'                => 'core',
			'BP_Button'                    => 'core',
			'BP_Component'                 => 'core',
			'BP_Customizer_Control_Range'  => 'core',
			'BP_Date_Query'                => 'core',
			'BP_Email_Delivery'            => 'core',
			'BP_Email_Recipient'           => 'core',
			'BP_Email'                     => 'core',
			'BP_Embed'                     => 'core',
			'BP_Media_Extractor'           => 'core',
			'BP_Members_Suggestions'       => 'core',
			'BP_PHPMailer'                 => 'core',
			'BP_Recursive_Query'           => 'core',
			'BP_Suggestions'               => 'core',
			'BP_Theme_Compat'              => 'core',
			'BP_User_Query'                => 'core',
			'BP_Walker_Category_Checklist' => 'core',
			'BP_Walker_Nav_Menu_Checklist' => 'core',
			'BP_Walker_Nav_Menu'           => 'core',

			//'BP_Core_Friends_Widget' => 'friends',

			'BP_Group_Extension'    => 'groups',
			'BP_Group_Member_Query' => 'groups',

			'BP_Core_Members_Template'       => 'members',
			'BP_Core_Members_Widget'         => 'members',
			'BP_Core_Recently_Active_Widget' => 'members',
			'BP_Core_Whos_Online_Widget'     => 'members',
			'BP_Registration_Theme_Compat'   => 'members',
			'BP_Signup'                      => 'members',
		);

		$component = null;

		// First check to see if the class is one without a properly namespaced name.
		if ( isset( $irregular_map[ $class ] ) ) {
			$component = $irregular_map[ $class ];

		// Next chunk is usually the component name.
		} elseif ( in_array( $class_parts[1], $components, true ) ) {
			$component = $class_parts[1];
		}

		if ( ! $component ) {
			return;
		}

		// Sanitize class name.
		$class = strtolower( str_replace( '_', '-', $class ) );

		$path = dirname( __FILE__ ) . "/bp-{$component}/classes/class-{$class}.php";

		// Sanity check.
		if ( ! file_exists( $path ) ) {
			return;
		}

		/*
		 * Sanity check 2 - Check if component is active before loading class.
		 * Skip if PHPUnit is running, or Profiles is installing for the first time.
		 */
		if (
			! in_array( $component, array( 'core', 'members' ), true ) &&
			! bp_is_active( $component ) &&
			! function_exists( 'tests_add_filter' )
		) {
			return;
		}

		require $path;
	}

	/**
	 * Set up the default hooks and actions.
	 *
	 * @since 1.6.0
	 *
	 */
	private function setup_actions() {

		// Add actions to plugin activation and deactivation hooks
		add_action( 'activate_'   . $this->basename, 'bp_activation'   );
		add_action( 'deactivate_' . $this->basename, 'bp_deactivation' );

		// If Profiles is being deactivated, do not add any actions
		if ( bp_is_deactivation( $this->basename ) ) {
			return;
		}

		// Array of Profiles core actions
		$actions = array(
			'setup_theme',              // Setup the default theme compat
			'setup_current_user',       // Setup currently logged in user
			'register_post_types',      // Register post types
			'register_post_statuses',   // Register post statuses
			'register_taxonomies',      // Register taxonomies
			'register_views',           // Register the views
			'register_theme_directory', // Register the theme directory
			'register_theme_packages',  // Register bundled theme packages (bp-themes)
			'load_textdomain',          // Load textdomain
			'add_rewrite_tags',         // Add rewrite tags
			'generate_rewrite_rules'    // Generate rewrite rules
		);

		// Add the actions
		foreach( $actions as $class_action ) {
			if ( method_exists( $this, $class_action ) ) {
				add_action( 'bp_' . $class_action, array( $this, $class_action ), 5 );
			}
		}

		/**
		 * Fires after the setup of all Profiles actions.
		 *
		 * Includes bbp-core-hooks.php.
		 *
		 * @since 1.7.0
		 *
		 * @param Profiles $this. Current Profiles instance. Passed by reference.
		 */
		do_action_ref_array( 'bp_after_setup_actions', array( &$this ) );
	}

	/**
	 * Private method to align the active and database versions.
	 *
	 * @since 1.7.0
	 */
	private function versions() {

		// Get the possible DB versions (boy is this gross)
		$versions               = array();
		$versions['1.6-single'] = get_blog_option( $this->root_blog_id, '_bp_db_version' );

		// 1.6-single exists, so trust it
		if ( !empty( $versions['1.6-single'] ) ) {
			$this->db_version_raw = (int) $versions['1.6-single'];

		// If no 1.6-single exists, use the max of the others
		} else {
			$versions['1.2']        = get_site_option(                      'bp-core-db-version' );
			$versions['1.5-multi']  = get_site_option(                           'bp-db-version' );
			$versions['1.6-multi']  = get_site_option(                          '_bp_db_version' );
			$versions['1.5-single'] = get_blog_option( $this->root_blog_id,      'bp-db-version' );

			// Remove empty array items
			$versions             = array_filter( $versions );
			$this->db_version_raw = (int) ( !empty( $versions ) ) ? (int) max( $versions ) : 0;
		}
	}

	/** Public Methods ********************************************************/

	/**
	 * Set up Profiles's legacy theme directory.
	 *
	 * Starting with version 1.2, and ending with version 1.8, Profiles
	 * registered a custom theme directory - bp-themes - which contained
	 * the bp-default theme. Since Profiles 1.9, bp-themes is no longer
	 * registered (and bp-default no longer offered) on new installations.
	 * Sites using bp-default (or a child theme of bp-default) will
	 * continue to have bp-themes registered as before.
	 *
	 * @since 1.5.0
	 *
	 * @todo Move bp-default to wordpress.org/extend/themes and remove this.
	 */
	public function register_theme_directory() {
		if ( ! bp_do_register_theme_directory() ) {
			return;
		}

		register_theme_directory( $this->old_themes_dir );
	}

	/**
	 * Register bundled theme packages.
	 *
	 * Note that since we currently have complete control over bp-themes and
	 * the bp-legacy folders, it's fine to hardcode these here. If at a
	 * later date we need to automate this, an API will need to be built.
	 *
	 * @since 1.7.0
	 */
	public function register_theme_packages() {

		// Register the default theme compatibility package
		bp_register_theme_package( array(
			'id'      => 'legacy',
			'name'    => __( 'Profiles Default', 'profiles' ),
			'version' => bp_get_version(),
			'dir'     => trailingslashit( $this->themes_dir . '/bp-legacy' ),
			'url'     => trailingslashit( $this->themes_url . '/bp-legacy' )
		) );

		// Register the basic theme stack. This is really dope.
		bp_register_template_stack( 'get_stylesheet_directory', 10 );
		bp_register_template_stack( 'get_template_directory',   12 );
		bp_register_template_stack( 'bp_get_theme_compat_dir',  14 );
	}

	/**
	 * Set up the default Profiles theme compatibility location.
	 *
	 * @since 1.7.0
	 */
	public function setup_theme() {

		// Bail if something already has this under control
		if ( ! empty( $this->theme_compat->theme ) ) {
			return;
		}

		// Setup the theme package to use for compatibility
		bp_setup_theme_compat( bp_get_theme_package_id() );
	}
}

/**
 * The main function responsible for returning the one true Profiles Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $bp = profiles(); ?>
 *
 * @return Profiles The one true Profiles Instance.
 */
function profiles() {
	return Profiles::instance();
}

/**
 * Hook Profiles early onto the 'plugins_loaded' action.
 *
 * This gives all other plugins the chance to load before Profiles, to get
 * their actions, filters, and overrides setup without Profiles being in the
 * way.
 */
if ( defined( 'BUDDYPRESS_LATE_LOAD' ) ) {
	add_action( 'plugins_loaded', 'profiles', (int) BUDDYPRESS_LATE_LOAD );

// "And now here's something we hope you'll really like!"
} else {
	$GLOBALS['bp'] = profiles();
}

endif;

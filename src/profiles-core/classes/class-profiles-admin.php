<?php
/**
 * Main Profiles Admin Class.
 *
 * @package Profiles
 * @suprofilesackage CoreAdministration
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'Profiles_Admin' ) ) :

/**
 * Load Profiles plugin admin area.
 *
 * @todo Break this apart into each applicable Component.
 *
 * @since 1.6.0
 */
class Profiles_Admin {

	/** Directory *************************************************************/

	/**
	 * Path to the Profiles admin directory.
	 *
	 * @since 1.6.0
	 * @var string $admin_dir
	 */
	public $admin_dir = '';

	/** URLs ******************************************************************/

	/**
	 * URL to the Profiles admin directory.
	 *
	 * @since 1.6.0
	 * @var string $admin_url
	 */
	public $admin_url = '';

	/**
	 * URL to the Profiles images directory.
	 *
	 * @since 1.6.0
	 * @var string $images_url
	 */
	public $images_url = '';

	/**
	 * URL to the Profiles admin CSS directory.
	 *
	 * @since 1.6.0
	 * @var string $css_url
	 */
	public $css_url = '';

	/**
	 * URL to the Profiles admin JS directory.
	 *
	 * @since 1.6.0
	 * @var string
	 */
	public $js_url = '';

	/** Other *****************************************************************/

	/**
	 * Notices used for user feedback, like saving settings.
	 *
	 * @since 1.9.0
	 * @var array()
	 */
	public $notices = array();

	/** Methods ***************************************************************/

	/**
	 * The main Profiles admin loader.
	 *
	 * @since 1.6.0
	 *
	 */
	public function __construct() {
		$this->setup_globals();
		$this->includes();
		$this->setup_actions();
	}

	/**
	 * Set admin-related globals.
	 *
	 * @since 1.6.0
	 */
	private function setup_globals() {
		$profiles = profiles();

		// Paths and URLs
		$this->admin_dir  = trailingslashit( $profiles->plugin_dir  . 'profiles-core/admin' ); // Admin path.
		$this->admin_url  = trailingslashit( $profiles->plugin_url  . 'profiles-core/admin' ); // Admin url.
		$this->images_url = trailingslashit( $this->admin_url . 'images'        ); // Admin images URL.
		$this->css_url    = trailingslashit( $this->admin_url . 'css'           ); // Admin css URL.
		$this->js_url     = trailingslashit( $this->admin_url . 'js'            ); // Admin css URL.

		// Main settings page.
		$this->settings_page = profiles_core_do_network_admin() ? 'settings.php' : 'options-general.php';

		// Main capability.
		$this->capability = profiles_core_do_network_admin() ? 'manage_network_options' : 'manage_options';
	}

	/**
	 * Include required files.
	 *
	 * @since 1.6.0
	 */
	private function includes() {
		if ( ! profiles()->do_autoload ) {
			require( $this->admin_dir . 'profiles-core-admin-classes.php'    );
		}

		require( $this->admin_dir . 'profiles-core-admin-actions.php'    );
		require( $this->admin_dir . 'profiles-core-admin-settings.php'   );
		require( $this->admin_dir . 'profiles-core-admin-functions.php'  );
		require( $this->admin_dir . 'profiles-core-admin-components.php' );
		require( $this->admin_dir . 'profiles-core-admin-slugs.php'      );
		require( $this->admin_dir . 'profiles-core-admin-tools.php'      );
	}

	/**
	 * Set up the admin hooks, actions, and filters.
	 *
	 * @since 1.6.0
	 *
	 */
	private function setup_actions() {

		/* General Actions ***************************************************/

		// Add some page specific output to the <head>.
		add_action( 'profiles_admin_head',            array( $this, 'admin_head'  ), 999 );

		// Add menu item to settings menu.
		add_action( 'admin_menu',               array( $this, 'site_admin_menus' ), 5 );
		add_action( profiles_core_admin_hook(),       array( $this, 'admin_menus' ), 5 );

		// Enqueue all admin JS and CSS.
		add_action( 'profiles_admin_enqueue_scripts', array( $this, 'admin_register_styles' ), 1 );
		add_action( 'profiles_admin_enqueue_scripts', array( $this, 'admin_register_scripts' ), 1 );
		add_action( 'profiles_admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		/* Profiles Actions ************************************************/

		// Load the Profiles metabox in the WP Nav Menu Admin UI.
		add_action( 'load-nav-menus.php', 'profiles_admin_wp_nav_menu_meta_box' );

		// Add settings.
		add_action( 'profiles_register_admin_settings', array( $this, 'register_admin_settings' ) );

		// Add a link to Profiles About page to the admin bar.
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_about_link' ), 15 );

		// Add a description of new Profiles tools in the available tools page.
		add_action( 'tool_box',            'profiles_core_admin_available_tools_intro' );
		add_action( 'profiles_network_tool_box', 'profiles_core_admin_available_tools_intro' );

		// On non-multisite, catch.
		add_action( 'load-users.php', 'profiles_core_admin_user_manage_spammers' );

		// Emails.
		add_filter( 'manage_' . profiles_get_email_post_type() . '_posts_columns',       array( $this, 'emails_register_situation_column' ) );
		add_action( 'manage_' . profiles_get_email_post_type() . '_posts_custom_column', array( $this, 'emails_display_situation_column_data' ), 10, 2 );

		/* Filters ***********************************************************/

		// Add link to settings page.
		add_filter( 'plugin_action_links',               array( $this, 'modify_plugin_action_links' ), 10, 2 );
		add_filter( 'network_admin_plugin_action_links', array( $this, 'modify_plugin_action_links' ), 10, 2 );

		// Add "Mark as Spam" row actions on users.php.
		add_filter( 'ms_user_row_actions', 'profiles_core_admin_user_row_actions', 10, 2 );
		add_filter( 'user_row_actions',    'profiles_core_admin_user_row_actions', 10, 2 );

		// Emails
		add_filter( 'profiles_admin_menu_order', array( $this, 'emails_admin_menu_order' ), 20 );
	}

	/**
	 * Register site- or network-admin nav menu elements.
	 *
	 * Contextually hooked to site or network-admin depending on current configuration.
	 *
	 * @since 1.6.0
	 *
	 *       section.
	 */
	public function admin_menus() {

		// Bail if user cannot moderate.
		if ( ! profiles_current_user_can( 'manage_options' ) ) {
			return;
		}

		// About.
		add_dashboard_page(
			__( 'Welcome to Profiles',  'profiles' ),
			__( 'Welcome to Profiles',  'profiles' ),
			'manage_options',
			'profiles-about',
			array( $this, 'about_screen' )
		);

		// Credits.
		add_dashboard_page(
			__( 'Welcome to Profiles',  'profiles' ),
			__( 'Welcome to Profiles',  'profiles' ),
			'manage_options',
			'profiles-credits',
			array( $this, 'credits_screen' )
		);

		$hooks = array();

		// Changed in BP 1.6 . See profiles_core_admin_backpat_menu().
		$hooks[] = add_menu_page(
			__( 'Profiles', 'profiles' ),
			__( 'Profiles', 'profiles' ),
			$this->capability,
			'profiles-general-settings',
			'profiles_core_admin_backpat_menu',
			'div'
		);

		$hooks[] = add_submenu_page(
			'profiles-general-settings',
			__( 'Profiles Help', 'profiles' ),
			__( 'Help', 'profiles' ),
			$this->capability,
			'profiles-general-settings',
			'profiles_core_admin_backpat_page'
		);

		// Add the option pages.
		$hooks[] = add_submenu_page(
			$this->settings_page,
			__( 'Profiles Components', 'profiles' ),
			__( 'Profiles', 'profiles' ),
			$this->capability,
			'profiles-components',
			'profiles_core_admin_components_settings'
		);

		$hooks[] = add_submenu_page(
			$this->settings_page,
			__( 'Profiles Pages', 'profiles' ),
			__( 'Profiles Pages', 'profiles' ),
			$this->capability,
			'profiles-page-settings',
			'profiles_core_admin_slugs_settings'
		);

		$hooks[] = add_submenu_page(
			$this->settings_page,
			__( 'Profiles Options', 'profiles' ),
			__( 'Profiles Options', 'profiles' ),
			$this->capability,
			'profiles-settings',
			'profiles_core_admin_settings'
		);

		// For consistency with non-Multisite, we add a Tools menu in
		// the Network Admin as a home for our Tools panel.
		if ( is_multisite() && profiles_core_do_network_admin() ) {
			$tools_parent = 'network-tools';

			$hooks[] = add_menu_page(
				__( 'Tools', 'profiles' ),
				__( 'Tools', 'profiles' ),
				$this->capability,
				$tools_parent,
				'profiles_core_tools_top_level_item',
				'',
				24 // Just above Settings.
			);

			$hooks[] = add_submenu_page(
				$tools_parent,
				__( 'Available Tools', 'profiles' ),
				__( 'Available Tools', 'profiles' ),
				$this->capability,
				'available-tools',
				'profiles_core_admin_available_tools_page'
			);
		} else {
			$tools_parent = 'tools.php';
		}

		$hooks[] = add_submenu_page(
			$tools_parent,
			__( 'Profiles Tools', 'profiles' ),
			__( 'Profiles', 'profiles' ),
			$this->capability,
			'profiles-tools',
			'profiles_core_admin_tools'
		);

		// For network-wide configs, add a link to (the root site's) Emails screen.
		if ( is_network_admin() && profiles_is_network_activated() ) {
			$email_labels = profiles_get_email_post_type_labels();
			$email_url    = get_admin_url( profiles_get_root_blog_id(), 'edit.php?post_type=' . profiles_get_email_post_type() );

			$hooks[] = add_menu_page(
				$email_labels['name'],
				$email_labels['menu_name'],
				$this->capability,
				'',
				'',
				'dashicons-email',
				26
			);

			// Hack: change the link to point to the root site's admin, not the network admin.
			$GLOBALS['menu'][26][2] = esc_url_raw( $email_url );
		}

		foreach( $hooks as $hook ) {
			add_action( "admin_head-$hook", 'profiles_core_modify_admin_menu_highlight' );
		}
	}

	/**
	 * Register site-admin nav menu elements.
	 *
	 * @since 2.5.0
	 */
	public function site_admin_menus() {
		if ( ! profiles_current_user_can( 'manage_options' ) ) {
			return;
		}

		$hooks = array();

		// Require WP 4.0+.
		if ( profiles_is_root_blog() && version_compare( $GLOBALS['wp_version'], '4.0', '>=' ) ) {
			// Appearance > Emails.
			$hooks[] = add_theme_page(
				_x( 'Emails', 'screen heading', 'profiles' ),
				_x( 'Emails', 'screen heading', 'profiles' ),
				$this->capability,
				'profiles-emails-customizer-redirect',
				'profiles_email_redirect_to_customizer'
			);

			// Emails > Customize.
			$hooks[] = add_submenu_page(
				'edit.php?post_type=' . profiles_get_email_post_type(),
				_x( 'Customize', 'email menu label', 'profiles' ),
				_x( 'Customize', 'email menu label', 'profiles' ),
				$this->capability,
				'profiles-emails-customizer-redirect',
				'profiles_email_redirect_to_customizer'
			);
		}

		foreach( $hooks as $hook ) {
			add_action( "admin_head-$hook", 'profiles_core_modify_admin_menu_highlight' );
		}
	}

	/**
	 * Register the settings.
	 *
	 * @since 1.6.0
	 *
	 */
	public function register_admin_settings() {

		/* Main Section ******************************************************/

		// Add the main section.
		add_settings_section( 'profiles_main', __( 'Main Settings', 'profiles' ), 'profiles_admin_setting_callback_main_section', 'profiles' );

		// Hide toolbar for logged out users setting.
		add_settings_field( 'hide-loggedout-adminbar', __( 'Toolbar', 'profiles' ), 'profiles_admin_setting_callback_admin_bar', 'profiles', 'profiles_main' );
		register_setting( 'profiles', 'hide-loggedout-adminbar', 'intval' );

		// Only show 'switch to Toolbar' option if the user chose to retain the BuddyBar during the 1.6 upgrade.
		if ( (bool) profiles_get_option( '_profiles_force_buddybar', false ) ) {
			add_settings_field( '_profiles_force_buddybar', __( 'Toolbar', 'profiles' ), 'profiles_admin_setting_callback_force_buddybar', 'profiles', 'profiles_main' );
			register_setting( 'profiles', '_profiles_force_buddybar', 'profiles_admin_sanitize_callback_force_buddybar' );
		}

		// Allow account deletion.
		add_settings_field( 'profiles-disable-account-deletion', __( 'Account Deletion', 'profiles' ), 'profiles_admin_setting_callback_account_deletion', 'profiles', 'profiles_main' );
		register_setting( 'profiles', 'profiles-disable-account-deletion', 'intval' );

		/* XProfile Section **************************************************/

		if ( profiles_is_active( 'xprofile' ) ) {

			// Add the main section.
			add_settings_section( 'profiles_xprofile', _x( 'Profile Settings', 'Profiles setting tab', 'profiles' ), 'profiles_admin_setting_callback_xprofile_section', 'profiles' );

			// Avatars.
			add_settings_field( 'profiles-disable-avatar-uploads', __( 'Profile Photo Uploads', 'profiles' ), 'profiles_admin_setting_callback_avatar_uploads', 'profiles', 'profiles_xprofile' );
			register_setting( 'profiles', 'profiles-disable-avatar-uploads', 'intval' );

			// Cover images.
			if ( profiles_is_active( 'xprofile', 'cover_image' ) ) {
				add_settings_field( 'profiles-disable-cover-image-uploads', __( 'Cover Image Uploads', 'profiles' ), 'profiles_admin_setting_callback_cover_image_uploads', 'profiles', 'profiles_xprofile' );
				register_setting( 'profiles', 'profiles-disable-cover-image-uploads', 'intval' );
			}

			// Profile sync setting.
			add_settings_field( 'profiles-disable-profile-sync',   __( 'Profile Syncing',  'profiles' ), 'profiles_admin_setting_callback_profile_sync', 'profiles', 'profiles_xprofile' );
			register_setting  ( 'profiles', 'profiles-disable-profile-sync', 'intval' );
		}

	}

	/**
	 * Add a link to Profiles About page to the admin bar.
	 *
	 * @since 1.9.0
	 *
	 * @param WP_Admin_Bar $wp_admin_bar As passed to 'admin_bar_menu'.
	 */
	public function admin_bar_about_link( $wp_admin_bar ) {
		if ( is_user_logged_in() ) {
			$wp_admin_bar->add_menu( array(
				'parent' => 'wp-logo',
				'id'     => 'profiles-about',
				'title'  => esc_html__( 'About Profiles', 'profiles' ),
				'href'   => add_query_arg( array( 'page' => 'profiles-about' ), profiles_get_admin_url( 'index.php' ) ),
			) );
		}
	}

	/**
	 * Add Settings link to plugins area.
	 *
	 * @since 1.6.0
	 *
	 * @param array  $links Links array in which we would prepend our link.
	 * @param string $file  Current plugin basename.
	 * @return array Processed links.
	 */
	public function modify_plugin_action_links( $links, $file ) {

		// Return normal links if not Profiles.
		if ( plugin_basename( profiles()->basename ) != $file ) {
			return $links;
		}

		// Add a few links to the existing links array.
		return array_merge( $links, array(
			'settings' => '<a href="' . esc_url( add_query_arg( array( 'page' => 'profiles-components' ), profiles_get_admin_url( $this->settings_page ) ) ) . '">' . esc_html__( 'Settings', 'profiles' ) . '</a>',
			'about'    => '<a href="' . esc_url( add_query_arg( array( 'page' => 'profiles-about'      ), profiles_get_admin_url( 'index.php'          ) ) ) . '">' . esc_html__( 'About',    'profiles' ) . '</a>'
		) );
	}

	/**
	 * Add some general styling to the admin area.
	 *
	 * @since 1.6.0
	 */
	public function admin_head() {

		// Settings pages.
		remove_submenu_page( $this->settings_page, 'profiles-page-settings' );
		remove_submenu_page( $this->settings_page, 'profiles-settings'      );

		// Network Admin Tools.
		remove_submenu_page( 'network-tools', 'network-tools' );

		// About and Credits pages.
		remove_submenu_page( 'index.php', 'profiles-about'   );
		remove_submenu_page( 'index.php', 'profiles-credits' );
	}

	/**
	 * Add some general styling to the admin area.
	 *
	 * @since 1.6.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'profiles-admin-common-css' );
	}

	/** About *****************************************************************/

	/**
	 * Output the about screen.
	 *
	 * @since 1.7.0
	 */
	public function about_screen() {
		$embedded_activity = '';

		if ( version_compare( $GLOBALS['wp_version'], '4.5', '>=' ) ) {
			$embedded_activity = wp_oembed_get( 'https://profiles.org/members/djpaul/activity/573821/' );
		}
	?>

		<div class="wrap about-wrap">

			<?php self::welcome_text(); ?>

			<?php self::tab_navigation( __METHOD__ ); ?>

			<?php if ( self::is_new_install() ) : ?>

				<div id="welcome-panel" class="welcome-panel">
					<div class="welcome-panel-content">
						<h3 style="margin:0"><?php _e( 'Getting Started with Profiles', 'profiles' ); ?></h3>
						<div class="welcome-panel-column-container">
							<div class="welcome-panel-column">
								<h4><?php _e( 'Configure Profiles', 'profiles' ); ?></h4>
								<ul>
									<li><?php printf(
									'<a href="%s" class="welcome-icon welcome-edit-page">' . __( 'Set Up Components', 'profiles' ) . '</a>', esc_url( profiles_get_admin_url( add_query_arg( array( 'page' => 'profiles-components' ), $this->settings_page ) ) )
									); ?></li>
									<li><?php printf(
									'<a href="%s" class="welcome-icon welcome-edit-page">' . __( 'Assign Components to Pages', 'profiles' ) . '</a>', esc_url( profiles_get_admin_url( add_query_arg( array( 'page' => 'profiles-page-settings' ), $this->settings_page ) ) )
									); ?></li>
									<li><?php printf(
									'<a href="%s" class="welcome-icon welcome-edit-page">' . __( 'Customize Settings', 'profiles' ) . '</a>', esc_url( profiles_get_admin_url( add_query_arg( array( 'page' => 'profiles-settings' ), $this->settings_page ) ) )
									); ?></li>
								</ul>
								<a class="button button-primary button-hero" style="margin-bottom:20px;margin-top:0;" href="<?php echo esc_url( profiles_get_admin_url( add_query_arg( array( 'page' => 'profiles-components' ), $this->settings_page ) ) ); ?>"><?php _e( 'Get Started', 'profiles' ); ?></a>
							</div>
							<div class="welcome-panel-column">
								<h4><?php _e( 'Administration Tools', 'profiles' ); ?></h4>
								<ul>
									<?php if ( profiles_is_active( 'members' ) ) : ?>
										<li><?php printf( '<a href="%s" class="welcome-icon welcome-add-page">' . __( 'Add User Profile Fields', 'profiles' ) . '</a>', esc_url( profiles_get_admin_url( add_query_arg( array( 'page' => 'profiles-profile-setup' ), 'users.php' ) ) ) ); ?></li>
									<?php endif; ?>
									<li><?php printf( '<a href="%s" class="welcome-icon welcome-add-page">' . __( 'Manage User Signups', 'profiles' ) . '</a>', esc_url( profiles_get_admin_url( add_query_arg( array( 'page' => 'profiles-signups' ), 'users.php' ) ) ) ); ?></li>
									<?php if ( profiles_is_active( 'groups' ) ) : ?>
										<li><?php printf( '<a href="%s" class="welcome-icon welcome-add-page">' . __( 'Manage Groups', 'profiles' ) . '</a>', esc_url( profiles_get_admin_url( add_query_arg( array( 'page' => 'profiles-groups' ), 'admin.php' ) ) ) ); ?></li>
									<?php endif; ?>
									<li><?php printf( '<a href="%s" class="welcome-icon welcome-add-page">' . __( 'Repair Data', 'profiles' ) . '</a>', esc_url( profiles_get_admin_url( add_query_arg( array( 'page' => 'profiles-tools' ), 'tools.php' ) ) ) ); ?>
									</li>
								</ul>
							</div>
							<div class="welcome-panel-column welcome-panel-last">
								<h4><?php _e( 'Community and Support', 'profiles'  ); ?></h4>
								<p class="welcome-icon welcome-learn-more" style="margin-right:10px"><?php _e( 'Looking for help? The <a href="https://codex.profiles.org/">Profiles Codex</a> has you covered.', 'profiles' ) ?></p>
								<p class="welcome-icon welcome-learn-more" style="margin-right:10px"><?php _e( 'Can&#8217;t find what you need? Stop by <a href="https://profiles.org/support/">our support forums</a>, where active Profiles users and developers are waiting to share tips and more.', 'profiles' ) ?></p>
							</div>
						</div>
					</div>
				</div>

			<?php endif; ?>

			<div class="profiles-headline-feature">
				<h3 class="headline-title"><?php esc_html_e( 'Activity Embeds', 'profiles' ); ?></h3>

				<?php if ( $embedded_activity ) :
					wp_enqueue_script( 'wp-embed' );
				?>
					<div class="embed-container">
						<?php echo $embedded_activity ; ?>
					</div>

				<?php else : ?>

					<div class="featured-image">
						<a href="http://wordpress.tv/2016/06/15/profiles-2-6-introducing-profiles-activity-embeds/" title="<?php esc_attr_e( 'View the Activity Embeds demo', 'profiles' ); ?>">
						<img src="<?php echo esc_url( profiles()->plugin_url . 'profiles-core/admin/images/activity-embeds.png' ); ?>" alt="<?php esc_attr_e( 'Embed activities into your WordPress posts or pages.', 'profiles' ); ?>">
						</a>
					</div>

				<?php endif ; ?>

				<p class="introduction"><?php _e( 'Embed activities into your WordPress posts or pages.', 'profiles' ); ?>  </p>
				<p><?php _e( 'Copy the permalink URL of the activity of your choice, paste it into the content editor of your WordPress post or page, and <em>voilà</em>&#33;, you&#39;ve embedded an activity update.', 'profiles' ); ?> <a href="http://wordpress.tv/2016/06/15/profiles-2-6-introducing-profiles-activity-embeds/"><?php esc_html_e( 'View the Activity Embeds demo', 'profiles' ); ?></a></p>

				<div class="clear"></div>
			</div>

			<hr />

			<div class="profiles-features-section">
				<h3 class="headline-title"><?php esc_html_e( 'Features', 'profiles' ); ?></h3>

				<div class="profiles-feature">
					<h4 class="feature-title"><?php esc_html_e( 'Custom Front Page for Member Profile ', 'profiles' ); ?></h4>
					<img src="<?php echo esc_url( profiles()->plugin_url . 'profiles-core/admin/images/user-frontpage.png' ); ?>" alt="<?php esc_attr_e( 'A member custom front page using widgets.', 'profiles' ); ?>">
					<p><?php _e( 'Theme developers or site owners can create custom front pages for their community&#39;s members by adding a <code>front.php</code> template to their template overrides. A specific template hierarchy is also available to make them even more unique.', 'profiles' ); ?> <a href="https://profilesdevel.wordpress.com/2016/05/24/custom-front-pages-for-your-users-profiles/"><?php esc_html_e( 'Read all about this new feature.', 'profiles' ); ?></a></p>
				</div>

				<div class="profiles-feature opposite">
					<h4 class="feature-title"><?php esc_html_e( 'Group Types API', 'profiles' ); ?></h4>
					<img src="<?php echo esc_url( profiles()->plugin_url . 'profiles-core/admin/images/group-type-pop.png' ); ?>" alt="<?php esc_attr_e( 'Group types metabox in Groups admin page.', 'profiles' ); ?>">
					<p><?php esc_html_e( 'Registering group types finally enables a strict separation of different and explicit types of groups. This new feature is available to plugin developers starting with Profiles 2.6.', 'profiles' ); ?> <a href="https://codex.profiles.org/developer/group-types/"><?php esc_html_e( 'Learn how to set up Group Types.', 'profiles' ); ?></a></p>
				</div>

				<div class="profiles-feature">
					<h4 class="feature-title"><?php esc_html_e( 'New Navigation API', 'profiles' ); ?></h4>
					<img src="<?php echo esc_url( profiles()->plugin_url . 'profiles-core/admin/images/new-nav-api.png' ); ?>" alt="<?php esc_attr_e( 'Sample code for using the new navigation API', 'profiles' ); ?>">
					<p><?php esc_html_e( 'The member and group navigation system has been totally rewritten, making it easier than ever to customize Profiles nav items.', 'profiles' ); ?> <a href="https://codex.profiles.org/developer/navigation-api/"><?php esc_html_e( 'Read the informative commit message.', 'profiles' ); ?></a></p>
				</div>

				<div class="profiles-feature opposite">
					<h4 class="feature-title"><?php esc_html_e( 'Stylesheets for Twenty Eleven and Twenty Ten', 'profiles' ); ?></h4>
					<img src="<?php echo esc_url( profiles()->plugin_url . 'profiles-core/admin/images/default-themes.png' ); ?>" alt="<?php esc_attr_e( 'Styled Profiles components in Twenty Eleven and Twenty Ten', 'profiles' ); ?>">
					<p><?php esc_html_e( 'Profiles feels right at home now in the classic default themes, Twenty Ten and Twenty Eleven.', 'profiles' ); ?></p>
				</div>
			</div>

			<div class="profiles-changelog-section">
				<h3 class="changelog-title"><?php esc_html_e( 'Under The Hood', 'profiles' ); ?></h3>

				<div class="profiles-changelog col two-col">
					<div>
						<h4 class="title"><?php esc_html_e( 'Performance Enhancements', 'profiles' ); ?></h4>
						<p><?php esc_html_e( 'Class autoloading reduces the memory needed to run Profiles on your server. Improved caching strategies for group membership statuses mean fewer round trips to your overworked database server.', 'profiles' ); ?></p>
						<h4 class="title"><?php esc_html_e( 'Localization Improvements', 'profiles' ); ?></h4>
						<p><?php esc_html_e( 'Improved localization strings and comments help translators do their much-appreciated work: making Profiles available in many languages.', 'profiles' ); ?></p>
					</div>

					<div class="last-feature">
						<h4 class="title"><?php esc_html_e( 'Notifications Updates', 'profiles' ); ?></h4>
						<p><?php esc_html_e( 'Adjustments to the notifications component allow members to receive timely and relevant updates about activity in your community.', 'profiles' ); ?></p>
						<h4 class="title"><?php esc_html_e( 'Accessibility Upgrades', 'profiles' ); ?></h4>
						<p><?php esc_html_e( 'Continued improvements help make Profiles&#39; back- and front-end screens usable for everyone &#40;and on more devices&#41;.', 'profiles' ); ?></p>
						<h4 class="title"><?php esc_html_e( 'Developer Reference', 'profiles' ); ?></h4>
						<p><?php esc_html_e( 'Regular updates to inline code documentation make it easier for developers to understand how Profiles works.', 'profiles' ); ?></p>
					</div>
				</div>

			</div>

			<div class="profiles-assets">
				<p><?php _ex( 'Learn more:', 'About screen, website links', 'profiles' ); ?> <a href="https://profiles.org/blog/"><?php _ex( 'News', 'About screen, link to project blog', 'profiles' ); ?></a> &bullet; <a href="https://profiles.org/support/"><?php _ex( 'Support', 'About screen, link to support site', 'profiles' ); ?></a> &bullet; <a href="https://codex.profiles.org/"><?php _ex( 'Documentation', 'About screen, link to documentation', 'profiles' ); ?></a> &bullet; <a href="https://profilesdevel.wordpress.com/"><?php _ex( 'Development Blog', 'About screen, link to development blog', 'profiles' ); ?></a></p>

				<p><?php _ex( 'Twitter:', 'official Twitter accounts:', 'profiles' ); ?> <a href="https://twitter.com/profiles/"><?php _ex( 'Profiles', '@profiles twitter account name', 'profiles' ); ?></a> &bullet; <a href="https://twitter.com/profilestrac/"><?php _ex( 'Trac', '@profilestrac twitter account name', 'profiles' ); ?></a> &bullet; <a href="https://twitter.com/profilesdev/"><?php _ex( 'Development', '@profilesdev twitter account name', 'profiles' ); ?></a></p>
			</div>

		</div>

		<?php
	}

	/**
	 * Output the credits screen.
	 *
	 * Hardcoding this in here is pretty janky. It's fine for now, but we'll
	 * want to leverage api.wordpress.org eventually.
	 *
	 * @since 1.7.0
	 */
	public function credits_screen() {
	?>

		<div class="wrap about-wrap">

			<?php self::welcome_text(); ?>

			<?php self::tab_navigation( __METHOD__ ); ?>

			<p class="about-description"><?php _e( 'Profiles is created by a worldwide network of friendly folks like these.', 'profiles' ); ?></p>

			<h3 class="wp-people-group"><?php _e( 'Project Leaders', 'profiles' ); ?></h3>
			<ul class="wp-people-group " id="wp-people-group-project-leaders">
				<li class="wp-person" id="wp-person-johnjamesjacoby">
					<a class="web" href="https://profiles.wordpress.org/johnjamesjacoby"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/81ec16063d89b162d55efe72165c105f?s=60">
					John James Jacoby</a>
					<span class="title"><?php _e( 'Project Lead', 'profiles' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-boonebgorges">
					<a class="web" href="https://profiles.wordpress.org/boonebgorges"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/9cf7c4541a582729a5fc7ae484786c0c?s=60">
					Boone B. Gorges</a>
					<span class="title"><?php _e( 'Lead Developer', 'profiles' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-djpaul">
					<a class="web" href="https://profiles.wordpress.org/djpaul"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/3bc9ab796299d67ce83dceb9554f75df?s=60">
					Paul Gibbs</a>
					<span class="title"><?php _e( 'Lead Developer', 'profiles' ); ?></span>
				</li>
			</ul>

			<h3 class="wp-people-group"><?php _e( 'Core Team', 'profiles' ); ?></h3>
			<ul class="wp-people-group " id="wp-people-group-core-team">
				<li class="wp-person" id="wp-person-r-a-y">
					<a class="web" href="https://profiles.wordpress.org/r-a-y"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/3bfa556a62b5bfac1012b6ba5f42ebfa?s=60">
					Ray</a>
					<span class="title"><?php _e( 'Core Developer', 'profiles' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-imath">
					<a class="web" href="https://profiles.wordpress.org/imath"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/8b208ca408dad63888253ee1800d6a03?s=60">
					Mathieu Viet</a>
					<span class="title"><?php _e( 'Core Developer', 'profiles' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-mercime">
					<a class="web" href="https://profiles.wordpress.org/mercime"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/fae451be6708241627983570a1a1817a?s=60">
					Mercime</a>
					<span class="title"><?php _e( 'Navigator', 'profiles' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-dcavins">
					<a class="web" href="https://profiles.wordpress.org/dcavins"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/a5fa7e83d59cb45ebb616235a176595a?s=60">
					David Cavins</a>
					<span class="title"><?php _e( 'Core Developer', 'profiles' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-tw2113">
					<a class="web" href="https://profiles.wordpress.org/tw2113"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/a5d7c934621fa1c025b83ee79bc62366?s=60">
					Michael Beckwith</a>
					<span class="title"><?php _e( 'Core Developer', 'profiles' ); ?></span>
				</li>
				<li class="wp-person" id="wp-person-hnla">
					<a class="web" href="https://profiles.wordpress.org/hnla"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/3860c955aa3f79f13b92826ae47d07fe?s=60">
					Hugo</a>
					<span class="title"><?php _e( 'Core Developer', 'profiles' ); ?></span>
				</li>
			</ul>

			<h3 class="wp-people-group"><?php _e( '&#x1f31f;Recent Rockstars&#x1f31f;', 'profiles' ); ?></h3>
			<ul class="wp-people-group " id="wp-people-group-rockstars">
				<li class="wp-person" id="wp-person-henry-wright">
					<a class="web" href="https://profiles.wordpress.org/henry.wright"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/0da2f1a9340d6af196b870f6c107a248?s=60">
					Henry Wright</a>
				</li>
				<li class="wp-person" id="wp-person-danprofiles">
					<a class="web" href="https://profiles.wordpress.org/danprofiles"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/0deae2e7003027fbf153500cd3fa5501?s=60">
					danprofiles</a>
				</li>
				<li class="wp-person" id="wp-person-shaneprofiles">
					<a class="web" href="https://profiles.wordpress.org/shaneprofiles"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/ffd294ab5833ba14aaf175f9acc71cc4?s=60">
					shaneprofiles</a>
				</li>
				<li class="wp-person" id="wp-person-netweb">
					<a class="web" href="https://profiles.wordpress.org/netweb"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/97e1620b501da675315ba7cfb740e80f?s=60">
					Stephen Edgar</a>
				</li>
				<li class="wp-person" id="wp-person-dimensionmedia">
					<a class="web" href="https://profiles.wordpress.org/dimensionmedia"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/7735aada1ec39d0c1118bd92ed4551f1?s=60">
					David Bisset</a>
				</li>
				<li class="wp-person" id="wp-person-offereins">
					<a class="web" href="https://profiles.wordpress.org/Offereins"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/2404ed0a35bb41aedefd42b0a7be61c1?s=60">
					Laurens Offereins</a>
				</li>
				<li class="wp-person" id="wp-person-garrett-eclipse">
					<a class="web" href="https://profiles.wordpress.org/garrett-eclipse"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/7f68f24441c61514d5d0e1451bb5bc9d?s=60">
					Garrett Hyder</a>
				</li>
				<li class="wp-person" id="wp-person-thebrandonallen">
					<a class="web" href="https://profiles.wordpress.org/thebrandonallen"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/6d3f77bf3c9ca94c406dea401b566950?s=60">
					Brandon Allen</a>
				</li>
				<li class="wp-person" id="wp-person-ramiy">
					<a class="web" href="https://profiles.wordpress.org/ramiy"><img alt="" class="gravatar" src="//www.gravatar.com/avatar/ce2a269e424156d79cb0c4e1d4d82db1?s=60">
					Rami Yushuvaev</a>
				</li>

			</ul>

			<h3 class="wp-people-group"><?php printf( esc_html__( 'Contributors to Profiles %s', 'profiles' ), self::display_version() ); ?></h3>
			<p class="wp-credits-list">
				<a href="https://profiles.wordpress.org/abweb/">abweb</a>,
				<a href="https://profiles.wordpress.org/boonebgorges/">Boone B Gorges (boonebgorges)</a>,
				<a href="https://profiles.wordpress.org/thebrandonallen/">Brandon Allen (thebrandonallen)</a>,
				<a href="https://profiles.wordpress.org/chherbst/">chherbst</a>,
				<a href="https://profiles.wordpress.org/danbrellis/">danbrellis</a>,
				<a href="https://profiles.wordpress.org/dcavins/">David Cavins (dcavins)</a>,
				<a href="https://profiles.wordpress.org/wpdennis/">Dennis (wpdennis)</a>,
				<a href="https://profiles.wordpress.org/emrikol/">emrikol</a>,
				<a href="https://profiles.wordpress.org/wdfee/">Fee (wdfee)</a>,
				<a href="https://profiles.wordpress.org/garrett-eclipse/">Garrett Hyder (garrett-eclipse)</a>,
				<a href="https://profiles.wordpress.org/pento/">Gary Pendergast (pento)</a>,
				<a href="https://profiles.wordpress.org/Mamaduka/">George Mamadashvili (Mamaduka)</a>,
				<a href="https://profiles.wordpress.org/henrywright/">Henry Wright (henry.wright)</a>,
				<a href="https://profiles.wordpress.org/hnla/">Hugo (hnla)</a>,
				<a href="https://profiles.wordpress.org/johnjamesjacoby/">John James Jacoby (johnjamesjacoby)</a>,
				<a href="https://profiles.wordpress.org/kmbdeamorg/">Klaus (kmbdeamorg)</a>,
				<a href="https://profiles.wordpress.org/sooskriszta/">OC2PS (sooskriszta)</a>,
				<a href="https://profiles.wordpress.org/lakrisgubben/">lakrisgubben</a>,
				<a href="https://profiles.wordpress.org/Offereins">Laurens Offereins (Offereins)</a>,
				<a href="https://profiles.wordpress.org/mahadri/">mahadri</a>,
				<a href="https://profiles.wordpress.org/imath/">Mathieu Viet (imath)</a>,
				<a href="https://profiles.wordpress.org/mercime/">mercime</a>,
				<a href="https://profiles.wordpress.org/tw2113/">Michael Beckwith (tw2113)</a>,
				<a href="https://profiles.wordpress.org/mmcachran/">mmcachran</a>,
				<a href="https://profiles.wordpress.org/modemlooper/">modemlooper</a>,
				<a href="https://profiles.wordpress.org/nickmomrik/">Nick Momrik (nickmomrik)</a>,
				<a href="https://profiles.wordpress.org/OakCreative/">OakCreative</a>,
				<a href="https://profiles.wordpress.org/oksankaa/">oksankaa</a>,
				<a href="https://profiles.wordpress.org/DJPaul/">Paul Gibbs (DJPaul)</a>,
				<a href="https://profiles.wordpress.org/ramiy/">Rami Yushuvaev (ramiy)</a>,
				<a href="https://profiles.wordpress.org/r-a-y/">r-a-y</a>,
				<a href="https://profiles.wordpress.org/rekmla/">rekmla</a>,
				<a href="https://profiles.wordpress.org/r0z/">r0z</a>,
				<a href="https://profiles.wordpress.org/SergeyBiryukov/">Sergey Biryukov (SergeyBiryukov)</a>,
				<a href="https://profiles.wordpress.org/singhleo/">singhleo</a>,
				<a href="https://profiles.wordpress.org/slaffik/">Slava UA (slaffik)</a>,
				<a href="https://profiles.wordpress.org/netweb/">Stephen Edgar (netweb)</a>,
				<a href="https://profiles.wordpress.org/tharsheblows/">tharsheblows</a>,
				<a href="https://profiles.wordpress.org/VibeThemes/">VibeThemes</a>,
				<a href="https://profiles.wordpress.org/vortfu/">vortfu</a>,
				<a href="https://profiles.wordpress.org/WeddyWood/">WeddyWood</a>,
				<a href="https://profiles.wordpress.org/w3dzign/">w3dzign</a>.
			</p>

			<h3 class="wp-people-group"><?php _e( '&#x1f496;With our thanks to these Open Source projects&#x1f496;', 'profiles' ); ?></h3>
			<p class="wp-credits-list">
				<a href="https://github.com/ichord/At.js">At.js</a>,
				<a href="https://bprofilesress.org">bbPress</a>,
				<a href="https://github.com/ichord/Caret.js">Caret.js</a>,
				<a href="http://tedgoas.github.io/Cerberus/">Cerberus</a>,
				<a href="http://ionicons.com/">Ionicons</a>,
				<a href="https://github.com/carhartl/jquery-cookie">jquery.cookie</a>,
				<a href="https://www.mediawiki.org/wiki/MediaWiki">MediaWiki</a>,
				<a href="https://wordpress.org">WordPress</a>.
			</p>

		</div>

		<?php
	}

	/**
	 * Output welcome text and badge for What's New and Credits pages.
	 *
	 * @since 2.2.0
	 */
	public static function welcome_text() {

		// Switch welcome text based on whether this is a new installation or not.
		$welcome_text = ( self::is_new_install() )
			? __( 'Thank you for installing Profiles! Profiles helps site builders and WordPress developers add community features to their websites, with user profile fields, activity streams, messaging, and notifications.', 'profiles' )
			: __( 'Thank you for updating! Profiles %s has many new features that you will enjoy.', 'profiles' );

		?>

		<h1><?php printf( esc_html__( 'Welcome to Profiles %s', 'profiles' ), self::display_version() ); ?></h1>

		<div class="about-text">
			<?php
			if ( self::is_new_install() ) {
				echo $welcome_text;
			} else {
				printf( $welcome_text, self::display_version() );
			}
			?>
		</div>

		<div class="profiles-badge"></div>

		<?php
	}

	/**
	 * Output tab navigation for `What's New` and `Credits` pages.
	 *
	 * @since 2.2.0
	 *
	 * @param string $tab Tab to highlight as active.
	 */
	public static function tab_navigation( $tab = 'whats_new' ) {
	?>

		<h2 class="nav-tab-wrapper">
			<a class="nav-tab <?php if ( 'Profiles_Admin::about_screen' === $tab ) : ?>nav-tab-active<?php endif; ?>" href="<?php echo esc_url( profiles_get_admin_url( add_query_arg( array( 'page' => 'profiles-about' ), 'index.php' ) ) ); ?>">
				<?php esc_html_e( 'What&#8217;s New', 'profiles' ); ?>
			</a><a class="nav-tab <?php if ( 'Profiles_Admin::credits_screen' === $tab ) : ?>nav-tab-active<?php endif; ?>" href="<?php echo esc_url( profiles_get_admin_url( add_query_arg( array( 'page' => 'profiles-credits' ), 'index.php' ) ) ); ?>">
				<?php esc_html_e( 'Credits', 'profiles' ); ?>
			</a>
		</h2>

	<?php
	}

	/** Emails ****************************************************************/

	/**
	 * Registers 'Situations' column on Emails dashboard page.
	 *
	 * @since 2.6.0
	 *
	 * @param array $columns Current column data.
	 * @return array
	 */
	public function emails_register_situation_column( $columns = array() ) {
		$situation = array(
			'situation' => _x( 'Situations', 'Email post type', 'profiles' )
		);

		// Inject our 'Situations' column just before the last 'Date' column.
		return array_slice( $columns, 0, -1, true ) + $situation + array_slice( $columns, -1, null, true );
	}

	/**
	 * Output column data for our custom 'Situations' column.
	 *
	 * @since 2.6.0
	 *
	 * @param string $column  Current column name.
	 * @param int    $post_id Current post ID.
	 */
	public function emails_display_situation_column_data( $column = '', $post_id = 0 ) {
		if ( 'situation' !== $column ) {
			return;
		}

		// Grab email situations for the current post.
		$situations = wp_list_pluck( get_the_terms( $post_id, profiles_get_email_tax_type() ), 'description' );

		// Output each situation as a list item.
		echo '<ul><li>';
		echo implode( '</li><li>', $situations );
		echo '</li></ul>';
	}

	/** Helpers ***************************************************************/

	/**
	 * Return true/false based on whether a query argument is set.
	 *
	 * @see profiles_do_activation_redirect()
	 *
	 * @since 2.2.0
	 *
	 * @return bool
	 */
	public static function is_new_install() {
		return (bool) isset( $_GET['is_new_install'] );
	}

	/**
	 * Return a user-friendly version-number string, for use in translations.
	 *
	 * @since 2.2.0
	 *
	 * @return string
	 */
	public static function display_version() {

		// Use static variable to prevent recalculations.
		static $display = '';

		// Only calculate on first run.
		if ( '' === $display ) {

			// Get current version.
			$version = profiles_get_version();

			// Check for prerelease hyphen.
			$pre     = strpos( $version, '-' );

			// Strip prerelease suffix.
			$display = ( false !== $pre )
				? substr( $version, 0, $pre )
				: $version;
		}

		// Done!
		return $display;
	}

	/**
	 * Add Emails menu item to custom menus array.
	 *
	 * Several Profiles components have top-level menu items in the Dashboard,
	 * which all appear together in the middle of the Dashboard menu. This function
	 * adds the Emails screen to the array of these menu items.
	 *
	 * @since 2.4.0
	 *
	 * @param array $custom_menus The list of top-level BP menu items.
	 * @return array $custom_menus List of top-level BP menu items, with Emails added.
	 */
	public function emails_admin_menu_order( $custom_menus = array() ) {
		array_push( $custom_menus, 'edit.php?post_type=' . profiles_get_email_post_type() );

		if ( is_network_admin() && profiles_is_network_activated() ) {
			array_push(
				$custom_menus,
				get_admin_url( profiles_get_root_blog_id(), 'edit.php?post_type=' . profiles_get_email_post_type() )
			);
		}

		return $custom_menus;
	}

	/**
	 * Register styles commonly used by Profiles wp-admin screens.
	 *
	 * @since 2.5.0
	 */
	public function admin_register_styles() {
		$min = profiles_core_get_minified_asset_suffix();
		$url = $this->css_url;

		/**
		 * Filters the Profiles Core Admin CSS file path.
		 *
		 * @since 1.6.0
		 *
		 * @param string $file File path for the admin CSS.
		 */
		$common_css = apply_filters( 'profiles_core_admin_common_css', "{$url}common{$min}.css" );

		/**
		 * Filters the Profiles admin stylesheet files to register.
		 *
		 * @since 2.5.0
		 *
		 * @param array $value Array of admin stylesheet file information to register.
		 */
		$styles = apply_filters( 'profiles_core_admin_register_styles', array(
			// Legacy.
			'profiles-admin-common-css' => array(
				'file'         => $common_css,
				'dependencies' => array(),
			),

			// 2.5
			'profiles-customizer-controls' => array(
				'file'         => "{$url}customizer-controls{$min}.css",
				'dependencies' => array(),
			),
		) );


		$version = profiles_get_version();

		foreach ( $styles as $id => $style ) {
			wp_register_style( $id, $style['file'], $style['dependencies'], $version );
			wp_style_add_data( $id, 'rtl', true );

			if ( $min ) {
				wp_style_add_data( $id, 'suffix', $min );
			}
		}
	}

	/**
	 * Register JS commonly used by Profiles wp-admin screens.
	 *
	 * @since 2.5.0
	 */
	public function admin_register_scripts() {
		$min = profiles_core_get_minified_asset_suffix();
		$url = $this->js_url;

		/**
		 * Filters the Profiles admin JS files to register.
		 *
		 * @since 2.5.0
		 *
		 * @param array $value Array of admin JS file information to register.
		 */
		$scripts = apply_filters( 'profiles_core_admin_register_scripts', array(
			// 2.5
			'profiles-customizer-controls' => array(
				'file'         => "{$url}customizer-controls{$min}.js",
				'dependencies' => array( 'jquery' ),
				'footer'       => true,
			),
		) );

		$version = profiles_get_version();

		foreach ( $scripts as $id => $script ) {
			wp_register_script( $id, $script['file'], $script['dependencies'], $version, $script['footer'] );
		}
	}
}
endif; // End class_exists check.

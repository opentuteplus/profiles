<?php
/**
 * Profiles Admin Slug Functions.
 *
 * @package Profiles
 * @suprofilesackage CoreAdministration
 * @since 2.3.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Renders the page mapping admin panel.
 *
 * @since 1.6.0
 * @todo Use settings API
 */
function profiles_core_admin_slugs_settings() {
?>

	<div class="wrap">

		<h1><?php _e( 'Profiles Settings', 'profiles' ); ?> </h1>

		<h2 class="nav-tab-wrapper"><?php profiles_core_admin_tabs( __( 'Pages', 'profiles' ) ); ?></h2>
		<form action="" method="post" id="profiles-admin-page-form">

			<?php profiles_core_admin_slugs_options(); ?>

			<p class="submit clear">
				<input class="button-primary" type="submit" name="profiles-admin-pages-submit" id="profiles-admin-pages-submit" value="<?php esc_attr_e( 'Save Settings', 'profiles' ) ?>"/>
			</p>

			<?php wp_nonce_field( 'profiles-admin-pages-setup' ); ?>

		</form>
	</div>

<?php
}

/**
 * Generate a list of directory pages, for use when building Components panel markup.
 *
 * @since 2.4.1
 *
 * @return array
 */
function profiles_core_admin_get_directory_pages() {
	$profiles = profiles();
	$directory_pages = array();

	// Loop through loaded components and collect directories.
	if ( is_array( $profiles->loaded_components ) ) {
		foreach( $profiles->loaded_components as $component_slug => $component_id ) {

			// Only components that need directories should be listed here.
			if ( isset( $profiles->{$component_id} ) && !empty( $profiles->{$component_id}->has_directory ) ) {

				// The component->name property was introduced in BP 1.5, so we must provide a fallback.
				$directory_pages[$component_id] = !empty( $profiles->{$component_id}->name ) ? $profiles->{$component_id}->name : ucwords( $component_id );
			}
		}
	}

	/** Directory Display *****************************************************/

	/**
	 * Filters the loaded components needing directory page association to a WordPress page.
	 *
	 * @since 1.5.0
	 *
	 * @param array $directory_pages Array of available components to set associations for.
	 */
	return apply_filters( 'profiles_directory_pages', $directory_pages );
}

/**
 * Generate a list of static pages, for use when building Components panel markup.
 *
 * By default, this list contains 'register' and 'activate'.
 *
 * @since 2.4.1
 *
 * @return array
 */
function profiles_core_admin_get_static_pages() {
	$static_pages = array(
		'register' => __( 'Register', 'profiles' ),
		'activate' => __( 'Activate', 'profiles' ),
	);

	/**
	 * Filters the default static pages for Profiles setup.
	 *
	 * @since 1.6.0
	 *
	 * @param array $static_pages Array of static default static pages.
	 */
	return apply_filters( 'profiles_static_pages', $static_pages );
}

/**
 * Creates reusable markup for page setup on the Components and Pages dashboard panel.
 *
 * @package Profiles
 * @since 1.6.0
 * @todo Use settings API
 */
function profiles_core_admin_slugs_options() {
	$profiles = profiles();

	// Get the existing WP pages
	$existing_pages = profiles_core_get_directory_page_ids();

	// Set up an array of components (along with component names) that have directory pages.
	$directory_pages = profiles_core_admin_get_directory_pages();

	if ( !empty( $directory_pages ) ) : ?>

		<h3><?php _e( 'Directories', 'profiles' ); ?></h3>

		<p><?php _e( 'Associate a WordPress Page with each Profiles component directory.', 'profiles' ); ?></p>

		<table class="form-table">
			<tbody>

				<?php foreach ( $directory_pages as $name => $label ) : ?>

					<tr valign="top">
						<th scope="row">
							<label for="profiles_pages[<?php echo esc_attr( $name ) ?>]"><?php echo esc_html( $label ) ?></label>
						</th>

						<td>

							<?php if ( ! profiles_is_root_blog() ) switch_to_blog( profiles_get_root_blog_id() ); ?>

							<?php echo wp_dropdown_pages( array(
								'name'             => 'profiles_pages[' . esc_attr( $name ) . ']',
								'echo'             => false,
								'show_option_none' => __( '- None -', 'profiles' ),
								'selected'         => !empty( $existing_pages[$name] ) ? $existing_pages[$name] : false
							) ); ?>

							<?php if ( !empty( $existing_pages[$name] ) ) : ?>

								<a href="<?php echo get_permalink( $existing_pages[$name] ); ?>" class="button-secondary" target="_profiles"><?php _e( 'View', 'profiles' ); ?></a>

							<?php endif; ?>

							<?php if ( ! profiles_is_root_blog() ) restore_current_blog(); ?>

						</td>
					</tr>


				<?php endforeach ?>

				<?php

				/**
				 * Fires after the display of default directories.
				 *
				 * Allows plugins to add their own directory associations.
				 *
				 * @since 1.5.0
				 */
				do_action( 'profiles_active_external_directories' ); ?>

			</tbody>
		</table>

	<?php

	endif;
}

/**
 * Handle saving of the Profiles slugs.
 *
 * @since 1.6.0
 * @todo Use settings API
 */
function profiles_core_admin_slugs_setup_handler() {

	if ( isset( $_POST['profiles-admin-pages-submit'] ) ) {
		if ( !check_admin_referer( 'profiles-admin-pages-setup' ) )
			return false;

		// Then, update the directory pages.
		if ( isset( $_POST['profiles_pages'] ) ) {
			$valid_pages = array_merge( profiles_core_admin_get_directory_pages(), profiles_core_admin_get_static_pages() );

			$new_directory_pages = array();
			foreach ( (array) $_POST['profiles_pages'] as $key => $value ) {
				if ( isset( $valid_pages[ $key ] ) ) {
					$new_directory_pages[ $key ] = (int) $value;
				}
			}
			profiles_core_update_directory_page_ids( $new_directory_pages );
		}

		$base_url = profiles_get_admin_url( add_query_arg( array( 'page' => 'profiles-page-settings', 'updated' => 'true' ), 'admin.php' ) );

		wp_redirect( $base_url );
	}
}
add_action( 'profiles_admin_init', 'profiles_core_admin_slugs_setup_handler' );

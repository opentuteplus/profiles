<?php
/**
 * Profiles Members Filters.
 *
 * Filters specific to the Members component.
 *
 * @package Profiles
 * @suprofilesackage MembersFilters
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Escape commonly used fullname output functions.
 */
add_filter( 'profiles_displayed_user_fullname',    'esc_html' );
add_filter( 'profiles_get_loggedin_user_fullname', 'esc_html' );


/**
 * Make sure the username is not the blog slug in case of root profile & subdirectory blog.
 *
 * If Profiles_ENABLE_ROOT_PROFILES is defined & multisite config is set to subdirectories,
 * then there is a chance site.url/username == site.url/blogslug. If so, user's profile
 * is not reachable, instead the blog is displayed. This filter makes sure the signup username
 * is not the same than the blog slug for this particular config.
 *
 * @since 2.1.0
 *
 * @param array $illegal_names Array of illiegal names.
 * @return array $illegal_names
 */
function profiles_members_signup_with_subdirectory_blog( $illegal_names = array() ) {
	if ( ! profiles_core_enable_root_profiles() ) {
		return $illegal_names;
	}

	if ( is_network_admin() && isset( $_POST['blog'] ) ) {
		$blog = $_POST['blog'];
		$domain = '';

		if ( preg_match( '|^([a-zA-Z0-9-])$|', $blog['domain'] ) ) {
			$domain = strtolower( $blog['domain'] );
		}

		if ( username_exists( $domain ) ) {
			$illegal_names[] = $domain;
		}

	} else {
		$illegal_names[] = profiles()->signup->username;
	}

	return $illegal_names;
}
add_filter( 'subdirectory_reserved_names', 'profiles_members_signup_with_subdirectory_blog', 10, 1 );

/**
 * Filter the user profile URL to point to Profiles profile edit.
 *
 * @since 1.6.0
 *
 * @param string $url     WP profile edit URL.
 * @param int    $user_id ID of the user.
 * @param string $scheme  Scheme to use.
 * @return string
 */
function profiles_members_edit_profile_url( $url, $user_id, $scheme = 'admin' ) {

	// If xprofile is active, use profile domain link.
	if ( ! is_admin() && profiles_is_active( 'xprofile' ) ) {
		$profile_link = trailingslashit( profiles_core_get_user_domain( $user_id ) . profiles_get_profile_slug() . '/edit' );

	} else {
		// Default to $url.
		$profile_link = $url;
	}

	/**
	 * Filters the user profile URL to point to Profiles profile edit.
	 *
	 * @since 1.5.2
	 *
	 * @param string $url WP profile edit URL.
	 * @param int    $user_id ID of the user.
	 * @param string $scheme Scheme to use.
	 */
	return apply_filters( 'profiles_members_edit_profile_url', $profile_link, $url, $user_id, $scheme );
}
add_filter( 'edit_profile_url', 'profiles_members_edit_profile_url', 10, 3 );

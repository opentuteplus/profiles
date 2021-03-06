<?php
/**
 * Core component template tag functions.
 *
 * @package Profiles
 * @suprofilesackage TemplateFunctions
 * @since 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Output the "options nav", the secondary-level single item navigation menu.
 *
 * Uses the component's nav global to render out the sub navigation for the
 * current component. Each component adds to its sub navigation array within
 * its own setup_nav() function.
 *
 * This sub navigation array is the secondary level navigation, so for profile
 * it contains:
 *      [Public, Edit Profile, Change Avatar]
 *
 * The function will also analyze the current action for the current component
 * to determine whether or not to highlight a particular sub nav item.
 *
 * @since 1.0.0
 *
 *       viewed user.
 *
 * @param string $parent_slug Options nav slug.
 * @return string
 */
function profiles_get_options_nav( $parent_slug = '' ) {
	$profiles = profiles();

	// If we are looking at a member profile, then the we can use the current
	// component as an index. Otherwise we need to use the component's root_slug.
	$component_index = !empty( $profiles->displayed_user ) ? profiles_current_component() : profiles_get_root_slug( profiles_current_component() );
	$selected_item   = profiles_current_action();

	// Default to the Members nav.
	if ( ! profiles_is_single_item() ) {
		// Set the parent slug, if not provided.
		if ( empty( $parent_slug ) ) {
			$parent_slug = $component_index;
		}

		$secondary_nav_items = $profiles->members->nav->get_secondary( array( 'parent_slug' => $parent_slug ) );

		if ( ! $secondary_nav_items ) {
			return false;
		}

	// For a single item, try to use the component's nav.
	} else {
		$current_item = profiles_current_item();
		$single_item_component = profiles_current_component();

		// Adjust the selected nav item for the current single item if needed.
		if ( ! empty( $parent_slug ) ) {
			$current_item  = $parent_slug;
			$selected_item = profiles_action_variable( 0 );
		}

		// If the nav is not defined by the parent component, look in the Members nav.
		if ( ! isset( $profiles->{$single_item_component}->nav ) ) {
			$secondary_nav_items = $profiles->members->nav->get_secondary( array( 'parent_slug' => $current_item ) );
		} else {
			$secondary_nav_items = $profiles->{$single_item_component}->nav->get_secondary( array( 'parent_slug' => $current_item ) );
		}

		if ( ! $secondary_nav_items ) {
			return false;
		}
	}

	// Loop through each navigation item.
	foreach ( $secondary_nav_items as $subnav_item ) {
		if ( empty( $subnav_item->user_has_access ) ) {
			continue;
		}

		// If the current action or an action variable matches the nav item id, then add a highlight CSS class.
		if ( $subnav_item->slug === $selected_item ) {
			$selected = ' class="current selected"';
		} else {
			$selected = '';
		}

		// List type depends on our current component.
		$list_type = profiles_is_group() ? 'groups' : 'personal';

		/**
		 * Filters the "options nav", the secondary-level single item navigation menu.
		 *
		 * This is a dynamic filter that is dependent on the provided css_id value.
		 *
		 * @since 1.1.0
		 *
		 * @param string $value         HTML list item for the submenu item.
		 * @param array  $subnav_item   Submenu array item being displayed.
		 * @param string $selected_item Current action.
		 */
		echo apply_filters( 'profiles_get_options_nav_' . $subnav_item->css_id, '<li id="' . esc_attr( $subnav_item->css_id . '-' . $list_type . '-li' ) . '" ' . $selected . '><a id="' . esc_attr( $subnav_item->css_id ) . '" href="' . esc_url( $subnav_item->link ) . '">' . $subnav_item->name . '</a></li>', $subnav_item, $selected_item );
	}
}

/**
 * Get the 'profiles_options_title' property from the BP global.
 *
 * Not currently used in Profiles.
 *
 * @todo Deprecate.
 */
function profiles_get_options_title() {
	$profiles = profiles();

	if ( empty( $profiles->profiles_options_title ) ) {
		$profiles->profiles_options_title = __( 'Options', 'profiles' );
	}

	echo apply_filters( 'profiles_get_options_title', esc_attr( $profiles->profiles_options_title ) );
}

/**
 * Get the directory title for a component.
 *
 * Used for the <title> element and the page header on the component directory
 * page.
 *
 * @since 2.0.0
 *
 * @param string $component Component to get directory title for.
 * @return string
 */
function profiles_get_directory_title( $component = '' ) {
	$title = '';

	// Use the string provided by the component.
	if ( ! empty( profiles()->{$component}->directory_title ) ) {
		$title = profiles()->{$component}->directory_title;

	// If none is found, concatenate.
	} elseif ( isset( profiles()->{$component}->name ) ) {
		$title = sprintf( __( '%s Directory', 'profiles' ), profiles()->{$component}->name );
	}

	/**
	 * Filters the directory title for a component.
	 *
	 * @since 2.0.0
	 *
	 * @param string $title     Text to be used in <title> tag.
	 * @param string $component Current componet being displayed.
	 */
	return apply_filters( 'profiles_get_directory_title', $title, $component );
}

/** Avatars *******************************************************************/

/**
 * Check to see if there is an options avatar.
 *
 * An options avatar is an avatar for something like a group, or a friend.
 * Basically an avatar that appears in the sub nav options bar.
 *
 * Not currently used in Profiles.
 *
 * @return bool $value Returns true if an options avatar has been set, otherwise false.
 */
function profiles_has_options_avatar() {
	return (bool) profiles()->profiles_options_avatar;
}

/**
 * Output the options avatar.
 *
 * Not currently used in Profiles.
 *
 * @todo Deprecate.
 */
function profiles_get_options_avatar() {
	echo apply_filters( 'profiles_get_options_avatar', profiles()->profiles_options_avatar );
}

/**
 * Output a comment author's avatar.
 *
 * Not currently used in Profiles.
 */
function profiles_comment_author_avatar() {
	global $comment;

	if ( function_exists( 'profiles_core_fetch_avatar' ) ) {
		echo apply_filters( 'profiles_comment_author_avatar', profiles_core_fetch_avatar( array(
			'item_id' => $comment->user_id,
			'type'    => 'thumb',
			'alt'     => sprintf( __( 'Profile photo of %s', 'profiles' ), profiles_core_get_user_displayname( $comment->user_id ) )
		) ) );
	} elseif ( function_exists( 'get_avatar' ) ) {
		get_avatar();
	}
}

/**
 * Output a post author's avatar.
 *
 * Not currently used in Profiles.
 */
function profiles_post_author_avatar() {
	global $post;

	if ( function_exists( 'profiles_core_fetch_avatar' ) ) {
		echo apply_filters( 'profiles_post_author_avatar', profiles_core_fetch_avatar( array(
			'item_id' => $post->post_author,
			'type'    => 'thumb',
			'alt'     => sprintf( __( 'Profile photo of %s', 'profiles' ), profiles_core_get_user_displayname( $post->post_author ) )
		) ) );
	} elseif ( function_exists( 'get_avatar' ) ) {
		get_avatar();
	}
}

/**
 * Output the current avatar upload step.
 *
 * @since 1.1.0
 */
function profiles_avatar_admin_step() {
	echo profiles_get_avatar_admin_step();
}
	/**
	 * Return the current avatar upload step.
	 *
	 * @since 1.1.0
	 *
	 * @return string The current avatar upload step. Returns 'upload-image'
	 *         if none is found.
	 */
	function profiles_get_avatar_admin_step() {
		$profiles   = profiles();
		$step = isset( $profiles->avatar_admin->step )
			? $step = $profiles->avatar_admin->step
			: 'upload-image';

		/**
		 * Filters the current avatar upload step.
		 *
		 * @since 1.1.0
		 *
		 * @param string $step The current avatar upload step.
		 */
		return apply_filters( 'profiles_get_avatar_admin_step', $step );
	}

/**
 * Output the URL of the avatar to crop.
 *
 * @since 1.1.0
 */
function profiles_avatar_to_crop() {
	echo profiles_get_avatar_to_crop();
}
	/**
	 * Return the URL of the avatar to crop.
	 *
	 * @since 1.1.0
	 *
	 * @return string URL of the avatar awaiting cropping.
	 */
	function profiles_get_avatar_to_crop() {
		$profiles  = profiles();
		$url = isset( $profiles->avatar_admin->image->url )
			? $profiles->avatar_admin->image->url
			: '';

		/**
		 * Filters the URL of the avatar to crop.
		 *
		 * @since 1.1.0
		 *
		 * @param string $url URL for the avatar.
		 */
		return apply_filters( 'profiles_get_avatar_to_crop', $url );
	}

/**
 * Output the relative file path to the avatar to crop.
 *
 * @since 1.1.0
 */
function profiles_avatar_to_crop_src() {
	echo profiles_get_avatar_to_crop_src();
}
	/**
	 * Return the relative file path to the avatar to crop.
	 *
	 * @since 1.1.0
	 *
	 * @return string Relative file path to the avatar.
	 */
	function profiles_get_avatar_to_crop_src() {
		$profiles  = profiles();
		$src = isset( $profiles->avatar_admin->image->dir )
			? str_replace( WP_CONTENT_DIR, '', $profiles->avatar_admin->image->dir )
			: '';

		/**
		 * Filters the relative file path to the avatar to crop.
		 *
		 * @since 1.1.0
		 *
		 * @param string $src Relative file path for the avatar.
		 */
		return apply_filters( 'profiles_get_avatar_to_crop_src', $src );
	}

/**
 * Output the avatar cropper <img> markup.
 *
 * No longer used in Profiles.
 *
 * @todo Deprecate.
 */
function profiles_avatar_cropper() {
?>
	<img id="avatar-to-crop" class="avatar" src="<?php echo esc_url( profiles()->avatar_admin->image ); ?>" />
<?php
}

/**
 * Output the name of the BP site. Used in RSS headers.
 *
 * @since 1.0.0
 */
function profiles_site_name() {
	echo profiles_get_site_name();
}
	/**
	 * Returns the name of the BP site. Used in RSS headers.
	 *
	 * @since 1.6.0
	 *
	 * @return string
	 */
	function profiles_get_site_name() {

		/**
		 * Filters the name of the BP site. Used in RSS headers.
		 *
		 * @since 1.0.0
		 *
		 * @param string $value Current BP site name.
		 */
		return apply_filters( 'profiles_site_name', get_bloginfo( 'name', 'display' ) );
	}

/**
 * Format a date based on a UNIX timestamp.
 *
 * This function can be used to turn a UNIX timestamp into a properly formatted
 * (and possibly localized) string, userful for ouputting the date & time an
 * action took place.
 *
 * Not to be confused with `profiles_core_time_since()`, this function is best used
 * for displaying a more exact date and time vs. a human-readable time.
 *
 * Note: This function may be improved or removed at a later date, as it is
 * hardly used and adds an additional layer of complexity to calculating dates
 * and times together with timezone offsets and i18n.
 *
 * @since 1.1.0
 *
 * @param int|string $time         The UNIX timestamp to be formatted.
 * @param bool       $exclude_time Optional. True to return only the month + day, false
 *                                 to return month, day, and time. Default: false.
 * @param bool       $gmt          Optional. True to display in local time, false to
 *                                  leave in GMT. Default: true.
 * @return mixed A string representation of $time, in the format
 *               "March 18, 2014 at 2:00 pm" (or whatever your
 *               'date_format' and 'time_format' settings are
 *               on your root blog). False on failure.
 */
function profiles_format_time( $time = '', $exclude_time = false, $gmt = true ) {

	// Bail if time is empty or not numeric
	// @todo We should output something smarter here.
	if ( empty( $time ) || ! is_numeric( $time ) ) {
		return false;
	}

	// Get GMT offset from root blog.
	if ( true === $gmt ) {

		// Use Timezone string if set.
		$timezone_string = profiles_get_option( 'timezone_string' );
		if ( ! empty( $timezone_string ) ) {
			$timezone_object = timezone_open( $timezone_string );
			$datetime_object = date_create( "@{$time}" );
			$timezone_offset = timezone_offset_get( $timezone_object, $datetime_object ) / HOUR_IN_SECONDS;

		// Fall back on less reliable gmt_offset.
		} else {
			$timezone_offset = profiles_get_option( 'gmt_offset' );
		}

		// Calculate time based on the offset.
		$calculated_time = $time + ( $timezone_offset * HOUR_IN_SECONDS );

	// No localizing, so just use the time that was submitted.
	} else {
		$calculated_time = $time;
	}

	// Formatted date: "March 18, 2014".
	$formatted_date = date_i18n( profiles_get_option( 'date_format' ), $calculated_time, $gmt );

	// Should we show the time also?
	if ( true !== $exclude_time ) {

		// Formatted time: "2:00 pm".
		$formatted_time = date_i18n( profiles_get_option( 'time_format' ), $calculated_time, $gmt );

		// Return string formatted with date and time.
		$formatted_date = sprintf( esc_html__( '%1$s at %2$s', 'profiles' ), $formatted_date, $formatted_time );
	}

	/**
	 * Filters the date based on a UNIX timestamp.
	 *
	 * @since 1.0.0
	 *
	 * @param string $formatted_date Formatted date from the timestamp.
	 */
	return apply_filters( 'profiles_format_time', $formatted_date );
}

/**
 * Select between two dynamic strings, according to context.
 *
 * This function can be used in cases where a phrase used in a template will
 * differ for a user looking at his own profile and a user looking at another
 * user's profile (eg, "My Friends" and "Joe's Friends"). Pass both versions
 * of the phrase, and profiles_word_or_name() will detect which is appropriate, and
 * do the necessary argument swapping for dynamic phrases.
 *
 * @since 1.0.0
 *
 * @param string $youtext    The "you" version of the phrase (eg "Your Friends").
 * @param string $nametext   The other-user version of the phrase. Should be in
 *                           a format appropriate for sprintf() - use %s in place of the displayed
 *                           user's name (eg "%'s Friends").
 * @param bool   $capitalize Optional. Force into title case. Default: true.
 * @param bool   $echo       Optional. True to echo the results, false to return them.
 *                           Default: true.
 * @return string|null $nametext If ! $echo, returns the appropriate string.
 */
function profiles_word_or_name( $youtext, $nametext, $capitalize = true, $echo = true ) {

	if ( ! empty( $capitalize ) ) {
		$youtext = profiles_core_ucfirst( $youtext );
	}

	if ( profiles_displayed_user_id() == profiles_loggedin_user_id() ) {
		if ( true == $echo ) {

			/**
			 * Filters the text used based on context of own profile or someone else's profile.
			 *
			 * @since 1.0.0
			 *
			 * @param string $youtext Context-determined string to display.
			 */
			echo apply_filters( 'profiles_word_or_name', $youtext );
		} else {

			/** This filter is documented in profiles-core/profiles-core-template.php */
			return apply_filters( 'profiles_word_or_name', $youtext );
		}
	} else {
		$fullname = profiles_get_displayed_user_fullname();
		$fullname = (array) explode( ' ', $fullname );
		$nametext = sprintf( $nametext, $fullname[0] );
		if ( true == $echo ) {

			/** This filter is documented in profiles-core/profiles-core-template.php */
			echo apply_filters( 'profiles_word_or_name', $nametext );
		} else {

			/** This filter is documented in profiles-core/profiles-core-template.php */
			return apply_filters( 'profiles_word_or_name', $nametext );
		}
	}
}

/**
 * Do the 'profiles_styles' action, and call wp_print_styles().
 *
 * No longer used in Profiles.
 *
 * @todo Deprecate.
 */
function profiles_styles() {
	do_action( 'profiles_styles' );
	wp_print_styles();
}

/** Search Form ***************************************************************/

/**
 * Return the "action" attribute for search forms.
 *
 * @since 1.0.0
 *
 * @return string URL action attribute for search forms, eg example.com/search/.
 */
function profiles_search_form_action() {

	/**
	 * Filters the "action" attribute for search forms.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value Search form action url.
	 */
	return apply_filters( 'profiles_search_form_action', trailingslashit( profiles_get_root_domain() . '/' . profiles_get_search_slug() ) );
}

/**
 * Generate the basic search form as used in BP-Default's header.
 *
 * @since 1.0.0
 *
 * @return string HTML <select> element.
 */
function profiles_search_form_type_select() {

	$options = array();

	if ( profiles_is_active( 'xprofile' ) ) {
		$options['members'] = _x( 'Members', 'search form', 'profiles' );
	}

	if ( profiles_is_active( 'groups' ) ) {
		$options['groups']  = _x( 'Groups', 'search form', 'profiles' );
	}

	if ( profiles_is_active( 'blogs' ) && is_multisite() ) {
		$options['blogs']   = _x( 'Blogs', 'search form', 'profiles' );
	}

	if ( profiles_is_active( 'forums' ) && profiles_forums_is_installed_correctly() && profiles_forums_has_directory() ) {
		$options['forums']  = _x( 'Forums', 'search form', 'profiles' );
	}

	$options['posts'] = _x( 'Posts', 'search form', 'profiles' );

	// Eventually this won't be needed and a page will be built to integrate all search results.
	$selection_box  = '<label for="search-which" class="accessibly-hidden">' . _x( 'Search these:', 'search form', 'profiles' ) . '</label>';
	$selection_box .= '<select name="search-which" id="search-which" style="width: auto">';

	/**
	 * Filters all of the component options available for search scope.
	 *
	 * @since 1.5.0
	 *
	 * @param array $options Array of options to add to select field.
	 */
	$options = apply_filters( 'profiles_search_form_type_select_options', $options );
	foreach( (array) $options as $option_value => $option_title ) {
		$selection_box .= sprintf( '<option value="%s">%s</option>', $option_value, $option_title );
	}

	$selection_box .= '</select>';

	/**
	 * Filters the complete <select> input used for search scope.
	 *
	 * @since 1.0.0
	 *
	 * @param string $selection_box <select> input for selecting search scope.
	 */
	return apply_filters( 'profiles_search_form_type_select', $selection_box );
}

/**
 * Output the default text for the search box for a given component.
 *
 * @since 1.5.0
 *
 * @see profiles_get_search_default_text()
 *
 * @param string $component See {@link profiles_get_search_default_text()}.
 */
function profiles_search_default_text( $component = '' ) {
	echo profiles_get_search_default_text( $component );
}
	/**
	 * Return the default text for the search box for a given component.
	 *
	 * @since 1.5.0
	 *
	 * @param string $component Component name. Default: current component.
	 * @return string Placeholder text for search field.
	 */
	function profiles_get_search_default_text( $component = '' ) {

		$profiles = profiles();

		if ( empty( $component ) ) {
			$component = profiles_current_component();
		}

		$default_text = __( 'Search anything...', 'profiles' );

		// Most of the time, $component will be the actual component ID.
		if ( !empty( $component ) ) {
			if ( !empty( $profiles->{$component}->search_string ) ) {
				$default_text = $profiles->{$component}->search_string;
			} else {
				// When the request comes through AJAX, we need to get the component
				// name out of $profiles->pages.
				if ( !empty( $profiles->pages->{$component}->slug ) ) {
					$key = $profiles->pages->{$component}->slug;
					if ( !empty( $profiles->{$key}->search_string ) ) {
						$default_text = $profiles->{$key}->search_string;
					}
				}
			}
		}

		/**
		 * Filters the default text for the search box for a given component.
		 *
		 * @since 1.5.0
		 *
		 * @param string $default_text Default text for search box.
		 * @param string $component    Current component displayed.
		 */
		return apply_filters( 'profiles_get_search_default_text', $default_text, $component );
	}

/**
 * Fire the 'profiles_custom_profile_boxes' action.
 *
 * No longer used in Profiles.
 *
 * @todo Deprecate.
 */
function profiles_custom_profile_boxes() {
	do_action( 'profiles_custom_profile_boxes' );
}

/**
 * Fire the 'profiles_custom_profile_sidebar_boxes' action.
 *
 * No longer used in Profiles.
 *
 * @todo Deprecate.
 */
function profiles_custom_profile_sidebar_boxes() {
	do_action( 'profiles_custom_profile_sidebar_boxes' );
}

/**
 * Output the attributes for a form field.
 *
 * @since 2.2.0
 *
 * @param string $name       The field name to output attributes for.
 * @param array  $attributes Array of existing attributes to add.
 */
function profiles_form_field_attributes( $name = '', $attributes = array() ) {
	echo profiles_get_form_field_attributes( $name, $attributes );
}
	/**
	 * Get the attributes for a form field.
	 *
	 * Primarily to add better support for touchscreen devices, but plugin devs
	 * can use the 'profiles_get_form_field_extra_attributes' filter for further
	 * manipulation.
	 *
	 * @since 2.2.0
	 *
	 * @param string $name       The field name to get attributes for.
	 * @param array  $attributes Array of existing attributes to add.
	 * @return string
	 */
	function profiles_get_form_field_attributes( $name = '', $attributes = array() ) {
		$retval = '';

		if ( empty( $attributes ) ) {
			$attributes = array();
		}

		$name = strtolower( $name );

		switch ( $name ) {
			case 'username' :
			case 'blogname' :
				$attributes['autocomplete']   = 'off';
				$attributes['autocapitalize'] = 'none';
				break;

			case 'email' :
				if ( wp_is_mobile() ) {
					$attributes['autocapitalize'] = 'none';
				}
				break;

			case 'password' :
				$attributes['spellcheck']   = 'false';
				$attributes['autocomplete'] = 'off';

				if ( wp_is_mobile() ) {
					$attributes['autocorrect']    = 'false';
					$attributes['autocapitalize'] = 'none';
				}
				break;
		}

		/**
		 * Filter the attributes for a field before rendering output.
		 *
		 * @since 2.2.0
		 *
		 * @param array  $attributes The field attributes.
		 * @param string $name       The field name.
		 */
		$attributes = (array) apply_filters( 'profiles_get_form_field_attributes', $attributes, $name );

		foreach( $attributes as $attr => $value ) {
			$retval .= sprintf( ' %s="%s"', sanitize_key( $attr ), esc_attr( $value ) );
		}

		return $retval;
	}

/**
 * Create and output a button.
 *
 * @since 1.2.6
 *
 * @see profiles_get_button()
 *
 * @param array|string $args See {@link Profiles_Button}.
 */
function profiles_button( $args = '' ) {
	echo profiles_get_button( $args );
}
	/**
	 * Create and return a button.
	 *
	 * @since 1.2.6
	 *
	 * @see Profiles_Button for a description of arguments and return value.
	 *
	 * @param array|string $args See {@link Profiles_Button}.
	 * @return string HTML markup for the button.
	 */
	function profiles_get_button( $args = '' ) {
		$button = new Profiles_Button( $args );

		/**
		 * Filters the requested button output.
		 *
		 * @since 1.2.6
		 *
		 * @param string    $contents  Button context to be used.
		 * @param array     $args      Array of args for the button.
		 * @param Profiles_Button $button    Profiles_Button object.
		 */
		return apply_filters( 'profiles_get_button', $button->contents, $args, $button );
	}

/**
 * Truncate text.
 *
 * Cuts a string to the length of $length and replaces the last characters
 * with the ending if the text is longer than length.
 *
 * This function is borrowed from CakePHP v2.0, under the MIT license. See
 * http://book.cakephp.org/view/1469/Text#truncate-1625
 *
 * @since 1.0.0
 * @since 2.6.0 Added 'strip_tags' and 'remove_links' as $options args.
 *
 * @param string $text   String to truncate.
 * @param int    $length Optional. Length of returned string, including ellipsis.
 *                       Default: 225.
 * @param array  $options {
 *     An array of HTML attributes and options. Each item is optional.
 *     @type string $ending            The string used after truncation.
 *                                     Default: ' [&hellip;]'.
 *     @type bool   $exact             If true, $text will be trimmed to exactly $length.
 *                                     If false, $text will not be cut mid-word. Default: false.
 *     @type bool   $html              If true, don't include HTML tags when calculating
 *                                     excerpt length. Default: true.
 *     @type bool   $filter_shortcodes If true, shortcodes will be stripped.
 *                                     Default: true.
 *     @type bool   $strip_tags        If true, HTML tags will be stripped. Default: false.
 *                                     Only applicable if $html is set to false.
 *     @type bool   $remove_links      If true, URLs will be stripped. Default: false.
 *                                     Only applicable if $html is set to false.
 * }
 * @return string Trimmed string.
 */
function profiles_create_excerpt( $text, $length = 225, $options = array() ) {

	// Backward compatibility. The third argument used to be a boolean $filter_shortcodes.
	$filter_shortcodes_default = is_bool( $options ) ? $options : true;

	$r = profiles_parse_args( $options, array(
		'ending'            => __( ' [&hellip;]', 'profiles' ),
		'exact'             => false,
		'html'              => true,
		'filter_shortcodes' => $filter_shortcodes_default,
		'strip_tags'        => false,
		'remove_links'      => false,
	), 'create_excerpt' );

	// Save the original text, to be passed along to the filter.
	$original_text = $text;

	/**
	 * Filters the excerpt length to trim text to.
	 *
	 * @since 1.5.0
	 *
	 * @param int $length Length of returned string, including ellipsis.
	 */
	$length = apply_filters( 'profiles_excerpt_length',      $length      );

	/**
	 * Filters the excerpt appended text value.
	 *
	 * @since 1.5.0
	 *
	 * @param string $value Text to append to the end of the excerpt.
	 */
	$ending = apply_filters( 'profiles_excerpt_append_text', $r['ending'] );

	// Remove shortcodes if necessary.
	if ( ! empty( $r['filter_shortcodes'] ) ) {
		$text = strip_shortcodes( $text );
	}

	// When $html is true, the excerpt should be created without including HTML tags in the
	// excerpt length.
	if ( ! empty( $r['html'] ) ) {

		// The text is short enough. No need to truncate.
		if ( mb_strlen( preg_replace( '/<.*?>/', '', $text ) ) <= $length ) {
			return $text;
		}

		$totalLength = mb_strlen( strip_tags( $ending ) );
		$openTags    = array();
		$truncate    = '';

		// Find all the tags and HTML comments and put them in a stack for later use.
		preg_match_all( '/(<\/?([\w+!]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER );

		foreach ( $tags as $tag ) {
			// Process tags that need to be closed.
			if ( !preg_match( '/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s',  $tag[2] ) ) {
				if ( preg_match( '/<[\w]+[^>]*>/s', $tag[0] ) ) {
					array_unshift( $openTags, $tag[2] );
				} elseif ( preg_match('/<\/([\w]+)[^>]*>/s', $tag[0], $closeTag ) ) {
					$pos = array_search( $closeTag[1], $openTags );
					if ( $pos !== false ) {
						array_splice( $openTags, $pos, 1 );
					}
				}
			}

			$truncate     .= $tag[1];
			$contentLength = mb_strlen( preg_replace( '/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $tag[3] ) );

			if ( $contentLength + $totalLength > $length ) {
				$left = $length - $totalLength;
				$entitiesLength = 0;
				if ( preg_match_all( '/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $tag[3], $entities, PREG_OFFSET_CAPTURE ) ) {
					foreach ( $entities[0] as $entity ) {
						if ( $entity[1] + 1 - $entitiesLength <= $left ) {
							$left--;
							$entitiesLength += mb_strlen( $entity[0] );
						} else {
							break;
						}
					}
				}

				$truncate .= mb_substr( $tag[3], 0 , $left + $entitiesLength );
				break;
			} else {
				$truncate .= $tag[3];
				$totalLength += $contentLength;
			}
			if ( $totalLength >= $length ) {
				break;
			}
		}
	} else {
		// Strip HTML tags if necessary.
		if ( ! empty( $r['strip_tags'] ) ) {
			$text = strip_tags( $text );
		}

		// Remove links if necessary.
		if ( ! empty( $r['remove_links'] ) ) {
			$text = preg_replace( '#^\s*(https?://[^\s"]+)\s*$#im', '', $text );
		}

		if ( mb_strlen( $text ) <= $length ) {
			/**
			 * Filters the final generated excerpt.
			 *
			 * @since 1.1.0
			 *
			 * @param string $truncate      Generated excerpt.
			 * @param string $original_text Original text provided.
			 * @param int    $length        Length of returned string, including ellipsis.
			 * @param array  $options       Array of HTML attributes and options.
			 */
			return apply_filters( 'profiles_create_excerpt', $text, $original_text, $length, $options );
		} else {
			$truncate = mb_substr( $text, 0, $length - mb_strlen( $ending ) );
		}
	}

	// If $exact is false, we can't break on words.
	if ( empty( $r['exact'] ) ) {
		// Find the position of the last space character not part of a tag.
		preg_match_all( '/<[a-z\!\/][^>]*>/', $truncate, $_truncate_tags, PREG_OFFSET_CAPTURE );

		// Rekey tags by the string index of their last character.
		$truncate_tags = array();
		if ( ! empty( $_truncate_tags[0] ) ) {
			foreach ( $_truncate_tags[0] as $_tt ) {
				$_tt['start'] = $_tt[1];
				$_tt['end']   = $_tt[1] + strlen( $_tt[0] );
				$truncate_tags[ $_tt['end'] ] = $_tt;
			}
		}

		$truncate_length = mb_strlen( $truncate );
		$spacepos = $truncate_length + 1;
		for ( $pos = $truncate_length - 1; $pos >= 0; $pos-- ) {
			// Word boundaries are spaces and the close of HTML tags, when the tag is preceded by a space.
			$is_word_boundary = ' ' === $truncate[ $pos ];
			if ( ! $is_word_boundary && isset( $truncate_tags[ $pos - 1 ] ) ) {
				$preceding_tag    = $truncate_tags[ $pos - 1 ];
				if ( ' ' === $truncate[ $preceding_tag['start'] - 1 ] ) {
					$is_word_boundary = true;
					break;
				}
			}

			if ( ! $is_word_boundary ) {
				continue;
			}

			// If there are no tags in the string, the first space found is the right one.
			if ( empty( $truncate_tags ) ) {
				$spacepos = $pos;
				break;
			}

			// Look at each tag to see if the space is inside of it.
			$intag = false;
			foreach ( $truncate_tags as $tt ) {
				if ( $pos > $tt['start'] && $pos < $tt['end'] ) {
					$intag = true;
					break;
				}
			}

			if ( ! $intag ) {
				$spacepos = $pos;
				break;
			}
		}

		if ( $r['html'] ) {
			$bits = mb_substr( $truncate, $spacepos );
			preg_match_all( '/<\/([a-z]+)>/', $bits, $droppedTags, PREG_SET_ORDER );
			if ( !empty( $droppedTags ) ) {
				foreach ( $droppedTags as $closingTag ) {
					if ( !in_array( $closingTag[1], $openTags ) ) {
						array_unshift( $openTags, $closingTag[1] );
					}
				}
			}
		}

		$truncate = rtrim( mb_substr( $truncate, 0, $spacepos ) );
	}
	$truncate .= $ending;

	if ( !empty( $r['html'] ) ) {
		foreach ( $openTags as $tag ) {
			$truncate .= '</' . $tag . '>';
		}
	}

	/** This filter is documented in /profiles-core/profiles-core-template.php */
	return apply_filters( 'profiles_create_excerpt', $truncate, $original_text, $length, $options );
}
add_filter( 'profiles_create_excerpt', 'stripslashes_deep'  );
add_filter( 'profiles_create_excerpt', 'force_balance_tags' );

/**
 * Output the total member count for the site.
 *
 * @since 1.2.0
 */
function profiles_total_member_count() {
	echo profiles_get_total_member_count();
}
	/**
	 * Return the total member count in your BP instance.
	 *
	 * Since Profiles 1.6, this function has used profiles_core_get_active_member_count(),
	 * which counts non-spam, non-deleted users who have last_activity.
	 * This value will correctly match the total member count number used
	 * for pagination on member directories.
	 *
	 * Before Profiles 1.6, this function used profiles_core_get_total_member_count(),
	 * which did not take into account last_activity, and thus often
	 * resulted in higher counts than shown by member directory pagination.
	 *
	 * @since 1.2.0
	 *
	 * @return int Member count.
	 */
	function profiles_get_total_member_count() {

		/**
		 * Filters the total member count in your BP instance.
		 *
		 * @since 1.2.0
		 *
		 * @param int $value Member count.
		 */
		return apply_filters( 'profiles_get_total_member_count', profiles_core_get_active_member_count() );
	}
	add_filter( 'profiles_get_total_member_count', 'profiles_core_number_format' );

/**
 * Output whether blog signup is allowed.
 *
 * @todo Deprecate. It doesn't make any sense to echo a boolean.
 */
function profiles_blog_signup_allowed() {
	echo profiles_get_blog_signup_allowed();
}
	/**
	 * Is blog signup allowed?
	 *
	 * Returns true if is_multisite() and blog creation is enabled at
	 * Network Admin > Settings.
	 *
	 * @since 1.2.0
	 *
	 * @return bool True if blog signup is allowed, otherwise false.
	 */
	function profiles_get_blog_signup_allowed() {

		if ( ! is_multisite() ) {
			return false;
		}

		$status = profiles_core_get_root_option( 'registration' );
		if ( ( 'none' !== $status ) && ( 'user' !== $status ) ) {
			return true;
		}

		return false;
	}

/**
 * Check whether an activation has just been completed.
 *
 * @since 1.1.0
 *
 * @return bool True if the activation_complete global flag has been set,
 *              otherwise false.
 */
function profiles_account_was_activated() {
	$profiles                  = profiles();
	$activation_complete = !empty( $profiles->activation_complete )
		? $profiles->activation_complete
		: false;

	return $activation_complete;
}

/**
 * Check whether registrations require activation on this installation.
 *
 * On a normal Profiles installation, all registrations require email
 * activation. This filter exists so that customizations that omit activation
 * can remove certain notification text from the registration screen.
 *
 * @since 1.2.0
 *
 * @return bool True by default.
 */
function profiles_registration_needs_activation() {

	/**
	 * Filters whether registrations require activation on this installation.
	 *
	 * @since 1.2.0
	 *
	 * @param bool $value Whether registrations require activation. Default true.
	 */
	return apply_filters( 'profiles_registration_needs_activation', true );
}

/**
 * Retrieve a client friendly version of the root blog name.
 *
 * The blogname option is escaped with esc_html on the way into the database in
 * sanitize_option, we want to reverse this for the plain text arena of emails.
 *
 * @since 1.7.0
 * @since 2.5.0 No longer used by Profiles, but not deprecated in case any existing plugins use it.
 *
 * @see https://profiles.trac.wordpress.org/ticket/4401
 *
 * @param array $args {
 *     Array of optional parameters.
 *     @type string $before  String to appear before the site name in the
 *                           email subject. Default: '['.
 *     @type string $after   String to appear after the site name in the
 *                           email subject. Default: ']'.
 *     @type string $default The default site name, to be used when none is
 *                           found in the database. Default: 'Community'.
 *     @type string $text    Text to append to the site name (ie, the main text of
 *                           the email subject).
 * }
 * @return string Sanitized email subject.
 */
function profiles_get_email_subject( $args = array() ) {

	$r = profiles_parse_args( $args, array(
		'before'  => '[',
		'after'   => ']',
		'default' => __( 'Community', 'profiles' ),
		'text'    => ''
	), 'get_email_subject' );

	$subject = $r['before'] . wp_specialchars_decode( profiles_get_option( 'blogname', $r['default'] ), ENT_QUOTES ) . $r['after'] . ' ' . $r['text'];

	/**
	 * Filters a client friendly version of the root blog name.
	 *
	 * @since 1.7.0
	 *
	 * @param string $subject Client friendy version of the root blog name.
	 * @param array  $r       Array of arguments for the email subject.
	 */
	return apply_filters( 'profiles_get_email_subject', $subject, $r );
}

/**
 * Allow templates to pass parameters directly into the template loops via AJAX.
 *
 * For the most part this will be filtered in a theme's functions.php for
 * example in the default theme it is filtered via profiles_dtheme_ajax_querystring().
 *
 * By using this template tag in the templates it will stop them from showing
 * errors if someone copies the templates from the default theme into another
 * WordPress theme without coping the functions from functions.php.
 *
 * @since 1.2.0
 *
 * @param string|bool $object Current template component.
 * @return string The AJAX querystring.
 */
function profiles_ajax_querystring( $object = false ) {
	$profiles = profiles();

	if ( ! isset( $profiles->ajax_querystring ) ) {
		$profiles->ajax_querystring = '';
	}

	/**
	 * Filters the template paramenters to be used in the query string.
	 *
	 * Allows templates to pass parameters into the template loops via AJAX.
	 *
	 * @since 1.2.0
	 *
	 * @param string $ajax_querystring Current query string.
	 * @param string $object           Current template component.
	 */
	return apply_filters( 'profiles_ajax_querystring', $profiles->ajax_querystring, $object );
}

/** Template Classes and _is functions ****************************************/

/**
 * Return the name of the current component.
 *
 * @since 1.0.0
 *
 * @return string Component name.
 */
function profiles_current_component() {
	$profiles                = profiles();
	$current_component = !empty( $profiles->current_component )
		? $profiles->current_component
		: false;

	/**
	 * Filters the name of the current component.
	 *
	 * @since 1.0.0
	 *
	 * @param string|bool $current_component Current component if available or false.
	 */
	return apply_filters( 'profiles_current_component', $current_component );
}

/**
 * Return the name of the current action.
 *
 * @since 1.0.0
 *
 * @return string Action name.
 */
function profiles_current_action() {
	$profiles             = profiles();
	$current_action = !empty( $profiles->current_action )
		? $profiles->current_action
		: '';

	/**
	 * Filters the name of the current action.
	 *
	 * @since 1.0.0
	 *
	 * @param string $current_action Current action.
	 */
	return apply_filters( 'profiles_current_action', $current_action );
}

/**
 * Return the name of the current item.
 *
 * @since 1.1.0
 *
 * @return string|bool
 */
function profiles_current_item() {
	$profiles           = profiles();
	$current_item = !empty( $profiles->current_item )
		? $profiles->current_item
		: false;

	/**
	 * Filters the name of the current item.
	 *
	 * @since 1.1.0
	 *
	 * @param string|bool $current_item Current item if available or false.
	 */
	return apply_filters( 'profiles_current_item', $current_item );
}

/**
 * Return the value of $profiles->action_variables.
 *
 * @since 1.0.0
 *
 * @return array|bool $action_variables The action variables array, or false
 *                                      if the array is empty.
 */
function profiles_action_variables() {
	$profiles               = profiles();
	$action_variables = !empty( $profiles->action_variables )
		? $profiles->action_variables
		: false;

	/**
	 * Filters the value of $profiles->action_variables.
	 *
	 * @since 1.0.0
	 *
	 * @param array|bool $action_variables Available action variables.
	 */
	return apply_filters( 'profiles_action_variables', $action_variables );
}

/**
 * Return the value of a given action variable.
 *
 * @since 1.5.0
 *
 * @param int $position The key of the action_variables array that you want.
 * @return string|bool $action_variable The value of that position in the
 *                                      array, or false if not found.
 */
function profiles_action_variable( $position = 0 ) {
	$action_variables = profiles_action_variables();
	$action_variable  = isset( $action_variables[ $position ] )
		? $action_variables[ $position ]
		: false;

	/**
	 * Filters the value of a given action variable.
	 *
	 * @since 1.5.0
	 *
	 * @param string|bool $action_variable Requested action variable based on position.
	 * @param int         $position        The key of the action variable requested.
	 */
	return apply_filters( 'profiles_action_variable', $action_variable, $position );
}

/**
 * Output the "root domain", the URL of the BP root blog.
 *
 * @since 1.1.0
 */
function profiles_root_domain() {
	echo profiles_get_root_domain();
}
	/**
	 * Return the "root domain", the URL of the BP root blog.
	 *
	 * @since 1.1.0
	 *
	 * @return string URL of the BP root blog.
	 */
	function profiles_get_root_domain() {
		$profiles = profiles();

		if ( ! empty( $profiles->root_domain ) ) {
			$domain = $profiles->root_domain;
		} else {
			$domain          = profiles_core_get_root_domain();
			$profiles->root_domain = $domain;
		}

		/**
		 * Filters the "root domain", the URL of the BP root blog.
		 *
		 * @since 1.2.4
		 *
		 * @param string $domain URL of the BP root blog.
		 */
		return apply_filters( 'profiles_get_root_domain', $domain );
	}

/**
 * Output the root slug for a given component.
 *
 * @since 1.5.0
 *
 * @param string $component The component name.
 */
function profiles_root_slug( $component = '' ) {
	echo profiles_get_root_slug( $component );
}
	/**
	 * Get the root slug for given component.
	 *
	 * The "root slug" is the string used when concatenating component
	 * directory URLs. For example, on an installation where the Groups
	 * component's directory is located at http://example.com/groups/, the
	 * root slug for the Groups component is 'groups'. This string
	 * generally corresponds to page_name of the component's directory
	 * page.
	 *
	 * In order to maintain backward compatibility, the following procedure
	 * is used:
	 * 1) Use the short slug to get the canonical component name from the
	 *    active component array.
	 * 2) Use the component name to get the root slug out of the
	 *    appropriate part of the $profiles global.
	 * 3) If nothing turns up, it probably means that $component is itself
	 *    a root slug.
	 *
	 * Example: If your groups directory is at /community/companies, this
	 * function first uses the short slug 'companies' (ie the current
	 * component) to look up the canonical name 'groups' in
	 * $profiles->active_components. Then it uses 'groups' to get the root slug,
	 * from $profiles->groups->root_slug.
	 *
	 * @since 1.5.0
	 *
	 * @param string $component Optional. Defaults to the current component.
	 * @return string $root_slug The root slug.
	 */
	function profiles_get_root_slug( $component = '' ) {
		$profiles        = profiles();
		$root_slug = '';

		// Use current global component if none passed.
		if ( empty( $component ) ) {
			$component = profiles_current_component();
		}

		// Component is active.
		if ( ! empty( $profiles->active_components[ $component ] ) ) {

			// Backward compatibility: in legacy plugins, the canonical component id
			// was stored as an array value in $profiles->active_components.
			$component_name = ( '1' == $profiles->active_components[ $component ] )
				? $component
				: $profiles->active_components[$component];

			// Component has specific root slug.
			if ( ! empty( $profiles->{$component_name}->root_slug ) ) {
				$root_slug = $profiles->{$component_name}->root_slug;
			}
		}

		// No specific root slug, so fall back to component slug.
		if ( empty( $root_slug ) ) {
			$root_slug = $component;
		}

		/**
		 * Filters the root slug for given component.
		 *
		 * @since 1.5.0
		 *
		 * @param string $root_slug Root slug for given component.
		 * @param string $component Current component.
		 */
		return apply_filters( 'profiles_get_root_slug', $root_slug, $component );
	}

/**
 * Return the component name based on a root slug.
 *
 * @since 1.5.0
 *
 * @param string $root_slug Needle to our active component haystack.
 * @return mixed False if none found, component name if found.
 */
function profiles_get_name_from_root_slug( $root_slug = '' ) {
	$profiles = profiles();

	// If no slug is passed, look at current_component.
	if ( empty( $root_slug ) ) {
		$root_slug = profiles_current_component();
	}

	// No current component or root slug, so flee.
	if ( empty( $root_slug ) ) {
		return false;
	}

	// Loop through active components and look for a match.
	foreach ( array_keys( $profiles->active_components ) as $component ) {
		if ( ( ! empty( $profiles->{$component}->slug ) && ( $profiles->{$component}->slug == $root_slug ) ) || ( ! empty( $profiles->{$component}->root_slug ) && ( $profiles->{$component}->root_slug === $root_slug ) ) ) {
			return $profiles->{$component}->name;
		}
	}

	return false;
}

/**
 * Returns whether or not a user has access.
 *
 * @since 1.2.4
 *
 * @return bool
 */
function profiles_user_has_access() {
	$has_access = profiles_current_user_can( 'profiles_moderate' ) || profiles_is_my_profile();

	/**
	 * Filters whether or not a user has access.
	 *
	 * @since 1.2.4
	 *
	 * @param bool $has_access Whether or not user has access.
	 */
	return (bool) apply_filters( 'profiles_user_has_access', $has_access );
}

/**
 * Output the search slug.
 *
 * @since 1.5.0
 *
 */
function profiles_search_slug() {
	echo profiles_get_search_slug();
}
	/**
	 * Return the search slug.
	 *
	 * @since 1.5.0
	 *
	 * @return string The search slug. Default: 'search'.
	 */
	function profiles_get_search_slug() {

		/**
		 * Filters the search slug.
		 *
		 * @since 1.5.0
		 *
		 * @const string Profiles_SEARCH_SLUG The search slug. Default "search".
		 */
		return apply_filters( 'profiles_get_search_slug', Profiles_SEARCH_SLUG );
	}

/**
 * Get the ID of the currently displayed user.
 *
 * @since 1.0.0
 *
 * @return int $id ID of the currently displayed user.
 */
function profiles_displayed_user_id() {
	$profiles = profiles();
	$id = !empty( $profiles->displayed_user->id )
		? $profiles->displayed_user->id
		: 0;

	/**
	 * Filters the ID of the currently displayed user.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id ID of the currently displayed user.
	 */
	return (int) apply_filters( 'profiles_displayed_user_id', $id );
}

/**
 * Get the ID of the currently logged-in user.
 *
 * @since 1.0.0
 *
 * @return int ID of the logged-in user.
 */
function profiles_loggedin_user_id() {
	$profiles = profiles();
	$id = !empty( $profiles->loggedin_user->id )
		? $profiles->loggedin_user->id
		: 0;

	/**
	 * Filters the ID of the currently logged-in user.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id ID of the currently logged-in user.
	 */
	return (int) apply_filters( 'profiles_loggedin_user_id', $id );
}

/** The is_() functions to determine the current page *****************************/

/**
 * Check to see whether the current page belongs to the specified component.
 *
 * This function is designed to be generous, accepting several different kinds
 * of value for the $component parameter. It checks $component_name against:
 * - the component's root_slug, which matches the page slug in $profiles->pages.
 * - the component's regular slug.
 * - the component's id, or 'canonical' name.
 *
 * @since 1.5.0
 *
 * @param string $component Name of the component being checked.
 * @return bool Returns true if the component matches, or else false.
 */
function profiles_is_current_component( $component = '' ) {

	// Default is no match. We'll check a few places for matches.
	$is_current_component = false;

	// Always return false if a null value is passed to the function.
	if ( empty( $component ) ) {
		return false;
	}

	// Backward compatibility: 'xprofile' should be read as 'profile'.
	if ( 'xprofile' === $component ) {
		$component = 'profile';
	}

	$profiles = profiles();

	// Only check if Profiles found a current_component.
	if ( ! empty( $profiles->current_component ) ) {

		// First, check to see whether $component_name and the current
		// component are a simple match.
		if ( $profiles->current_component == $component ) {
			$is_current_component = true;

		// Since the current component is based on the visible URL slug let's
		// check the component being passed and see if its root_slug matches.
		} elseif ( isset( $profiles->{$component}->root_slug ) && $profiles->{$component}->root_slug == $profiles->current_component ) {
			$is_current_component = true;

		// Because slugs can differ from root_slugs, we should check them too.
		} elseif ( isset( $profiles->{$component}->slug ) && $profiles->{$component}->slug == $profiles->current_component ) {
			$is_current_component = true;

		// Next, check to see whether $component is a canonical,
		// non-translatable component name. If so, we can return its
		// corresponding slug from $profiles->active_components.
		} elseif ( $key = array_search( $component, $profiles->active_components ) ) {
			if ( strstr( $profiles->current_component, $key ) ) {
				$is_current_component = true;
			}

		// If we haven't found a match yet, check against the root_slugs
		// created by $profiles->pages, as well as the regular slugs.
		} else {
			foreach ( $profiles->active_components as $id ) {
				// If the $component parameter does not match the current_component,
				// then move along, these are not the droids you are looking for.
				if ( empty( $profiles->{$id}->root_slug ) || $profiles->{$id}->root_slug != $profiles->current_component ) {
					continue;
				}

				if ( $id == $component ) {
					$is_current_component = true;
					break;
				}
			}
		}
	}

	/**
	 * Filters whether the current page belongs to the specified component.
	 *
	 * @since 1.5.0
	 *
	 * @param bool   $is_current_component Whether or not the current page belongs to specified component.
	 * @param string $component            Name of the component being checked.
	 */
	return apply_filters( 'profiles_is_current_component', $is_current_component, $component );
}

/**
 * Check to see whether the current page matches a given action.
 *
 * Along with profiles_is_current_component() and profiles_is_action_variable(), this
 * function is mostly used to help determine when to use a given screen
 * function.
 *
 * In BP parlance, the current_action is the URL chunk that comes directly
 * after the current item slug. E.g., in
 *   http://example.com/groups/my-group/members
 * the current_action is 'members'.
 *
 * @since 1.5.0
 *
 * @param string $action The action being tested against.
 * @return bool True if the current action matches $action.
 */
function profiles_is_current_action( $action = '' ) {
	return (bool) ( $action === profiles_current_action() );
}

/**
 * Check to see whether the current page matches a given action_variable.
 *
 * Along with profiles_is_current_component() and profiles_is_current_action(), this
 * function is mostly used to help determine when to use a given screen
 * function.
 *
 * In BP parlance, action_variables are an array made up of the URL chunks
 * appearing after the current_action in a URL. For example,
 *   http://example.com/groups/my-group/admin/group-settings
 * $action_variables[0] is 'group-settings'.
 *
 * @since 1.5.0
 *
 * @param string   $action_variable The action_variable being tested against.
 * @param int|bool $position        Optional. The array key you're testing against. If you
 *                                  don't provide a $position, the function will return true if the
 *                                  $action_variable is found *anywhere* in the action variables array.
 * @return bool True if $action_variable matches at the $position provided.
 */
function profiles_is_action_variable( $action_variable = '', $position = false ) {
	$is_action_variable = false;

	if ( false !== $position ) {
		// When a $position is specified, check that slot in the action_variables array.
		if ( $action_variable ) {
			$is_action_variable = $action_variable == profiles_action_variable( $position );
		} else {
			// If no $action_variable is provided, we are essentially checking to see
			// whether the slot is empty.
			$is_action_variable = !profiles_action_variable( $position );
		}
	} else {
		// When no $position is specified, check the entire array.
		$is_action_variable = in_array( $action_variable, (array)profiles_action_variables() );
	}

	/**
	 * Filters whether the current page matches a given action_variable.
	 *
	 * @since 1.5.0
	 *
	 * @param bool   $is_action_variable Whether the current page matches a given action_variable.
	 * @param string $action_variable    The action_variable being tested against.
	 * @param int    $position           The array key tested against.
	 */
	return apply_filters( 'profiles_is_action_variable', $is_action_variable, $action_variable, $position );
}

/**
 * Check against the current_item.
 *
 * @since 1.5.0
 *
 * @param string $item The item being checked.
 * @return bool True if $item is the current item.
 */
function profiles_is_current_item( $item = '' ) {
	$retval = ( $item === profiles_current_item() );

	/**
	 * Filters whether or not an item is the current item.
	 *
	 * @since 2.1.0
	 *
	 * @param bool   $retval Whether or not an item is the current item.
	 * @param string $item   The item being checked.
	 */
	return (bool) apply_filters( 'profiles_is_current_item', $retval, $item );
}

/**
 * Are we looking at a single item? (group, user, etc).
 *
 * @since 1.1.0
 *
 * @return bool True if looking at a single item, otherwise false.
 */
function profiles_is_single_item() {
	$profiles     = profiles();
	$retval = false;

	if ( isset( $profiles->is_single_item ) ) {
		$retval = $profiles->is_single_item;
	}

	/**
	 * Filters whether or not an item is the a single item. (group, user, etc)
	 *
	 * @since 2.1.0
	 *
	 * @param bool $retval Whether or not an item is a single item.
	 */
	return (bool) apply_filters( 'profiles_is_single_item', $retval );
}

/**
 * Is the logged-in user an admin for the current item?
 *
 * @since 1.5.0
 *
 * @return bool True if the current user is an admin for the current item,
 *              otherwise false.
 */
function profiles_is_item_admin() {
	$profiles     = profiles();
	$retval = false;

	if ( isset( $profiles->is_item_admin ) ) {
		$retval = $profiles->is_item_admin;
	}

	/**
	 * Filters whether or not the logged-in user is an admin for the current item.
	 *
	 * @since 2.1.0
	 *
	 * @param bool $retval Whether or not the logged-in user is an admin.
	 */
	return (bool) apply_filters( 'profiles_is_item_admin', $retval );
}

/**
 * Is the logged-in user a mod for the current item?
 *
 * @since 1.5.0
 *
 * @return bool True if the current user is a mod for the current item,
 *              otherwise false.
 */
function profiles_is_item_mod() {
	$profiles     = profiles();
	$retval = false;

	if ( isset( $profiles->is_item_mod ) ) {
		$retval = $profiles->is_item_mod;
	}

	/**
	 * Filters whether or not the logged-in user is a mod for the current item.
	 *
	 * @since 2.1.0
	 *
	 * @param bool $retval Whether or not the logged-in user is a mod.
	 */
	return (bool) apply_filters( 'profiles_is_item_mod', $retval );
}

/**
 * Is this a component directory page?
 *
 * @since 1.0.0
 *
 * @return bool True if the current page is a component directory, otherwise false.
 */
function profiles_is_directory() {
	$profiles     = profiles();
	$retval = false;

	if ( isset( $profiles->is_directory ) ) {
		$retval = $profiles->is_directory;
	}

	/**
	 * Filters whether or not user is on a component directory page.
	 *
	 * @since 2.1.0
	 *
	 * @param bool $retval Whether or not user is on a component directory page.
	 */
	return (bool) apply_filters( 'profiles_is_directory', $retval );
}

/**
 * Check to see if a component's URL should be in the root, not under a member page.
 *
 * - Yes ('groups' is root)    : http://example.com/groups/the-group
 * - No  ('groups' is not-root): http://example.com/members/andy/groups/the-group
 *
 * This function is on the chopping block. It's currently only used by a few
 * already deprecated functions.
 *
 * @since 1.5.0
 *
 * @param string $component_name Component name to check.
 *
 * @return bool True if root component, else false.
 */
function profiles_is_root_component( $component_name = '' ) {
	$profiles     = profiles();
	$retval = false;

	// Default to the current component if none is passed.
	if ( empty( $component_name ) ) {
		$component_name = profiles_current_component();
	}

	// Loop through active components and check for key/slug matches.
	if ( ! empty( $profiles->active_components ) ) {
		foreach ( (array) $profiles->active_components as $key => $slug ) {
			if ( ( $key === $component_name ) || ( $slug === $component_name ) ) {
				$retval = true;
				break;
			}
		}
	}

	/**
	 * Filters whether or not a component's URL should be in the root, not under a member page.
	 *
	 * @since 2.1.0
	 *
	 * @param bool $retval Whether or not URL should be in the root.
	 */
	return (bool) apply_filters( 'profiles_is_root_component', $retval );
}

/**
 * Check if the specified Profiles component directory is set to be the front page.
 *
 * Corresponds to the setting in wp-admin's Settings > Reading screen.
 *
 * @since 1.5.0
 *
 * @global int $current_blog WordPress global for the current blog.
 *
 * @param string $component Optional. Name of the component to check for.
 *                          Default: current component.
 * @return bool True if the specified component is set to be the site's front
 *              page, otherwise false.
 */
function profiles_is_component_front_page( $component = '' ) {
	global $current_blog;

	$profiles = profiles();

	// Default to the current component if none is passed.
	if ( empty( $component ) ) {
		$component = profiles_current_component();
	}

	// Get the path for the current blog/site.
	$path = is_main_site()
		? profiles_core_get_site_path()
		: $current_blog->path;

	// Get the front page variables.
	$show_on_front = get_option( 'show_on_front' );
	$page_on_front = get_option( 'page_on_front' );

	if ( ( 'page' !== $show_on_front ) || empty( $component ) || empty( $profiles->pages->{$component} ) || ( $_SERVER['REQUEST_URI'] !== $path ) ) {
		return false;
	}

	/**
	 * Filters whether or not the specified Profiles component directory is set to be the front page.
	 *
	 * @since 1.5.0
	 *
	 * @param bool   $value     Whether or not the specified component directory is set as front page.
	 * @param string $component Current component being checked.
	 */
	return (bool) apply_filters( 'profiles_is_component_front_page', ( $profiles->pages->{$component}->id == $page_on_front ), $component );
}

/**
 * Is this a blog page, ie a non-BP page?
 *
 * You can tell if a page is displaying BP content by whether the
 * current_component has been defined.
 *
 * @since 1.0.0
 *
 * @return bool True if it's a non-BP page, false otherwise.
 */
function profiles_is_blog_page() {

	$is_blog_page = false;

	// Generally, we can just check to see that there's no current component.
	// The one exception is single user home tabs, where $profiles->current_component
	// is unset. Thus the addition of the profiles_is_user() check.
	if ( ! profiles_current_component() && ! profiles_is_user() ) {
		$is_blog_page = true;
	}

	/**
	 * Filters whether or not current page is a blog page or not.
	 *
	 * @since 1.5.0
	 *
	 * @param bool $is_blog_page Whether or not current page is a blog page.
	 */
	return (bool) apply_filters( 'profiles_is_blog_page', $is_blog_page );
}

/**
 * Is this a Profiles component?
 *
 * You can tell if a page is displaying BP content by whether the
 * current_component has been defined.
 *
 * Generally, we can just check to see that there's no current component.
 * The one exception is single user home tabs, where $profiles->current_component
 * is unset. Thus the addition of the profiles_is_user() check.
 *
 * @since 1.7.0
 *
 * @return bool True if it's a Profiles page, false otherwise.
 */
function is_profiles() {
	$retval = (bool) ( profiles_current_component() || profiles_is_user() );

	/**
	 * Filters whether or not this is a Profiles component.
	 *
	 * @since 1.7.0
	 *
	 * @param bool $retval Whether or not this is a Profiles component.
	 */
	return apply_filters( 'is_profiles', $retval );
}

/** Components ****************************************************************/

/**
 * Check whether a given component (or feature of a component) is active.
 *
 * @since 1.2.0 See r2539.
 * @since 2.3.0 Added $feature as a parameter.
 *
 * @param string $component The component name.
 * @param string $feature   The feature name.
 * @return bool
 */
function profiles_is_active( $component = '', $feature = '' ) {
	$retval = false;

	// Default to the current component if none is passed.
	if ( empty( $component ) ) {
		$component = profiles_current_component();
	}

	// Is component in either the active or required components arrays.
	if ( isset( profiles()->active_components[ $component ] ) || isset( profiles()->required_components[ $component ] ) ) {
		$retval = true;

		// Is feature active?
		if ( ! empty( $feature ) ) {
			// The xProfile component is specific.
			if ( 'xprofile' === $component ) {
				$component = 'profile';
			}

			if ( empty( profiles()->$component->features ) || false === in_array( $feature, profiles()->$component->features, true ) ) {
				$retval = false;
			}

			/**
			 * Filters whether or not a given feature for a component is active.
			 *
			 * This is a variable filter that is based on the component and feature
			 * that you are checking of active status of.
			 *
			 * @since 2.3.0
			 *
			 * @param bool $retval
			 */
			$retval = apply_filters( "profiles_is_{$component}_{$feature}_active", $retval );
		}
	}

	/**
	 * Filters whether or not a given component has been activated by the admin.
	 *
	 * @since 2.1.0
	 *
	 * @param bool   $retval    Whether or not a given component has been activated by the admin.
	 * @param string $component Current component being checked.
	 */
	return apply_filters( 'profiles_is_active', $retval, $component );
}

/**
 * Check whether the current page is part of the Members component.
 *
 * @since 1.5.0
 *
 * @return bool True if the current page is part of the Members component.
 */
function profiles_is_members_component() {
	return (bool) profiles_is_current_component( 'members' );
}

/**
 * Check whether the current page is part of the Profile component.
 *
 * @since 1.1.0
 *
 * @return bool True if the current page is part of the Profile component.
 */
function profiles_is_profile_component() {
	return (bool) profiles_is_current_component( 'xprofile' );
}

/**
 * Check whether the current page is part of the Activity component.
 *
 * @since 1.1.0
 *
 * @return bool True if the current page is part of the Activity component.
 */
function profiles_is_activity_component() {
	return (bool) profiles_is_current_component( 'activity' );
}

/**
 * Check whether the current page is part of the Blogs component.
 *
 * @since 1.1.0
 *
 * @return bool True if the current page is part of the Blogs component.
 */
function profiles_is_blogs_component() {
	return (bool) ( is_multisite() && profiles_is_current_component( 'blogs' ) );
}

/**
 * Check whether the current page is part of the Messages component.
 *
 * @since 1.1.0
 *
 * @return bool True if the current page is part of the Messages component.
 */
function profiles_is_messages_component() {
	return (bool) profiles_is_current_component( 'messages' );
}

/**
 * Check whether the current page is part of the Friends component.
 *
 * @since 1.1.0
 *
 * @return bool True if the current page is part of the Friends component.
 */
function profiles_is_friends_component() {
	return (bool) profiles_is_current_component( 'friends' );
}

/**
 * Check whether the current page is part of the Groups component.
 *
 * @since 1.1.0
 *
 * @return bool True if the current page is part of the Groups component.
 */
function profiles_is_groups_component() {
	return (bool) profiles_is_current_component( 'groups' );
}

/**
 * Check whether the current page is part of the Forums component.
 *
 * @since 1.5.0
 *
 * @return bool True if the current page is part of the Forums component.
 */
function profiles_is_forums_component() {
	return (bool) profiles_is_current_component( 'forums' );
}

/**
 * Check whether the current page is part of the Notifications component.
 *
 * @since 1.9.0
 *
 * @return bool True if the current page is part of the Notifications component.
 */
function profiles_is_notifications_component() {
	return (bool) profiles_is_current_component( 'notifications' );
}

/**
 * Check whether the current page is part of the Settings component.
 *
 * @since 1.1.0
 *
 * @return bool True if the current page is part of the Settings component.
 */
function profiles_is_settings_component() {
	return (bool) profiles_is_current_component( 'settings' );
}

/**
 * Is the current component an active core component?
 *
 * Use this function when you need to check if the current component is an
 * active core component of Profiles. If the current component is inactive, it
 * will return false. If the current component is not part of Profiles core,
 * it will return false. If the current component is active, and is part of
 * Profiles core, it will return true.
 *
 * @since 1.7.0
 *
 * @return bool True if the current component is active and is one of BP's
 *              packaged components.
 */
function profiles_is_current_component_core() {
	$retval = false;

	foreach ( profiles_core_get_packaged_component_ids() as $active_component ) {
		if ( profiles_is_current_component( $active_component ) ) {
			$retval = true;
			break;
		}
	}

	return $retval;
}

/** Activity ******************************************************************/

/**
 * Is the current page the activity directory?
 *
 * @since 2.0.0
 *
 * @return bool True if the current page is the activity directory.
 */
function profiles_is_activity_directory() {
	if ( ! profiles_displayed_user_id() && profiles_is_activity_component() && ! profiles_current_action() ) {
		return true;
	}

	return false;
}

/**
 * Is the current page a single activity item permalink?
 *
 * @since 1.5.0
 *
 * @return bool True if the current page is a single activity item permalink.
 */
function profiles_is_single_activity() {
	return (bool) ( profiles_is_activity_component() && is_numeric( profiles_current_action() ) );
}

/** User **********************************************************************/

/**
 * Is the current page the members directory?
 *
 * @since 2.0.0
 *
 * @return bool True if the current page is the members directory.
 */
function profiles_is_members_directory() {
	if ( ! profiles_is_user() && profiles_is_members_component() ) {
		return true;
	}

	return false;
}

/**
 * Is the current page part of the profile of the logged-in user?
 *
 * Will return true for any suprofilesage of the logged-in user's profile, eg
 * http://example.com/members/joe/friends/.
 *
 * @since 1.2.0
 *
 * @return bool True if the current page is part of the profile of the logged-in user.
 */
function profiles_is_my_profile() {
	if ( is_user_logged_in() && profiles_loggedin_user_id() == profiles_displayed_user_id() ) {
		$my_profile = true;
	} else {
		$my_profile = false;
	}

	/**
	 * Filters whether or not current page is part of the profile for the logged-in user.
	 *
	 * @since 1.2.4
	 *
	 * @param bool $my_profile Whether or not current page is part of the profile for the logged-in user.
	 */
	return apply_filters( 'profiles_is_my_profile', $my_profile );
}

/**
 * Is the current page a user page?
 *
 * Will return true anytime there is a displayed user.
 *
 * @since 1.5.0
 *
 * @return bool True if the current page is a user page.
 */
function profiles_is_user() {
	return (bool) profiles_displayed_user_id();
}

/**
 * Is the current page a user custom front page?
 *
 * Will return true anytime there is a custom front page for the displayed user.
 *
 * @since 2.6.0
 *
 * @return bool True if the current page is a user custom front page.
 */
function profiles_is_user_front() {
	return (bool) ( profiles_is_user() && profiles_is_current_component( 'front' ) );
}

/**
 * Is the current page a user's activity stream page?
 *
 * Eg http://example.com/members/joe/activity/ (or any suprofilesages thereof).
 *
 * @since 1.1.0
 *
 * @return bool True if the current page is a user's activity stream page.
 */
function profiles_is_user_activity() {
	return (bool) ( profiles_is_user() && profiles_is_activity_component() );
}

/**
 * Is the current page a user's Friends activity stream?
 *
 * Eg http://example.com/members/joe/friends/
 *
 * @since 1.1.0
 *
 * @return bool True if the current page is a user's Friends activity stream.
 */
function profiles_is_user_friends_activity() {

	if ( ! profiles_is_active( 'friends' ) ) {
		return false;
	}

	$slug = profiles_get_friends_slug();

	if ( empty( $slug ) ) {
		$slug = 'friends';
	}

	if ( profiles_is_user_activity() && profiles_is_current_action( $slug ) ) {
		return true;
	}

	return false;
}


/**
 * Is the current page part of a user's extended profile?
 *
 * Eg http://example.com/members/joe/profile/ (or a suprofilesage thereof).
 *
 * @since 1.1.0
 *
 * @return bool True if the current page is part of a user's extended profile.
 */
function profiles_is_user_profile() {
	return (bool) ( profiles_is_profile_component() || profiles_is_current_component( 'profile' ) );
}

/**
 * Is the current page part of a user's profile editing section?
 *
 * Eg http://example.com/members/joe/profile/edit/ (or a suprofilesage thereof).
 *
 * @since 1.5.0
 *
 * @return bool True if the current page is a user's profile edit page.
 */
function profiles_is_user_profile_edit() {
	return (bool) ( profiles_is_profile_component() && profiles_is_current_action( 'edit' ) );
}

/**
 * Is the current page part of a user's profile avatar editing section?
 *
 * Eg http://example.com/members/joe/profile/change-avatar/ (or a suprofilesage thereof).
 *
 * @since 1.5.0
 *
 * @return bool True if the current page is the user's avatar edit page.
 */
function profiles_is_user_change_avatar() {
	return (bool) ( profiles_is_profile_component() && profiles_is_current_action( 'change-avatar' ) );
}

/**
 * Is the current page the a user's change cover image profile page?
 *
 * Eg http://example.com/members/joe/profile/change-cover-image/ (or a suprofilesage thereof).
 *
 * @since 2.4.0
 *
 * @return bool True if the current page is a user's profile edit cover image page.
 */
function profiles_is_user_change_cover_image() {
	return (bool) ( profiles_is_profile_component() && profiles_is_current_action( 'change-cover-image' ) );
}

/**
 * Is this a user's forums page?
 *
 * Eg http://example.com/members/joe/forums/ (or a suprofilesage thereof).
 *
 * @since 1.5.0
 *
 * @return bool True if the current page is a user's forums page.
 */
function profiles_is_user_forums() {

	if ( ! profiles_is_active( 'forums' ) ) {
		return false;
	}

	if ( profiles_is_user() && profiles_is_forums_component() ) {
		return true;
	}

	return false;
}

/**
 * Is this a user's "Topics Started" page?
 *
 * Eg http://example.com/members/joe/forums/topics/.
 *
 * @since 1.5.0
 *
 * @return bool True if the current page is a user's Topics Started page.
 */
function profiles_is_user_forums_started() {
	return (bool) ( profiles_is_user_forums() && profiles_is_current_action( 'topics' ) );
}

/**
 * Is this a user's "Replied To" page?
 *
 * Eg http://example.com/members/joe/forums/replies/.
 *
 * @since 1.5.0
 *
 * @return bool True if the current page is a user's Replied To forums page.
 */
function profiles_is_user_forums_replied_to() {
	return (bool) ( profiles_is_user_forums() && profiles_is_current_action( 'replies' ) );
}

/**
 * Is the current page part of a user's Groups page?
 *
 * Eg http://example.com/members/joe/groups/ (or a suprofilesage thereof).
 *
 * @since 1.1.0
 *
 * @return bool True if the current page is a user's Groups page.
 */
function profiles_is_user_groups() {
	return (bool) ( profiles_is_user() && profiles_is_groups_component() );
}

/**
 * Is the current page part of a user's Blogs page?
 *
 * Eg http://example.com/members/joe/blogs/ (or a suprofilesage thereof).
 *
 * @since 1.1.0
 *
 * @return bool True if the current page is a user's Blogs page.
 */
function profiles_is_user_blogs() {
	return (bool) ( profiles_is_user() && profiles_is_blogs_component() );
}

/**
 * Is the current page a user's Recent Blog Posts page?
 *
 * Eg http://example.com/members/joe/blogs/recent-posts/.
 *
 * @since 1.1.0
 *
 * @return bool True if the current page is a user's Recent Blog Posts page.
 */
function profiles_is_user_recent_posts() {
	return (bool) ( profiles_is_user_blogs() && profiles_is_current_action( 'recent-posts' ) );
}

/**
 * Is the current page a user's Recent Blog Comments page?
 *
 * Eg http://example.com/members/joe/blogs/recent-comments/.
 *
 * @since 1.1.0
 *
 * @return bool True if the current page is a user's Recent Blog Comments page.
 */
function profiles_is_user_recent_commments() {
	return (bool) ( profiles_is_user_blogs() && profiles_is_current_action( 'recent-comments' ) );
}

/**
 * Is the current page a user's Friends page?
 *
 * Eg http://example.com/members/joe/blogs/friends/ (or a suprofilesage thereof).
 *
 * @since 1.1.0
 *
 * @return bool True if the current page is a user's Friends page.
 */
function profiles_is_user_friends() {
	return (bool) ( profiles_is_user() && profiles_is_friends_component() );
}

/**
 * Is the current page a user's Friend Requests page?
 *
 * Eg http://example.com/members/joe/friends/requests/.
 *
 * @since 1.5.0
 *
 * @return bool True if the current page is a user's Friends Requests page.
 */
function profiles_is_user_friend_requests() {
	return (bool) ( profiles_is_user_friends() && profiles_is_current_action( 'requests' ) );
}

/**
 * Is this a user's notifications page?
 *
 * Eg http://example.com/members/joe/notifications/ (or a suprofilesage thereof).
 *
 * @since 1.9.0
 *
 * @return bool True if the current page is a user's Notifications page.
 */
function profiles_is_user_notifications() {
	return (bool) ( profiles_is_user() && profiles_is_notifications_component() );
}

/**
 * Is this a user's settings page?
 *
 * Eg http://example.com/members/joe/settings/ (or a suprofilesage thereof).
 *
 * @since 1.5.0
 *
 * @return bool True if the current page is a user's Settings page.
 */
function profiles_is_user_settings() {
	return (bool) ( profiles_is_user() && profiles_is_settings_component() );
}

/**
 * Is this a user's General Settings page?
 *
 * Eg http://example.com/members/joe/settings/general/.
 *
 * @since 1.5.0
 *
 * @return bool True if the current page is a user's General Settings page.
 */
function profiles_is_user_settings_general() {
	return (bool) ( profiles_is_user_settings() && profiles_is_current_action( 'general' ) );
}

/**
 * Is this a user's Notification Settings page?
 *
 * Eg http://example.com/members/joe/settings/notifications/.
 *
 * @since 1.5.0
 *
 * @return bool True if the current page is a user's Notification Settings page.
 */
function profiles_is_user_settings_notifications() {
	return (bool) ( profiles_is_user_settings() && profiles_is_current_action( 'notifications' ) );
}

/**
 * Is this a user's Account Deletion page?
 *
 * Eg http://example.com/members/joe/settings/delete-account/.
 *
 * @since 1.5.0
 *
 * @return bool True if the current page is a user's Delete Account page.
 */
function profiles_is_user_settings_account_delete() {
	return (bool) ( profiles_is_user_settings() && profiles_is_current_action( 'delete-account' ) );
}

/**
 * Is this a user's profile settings?
 *
 * Eg http://example.com/members/joe/settings/profile/.
 *
 * @since 2.0.0
 *
 * @return bool True if the current page is a user's Profile Settings page.
 */
function profiles_is_user_settings_profile() {
	return (bool) ( profiles_is_user_settings() && profiles_is_current_action( 'profile' ) );
}

/** Groups ********************************************************************/

/**
 * Is the current page the groups directory?
 *
 * @since 2.0.0
 *
 * @return True if the current page is the groups directory.
 */
function profiles_is_groups_directory() {
	if ( profiles_is_groups_component() && ! profiles_current_action() && ! profiles_current_item() ) {
		return true;
	}

	return false;
}

/**
 * Does the current page belong to a single group?
 *
 * Will return true for any suprofilesage of a single group.
 *
 * @since 1.2.0
 *
 * @return bool True if the current page is part of a single group.
 */
function profiles_is_group() {
	$retval = profiles_is_active( 'groups' );

	if ( ! empty( $retval ) ) {
		$retval = profiles_is_groups_component() && groups_get_current_group();
	}

	return (bool) $retval;
}

/**
 * Is the current page a single group's home page?
 *
 * URL will vary depending on which group tab is set to be the "home". By
 * default, it's the group's recent activity.
 *
 * @since 1.1.0
 *
 * @return bool True if the current page is a single group's home page.
 */
function profiles_is_group_home() {
	if ( profiles_is_single_item() && profiles_is_groups_component() && ( ! profiles_current_action() || profiles_is_current_action( 'home' ) ) ) {
		return true;
	}

	return false;
}

/**
 * Is the current page part of the group creation process?
 *
 * @since 1.1.0
 *
 * @return bool True if the current page is part of the group creation process.
 */
function profiles_is_group_create() {
	return (bool) ( profiles_is_groups_component() && profiles_is_current_action( 'create' ) );
}

/**
 * Is the current page part of a single group's admin screens?
 *
 * Eg http://example.com/groups/mygroup/admin/settings/.
 *
 * @since 1.1.0
 *
 * @return bool True if the current page is part of a single group's admin.
 */
function profiles_is_group_admin_page() {
	return (bool) ( profiles_is_single_item() && profiles_is_groups_component() && profiles_is_current_action( 'admin' ) );
}

/**
 * Is the current page a group's forum page?
 *
 * Only applies to legacy bbPress forums.
 *
 * @since 1.1.0
 *
 * @return bool True if the current page is a group forum page.
 */
function profiles_is_group_forum() {
	$retval = false;

	// At a forum URL.
	if ( profiles_is_single_item() && profiles_is_groups_component() && profiles_is_current_action( 'forum' ) ) {
		$retval = true;

		// If at a forum URL, set back to false if forums are inactive, or not
		// installed correctly.
		if ( ! profiles_is_active( 'forums' ) || ! profiles_forums_is_installed_correctly() ) {
			$retval = false;
		}
	}

	return $retval;
}

/**
 * Is the current page a group's activity page?
 *
 * @since 1.2.1
 *
 * @return True if the current page is a group's activity page.
 */
function profiles_is_group_activity() {
	$retval = false;

	if ( profiles_is_single_item() && profiles_is_groups_component() && profiles_is_current_action( 'activity' ) ) {
		$retval = true;
	}

	return $retval;
}

/**
 * Is the current page a group forum topic?
 *
 * Only applies to legacy bbPress (1.x) forums.
 *
 * @since 1.1.0
 *
 * @return bool True if the current page is part of a group forum topic.
 */
function profiles_is_group_forum_topic() {
	return (bool) ( profiles_is_single_item() && profiles_is_groups_component() && profiles_is_current_action( 'forum' ) && profiles_is_action_variable( 'topic', 0 ) );
}

/**
 * Is the current page a group forum topic edit page?
 *
 * Only applies to legacy bbPress (1.x) forums.
 *
 * @since 1.2.0
 *
 * @return bool True if the current page is part of a group forum topic edit page.
 */
function profiles_is_group_forum_topic_edit() {
	return (bool) ( profiles_is_single_item() && profiles_is_groups_component() && profiles_is_current_action( 'forum' ) && profiles_is_action_variable( 'topic', 0 ) && profiles_is_action_variable( 'edit', 2 ) );
}

/**
 * Is the current page a group's Members page?
 *
 * Eg http://example.com/groups/mygroup/members/.
 *
 * @since 1.1.0
 *
 * @return bool True if the current page is part of a group's Members page.
 */
function profiles_is_group_members() {
	$retval = false;

	if ( profiles_is_single_item() && profiles_is_groups_component() && profiles_is_current_action( 'members' ) ) {
		$retval = true;
	}

	return $retval;
}

/**
 * Is the current page a group's Invites page?
 *
 * Eg http://example.com/groups/mygroup/send-invites/.
 *
 * @since 1.1.0
 *
 * @return bool True if the current page is a group's Send Invites page.
 */
function profiles_is_group_invites() {
	return (bool) ( profiles_is_groups_component() && profiles_is_current_action( 'send-invites' ) );
}

/**
 * Is the current page a group's Request Membership page?
 *
 * Eg http://example.com/groups/mygroup/request-membership/.
 *
 * @since 1.2.0
 *
 * @return bool True if the current page is a group's Request Membership page.
 */
function profiles_is_group_membership_request() {
	return (bool) ( profiles_is_groups_component() && profiles_is_current_action( 'request-membership' ) );
}

/**
 * Is the current page a leave group attempt?
 *
 * @since 1.1.0
 *
 * @return bool True if the current page is a Leave Group attempt.
 */
function profiles_is_group_leave() {
	return (bool) ( profiles_is_groups_component() && profiles_is_single_item() && profiles_is_current_action( 'leave-group' ) );
}

/**
 * Is the current page part of a single group?
 *
 * Not currently used by Profiles.
 *
 * @todo How is this functionally different from profiles_is_group()?
 *
 * @return bool True if the current page is part of a single group.
 */
function profiles_is_group_single() {
	return (bool) ( profiles_is_groups_component() && profiles_is_single_item() );
}

/**
 * Is the current group page a custom front?
 *
 * @since 2.4.0
 *
 * @return bool True if the current group page is a custom front.
 */
function profiles_is_group_custom_front() {
	$profiles = profiles();
	return (bool) profiles_is_group_home() && ! empty( $profiles->groups->current_group->front_template );
}

/**
 * Is the current page the Create a Blog page?
 *
 * Eg http://example.com/sites/create/.
 *
 * @since 1.1.0
 *
 * @return bool True if the current page is the Create a Blog page.
 */
function profiles_is_create_blog() {
	return (bool) ( profiles_is_blogs_component() && profiles_is_current_action( 'create' ) );
}

/**
 * Is the current page the blogs directory ?
 *
 * @since 2.0.0
 *
 * @return True if the current page is the blogs directory.
 */
function profiles_is_blogs_directory() {
	if ( is_multisite() && profiles_is_blogs_component() && ! profiles_current_action() ) {
		return true;
	}

	return false;
}

/** Messages ******************************************************************/

/**
 * Is the current page part of a user's Messages pages?
 *
 * Eg http://example.com/members/joe/messages/ (or a suprofilesage thereof).
 *
 * @since 1.2.0
 *
 * @return bool True if the current page is part of a user's Messages pages.
 */
function profiles_is_user_messages() {
	return (bool) ( profiles_is_user() && profiles_is_messages_component() );
}

/**
 * Is the current page a user's Messages Inbox?
 *
 * Eg http://example.com/members/joe/messages/inbox/.
 *
 * @since 1.1.0
 *
 * @return bool True if the current page is a user's Messages Inbox.
 */
function profiles_is_messages_inbox() {
	if ( profiles_is_user_messages() && ( ! profiles_current_action() || profiles_is_current_action( 'inbox' ) ) ) {
		return true;
	}

	return false;
}

/**
 * Is the current page a user's Messages Sentbox?
 *
 * Eg http://example.com/members/joe/messages/sentbox/.
 *
 * @since 1.1.0
 *
 * @return bool True if the current page is a user's Messages Sentbox.
 */
function profiles_is_messages_sentbox() {
	return (bool) ( profiles_is_user_messages() && profiles_is_current_action( 'sentbox' ) );
}

/**
 * Is the current page a user's Messages Compose screen??
 *
 * Eg http://example.com/members/joe/messages/compose/.
 *
 * @since 1.1.0
 *
 * @return bool True if the current page is a user's Messages Compose screen.
 */
function profiles_is_messages_compose_screen() {
	return (bool) ( profiles_is_user_messages() && profiles_is_current_action( 'compose' ) );
}

/**
 * Is the current page the Notices screen?
 *
 * Eg http://example.com/members/joe/messages/notices/.
 *
 * @since 1.1.0
 *
 * @return bool True if the current page is the Notices screen.
 */
function profiles_is_notices() {
	return (bool) ( profiles_is_user_messages() && profiles_is_current_action( 'notices' ) );
}

/**
 * Is the current page a single Messages conversation thread?
 *
 * @since 1.6.0
 *
 * @return bool True if the current page a single Messages conversation thread?
 */
function profiles_is_messages_conversation() {
	return (bool) ( profiles_is_user_messages() && ( profiles_is_current_action( 'view' ) ) );
}

/**
 * Not currently used by Profiles.
 *
 * @param string $component Current component to check for.
 * @param string $callback  Callback to invoke.
 * @return bool
 */
function profiles_is_single( $component, $callback ) {
	return (bool) ( profiles_is_current_component( $component ) && ( true === call_user_func( $callback ) ) );
}

/** Registration **************************************************************/

/**
 * Is the current page the Activate page?
 *
 * Eg http://example.com/activate/.
 *
 * @since 1.1.0
 *
 * @return bool True if the current page is the Activate page.
 */
function profiles_is_activation_page() {
	return (bool) profiles_is_current_component( 'activate' );
}

/**
 * Is the current page the Register page?
 *
 * Eg http://example.com/register/.
 *
 * @since 1.1.0
 *
 * @return bool True if the current page is the Register page.
 */
function profiles_is_register_page() {
	return (bool) profiles_is_current_component( 'register' );
}

/**
 * Get the title parts of the Profiles displayed page
 *
 * @since 2.4.3
 *
 * @param string $seplocation Location for the separator.
 * @return array the title parts
 */
function profiles_get_title_parts( $seplocation = 'right' ) {
	$profiles = profiles();

	// Defaults to an empty array.
	$profiles_title_parts = array();

	// If this is not a BP page, return the empty array.
	if ( profiles_is_blog_page() ) {
		return $profiles_title_parts;
	}

	// If this is a 404, return the empty array.
	if ( is_404() ) {
		return $profiles_title_parts;
	}

	// If this is the front page of the site, return the empty array.
	if ( is_front_page() || is_home() ) {
		return $profiles_title_parts;
	}

	// Return the empty array if not a Profiles page.
	if ( ! is_profiles() ) {
		return $profiles_title_parts;
	}

	// Now we can build the BP Title Parts
	// Is there a displayed user, and do they have a name?
	$displayed_user_name = profiles_get_displayed_user_fullname();

	// Displayed user.
	if ( ! empty( $displayed_user_name ) && ! is_404() ) {

		// Get the component's ID to try and get its name.
		$component_id = $component_name = profiles_current_component();

		// Set empty subnav name.
		$component_subnav_name = '';

		if ( ! empty( $profiles->members->nav ) ) {
			$primary_nav_item = $profiles->members->nav->get_primary( array( 'slug' => $component_id ), false );
			$primary_nav_item = reset( $primary_nav_item );
		}

		// Use the component nav name.
		if ( ! empty( $primary_nav_item->name ) ) {
			$component_name = _profiles_strip_spans_from_title( $primary_nav_item->name );

		// Fall back on the component ID.
		} elseif ( ! empty( $profiles->{$component_id}->id ) ) {
			$component_name = ucwords( $profiles->{$component_id}->id );
		}

		if ( ! empty( $profiles->members->nav ) ) {
			$secondary_nav_item = $profiles->members->nav->get_secondary( array(
				'parent_slug' => $component_id,
				'slug'        => profiles_current_action()
			), false );

			if ( $secondary_nav_item ) {
				$secondary_nav_item = reset( $secondary_nav_item );
			}
		}

		// Append action name if we're on a member component sub-page.
		if ( ! empty( $secondary_nav_item->name ) && ! empty( $profiles->canonical_stack['action'] ) ) {
			$component_subnav_name = $secondary_nav_item->name;
		}

		// If on the user profile's landing page, just use the fullname.
		if ( profiles_is_current_component( $profiles->default_component ) && ( profiles_get_requested_url() === profiles_displayed_user_domain() ) ) {
			$profiles_title_parts[] = $displayed_user_name;

		// Use component name on member pages.
		} else {
			$profiles_title_parts = array_merge( $profiles_title_parts, array_map( 'strip_tags', array(
				$displayed_user_name,
				$component_name,
			) ) );

			// If we have a subnav name, add it separately for localization.
			if ( ! empty( $component_subnav_name ) ) {
				$profiles_title_parts[] = strip_tags( $component_subnav_name );
			}
		}

	// A single item from a component other than Members.
	} elseif ( profiles_is_single_item() ) {
		$component_id = profiles_current_component();

		if ( ! empty( $profiles->{$component_id}->nav ) ) {
			$secondary_nav_item = $profiles->{$component_id}->nav->get_secondary( array(
				'parent_slug' => profiles_current_item(),
				'slug'        => profiles_current_action()
			), false );

			if ( $secondary_nav_item ) {
				$secondary_nav_item = reset( $secondary_nav_item );
			}
		}

		$single_item_subnav = '';

		if ( ! empty( $secondary_nav_item->name ) ) {
			$single_item_subnav = $secondary_nav_item->name;
		}

		$profiles_title_parts = array( $profiles->profiles_options_title, $single_item_subnav );

	// An index or directory.
	} elseif ( profiles_is_directory() ) {
		$current_component = profiles_current_component();

		// No current component (when does this happen?).
		$profiles_title_parts = array( _x( 'Directory', 'component directory title', 'profiles' ) );

		if ( ! empty( $current_component ) ) {
			$profiles_title_parts = array( profiles_get_directory_title( $current_component ) );
		}

	}

	// Strip spans.
	$profiles_title_parts = array_map( '_profiles_strip_spans_from_title', $profiles_title_parts );

	// Sep on right, so reverse the order.
	if ( 'right' === $seplocation ) {
		$profiles_title_parts = array_reverse( $profiles_title_parts );
	}

	/**
	 * Filter Profiles title parts before joining.
	 *
	 * @since 2.4.3
	 *
	 * @param array $profiles_title_parts Current Profiles title parts.
	 * @return array
	 */
	return (array) apply_filters( 'profiles_get_title_parts', $profiles_title_parts );
}

/**
 * Customize the body class, according to the currently displayed BP content.
 *
 * @since 1.1.0
 */
function profiles_the_body_class() {
	echo profiles_get_the_body_class();
}
	/**
	 * Customize the body class, according to the currently displayed BP content.
	 *
	 * Uses the above is_() functions to output a body class for each scenario.
	 *
	 * @since 1.1.0
	 *
	 * @param array      $wp_classes     The body classes coming from WP.
	 * @param array|bool $custom_classes Classes that were passed to get_body_class().
	 * @return array $classes The BP-adjusted body classes.
	 */
	function profiles_get_the_body_class( $wp_classes = array(), $custom_classes = false ) {

		$profiles_classes = array();

		/* Pages *************************************************************/

		if ( is_front_page() ) {
			$profiles_classes[] = 'home-page';
		}

		if ( profiles_is_directory() ) {
			$profiles_classes[] = 'directory';
		}

		if ( profiles_is_single_item() ) {
			$profiles_classes[] = 'single-item';
		}

		/* Components ********************************************************/

		if ( ! profiles_is_blog_page() ) {
			if ( profiles_is_user_profile() )  {
				$profiles_classes[] = 'xprofile';
			}

			if ( profiles_is_activity_component() ) {
				$profiles_classes[] = 'activity';
			}

			if ( profiles_is_blogs_component() ) {
				$profiles_classes[] = 'blogs';
			}

			if ( profiles_is_messages_component() ) {
				$profiles_classes[] = 'messages';
			}

			if ( profiles_is_friends_component() ) {
				$profiles_classes[] = 'friends';
			}

			if ( profiles_is_groups_component() ) {
				$profiles_classes[] = 'groups';
			}

			if ( profiles_is_settings_component()  ) {
				$profiles_classes[] = 'settings';
			}
		}

		/* User **************************************************************/

		if ( profiles_is_user() ) {
			$profiles_classes[] = 'profiles-user';
		}

		if ( ! profiles_is_directory() ) {
			if ( profiles_is_user_blogs() ) {
				$profiles_classes[] = 'my-blogs';
			}

			if ( profiles_is_user_groups() ) {
				$profiles_classes[] = 'my-groups';
			}

			if ( profiles_is_user_activity() ) {
				$profiles_classes[] = 'my-activity';
			}
		}

		if ( profiles_is_my_profile() ) {
			$profiles_classes[] = 'my-account';
		}

		if ( profiles_is_user_profile() ) {
			$profiles_classes[] = 'my-profile';
		}

		if ( profiles_is_user_friends() ) {
			$profiles_classes[] = 'my-friends';
		}

		if ( profiles_is_user_messages() ) {
			$profiles_classes[] = 'my-messages';
		}

		if ( profiles_is_user_recent_commments() ) {
			$profiles_classes[] = 'recent-comments';
		}

		if ( profiles_is_user_recent_posts() ) {
			$profiles_classes[] = 'recent-posts';
		}

		if ( profiles_is_user_change_avatar() ) {
			$profiles_classes[] = 'change-avatar';
		}

		if ( profiles_is_user_profile_edit() ) {
			$profiles_classes[] = 'profile-edit';
		}

		

		/* Messages **********************************************************/

		if ( profiles_is_messages_inbox() ) {
			$profiles_classes[] = 'inbox';
		}

		if ( profiles_is_messages_sentbox() ) {
			$profiles_classes[] = 'sentbox';
		}

		if ( profiles_is_messages_compose_screen() ) {
			$profiles_classes[] = 'compose';
		}

		if ( profiles_is_notices() ) {
			$profiles_classes[] = 'notices';
		}

		if ( profiles_is_user_friend_requests() ) {
			$profiles_classes[] = 'friend-requests';
		}

		if ( profiles_is_create_blog() ) {
			$profiles_classes[] = 'create-blog';
		}

		/* Registration ******************************************************/

		if ( profiles_is_register_page() ) {
			$profiles_classes[] = 'registration';
		}

		if ( profiles_is_activation_page() ) {
			$profiles_classes[] = 'activation';
		}

		/* Current Component & Action ****************************************/

		if ( ! profiles_is_blog_page() ) {
			$profiles_classes[] = profiles_current_component();
			$profiles_classes[] = profiles_current_action();
		}

		/* Clean up ***********************************************************/

		// Add Profiles class if we are within a Profiles page.
		if ( ! profiles_is_blog_page() ) {
			$profiles_classes[] = 'profiles';
		}

		// Merge WP classes with Profiles classes and remove any duplicates.
		$classes = array_unique( array_merge( (array) $profiles_classes, (array) $wp_classes ) );

		/**
		 * Filters the Profiles classes to be added to body_class()
		 *
		 * @since 1.1.0
		 *
		 * @param array $classes        Array of body classes to add.
		 * @param array $profiles_classes     Array of Profiles-based classes.
		 * @param array $wp_classes     Array of WordPress-based classes.
		 * @param array $custom_classes Array of classes that were passed to get_body_class().
		 */
		return apply_filters( 'profiles_get_the_body_class', $classes, $profiles_classes, $wp_classes, $custom_classes );
	}
	add_filter( 'body_class', 'profiles_get_the_body_class', 10, 2 );

/**
 * Customizes the post CSS class according to Profiles content.
 *
 * Hooked to the 'post_class' filter.
 *
 * @since 2.1.0
 *
 * @param array $wp_classes The post classes coming from WordPress.
 * @return array
 */
function profiles_get_the_post_class( $wp_classes = array() ) {
	// Don't do anything if we're not on a BP page.
	if ( ! is_profiles() ) {
		return $wp_classes;
	}

	$profiles_classes = array();

	if ( profiles_is_user() || profiles_is_single_activity() ) {
		$profiles_classes[] = 'profiles_members';

	} elseif ( profiles_is_group() ) {
		$profiles_classes[] = 'profiles_group';

	} elseif ( profiles_is_activity_component() ) {
		$profiles_classes[] = 'profiles_activity';

	} elseif ( profiles_is_blogs_component() ) {
		$profiles_classes[] = 'profiles_blogs';

	} elseif ( profiles_is_register_page() ) {
		$profiles_classes[] = 'profiles_register';

	} elseif ( profiles_is_activation_page() ) {
		$profiles_classes[] = 'profiles_activate';

	} elseif ( profiles_is_forums_component() && profiles_is_directory() ) {
		$profiles_classes[] = 'profiles_forum';
	}

	if ( empty( $profiles_classes ) ) {
		return $wp_classes;
	}

	// Emulate post type css class.
	foreach ( $profiles_classes as $profiles_class ) {
		$profiles_classes[] = "type-{$profiles_class}";
	}

	// Okay let's merge!
	return array_unique( array_merge( $profiles_classes, $wp_classes ) );
}
add_filter( 'post_class', 'profiles_get_the_post_class' );

/**
 * Sort Profiles nav menu items by their position property.
 *
 * This is an internal convenience function and it will probably be removed in
 * a later release. Do not use.
 *
 * @access private
 * @since 1.7.0
 *
 * @param array $a First item.
 * @param array $b Second item.
 * @return int Returns an integer less than, equal to, or greater than zero if
 *             the first argument is considered to be respectively less than,
 *             equal to, or greater than the second.
 */
function _profiles_nav_menu_sort( $a, $b ) {
	if ( $a['position'] == $b['position'] ) {
		return 0;
	} elseif ( $a['position'] < $b['position'] ) {
		return -1;
	} else {
		return 1;
	}
}

/**
 * Get the items registered in the primary and secondary Profiles navigation menus.
 *
 * @since 1.7.0
 * @since 2.6.0 Introduced the `$component` parameter.
 *
 * @param string $component Optional. Component whose nav items are being fetched.
 * @return array A multidimensional array of all navigation items.
 */
function profiles_get_nav_menu_items( $component = 'members' ) {
	$profiles    = profiles();
	$menus = array();

	if ( ! isset( $profiles->{$component}->nav ) ) {
		return $menus;
	}

	// Get the item nav and build the menus.
	foreach ( $profiles->{$component}->nav->get_item_nav() as $nav_menu ) {
		// Get the correct menu link. See https://profiles.trac.wordpress.org/ticket/4624.
		$link = profiles_loggedin_user_domain() ? str_replace( profiles_loggedin_user_domain(), profiles_displayed_user_domain(), $nav_menu->link ) : trailingslashit( profiles_displayed_user_domain() . $nav_menu->link );

		// Add this menu.
		$menu         = new stdClass;
		$menu->class  = array( 'menu-parent' );
		$menu->css_id = $nav_menu->css_id;
		$menu->link   = $link;
		$menu->name   = $nav_menu->name;
		$menu->parent = 0;

		if ( ! empty( $nav_menu->children ) ) {
			$submenus = array();

			foreach( $nav_menu->children as $sub_menu ) {
				$submenu = new stdClass;
				$submenu->class  = array( 'menu-child' );
				$submenu->css_id = $sub_menu->css_id;
				$submenu->link   = $sub_menu->link;
				$submenu->name   = $sub_menu->name;
				$submenu->parent = $nav_menu->slug;

				// If we're viewing this item's screen, record that we need to mark its parent menu to be selected.
				if ( $sub_menu->slug == profiles_current_action() ) {
					$menu->class[]    = 'current-menu-parent';
					$submenu->class[] = 'current-menu-item';
				}

				$submenus[] = $submenu;
			}
		}

		$menus[] = $menu;

		if ( ! empty( $submenus ) ) {
			$menus = array_merge( $menus, $submenus );
		}
	}

	/**
	 * Filters the items registered in the primary and secondary Profiles navigation menus.
	 *
	 * @since 1.7.0
	 *
	 * @param array $menus Array of items registered in the primary and secondary Profiles navigation.
	 */
	return apply_filters( 'profiles_get_nav_menu_items', $menus );
}

/**
 * Display a navigation menu.
 *
 * @since 1.7.0
 *
 * @param string|array $args {
 *     An array of optional arguments.
 *
 *     @type string $after           Text after the link text. Default: ''.
 *     @type string $before          Text before the link text. Default: ''.
 *     @type string $container       The name of the element to wrap the navigation
 *                                   with. 'div' or 'nav'. Default: 'div'.
 *     @type string $container_class The class that is applied to the container.
 *                                   Default: 'menu-profiles-container'.
 *     @type string $container_id    The ID that is applied to the container.
 *                                   Default: ''.
 *     @type int    $depth           How many levels of the hierarchy are to be included.
 *                                   0 means all. Default: 0.
 *     @type bool   $echo            True to echo the menu, false to return it.
 *                                   Default: true.
 *     @type bool   $fallback_cb     If the menu doesn't exist, should a callback
 *                                   function be fired? Default: false (no fallback).
 *     @type string $items_wrap      How the list items should be wrapped. Should be
 *                                   in the form of a printf()-friendly string, using numbered
 *                                   placeholders. Default: '<ul id="%1$s" class="%2$s">%3$s</ul>'.
 *     @type string $link_after      Text after the link. Default: ''.
 *     @type string $link_before     Text before the link. Default: ''.
 *     @type string $menu_class      CSS class to use for the <ul> element which
 *                                   forms the menu. Default: 'menu'.
 *     @type string $menu_id         The ID that is applied to the <ul> element which
 *                                   forms the menu. Default: 'menu-profiles', incremented.
 *     @type string $walker          Allows a custom walker class to be specified.
 *                                   Default: 'Profiles_Walker_Nav_Menu'.
 * }
 * @return string|null If $echo is false, returns a string containing the nav
 *                     menu markup.
 */
function profiles_nav_menu( $args = array() ) {
	static $menu_id_slugs = array();

	$defaults = array(
		'after'           => '',
		'before'          => '',
		'container'       => 'div',
		'container_class' => '',
		'container_id'    => '',
		'depth'           => 0,
		'echo'            => true,
		'fallback_cb'     => false,
		'items_wrap'      => '<ul id="%1$s" class="%2$s">%3$s</ul>',
		'link_after'      => '',
		'link_before'     => '',
		'menu_class'      => 'menu',
		'menu_id'         => '',
		'walker'          => '',
	);
	$args = wp_parse_args( $args, $defaults );

	/**
	 * Filters the parsed profiles_nav_menu arguments.
	 *
	 * @since 1.7.0
	 *
	 * @param array $args Array of parsed arguments.
	 */
	$args = apply_filters( 'profiles_nav_menu_args', $args );
	$args = (object) $args;

	$items = $nav_menu = '';
	$show_container = false;

	// Create custom walker if one wasn't set.
	if ( empty( $args->walker ) ) {
		$args->walker = new Profiles_Walker_Nav_Menu;
	}

	// Sanitise values for class and ID.
	$args->container_class = sanitize_html_class( $args->container_class );
	$args->container_id    = sanitize_html_class( $args->container_id );

	// Whether to wrap the ul, and what to wrap it with.
	if ( $args->container ) {

		/**
		 * Filters the allowed tags for the wp_nav_menu_container.
		 *
		 * @since 1.7.0
		 *
		 * @param array $value Array of allowed tags. Default 'div' and 'nav'.
		 */
		$allowed_tags = apply_filters( 'wp_nav_menu_container_allowedtags', array( 'div', 'nav', ) );

		if ( in_array( $args->container, $allowed_tags ) ) {
			$show_container = true;

			$class     = $args->container_class ? ' class="' . esc_attr( $args->container_class ) . '"' : ' class="menu-profiles-container"';
			$id        = $args->container_id    ? ' id="' . esc_attr( $args->container_id ) . '"'       : '';
			$nav_menu .= '<' . $args->container . $id . $class . '>';
		}
	}

	/**
	 * Filters the Profiles menu objects.
	 *
	 * @since 1.7.0
	 *
	 * @param array $value Array of nav menu objects.
	 * @param array $args  Array of arguments for the menu.
	 */
	$menu_items = apply_filters( 'profiles_nav_menu_objects', profiles_get_nav_menu_items(), $args );
	$items      = walk_nav_menu_tree( $menu_items, $args->depth, $args );
	unset( $menu_items );

	// Set the ID that is applied to the ul element which forms the menu.
	if ( ! empty( $args->menu_id ) ) {
		$wrap_id = $args->menu_id;

	} else {
		$wrap_id = 'menu-profiles';

		// If a specific ID wasn't requested, and there are multiple menus on the same screen, make sure the autogenerated ID is unique.
		while ( in_array( $wrap_id, $menu_id_slugs ) ) {
			if ( preg_match( '#-(\d+)$#', $wrap_id, $matches ) ) {
				$wrap_id = preg_replace('#-(\d+)$#', '-' . ++$matches[1], $wrap_id );
			} else {
				$wrap_id = $wrap_id . '-1';
			}
		}
	}
	$menu_id_slugs[] = $wrap_id;

	/**
	 * Filters the Profiles menu items.
	 *
	 * Allow plugins to hook into the menu to add their own <li>'s
	 *
	 * @since 1.7.0
	 *
	 * @param array $items Array of nav menu items.
	 * @param array $args  Array of arguments for the menu.
	 */
	$items = apply_filters( 'profiles_nav_menu_items', $items, $args );

	// Build the output.
	$wrap_class  = $args->menu_class ? $args->menu_class : '';
	$nav_menu   .= sprintf( $args->items_wrap, esc_attr( $wrap_id ), esc_attr( $wrap_class ), $items );
	unset( $items );

	// If we've wrapped the ul, close it.
	if ( ! empty( $show_container ) ) {
		$nav_menu .= '</' . $args->container . '>';
	}

	/**
	 * Filters the final Profiles menu output.
	 *
	 * @since 1.7.0
	 *
	 * @param string $nav_menu Final nav menu output.
	 * @param array  $args     Array of arguments for the menu.
	 */
	$nav_menu = apply_filters( 'profiles_nav_menu', $nav_menu, $args );

	if ( ! empty( $args->echo ) ) {
		echo $nav_menu;
	} else {
		return $nav_menu;
	}
}

/**
 * Prints the Recipient Salutation.
 *
 * @since 2.5.0
 *
 * @param array $settings Email Settings.
 */
function profiles_email_the_salutation( $settings = array() ) {
	echo profiles_email_get_salutation( $settings );
}

	/**
	 * Gets the Recipient Salutation.
	 *
	 * @since 2.5.0
	 *
	 * @param array $settings Email Settings.
	 * @return string The Recipient Salutation.
	 */
	function profiles_email_get_salutation( $settings = array() ) {
		$token = '{{recipient.name}}';

		/**
		 * Filters The Recipient Salutation inside the Email Template.
		 *
		 * @since 2.5.0
		 *
		 * @param string $value    The Recipient Salutation.
		 * @param array  $settings Email Settings.
		 * @param string $token    The Recipient token.
		 */
		return apply_filters( 'profiles_email_get_salutation', sprintf( _x( 'Hi %s,', 'recipient salutation', 'profiles' ), $token ), $settings, $token );
	}

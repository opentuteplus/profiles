<?php
/**
 * Profiles Core Login Widget.
 *
 * @package Profiles
 * @suprofilesackage Core
 * @since 1.9.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Profiles Login Widget.
 *
 * @since 1.9.0
 */
class Profiles_Core_Login_Widget extends WP_Widget {

	/**
	 * Constructor method.
	 *
	 * @since 1.9.0
	 */
	public function __construct() {
		parent::__construct(
			false,
			_x( '(Profiles) Log In', 'Title of the login widget', 'profiles' ),
			array(
				'description'                 => __( 'Show a Log In form to logged-out visitors, and a Log Out link to those who are logged in.', 'profiles' ),
				'classname'                   => 'widget_profiles_core_login_widget profiles widget',
				'customize_selective_refresh' => true,
			)
		);
	}

	/**
	 * Display the login widget.
	 *
	 * @since 1.9.0
	 *
	 * @see WP_Widget::widget() for description of parameters.
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Widget settings, as saved by the user.
	 */
	public function widget( $args, $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : '';

		/**
		 * Filters the title of the Login widget.
		 *
		 * @since 1.9.0
		 * @since 2.3.0 Added 'instance' and 'id_base' to arguments passed to filter.
		 *
		 * @param string $title    The widget title.
		 * @param array  $instance The settings for the particular instance of the widget.
		 * @param string $id_base  Root ID for all widgets of this type.
		 */
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		echo $args['before_widget'];

		echo $args['before_title'] . esc_html( $title ) . $args['after_title']; ?>

		<?php if ( is_user_logged_in() ) : ?>

			<?php
			/**
			 * Fires before the display of widget content if logged in.
			 *
			 * @since 1.9.0
			 */
			do_action( 'profiles_before_login_widget_loggedin' ); ?>

			<div class="profiles-login-widget-user-avatar">
				<a href="<?php echo profiles_loggedin_user_domain(); ?>">
					<?php profiles_loggedin_user_avatar( 'type=thumb&width=50&height=50' ); ?>
				</a>
			</div>

			<div class="profiles-login-widget-user-links">
				<div class="profiles-login-widget-user-link"><?php echo profiles_core_get_userlink( profiles_loggedin_user_id() ); ?></div>
				<div class="profiles-login-widget-user-logout"><a class="logout" href="<?php echo wp_logout_url( profiles_get_requested_url() ); ?>"><?php _e( 'Log Out', 'profiles' ); ?></a></div>
			</div>

			<?php

			/**
			 * Fires after the display of widget content if logged in.
			 *
			 * @since 1.9.0
			 */
			do_action( 'profiles_after_login_widget_loggedin' ); ?>

		<?php else : ?>

			<?php

			/**
			 * Fires before the display of widget content if logged out.
			 *
			 * @since 1.9.0
			 */
			do_action( 'profiles_before_login_widget_loggedout' ); ?>

			<form name="profiles-login-form" id="profiles-login-widget-form" class="standard-form" action="<?php echo esc_url( site_url( 'wp-login.php', 'login_post' ) ); ?>" method="post">
				<label for="profiles-login-widget-user-login"><?php _e( 'Username', 'profiles' ); ?></label>
				<input type="text" name="log" id="profiles-login-widget-user-login" class="input" value="" />

				<label for="profiles-login-widget-user-pass"><?php _e( 'Password', 'profiles' ); ?></label>
				<input type="password" name="pwd" id="profiles-login-widget-user-pass" class="input" value="" <?php profiles_form_field_attributes( 'password' ) ?> />

				<div class="forgetmenot"><label for="profiles-login-widget-rememberme"><input name="rememberme" type="checkbox" id="profiles-login-widget-rememberme" value="forever" /> <?php _e( 'Remember Me', 'profiles' ); ?></label></div>

				<input type="submit" name="wp-submit" id="profiles-login-widget-submit" value="<?php esc_attr_e( 'Log In', 'profiles' ); ?>" />

				<?php

				/**
				 * Fires inside the display of the login widget form.
				 *
				 * @since 2.4.0
				 */
				do_action( 'profiles_login_widget_form' ); ?>

			</form>

			<?php

			/**
			 * Fires after the display of widget content if logged out.
			 *
			 * @since 1.9.0
			 */
			do_action( 'profiles_after_login_widget_loggedout' ); ?>

		<?php endif;

		echo $args['after_widget'];
	}

	/**
	 * Update the login widget options.
	 *
	 * @since 1.9.0
	 *
	 * @param array $new_instance The new instance options.
	 * @param array $old_instance The old instance options.
	 * @return array $instance The parsed options to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance             = $old_instance;
		$instance['title']    = isset( $new_instance['title'] ) ? strip_tags( $new_instance['title'] ) : '';

		return $instance;
	}

	/**
	 * Output the login widget options form.
	 *
	 * @since 1.9.0
	 *
	 * @param array $instance Settings for this widget.
	 * @return void
	 */
	public function form( $instance = array() ) {

		$settings = wp_parse_args( $instance, array(
			'title' => '',
		) ); ?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'profiles' ); ?>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $settings['title'] ); ?>" /></label>
		</p>

		<?php
	}
}

<?php
/**
 * Settings page view.
 *
 * @package PrimeStories
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap prime-stories-admin-wrap">
	<?php
	prime_stories_render_admin_header(
		__( 'Prime Stories Settings', 'prime-stories' ),
		__( 'Control the default layout, colors, performance behavior, analytics, and uninstall preferences for Prime Stories.', 'prime-stories' )
	);
	?>
	<form action="options.php" method="post">
		<?php settings_fields( 'prime_stories_settings_group' ); ?>
		<?php do_settings_sections( 'prime-stories-settings' ); ?>
		<?php submit_button(); ?>
	</form>
</div>

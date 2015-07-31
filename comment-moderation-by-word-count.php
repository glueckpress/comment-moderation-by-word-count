<?php
/**
 * Plugin Name: Comment Moderation by Word Count
 * Description: Sends a comment to moderation queue if it contains more than a given number of words.
 * Version:     0.1
 * Author:      Caspar Hübinger
 * Plugin URI:  https://github.com/glueckpress/comment-moderation-by-word-count/
 * Author URI:  https://profiles.wordpress.org/glueckpress
 * License: GNU General Public License v3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: comment-moderation-by-word-count
 * Domain Path: /languages
 */


if ( ! defined( 'ABSPATH' ) )
	exit;


/**
 * Initializes the plugin.
 *
 * @return void
 */
function comment_moderation_by_word_count__settings_init() {

	$plugin_slug = plugin_basename( __FILE__ );

	// i18n
	load_plugin_textdomain(
		'comment-moderation-by-word-count',
		false,
		dirname( $plugin_slug ) . '/languages/'
	);

	// Render Settings link on plugins page.
	add_filter( "plugin_action_links_$plugin_slug", 'comment_moderation_by_word_count__settings_link' );

	// Register setting.
	register_setting( 'discussion', 'comment-moderation-by-word-count' );

	// Add field.
	add_settings_field(
		'comment-moderation-by-word-count',
		__( 'Moderation by Word Count', 'comment-moderation-by-word-count' ),
		'comment_moderation_by_word_count__render_settings_field',
		'discussion',
		'default'
	);
}
add_action( 'admin_init', 'comment_moderation_by_word_count__settings_init' );


/**
 * Adds functionality in the front-end.
 *
 * @return void
 */
function comment_moderation_by_word_count__pre_comment_approved() {

	// Not needed in the back-end.
	if ( is_admin() )
		return;

	add_filter( 'pre_comment_approved', 'comment_moderation_by_word_count__unapprove', 10, 2 );
}
add_action( 'plugins_loaded' , 'comment_moderation_by_word_count__pre_comment_approved' );


/**
 * Send comment to moderation if text is longer than given number of words.
 *
 * @wp-hook pre_comment_approved
 *
 * @param   bool   $approved
 * @param   array  $commentdata
 * @return  bool
 */
function comment_moderation_by_word_count__unapprove( $approved , $commentdata ) {
	$option = comment_moderation_by_word_count__get_sanitized_word_count();

	if ( $approved !== 1 || ! $option || current_user_can( 'edit_posts' ) )
		return $approved;

	return str_word_count( utf8_decode( $commentdata[ 'comment_content' ] ) ) > absint( $option ) ? 0 : $approved;
}


/**
 * Renders the settings field on Settings → Discussion.
 *
 * @return string
 */
function comment_moderation_by_word_count__render_settings_field() {
	$id     = 'comment-moderation-by-word-count';
	$option = comment_moderation_by_word_count__get_sanitized_word_count();
	$field  = sprintf(
		'<input type="number" id="%1$s" name="%1$s" value="%2$s" class="small-text" />',
		$id,
		$option
	);
	$label_text = sprintf(
		__( 'Hold a comment in queue if it contains more than %s words. (Leave empty to disable.)', 'comment-moderation-by-word-count' ),
		$field
	);

	printf(
		'<label for="%1$s">%2$s</label>',
		$id,
		$label_text
	);
}


/**
 * Adds a Settings link on the plugin page.
 *
 * @param  array $links
 * @return array $links
 */
function comment_moderation_by_word_count__settings_link( $links ) {
	$settings_link = sprintf(
		'<a href="%1$s">%2$s</a>',
		admin_url( 'options-discussion.php#blacklist_keys' ),
		__( 'Settings' )
	);
	array_unshift( $links, $settings_link );

	return $links;
}


/**
 * Sanitze option value to be integer or empty string.
 *
 * @return integer|string
 */
function comment_moderation_by_word_count__get_sanitized_word_count() {
	$option = get_option( 'comment-moderation-by-word-count', '' );

	if ( absint( $option ) > 0 ) {
		$option = absint( $option );
	} else {
		$option = '';
	}

	return $option;
}

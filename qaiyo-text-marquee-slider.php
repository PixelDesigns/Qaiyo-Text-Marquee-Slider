<?php
/**
 * Plugin Name: Qaiyo Text Marquee Slider
 * Plugin URI: https://www.pixeldesigns.hu/pluginok/
 * Description: Testreszabható, animált marquee slider sorokkal, ikonokkal, elválasztókkal és halványuló szélekkel.
 * Version: 1.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: PixelDesigns
 * Author URI: https://pixeldesigns.hu
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: qaiyo-text-marquee-slider
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'QTMS_VERSION', '1.0' );
define( 'QTMS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'QTMS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'QTMS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once QTMS_PLUGIN_DIR . 'includes/class-qtms-i18n.php';
require_once QTMS_PLUGIN_DIR . 'includes/class-qtms-post-type.php';
require_once QTMS_PLUGIN_DIR . 'includes/class-qtms-admin.php';
require_once QTMS_PLUGIN_DIR . 'includes/class-qtms-frontend.php';

function qtms_init() {
    QTMS_I18n::init();
    QTMS_Post_Type::init();

    if ( is_admin() ) {
        QTMS_Admin::init();
    }

    QTMS_Frontend::init();
}
add_action( 'plugins_loaded', 'qtms_init' );

function qtms_activate() {
    QTMS_Post_Type::register_post_type();
    QTMS_Post_Type::register_taxonomy();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'qtms_activate' );

function qtms_deactivate() {
    flush_rewrite_rules();
    delete_option( 'qtms_detected_fonts' );
}
register_deactivation_hook( __FILE__, 'qtms_deactivate' );

/**
 * AJAX handler: save detected frontend fonts.
 * A tiny inline script on the frontend reads getComputedStyle for body/h1/h2/h3
 * and POSTs the results here once per 24 h.
 */
function qtms_ajax_save_detected_fonts() {
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    $raw = isset( $_POST['fonts'] ) ? wp_unslash( $_POST['fonts'] ) : array();
    if ( ! is_array( $raw ) ) {
        wp_send_json_error();
    }

    $allowed_keys = array( 'body', 'h1', 'h2', 'h3' );
    $clean        = array();
    foreach ( $allowed_keys as $k ) {
        $clean[ $k ] = isset( $raw[ $k ] ) ? sanitize_text_field( $raw[ $k ] ) : '';
    }

    update_option( 'qtms_detected_fonts', $clean, false );
    update_option( 'qtms_detected_fonts_ts', time(), false );
    wp_send_json_success();
}
add_action( 'wp_ajax_qtms_detect_fonts', 'qtms_ajax_save_detected_fonts' );
add_action( 'wp_ajax_nopriv_qtms_detect_fonts', 'qtms_ajax_save_detected_fonts' );

function qtms_allow_svg_upload( $mimes ) {
    if ( current_user_can( 'manage_options' ) ) {
        $mimes['svg'] = 'image/svg+xml';
    }
    return $mimes;
}
add_filter( 'upload_mimes', 'qtms_allow_svg_upload' );

function qtms_check_svg_filetype( $data, $file, $filename, $mimes ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        return $data;
    }

    if ( ! empty( $data['ext'] ) && 'svg' === $data['ext'] ) {
        return $data;
    }

    $filetype = wp_check_filetype( $filename, $mimes );
    if ( 'svg' === $filetype['ext'] ) {
        $data['ext']  = 'svg';
        $data['type'] = 'image/svg+xml';
    }

    return $data;
}
add_filter( 'wp_check_filetype_and_ext', 'qtms_check_svg_filetype', 10, 4 );

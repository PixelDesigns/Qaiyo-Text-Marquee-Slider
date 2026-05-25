<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class QTMS_I18n {

    private static $translations = array();

    public static function init() {
        // Load PHP translations IMMEDIATELY during plugins_loaded
        // so the gettext filter is active before any __() call.
        // WordPress 4.6+ automatically loads .mo files based on the Text Domain
        // header and the /languages folder, so we don't need load_plugin_textdomain().
        self::load_php_translations();
    }

    private static function load_php_translations() {
        $locale = determine_locale();
        $file   = QTMS_PLUGIN_DIR . 'languages/qaiyo-text-marquee-slider-' . $locale . '.php';

        if ( ! file_exists( $file ) ) {
            $short = substr( $locale, 0, 2 );
            $map   = array(
                'hu' => 'hu_HU',
                'de' => 'de_DE',
                'fr' => 'fr_FR',
                'es' => 'es_ES',
            );
            if ( isset( $map[ $short ] ) ) {
                $file = QTMS_PLUGIN_DIR . 'languages/qaiyo-text-marquee-slider-' . $map[ $short ] . '.php';
            }
        }

        if ( file_exists( $file ) ) {
            self::$translations = include $file;
            add_filter( 'gettext', array( __CLASS__, 'filter_gettext' ), 10, 3 );
            add_filter( 'ngettext', array( __CLASS__, 'filter_ngettext' ), 10, 5 );
        }
    }

    public static function filter_gettext( $translated, $text, $domain ) {
        if ( 'qaiyo-text-marquee-slider' !== $domain ) {
            return $translated;
        }
        return isset( self::$translations[ $text ] ) ? self::$translations[ $text ] : $translated;
    }

    public static function filter_ngettext( $translated, $single, $plural, $number, $domain ) {
        if ( 'qaiyo-text-marquee-slider' !== $domain ) {
            return $translated;
        }
        $key = ( 1 === $number ) ? $single : $plural;
        return isset( self::$translations[ $key ] ) ? self::$translations[ $key ] : $translated;
    }
}

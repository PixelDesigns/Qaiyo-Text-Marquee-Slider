<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class QTMS_Post_Type {

    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_post_type' ) );
        add_action( 'init', array( __CLASS__, 'register_taxonomy' ) );
        add_filter( 'pll_get_post_types', array( __CLASS__, 'polylang_post_types' ) );
        add_filter( 'pll_get_taxonomies', array( __CLASS__, 'polylang_taxonomies' ) );
    }

    public static function register_post_type() {
        $labels = array(
            'name'               => __( 'Marquee Sliders', 'qaiyo-text-marquee-slider' ),
            'singular_name'      => __( 'Marquee Slider', 'qaiyo-text-marquee-slider' ),
            'add_new'            => __( 'Add New', 'qaiyo-text-marquee-slider' ),
            'add_new_item'       => __( 'Add New Slider', 'qaiyo-text-marquee-slider' ),
            'edit_item'          => __( 'Edit Slider', 'qaiyo-text-marquee-slider' ),
            'new_item'           => __( 'New Slider', 'qaiyo-text-marquee-slider' ),
            'view_item'          => __( 'View Slider', 'qaiyo-text-marquee-slider' ),
            'search_items'       => __( 'Search Sliders', 'qaiyo-text-marquee-slider' ),
            'not_found'          => __( 'No sliders found', 'qaiyo-text-marquee-slider' ),
            'not_found_in_trash' => __( 'No sliders found in Trash', 'qaiyo-text-marquee-slider' ),
            'all_items'          => __( 'All Sliders', 'qaiyo-text-marquee-slider' ),
            'menu_name'          => __( 'Marquee Slider', 'qaiyo-text-marquee-slider' ),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_position'       => 30,
            'menu_icon'           => 'dashicons-slides',
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'supports'            => array( 'title' ),
            'has_archive'         => false,
            'rewrite'             => false,
            'query_var'           => false,
            'show_in_rest'        => false,
            'exclude_from_search' => true,
        );

        register_post_type( 'qtms_slider', $args );
    }

    public static function register_taxonomy() {
        $labels = array(
            'name'              => __( 'Slider Categories', 'qaiyo-text-marquee-slider' ),
            'singular_name'     => __( 'Slider Category', 'qaiyo-text-marquee-slider' ),
            'search_items'      => __( 'Search Categories', 'qaiyo-text-marquee-slider' ),
            'all_items'         => __( 'All Categories', 'qaiyo-text-marquee-slider' ),
            'parent_item'       => __( 'Parent Category', 'qaiyo-text-marquee-slider' ),
            'parent_item_colon' => __( 'Parent Category:', 'qaiyo-text-marquee-slider' ),
            'edit_item'         => __( 'Edit Category', 'qaiyo-text-marquee-slider' ),
            'update_item'       => __( 'Update Category', 'qaiyo-text-marquee-slider' ),
            'add_new_item'      => __( 'Add New Category', 'qaiyo-text-marquee-slider' ),
            'new_item_name'     => __( 'New Category Name', 'qaiyo-text-marquee-slider' ),
            'menu_name'         => __( 'Categories', 'qaiyo-text-marquee-slider' ),
        );

        $args = array(
            'labels'            => $labels,
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => false,
            'rewrite'           => false,
            'show_in_rest'      => false,
        );

        register_taxonomy( 'qtms_category', 'qtms_slider', $args );
    }

    public static function polylang_post_types( $types ) {
        $types['qtms_slider'] = 'qtms_slider';
        return $types;
    }

    public static function polylang_taxonomies( $taxonomies ) {
        $taxonomies['qtms_category'] = 'qtms_category';
        return $taxonomies;
    }
}

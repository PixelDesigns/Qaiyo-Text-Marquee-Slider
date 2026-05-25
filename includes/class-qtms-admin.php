<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class QTMS_Admin {

    public static function init() {
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
        add_action( 'save_post_qtms_slider', array( __CLASS__, 'save_meta' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        add_filter( 'manage_qtms_slider_posts_columns', array( __CLASS__, 'admin_columns' ) );
        add_action( 'manage_qtms_slider_posts_custom_column', array( __CLASS__, 'admin_column_content' ), 10, 2 );
    }

    public static function enqueue_assets( $hook ) {
        if ( ! in_array( $hook, array( 'post.php', 'post-new.php', 'edit.php' ), true ) ) {
            return;
        }
        $screen = get_current_screen();
        if ( ! $screen || 'qtms_slider' !== $screen->post_type ) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'jquery-ui-sortable' );

        wp_enqueue_style(
            'qtms-admin',
            QTMS_PLUGIN_URL . 'admin/css/qtms-admin.css',
            array( 'wp-color-picker' ),
            QTMS_VERSION
        );

        wp_enqueue_script(
            'qtms-admin',
            QTMS_PLUGIN_URL . 'admin/js/qtms-admin.js',
            array( 'jquery', 'wp-color-picker', 'jquery-ui-sortable' ),
            QTMS_VERSION,
            true
        );

        wp_localize_script( 'qtms-admin', 'qtmsAdmin', array(
            'selectImage'  => __( 'Select Image', 'qaiyo-text-marquee-slider' ),
            'useImage'     => __( 'Use Image', 'qaiyo-text-marquee-slider' ),
            'removeImage'  => __( 'Remove', 'qaiyo-text-marquee-slider' ),
            'confirmDelete' => __( 'Are you sure you want to remove this item?', 'qaiyo-text-marquee-slider' ),
        ) );
    }

    public static function add_meta_boxes() {
        add_meta_box(
            'qtms_shortcode',
            __( 'Shortcode', 'qaiyo-text-marquee-slider' ),
            array( __CLASS__, 'render_shortcode_box' ),
            'qtms_slider',
            'side',
            'high'
        );

        add_meta_box(
            'qtms_settings',
            __( 'Marquee Settings', 'qaiyo-text-marquee-slider' ),
            array( __CLASS__, 'render_settings_box' ),
            'qtms_slider',
            'normal',
            'high'
        );
    }

    public static function render_shortcode_box( $post ) {
        if ( 'auto-draft' === $post->post_status ) {
            echo '<p class="qtms-hint">' . esc_html__( 'Save the slider first to get the shortcode.', 'qaiyo-text-marquee-slider' ) . '</p>';
            return;
        }
        $shortcode = '[qaiyo_text_marquee id="' . (int) $post->ID . '"]';
        ?>
        <div class="qtms-shortcode-wrap">
            <input type="text" readonly value="<?php echo esc_attr( $shortcode ); ?>" class="qtms-shortcode-input" onclick="this.select();">
            <button type="button" class="button qtms-copy-btn" data-clipboard="<?php echo esc_attr( $shortcode ); ?>">
                <span class="dashicons dashicons-clipboard"></span>
            </button>
        </div>
        <p class="qtms-hint"><?php esc_html_e( 'Copy and paste this shortcode into any page or post.', 'qaiyo-text-marquee-slider' ); ?></p>
        <?php
    }

    public static function render_settings_box( $post ) {
        wp_nonce_field( 'qtms_save_meta', 'qtms_nonce' );

        $row_count  = (int) get_post_meta( $post->ID, '_qtms_row_count', true ) ?: 1;
        $row_gap    = get_post_meta( $post->ID, '_qtms_row_gap', true );
        $fade_width = get_post_meta( $post->ID, '_qtms_fade_width', true );
        $font_size   = get_post_meta( $post->ID, '_qtms_font_size', true );
        $font_family = get_post_meta( $post->ID, '_qtms_font_family', true ) ?: '';
        $bg_color    = get_post_meta( $post->ID, '_qtms_bg_color', true ) ?: 'transparent';
        $schema      = (int) get_post_meta( $post->ID, '_qtms_schema', true );
        $rows       = get_post_meta( $post->ID, '_qtms_rows', true );

        $row_gap    = '' !== $row_gap ? (int) $row_gap : 20;
        $fade_width = '' !== $fade_width ? (int) $fade_width : 80;
        $font_size  = '' !== $font_size ? (int) $font_size : 16;

        if ( ! is_array( $rows ) || empty( $rows ) ) {
            $rows = array( self::get_default_row() );
        }

        while ( count( $rows ) < 3 ) {
            $rows[] = self::get_default_row();
        }
        ?>
        <div class="qtms-settings-wrap">

            <div class="qtms-global-settings">
                <div class="qtms-field-row">
                    <div class="qtms-field">
                        <label><?php esc_html_e( 'Number of Rows', 'qaiyo-text-marquee-slider' ); ?></label>
                        <div class="qtms-radio-group">
                            <?php for ( $i = 1; $i <= 3; $i++ ) : ?>
                                <label class="qtms-radio-label <?php echo $row_count === $i ? 'active' : ''; ?>">
                                    <input type="radio" name="qtms_row_count" value="<?php echo (int) $i; ?>"
                                        <?php checked( $row_count, $i ); ?>>
                                    <span><?php echo (int) $i; ?></span>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="qtms-field">
                        <label><?php esc_html_e( 'Row Gap', 'qaiyo-text-marquee-slider' ); ?></label>
                        <div class="qtms-input-with-unit">
                            <input type="number" name="qtms_row_gap" value="<?php echo esc_attr( $row_gap ); ?>" min="0" max="200">
                            <span class="qtms-unit">px</span>
                        </div>
                    </div>
                    <div class="qtms-field">
                        <label><?php esc_html_e( 'Fade Width', 'qaiyo-text-marquee-slider' ); ?></label>
                        <div class="qtms-input-with-unit">
                            <input type="number" name="qtms_fade_width" value="<?php echo esc_attr( $fade_width ); ?>" min="0" max="300">
                            <span class="qtms-unit">px</span>
                        </div>
                    </div>
                    <div class="qtms-field">
                        <label><?php esc_html_e( 'Font Size', 'qaiyo-text-marquee-slider' ); ?></label>
                        <div class="qtms-input-with-unit">
                            <input type="number" name="qtms_font_size" value="<?php echo esc_attr( $font_size ); ?>" min="8" max="200">
                            <span class="qtms-unit">px</span>
                        </div>
                    </div>
                    <div class="qtms-field">
                        <label><?php esc_html_e( 'Font Family', 'qaiyo-text-marquee-slider' ); ?></label>
                        <?php
                        $detected = get_option( 'qtms_detected_fonts', array() );
                        $font_options = array(
                            '' => __( 'System default (system-ui)', 'qaiyo-text-marquee-slider' ),
                        );

                        // Build unique detected fonts list
                        $seen = array();
                        $labels = array(
                            'body' => __( 'Body', 'qaiyo-text-marquee-slider' ),
                            'h1'   => 'H1',
                            'h2'   => 'H2',
                            'h3'   => 'H3',
                        );
                        foreach ( array( 'body', 'h1', 'h2', 'h3' ) as $tag ) {
                            if ( ! empty( $detected[ $tag ] ) && ! isset( $seen[ $detected[ $tag ] ] ) ) {
                                $seen[ $detected[ $tag ] ] = true;
                                $used_in = array();
                                foreach ( array( 'body', 'h1', 'h2', 'h3' ) as $t ) {
                                    if ( ! empty( $detected[ $t ] ) && $detected[ $t ] === $detected[ $tag ] ) {
                                        $used_in[] = $labels[ $t ];
                                    }
                                }
                                $font_options[ $detected[ $tag ] ] = $detected[ $tag ] . ' (' . implode( ', ', $used_in ) . ')';
                            }
                        }
                        ?>
                        <select name="qtms_font_family">
                            <?php foreach ( $font_options as $val => $label ) : ?>
                                <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $font_family, $val ); ?>><?php echo esc_html( $label ); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ( empty( $detected ) ) : ?>
                            <p class="qtms-hint"><?php esc_html_e( 'Visit the frontend once to detect your site fonts.', 'qaiyo-text-marquee-slider' ); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="qtms-field">
                        <label><?php esc_html_e( 'Background Color', 'qaiyo-text-marquee-slider' ); ?></label>
                        <input type="text" name="qtms_bg_color" value="<?php echo esc_attr( $bg_color ); ?>" class="qtms-color-picker" data-default-color="transparent">
                    </div>
                    <div class="qtms-field qtms-field-toggle">
                        <label><?php esc_html_e( 'SEO Schema', 'qaiyo-text-marquee-slider' ); ?></label>
                        <label class="qtms-toggle">
                            <input type="checkbox" name="qtms_schema" value="1" <?php checked( $schema, 1 ); ?>>
                            <span><?php esc_html_e( 'Generate ItemList markup', 'qaiyo-text-marquee-slider' ); ?></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="qtms-rows-wrap">
                <?php for ( $r = 0; $r < 3; $r++ ) :
                    $row     = $rows[ $r ];
                    $visible = $r < $row_count;
                ?>
                <div class="qtms-row-panel" data-row="<?php echo (int) $r; ?>" style="<?php echo $visible ? '' : 'display:none;'; ?>">
                    <div class="qtms-row-header">
                        <h3>
                            <?php
                            /* translators: %d: row number */
                            echo esc_html( sprintf( __( 'Row %d', 'qaiyo-text-marquee-slider' ), $r + 1 ) );
                            ?>
                        </h3>
                        <span class="qtms-row-toggle dashicons dashicons-arrow-down-alt2"></span>
                    </div>
                    <div class="qtms-row-body">
                        <div class="qtms-row-settings">
                            <div class="qtms-field">
                                <label><?php esc_html_e( 'Direction', 'qaiyo-text-marquee-slider' ); ?></label>
                                <div class="qtms-direction-toggle">
                                    <button type="button" class="qtms-dir-btn <?php echo 'left' === $row['direction'] ? 'active' : ''; ?>" data-dir="left">
                                        <span class="dashicons dashicons-arrow-left-alt"></span>
                                        <?php esc_html_e( 'Left', 'qaiyo-text-marquee-slider' ); ?>
                                    </button>
                                    <button type="button" class="qtms-dir-btn <?php echo 'right' === $row['direction'] ? 'active' : ''; ?>" data-dir="right">
                                        <?php esc_html_e( 'Right', 'qaiyo-text-marquee-slider' ); ?>
                                        <span class="dashicons dashicons-arrow-right-alt"></span>
                                    </button>
                                    <input type="hidden" name="qtms_rows[<?php echo (int) $r; ?>][direction]" value="<?php echo esc_attr( $row['direction'] ); ?>">
                                </div>
                            </div>
                            <div class="qtms-field">
                                <label><?php esc_html_e( 'Speed', 'qaiyo-text-marquee-slider' ); ?></label>
                                <div class="qtms-range-wrap">
                                    <input type="range" name="qtms_rows[<?php echo (int) $r; ?>][speed]"
                                        value="<?php echo esc_attr( $row['speed'] ); ?>" min="1" max="100" class="qtms-range">
                                    <span class="qtms-range-val"><?php echo esc_html( $row['speed'] ); ?></span>
                                </div>
                            </div>
                            <div class="qtms-field">
                                <label><?php esc_html_e( 'Separator Image', 'qaiyo-text-marquee-slider' ); ?></label>
                                <div class="qtms-media-field">
                                    <?php
                                    $sep_id  = ! empty( $row['separator_image'] ) ? (int) $row['separator_image'] : 0;
                                    $sep_url = $sep_id ? wp_get_attachment_url( $sep_id ) : '';
                                    ?>
                                    <input type="hidden" name="qtms_rows[<?php echo (int) $r; ?>][separator_image]" value="<?php echo esc_attr( $sep_id ); ?>" class="qtms-media-id">
                                    <div class="qtms-media-preview" <?php echo $sep_url ? '' : 'style="display:none;"'; ?>>
                                        <img src="<?php echo esc_url( $sep_url ); ?>" alt="">
                                        <button type="button" class="qtms-media-remove">&times;</button>
                                    </div>
                                    <button type="button" class="button qtms-media-upload" <?php echo $sep_url ? 'style="display:none;"' : ''; ?>>
                                        <span class="dashicons dashicons-upload"></span>
                                        <?php esc_html_e( 'Upload', 'qaiyo-text-marquee-slider' ); ?>
                                    </button>
                                </div>
                            </div>
                            <div class="qtms-field">
                                <label><?php esc_html_e( 'Separator Size', 'qaiyo-text-marquee-slider' ); ?></label>
                                <div class="qtms-input-with-unit">
                                    <input type="number" name="qtms_rows[<?php echo (int) $r; ?>][separator_size]"
                                        value="<?php echo esc_attr( ! empty( $row['separator_size'] ) ? $row['separator_size'] : 8 ); ?>" min="2" max="100">
                                    <span class="qtms-unit">px</span>
                                </div>
                            </div>
                        </div>

                        <div class="qtms-items-header">
                            <h4><?php esc_html_e( 'Items', 'qaiyo-text-marquee-slider' ); ?></h4>
                            <button type="button" class="button qtms-add-item">
                                <span class="dashicons dashicons-plus-alt2"></span>
                                <?php esc_html_e( 'Add Item', 'qaiyo-text-marquee-slider' ); ?>
                            </button>
                        </div>

                        <div class="qtms-items-list" data-row="<?php echo (int) $r; ?>">
                            <?php
                            if ( ! empty( $row['items'] ) ) {
                                foreach ( $row['items'] as $idx => $item ) {
                                    self::render_item_card( $r, $idx, $item );
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>

            <template id="qtms-item-template">
                <?php self::render_item_card( '__ROW__', '__IDX__', self::get_default_item() ); ?>
            </template>

            <div class="qtms-footer-info">
                Qaiyo Text Marquee Slider v<?php echo esc_html( QTMS_VERSION ); ?> &mdash; <?php esc_html_e( 'Created by', 'qaiyo-text-marquee-slider' ); ?>: PixelDesigns
            </div>
        </div>
        <?php
    }

    private static function render_item_card( $row_idx, $item_idx, $item ) {
        $prefix = "qtms_rows[{$row_idx}][items][{$item_idx}]";
        ?>
        <div class="qtms-item-card">
            <div class="qtms-item-drag-handle">
                <span class="dashicons dashicons-menu"></span>
            </div>
            <div class="qtms-item-fields">
                <div class="qtms-item-row-top">
                    <div class="qtms-field qtms-field-text">
                        <label><?php esc_html_e( 'Text', 'qaiyo-text-marquee-slider' ); ?></label>
                        <input type="text" name="<?php echo esc_attr( $prefix ); ?>[text]"
                            value="<?php echo esc_attr( $item['text'] ); ?>" placeholder="<?php esc_attr_e( 'Item text...', 'qaiyo-text-marquee-slider' ); ?>">
                    </div>
                    <button type="button" class="qtms-item-remove" title="<?php esc_attr_e( 'Remove', 'qaiyo-text-marquee-slider' ); ?>">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
                <div class="qtms-item-row-mid">
                    <div class="qtms-field qtms-field-color">
                        <label><?php esc_html_e( 'Text Color', 'qaiyo-text-marquee-slider' ); ?></label>
                        <input type="text" name="<?php echo esc_attr( $prefix ); ?>[text_color]"
                            value="<?php echo esc_attr( $item['text_color'] ); ?>" class="qtms-color-picker" data-default-color="#ffffff">
                    </div>
                    <div class="qtms-field qtms-field-spacing">
                        <label><?php esc_html_e( 'Spacing', 'qaiyo-text-marquee-slider' ); ?></label>
                        <div class="qtms-input-with-unit">
                            <input type="number" name="<?php echo esc_attr( $prefix ); ?>[spacing]"
                                value="<?php echo esc_attr( $item['spacing'] ); ?>" min="0" max="500">
                            <span class="qtms-unit">px</span>
                        </div>
                    </div>
                </div>
                <div class="qtms-item-row-badge">
                    <div class="qtms-field qtms-field-color">
                        <label><?php esc_html_e( 'Background', 'qaiyo-text-marquee-slider' ); ?></label>
                        <input type="text" name="<?php echo esc_attr( $prefix ); ?>[bg_color]"
                            value="<?php echo esc_attr( isset( $item['bg_color'] ) ? $item['bg_color'] : '' ); ?>"
                            class="qtms-color-picker" data-default-color="transparent">
                    </div>
                    <div class="qtms-field qtms-field-color">
                        <label><?php esc_html_e( 'Border Color', 'qaiyo-text-marquee-slider' ); ?></label>
                        <input type="text" name="<?php echo esc_attr( $prefix ); ?>[border_color]"
                            value="<?php echo esc_attr( isset( $item['border_color'] ) ? $item['border_color'] : '' ); ?>"
                            class="qtms-color-picker" data-default-color="transparent">
                    </div>
                    <div class="qtms-field">
                        <label><?php esc_html_e( 'Border Width', 'qaiyo-text-marquee-slider' ); ?></label>
                        <div class="qtms-input-with-unit">
                            <input type="number" name="<?php echo esc_attr( $prefix ); ?>[border_width]"
                                value="<?php echo esc_attr( isset( $item['border_width'] ) ? $item['border_width'] : 0 ); ?>" min="0" max="20">
                            <span class="qtms-unit">px</span>
                        </div>
                    </div>
                    <div class="qtms-field">
                        <label><?php esc_html_e( 'Radius', 'qaiyo-text-marquee-slider' ); ?></label>
                        <div class="qtms-input-with-unit">
                            <input type="number" name="<?php echo esc_attr( $prefix ); ?>[border_radius]"
                                value="<?php echo esc_attr( isset( $item['border_radius'] ) ? $item['border_radius'] : 0 ); ?>" min="0" max="999">
                            <span class="qtms-unit">px</span>
                        </div>
                    </div>
                </div>
                <div class="qtms-item-row-link">
                    <div class="qtms-field qtms-field-link">
                        <label><?php esc_html_e( 'Link', 'qaiyo-text-marquee-slider' ); ?></label>
                        <input type="url" name="<?php echo esc_attr( $prefix ); ?>[link_url]"
                            value="<?php echo esc_attr( $item['link_url'] ); ?>" placeholder="https://">
                    </div>
                    <div class="qtms-field qtms-field-target">
                        <label>
                            <input type="checkbox" name="<?php echo esc_attr( $prefix ); ?>[link_target]" value="_blank"
                                <?php checked( $item['link_target'], '_blank' ); ?>>
                            <?php esc_html_e( 'New tab', 'qaiyo-text-marquee-slider' ); ?>
                        </label>
                    </div>
                </div>
                <div class="qtms-item-row-icons">
                    <div class="qtms-field qtms-field-icon">
                        <label><?php esc_html_e( 'Icon Before', 'qaiyo-text-marquee-slider' ); ?></label>
                        <?php self::render_icon_field( $prefix . '[icon_before]', $item['icon_before'] ); ?>
                    </div>
                    <div class="qtms-field qtms-field-icon">
                        <label><?php esc_html_e( 'Icon After', 'qaiyo-text-marquee-slider' ); ?></label>
                        <?php self::render_icon_field( $prefix . '[icon_after]', $item['icon_after'] ); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private static function render_icon_field( $name, $attachment_id ) {
        $attachment_id = (int) $attachment_id;
        $url           = $attachment_id ? wp_get_attachment_url( $attachment_id ) : '';
        ?>
        <div class="qtms-media-field qtms-icon-field">
            <input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $attachment_id ); ?>" class="qtms-media-id">
            <div class="qtms-media-preview qtms-icon-preview" <?php echo $url ? '' : 'style="display:none;"'; ?>>
                <img src="<?php echo esc_url( $url ); ?>" alt="">
                <button type="button" class="qtms-media-remove">&times;</button>
            </div>
            <button type="button" class="button button-small qtms-media-upload" <?php echo $url ? 'style="display:none;"' : ''; ?>>
                <span class="dashicons dashicons-format-image"></span>
            </button>
        </div>
        <?php
    }

    public static function save_meta( $post_id, $post ) {
        if ( ! isset( $_POST['qtms_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['qtms_nonce'] ) ), 'qtms_save_meta' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $row_count    = isset( $_POST['qtms_row_count'] ) ? absint( wp_unslash( $_POST['qtms_row_count'] ) ) : 1;
        $row_count    = max( 1, min( 3, $row_count ) );
        $row_gap      = isset( $_POST['qtms_row_gap'] ) ? absint( wp_unslash( $_POST['qtms_row_gap'] ) ) : 20;
        $fade_width   = isset( $_POST['qtms_fade_width'] ) ? absint( wp_unslash( $_POST['qtms_fade_width'] ) ) : 80;
        $font_size    = isset( $_POST['qtms_font_size'] ) ? absint( wp_unslash( $_POST['qtms_font_size'] ) ) : 16;
        $font_size    = max( 8, min( 200, $font_size ) );
        $font_family  = isset( $_POST['qtms_font_family'] ) ? sanitize_text_field( wp_unslash( $_POST['qtms_font_family'] ) ) : '';
        $bg_color     = isset( $_POST['qtms_bg_color'] ) ? sanitize_text_field( wp_unslash( $_POST['qtms_bg_color'] ) ) : 'transparent';
        $schema       = isset( $_POST['qtms_schema'] ) ? 1 : 0;

        update_post_meta( $post_id, '_qtms_row_count', $row_count );
        update_post_meta( $post_id, '_qtms_row_gap', $row_gap );
        update_post_meta( $post_id, '_qtms_fade_width', $fade_width );
        update_post_meta( $post_id, '_qtms_font_size', $font_size );
        update_post_meta( $post_id, '_qtms_font_family', $font_family );
        update_post_meta( $post_id, '_qtms_bg_color', $bg_color );
        update_post_meta( $post_id, '_qtms_schema', $schema );

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nested array sanitized recursively below.
        $raw_rows   = isset( $_POST['qtms_rows'] ) && is_array( $_POST['qtms_rows'] ) ? wp_unslash( $_POST['qtms_rows'] ) : array();
        $clean_rows = array();

        for ( $r = 0; $r < 3; $r++ ) {
            $raw = isset( $raw_rows[ $r ] ) ? $raw_rows[ $r ] : array();

            $direction = isset( $raw['direction'] ) && in_array( $raw['direction'], array( 'left', 'right' ), true )
                ? $raw['direction'] : 'left';
            $speed = isset( $raw['speed'] ) ? absint( $raw['speed'] ) : 30;
            $speed = max( 1, min( 100, $speed ) );

            $separator_image = isset( $raw['separator_image'] ) ? absint( $raw['separator_image'] ) : 0;
            $separator_size  = isset( $raw['separator_size'] ) ? absint( $raw['separator_size'] ) : 8;

            $items     = array();
            $raw_items = isset( $raw['items'] ) && is_array( $raw['items'] ) ? $raw['items'] : array();

            foreach ( $raw_items as $raw_item ) {
                $text = isset( $raw_item['text'] ) ? sanitize_text_field( $raw_item['text'] ) : '';
                if ( '' === $text ) {
                    continue;
                }

                $border_width = isset( $raw_item['border_width'] ) ? absint( $raw_item['border_width'] ) : 0;
                $border_width = min( 20, $border_width );

                $items[] = array(
                    'text'          => $text,
                    'text_color'    => isset( $raw_item['text_color'] ) ? sanitize_hex_color( $raw_item['text_color'] ) ?: '#ffffff' : '#ffffff',
                    'bg_color'      => isset( $raw_item['bg_color'] ) ? sanitize_text_field( $raw_item['bg_color'] ) : '',
                    'border_color'  => isset( $raw_item['border_color'] ) ? sanitize_text_field( $raw_item['border_color'] ) : '',
                    'border_width'  => $border_width,
                    'border_radius' => isset( $raw_item['border_radius'] ) ? absint( $raw_item['border_radius'] ) : 0,
                    'link_url'      => isset( $raw_item['link_url'] ) ? esc_url_raw( $raw_item['link_url'] ) : '',
                    'link_target'   => isset( $raw_item['link_target'] ) && '_blank' === $raw_item['link_target'] ? '_blank' : '_self',
                    'icon_before'   => isset( $raw_item['icon_before'] ) ? absint( $raw_item['icon_before'] ) : 0,
                    'icon_after'    => isset( $raw_item['icon_after'] ) ? absint( $raw_item['icon_after'] ) : 0,
                    'spacing'       => isset( $raw_item['spacing'] ) ? absint( $raw_item['spacing'] ) : 40,
                );
            }

            $clean_rows[] = array(
                'direction'       => $direction,
                'speed'           => $speed,
                'separator_image' => $separator_image,
                'separator_size'  => $separator_size,
                'items'           => $items,
            );
        }

        update_post_meta( $post_id, '_qtms_rows', $clean_rows );
    }

    public static function admin_columns( $columns ) {
        $new = array();
        foreach ( $columns as $key => $label ) {
            $new[ $key ] = $label;
            if ( 'title' === $key ) {
                $new['qtms_shortcode'] = __( 'Shortcode', 'qaiyo-text-marquee-slider' );
                $new['qtms_rows']      = __( 'Rows', 'qaiyo-text-marquee-slider' );
            }
        }
        return $new;
    }

    public static function admin_column_content( $column, $post_id ) {
        if ( 'qtms_shortcode' === $column ) {
            echo '<code>[qaiyo_text_marquee id="' . esc_html( $post_id ) . '"]</code>';
        } elseif ( 'qtms_rows' === $column ) {
            echo esc_html( get_post_meta( $post_id, '_qtms_row_count', true ) ?: 1 );
        }
    }

    private static function get_default_row() {
        return array(
            'direction'       => 'left',
            'speed'           => 30,
            'separator_image' => 0,
            'separator_size'  => 8,
            'items'           => array( self::get_default_item() ),
        );
    }

    private static function get_default_item() {
        return array(
            'text'          => '',
            'text_color'    => '#ffffff',
            'bg_color'      => '',
            'border_color'  => '',
            'border_width'  => 0,
            'border_radius' => 0,
            'link_url'      => '',
            'link_target'   => '_self',
            'icon_before'   => 0,
            'icon_after'    => 0,
            'spacing'       => 40,
        );
    }
}

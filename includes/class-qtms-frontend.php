<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class QTMS_Frontend {

    private static $enqueued = false;

    public static function init() {
        add_shortcode( 'qaiyo_text_marquee', array( __CLASS__, 'render_shortcode' ) );
    }

    private static function enqueue_assets() {
        if ( self::$enqueued ) {
            return;
        }
        self::$enqueued = true;

        wp_enqueue_style(
            'qtms-frontend',
            QTMS_PLUGIN_URL . 'public/css/qtms-frontend.css',
            array(),
            QTMS_VERSION
        );

        wp_enqueue_script(
            'qtms-frontend',
            QTMS_PLUGIN_URL . 'public/js/qtms-frontend.js',
            array(),
            QTMS_VERSION,
            true
        );

        // Detect site fonts once per 24h and save via AJAX for admin use.
        $detected = get_option( 'qtms_detected_fonts', array() );
        $last     = get_option( 'qtms_detected_fonts_ts', 0 );
        if ( empty( $detected ) || ( time() - (int) $last ) > DAY_IN_SECONDS ) {
            wp_add_inline_script( 'qtms-frontend', self::get_font_detect_js() );
        }
    }

    public static function render_shortcode( $atts ) {
        $atts = shortcode_atts( array( 'id' => 0 ), $atts, 'qaiyo_text_marquee' );
        $id   = absint( $atts['id'] );

        if ( ! $id ) {
            return '';
        }

        $post = get_post( $id );
        if ( ! $post || 'qtms_slider' !== $post->post_type || 'publish' !== $post->post_status ) {
            return '';
        }

        self::enqueue_assets();

        $row_count    = (int) get_post_meta( $id, '_qtms_row_count', true ) ?: 1;
        $row_gap      = get_post_meta( $id, '_qtms_row_gap', true );
        $fade_width   = get_post_meta( $id, '_qtms_fade_width', true );
        $font_size    = get_post_meta( $id, '_qtms_font_size', true );
        $bg_color     = get_post_meta( $id, '_qtms_bg_color', true ) ?: 'transparent';
        $schema       = (int) get_post_meta( $id, '_qtms_schema', true );
        $rows         = get_post_meta( $id, '_qtms_rows', true );
        $font_family  = get_post_meta( $id, '_qtms_font_family', true ) ?: '';

        $row_gap      = '' !== $row_gap ? (int) $row_gap : 20;
        $fade_width   = '' !== $fade_width ? (int) $fade_width : 80;
        $font_size    = '' !== $font_size ? (int) $font_size : 16;

        if ( ! is_array( $rows ) || empty( $rows ) ) {
            return '';
        }

        ob_start();

        if ( $schema ) {
            self::render_schema( $rows, $row_count );
        }

        $font_css = '';
        if ( '' !== $font_family ) {
            $font_css = 'font-family:' . self::quote_font( $font_family ) . ', system-ui, sans-serif;';
        } else {
            $font_css = 'font-family:system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;';
        }

        $container_style = sprintf(
            '--qtms-row-gap:%dpx;--qtms-fade:%dpx;--qtms-bg:%s;font-size:%dpx;%s',
            $row_gap,
            $fade_width,
            $bg_color,
            $font_size,
            $font_css
        );
        ?>
        <div class="qtms-marquee"
            data-slider-id="<?php echo esc_attr( $id ); ?>"
            style="<?php echo esc_attr( $container_style ); ?>">
            <?php
            for ( $r = 0; $r < $row_count; $r++ ) {
                if ( ! isset( $rows[ $r ] ) || empty( $rows[ $r ]['items'] ) ) {
                    continue;
                }
                $row = $rows[ $r ];
                self::render_row( $row, $r );
            }
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function render_row( $row, $index ) {
        $direction = isset( $row['direction'] ) ? $row['direction'] : 'left';
        $speed     = (int) $row['speed'];
        $items     = $row['items'];

        $sep_id   = ! empty( $row['separator_image'] ) ? (int) $row['separator_image'] : 0;
        $sep_url  = $sep_id ? wp_get_attachment_url( $sep_id ) : '';
        $sep_size = ! empty( $row['separator_size'] ) ? (int) $row['separator_size'] : 8;
        ?>
        <div class="qtms-row" data-direction="<?php echo esc_attr( $direction ); ?>" data-speed="<?php echo (int) $speed; ?>" aria-label="<?php esc_attr_e( 'Scrolling content', 'qaiyo-text-marquee-slider' ); ?>">
            <div class="qtms-track">
                <ul class="qtms-track-copy">
                    <?php
                    foreach ( $items as $i => $item ) {
                        if ( $i > 0 && $sep_url ) {
                            printf(
                                '<li class="qtms-separator" aria-hidden="true" role="presentation" style="width:%1$dpx;height:%1$dpx;"><img src="%2$s" alt="" loading="lazy"></li>',
                                (int) $sep_size,
                                esc_url( $sep_url )
                            );
                        }
                        self::render_item( $item );
                    }
                    if ( $sep_url ) {
                        printf(
                            '<li class="qtms-separator" aria-hidden="true" role="presentation" style="width:%1$dpx;height:%1$dpx;"><img src="%2$s" alt="" loading="lazy"></li>',
                            (int) $sep_size,
                            esc_url( $sep_url )
                        );
                    }
                    ?>
                </ul>
            </div>
        </div>
        <?php
    }

    private static function render_item( $item ) {
        $text          = isset( $item['text'] ) ? $item['text'] : '';
        $color         = ! empty( $item['text_color'] ) ? $item['text_color'] : '#ffffff';
        $link          = isset( $item['link_url'] ) ? $item['link_url'] : '';
        $target        = isset( $item['link_target'] ) && '_blank' === $item['link_target'] ? '_blank' : '_self';
        $spacing       = isset( $item['spacing'] ) ? (int) $item['spacing'] : 40;
        $bg_color      = ! empty( $item['bg_color'] ) ? $item['bg_color'] : 'transparent';
        $border_color  = ! empty( $item['border_color'] ) ? $item['border_color'] : 'transparent';
        $border_width  = isset( $item['border_width'] ) ? (int) $item['border_width'] : 0;
        $border_radius = isset( $item['border_radius'] ) ? (int) $item['border_radius'] : 0;

        $icon_before_id  = ! empty( $item['icon_before'] ) ? (int) $item['icon_before'] : 0;
        $icon_after_id   = ! empty( $item['icon_after'] ) ? (int) $item['icon_after'] : 0;
        $icon_before_url = $icon_before_id ? wp_get_attachment_url( $icon_before_id ) : '';
        $icon_after_url  = $icon_after_id ? wp_get_attachment_url( $icon_after_id ) : '';

        $style = sprintf(
            'color:%s;--qtms-spacing:%dpx;background:%s;border:%dpx solid %s;border-radius:%dpx;',
            $color,
            $spacing,
            $bg_color,
            $border_width,
            $border_color,
            $border_radius
        );
        ?>
        <li class="qtms-item" style="<?php echo esc_attr( $style ); ?>">
            <?php if ( $icon_before_url ) : ?>
                <img class="qtms-icon qtms-icon-before" src="<?php echo esc_url( $icon_before_url ); ?>" alt="" loading="lazy">
            <?php endif; ?>
            <?php if ( $link ) : ?>
                <a href="<?php echo esc_url( $link ); ?>" target="<?php echo esc_attr( $target ); ?>"
                    <?php echo '_blank' === $target ? 'rel="noopener noreferrer"' : ''; ?>
                    class="qtms-text" style="color:inherit;">
                    <?php echo esc_html( $text ); ?>
                </a>
            <?php else : ?>
                <span class="qtms-text"><?php echo esc_html( $text ); ?></span>
            <?php endif; ?>
            <?php if ( $icon_after_url ) : ?>
                <img class="qtms-icon qtms-icon-after" src="<?php echo esc_url( $icon_after_url ); ?>" alt="" loading="lazy">
            <?php endif; ?>
        </li>
        <?php
    }

    private static function render_schema( $rows, $row_count ) {
        $list = array();
        $pos  = 1;
        for ( $r = 0; $r < $row_count; $r++ ) {
            if ( empty( $rows[ $r ]['items'] ) ) {
                continue;
            }
            foreach ( $rows[ $r ]['items'] as $item ) {
                if ( empty( $item['text'] ) ) {
                    continue;
                }
                $entry = array(
                    '@type'    => 'ListItem',
                    'position' => $pos++,
                    'name'     => wp_strip_all_tags( $item['text'] ),
                );
                if ( ! empty( $item['link_url'] ) ) {
                    $entry['url'] = esc_url_raw( $item['link_url'] );
                }
                $list[] = $entry;
            }
        }

        if ( empty( $list ) ) {
            return;
        }

        $data = array(
            '@context'        => 'https://schema.org',
            '@type'           => 'ItemList',
            'itemListElement' => $list,
        );

        echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
    }

    /**
     * Quote a font name for safe CSS injection.
     * Single-name fonts with spaces get wrapped in double quotes.
     */
    private static function quote_font( $font ) {
        $font = trim( $font );
        if ( '' === $font ) {
            return '';
        }
        // Already a stack (comma-separated) — leave as-is.
        if ( strpos( $font, ',' ) !== false ) {
            return $font;
        }
        // Already quoted — leave as-is.
        $first = substr( $font, 0, 1 );
        if ( '"' === $first || "'" === $first ) {
            return $font;
        }
        // Contains space — needs quoting.
        if ( strpos( $font, ' ' ) !== false ) {
            return '"' . $font . '"';
        }
        return $font;
    }

    /**
     * Inline JS that detects computed font-family for body, h1, h2, h3
     * and sends them to the server via AJAX (runs once per 24h).
     */
    private static function get_font_detect_js() {
        $ajax_url = admin_url( 'admin-ajax.php' );
        return <<<JS
(function(){
    function first(ff){
        if(!ff) return '';
        return ff.split(',')[0].trim().replace(/^["']|["']$/g,'');
    }
    function detect(){
        var b=document.body;
        if(!b) return;
        var tags={body:b,h1:null,h2:null,h3:null};
        ['h1','h2','h3'].forEach(function(t){
            var el=document.querySelector(t);
            if(!el){el=document.createElement(t);el.style.position='absolute';el.style.visibility='hidden';el.textContent='X';b.appendChild(el);tags[t]=el;tags[t]._temp=true;}
            else{tags[t]=el;}
        });
        var fonts={};
        Object.keys(tags).forEach(function(k){
            if(tags[k]) fonts[k]=first(getComputedStyle(tags[k]).fontFamily);
        });
        ['h1','h2','h3'].forEach(function(t){if(tags[t]&&tags[t]._temp)tags[t].remove();});
        var fd=new FormData();
        fd.append('action','qtms_detect_fonts');
        Object.keys(fonts).forEach(function(k){fd.append('fonts['+k+']',fonts[k]);});
        navigator.sendBeacon?navigator.sendBeacon('{$ajax_url}',fd):
            fetch('{$ajax_url}',{method:'POST',body:fd,keepalive:true});
    }
    if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',detect);
    else setTimeout(detect,100);
})();
JS;
    }
}

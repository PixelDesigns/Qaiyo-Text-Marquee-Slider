# Qaiyo Text Marquee Slider

WordPress shortcode plugin for infinite scrolling text marquees with per-row control, badge-style elements, font detection, and full page builder compatibility.

**Version:** 1.0
**Author:** Qaiyo by PixelDesigns
**License:** GPL-2.0-or-later
**Requires:** WordPress 5.8+, PHP 7.4+
**Slug:** qaiyo-text-marquee-slider

---

## What it does

Place `[qaiyo_text_marquee id="..."]` anywhere on your site. Each marquee supports up to three rows, each with its own scroll direction, speed, separator image, and items. Items carry individual color, border, and icon settings. The plugin reads your theme's fonts and offers them in the admin.

## Features

| Feature | Details |
|---|---|
| Rows | 1–3 parallel rows per marquee |
| Per-row settings | Direction (L/R), speed, separator image |
| Per-element settings | Text color, background, border (color/width/radius), gap |
| Icons | Image before and/or after each element |
| Item ordering | Drag and drop within each row |
| Font detection | Reads active theme H1/H2/H3/body fonts |
| Global controls | Font size, edge fade effect |
| Loop | Seamless infinite scroll, hover pause |
| Responsive | Adjusts to any viewport width |
| SEO | Static HTML copy for crawlers, JS-only clones |
| Schema | Optional schema.org ItemList markup |
| Accessibility | aria-hidden + inert + tabindex on clones, prefers-reduced-motion |
| Shortcode | `[qaiyo_text_marquee id="..."]` |
| Page builders | Bricks, Elementor, Divi, Avada, Gutenberg |
| Multilingual | WPML, Polylang compatible |

## Installation

1. Upload the `qaiyo-text-marquee-slider` folder to `/wp-content/plugins/`
2. Activate through **Plugins → Installed Plugins**
3. Go to **Qaiyo Marquee** in the admin menu
4. Create a marquee, configure rows and items, copy the shortcode
5. Paste `[qaiyo_text_marquee id="..."]` wherever you want it

## Shortcode

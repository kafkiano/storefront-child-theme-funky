<?php
/**
 * Load cunstom stylesheets
 */
add_action('wp_enqueue_scripts', 'funky_enqueue_styles');
function funky_enqueue_styles() {
    // Parent style (Storefront)
    wp_enqueue_style('storefront-style', get_template_directory_uri() . '/style.css');

    // Child style (depends on parent style)
    wp_enqueue_style('storefront-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array('storefront-style'),
        wp_get_theme()->get('Version')
    );

    // Inject color variables as inline CSS
    $colors = funky_get_cached_theme_colors();
    $variable_map = funky_get_color_variable_map();

    if (!empty($colors) && !empty($variable_map)) {
        $css = ':root {';
        foreach ($variable_map as $php_key => $css_var) {
            if (isset($colors[$php_key])) {
                $css .= sprintf('--storefront-%s: %s;', $css_var, $colors[$php_key]);
            }
        }
        $css .= '}';
        wp_add_inline_style('storefront-child-style', $css);
    }

    // Off-canvas menu JavaScript
    wp_enqueue_script(
        'storefront-child-off-canvas',
        get_stylesheet_directory_uri() . '/assets/js/off-canvas-menu.js',
        array(), // no dependencies
        wp_get_theme()->get('Version'),
        true // load in footer
    );
}

// Tell WP you support editor styles
add_action( 'after_setup_theme', function() {
  // Registers editor-style.css in your child theme root
  add_theme_support( 'editor-styles' );
  add_editor_style( 'editor-style.css' );
} );


/**
 * Disable the Search Box in the Storefront Theme
 */
add_action( 'init', 'jk_remove_storefront_header_search' );
function jk_remove_storefront_header_search() {
    remove_action( 'storefront_header', 'storefront_product_search', 40 );
}

/**
 * Remove first col-full container hooks to unify header structure
 */
add_action( 'init', 'funky_remove_first_col_full' );
function funky_remove_first_col_full() {
    remove_action( 'storefront_header', 'storefront_header_container', 0 );
    remove_action( 'storefront_header', 'storefront_header_container_close', 41 );
}

/**
 * Remove Breadcrumbs
 */
add_action( 'init', 'bbloomer_remove_storefront_breadcrumbs' );
function bbloomer_remove_storefront_breadcrumbs() {
   remove_action( 'storefront_before_content', 'woocommerce_breadcrumb', 10 );
}

/**
 * Move site branding into navigation wrapper for three-column header
 */
add_action( 'init', 'funky_move_site_branding' );
function funky_move_site_branding() {
    // Remove original site branding hook
    remove_action( 'storefront_header', 'storefront_site_branding', 20 );
    // Remove primary navigation wrapper hooks to avoid duplication
    remove_action( 'storefront_header', 'storefront_primary_navigation_wrapper', 42 );
    remove_action( 'storefront_header', 'storefront_primary_navigation_wrapper_close', 68 );
    // Remove default primary navigation hook and replace with custom
    remove_action( 'storefront_header', 'storefront_primary_navigation', 50 );
    add_action( 'storefront_header', 'funky_primary_navigation', 50 );
}

/**
 * Three-column header wrapper functions
 */
function funky_three_column_header_wrapper() {
    echo '<div class="storefront-primary-navigation"><div class="col-full">';
    echo '<div class="header-three-column-grid">';
    echo '<div class="header-column header-column-left">';
    // Left column remains open for menu toggle at priority 50
}

function funky_close_left_column() {
    echo '</div>'; // close left column
    echo '<div class="header-column header-column-center">';
    // Center column open for branding at priority 52
}

function funky_close_center_column() {
    echo '</div>'; // close center column
    echo '<div class="header-column header-column-right">';
    // Right column open for cart at priority 60
}

function funky_close_right_column() {
    echo '</div>'; // close right column
    echo '</div>'; // close header-three-column-grid
    echo '</div></div>'; // close col-full and storefront-primary-navigation
}

// Add custom three-column header structure
add_action( 'storefront_header', 'funky_three_column_header_wrapper', 42 );
add_action( 'storefront_header', 'funky_close_left_column', 51 );
add_action( 'storefront_header', 'funky_close_center_column', 53 );
add_action( 'storefront_header', 'funky_close_right_column', 61 );
// Re-add site branding at priority 52 (after center column opens)
add_action( 'storefront_header', 'storefront_site_branding', 52 );

/**
 * Remove the Storefront Theme Copyright Link “Built with Storefront”
 */
add_filter( 'storefront_credit_link', '__return_false' );

/**
 * Allow svg file uploads
 */
function wpdocs_add_svg( $mimes ) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
}
add_filter( 'upload_mimes', 'wpdocs_add_svg' );

/**
 *  Smart SEO Meta Tags with automation
 *
 * Override behavior: Add custom fields:
 *    og_title = "My Custom Share Title"
 *    og_description = "My custom share description"
 *    og_image = "https://example.com/custom-image.jpg"
 *    meta_description = "My SEO description"
 */
 // Get content type
function get_og_type() {
     if (is_front_page() || is_home()) {
         return 'website';
     } elseif (is_singular('post')) {
         return 'article';
     } elseif (is_singular('product')) {
         return 'product'; // If you have WooCommerce
     } elseif (is_author()) {
         return 'profile';
     } elseif (is_singular()) {
         // For pages (about, contact, etc.) - use 'website'
         return 'website';
     } else {
         // Fallback for archives, search, etc.
         return 'website';
     }
}

// Add seo tags
function add_smart_seo_tags() {
    if (!is_singular()) return;

    global $post;

    // Get basic info
    $title = get_the_title();
    $site_name = get_bloginfo('name');
    $permalink = get_permalink();
    $meta_desc = get_post_meta($post->ID, 'meta_description', true);

    // Determine OG type
    $og_type = get_og_type();

    // Open Graph Title
    $og_title = get_post_meta($post->ID, 'og_title', true);
    if (empty($og_title)) {
        $og_title = $title . ' - ' . $site_name;
    }

    // Open Graph Description
    $og_description = get_post_meta($post->ID, 'og_description', true);
    if (empty($og_description)  && !empty($meta_desc)) {
        $og_description = $meta_desc;
    }

    // Open Graph Image
    $og_image = get_post_meta($post->ID, 'og_image', true);
    if (empty($og_image) && has_post_thumbnail($post->ID)) {
        $og_image = get_the_post_thumbnail_url($post->ID, 'large');
    }

    // Output Open Graph Tags
    echo '<meta property="og:title" content="' . esc_attr($og_title) . '" />' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($og_description) . '" />' . "\n";
    echo '<meta property="og:image" content="' . esc_url($og_image) . '" />' . "\n";
    echo '<meta property="og:url" content="' . esc_url($permalink) . '" />' . "\n";
    echo '<meta property="og:type" content="' . esc_attr($og_type) . '" />' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '" />' . "\n";

    // Twitter Card Tags
    echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($og_title) . '" />' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr($og_description) . '" />' . "\n";
    echo '<meta name="twitter:image" content="' . esc_url($og_image) . '" />' . "\n";

    // Output Regular Meta Description
    if (!empty($meta_desc)) {
        echo '<meta name="description" content="' . esc_attr($meta_desc) . '" />' . "\n";
    }

}
add_action('wp_head', 'add_smart_seo_tags');


// Add Article Schema.org markup for blog posts
function add_article_schema() {
    if (!is_singular('post')) return;

    global $post;

    $author_name = get_the_author_meta('display_name', $post->post_author);

    $description = get_post_meta($post->ID, 'meta_description', true);
    if (empty($description)) {
        $description = has_excerpt() ? get_the_excerpt() : wp_trim_words($post->post_content, 30);
    }

    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => get_the_title(),
        'description' => $description,
        'datePublished' => get_the_date('c'),
        'dateModified' => get_the_modified_date('c'),
        'author' => array(
            '@type' => 'Person',
            'name' => $author_name
        ),
        'publisher' => array(
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
            'logo' => get_site_logo_schema() // Improved logo function
        ),
        'mainEntityOfPage' => array(
            '@type' => 'WebPage',
            '@id' => get_permalink()
        )
    );

    // IMPROVED: Get actual image dimensions
    if (has_post_thumbnail($post->ID)) {
        $image_data = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'full');
        if ($image_data) {
            list($src, $width, $height) = $image_data;
            $schema['image'] = array(
                '@type' => 'ImageObject',
                'url' => $src,
                'width' => $width,
                'height' => $height
            );
        }
    }

    echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
}
add_action('wp_head', 'add_article_schema');

// Get dimensions of image
function get_site_logo_schema() {
    $custom_logo_id = get_theme_mod('custom_logo');
    if ($custom_logo_id) {
        $logo_data = wp_get_attachment_image_src($custom_logo_id, 'full');
        if ($logo_data) {
            list($src, $width, $height) = $logo_data;
            return array(
                '@type' => 'ImageObject',
                'url' => $src,
                'width' => $width,
                'height' => $height
            );
        }
    }
}

/**
 * Returns Storefront color settings with proper # prefix.
 *
 * @return array {
 *     @type string $accent_color           Accent/link color (with #).
 *     @type string $background_color       Background color (with #).
 *     @type string $header_link_color      Navigation link color (with #).
 *     @type string $heading_color          Heading color (with #).
 *     @type string $text_color             Text color (with #).
 *     @type string $hero_heading_color     Hero heading color (with #).
 *     @type string $hero_text_color        Hero text color (with #).
 *     @type string $header_background_color Header background color (with #).
 *     @type string $header_text_color      Header text color (with #).
 *     @type string $footer_background_color Footer background color (with #).
 *     @type string $footer_heading_color   Footer heading color (with #).
 *     @type string $footer_text_color      Footer text color (with #).
 *     @type string $footer_link_color      Footer link color (with #).
 *     @type string $button_background_color Button background color (with #).
 *     @type string $button_text_color      Button text color (with #).
 *     @type string $button_alt_background_color Alternate button background (with #).
 *     @type string $button_alt_text_color  Alternate button text (with #).
 * }
 */
function funky_get_theme_colors() {
	// Use Storefront's built-in function if available
	if (function_exists('storefront_get_content_background_color')) {
		$background_color = storefront_get_content_background_color();
	} else {
		$bg_value = get_theme_mod('background_color', 'ffffff');
		$background_color = $bg_value ? '#' . ltrim($bg_value, '#') : '#ffffff';
	}
	
	// Map Storefront theme mods to our array with descriptive keys
	$colors = array(
		'accent_color'           => get_theme_mod('storefront_accent_color', funky_get_storefront_default('storefront_accent_color')),
		'background_color'       => $background_color,
		'header_link_color'      => get_theme_mod('storefront_header_link_color', funky_get_storefront_default('storefront_header_link_color')),
		'heading_color'          => get_theme_mod('storefront_heading_color', funky_get_storefront_default('storefront_heading_color')),
		'text_color'             => get_theme_mod('storefront_text_color', funky_get_storefront_default('storefront_text_color')),
		'hero_heading_color'     => get_theme_mod('storefront_hero_heading_color', funky_get_storefront_default('storefront_hero_heading_color')),
		'hero_text_color'        => get_theme_mod('storefront_hero_text_color', funky_get_storefront_default('storefront_hero_text_color')),
		'header_background_color' => get_theme_mod('storefront_header_background_color', funky_get_storefront_default('storefront_header_background_color')),
		'header_text_color'      => get_theme_mod('storefront_header_text_color', funky_get_storefront_default('storefront_header_text_color')),
		'footer_background_color' => get_theme_mod('storefront_footer_background_color', funky_get_storefront_default('storefront_footer_background_color')),
		'footer_heading_color'   => get_theme_mod('storefront_footer_heading_color', funky_get_storefront_default('storefront_footer_heading_color')),
		'footer_text_color'      => get_theme_mod('storefront_footer_text_color', funky_get_storefront_default('storefront_footer_text_color')),
		'footer_link_color'      => get_theme_mod('storefront_footer_link_color', funky_get_storefront_default('storefront_footer_link_color')),
		'button_background_color' => get_theme_mod('storefront_button_background_color', funky_get_storefront_default('storefront_button_background_color')),
		'button_text_color'      => get_theme_mod('storefront_button_text_color', funky_get_storefront_default('storefront_button_text_color')),
		'button_alt_background_color' => get_theme_mod('storefront_button_alt_background_color', funky_get_storefront_default('storefront_button_alt_background_color')),
		'button_alt_text_color'  => get_theme_mod('storefront_button_alt_text_color', funky_get_storefront_default('storefront_button_alt_text_color')),
	);
	
	// Ensure all values have # prefix
	foreach ($colors as $key => $value) {
		if ($value && strpos($value, '#') !== 0) {
			$colors[$key] = '#' . $value;
		}
	}
	
	/**
	 * Filter the theme colors array.
	 *
	 * @param array $colors Associative array of color values.
	 */
	return apply_filters('funky_theme_colors', $colors);
}

/**
	* Get Storefront theme colors with transient caching.
	*
	* Caches colors for 12 hours to reduce database queries.
	* Automatically clears cache when theme mods change.
	*
	* @return array Associative array of color values.
	*/
function funky_get_cached_theme_colors() {
	   $cache_key = 'funky_theme_colors_' . get_stylesheet();
	   $cached = get_transient($cache_key);
	   
	   if (false !== $cached) {
	       return $cached;
	   }
	   
	   $colors = funky_get_theme_colors();
	   
	   // Cache for 12 hours
	   set_transient($cache_key, $colors, 12 * HOUR_IN_SECONDS);
	   
	   return $colors;
}

/**
	* Clear color cache when theme mods change.
	*/
add_action('customize_save_after', 'funky_clear_color_cache');
function funky_clear_color_cache() {
	   $cache_key = 'funky_theme_colors_' . get_stylesheet();
	   delete_transient($cache_key);
}

/**
 * Map PHP color keys to CSS variable names.
 *
 * Provides abstraction layer between PHP array structure
 * and CSS custom property names.
 *
 * @return array Associative array of PHP_key => CSS_variable_name
 */
function funky_get_color_variable_map() {
    return array(
        'accent_color'           => 'accent-color',
        'background_color'       => 'background-color',
        'header_link_color'      => 'header-link-color',
        'heading_color'          => 'heading-color',
        'text_color'             => 'text-color',
        'hero_heading_color'     => 'hero-heading-color',
        'hero_text_color'        => 'hero-text-color',
        'header_background_color' => 'header-background-color',
        'header_text_color'      => 'header-text-color',
        'footer_background_color' => 'footer-background-color',
        'footer_heading_color'   => 'footer-heading-color',
        'footer_text_color'      => 'footer-text-color',
        'footer_link_color'      => 'footer-link-color',
        'button_background_color' => 'button-background-color',
        'button_text_color'      => 'button-text-color',
        'button_alt_background_color' => 'button-alt-background-color',
        'button_alt_text_color'  => 'button-alt-text-color',
    );
}

/**
 * Get Storefront's default color values.
 *
 * Uses Storefront's actual default constants instead of hardcoded values.
 *
 * @param string $color_key The color key to get default for.
 * @return string Default hex color value.
 */
function funky_get_storefront_default($color_key) {
    $defaults = array(
        'storefront_accent_color'            => '#7f54b3',
        'storefront_heading_color'           => '#333333',
        'storefront_text_color'              => '#6d6d6d',
        'storefront_hero_heading_color'      => '#000000',
        'storefront_hero_text_color'         => '#000000',
        'storefront_header_background_color' => '#ffffff',
        'storefront_header_text_color'       => '#404040',
        'storefront_header_link_color'       => '#333333',
        'storefront_footer_background_color' => '#f0f0f0',
        'storefront_footer_heading_color'    => '#333333',
        'storefront_footer_text_color'       => '#6d6d6d',
        'storefront_footer_link_color'       => '#333333',
        'storefront_button_background_color' => '#eeeeee',
        'storefront_button_text_color'       => '#333333',
        'storefront_button_alt_background_color' => '#333333',
        'storefront_button_alt_text_color'   => '#ffffff',
    );
    
    return isset($defaults[$color_key]) ? $defaults[$color_key] : '#ffffff';
}

/**
 * Override Storefront's primary navigation to output only handheld menu
 * when off-canvas mode is active, eliminating duplicate menu elements
 */
function funky_primary_navigation() {
    ?>
    <nav id="site-navigation" class="main-navigation" role="navigation" aria-label="Primary Navigation">
        <button id="site-navigation-menu-toggle" class="menu-toggle" aria-controls="site-navigation" aria-expanded="false">
            <i class="fa fa-bars" aria-hidden="true"></i>
            <span class="screen-reader-text"><?php echo esc_html( apply_filters( 'storefront_menu_toggle_text', __( 'Menu', 'storefront' ) ) ); ?></span>
        </button>
        <?php
        // Off-canvas menu structure (keeps existing off-canvas implementation)
        wp_nav_menu(
            array(
                'theme_location'  => 'handheld',
                'container_class' => 'handheld-navigation',
                'menu_class'      => 'off-canvas-menu',
            )
        );
        ?>
        <div class="off-canvas-overlay" aria-hidden="false" role="presentation"></div>
    </nav>
    <?php
}

/*
	*Product Carousel Slider Shortcode
 *[woo-slider card="4" num="10" sale_badge="on" rating="on" description="off" check_stock="on" id="" on_sale="off" cats=""  offset="" type="" ]
 *https://redpishi.com/wordpress-tutorials/product-carousel-slider-shortcode/
 */
function woo_slider_shortcode($atts) {
	if (!function_exists('is_woocommerce')) {
		return;
	}

    // Parse shortcode attributes
    $atts = shortcode_atts(array(
        'num'         => 10,
        'sale_badge'  => 'on',
        'offset'      => 0,
        'rating'      => 'on',
        'description' => 'off',
        'check_stock' => 'on',
        'on_sale'     => 'off',
        'cats'        => '',
        'tags'        => '',
        'type'        => '',
        'id'          => '',
        'card'        => '4',
        'auto_paly'   => 'off',
        'theme'		  => '1',
        'card-details'=> 'on',

    ), $atts, 'woo-slider');

    $card = $atts["card"];
    $theme = $atts["theme"];
    if ( $atts['auto_paly'] == "on" ) { $auto_paly = true; } else { $auto_paly = "false";}
    static $woo_slider_id = 1;

    // Start building WP_Query arguments
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => intval($atts['num']),
        'offset'         => intval($atts['offset']),
        'post_status'    => 'publish',
    );

    // Default sorting - newest first
    $args['orderby'] = 'date';
    $args['order'] = 'DESC';

    // Handle specific product IDs
    if (!empty($atts['id']) && $atts['id'] == "6969" ) {
        $product_ids = array_map('intval', explode(',', $atts['id']));
        $args['post__in'] = $product_ids;
        $args['orderby'] = 'post__in'; // Maintain the order of IDs
    } else {
        // Handle stock status
        if ($atts['check_stock'] === 'on') {
            $args['meta_query'][] = array(
                'key'     => '_stock_status',
                'value'   => 'outofstock',
                'compare' => 'NOT IN',
            );
        }

        // Handle sale items - FIX: Use WooCommerce's built-in function instead of custom meta query
        if ($atts['on_sale'] === 'on') {
            $on_sale_ids = wc_get_product_ids_on_sale();
            if (!empty($on_sale_ids)) {
                $args['post__in'] = $on_sale_ids;
            } else {
                // If no products are on sale, return empty results
                $args['post__in'] = array(0);
            }
        }

        // Handle categories and tags sorting
        $tax_based_ordering = false;

        // Handle categories priority sorting
        if (!empty($atts['cats'])) {
            $cat_ids = array_map('intval', explode(',', $atts['cats']));
            $args['tax_query'][] = array(
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $cat_ids,
            );
            $tax_based_ordering = true;
        }

        // Handle tags priority sorting
        if (!empty($atts['tags'])) {
            $tag_ids = array_map('intval', explode(',', $atts['tags']));
            $args['tax_query'][] = array(
                'taxonomy' => 'product_tag',
                'field'    => 'term_id',
                'terms'    => $tag_ids,
            );
            $tax_based_ordering = true;
        }

        // Handle tax_query relation if both categories and tags are specified
        if (!empty($atts['cats']) && !empty($atts['tags'])) {
            $args['tax_query']['relation'] = 'AND';
        }

        // Handle product type (featured or bestselling)
        if ($atts['type'] === 'featured') {
            $args['tax_query'][] = array(
                'taxonomy' => 'product_visibility',
                'field'    => 'name',
                'terms'    => 'featured',
            );
        } elseif ($atts['type'] === 'bestselling') {
            $args['meta_key'] = 'total_sales';
            $args['orderby']  = 'meta_value_num';
            $args['order']    = 'DESC';
        }
    }

    // Run the query
    $products = new WP_Query($args);

    // Start output buffering
    ob_start();

    // Custom sorting for categories or tags priority
    $sorted_posts = array();

    if ((!empty($atts['cats']) || !empty($atts['tags'])) && $products->have_posts()) {
        $wp_query_args = $args;

        // If using categories for priority sorting
        if (!empty($atts['cats'])) {
            $cat_ids = array_map('intval', explode(',', $atts['cats']));

            // Get products for each category separately in the specified order
            foreach ($cat_ids as $cat_id) {
                $cat_query_args = $wp_query_args;

                // Override the tax query for this specific category
                $cat_query_args['tax_query'] = array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field'    => 'term_id',
                        'terms'    => $cat_id,
                    )
                );

                // If we need to maintain stock and sale filters
                if (!empty($wp_query_args['meta_query'])) {
                    $cat_query_args['meta_query'] = $wp_query_args['meta_query'];
                }

                // If we're filtering by on_sale products, maintain that filter
                if (isset($wp_query_args['post__in']) && $atts['on_sale'] === 'on') {
                    $cat_query_args['post__in'] = $wp_query_args['post__in'];
                }

                // Don't limit posts for individual category queries
                $cat_query_args['posts_per_page'] = -1;
                $cat_query_args['fields'] = 'ids'; // Just get IDs to be efficient

                // Get all products from this category
                $cat_products = get_posts($cat_query_args);

                // Add these product IDs to our sorted array
                $sorted_posts = array_merge($sorted_posts, $cat_products);
            }
        }
        // If using tags for priority sorting
        elseif (!empty($atts['tags'])) {
            $tag_ids = array_map('intval', explode(',', $atts['tags']));

            // Get products for each tag separately in the specified order
            foreach ($tag_ids as $tag_id) {
                $tag_query_args = $wp_query_args;

                // Override the tax query for this specific tag
                $tag_query_args['tax_query'] = array(
                    array(
                        'taxonomy' => 'product_tag',
                        'field'    => 'term_id',
                        'terms'    => $tag_id,
                    )
                );

                // If we need to maintain stock and sale filters
                if (!empty($wp_query_args['meta_query'])) {
                    $tag_query_args['meta_query'] = $wp_query_args['meta_query'];
                }

                // If we're filtering by on_sale products, maintain that filter
                if (isset($wp_query_args['post__in']) && $atts['on_sale'] === 'on') {
                    $tag_query_args['post__in'] = $wp_query_args['post__in'];
                }

                // Don't limit posts for individual tag queries
                $tag_query_args['posts_per_page'] = -1;
                $tag_query_args['fields'] = 'ids'; // Just get IDs to be efficient

                // Get all products from this tag
                $tag_products = get_posts($tag_query_args);

                // Add these product IDs to our sorted array
                $sorted_posts = array_merge($sorted_posts, $tag_products);
            }
        }

        // Remove duplicates while preserving order
        $sorted_posts = array_unique($sorted_posts);

        // Limit to the requested number of products
        $sorted_posts = array_slice($sorted_posts, intval($atts['offset']), intval($atts['num']));

        // Now create a new query with these exact product IDs in the correct order
        if (!empty($sorted_posts)) {
            $args = array(
                'post_type'      => 'product',
                'posts_per_page' => -1,
                'post__in'       => $sorted_posts,
                'orderby'        => 'post__in', // Maintain our custom order
            );

            // Replace the original query
            $products = new WP_Query($args);
        }
    }

    if ($products->have_posts()) {
        echo '<div class="blaze-slider" id="woo_slider'.$woo_slider_id.'">
        <div class="my-structure">
    		<span class="blaze-prev" aria-label="Go to previous slide">
        		<svg width="40" height="40" version="1.1" viewBox="0 0 10.583 10.583" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="5.2964" cy="5.2964" r="4.8304" style="fill:var(--storefront-background-color, #fff);paint-order:stroke fill markers;stroke-width:.64651"/>
                    <path d="m4.7096 3.4245 1.8579 1.8341-1.8579 1.9147" style="fill:none;paint-order:stroke fill markers;stroke-linecap:round;stroke-linejoin:round;stroke-width:.64651;stroke:var(--storefront-text-color, #1a1a1a)"/>
                </svg>
            </span>
    		<span class="blaze-next" aria-label="Go to next slide">
        		<svg width="40" height="40" version="1.1" viewBox="0 0 10.583 10.583" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="5.2964" cy="5.2964" r="4.8304" style="fill:var(--storefront-background-color, #fff);paint-order:stroke fill markers;stroke-width:.64651"/>
                    <path d="m4.7096 3.4245 1.8579 1.8341-1.8579 1.9147" style="fill:none;paint-order:stroke fill markers;stroke-linecap:round;stroke-linejoin:round;stroke-width:.64651;stroke:var(--storefront-text-color, #1a1a1a)"/>
                </svg>
            </span>
        </div>
        <div class="blaze-container">
            <div class="blaze-track-container">
        <div class="woo-slider blaze-track">';

        while ($products->have_posts()) {
            $products->the_post();
            global $product;

            if (!$product || !$product->is_visible()) {
                continue;
            }

            // Product card HTML
            ?>
            <div class="post_card">
                <a href="<?php echo esc_url(get_permalink()); ?>" style="position: relative;">
                    <?php
                    // Display product thumbnail
                    echo woocommerce_get_product_thumbnail();

                    // Show sale badge if enabled and product is on sale
                    if ($atts['sale_badge'] === 'on' && $product->is_on_sale()) {
                        echo '<span class="onsale">' . esc_html__('Sale!', 'woocommerce') . '</span>';
                    }
                    ?>
                </a>
            <?php
                if ($atts['card-details'] === 'on') {

                    echo '<span class="wwo_card_details">';
                        echo '<a class="p_title" href="' . esc_url(get_permalink()) . '">' . get_the_title() . '</a>';

                        // Rating
                        if ($atts['rating'] === 'on' && $product->get_average_rating() > 0) {
                            echo '<span class="woocommerce">' . wc_get_rating_html($product->get_average_rating()) . '</span>';
                        }

                        // Description
                        if ($atts['description'] === 'on') {
                            echo '<p>' . wp_trim_words(get_the_excerpt(), 15, '...') . '</p>';
                        }

                        // Price
                        echo '<span class="price"><div>' . $product->get_price_html() . '</div></span>';

                        // Add to Cart Button
                        echo sprintf(
                            '<a href="%s" data-quantity="1" class="button add_to_cart_button ajax_add_to_cart" data-product_id="%s" rel="nofollow">%s</a>',
                            esc_url($product->add_to_cart_url()),
                            esc_attr($product->get_id()),
                            esc_html($product->add_to_cart_text())
                        );
                    echo '</span>';
                }?>
            </div>
    <?php
        }

        echo '</div></div></div><!--<div class="blaze-pagination"></div>--></div>'; // Close .woo-slider

        if ( $woo_slider_id ==1 ) {
			add_action("wp_footer", function(){

	?>

		<script>
    		var BlazeSlider=function(){"use strict";const t="start";class e{constructor(t,e){this.config=e,this.totalSlides=t,this.isTransitioning=!1,n(this,t,e)}next(t=1){if(this.isTransitioning||this.isStatic)return;const{stateIndex:e}=this;let n=0,i=e;for(let e=0;e<t;e++){const t=this.states[i];n+=t.next.moveSlides,i=t.next.stateIndex}return i!==e?(this.stateIndex=i,[e,n]):void 0}prev(t=1){if(this.isTransitioning||this.isStatic)return;const{stateIndex:e}=this;let n=0,i=e;for(let e=0;e<t;e++){const t=this.states[i];n+=t.prev.moveSlides,i=t.prev.stateIndex}return i!==e?(this.stateIndex=i,[e,n]):void 0}}function n(t,e,n){t.stateIndex=0,function(t){const{slidesToScroll:e,slidesToShow:n}=t.config,{totalSlides:i,config:s}=t;if(i<n&&(s.slidesToShow=i),!(i<=n)&&(e>n&&(s.slidesToScroll=n),i<e+n)){const t=i-n;s.slidesToScroll=t}}(t),t.isStatic=e<=n.slidesToShow,t.states=function(t){const{totalSlides:e}=t,{loop:n}=t.config,i=function(t){const{slidesToShow:e,slidesToScroll:n,loop:i}=t.config,{isStatic:s,totalSlides:o}=t,r=[],a=o-1;for(let t=0;t<o;t+=n){const n=t+e-1;if(n>a){if(!i){const t=a-e+1,n=r.length-1;(0===r.length||r.length>0&&r[n][0]!==t)&&r.push([t,a]);break}{const e=n-o;r.push([t,e])}}else r.push([t,n]);if(s)break}return r}(t),s=[],o=i.length-1;for(let t=0;t<i.length;t++){let r,a;n?(r=t===o?0:t+1,a=0===t?o:t-1):(r=t===o?o:t+1,a=0===t?0:t-1);const l=i[t][0],c=i[r][0],d=i[a][0];let u=c-l;c<l&&(u+=e);let f=l-d;d>l&&(f+=e),s.push({page:i[t],next:{stateIndex:r,moveSlides:u},prev:{stateIndex:a,moveSlides:f}})}return s}(t)}function i(t){if(t.onSlideCbs){const e=t.states[t.stateIndex],[n,i]=e.page;t.onSlideCbs.forEach((e=>e(t.stateIndex,n,i)))}}function s(t){t.offset=-1*t.states[t.stateIndex].page[0],o(t),i(t)}function o(t){const{track:e,offset:n,dragged:i}=t;e.style.transform=0===n?`translate3d(${i}px,0px,0px)`:`translate3d(  calc( ${i}px + ${n} * (var(--slide-width) + ${t.config.slideGap})),0px,0px)`}function r(t){t.track.style.transitionDuration=`${t.config.transitionDuration}ms`}function a(t){t.track.style.transitionDuration="0ms"}const l=10,c=()=>"ontouchstart"in window;function d(t){const e=this,n=e.slider;if(!n.isTransitioning){if(n.dragged=0,e.isScrolled=!1,e.startMouseClientX="touches"in t?t.touches[0].clientX:t.clientX,!("touches"in t)){(t.target||e).setPointerCapture(t.pointerId)}a(n),p(e,"addEventListener")}}function u(t){const e=this,n="touches"in t?t.touches[0].clientX:t.clientX,i=e.slider.dragged=n-e.startMouseClientX,s=Math.abs(i);s>5&&(e.slider.isDragging=!0),s>15&&t.preventDefault(),e.slider.dragged=i,o(e.slider),!e.isScrolled&&e.slider.config.loop&&i>l&&(e.isScrolled=!0,e.slider.prev())}function f(){const t=this,e=t.slider.dragged;t.slider.isDragging=!1,p(t,"removeEventListener"),t.slider.dragged=0,o(t.slider),r(t.slider),t.isScrolled||(e<-1*l?t.slider.next():e>l&&t.slider.prev())}const h=t=>t.preventDefault();function p(t,e){t[e]("contextmenu",f),c()?(t[e]("touchend",f),t[e]("touchmove",u)):(t[e]("pointerup",f),t[e]("pointermove",u))}const g={slideGap:"20px",slidesToScroll:1,slidesToShow:1,loop:!0,enableAutoplay:!1,stopAutoplayOnInteraction:!0,autoplayInterval:3e3,autoplayDirection:"to left",enablePagination:!0,transitionDuration:300,transitionTimingFunction:"ease",draggable:!0};function v(t){const e={...g};for(const n in t)if(window.matchMedia(n).matches){const i=t[n];for(const t in i)e[t]=i[t]}return e}function S(){const t=this.index,e=this.slider,n=e.stateIndex,i=e.config.loop,s=Math.abs(t-n),o=e.states.length-s,r=s>e.states.length/2&&i;t>n?r?e.prev(o):e.next(s):r?e.next(o):e.prev(s)}function m(t,e=t.config.transitionDuration){t.isTransitioning=!0,setTimeout((()=>{t.isTransitioning=!1}),e)}function x(e,n){const i=e.el.classList,s=e.stateIndex,o=e.paginationButtons;e.config.loop||(0===s?i.add(t):i.remove(t),s===e.states.length-1?i.add("end"):i.remove("end")),o&&e.config.enablePagination&&(o[n].classList.remove("active"),o[s].classList.add("active"))}function y(e,i){const s=i.track;i.slides=s.children,i.offset=0,i.config=e,n(i,i.totalSlides,e),e.loop||i.el.classList.add(t),e.enableAutoplay&&!e.loop&&(e.enableAutoplay=!1),s.style.transitionProperty="transform",s.style.transitionTimingFunction=i.config.transitionTimingFunction,s.style.transitionDuration=`${i.config.transitionDuration}ms`;const{slidesToShow:r,slideGap:a}=i.config;i.el.style.setProperty("--slides-to-show",r+""),i.el.style.setProperty("--slide-gap",a),i.isStatic?i.el.classList.add("static"):e.draggable&&function(t){const e=t.track;e.slider=t;const n=c()?"touchstart":"pointerdown";e.addEventListener(n,d),e.addEventListener("click",(e=>{(t.isTransitioning||t.isDragging)&&(e.preventDefault(),e.stopImmediatePropagation(),e.stopPropagation())}),{capture:!0}),e.addEventListener("dragstart",h)}(i),function(t){if(!t.config.enablePagination||t.isStatic)return;const e=t.el.querySelector(".blaze-pagination");if(!e)return;t.paginationButtons=[];const n=t.states.length;for(let i=0;i<n;i++){const s=document.createElement("button");t.paginationButtons.push(s),s.textContent="",s.ariaLabel=`${i+1} of ${n}`,e.append(s),s.slider=t,s.index=i,s.onclick=S}t.paginationButtons[0].classList.add("active")}(i),function(t){const e=t.config;if(!e.enableAutoplay)return;const n="to left"===e.autoplayDirection?"next":"prev";t.autoplayTimer=setInterval((()=>{t[n]()}),e.autoplayInterval),e.stopAutoplayOnInteraction&&t.el.addEventListener(c()?"touchstart":"mousedown",(()=>{clearInterval(t.autoplayTimer)}),{once:!0})}(i),function(t){const e=t.el.querySelector(".blaze-prev"),n=t.el.querySelector(".blaze-next");e&&(e.onclick=()=>{t.prev()}),n&&(n.onclick=()=>{t.next()})}(i),o(i)}return class extends e{constructor(t,e){const n=t.querySelector(".blaze-track"),i=n.children,s=e?v(e):{...g};super(i.length,s),this.config=s,this.el=t,this.track=n,this.slides=i,this.offset=0,this.dragged=0,this.isDragging=!1,this.el.blazeSlider=this,this.passedConfig=e;const o=this;n.slider=o,y(s,o);let r=!1,a=0;window.addEventListener("resize",(()=>{if(0===a)return void(a=window.innerWidth);const t=window.innerWidth;a!==t&&(a=t,r||(r=!0,setTimeout((()=>{o.refresh(),r=!1}),200)))}))}next(t){if(this.isTransitioning)return;const e=super.next(t);if(!e)return void m(this);const[n,l]=e;x(this,n),m(this),function(t,e){const n=requestAnimationFrame;t.config.loop?(t.offset=-1*e,o(t),setTimeout((()=>{!function(t,e){for(let n=0;n<e;n++)t.track.append(t.slides[0])}(t,e),a(t),t.offset=0,o(t),n((()=>{n((()=>{r(t),i(t)}))}))}),t.config.transitionDuration)):s(t)}(this,l)}prev(t){if(this.isTransitioning)return;const e=super.prev(t);if(!e)return void m(this);const[n,l]=e;x(this,n),m(this),function(t,e){const n=requestAnimationFrame;if(t.config.loop){a(t),t.offset=-1*e,o(t),function(t,e){const n=t.slides.length;for(let i=0;i<e;i++){const e=t.slides[n-1];t.track.prepend(e)}}(t,e);const s=()=>{n((()=>{r(t),n((()=>{t.offset=0,o(t),i(t)}))}))};t.isDragging?c()?t.track.addEventListener("touchend",s,{once:!0}):t.track.addEventListener("pointerup",s,{once:!0}):n(s)}else s(t)}(this,l)}stopAutoplay(){clearInterval(this.autoplayTimer)}destroy(){this.track.removeEventListener(c()?"touchstart":"pointerdown",d),this.stopAutoplay(),this.paginationButtons?.forEach((t=>t.remove())),this.el.classList.remove("static"),this.el.classList.remove(t)}refresh(){const t=this.passedConfig?v(this.passedConfig):{...g};this.destroy(),y(t,this)}onSlide(t){return this.onSlideCbs||(this.onSlideCbs=new Set),this.onSlideCbs.add(t),()=>this.onSlideCbs.delete(t)}}}();
		</script>

	<?php }  );

            }
        } else {
            echo '<p>' . esc_html__('No products found.', 'woocommerce') . '</p>';
        }
        add_action("wp_footer", function()use ($woo_slider_id, $auto_paly , $card){
    ?>
    <script>
        new BlazeSlider(document.querySelector('#woo_slider<?=$woo_slider_id?>'), {
    	  all: {
    		enableAutoplay: false,
    		stopAutoplayOnInteraction: true,
    		autoplayInterval: 4000,
    		transitionDuration: 300,
    		slidesToShow: <?=$card ?>,
    		slidesToScroll: 1,
    		slideGap: '10px',
    		loop: true,
    		enablePagination: true,
    		transitionDuration: 500,
    		transitionTimingFunction: 'ease',
    		draggable: true
    	  },
    	  '(max-width: 900px)': {
    		slidesToShow: 2,
    	  },
    	  '(max-width: 500px)': {
    		slidesToShow: 1,
    	  },
        })
    </script>

    <?php });

    // theme 2 css

    add_action( 'wp_footer', function () use($woo_slider_id, $theme ) { if ( $theme == "2"   ) {  ?>
        <style>
            div#woo_slider<?=$woo_slider_id?> .slider_card.slide-visible {
                overflow: hidden;
            }

            div#woo_slider<?=$woo_slider_id?> span.wwo_card_details {
                transition: all 0.3s ease;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 10px;
                width: 100%;
                position: absolute;
                inset: 0;
                background-color: var(--storefront-background-color, #ffffff);
                opacity: 0.9;
                justify-content: center;
                transform: translateY(100%);
            }

            div#woo_slider<?=$woo_slider_id?>  .post_card:hover span.wwo_card_details {
                transform: translateY(0px);
            }
        </style>
<?php }
});

    // Reset post data
    wp_reset_postdata();
    $woo_slider_id++;

    // Return the buffered output
    return ob_get_clean();
}
// Register the shortcode
add_shortcode('woo-slider', 'woo_slider_shortcode');

// Manual Url Button Shortcode
function manual_url_button_shortcode($atts) {
    $atts = shortcode_atts(array(
        'url' => '',
        'image' => '',
        'title' => '',
        'description' => ''
    ), $atts);

    if (empty($atts['url'])) {
        return '<!-- Error: URL is required -->';
    }

    $url = esc_url($atts['url']);
    $image = esc_url($atts['image']);
    $title = sanitize_text_field($atts['title']);
    $description = sanitize_text_field($atts['description']);

    $output = '<a href="' . $url . '" target="_blank" rel="noopener" class="og-preview">';
    if (!empty($image)) {
        $output .= '<div class="og-image"><img src="' . $image . '" alt="' . esc_attr($title) . '"></div>';
    }
    if (!empty($title)) {
        $output .= '<h3 class="wp-block-post-title og-title">' . $title . '</h3>';
    }
    if (!empty($description)) {
        $output .= '<p class="main-header has-medium-font-size og-desc">' . $description . '</p>';
    }
    $output .= '</a>';

    return $output;
}
add_shortcode('url-button', 'manual_url_button_shortcode');
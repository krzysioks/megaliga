<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>" />
    <meta name="viewport" content="width=device-width" />
    <?php wp_head(); ?>
</head>

<body style="background-repeat: no-repeat;" <?php body_class(); ?>>
    <div id="wrapper" class="hfeed">
        <header id="header" style="background-image:url(<?php if (get_header_image()) {
                                                            echo esc_url(get_header_image());
                                                        } ?>)">
            <div id="header-nav">
                <div id="site-title">
                    <?php
                    $custom_logo_id = get_theme_mod('custom_logo');
                    $logo = wp_get_attachment_image_src($custom_logo_id, 'full');
                    if (is_front_page() || is_home() || is_front_page() && is_home()) {
                        echo '<h1>';
                    }
                    echo '<a href="' . esc_url(home_url('/')) . '" title="' . esc_attr(get_bloginfo('name')) . '" rel="home">';
                    if (has_custom_logo()) {
                        echo '<img src="' . esc_url($logo[0]) . '" id="logo" />';
                    } else {
                        bloginfo('name');
                    }
                    echo '</a>';
                    if (is_front_page() || is_home() || is_front_page() && is_home()) {
                        echo '</h1>';
                    }
                    ?>
                </div>
                <nav id="menu">
                    <div id="search">
                        <?php get_search_form(); ?>
                    </div>
                    <!-- <label class="toggle" for="toggle">&#9776; <?php //esc_html_e('Menu', 'services'); 
                                                                    ?></label>
                    <input id="toggle" class="toggle" type="checkbox" /> -->
                    <button type="button" class="menu-toggle"><span class="menu-icon">&#9776;</span><span class="menu-text screen-reader-text"><?php esc_html_e(' Menu', 'services'); ?></span></button>
                    <?php wp_nav_menu(array('theme_location' => 'main-menu')); ?>
                </nav>
            </div>
            <div id="site-description"><?php bloginfo('description'); ?></div>
            <?php if (is_active_sidebar('header-widget-area')) : ?>
                <aside id="header-sidebar" role="complementary">
                    <div id="hsidebar" class="widget-area">
                        <ul class="xoxo">
                            <?php dynamic_sidebar('header-widget-area'); ?>
                        </ul>
                        <div class="clear"></div>
                    </div>
                </aside>
            <?php endif; ?>
        </header>
        <div id="container">
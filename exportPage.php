<?php
/*
Template Name: Export
Description: Allows to export score table from megaliga or playoff page
 */

get_header();

do_action('hestia_before_single_page_wrapper');

?>

<div class="<?php echo hestia_layout(); ?>">
    <?php
    $class_to_add = '';
    if (class_exists('WooCommerce', false) && !is_cart()) {
        $class_to_add = 'blog-post-wrapper';
    }
    ?>
    <div class="blog-post <?php esc_attr($class_to_add); ?>">
        <div class="container">
            <?php
            if (have_posts()) :
                while (have_posts()) :
                    the_post();

                    $sidebar_layout = apply_filters('hestia_sidebar_layout', get_theme_mod('hestia_page_sidebar_layout', 'full-width'));
                    $wrap_class     = apply_filters('hestia_filter_page_content_classes', 'col-md-8 page-content-wrap ');
            ?>

                    <article id="post-<?php the_ID(); ?>" class="section section-text">
                        <div class="row">
                            <?php
                            if ($sidebar_layout === 'sidebar-left') {
                                do_action('hestia_page_sidebar');
                            }
                            ?>
                            <div class="<?php echo esc_attr($wrap_class); ?>">
                                <?php
                                do_action('hestia_before_page_content');

                                //custom code starts here
                                global $wpdb;
                                $title = the_title('', '', false);
                                $current_user = wp_get_current_user();
                                //show form only for user with ID == 14 (mbaginski) || 58 (lukaszenko2)
                                // $isForm = $userId == 14 || $userId == 58;
                                //8 - length of "kolejka" string which is in every title of składy subpage
                                // $round_number = substr($title, 0, strlen($title) - 8);
                                $userId = $current_user->ID;
                                // $userId = 58; //14;

                                if (is_user_logged_in() && ($userId == 14 || $userId == 58 || $userId == 1)) {
                                    //content of export page
                                    echo '<div>';
                                    echo '  <form action="' . esc_attr(admin_url('admin-post.php')) . '" method="post">';
                                    echo '      <input type="hidden" name="action" value="handle_csv_export" />';
                                    echo '      <div>';
                                    echo '          <div>';
                                    echo '              <input type="submit" name="submitExportMegaliga" value="Eksport megaliga" />';
                                    echo '          </div>';
                                    echo '          <div>';
                                    echo '              <input type="submit" name="submitEksportPlayoff" value="Eksport playoff" />';
                                    echo '          </div>';
                                    echo '      </div>';
                                    echo '  </form>';
                                    echo '</div>';
                                }
                                //custom code ends here

                                echo apply_filters('hestia_filter_blog_social_icons', '');

                                if (comments_open() || get_comments_number()) :
                                    comments_template();
                                endif;
                                ?>
                            </div>
                            <?php
                            if ($sidebar_layout === 'sidebar-right') {
                                do_action('hestia_page_sidebar');
                            }
                            ?>
                        </div>
                    </article>
            <?php
                    if (is_paged()) {
                        hestia_single_pagination();
                    }

                endwhile;
            else :
                get_template_part('template-parts/content', 'none');
            endif;
            ?>
        </div>
    </div>

    <?php get_footer(); ?>
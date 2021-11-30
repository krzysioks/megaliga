<?php
/*
Template Name: Export
Description: Allows to export score table from megaliga or playoff page
 */
?>

<?php get_header(); ?>
<main id="content">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="header">
                    <h1 class="entry-title"><?php the_title('megaliga: '); ?></h1> <?php edit_post_link(); ?>
                </header>
                <div class="entry-content">
                    <?php if (has_post_thumbnail()) {
                        the_post_thumbnail();
                    } ?>

                    <?php
                    global $wpdb;
                    $title = the_title('', '', false);
                    $current_user = wp_get_current_user();
                    //show form only for user with ID == 14 (mbaginski) || 48 (Gabbana)
                    // $isForm = $userId == 14 || $userId == 48;
                    //8 - length of "kolejka" string which is in every title of składy subpage
                    // $round_number = substr($title, 0, strlen($title) - 8);
                    $userId = $current_user->ID;
                    // $userId = 48; //14;

                    if (is_user_logged_in() && ($userId == 14 || $userId == 48 || $userId == 1)) {
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

                    ?>
                    <div class="entry-links"><?php wp_link_pages(); ?></div>
                </div>
            </article>
            <?php if (!post_password_required()) comments_template('', true); ?>
    <?php endwhile;
    endif; ?>
</main>
<?php get_sidebar(); ?>
<?php get_footer(); ?>
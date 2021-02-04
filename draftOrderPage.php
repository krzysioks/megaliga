<?php
/*
Template Name: Draft order ver 3.0
Description: Shows draft order table for one group in the ligue
 */
?>
<?php get_header(); ?>
<main id="content">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="header">
                    <h1 class="entry-title"><?php the_title('tabela: '); ?></h1> <?php edit_post_link(); ?>
                </header>
                <div class="entry-content">
                    <?php if (has_post_thumbnail()) {
                        the_post_thumbnail();
                    } ?>

                    <?php
                    global $wpdb;

                    //get info about whose turn is now if draft is open
                    $getDraftWindowState = $wpdb->get_results('SELECT draft_window_open, draft_current_round FROM megaliga_draft_data');
                    $getTeamId = $wpdb->get_results('SELECT team_names_id FROM megaliga_season_draft_order WHERE id_season_draft_order = ' . $getDraftWindowState[0]->draft_current_round);
                    $getTeam = $wpdb->get_results('SELECT name FROM megaliga_team_names WHERE team_names_id = ' . $getTeamId[0]->team_names_id);

                    if ($getDraftWindowState[0]->draft_window_open) {
                        echo "</br>";
                        echo "<span>Obecnie w rundzie <span class='playoffPhaseTitle'>" . $getDraftWindowState[0]->draft_current_round . "</span> wybiera zespół: <span class='playoffPhaseTitle'>" . $getTeam[0]->name . "</span></span>";
                        echo "</br>";
                        echo "</br>";
                    }

                    the_content();
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
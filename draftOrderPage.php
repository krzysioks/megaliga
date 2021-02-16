<?php
/*
Template Name: Draft Order
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
                    //TODO add logic to update megaliga_season_draft_order_dolce/gabbana tables

                    //get info about whose turn is now if draft is open for Dolce group...
                    $getDraftWindowState = $wpdb->get_results('SELECT draft_window_open, draft_current_round_dolce, draft_current_round_gabbana FROM megaliga_draft_data');
                    $getTeamIdDolce = $wpdb->get_results('SELECT team_names_id FROM megaliga_season_draft_order_dolce WHERE draft_order = ' . $getDraftWindowState[0]->draft_current_round_dolce);
                    $getTeamDolce = $wpdb->get_results('SELECT name FROM megaliga_team_names WHERE team_names_id = ' . $getTeamIdDolce[0]->team_names_id);

                    //... for Gabbana group
                    $getTeamIdGabbana = $wpdb->get_results('SELECT team_names_id FROM megaliga_season_draft_order_gabbana WHERE draft_order = ' . $getDraftWindowState[0]->draft_current_round_gabbana);
                    $getTeamGabbana = $wpdb->get_results('SELECT name FROM megaliga_team_names WHERE team_names_id = ' . $getTeamIdGabbana[0]->team_names_id);

                    if ($getDraftWindowState[0]->draft_window_open) {

                        echo '<div class="displayFlex playoffLadderflexDirection">';
                        echo '  <div class="width_100 padding10">';
                        echo "      <span class='padding10'>W grupie Dolce, w rundzie <span class='playoffPhaseTitle'>" . $getDraftWindowState[0]->draft_current_round_dolce . "</span> teraz wybiera zespół: <span class='playoffPhaseTitle'>" . $getTeamDolce[0]->name . "</span></span>";
                        echo '  </div>';
                        echo '  <div class="width_100 padding10">';
                        echo "      <span class='padding10'>W grupie Gabbana, w rundzie <span class='playoffPhaseTitle'>" . $getDraftWindowState[0]->draft_current_round_gabbana . "</span> teraz wybiera zespół: <span class='playoffPhaseTitle'>" . $getTeamGabbana[0]->name . "</span></span>";
                        echo '  </div>';
                        echo '</div>';
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
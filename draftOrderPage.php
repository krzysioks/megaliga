<?php
/*
Template Name: Draft Order
Description: Shows draft order table for two group in the ligue
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

                    //check if draft order lottery is finished for dolce..
                    $getCheckIfDraftOrderEstablishedDolce = $wpdb->get_results('SELECT COUNT(*) as "dolce" FROM megaliga_user_data WHERE is_draw_round1_draft_order = 1 AND ligue_groups_id = 1');
                    //... for gabbana
                    $getCheckIfDraftOrderEstablishedGabbana = $wpdb->get_results('SELECT COUNT(*) as "gabbana" FROM megaliga_user_data WHERE is_draw_round1_draft_order = 1 AND ligue_groups_id = 2');

                    $getUserData = $wpdb->get_results('SELECT ID, team_names_id FROM megaliga_user_data');

                    function updateDraftOrderTable($getDraftOrder, $draftOrderTableName, $teamId2userIdMapper)
                    {
                        global $wpdb;

                        //map team_names_id to order position
                        $draftOrder2teamIdMapper = array();
                        $i = 1;
                        foreach ($getDraftOrder as $data) {
                            $draftOrder2teamIdMapper[$i] = $data;
                            $i++;
                        }

                        //order in which teams will be drafting players
                        $draftOrderArray = array(1, 2, 3, 4, 5, 6, 6, 5, 4, 3, 2, 1, 1, 2, 3, 4, 5, 6, 6, 5, 4, 3, 2, 1, 1, 2, 3, 4, 5, 6, 6, 5, 4, 3, 2, 1, 1, 2, 3, 4, 5, 6, 6, 5, 4, 3, 2, 1, 1, 2, 3, 4, 5, 6, 6, 5, 4, 3, 2, 1, 1, 2, 3, 4, 5, 6, 6, 5, 4, 3, 2, 1, 1, 2, 3, 4, 5, 6, 6, 5, 4, 3, 2, 1);

                        //check if $draftOrderTableName has already records -> UPDATE or is empty -> INSERT
                        $getNumberOfRounds = $wpdb->get_results('SELECT COUNT(*) as "size" FROM ' . $draftOrderTableName);

                        $i = 1;
                        foreach ($draftOrderArray as $value) {
                            // prepare data for submission
                            $submitDataArray = array();
                            $submitDataArray['draft_order'] = $i;
                            $submitDataArray['ID'] = $teamId2userIdMapper[$draftOrder2teamIdMapper[$value]];
                            $submitDataArray['team_names_id'] = $draftOrder2teamIdMapper[$value];

                            if (!$getNumberOfRounds[0]->size) {
                                $wpdb->insert($draftOrderTableName, $submitDataArray);
                            } else {
                                //update if records already exists
                                $where = array('draft_order' => $i);
                                $wpdb->update($draftOrderTableName, $submitDataArray, $where);
                            }

                            $i++;
                        }
                    }

                    //only if draft order lottery is finished -> update table with draft order (megaliga_season_draft_order_dolce/gabbana)
                    if ($getCheckIfDraftOrderEstablishedDolce[0]->dolce == 6 && $getCheckIfDraftOrderEstablishedGabbana[0]->gabbana == 6) {
                        $getDraftOrder = $wpdb->get_results('SELECT one, two, three, four, five, six FROM megaliga_1round_draft_order_lottery_outcome');
                        $getUserData = $wpdb->get_results('SELECT ID, team_names_id FROM megaliga_user_data');

                        $teamId2userIdMapper = array();
                        foreach ($getUserData as $userData) {
                            $teamId2userIdMapper[$userData->team_names_id] = $userData->ID;
                        }

                        updateDraftOrderTable($getDraftOrder[0], 'megaliga_season_draft_order_dolce', $teamId2userIdMapper);
                        updateDraftOrderTable($getDraftOrder[1], 'megaliga_season_draft_order_gabbana', $teamId2userIdMapper);
                    }



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
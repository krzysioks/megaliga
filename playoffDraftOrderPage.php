<?php
/*
Template Name: Playoff Draft Order
Description: Shows playoff draft order table for two groups in the ligue
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
                            <?php
                            do_action('hestia_before_page_content');

                            //custom code starts here
                            global $wpdb;

                            function compareTeams($a, $b)
                            {
                                // sort by points
                                $retval = strnatcmp($b['points'], $a['points']);
                                // if points are identical, sort balance
                                if (!$retval) {
                                    $retval = (int) $b['balance'] - (int) $a['balance'];
                                }

                                //if balance identical -> sort by totalScore
                                if (!$retval) {
                                    $retval = (int) $b['totalScore'] - (int) $a['totalScore'];
                                }

                                return $retval;
                            }

                            function calculateStandingsData($scheduleQuery, $userIDquery)
                            {
                                global $wpdb;
                                $standingsData = array();
                                foreach ($userIDquery as $user) {
                                    $standingsData[$user->ID] = array('gamesPlayed' => 0, 'wins' => 0, 'looses' => 0, 'draws' => 0, 'balance' => 0, 'points' => 0, 'ID' => $user->ID, 'totalScore' => 0);
                                }

                                foreach ($scheduleQuery as $game) {
                                    if ($game->team1_score != null) {
                                        $standingsData[$game->id_user_team1]['totalScore'] = $standingsData[$game->id_user_team1]['totalScore'] + $game->team1_score;
                                        $standingsData[$game->id_user_team2]['totalScore'] = $standingsData[$game->id_user_team2]['totalScore'] + $game->team2_score;

                                        //when team1 wins
                                        if ($game->team1_score > $game->team2_score) {
                                            $standingsData[$game->id_user_team1]['gamesPlayed'] = $standingsData[$game->id_user_team1]['gamesPlayed'] + 1;
                                            $standingsData[$game->id_user_team1]['wins'] = $standingsData[$game->id_user_team1]['wins'] + 1;
                                            $standingsData[$game->id_user_team1]['balance'] = $standingsData[$game->id_user_team1]['balance'] + $game->team1_score - $game->team2_score;
                                            $standingsData[$game->id_user_team1]['points'] = $standingsData[$game->id_user_team1]['points'] + 2;

                                            $standingsData[$game->id_user_team2]['gamesPlayed'] = $standingsData[$game->id_user_team2]['gamesPlayed'] + 1;
                                            $standingsData[$game->id_user_team2]['looses'] = $standingsData[$game->id_user_team2]['looses'] + 1;
                                            $standingsData[$game->id_user_team2]['balance'] = $standingsData[$game->id_user_team2]['balance'] + $game->team2_score - $game->team1_score;
                                        }

                                        //when team2 wins
                                        if ($game->team1_score < $game->team2_score) {
                                            $standingsData[$game->id_user_team2]['gamesPlayed'] = $standingsData[$game->id_user_team2]['gamesPlayed'] + 1;
                                            $standingsData[$game->id_user_team2]['wins'] = $standingsData[$game->id_user_team2]['wins'] + 1;
                                            $standingsData[$game->id_user_team2]['balance'] = $standingsData[$game->id_user_team2]['balance'] + $game->team2_score - $game->team1_score;
                                            $standingsData[$game->id_user_team2]['points'] = $standingsData[$game->id_user_team2]['points'] + 2;

                                            $standingsData[$game->id_user_team1]['gamesPlayed'] = $standingsData[$game->id_user_team1]['gamesPlayed'] + 1;
                                            $standingsData[$game->id_user_team1]['looses'] = $standingsData[$game->id_user_team1]['looses'] + 1;
                                            $standingsData[$game->id_user_team1]['balance'] = $standingsData[$game->id_user_team1]['balance'] + $game->team1_score - $game->team2_score;
                                        }

                                        //when team1 draws with team2
                                        if ($game->team1_score == $game->team2_score) {
                                            $standingsData[$game->id_user_team1]['gamesPlayed'] = $standingsData[$game->id_user_team1]['gamesPlayed'] + 1;
                                            $standingsData[$game->id_user_team1]['draws'] = $standingsData[$game->id_user_team1]['draws'] + 1;
                                            $standingsData[$game->id_user_team1]['points'] = $standingsData[$game->id_user_team1]['points'] + 1;

                                            $standingsData[$game->id_user_team2]['gamesPlayed'] = $standingsData[$game->id_user_team2]['gamesPlayed'] + 1;
                                            $standingsData[$game->id_user_team2]['draws'] = $standingsData[$game->id_user_team2]['draws'] + 1;
                                            $standingsData[$game->id_user_team2]['points'] = $standingsData[$game->id_user_team2]['points'] + 1;
                                        }
                                    }
                                }

                                uasort($standingsData, 'compareTeams');
                                return $standingsData;
                            }

                            //function returns 2 teams which has 1,2 place in group table
                            function getReachedPlayoffTeams($standings, $numberOfTeamsPassing)
                            {
                                global $wpdb;
                                $i = 1;
                                $teamsReachedPlayoff = array();
                                foreach ($standings as $team) {
                                    $getTeamId = $wpdb->get_results('SELECT team_names_id FROM megaliga_user_data WHERE megaliga_user_data.ID = ' . $team['ID']);
                                    if ($i <= $numberOfTeamsPassing) {
                                        $teamsReachedPlayoff[$i] = array("ID" => $team['ID'], "team_names_id" => $getTeamId[0]->team_names_id);
                                    }
                                    $i++;
                                }

                                return $teamsReachedPlayoff;
                            }

                            $getUsersID = $wpdb->get_results('SELECT ID FROM megaliga_user_data WHERE ligue_groups_id = 1 OR ligue_groups_id = 2');

                            $getSchedule = $wpdb->get_results('SELECT team1_score, team2_score, id_user_team1, id_user_team2 FROM megaliga_schedule');

                            $standings = calculateStandingsData($getSchedule, $getUsersID);

                            //get 4 first best teams from standings
                            $teamsReachedPlayoff = getReachedPlayoffTeams($standings, 4);

                            //update draft order table only if there are any teams that reached playoff (case when teams are not yet assigned to groups)
                            if (count($teamsReachedPlayoff)) {
                                //order in which teams will be drafting players
                                $playoffDraftOrderArray = array(1, 2, 3, 4, 1, 2, 3, 4, 4, 3, 2, 1, 1, 2, 3, 4, 4, 3, 2, 1, 1, 2, 3, 4, 4, 3, 2, 1, 1, 2, 3, 4, 4, 3, 2, 1, 1, 2, 3, 4, 4, 3, 2, 1, 1, 2, 3, 4, 4, 3, 2, 1, 1, 2, 3, 4, 4, 3, 2, 1, 1, 2, 3, 4, 4, 3, 2, 1, 1, 2, 3, 4, 4, 3, 2, 1, 1, 2, 3, 4);
                                //check if megaliga_playoff_draft_order has already records -> UPDATE or is empty -> INSERT
                                $getNumberOfRounds = $wpdb->get_results('SELECT COUNT(*) as "size" FROM megaliga_playoff_draft_order');


                                $i = 1;
                                foreach ($playoffDraftOrderArray as $value) {
                                    // prepare data for submission
                                    $submitDataArray = array();
                                    $submitDataArray['draft_order'] = $i;
                                    $submitDataArray['ID'] = $teamsReachedPlayoff[$value]['ID'];
                                    $submitDataArray['team_names_id'] = $teamsReachedPlayoff[$value]['team_names_id'];

                                    if (!$getNumberOfRounds[0]->size) {
                                        $wpdb->insert('megaliga_playoff_draft_order', $submitDataArray);
                                    } else {
                                        //update if records already exists
                                        $where = array('draft_order' => $i);
                                        $wpdb->update('megaliga_playoff_draft_order', $submitDataArray, $where);
                                    }

                                    $i++;
                                }
                            }


                            //get info about whose turn is now if playoff draft is open
                            $getDraftWindowState = $wpdb->get_results('SELECT playoff_draft_window_open, playoff_draft_current_round FROM megaliga_draft_data');
                            $getTeamId = $wpdb->get_results('SELECT team_names_id FROM megaliga_playoff_draft_order WHERE draft_order = ' . $getDraftWindowState[0]->playoff_draft_current_round);
                            $getTeam = $wpdb->get_results('SELECT name FROM megaliga_team_names WHERE team_names_id = ' . $getTeamId[0]->team_names_id);

                            if ($getDraftWindowState[0]->playoff_draft_window_open) {
                                echo "</br>";
                                echo '<span class="marginLeft1em">Obecnie w rundzie <span class="playoffPhaseTitle">' . $getDraftWindowState[0]->playoff_draft_current_round . "</span> wybiera zespół: <span class='playoffPhaseTitle'>" . $getTeam[0]->name . "</span></span>";
                                echo "</br>";
                                echo "</br>";
                            }

                            the_content();
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
<?php
/*
Template Name: Standings
Description: Shows standings of the regular season for two groups in the ligue
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

                                function compareTeams($a, $b)
                                {
                                    // sort by points
                                    $retval = strnatcmp($b['points'], $a['points']);
                                    // if points are identical, sort balance
                                    if (!$retval) {
                                        $retval = (int)$b['balance'] - (int)$a['balance'];
                                        //$retval = strnatcmp((integer)$b['balance'], (integer)$a['balance']);
                                    }

                                    //if balance identical -> sort by totalScore
                                    if (!$retval) {
                                        $retval = (int)$b['totalScore'] - (int)$a['totalScore'];
                                        //$retval = strnatcmp((integer)$b['balance'], (integer)$a['balance']);
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

                                            //if $game is played during rematch round -> add bonus point for team that has better balance after 2 matches
                                            if ($game->id_rematch_schedule !== null) {
                                                // print_r($game);
                                                // echo '<br>';
                                                $getRematchSchedule = $wpdb->get_results('SELECT team1_score, team2_score, id_user_team1, id_user_team2 FROM megaliga_schedule WHERE id_schedule = ' . $game->id_rematch_schedule);

                                                //count total points scored by each team during both rounds (1st and rematch)
                                                $team1MatchupScore = $game->id_user_team1 == $getRematchSchedule[0]->id_user_team1 ? $game->team1_score + $getRematchSchedule[0]->team1_score : $game->team1_score + $getRematchSchedule[0]->team2_score;
                                                $team2MatchupScore = $game->id_user_team2 == $getRematchSchedule[0]->id_user_team2 ? $game->team2_score + $getRematchSchedule[0]->team2_score : $game->team2_score + $getRematchSchedule[0]->team1_score;

                                                //if given teams play more than 2 times witch each other -> take into account also third match
                                                if ($game->id_rematch_schedule2 !== null) {
                                                    // echo '3rd match </br>';
                                                    $getRematchSchedule2 = $wpdb->get_results('SELECT team1_score, team2_score, id_user_team1, id_user_team2 FROM megaliga_schedule WHERE id_schedule = ' . $game->id_rematch_schedule2);

                                                    $team1MatchupScore = $game->id_user_team1 == $getRematchSchedule2[0]->id_user_team1 ? $team1MatchupScore + $getRematchSchedule2[0]->team1_score : $team1MatchupScore + $getRematchSchedule2[0]->team2_score;
                                                    $team2MatchupScore = $game->id_user_team2 == $getRematchSchedule2[0]->id_user_team2 ? $team2MatchupScore + $getRematchSchedule2[0]->team2_score : $team2MatchupScore + $getRematchSchedule2[0]->team1_score;
                                                }
                                                // echo '$team1MatchupScore: ' . $team1MatchupScore . '   $team2MatchupScore: ' . $team2MatchupScore . '</br>';

                                                //when team 1 wins the matchup
                                                if ($team1MatchupScore > $team2MatchupScore) {
                                                    // echo 'team one gets extra point</br>';
                                                    $standingsData[$game->id_user_team1]['points'] = $standingsData[$game->id_user_team1]['points'] + 1;
                                                } else if ($team1MatchupScore < $team2MatchupScore) {
                                                    // echo 'team two gets extra point</br>';
                                                    //when team 2 wins the matchup
                                                    $standingsData[$game->id_user_team2]['points'] = $standingsData[$game->id_user_team2]['points'] + 1;
                                                }
                                                // echo '<br><br>';
                                            }
                                        }
                                    }

                                    uasort($standingsData, 'compareTeams');
                                    return $standingsData;
                                }

                                function drawStandings($standings, $side, $groupName)
                                {
                                    global $wpdb;
                                    $margin = $side == 'left' ? 'marginRight40' : '';
                                    echo '<table class="scheduleTable ' . $margin . '" border="0">';
                                    echo '  <tr><td colspan="8" class="scheduleTableName textLeft">Grupa ' . $groupName . '</td></tr>';
                                    echo '  <tr>
                                    <th class="scheduleHeader textLeft">m</th>
                                    <th class="scheduleHeader standingsHeader textLeft">drużyna</th>
                                    <th class="scheduleHeader standingsHeader textLeft">m</th>
                                    <th class="scheduleHeader standingsHeader textLeft">z</th>
                                    <th class="scheduleHeader standingsHeader textLeft">r</th>
                                    <th class="scheduleHeader standingsHeader textLeft">p</th>
                                    <th class="scheduleHeader standingsHeader textLeft">suma</th>
                                    <th class="scheduleHeader standingsHeader textLeft">+/-</th>
                                    <th class="scheduleHeader standingsHeader textLeft">pkt</th>
                                </tr>';
                                    $i = 1;
                                    foreach ($standings as $team) {
                                        $getTeamName = $wpdb->get_results('SELECT megaliga_team_names.name as "teamName" FROM megaliga_team_names, megaliga_user_data WHERE megaliga_user_data.team_names_id = megaliga_team_names.team_names_id AND megaliga_user_data.ID = ' . $team['ID']);

                                        $trClass = $i % 2 == 0 ? 'even' : 'odd';
                                        echo '<tr class="' . $trClass . '">
                                    <td class="scheduleTdImg paddingLeft10">' . $i . '</td>
                                    <td class="scheduleTd textLeft">' . $getTeamName[0]->teamName . '</td>
                                    <td class="scheduleTdImg">' . $team['gamesPlayed'] . '</td>
                                    <td class="scheduleTdImg">' . $team['wins'] . '</td>
                                    <td class="scheduleTdImg">' . $team['draws'] . '</td>
                                    <td class="scheduleTdImg">' . $team['looses'] . '</td>
                                    <td class="scheduleTdImg">' . $team['totalScore'] . '</td>
                                    <td class="scheduleTdImg">' . $team['balance'] . '</td>
                                    <td class="scheduleTdImg">' . $team['points'] . '</td>';
                                        echo '</tr>';

                                        $i++;
                                    }

                                    echo '</table>';
                                }

                                $getDolceUserID = $wpdb->get_results('SELECT ID FROM megaliga_user_data WHERE ligue_groups_id = 1');
                                $getGabbanaUserID = $wpdb->get_results('SELECT ID FROM megaliga_user_data WHERE ligue_groups_id = 2');

                                $getDolceSchedule = $wpdb->get_results('SELECT team1_score, team2_score, id_user_team1, id_user_team2, id_rematch_schedule, id_rematch_schedule2 FROM megaliga_schedule WHERE id_ligue_group = 1');
                                $getGabbanaSchedule = $wpdb->get_results('SELECT team1_score, team2_score, id_user_team1, id_user_team2, id_rematch_schedule, id_rematch_schedule2 FROM megaliga_schedule WHERE id_ligue_group = 2');

                                $standingsDolce = calculateStandingsData($getDolceSchedule, $getDolceUserID);
                                $standingsGabbana = calculateStandingsData($getGabbanaSchedule, $getGabbanaUserID);

                                //uncomment if you want to display content of the page added in wp admin panel
                                // the_content();
                                //content of the team page
                                echo '<div class="scheduleContainer">';
                                drawStandings($standingsDolce, 'left', 'dolce');
                                drawStandings($standingsGabbana, 'right', 'gabbana');
                                echo '</div>';
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
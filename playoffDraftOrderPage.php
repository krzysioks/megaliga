<?php
/*
Template Name: Playoff Draft Order
Description: Shows playoff draft order table for two groups in the ligue
 */
?>
<?php get_header(); ?>
<main id="content">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="header">
                    <h1 class="entry-title"><?php the_title(); ?></h1> <?php edit_post_link(); ?>
                </header>
                <div class="entry-content">
                    <?php if (has_post_thumbnail()) {
                        the_post_thumbnail();
                    } ?>

                    <?php
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

                                //if $game is played during rematch round -> add bonus point for team that has better balance after 2 matches
                                if ($game->id_rematch_schedule !== null) {
                                    $getRematchSchedule = $wpdb->get_results('SELECT team1_score, team2_score, id_user_team1, id_user_team2 FROM megaliga_schedule WHERE id_schedule = ' . $game->id_rematch_schedule);

                                    //count total points scored by each team during both rounds (1st and rematch)
                                    $team1MatchupScore = $game->id_user_team1 == $getRematchSchedule[0]->id_user_team1 ? $game->team1_score + $getRematchSchedule[0]->team1_score : $game->team1_score + $getRematchSchedule[0]->team2_score;
                                    $team2MatchupScore = $game->id_user_team2 == $getRematchSchedule[0]->id_user_team2 ? $game->team2_score + $getRematchSchedule[0]->team2_score : $game->team2_score + $getRematchSchedule[0]->team1_score;

                                    //if given teams play more than 2 times witch each other -> take into account also third match
                                    if ($game->id_rematch_schedule2 !== null) {
                                        $getRematchSchedule2 = $wpdb->get_results('SELECT team1_score, team2_score, id_user_team1, id_user_team2 FROM megaliga_schedule WHERE id_schedule = ' . $game->id_rematch_schedule2);

                                        $team1MatchupScore = $game->id_user_team1 == $getRematchSchedule2[0]->id_user_team1 ? $team1MatchupScore + $getRematchSchedule2[0]->team1_score : $team1MatchupScore + $getRematchSchedule2[0]->team2_score;
                                        $team2MatchupScore = $game->id_user_team2 == $getRematchSchedule2[0]->id_user_team2 ? $team2MatchupScore + $getRematchSchedule2[0]->team2_score : $team2MatchupScore + $getRematchSchedule2[0]->team1_score;
                                    }

                                    //when team 1 wins the matchup
                                    if ($team1MatchupScore > $team2MatchupScore) {
                                        $standingsData[$game->id_user_team1]['points'] = $standingsData[$game->id_user_team1]['points'] + 1;
                                    } else if ($team1MatchupScore < $team2MatchupScore) {
                                        //when team 2 wins the matchup
                                        $standingsData[$game->id_user_team2]['points'] = $standingsData[$game->id_user_team2]['points'] + 1;
                                    }
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

                    //function return ID of user, which team reached playoff
                    function getReachedPlayoffID($teamsReachedPlayoff)
                    {
                        $reachedPlayoffIDarray = array();
                        foreach ($teamsReachedPlayoff as $teamReachedPlayoff) {
                            array_push($reachedPlayoffIDarray, $teamReachedPlayoff['ID']);
                        }

                        return $reachedPlayoffIDarray;
                    }

                    //assessing first 4 (2 from each group) teams in the leage which currently reach playoff
                    $getDolceUserID = $wpdb->get_results('SELECT ID FROM megaliga_user_data WHERE ligue_groups_id = 1');
                    $getGabbanaUserID = $wpdb->get_results('SELECT ID FROM megaliga_user_data WHERE ligue_groups_id = 2');

                    $getDolceSchedule = $wpdb->get_results('SELECT team1_score, team2_score, id_user_team1, id_user_team2, id_rematch_schedule, id_rematch_schedule2 FROM megaliga_schedule WHERE id_ligue_group = 1');
                    $getGabbanaSchedule = $wpdb->get_results('SELECT team1_score, team2_score, id_user_team1, id_user_team2, id_rematch_schedule, id_rematch_schedule2 FROM megaliga_schedule WHERE id_ligue_group = 2');

                    $standingsDolce = calculateStandingsData($getDolceSchedule, $getDolceUserID);
                    $standingsGabbana = calculateStandingsData($getGabbanaSchedule, $getGabbanaUserID);

                    //get 2 best teams from each group
                    $teamsReachedPlayoffDolce = getReachedPlayoffTeams($standingsDolce, 2);
                    $teamsReachedPlayoffGabbana = getReachedPlayoffTeams($standingsGabbana, 2);

                    //calculate standings data for those 4 selected teams to sort them from the best to the worst
                    //get ID of teams that reached playoff
                    $reachedPlayoffDolceIDarray = getReachedPlayoffID($teamsReachedPlayoffDolce);
                    $reachedPlayoffGabbanaIDarray = getReachedPlayoffID($teamsReachedPlayoffGabbana);

                    //filter standings to get standings for 4 teams that reached playoffs
                    $reachedPlayoffStandings = array();
                    foreach ($reachedPlayoffDolceIDarray as $ID) {
                        $reachedPlayoffStandings[$ID] = $standingsDolce[$ID];
                    }
                    foreach ($reachedPlayoffGabbanaIDarray as $ID) {
                        $reachedPlayoffStandings[$ID] = $standingsGabbana[$ID];
                    }

                    //sort them to get order from best to the worst
                    uasort($reachedPlayoffStandings, 'compareTeams');

                    //for those 4 (2 from each group) teams playoff draft order will be set. It is dynamic table which will change during season while teams change their positions in the table
                    $teamsReachedPlayoff = getReachedPlayoffTeams($reachedPlayoffStandings, 4);

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
                        echo "<span>Obecnie w rundzie <span class='playoffPhaseTitle'>" . $getDraftWindowState[0]->playoff_draft_current_round . "</span> wybiera zespół: <span class='playoffPhaseTitle'>" . $getTeam[0]->name . "</span></span>";
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
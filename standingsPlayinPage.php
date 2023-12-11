<?php
/*
Template Name: Standings Playin
Description: Shows standings of the playin phase for two groups in the ligue. 3 teams, which scores points in 2 matches will advance to play off. 4th team is a lucky looser, which scored most points out of the teams that, have lost in their pairs
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

                            // funtion return array with data needed to render each of 3 matchups in playin phase
                            function getData()
                            {
                                global $wpdb;

                                // array consisting data for 3 matchups
                                $playinData = array();

                                $getSchedulePlayin = $wpdb->get_results('SELECT id_user_team1, id_user_team2, team1_score, team2_score, team1_seed, team2_seed FROM megaliga_schedule_playin');

                                function compareTeams($a, $b)
                                {
                                    // sort by points
                                    $retval = strnatcmp($b['points'], $a['points']);
                                    // if points are identical, sort balance
                                    if (!$retval) {
                                        $retval = (int)$b['balance'] - (int)$a['balance'];
                                    }

                                    //if balance identical -> sort by totalScore
                                    if (!$retval) {
                                        $retval = (int)$b['totalScore'] - (int)$a['totalScore'];
                                    }

                                    return $retval;
                                }

                                // get current megaliga standings
                                $getDolceUserID = $wpdb->get_results('SELECT ID FROM megaliga_user_data WHERE ligue_groups_id = 1');
                                $getGabbanaUserID = $wpdb->get_results('SELECT ID FROM megaliga_user_data WHERE ligue_groups_id = 2');

                                $getDolceSchedule = $wpdb->get_results('SELECT team1_score, team2_score, id_user_team1, id_user_team2, id_rematch_schedule, id_rematch_schedule2 FROM megaliga_schedule WHERE id_ligue_group = 1');
                                $getGabbanaSchedule = $wpdb->get_results('SELECT team1_score, team2_score, id_user_team1, id_user_team2, id_rematch_schedule, id_rematch_schedule2 FROM megaliga_schedule WHERE id_ligue_group = 2');

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

                                $standingsDolce = calculateStandingsData($getDolceSchedule, $getDolceUserID);
                                $standingsGabbana = calculateStandingsData($getGabbanaSchedule, $getGabbanaUserID);

                                for ($i = 0; $i < 5; $i++) {
                                    $matchupData = array('team1Name' => '', 'team2Name' => '', 'scoreTeam1Round1' => 0, 'scoreTeam2Round1' => 0, 'scoreTeam1Round2' => 0, 'scoreTeam2Round2' => 0, 'totalScoreTeam1' => null, 'totalScoreTeam2' => null, 'seedNumberTeam1' => 0, 'seedNumberTeam2' => 0, 'winner' => 'none', 'winnerTotalWinsNumber' => 0, 'winnerTotalScore' => 0);

                                    //get team names of both users
                                    $getTeam1Name = $wpdb->get_results('SELECT megaliga_team_names.name as "team_name" FROM megaliga_team_names, megaliga_user_data WHERE megaliga_team_names.team_names_id = megaliga_user_data.team_names_id  AND megaliga_user_data.ID = ' . $getSchedulePlayin[$i]->id_user_team1);

                                    $getTeam2Name = $wpdb->get_results('SELECT megaliga_team_names.name as "team_name" FROM megaliga_team_names, megaliga_user_data WHERE megaliga_team_names.team_names_id = megaliga_user_data.team_names_id  AND megaliga_user_data.ID = ' . $getSchedulePlayin[$i]->id_user_team2);

                                    $matchupData['team1Name'] = $getTeam1Name[0]->team_name;
                                    $matchupData['team2Name'] = $getTeam2Name[0]->team_name;

                                    //setting score for teams for  round 1 and 2
                                    $matchupData['scoreTeam1Round1'] = $getSchedulePlayin[$i]->team1_score;
                                    $matchupData['scoreTeam2Round1'] = $getSchedulePlayin[$i]->team2_score;

                                    $matchupData['scoreTeam1Round2'] = $getSchedulePlayin[$i + 1]->team1_score;
                                    $matchupData['scoreTeam2Round2'] = $getSchedulePlayin[$i + 1]->team2_score;

                                    //setting totalScore
                                    $matchupData['totalScoreTeam1'] = $matchupData['scoreTeam1Round1'] + $matchupData['scoreTeam1Round2'];
                                    $matchupData['totalScoreTeam2'] = $matchupData['scoreTeam2Round1'] + $matchupData['scoreTeam2Round2'];

                                    //setting seed number
                                    $matchupData['seedNumberTeam1'] = $getSchedulePlayin[$i]->team1_seed;
                                    $matchupData['seedNumberTeam2'] = $getSchedulePlayin[$i]->team2_seed;

                                    //setting winning team. Used to set special styling
                                    if ($matchupData['scoreTeam1Round1'] != 0 && $matchupData['scoreTeam1Round2'] != 0 && $matchupData['scoreTeam2Round1'] != 0 && $matchupData['scoreTeam2Round2'] != 0) {
                                        if ($matchupData['totalScoreTeam1'] > $matchupData['totalScoreTeam2']) {
                                            $matchupData['winner'] = 'team1';
                                        } else if ($matchupData['totalScoreTeam1'] < $returnData['totalScoreTeam2']) {
                                            $matchupData['winner'] = 'team2';
                                        } else {
                                            //if totalScore of team1 and 2 equals -> team with highier seed wins
                                            // if team's seed equals -> use the same criteria as when calculating position of team in season standigs (sort by points, then by balance, finally by totalScore)
                                            if ($matchupData['seedNumberTeam1'] == $matchupData['seedNumberTeam2']) {
                                                $dolceTeamSeasonStandings = $standingsDolce[$getSchedulePlayin[$i]->id_user_team1];
                                                $gabbanaTeamSeasonStandings = $standingsGabbana[$getSchedulePlayin[$i]->id_user_team2];

                                                if ($dolceTeamSeasonStandings['points'] > $gabbanaTeamSeasonStandings['points']) {
                                                    $matchupData['winner'] = 'team1';
                                                } else if ($dolceTeamSeasonStandings['points'] < $gabbanaTeamSeasonStandings['points']) {
                                                    $matchupData['winner'] = 'team2';
                                                } else {
                                                    if ($dolceTeamSeasonStandings['balance'] > $gabbanaTeamSeasonStandings['balance']) {
                                                        $matchupData['winner'] = 'team1';
                                                    } else if ($dolceTeamSeasonStandings['balance'] < $gabbanaTeamSeasonStandings['balance']) {
                                                        $matchupData['winner'] = 'team2';
                                                    } else {
                                                        if ($dolceTeamSeasonStandings['totalScore'] > $gabbanaTeamSeasonStandings['totalScore']) {
                                                            $matchupData['winner'] = 'team1';
                                                        } else if ($dolceTeamSeasonStandings['totalScore'] < $gabbanaTeamSeasonStandings['totalScore']) {
                                                            $matchupData['winner'] = 'team2';
                                                        }
                                                    }
                                                }
                                            } else {
                                                //if totalScore of team1 and 2 equals -> team with highier seed wins
                                                $matchupData['winner'] = ($matchupData['seedNumberTeam1'] > $matchupData['seedNumberTeam2']) ? 'team2' : 'team1';
                                            }
                                        }
                                    }

                                    //Fill in winnerTotalWinsNumber, winnerTotalScore as those 2 parameters will be used to define seed number in playoff
                                    if ($matchupData['winner'] == 'team1') {
                                        $dolceTeamSeasonStandings = $standingsDolce[$getSchedulePlayin[$i]->id_user_team1];
                                        $playinWins = 0;

                                        if ($matchupData['scoreTeam1Round1'] > $matchupData['scoreTeam2Round1']) {
                                            $playinWins++;
                                        }
                                        if ($matchupData['scoreTeam1Round2'] > $matchupData['scoreTeam2Round2']) {
                                            $playinWins++;
                                        }

                                        $matchupData['winnerTotalWinsNumber'] = $dolceTeamSeasonStandings['wins'] + $playinWins;
                                        $matchupData['winnerTotalScore'] = $dolceTeamSeasonStandings['totalScore'] +  $matchupData['totalScoreTeam1'];
                                    } else if ($matchupData['winner'] == 'team2') {
                                        $gabbanaTeamSeasonStandings = $standingsGabbana[$getSchedulePlayin[$i]->id_user_team2];
                                        $playinWins = 0;

                                        if ($matchupData['scoreTeam2Round1'] > $matchupData['scoreTeam1Round1']) {
                                            $playinWins++;
                                        }
                                        if ($matchupData['scoreTeam2Round2'] > $matchupData['scoreTeam1Round2']) {
                                            $playinWins++;
                                        }

                                        $matchupData['winnerTotalWinsNumber'] = $gabbanaTeamSeasonStandings['wins'] + $playinWins;
                                        $matchupData['winnerTotalScore'] = $gabbanaTeamSeasonStandings['totalScore'] +  $matchupData['totalScoreTeam2'];
                                    }


                                    array_push($playinData, $matchupData);

                                    $i++;
                                }

                                return $playinData;
                            }

                            function drawMatchups($matchupData)
                            {
                                $isPlayinPhaseFinished = array();

                                $i = 1;
                                foreach ($matchupData as $data) {
                                    array_push($isPlayinPhaseFinished, $data['winner'] != 'none' ? 1 : 0);

                                    echo '<div class="pairLadderContainer flexDirectionColumn">';

                                    $team1AddedStyle = '';
                                    $team2AddedStyle = '';
                                    switch ($data['winner']) {
                                        case 'team1':
                                            $team1AddedStyle = ' winner';
                                            $team2AddedStyle = ' looser';
                                            break;
                                        case 'team2':
                                            $team1AddedStyle = ' looser';
                                            $team2AddedStyle = ' winner';
                                            break;
                                    }

                                    $totalScoreTeam1 = $data['totalScoreTeam1'] == 0 ? '' : $data['totalScoreTeam1'];
                                    $totalScoreTeam2 = $data['totalScoreTeam2'] == 0 ? '' : $data['totalScoreTeam2'];

                                    echo '  <div class="teamLadderContainer">';
                                    echo '      <div class="seedNumberContainer">' . $data['seedNumberTeam1'] . '</div>';
                                    echo '      <div class="teamNameContainer matchupTableFirstRow' . $team1AddedStyle . '">';
                                    echo '          <span class="playoffLadderContent">' . $data['team1Name'] . '</span>';
                                    echo '          <span class="score">' . $data['scoreTeam1Round1'] . '</span>';
                                    echo '          <span class="score">' .  $data['scoreTeam1Round2'] . '</span>';
                                    echo '          <span class="score">' . $totalScoreTeam1 . '</span>';
                                    echo '      </div>';
                                    echo '  </div>';
                                    echo '  <div class="teamLadderContainer">';
                                    echo '      <div class="seedNumberContainer">' . $data['seedNumberTeam2'] . '</div>';
                                    echo '      <div class="teamNameContainer' . $team2AddedStyle . '">';
                                    echo '          <span class="playoffLadderContent">' . $data['team2Name'] . '</span>';
                                    echo '          <span class="score">' . $data['scoreTeam2Round1'] . '</span>';
                                    echo '          <span class="score">' . $data['scoreTeam2Round2'] . '</span>';
                                    echo '          <span class="score">' . $totalScoreTeam2 . '</span>';
                                    echo '      </div>';
                                    echo '  </div>';

                                    echo '</div>';

                                    $i++;
                                }

                                return $isPlayinPhaseFinished[0] * $isPlayinPhaseFinished[1] * $isPlayinPhaseFinished[2];
                            }

                            function drawPlayOffSeed($matchupData)
                            {
                                function compareWinners($a, $b)
                                {
                                    // sort by total wins
                                    $retval = strnatcmp($b['winnerTotalWinsNumber'], $a['winnerTotalWinsNumber']);
                                    // if total wins are identical, sort by total score
                                    if (!$retval) {
                                        $retval = (int)$b['winnerTotalScore'] - (int)$a['winnerTotalScore'];
                                    }

                                    return $retval;
                                }

                                function compareLoosers($a, $b)
                                {
                                    // sort by score gap to winner
                                    $retval = strnatcmp($b['scoreGapToWinner'], $a['scoreGapToWinner']);

                                    return -$retval;
                                }

                                $luckyLooserData = array();
                                $winnersData = array();

                                foreach ($matchupData as $matchup) {
                                    if ($matchup['winner'] == 'team1') {
                                        array_push($winnersData, array('teamName' => $matchup['team1Name'], 'winnerTotalWinsNumber' => $matchup['winnerTotalWinsNumber'], 'winnerTotalScore' => $matchup['winnerTotalScore']));

                                        array_push($luckyLooserData,  array('teamName' => $matchup['team2Name'], 'scoreGapToWinner' => $matchup['totalScoreTeam1'] - $matchup['totalScoreTeam2']));
                                    } else if ($matchup['winner'] == 'team2') {
                                        array_push($winnersData, array('teamName' => $matchup['team2Name'], 'winnerTotalWinsNumber' => $matchup['winnerTotalWinsNumber'], 'winnerTotalScore' => $matchup['winnerTotalScore']));

                                        array_push($luckyLooserData,  array('teamName' => $matchup['team1Name'], 'scoreGapToWinner' => $matchup['totalScoreTeam2'] - $matchup['totalScoreTeam1']));
                                    }
                                }

                                uasort($winnersData, 'compareWinners');
                                uasort($luckyLooserData, 'compareLoosers');

                                array_push($winnersData, $luckyLooserData[array_key_first($luckyLooserData)]);

                                echo '  <div class="advancedToPlayoffContainer flexDirectionColumn marginBottom20">';

                                $i = 1;
                                foreach ($winnersData as $team) {
                                    $suffix = $i == 4 ? ' <span class="luckyLooser">( lucky loser )</span>' : '';
                                    echo '<div class="advancedToPlayoffRow displayFlex flexDirectionRow">';
                                    echo '  <div class="advancedToPlayoffSeedNumber">';
                                    echo $i . '.';
                                    echo '  </div>';
                                    echo '  <div class="advancedToPlayoffTeam">';
                                    echo $team['teamName'] . $suffix;
                                    echo '  </div>';
                                    echo '</div>';

                                    $i++;
                                }

                                echo '  </div>';
                            }

                            //content of the playin standings page
                            $matchupData = getData();

                            echo '<div>';
                            echo '  <div class="playoffLadder displayFlex justifyContentEvenly playoffLadderflexDirection ">';
                            echo '      <div class="phaseContainer">';
                            echo '          <div class="playoffPhaseTitlePosition marginTop20 marginBottom40">';
                            echo '              <span class="playoffPhaseTitle">play in</span>';
                            echo '          </div>';
                            $isPlayinPhaseFinished = drawMatchups($matchupData);
                            echo '      </div>';

                            if ($isPlayinPhaseFinished == 1) {
                                echo '      <div class="phaseContainer">';
                                echo '          <div class="playoffPhaseTitlePosition marginTop20 marginBottom40">';
                                echo '              <span class="playoffPhaseTitle">rozstawienie drużyn w fazie play off</span>';
                                echo '          </div>';
                                drawPlayOffSeed($matchupData);
                                echo '      </div>';
                            }

                            echo '  </div>';
                            echo '</div>';
                            //custom code ends here

                            echo apply_filters('hestia_filter_blog_social_icons', '');

                            if (comments_open() || get_comments_number()) :
                                comments_template();
                            endif;
                            ?>

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
    <script type="text/javascript">
        (function() {
            var title = document.querySelector('#primary > div.container > div > div > h1');
            title.innerHTML = 'tabela - ' + title.innerHTML;
        })();
    </script>
    <?php get_footer(); ?>
<?php
/*
Template Name: Dashboard
Description: Render megaliga dashboard front page: Tribune, table, score of last round
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

                            function drawChampion($championData, $title)
                            {
                                echo '  <div class="playoffPhaseTitlePosition marginTop20"><span class="playoffPhaseTitle">' . $title . '</span></div>';
                                echo '  <div class="winnerContainerDashboard">';
                                echo '      <div class="winnerImgContainer displayFlex">';
                                echo '          <img class="winner" src="' . $championData->logo_url . '" width="75px" height="75px">';
                                echo '      </div>';
                                echo '      <div class="winnerNameContainer displayFlex center">';
                                echo '        <span class="winnerName">' . $championData->team_name . '</span>';
                                echo '      </div>';
                                echo '  </div>';
                            }

                            function drawGrandPrixChampion($championData, $title)
                            {
                                echo '<div class="displayFlex flexDirectionColumn justifyContentCenter">';
                                echo '  <div class="gpWinnerTribuneContainer">';
                                echo '      <div class="displayFlex">';
                                echo '          <img src="https://megaliga.eu/wp-content/uploads/2024/02/pucharGP.png" width="75px" height="100px">';
                                echo '      </div>';
                                echo '      <div class="marginLeft10 displayFlex flexDirectionColumn">';
                                echo '        <span class="gpChampionTitle">' . $title . '</span>';
                                echo '        <span class="gpWinnerName">' . $championData->user_name . '</span>';
                                echo '      </div>';
                                echo '  </div>';
                                echo '</div>';
                            }

                            function drawCurrentRoundScore($queryTeam1Result, $queryTeam2Result, $gameIdentificationData, $groupName, $side, $round_number)
                            {
                                $margin = $side == 'left' ? 'marginRight40' : '';
                                echo '<table class="megaligaScoresTable scheduleTable ' . $margin . '" border="0">';
                                echo '  <tr><td colspan="6" class="scheduleTableName textLeft">Grupa ' . $groupName . '</td></tr>';
                                echo '  <tr>
                            <th colspan="3" class="scheduleHeader textLeft">megaliga</th>
                            <th colspan="3" class="scheduleHeader textRight">' . $round_number . '. kolejka</th>
                        </tr>';
                                $i = 0;
                                foreach ($gameIdentificationData as $gameIdentity) {
                                    //take team1 and team2 records that represents the same game in schedule
                                    $team1Data = array();
                                    $team2Data = array();
                                    foreach ($queryTeam1Result as $field) {
                                        if ($field->id_user_team1 == $gameIdentity->id_user_team1) {
                                            $team1Data = $field;
                                        }
                                    }
                                    foreach ($queryTeam2Result as $field) {
                                        if ($field->id_user_team2 == $gameIdentity->id_user_team2) {
                                            $team2Data = $field;
                                        }
                                    }

                                    $trClass = $i % 2 == 0 ? 'even' : 'odd';
                                    echo '<tr class="' . $trClass . '">
                            <td class="scheduleTd textLeft">' . $team1Data->team_name . '</td>
                            <td class="scheduleTdImg">
                                <img src="' . $team1Data->logo_url . '" width="40px" height="40px" class="floatRight">
                            </td>';
                                    $score1 = $team1Data->team1_score ? $team1Data->team1_score : '-';
                                    $score1 = strlen($score1) === 2 ? $score1 . '&nbsp;' : $score1;
                                    $score2 = $team2Data->team2_score ? $team2Data->team2_score : '-';
                                    $scheduleLeft = $team1Data->team1_score ? '' : 'scheduleNoScoreLeft';
                                    echo '<td class="scheduleTd scheduleScore ' . $scheduleLeft . ' floatRight">' . $score1 . ' :</td>
                            <td class="scheduleTd scheduleScore scheduleScoreRight floatLeft">' . $score2 . '</td>';

                                    echo '  <td class="scheduleTdImg">
                                <img src="' . $team2Data->logo_url . '" width="40px" height="40px" class="floatLeft">
                            </td>
                            <td class="scheduleTd textRight">' . $team2Data->team_name . '</td>
                        </tr>';

                                    $i++;
                                }

                                echo '</table>';
                            }

                            function drawCurrentGrandPrixRoundScore($standings, $showPoints)
                            {
                                echo '<table class="megaligaScoresTable scheduleTable" border="0">';
                                echo '  <tr>
                            <th class="scheduleHeader textLeft">trener</th>
                            <th colspan="2" class="scheduleHeader textRight">punkty</th>
                        </tr>';
                                $i = 1;
                                foreach ($standings as $trainer) {
                                    $trClass = $i % 2 == 0 ? 'even' : 'odd';
                                    echo '<tr class="' . $trClass . '">
                        <td class="scheduleTdImg paddingLeft10">' . $i . '</td>
                        <td class="scheduleTdImg">' . $trainer['trainerName'] . '</td>
                        <td class="scheduleTdImg">' . ($showPoints ? $trainer['points'] : 'tbd') . '</td>';
                                    echo '</tr>';

                                    $i++;
                                }

                                echo '</table>';
                            }

                            function getPlayedRounds($roundsCalendarData, $currentDateTimestamp)
                            {
                                $playedRounds = array();
                                foreach ($roundsCalendarData as $roundCalendar) {
                                    $roundDateTimestamp = strtotime($roundCalendar->round_date);
                                    if ($currentDateTimestamp > $roundDateTimestamp) {
                                        array_push($playedRounds, $roundCalendar->round_number);
                                    }
                                }

                                return $playedRounds;
                            }

                            function compareTrainers($a, $b)
                            {
                                // sort by points
                                $retval = strnatcmp($b['points'], $a['points']);

                                // if points are identical, sort number of completed GP's
                                if (!$retval) {
                                    $retval = (int)$b['gamesPlayed'] - (int)$a['gamesPlayed'];
                                }

                                return $retval;
                            }

                            $getRoundsCalendar = $wpdb->get_results('SELECT round_number, round_date FROM megaliga_round_calendar');
                            $currentDate = getdate();

                            $currentDateTimestamp = strtotime($currentDate['year'] . '-' . $currentDate['mon'] . '-' . $currentDate['mday']);

                            // $currentDateTimestamp = strtotime('2024-04-29');

                            //find last round
                            $playedRounds = getPlayedRounds($getRoundsCalendar, $currentDateTimestamp);

                            //last played round is the highiest round from those which have been played
                            $lastPlayedRound = count($playedRounds) > 0 ? max($playedRounds) : 0;

                            if ($lastPlayedRound > 0) {
                                //get teams for Dolce ligue
                                $getSchedule4DolceTeam1 = $wpdb->get_results('SELECT megaliga_team_names.name as "team_name", megaliga_schedule.team1_score, megaliga_schedule.id_user_team1, megaliga_user_data.logo_url FROM megaliga_user_data, megaliga_team_names, megaliga_schedule WHERE megaliga_user_data.ID = megaliga_schedule.id_user_team1 AND megaliga_user_data.ligue_groups_id = megaliga_schedule.id_ligue_group AND megaliga_schedule.id_ligue_group = 1 AND megaliga_user_data.team_names_id = megaliga_team_names.team_names_id AND megaliga_schedule.round_number = ' . $lastPlayedRound);

                                $getSchedule4DolceTeam2 = $wpdb->get_results('SELECT megaliga_team_names.name as "team_name", megaliga_schedule.team2_score, megaliga_schedule.id_user_team2, megaliga_user_data.logo_url FROM megaliga_user_data, megaliga_team_names, megaliga_schedule WHERE megaliga_user_data.ID = megaliga_schedule.id_user_team2 AND megaliga_user_data.ligue_groups_id = megaliga_schedule.id_ligue_group AND megaliga_schedule.id_ligue_group = 1 AND megaliga_user_data.team_names_id = megaliga_team_names.team_names_id AND megaliga_schedule.round_number = ' . $lastPlayedRound);

                                //get teams for Gabbama ligue
                                $getSchedule4GabbanaTeam1 = $wpdb->get_results('SELECT megaliga_team_names.name as "team_name", megaliga_schedule.team1_score, megaliga_schedule.id_user_team1, megaliga_user_data.logo_url FROM megaliga_user_data, megaliga_team_names, megaliga_schedule WHERE megaliga_user_data.ID = megaliga_schedule.id_user_team1 AND megaliga_user_data.ligue_groups_id = megaliga_schedule.id_ligue_group AND megaliga_schedule.id_ligue_group = 2 AND megaliga_user_data.team_names_id = megaliga_team_names.team_names_id AND megaliga_schedule.round_number = ' . $lastPlayedRound);

                                $getSchedule4GabbanaTeam2 = $wpdb->get_results('SELECT megaliga_team_names.name as "team_name", megaliga_schedule.team2_score, megaliga_schedule.id_user_team2, megaliga_user_data.logo_url FROM megaliga_user_data, megaliga_team_names, megaliga_schedule WHERE megaliga_user_data.ID = megaliga_schedule.id_user_team2 AND megaliga_user_data.ligue_groups_id = megaliga_schedule.id_ligue_group AND megaliga_schedule.id_ligue_group = 2 AND megaliga_user_data.team_names_id = megaliga_team_names.team_names_id AND megaliga_schedule.round_number = ' . $lastPlayedRound);

                                //get all games for Dolce for given round
                                $getGames4Dolce = $wpdb->get_results('SELECT id_schedule, id_user_team1, id_user_team2 FROM megaliga_schedule WHERE id_ligue_group = 1 AND round_number = ' . $lastPlayedRound);

                                //get all games for Gabbana for given round
                                $getGames4Gabbana = $wpdb->get_results('SELECT id_schedule, id_user_team1, id_user_team2 FROM megaliga_schedule WHERE id_ligue_group = 2 AND round_number = ' . $lastPlayedRound);
                            }

                            // get data for displaying scores of last GP round
                            $getGrandPrixRoundsCalendar = $wpdb->get_results('SELECT round_number, round_date FROM megaliga_grandprix_round_calendar');
                            //find last GP round
                            $playedGrandPrixRounds = getPlayedRounds($getGrandPrixRoundsCalendar, $currentDateTimestamp);
                            //last played round is the highiest round from those which have been played
                            $lastPlayedGrandPrixRound = count($playedGrandPrixRounds) > 0 ? max($playedGrandPrixRounds) : 0;

                            // defines if to show scores of trainers for current round. Scores will be visible as soon as results for given round are added by admin
                            $showGrandPrixRoundPoints = false;
                            if ($lastPlayedGrandPrixRound > 0) {
                                $getUserDataQuery = $wpdb->get_results('SELECT wp_users.user_login, megaliga_user_data.ID FROM wp_users, megaliga_user_data WHERE wp_users.ID = megaliga_user_data.ID');

                                $standingsData = array();
                                foreach ($getUserDataQuery as $user) {
                                    $standingsData[$user->ID] = array('trainerName' => $user->user_login, 'ID' => $user->ID, 'points' => 0, 'showPoints' => false);
                                }

                                $getGrandPrixResultQuery = $wpdb->get_results('SELECT * FROM megaliga_grandprix_results WHERE round_number = ' . $lastPlayedGrandPrixRound);

                                if (count($getGrandPrixResultQuery) > 0) {
                                    $showGrandPrixRoundPoints = true;
                                    $getTrainersBetsQuery = $wpdb->get_results('SELECT * FROM megaliga_grandprix_bets WHERE round_number =' . $lastPlayedGrandPrixRound);

                                    foreach ($getTrainersBetsQuery as $trainerBet) {
                                        $fieldNameList = array('player_1', 'player_2', 'player_3', 'player_4', 'player_5', 'player_6', 'player_7', 'player_8', 'player_9', 'player_10', 'player_11', 'player_12', 'player_13', 'player_14', 'player_15', 'player_16');

                                        for ($i = 0; $i < 16; $i++) {
                                            if ($trainerBet->{$fieldNameList[$i]} == $getGrandPrixResultQuery[0]->{$fieldNameList[$i]}) {
                                                // if trainer bet correctly position of given player -> add 1 point
                                                $standingsData[$trainerBet->ID]['points'] = $standingsData[$trainerBet->ID]['points'] + 1;

                                                if ($trainerBet->{$fieldNameList[$i]} == 1) {
                                                    //additionally if correctly bet position is exactly 1st place -> add additional 1 point
                                                    $standingsData[$trainerBet->ID]['points'] = $standingsData[$trainerBet->ID]['points'] + 1;
                                                }

                                                if ($trainerBet->{$fieldNameList[$i]} >= 1 && $trainerBet->{$fieldNameList[$i]} <= 4) {
                                                    //additionally if correctly bet position is from place 1-4 -> add additional 1 point
                                                    $standingsData[$trainerBet->ID]['points'] = $standingsData[$trainerBet->ID]['points'] + 1;
                                                }

                                                if ($trainerBet->{$fieldNameList[$i]} >= 1 && $trainerBet->{$fieldNameList[$i]} <= 8) {
                                                    //additionally if correctly bet position is from place 1-8 -> add additional 1 point
                                                    $standingsData[$trainerBet->ID]['points'] = $standingsData[$trainerBet->ID]['points'] + 1;
                                                }
                                            }
                                        }
                                    }

                                    uasort($standingsData, 'compareTrainers');
                                }
                            }

                            //get data for current champion
                            $getChampionData = $wpdb->get_results('SELECT team_name, logo_url FROM megaliga_champion');

                            //get data for previous champions
                            $getPreviousChampions = $wpdb->get_results('SELECT team_name, logo_url, season_name FROM megaliga_history_champion ORDER BY season_name DESC');

                            //get data for current GP champion
                            $getGrandPrixChampionData = $wpdb->get_results('SELECT user_name FROM megaliga_grandprix_champion');

                            //get data for previous GP champions
                            $getPreviousGrandPrixChampions = $wpdb->get_results('SELECT user_name, season_name FROM megaliga_grandprix_champion_history ORDER BY season_name DESC');

                            //custom code starts here
                            echo '<div class="displayFlex championsContainer">';
                            echo '  <div class="currentChampion">';
                            drawChampion($getChampionData[0], 'Obecny Mistrz megaligi');
                            echo '  </div>';

                            if (count($getGrandPrixChampionData) > 0) {
                                echo '  <div class="currentChampion">';
                                drawGrandPrixChampion($getGrandPrixChampionData[0], 'Obecny Mistrz Grand Prix');
                                echo '  </div>';
                            }

                            echo '</div>';

                            echo '<div class="displayFlex dashboardContainer">';
                            echo '  <div class="displayFlex dashboardCol1">';
                            echo '      <div class="individualCommentsTitle">Komunikaty</div>';
                            echo '      <div class="individualCommentsContainer">';
                            the_content();
                            echo '      </div>';
                            echo '      <div class="tribuneContainer">';

                            echo apply_filters('hestia_filter_blog_social_icons', '');

                            if (comments_open() || get_comments_number()) :
                                comments_template();
                            endif;

                            echo '      </div>';
                            echo '  </div>';
                            echo '  <div class="displayFlex dashboardCol2">';

                            if ($lastPlayedRound > 0) {
                                echo '      <div class="individualCommentsTitle">Wyniki ostatniej kolejki megaligi</div>';
                                echo '      <div class="scoreTableDolce">';
                                drawCurrentRoundScore($getSchedule4DolceTeam1, $getSchedule4DolceTeam2, $getGames4Dolce, 'dolce', 'right', $lastPlayedRound);
                                echo '      </div>';
                                echo '      <div class="scoreTableGabbana">';
                                drawCurrentRoundScore($getSchedule4GabbanaTeam1, $getSchedule4GabbanaTeam2, $getGames4Gabbana, 'gabbana', 'right', $lastPlayedRound);
                                echo '      </div>';
                            }

                            if ($lastPlayedGrandPrixRound > 0) {
                                echo '      <div class="individualCommentsTitle">Wyniki ' . $lastPlayedGrandPrixRound . ' kolejki Grand Prix</div>';
                                echo '      <div class="scoreTableDolce">';
                                drawCurrentGrandPrixRoundScore($standingsData, $showGrandPrixRoundPoints);
                                echo '      </div>';
                            }

                            echo '      <div class="individualCommentsTitle">Poprzedni Mistrzowie Megaligi</div>';
                            foreach ($getPreviousChampions as $champion) {
                                echo '  <div class="previousChampion">';
                                $title = 'Mistrz megaligi ' . $champion->season_name;
                                drawChampion($champion, $title);
                                echo '  </div>';
                            }

                            if (count($getPreviousGrandPrixChampions) > 0) {
                                echo '      <div class="individualCommentsTitle">Poprzedni Mistrzowie Grand Prix</div>';
                                foreach ($getPreviousGrandPrixChampions as $gpChampion) {
                                    echo '  <div class="previousChampion">';
                                    $title = 'Mistrz Grand Prix ' . $gpChampion->season_name;
                                    drawGrandPrixChampion($gpChampion, $title);
                                    echo '  </div>';
                                }
                            }

                            echo '  </div>';
                            echo '</div>';
                            //custom code ends here
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

    <?php get_footer(); ?>
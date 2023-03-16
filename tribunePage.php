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

                            $getRoundsCalendar = $wpdb->get_results('SELECT round_number, round_date FROM megaliga_round_calendar');
                            $currentDate = getdate();

                            $currentDateTimestamp = strtotime($currentDate['year'] . '-' . $currentDate['mon'] . '-' . $currentDate['mday']);

                            // $currentDateTimestamp = strtotime('2023-05-25');

                            //find last round
                            $playedRounds = array();
                            foreach ($getRoundsCalendar as $roundCalendar) {
                                $roundDateTimestamp = strtotime($roundCalendar->round_date);
                                if ($currentDateTimestamp > $roundDateTimestamp) {
                                    array_push($playedRounds, $roundCalendar->round_number);
                                }
                            }

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


                            //get data for current champion
                            $getChampionData = $wpdb->get_results('SELECT team_name, logo_url FROM megaliga_champion');

                            //get data for previous champions
                            $getPreviousChampions = $wpdb->get_results('SELECT team_name, logo_url, season_name FROM megaliga_history_champion ORDER BY season_name DESC');

                            //custom code starts here
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
                            echo '      <div class="currentChampion">';
                            drawChampion($getChampionData[0], 'Mistrz megaligi');
                            echo '      </div>';
                            foreach ($getPreviousChampions as $champion) {
                                echo '  <div class="previousChampion">';
                                $title = 'Mistrz megaligi ' . $champion->season_name;
                                drawChampion($champion, $title);
                                echo '  </div>';
                            }

                            if ($lastPlayedRound > 0) {
                                echo '      <div class="individualCommentsTitle">Wyniki ostatniej kolejki</div>';
                                echo '      <div class="scoreTableDolce">';
                                drawCurrentRoundScore($getSchedule4DolceTeam1, $getSchedule4DolceTeam2, $getGames4Dolce, 'dolce', 'right', $lastPlayedRound);
                                echo '      </div>';
                                echo '      <div class="scoreTableGabbana">';
                                drawCurrentRoundScore($getSchedule4GabbanaTeam1, $getSchedule4GabbanaTeam2, $getGames4Gabbana, 'gabbana', 'right', $lastPlayedRound);
                                echo '      </div>';
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
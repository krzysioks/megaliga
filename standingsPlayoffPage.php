<?php
/*
Template Name: Standings Playoff
Description: Shows standings of the playoff phase for two groups in the ligue. Shows the playoff ladder, which consists of 2 phases: Semifinal (relative to round 1 and 2) where the winner is team which scores more points; final and 3rd place game (relative to round 3 and 4) where the winner is team which scores more points
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

                            function getStageData($stage, $type)
                            {
                                global $wpdb;

                                $returnData = array('team1Name' => '', 'team2Name' => '', 'scoreTeam1Round1' => 0, 'scoreTeam2Round1' => 0, 'scoreTeam1Round2' => 0, 'scoreTeam2Round2' => 0, 'totalTeam1' => null, 'totalTeam2' => null, 'seedNumberTeam1' => 0, 'seedNumberTeam2' => 0, 'winner' => 'noone');

                                //get team names of both users
                                $getTeam1Name = $wpdb->get_results('SELECT megaliga_team_names.name as "team_name",megaliga_user_data.logo_url FROM megaliga_team_names, megaliga_user_data WHERE megaliga_team_names.team_names_id = megaliga_user_data.team_names_id AND megaliga_user_data.reached_playoff = 1 AND megaliga_user_data.ID = ' . $stage->id_user_team1);
                                $getTeam2Name = $wpdb->get_results('SELECT megaliga_team_names.name as "team_name",megaliga_user_data.logo_url  FROM megaliga_team_names, megaliga_user_data WHERE megaliga_team_names.team_names_id = megaliga_user_data.team_names_id AND megaliga_user_data.reached_playoff = 1 AND megaliga_user_data.ID = ' . $stage->id_user_team2);

                                $returnData['team1Name'] = $getTeam1Name[0]->team_name;
                                $returnData['team2Name'] = $getTeam2Name[0]->team_name;

                                //get teams' score for both rounds
                                $getScoreRound1 = $wpdb->get_results('SELECT team1_score, team2_score FROM megaliga_schedule_playoff WHERE id_schedule = ' . $stage->id_schedule_round1);
                                $getScoreRound2 = $wpdb->get_results('SELECT team1_score, team2_score FROM megaliga_schedule_playoff WHERE id_schedule = ' . $stage->id_schedule_round2);

                                //setting score for teams for  round 1 and 2
                                $returnData['scoreTeam1Round1'] = $getScoreRound1[0]->team1_score;
                                $returnData['scoreTeam2Round1'] = $getScoreRound1[0]->team2_score;

                                $returnData['scoreTeam1Round2'] = $getScoreRound2[0]->team1_score;
                                $returnData['scoreTeam2Round2'] = $getScoreRound2[0]->team2_score;

                                //setting totalScore
                                $returnData['totalScoreTeam1'] = $returnData['scoreTeam1Round1'] + $returnData['scoreTeam1Round2'];
                                $returnData['totalScoreTeam2'] = $returnData['scoreTeam2Round1'] + $returnData['scoreTeam2Round2'];

                                //setting seed number
                                $returnData['seedNumberTeam1'] = $stage->seed_number_team1;
                                $returnData['seedNumberTeam2'] = $stage->seed_number_team2;

                                //setting winning team. Used to set special styling
                                if ($returnData['scoreTeam1Round1'] != 0 && $returnData['scoreTeam1Round2'] != 0 && $returnData['scoreTeam2Round1'] != 0 && $returnData['scoreTeam2Round2'] != 0) {
                                    if ($returnData['totalScoreTeam1'] > $returnData['totalScoreTeam2']) {
                                        $returnData['winner'] = 'team1';
                                    } else if ($returnData['totalScoreTeam1'] < $returnData['totalScoreTeam2']) {
                                        $returnData['winner'] = 'team2';
                                    } else {
                                        //if totalScore of team1 and 2 equals -> team with highier seed wins
                                        $returnData['winner'] = ($returnData['seedNumberTeam1'] > $returnData['seedNumberTeam2']) ? 'team2' : 'team1';
                                    }

                                    //if winner is defined -> save his ID to megaliga_champion to be able to show current champion on dashboard if data is of type "final"
                                    if ($type == 'final') {
                                        //define if insert od update record of megaliga_champion
                                        $checkIfRecordExist = $wpdb->get_results('SELECT COUNT(*) as "champion" FROM megaliga_champion');

                                        // prepare data for submission
                                        $submitDataArray = array();
                                        $submitDataArray['team_name'] = $returnData['winner'] == 'team1' ? $getTeam1Name[0]->team_name : $getTeam2Name[0]->team_name;
                                        $submitDataArray['logo_url'] = $returnData['winner'] == 'team1' ? $getTeam1Name[0]->logo_url : $getTeam2Name[0]->logo_url;

                                        if ($checkIfRecordExist[0]->champion == 1) {
                                            //update record
                                            $where = array('id_champion' => '1');
                                            $wpdb->update('megaliga_champion', $submitDataArray, $where);
                                        } else {
                                            //insert record
                                            $wpdb->insert('megaliga_champion', $submitDataArray);
                                        }
                                    }
                                }

                                return $returnData;
                            }

                            function drawSecondStageMatchup($data, $title, $marginSize)
                            {
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

                                echo '<div class="playoffPhaseTitlePosition marginTop' . $marginSize . '"><span class="playoffPhaseTitle">' . $title . '</span></div>';

                                echo '<div class="pairLadderContainer flexDirectionColumn">';
                                echo '    <div class="teamLadderContainer">';
                                echo '        <div class="seedNumberContainer">' . $data['seedNumberTeam1'] . '</div>';
                                echo '        <div class="teamNameContainer matchupTableFirstRow' . $team1AddedStyle . '">';
                                echo '            <span class="playoffLadderContent">' . $data['team1Name'] . '</span>';
                                echo '            <span class="score">' . $data['scoreTeam1Round1'] . '</span>';
                                echo '            <span class="score">' . $data['scoreTeam1Round2'] . '</span>';
                                echo '            <span class="score">' . $totalScoreTeam1 . '</span>';
                                echo '        </div>';
                                echo '    </div>';
                                echo '    <div class="teamLadderContainer">';
                                echo '        <div class="seedNumberContainer">' . $data['seedNumberTeam2'] . '</div>';
                                echo '        <div class="teamNameContainer' . $team2AddedStyle . '">';
                                echo '            <span class="playoffLadderContent">' . $data['team2Name'] . '</span>';
                                echo '            <span class="score">' . $data['scoreTeam2Round1'] . '</span>';
                                echo '            <span class="score">' . $data['scoreTeam2Round2'] . '</span>';
                                echo '            <span class="score">' . $totalScoreTeam2 . '</span>';
                                echo '        </div>';
                                echo '    </div>';
                                echo '</div>';
                            }

                            function drawFirstStageMatchup($data)
                            {
                                echo '<div class="pairLadderContainer flexDirectionColumn firstStageTable">';

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
                            }


                            function drawLadder()
                            {
                                global $wpdb;

                                //get data for semifinal stage
                                $semifinalData = array();

                                $getSemifinalStage = $wpdb->get_results('SELECT id_user_team1, id_user_team2, stage, id_schedule_round1, id_schedule_round2, seed_number_team1, seed_number_team2 FROM megaliga_playoff_ladder WHERE stage = "semifinal"');

                                $i = 0;
                                foreach ($getSemifinalStage as $stage) {
                                    $semifinalData[$i] = getStageData($stage, 'semifinals');
                                    $i++;
                                }

                                //draw 1st semifinal stage
                                echo '<div class="phaseContainer order-1">';
                                echo '<div class="playoffPhaseTitlePosition marginTop20"><span class="playoffPhaseTitle">półfinał</span></div>';
                                drawFirstStageMatchup($semifinalData[0]);
                                echo '</div>';


                                //get data for final and 3rd place matchup stage
                                $finalData = array();
                                $thridPlaceData = array();

                                $getfinalStage = $wpdb->get_results('SELECT id_user_team1, id_user_team2, stage, id_schedule_round1, id_schedule_round2, seed_number_team1, seed_number_team2 FROM megaliga_playoff_ladder WHERE stage = "final"');

                                $getThirdPlaceStage = $wpdb->get_results('SELECT id_user_team1, id_user_team2, stage, id_schedule_round1, id_schedule_round2, seed_number_team1, seed_number_team2 FROM megaliga_playoff_ladder WHERE stage = "3rdplace"');

                                $finalData = getStageData($getfinalStage[0], 'final');
                                $thridPlaceData = getStageData($getThirdPlaceStage[0], '3rdpalce');

                                //draw 2nd stage
                                echo '<div class="phaseContainer order-3">';

                                //draw results
                                //draw results only when winner of each matchup is complete
                                if ($finalData['winner'] != 'noone' && $thridPlaceData['winner'] != 'noone') {
                                    //get data
                                    $winnerId = $finalData['winner'] == 'team1' ? $getfinalStage[0]->id_user_team1 : $getfinalStage[0]->id_user_team2;

                                    $getWinnerData = $wpdb->get_results('SELECT logo_url FROM megaliga_user_data WHERE reached_playoff = 1 AND ID = ' . $winnerId);

                                    $winnerTeamNameKey = $finalData['winner'] . 'Name';
                                    echo '<div class="order-3">';
                                    echo '  <div class="playoffPhaseTitlePosition marginTop20"><span class="playoffPhaseTitle">mistrz megaligi</span></div>';
                                    echo '  <div class="winnerContainer">';
                                    echo '      <div class="winnerImgContainer displayFlex">';
                                    echo '          <img class="winner" src="' . $getWinnerData[0]->logo_url . '" width="75px" height="75px">';
                                    echo '      </div>';
                                    echo '      <div class="winnerNameContainer displayFlex center">';
                                    echo '        <span class="winnerName">' . $finalData[$winnerTeamNameKey] . '</span>';
                                    echo '      </div>';
                                    echo '  </div>';
                                    echo '</div>';
                                }

                                //draw final stage
                                echo '  <div clas="order-1">';
                                drawSecondStageMatchup($finalData, 'finał', '20');
                                echo '  </div>';
                                //draw 3rd place stage
                                echo '  <div clas="order-2">';
                                drawSecondStageMatchup($thridPlaceData, 'mecz o 3 miejsce', '40');
                                echo '  </div>';
                                echo '</div>';



                                //draw 2nd semifinal stage
                                echo '<div class="phaseContainer order-2">';
                                echo '<div class="playoffPhaseTitlePosition marginTop20"><span class="playoffPhaseTitle">półfinał</span></div>';
                                drawFirstStageMatchup($semifinalData[1]);
                                echo '</div>';
                            }

                            $getSchedulePlayoff = $wpdb->get_results('SELECT team1_score, team2_score, id_user_team1, id_user_team2, round_number FROM megaliga_schedule_playoff ORDER BY round_number');

                            $getPlayoffLadderData = $wpdb->get_results('SELECT id_user_team1, id_user_team2, stage, id_schedule_round1, id_schedule_round2 FROM megaliga_playoff_ladder');

                            //content of the team page
                            echo '<div>';
                            echo '  <div class="playoffLadder displayFlex playoffLadderflexDirection">';
                            drawLadder($getSchedulePlayoff);
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
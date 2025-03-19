<?php
/*
Template Name: Megaliga Playoff
Description: Shows playoff schedule and scoreboard of given round for one group in the ligue
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
                            $title = the_title('', '', false);
                            $current_user = wp_get_current_user();
                            //8 - length of "kolejka" string which is in every title of składy subpage
                            $round_number = substr($title, 0, strlen($title) - 8);
                            $userId = $current_user->ID;

                            //values for test
                            //$round_number = 1;
                            // $userId = 14; //58;//14;

                            //handle submission
                            if ($_POST['submitScore']) {
                                // returns array which define what database action shall be done on score row of given player
                                $playerKeyList = array('player1', 'player2', 'player3', 'player4', 'player5');
                                $keysToSubmitArray = array(array('key' => 'heat1', 'postKey' => '1'), array('key' => 'heat2', 'postKey' => '2'), array('key' => 'heat3', 'postKey' => '3'), array('key' => 'heat4', 'postKey' => '4'), array('key' => 'heat5', 'postKey' => '5'), array('key' => 'heat6', 'postKey' => '6'), array('key' => 'heat7', 'postKey' => '7'), array('key' => 'setplays', 'postKey' => 'setplay'), array('key' => 'comment', 'postKey' => 'comment'));

                                function getDatabaseActionOnRow($getPlayersIdQuery, $playerKeyList, $idStartingLineup)
                                {
                                    global $wpdb;
                                    $selectedAction = array();
                                    //$row - array with player1, player2.. keys
                                    foreach ($getPlayersIdQuery as $row) {
                                        //$col - value of given key (player1, player2..)
                                        $i = 0;
                                        foreach ($row as $col) {
                                            //perform validation for existing id (omit null, 0 which indicates that given //starting lineup position is not used yet)
                                            if ($col) {
                                                $checkIfExistQuery = $wpdb->get_results('SELECT id_scores FROM megaliga_scores_playoff WHERE id_schedule = ' . $_POST['id_schedule'] . ' AND id_starting_lineup = ' . $_POST[$idStartingLineup] . ' AND id_player = ' . $col);

                                                $selectedAction[$playerKeyList[$i]] = (count($checkIfExistQuery) == 1) ? $checkIfExistQuery[0]->id_scores : 0;
                                            }
                                            $i++;
                                        }
                                    }

                                    return $selectedAction;
                                }

                                //posts score for players of given team
                                function postPlayersScore($getPlayersIdQuery, $playerKeyList, $selectedAction, $keysToSubmitArray, $idStartingLineup)
                                {
                                    global $wpdb;
                                    $score = 0;
                                    foreach ($getPlayersIdQuery as $row) {
                                        //$col - id of given player (player1, player2..)
                                        $i = 0;
                                        foreach ($row as $col) {
                                            if ($col) {
                                                //prepare data for submission
                                                $submitDataArray = array();
                                                foreach ($keysToSubmitArray as $key) {
                                                    $submitDataArray[$key['key']] = $_POST['id' . $col . '_' . $key['postKey']];
                                                    if ($key['key'] != 'comment') {
                                                        $score += $submitDataArray[$key['key']];
                                                    }
                                                }

                                                $submitDataArray['starting_order'] = $i + 1;
                                                $submitDataArray['id_schedule'] = $_POST['id_schedule'];
                                                $submitDataArray['id_starting_lineup'] = $idStartingLineup;
                                                $submitDataArray['id_player'] = $col;

                                                if ($selectedAction[$playerKeyList[$i]]) {
                                                    $where = array('id_scores' => $selectedAction[$playerKeyList[$i]]);
                                                    $wpdb->update('megaliga_scores_playoff', $submitDataArray, $where);
                                                } else {
                                                    $wpdb->insert('megaliga_scores_playoff', $submitDataArray);
                                                }
                                            }
                                            $i++;
                                        }
                                    }

                                    return $score;
                                }

                                //post score of the trainer for given team
                                function postTrainerScore($teamNumber, $selectedAction, $keysToSubmitArray, $ID)
                                {
                                    global $wpdb;
                                    $score = 0;
                                    //prepare data for submission
                                    $submitDataArray = array();
                                    foreach ($keysToSubmitArray as $key) {
                                        $submitDataArray[$key['key']] = $_POST['id_trainerTeam' . $teamNumber . '_' . $key['postKey']];
                                        if ($key['key'] != 'comment') {
                                            $score += $submitDataArray[$key['key']];
                                        }
                                    }
                                    $submitDataArray['id_schedule'] = $_POST['id_schedule'];
                                    $submitDataArray['ID'] = $ID;

                                    //$selectedActionTeam1 contains id_scores if record already exists (update); else = 0 (insert)
                                    if ($selectedAction) {
                                        $where = array('id_trainer' => $selectedAction);
                                        $wpdb->update('megaliga_trainer_score_playoff', $submitDataArray, $where);
                                    } else {
                                        $wpdb->insert('megaliga_trainer_score_playoff', $submitDataArray);
                                    }

                                    return $score;
                                }

                                //get players' id for team1
                                $getTeam1PlayersIdQuery = $wpdb->get_results('SELECT player1, player2, player3, player4, player5 FROM megaliga_starting_lineup_playoff WHERE id_starting_lineup = ' . $_POST['id_starting_lineup_team1']);

                                //get players' id for team2
                                $getTeam2PlayersIdQuery = $wpdb->get_results('SELECT player1, player2, player3, player4, player5 FROM megaliga_starting_lineup_playoff WHERE id_starting_lineup = ' . $_POST['id_starting_lineup_team2']);

                                //verify if for given player id add new record or edit existing one to megaliga_score table
                                $selectedActionTeam1 = getDatabaseActionOnRow($getTeam1PlayersIdQuery, $playerKeyList, 'id_starting_lineup_team1');
                                $selectedActionTeam2 = getDatabaseActionOnRow($getTeam2PlayersIdQuery, $playerKeyList, 'id_starting_lineup_team2');

                                //verify if to add or update trainer score data
                                $checkIfExistTrainerTeam1Query = $wpdb->get_results('SELECT id_trainer FROM megaliga_trainer_score_playoff WHERE id_schedule = ' . $_POST['id_schedule'] . ' AND ID = ' . $_POST['id_user_team1']);

                                $checkIfExistTrainerTeam2Query = $wpdb->get_results('SELECT id_trainer FROM megaliga_trainer_score_playoff WHERE id_schedule = ' . $_POST['id_schedule'] . ' AND ID = ' . $_POST['id_user_team2']);

                                $selectedActionTrainerTeam1 = (count($checkIfExistTrainerTeam1Query) == 1) ? $checkIfExistTrainerTeam1Query[0]->id_trainer : 0;
                                $selectedActionTrainerTeam2 = (count($checkIfExistTrainerTeam2Query) == 1) ? $checkIfExistTrainerTeam2Query[0]->id_trainer : 0;

                                //submit data team1
                                $team1Score = postPlayersScore($getTeam1PlayersIdQuery, $playerKeyList, $selectedActionTeam1, $keysToSubmitArray, $_POST['id_starting_lineup_team1']);
                                $trainerTeam1Score = postTrainerScore('1', $selectedActionTrainerTeam1, $keysToSubmitArray, $_POST['id_user_team1']);

                                //submit data team2
                                $team2Score = postPlayersScore($getTeam2PlayersIdQuery, $playerKeyList, $selectedActionTeam2, $keysToSubmitArray, $_POST['id_starting_lineup_team2']);
                                $trainerTeam2Score = postTrainerScore('2', $selectedActionTrainerTeam2, $keysToSubmitArray, $_POST['id_user_team2']);

                                //save score of both teams
                                $team1Outcome = $team1Score + $trainerTeam1Score;
                                $team2Outcome = $team2Score + $trainerTeam2Score;
                                $where = array('id_schedule' => $_POST['id_schedule']);
                                $wpdb->update('megaliga_schedule_playoff', array('team1_score' => $team1Outcome), $where);
                                $wpdb->update('megaliga_schedule_playoff', array('team2_score' => $team2Outcome), $where);
                            }



                            if ($_POST['submitPlayOffScheduleR1']) {
                                global $wpdb;
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

                                function calculateStandingsData($scheduleQuery, $userIDquery)
                                {
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

                                $getUsersID = $wpdb->get_results('SELECT ID FROM megaliga_user_data WHERE ligue_groups_id = 1 OR ligue_groups_id = 2');

                                $getSchedule = $wpdb->get_results('SELECT team1_score, team2_score, id_user_team1, id_user_team2 FROM megaliga_schedule');

                                $standings = calculateStandingsData($getSchedule, $getUsersID);

                                $reachPlayoffTeams = array_slice($standings, 0, 4);

                                $checkIfRecordsExists = $wpdb->get_results('SELECT id_schedule, round_number FROM megaliga_schedule_playoff WHERE stage = "semifinal"');

                                if (!count($checkIfRecordsExists)) {
                                    // schedule of semifinals - 1st place vs 4th place and..
                                    $submitDataArray = array();
                                    $submitDataArray['id_user_team1'] = $reachPlayoffTeams[0]['ID'];
                                    $submitDataArray['id_user_team2'] = $reachPlayoffTeams[3]['ID'];
                                    $submitDataArray['round_number'] = 1;
                                    $submitDataArray['team1_score'] = null;
                                    $submitDataArray['team2_score'] = null;
                                    $submitDataArray['team1_seed'] = 1;
                                    $submitDataArray['team2_seed'] = 4;
                                    $submitDataArray['stage'] = 'semifinal';
                                    $wpdb->insert('megaliga_schedule_playoff', $submitDataArray);

                                    //...2nd place vs 3rd place
                                    $submitDataArray['id_user_team1'] = $reachPlayoffTeams[1]['ID'];
                                    $submitDataArray['id_user_team2'] = $reachPlayoffTeams[2]['ID'];
                                    $submitDataArray['round_number'] = 1;
                                    $submitDataArray['team1_seed'] = 2;
                                    $submitDataArray['team2_seed'] = 3;
                                    $wpdb->insert('megaliga_schedule_playoff', $submitDataArray);

                                    // set megaliga_user_data.reached_playoff = 1 for teams that have reached playins
                                    $getAllUsers = $wpdb->get_results('SELECT ID FROM megaliga_user_data');

                                    //clear reached_playoff to 0 for all users
                                    foreach ($getAllUsers as $record) {
                                        $submitDataArray = array();
                                        $submitDataArray['reached_playoff'] = 0;
                                        $where = array('ID' => $record->ID);
                                        $wpdb->update('megaliga_user_data', $submitDataArray, $where);
                                    }

                                    // set reached_playoff for those users, who reached playoff
                                    for ($i = 0; $i < 4; $i++) {
                                        $submitDataArray = array();
                                        $submitDataArray['reached_playoff'] = 1;
                                        $where = array('ID' => $reachPlayoffTeams[$i]['ID']);
                                        $wpdb->update('megaliga_user_data', $submitDataArray, $where);
                                    }

                                    echo "<div class='displayFlex marginTop20 marginBottom20 schedulePlayinGeneratorSuccess'>";
                                    echo "  Terminarz dla fazy playoff - półfinały wygenerowany poprawnie";
                                    echo "</div>";
                                } else {
                                    // show green success even if admin has already generated playoff schedule
                                    echo "<div class='displayFlex marginTop20 marginBottom20 schedulePlayinGeneratorSuccess'>";
                                    echo "  Terminarz dla fazy playoff - jest już wygenerowany";
                                    echo "</div>";
                                }
                            }

                            if ($_POST['submitPlayOffScheduleR2']) {
                                function getStageData($leg1)
                                {
                                    $returnData = array('winnerId' => 0, 'loserId' => 0, 'winnerSeed' => 0, 'loserSeed' => 0);

                                    //setting totalScore
                                    $returnData['totalScoreTeam1'] = $leg1->team1_score;
                                    $returnData['totalScoreTeam2'] =  $leg1->team2_score;

                                    //setting winning team. Used to set special styling
                                    if ($returnData['totalScoreTeam1'] != 0 && $returnData['totalScoreTeam2'] != 0) {
                                        if ($returnData['totalScoreTeam1'] > $returnData['totalScoreTeam2']) {
                                            $returnData['winnerId'] = $leg1->id_user_team1;
                                            $returnData['loserId'] = $leg1->id_user_team2;
                                            $returnData['winnerSeed'] = $leg1->team1_seed;
                                            $returnData['loserSeed'] = $leg1->team2_seed;
                                        } else if ($returnData['totalScoreTeam1'] < $returnData['totalScoreTeam2']) {
                                            $returnData['winnerId'] = $leg1->id_user_team2;
                                            $returnData['loserId'] = $leg1->id_user_team1;
                                            $returnData['winnerSeed'] = $leg1->team2_seed;
                                            $returnData['loserSeed'] = $leg1->team1_seed;
                                        } else {
                                            if ($returnData['seedNumberTeam1'] > $returnData['seedNumberTeam2']) {
                                                $returnData['winnerId'] = $leg1->id_user_team2;
                                                $returnData['loserId'] = $leg1->id_user_team1;
                                                $returnData['winnerSeed'] = $leg1->team2_seed;
                                                $returnData['loserSeed'] = $leg1->team1_seed;
                                            } else {
                                                $returnData['winnerId'] = $leg1->id_user_team1;
                                                $returnData['loserId'] = $leg1->id_user_team2;
                                                $returnData['winnerSeed'] = $leg1->team1_seed;
                                                $returnData['loserSeed'] = $leg1->team2_seed;
                                            }
                                        }
                                    }

                                    return $returnData;
                                }

                                $getSemifinalStage = $wpdb->get_results('SELECT id_user_team1, id_user_team2, round_number, stage, team1_score, team2_score, team1_seed, team2_seed FROM megaliga_schedule_playoff WHERE stage = "semifinal"');

                                $semifinalData = array();
                                $semifinalData[0] = getStageData($getSemifinalStage[0]);
                                $semifinalData[1] = getStageData($getSemifinalStage[1]);

                                $checkIf3rdPlaceRecordsExists = $wpdb->get_results('SELECT id_schedule, round_number FROM megaliga_schedule_playoff WHERE stage = "3rdplace"');
                                $checkIfFinalRecordsExists = $wpdb->get_results('SELECT id_schedule, round_number FROM megaliga_schedule_playoff WHERE stage = "final"');

                                $message3rdPlace = '';
                                $messageFinal = '';

                                if (!count($checkIf3rdPlaceRecordsExists)) {
                                    $submitDataArray = array();
                                    $submitDataArray['id_user_team1'] = $semifinalData[0]['loserId'];
                                    $submitDataArray['id_user_team2'] = $semifinalData[1]['loserId'];
                                    $submitDataArray['round_number'] = 2;
                                    $submitDataArray['team1_score'] = null;
                                    $submitDataArray['team2_score'] = null;
                                    $submitDataArray['team1_seed'] = $semifinalData[0]['loserSeed'];
                                    $submitDataArray['team2_seed'] = $semifinalData[1]['loserSeed'];
                                    $submitDataArray['stage'] = '3rdplace';
                                    $wpdb->insert('megaliga_schedule_playoff', $submitDataArray);

                                    $message3rdPlace = ' wygenerowany poprawnie';
                                } else {
                                    $message3rdPlace = ' jest już wygenerowany';
                                }

                                if (!count($checkIfFinalRecordsExists)) {
                                    $submitDataArray = array();
                                    $submitDataArray['id_user_team1'] = $semifinalData[0]['winnerId'];
                                    $submitDataArray['id_user_team2'] = $semifinalData[1]['winnerId'];
                                    $submitDataArray['round_number'] = 2;
                                    $submitDataArray['team1_score'] = null;
                                    $submitDataArray['team2_score'] = null;
                                    $submitDataArray['team1_seed'] = $semifinalData[0]['winnerSeed'];
                                    $submitDataArray['team2_seed'] = $semifinalData[1]['winnerSeed'];
                                    $submitDataArray['stage'] = 'final';
                                    $wpdb->insert('megaliga_schedule_playoff', $submitDataArray);

                                    $messageFinal = ' wygenerowany poprawnie';
                                } else {
                                    $messageFinal = ' jest już wygenerowany';
                                }

                                echo "<div class='displayFlex marginTop20 marginBottom20 schedulePlayinGeneratorSuccess'>";
                                echo "  Terminarz dla fazy playoff - finały " . $messageFinal . " a dla meczu o 3 miejsce" . $message3rdPlace;
                                echo "</div>";
                            }

                            function drawSchedule($queryTeam1Result, $queryTeam2Result, $gameIdentificationData, $side, $round_number)
                            {
                                $margin = $side == 'left' ? 'marginRight40' : '';
                                echo '<table class="scheduleTable playoffs ' . $margin . '" border="0">';
                                echo '  <tr>
                            <th colspan="3" class="scheduleHeader textLeft">playoffs</th>
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

                            //draws scoreboard for given game
                            function drawScoreBoard($scoreBoardData, $userId)
                            {
                                global $wpdb;
                                //show form only for user with ID == 14 (mbaginski) || 48 (Gabbana)
                                $isForm = $userId == 14 || $userId == 48;
                                $setplayTeam1 = ($scoreBoardData['team1StartingLineup']->setplays != '') ? $scoreBoardData['team1StartingLineup']->setplays : 'nie wybrano';
                                $setplayTeam2 = ($scoreBoardData['team2StartingLineup']->setplays != '') ? $scoreBoardData['team2StartingLineup']->setplays : 'nie wybrano';
                                $playerNoTeam1 = 0;
                                $playerNoTeam2 = 0;

                                echo '<div class="scoreBoardContainer">';

                                if ($isForm) {
                                    echo '<form action="" method="post">';
                                }

                                echo '<table class="scoreBoardTable" border="0">';
                                echo '  <tr><td colspan="11" class="teamOverviewContent textLeft">' . $scoreBoardData['team1Data']->team_name . ' : ' . $scoreBoardData['team2Data']->team_name . '</td></tr>';
                                echo '  <tr><td colspan="6" class="textLeft"><span class="setplayTitle">Zagrywki: </span><span class="setplayName">' . $scoreBoardData['team1Data']->team_name . ' - ' . $setplayTeam1 . '</span></td><td colspan="5" class="setplayName textLeft">' . $scoreBoardData['team2Data']->team_name . ' - ' . $setplayTeam2 . '</td></tr>';
                                echo '  <tr class="textLeft">';
                                echo '      <th class="scoreHeader">Zawodnik</th>';
                                echo '      <th class="scoreHeader">Drużyna</th>';
                                echo '      <th class="scoreHeader">Bieg 1</th>';
                                echo '      <th class="scoreHeader">Bieg 2</th>';
                                echo '      <th class="scoreHeader">Bieg 3</th>';
                                echo '      <th class="scoreHeader">Bieg 4</th>';
                                echo '      <th class="scoreHeader">Bieg 5</th>';
                                echo '      <th class="scoreHeader">Bieg 6</th>';
                                echo '      <th class="scoreHeader">Bieg 7</th>';
                                echo '      <th class="scoreHeader">Zagrywki</th>';
                                echo '      <th class="scoreHeader">Komentarz</th>';
                                echo '  </tr>';

                                //get score data for team1
                                $idStartingLineup = ($scoreBoardData['team1StartingLineup']->id_starting_lineup) ? $scoreBoardData['team1StartingLineup']->id_starting_lineup : 0;
                                $getScoreDataTeam1Query = $wpdb->get_results('SELECT heat1, heat2, heat3, heat4, heat5, heat6, heat7, setplays, comment FROM megaliga_scores_playoff WHERE id_schedule = ' . $scoreBoardData['id_schedule'] . ' AND id_starting_lineup = ' . $idStartingLineup . ' ORDER BY starting_order ASC');

                                //get score for team1 trainer
                                $getScoreDataTeam1TrainerQuery = $wpdb->get_results('SELECT heat1, heat2, heat3, heat4, heat5, heat6, heat7, setplays, comment FROM megaliga_trainer_score_playoff WHERE id_schedule = ' . $scoreBoardData['id_schedule'] . ' AND ID = ' . $scoreBoardData['id_user_team1']);

                                if ($scoreBoardData['player1DataTeam1']->ekstraliga_player_name != '') {
                                    $playerNoTeam1++;
                                    echo '  <tr class="even">';
                                    echo '      <td class="padding10">' . $scoreBoardData['player1DataTeam1']->ekstraliga_player_name . '</td>';
                                    echo '      <td class="padding10">' . $scoreBoardData['team1Data']->team_name . '</td>';

                                    $id1 = 'id' . $scoreBoardData['team1StartingLineup']->player1 . '_1';
                                    $id2 = 'id' . $scoreBoardData['team1StartingLineup']->player1 . '_2';
                                    $id3 = 'id' . $scoreBoardData['team1StartingLineup']->player1 . '_3';
                                    $id4 = 'id' . $scoreBoardData['team1StartingLineup']->player1 . '_4';
                                    $id5 = 'id' . $scoreBoardData['team1StartingLineup']->player1 . '_5';
                                    $id6 = 'id' . $scoreBoardData['team1StartingLineup']->player1 . '_6';
                                    $id7 = 'id' . $scoreBoardData['team1StartingLineup']->player1 . '_7';
                                    $id8 = 'id' . $scoreBoardData['team1StartingLineup']->player1 . '_setplay';
                                    $id9 = 'id' . $scoreBoardData['team1StartingLineup']->player1 . '_comment';
                                    $value1 = $getScoreDataTeam1Query[0]->heat1;
                                    $value2 = $getScoreDataTeam1Query[0]->heat2;
                                    $value3 = $getScoreDataTeam1Query[0]->heat3;
                                    $value4 = $getScoreDataTeam1Query[0]->heat4;
                                    $value5 = $getScoreDataTeam1Query[0]->heat5;
                                    $value6 = $getScoreDataTeam1Query[0]->heat6;
                                    $value7 = $getScoreDataTeam1Query[0]->heat7;
                                    $value8 = $getScoreDataTeam1Query[0]->setplays;
                                    $value9 = $getScoreDataTeam1Query[0]->comment;

                                    $heat1 = $isForm ? '<input name="' . $id1 . '" id="' . $id1 . '" class="heatInput" type="text" value="' . $value1 . '">' : $value1;
                                    $heat2 = $isForm ? '<input name="' . $id2 . '" id="' . $id2 . '" class="heatInput" type="text" value="' . $value2 . '">' : $value2;
                                    $heat3 = $isForm ? '<input name="' . $id3 . '" id="' . $id3 . '" class="heatInput" type="text" value="' . $value3 . '">' : $value3;
                                    $heat4 = $isForm ? '<input name="' . $id4 . '" id="' . $id4 . '" class="heatInput" type="text" value="' . $value4 . '">' : $value4;
                                    $heat5 = $isForm ? '<input name="' . $id5 . '" id="' . $id5 . '" class="heatInput" type="text" value="' . $value5 . '">' : $value5;
                                    $heat6 = $isForm ? '<input name="' . $id6 . '" id="' . $id6 . '" class="heatInput" type="text" value="' . $value6 . '">' : $value6;
                                    $heat7 = $isForm ? '<input name="' . $id7 . '" id="' . $id7 . '" class="heatInput" type="text" value="' . $value7 . '">' : $value7;
                                    $setplayPoint = $isForm ? '<input name="' . $id8 . '" id="' . $id8 . '" class="heatInput" type="text" value="' . $value8 . '">' : $value8;
                                    $comment = $isForm ? '<input name="' . $id9 . '" id="' . $id9 . '" class="commentInput" type="text" value="' . $value9 . '">' : $value9;

                                    echo '      <td class="padding10">' . $heat1 . '</td>';
                                    echo '      <td class="padding10">' . $heat2 . '</td>';
                                    echo '      <td class="padding10">' . $heat3 . '</td>';
                                    echo '      <td class="padding10">' . $heat4 . '</td>';
                                    echo '      <td class="padding10">' . $heat5 . '</td>';
                                    echo '      <td class="padding10">' . $heat6 . '</td>';
                                    echo '      <td class="padding10">' . $heat7 . '</td>';
                                    echo '      <td class="padding10">' . $setplayPoint . '</td>';
                                    echo '      <td class="padding10">' . $comment . '</td>';
                                    echo '  </tr>';
                                }

                                if ($scoreBoardData['player2DataTeam1']->ekstraliga_player_name != '') {
                                    $playerNoTeam1++;
                                    echo '  <tr class="odd">';
                                    echo '      <td class="padding10">' . $scoreBoardData['player2DataTeam1']->ekstraliga_player_name . '</td>';
                                    echo '      <td class="padding10">' . $scoreBoardData['team1Data']->team_name . '</td>';
                                    $id1 = 'id' . $scoreBoardData['team1StartingLineup']->player2 . '_1';
                                    $id2 = 'id' . $scoreBoardData['team1StartingLineup']->player2 . '_2';
                                    $id3 = 'id' . $scoreBoardData['team1StartingLineup']->player2 . '_3';
                                    $id4 = 'id' . $scoreBoardData['team1StartingLineup']->player2 . '_4';
                                    $id5 = 'id' . $scoreBoardData['team1StartingLineup']->player2 . '_5';
                                    $id6 = 'id' . $scoreBoardData['team1StartingLineup']->player2 . '_6';
                                    $id7 = 'id' . $scoreBoardData['team1StartingLineup']->player2 . '_7';
                                    $id8 = 'id' . $scoreBoardData['team1StartingLineup']->player2 . '_setplay';
                                    $id9 = 'id' . $scoreBoardData['team1StartingLineup']->player2 . '_comment';
                                    $value1 = $getScoreDataTeam1Query[1]->heat1;
                                    $value2 = $getScoreDataTeam1Query[1]->heat2;
                                    $value3 = $getScoreDataTeam1Query[1]->heat3;
                                    $value4 = $getScoreDataTeam1Query[1]->heat4;
                                    $value5 = $getScoreDataTeam1Query[1]->heat5;
                                    $value6 = $getScoreDataTeam1Query[1]->heat6;
                                    $value7 = $getScoreDataTeam1Query[1]->heat7;
                                    $value8 = $getScoreDataTeam1Query[1]->setplays;
                                    $value9 = $getScoreDataTeam1Query[1]->comment;

                                    $heat1 = $isForm ? '<input name="' . $id1 . '" id="' . $id1 . '" class="heatInput" type="text" value="' . $value1 . '">' : $value1;
                                    $heat2 = $isForm ? '<input name="' . $id2 . '" id="' . $id2 . '" class="heatInput" type="text" value="' . $value2 . '">' : $value2;
                                    $heat3 = $isForm ? '<input name="' . $id3 . '" id="' . $id3 . '" class="heatInput" type="text" value="' . $value3 . '">' : $value3;
                                    $heat4 = $isForm ? '<input name="' . $id4 . '" id="' . $id4 . '" class="heatInput" type="text" value="' . $value4 . '">' : $value4;
                                    $heat5 = $isForm ? '<input name="' . $id5 . '" id="' . $id5 . '" class="heatInput" type="text" value="' . $value5 . '">' : $value5;
                                    $heat6 = $isForm ? '<input name="' . $id6 . '" id="' . $id6 . '" class="heatInput" type="text" value="' . $value6 . '">' : $value6;
                                    $heat7 = $isForm ? '<input name="' . $id7 . '" id="' . $id7 . '" class="heatInput" type="text" value="' . $value7 . '">' : $value7;
                                    $setplayPoint = $isForm ? '<input name="' . $id8 . '" id="' . $id8 . '" class="heatInput" type="text" value="' . $value8 . '">' : $value8;
                                    $comment = $isForm ? '<input name="' . $id9 . '" id="' . $id9 . '" class="commentInput" type="text" value="' . $value9 . '">' : $value9;

                                    echo '      <td class="padding10">' . $heat1 . '</td>';
                                    echo '      <td class="padding10">' . $heat2 . '</td>';
                                    echo '      <td class="padding10">' . $heat3 . '</td>';
                                    echo '      <td class="padding10">' . $heat4 . '</td>';
                                    echo '      <td class="padding10">' . $heat5 . '</td>';
                                    echo '      <td class="padding10">' . $heat6 . '</td>';
                                    echo '      <td class="padding10">' . $heat7 . '</td>';
                                    echo '      <td class="padding10">' . $setplayPoint . '</td>';
                                    echo '      <td class="padding10">' . $comment . '</td>';
                                    echo '  </tr>';
                                }

                                if ($scoreBoardData['player3DataTeam1']->ekstraliga_player_name != '') {
                                    $playerNoTeam1++;
                                    echo '  <tr class="even">';
                                    echo '      <td class="padding10">' . $scoreBoardData['player3DataTeam1']->ekstraliga_player_name . '</td>';
                                    echo '      <td class="padding10">' . $scoreBoardData['team1Data']->team_name . '</td>';
                                    $id1 = 'id' . $scoreBoardData['team1StartingLineup']->player3 . '_1';
                                    $id2 = 'id' . $scoreBoardData['team1StartingLineup']->player3 . '_2';
                                    $id3 = 'id' . $scoreBoardData['team1StartingLineup']->player3 . '_3';
                                    $id4 = 'id' . $scoreBoardData['team1StartingLineup']->player3 . '_4';
                                    $id5 = 'id' . $scoreBoardData['team1StartingLineup']->player3 . '_5';
                                    $id6 = 'id' . $scoreBoardData['team1StartingLineup']->player3 . '_6';
                                    $id7 = 'id' . $scoreBoardData['team1StartingLineup']->player3 . '_7';
                                    $id8 = 'id' . $scoreBoardData['team1StartingLineup']->player3 . '_setplay';
                                    $id9 = 'id' . $scoreBoardData['team1StartingLineup']->player3 . '_comment';
                                    $value1 = $getScoreDataTeam1Query[2]->heat1;
                                    $value2 = $getScoreDataTeam1Query[2]->heat2;
                                    $value3 = $getScoreDataTeam1Query[2]->heat3;
                                    $value4 = $getScoreDataTeam1Query[2]->heat4;
                                    $value5 = $getScoreDataTeam1Query[2]->heat5;
                                    $value6 = $getScoreDataTeam1Query[2]->heat6;
                                    $value7 = $getScoreDataTeam1Query[2]->heat7;
                                    $value8 = $getScoreDataTeam1Query[2]->setplays;
                                    $value9 = $getScoreDataTeam1Query[2]->comment;

                                    $heat1 = $isForm ? '<input name="' . $id1 . '" id="' . $id1 . '" class="heatInput" type="text" value="' . $value1 . '">' : $value1;
                                    $heat2 = $isForm ? '<input name="' . $id2 . '" id="' . $id2 . '" class="heatInput" type="text" value="' . $value2 . '">' : $value2;
                                    $heat3 = $isForm ? '<input name="' . $id3 . '" id="' . $id3 . '" class="heatInput" type="text" value="' . $value3 . '">' : $value3;
                                    $heat4 = $isForm ? '<input name="' . $id4 . '" id="' . $id4 . '" class="heatInput" type="text" value="' . $value4 . '">' : $value4;
                                    $heat5 = $isForm ? '<input name="' . $id5 . '" id="' . $id5 . '" class="heatInput" type="text" value="' . $value5 . '">' : $value5;
                                    $heat6 = $isForm ? '<input name="' . $id6 . '" id="' . $id6 . '" class="heatInput" type="text" value="' . $value6 . '">' : $value6;
                                    $heat7 = $isForm ? '<input name="' . $id7 . '" id="' . $id7 . '" class="heatInput" type="text" value="' . $value7 . '">' : $value7;
                                    $setplayPoint = $isForm ? '<input name="' . $id8 . '" id="' . $id8 . '" class="heatInput" type="text" value="' . $value8 . '">' : $value8;
                                    $comment = $isForm ? '<input name="' . $id9 . '" id="' . $id9 . '" class="commentInput" type="text" value="' . $value9 . '">' : $value9;

                                    echo '      <td class="padding10">' . $heat1 . '</td>';
                                    echo '      <td class="padding10">' . $heat2 . '</td>';
                                    echo '      <td class="padding10">' . $heat3 . '</td>';
                                    echo '      <td class="padding10">' . $heat4 . '</td>';
                                    echo '      <td class="padding10">' . $heat5 . '</td>';
                                    echo '      <td class="padding10">' . $heat6 . '</td>';
                                    echo '      <td class="padding10">' . $heat7 . '</td>';
                                    echo '      <td class="padding10">' . $setplayPoint . '</td>';
                                    echo '      <td class="padding10">' . $comment . '</td>';
                                    echo '  </tr>';
                                }

                                if ($scoreBoardData['player4DataTeam1']->ekstraliga_player_name != '') {
                                    $playerNoTeam1++;
                                    echo '  <tr class="odd">';
                                    echo '      <td class="padding10">' . $scoreBoardData['player4DataTeam1']->ekstraliga_player_name . '</td>';
                                    echo '      <td class="padding10">' . $scoreBoardData['team1Data']->team_name . '</td>';
                                    $id1 = 'id' . $scoreBoardData['team1StartingLineup']->player4 . '_1';
                                    $id2 = 'id' . $scoreBoardData['team1StartingLineup']->player4 . '_2';
                                    $id3 = 'id' . $scoreBoardData['team1StartingLineup']->player4 . '_3';
                                    $id4 = 'id' . $scoreBoardData['team1StartingLineup']->player4 . '_4';
                                    $id5 = 'id' . $scoreBoardData['team1StartingLineup']->player4 . '_5';
                                    $id6 = 'id' . $scoreBoardData['team1StartingLineup']->player4 . '_6';
                                    $id7 = 'id' . $scoreBoardData['team1StartingLineup']->player4 . '_7';
                                    $id8 = 'id' . $scoreBoardData['team1StartingLineup']->player4 . '_setplay';
                                    $id9 = 'id' . $scoreBoardData['team1StartingLineup']->player4 . '_comment';
                                    $value1 = $getScoreDataTeam1Query[3]->heat1;
                                    $value2 = $getScoreDataTeam1Query[3]->heat2;
                                    $value3 = $getScoreDataTeam1Query[3]->heat3;
                                    $value4 = $getScoreDataTeam1Query[3]->heat4;
                                    $value5 = $getScoreDataTeam1Query[3]->heat5;
                                    $value6 = $getScoreDataTeam1Query[3]->heat6;
                                    $value7 = $getScoreDataTeam1Query[3]->heat7;
                                    $value8 = $getScoreDataTeam1Query[3]->setplays;
                                    $value9 = $getScoreDataTeam1Query[3]->comment;

                                    $heat1 = $isForm ? '<input name="' . $id1 . '" id="' . $id1 . '" class="heatInput" type="text" value="' . $value1 . '">' : $value1;
                                    $heat2 = $isForm ? '<input name="' . $id2 . '" id="' . $id2 . '" class="heatInput" type="text" value="' . $value2 . '">' : $value2;
                                    $heat3 = $isForm ? '<input name="' . $id3 . '" id="' . $id3 . '" class="heatInput" type="text" value="' . $value3 . '">' : $value3;
                                    $heat4 = $isForm ? '<input name="' . $id4 . '" id="' . $id4 . '" class="heatInput" type="text" value="' . $value4 . '">' : $value4;
                                    $heat5 = $isForm ? '<input name="' . $id5 . '" id="' . $id5 . '" class="heatInput" type="text" value="' . $value5 . '">' : $value5;
                                    $heat6 = $isForm ? '<input name="' . $id6 . '" id="' . $id6 . '" class="heatInput" type="text" value="' . $value6 . '">' : $value6;
                                    $heat7 = $isForm ? '<input name="' . $id7 . '" id="' . $id7 . '" class="heatInput" type="text" value="' . $value7 . '">' : $value7;
                                    $setplayPoint = $isForm ? '<input name="' . $id8 . '" id="' . $id8 . '" class="heatInput" type="text" value="' . $value8 . '">' : $value8;
                                    $comment = $isForm ? '<input name="' . $id9 . '" id="' . $id9 . '" class="commentInput" type="text" value="' . $value9 . '">' : $value9;

                                    echo '      <td class="padding10">' . $heat1 . '</td>';
                                    echo '      <td class="padding10">' . $heat2 . '</td>';
                                    echo '      <td class="padding10">' . $heat3 . '</td>';
                                    echo '      <td class="padding10">' . $heat4 . '</td>';
                                    echo '      <td class="padding10">' . $heat5 . '</td>';
                                    echo '      <td class="padding10">' . $heat6 . '</td>';
                                    echo '      <td class="padding10">' . $heat7 . '</td>';
                                    echo '      <td class="padding10">' . $setplayPoint . '</td>';
                                    echo '      <td class="padding10">' . $comment . '</td>';
                                    echo '  </tr>';
                                }

                                if ($scoreBoardData['player5DataTeam1']->ekstraliga_player_name != '') {
                                    $playerNoTeam1++;
                                    echo '  <tr class="even">';
                                    echo '      <td class="padding10">' . $scoreBoardData['player5DataTeam1']->ekstraliga_player_name . '</td>';
                                    echo '      <td class="padding10">' . $scoreBoardData['team1Data']->team_name . '</td>';

                                    $id1 = 'id' . $scoreBoardData['team1StartingLineup']->player5 . '_1';
                                    $id2 = 'id' . $scoreBoardData['team1StartingLineup']->player5 . '_2';
                                    $id3 = 'id' . $scoreBoardData['team1StartingLineup']->player5 . '_3';
                                    $id4 = 'id' . $scoreBoardData['team1StartingLineup']->player5 . '_4';
                                    $id5 = 'id' . $scoreBoardData['team1StartingLineup']->player5 . '_5';
                                    $id6 = 'id' . $scoreBoardData['team1StartingLineup']->player5 . '_6';
                                    $id7 = 'id' . $scoreBoardData['team1StartingLineup']->player5 . '_7';
                                    $id8 = 'id' . $scoreBoardData['team1StartingLineup']->player5 . '_setplay';
                                    $id9 = 'id' . $scoreBoardData['team1StartingLineup']->player5 . '_comment';
                                    $value1 = $getScoreDataTeam1Query[4]->heat1;
                                    $value2 = $getScoreDataTeam1Query[4]->heat2;
                                    $value3 = $getScoreDataTeam1Query[4]->heat3;
                                    $value4 = $getScoreDataTeam1Query[4]->heat4;
                                    $value5 = $getScoreDataTeam1Query[4]->heat5;
                                    $value6 = $getScoreDataTeam1Query[4]->heat6;
                                    $value7 = $getScoreDataTeam1Query[4]->heat7;
                                    $value8 = $getScoreDataTeam1Query[4]->setplays;
                                    $value9 = $getScoreDataTeam1Query[4]->comment;

                                    $heat1 = $isForm ? '<input name="' . $id1 . '" id="' . $id1 . '" class="heatInput" type="text" value="' . $value1 . '">' : $value1;
                                    $heat2 = $isForm ? '<input name="' . $id2 . '" id="' . $id2 . '" class="heatInput" type="text" value="' . $value2 . '">' : $value2;
                                    $heat3 = $isForm ? '<input name="' . $id3 . '" id="' . $id3 . '" class="heatInput" type="text" value="' . $value3 . '">' : $value3;
                                    $heat4 = $isForm ? '<input name="' . $id4 . '" id="' . $id4 . '" class="heatInput" type="text" value="' . $value4 . '">' : $value4;
                                    $heat5 = $isForm ? '<input name="' . $id5 . '" id="' . $id5 . '" class="heatInput" type="text" value="' . $value5 . '">' : $value5;
                                    $heat6 = $isForm ? '<input name="' . $id6 . '" id="' . $id6 . '" class="heatInput" type="text" value="' . $value6 . '">' : $value6;
                                    $heat7 = $isForm ? '<input name="' . $id7 . '" id="' . $id7 . '" class="heatInput" type="text" value="' . $value7 . '">' : $value7;
                                    $setplayPoint = $isForm ? '<input name="' . $id8 . '" id="' . $id8 . '" class="heatInput" type="text" value="' . $value8 . '">' : $value8;
                                    $comment = $isForm ? '<input name="' . $id9 . '" id="' . $id9 . '" class="commentInput" type="text" value="' . $value9 . '">' : $value9;

                                    echo '      <td class="padding10">' . $heat1 . '</td>';
                                    echo '      <td class="padding10">' . $heat2 . '</td>';
                                    echo '      <td class="padding10">' . $heat3 . '</td>';
                                    echo '      <td class="padding10">' . $heat4 . '</td>';
                                    echo '      <td class="padding10">' . $heat5 . '</td>';
                                    echo '      <td class="padding10">' . $heat6 . '</td>';
                                    echo '      <td class="padding10">' . $heat7 . '</td>';
                                    echo '      <td class="padding10">' . $setplayPoint . '</td>';
                                    echo '      <td class="padding10">' . $comment . '</td>';
                                    echo '  </tr>';
                                }

                                echo '  <tr class="odd">';
                                echo '      <td class="padding10">Trener</td>';
                                echo '      <td class="padding10">' . $scoreBoardData['team1Data']->team_name . '</td>';
                                $id1 = 'id_trainerTeam1_1';
                                $id2 = 'id_trainerTeam1_2';
                                $id3 = 'id_trainerTeam1_3';
                                $id4 = 'id_trainerTeam1_4';
                                $id5 = 'id_trainerTeam1_5';
                                $id6 = 'id_trainerTeam1_6';
                                $id7 = 'id_trainerTeam1_7';
                                $id8 = 'id_trainerTeam1_setplay';
                                $id9 = 'id_trainerTeam1_comment';
                                $value1 = $getScoreDataTeam1TrainerQuery[0]->heat1;
                                $value2 = $getScoreDataTeam1TrainerQuery[0]->heat2;
                                $value3 = $getScoreDataTeam1TrainerQuery[0]->heat3;
                                $value4 = $getScoreDataTeam1TrainerQuery[0]->heat4;
                                $value5 = $getScoreDataTeam1TrainerQuery[0]->heat5;
                                $value6 = $getScoreDataTeam1TrainerQuery[0]->heat6;
                                $value7 = $getScoreDataTeam1TrainerQuery[0]->heat7;
                                $value8 = $getScoreDataTeam1TrainerQuery[0]->setplays;
                                $value9 = $getScoreDataTeam1TrainerQuery[0]->comment;

                                $heat1 = $isForm ? '<input name="' . $id1 . '" id="' . $id1 . '" class="heatInput" type="text" value="' . $value1 . '">' : $value1;
                                $heat2 = $isForm ? '<input name="' . $id2 . '" id="' . $id2 . '" class="heatInput" type="text" value="' . $value2 . '">' : $value2;
                                $heat3 = $isForm ? '<input name="' . $id3 . '" id="' . $id3 . '" class="heatInput" type="text" value="' . $value3 . '">' : $value3;
                                $heat4 = $isForm ? '<input name="' . $id4 . '" id="' . $id4 . '" class="heatInput" type="text" value="' . $value4 . '">' : $value4;
                                $heat5 = $isForm ? '<input name="' . $id5 . '" id="' . $id5 . '" class="heatInput" type="text" value="' . $value5 . '">' : $value5;
                                $heat6 = $isForm ? '<input name="' . $id6 . '" id="' . $id6 . '" class="heatInput" type="text" value="' . $value6 . '">' : $value6;
                                $heat7 = $isForm ? '<input name="' . $id7 . '" id="' . $id7 . '" class="heatInput" type="text" value="' . $value7 . '">' : $value7;
                                $setplayPoint = $isForm ? '<input name="' . $id8 . '" id="' . $id8 . '" class="heatInput" type="text" value="' . $value8 . '">' : $value8;
                                $comment = $isForm ? '<input name="' . $id9 . '" id="' . $id9 . '" class="commentInput" type="text" value="' . $value9 . '">' : $value9;

                                echo '      <td class="padding10">' . $heat1 . '</td>';
                                echo '      <td class="padding10">' . $heat2 . '</td>';
                                echo '      <td class="padding10">' . $heat3 . '</td>';
                                echo '      <td class="padding10">' . $heat4 . '</td>';
                                echo '      <td class="padding10">' . $heat5 . '</td>';
                                echo '      <td class="padding10">' . $heat6 . '</td>';
                                echo '      <td class="padding10">' . $heat7 . '</td>';
                                echo '      <td class="padding10">' . $setplayPoint . '</td>';
                                echo '      <td class="padding10">' . $comment . '</td>';
                                echo '  </tr>';

                                echo '  <tr class="scoreSeparator">';
                                echo '      <td class="scoreSeparator"></td>';
                                echo '      <td class="scoreSeparator"></td>';
                                echo '      <td class="scoreSeparator"></td>';
                                echo '      <td class="scoreSeparator"></td>';
                                echo '      <td class="scoreSeparator"></td>';
                                echo '      <td class="scoreSeparator"></td>';
                                echo '      <td class="scoreSeparator"></td>';
                                echo '      <td class="scoreSeparator"></td>';
                                echo '      <td class="scoreSeparator"></td>';
                                echo '      <td class="scoreSeparator"></td>';
                                echo '      <td class="scoreSeparator"></td>';
                                echo '  </tr>';

                                //get score data for team2
                                $idStartingLineup2 = ($scoreBoardData['team2StartingLineup']->id_starting_lineup) ? $scoreBoardData['team2StartingLineup']->id_starting_lineup : 0;
                                $getScoreDataTeam2Query = $wpdb->get_results('SELECT heat1, heat2, heat3, heat4, heat5, heat6, heat7, setplays, comment FROM megaliga_scores_playoff WHERE id_schedule = ' . $scoreBoardData['id_schedule'] . ' AND id_starting_lineup = ' . $idStartingLineup2 . ' ORDER BY starting_order ASC');

                                //get score for team2 trainer
                                $getScoreDataTeam2TrainerQuery = $wpdb->get_results('SELECT heat1, heat2, heat3, heat4, heat5, heat6, heat7, setplays, comment FROM megaliga_trainer_score_playoff WHERE id_schedule = ' . $scoreBoardData['id_schedule'] . ' AND ID = ' . $scoreBoardData['id_user_team2']);

                                if ($scoreBoardData['player1DataTeam2']->ekstraliga_player_name != '') {
                                    $playerNoTeam2++;
                                    echo '  <tr class="even">';
                                    echo '      <td class="padding10">' . $scoreBoardData['player1DataTeam2']->ekstraliga_player_name . '</td>';
                                    echo '      <td class="padding10">' . $scoreBoardData['team2Data']->team_name . '</td>';

                                    $id1 = 'id' . $scoreBoardData['team2StartingLineup']->player1 . '_1';
                                    $id2 = 'id' . $scoreBoardData['team2StartingLineup']->player1 . '_2';
                                    $id3 = 'id' . $scoreBoardData['team2StartingLineup']->player1 . '_3';
                                    $id4 = 'id' . $scoreBoardData['team2StartingLineup']->player1 . '_4';
                                    $id5 = 'id' . $scoreBoardData['team2StartingLineup']->player1 . '_5';
                                    $id6 = 'id' . $scoreBoardData['team2StartingLineup']->player1 . '_6';
                                    $id7 = 'id' . $scoreBoardData['team2StartingLineup']->player1 . '_7';
                                    $id8 = 'id' . $scoreBoardData['team2StartingLineup']->player1 . '_setplay';
                                    $id9 = 'id' . $scoreBoardData['team2StartingLineup']->player1 . '_comment';
                                    $value1 = $getScoreDataTeam2Query[0]->heat1;
                                    $value2 = $getScoreDataTeam2Query[0]->heat2;
                                    $value3 = $getScoreDataTeam2Query[0]->heat3;
                                    $value4 = $getScoreDataTeam2Query[0]->heat4;
                                    $value5 = $getScoreDataTeam2Query[0]->heat5;
                                    $value6 = $getScoreDataTeam2Query[0]->heat6;
                                    $value7 = $getScoreDataTeam2Query[0]->heat7;
                                    $value8 = $getScoreDataTeam2Query[0]->setplays;
                                    $value9 = $getScoreDataTeam2Query[0]->comment;

                                    $heat1 = $isForm ? '<input name="' . $id1 . '" id="' . $id1 . '" class="heatInput" type="text" value="' . $value1 . '">' : $value1;
                                    $heat2 = $isForm ? '<input name="' . $id2 . '" id="' . $id2 . '" class="heatInput" type="text" value="' . $value2 . '">' : $value2;
                                    $heat3 = $isForm ? '<input name="' . $id3 . '" id="' . $id3 . '" class="heatInput" type="text" value="' . $value3 . '">' : $value3;
                                    $heat4 = $isForm ? '<input name="' . $id4 . '" id="' . $id4 . '" class="heatInput" type="text" value="' . $value4 . '">' : $value4;
                                    $heat5 = $isForm ? '<input name="' . $id5 . '" id="' . $id5 . '" class="heatInput" type="text" value="' . $value5 . '">' : $value5;
                                    $heat6 = $isForm ? '<input name="' . $id6 . '" id="' . $id6 . '" class="heatInput" type="text" value="' . $value6 . '">' : $value6;
                                    $heat7 = $isForm ? '<input name="' . $id7 . '" id="' . $id7 . '" class="heatInput" type="text" value="' . $value7 . '">' : $value7;
                                    $setplayPoint = $isForm ? '<input name="' . $id8 . '" id="' . $id8 . '" class="heatInput" type="text" value="' . $value8 . '">' : $value8;
                                    $comment = $isForm ? '<input name="' . $id9 . '" id="' . $id9 . '" class="commentInput" type="text" value="' . $value9 . '">' : $value9;

                                    echo '      <td class="padding10">' . $heat1 . '</td>';
                                    echo '      <td class="padding10">' . $heat2 . '</td>';
                                    echo '      <td class="padding10">' . $heat3 . '</td>';
                                    echo '      <td class="padding10">' . $heat4 . '</td>';
                                    echo '      <td class="padding10">' . $heat5 . '</td>';
                                    echo '      <td class="padding10">' . $heat6 . '</td>';
                                    echo '      <td class="padding10">' . $heat7 . '</td>';
                                    echo '      <td class="padding10">' . $setplayPoint . '</td>';
                                    echo '      <td class="padding10">' . $comment . '</td>';
                                    echo '  </tr>';
                                }

                                if ($scoreBoardData['player2DataTeam2']->ekstraliga_player_name != '') {
                                    $playerNoTeam2++;
                                    echo '  <tr class="odd">';
                                    echo '      <td class="padding10">' . $scoreBoardData['player2DataTeam2']->ekstraliga_player_name . '</td>';
                                    echo '      <td class="padding10">' . $scoreBoardData['team2Data']->team_name . '</td>';

                                    $id1 = 'id' . $scoreBoardData['team2StartingLineup']->player2 . '_1';
                                    $id2 = 'id' . $scoreBoardData['team2StartingLineup']->player2 . '_2';
                                    $id3 = 'id' . $scoreBoardData['team2StartingLineup']->player2 . '_3';
                                    $id4 = 'id' . $scoreBoardData['team2StartingLineup']->player2 . '_4';
                                    $id5 = 'id' . $scoreBoardData['team2StartingLineup']->player2 . '_5';
                                    $id6 = 'id' . $scoreBoardData['team2StartingLineup']->player2 . '_6';
                                    $id7 = 'id' . $scoreBoardData['team2StartingLineup']->player2 . '_7';
                                    $id8 = 'id' . $scoreBoardData['team2StartingLineup']->player2 . '_setplay';
                                    $id9 = 'id' . $scoreBoardData['team2StartingLineup']->player2 . '_comment';
                                    $value1 = $getScoreDataTeam2Query[1]->heat1;
                                    $value2 = $getScoreDataTeam2Query[1]->heat2;
                                    $value3 = $getScoreDataTeam2Query[1]->heat3;
                                    $value4 = $getScoreDataTeam2Query[1]->heat4;
                                    $value5 = $getScoreDataTeam2Query[1]->heat5;
                                    $value6 = $getScoreDataTeam2Query[1]->heat6;
                                    $value7 = $getScoreDataTeam2Query[1]->heat7;
                                    $value8 = $getScoreDataTeam2Query[1]->setplays;
                                    $value9 = $getScoreDataTeam2Query[1]->comment;

                                    $heat1 = $isForm ? '<input name="' . $id1 . '" id="' . $id1 . '" class="heatInput" type="text" value="' . $value1 . '">' : $value1;
                                    $heat2 = $isForm ? '<input name="' . $id2 . '" id="' . $id2 . '" class="heatInput" type="text" value="' . $value2 . '">' : $value2;
                                    $heat3 = $isForm ? '<input name="' . $id3 . '" id="' . $id3 . '" class="heatInput" type="text" value="' . $value3 . '">' : $value3;
                                    $heat4 = $isForm ? '<input name="' . $id4 . '" id="' . $id4 . '" class="heatInput" type="text" value="' . $value4 . '">' : $value4;
                                    $heat5 = $isForm ? '<input name="' . $id5 . '" id="' . $id5 . '" class="heatInput" type="text" value="' . $value5 . '">' : $value5;
                                    $heat6 = $isForm ? '<input name="' . $id6 . '" id="' . $id6 . '" class="heatInput" type="text" value="' . $value6 . '">' : $value6;
                                    $heat7 = $isForm ? '<input name="' . $id7 . '" id="' . $id7 . '" class="heatInput" type="text" value="' . $value7 . '">' : $value7;
                                    $setplayPoint = $isForm ? '<input name="' . $id8 . '" id="' . $id8 . '" class="heatInput" type="text" value="' . $value8 . '">' : $value8;
                                    $comment = $isForm ? '<input name="' . $id9 . '" id="' . $id9 . '" class="commentInput" type="text" value="' . $value9 . '">' : $value9;

                                    echo '      <td class="padding10">' . $heat1 . '</td>';
                                    echo '      <td class="padding10">' . $heat2 . '</td>';
                                    echo '      <td class="padding10">' . $heat3 . '</td>';
                                    echo '      <td class="padding10">' . $heat4 . '</td>';
                                    echo '      <td class="padding10">' . $heat5 . '</td>';
                                    echo '      <td class="padding10">' . $heat6 . '</td>';
                                    echo '      <td class="padding10">' . $heat7 . '</td>';
                                    echo '      <td class="padding10">' . $setplayPoint . '</td>';
                                    echo '      <td class="padding10">' . $comment . '</td>';
                                    echo '  </tr>';
                                }

                                if ($scoreBoardData['player3DataTeam2']->ekstraliga_player_name != '') {
                                    $playerNoTeam2++;
                                    echo '  <tr class="even">';
                                    echo '      <td class="padding10">' . $scoreBoardData['player3DataTeam2']->ekstraliga_player_name . '</td>';
                                    echo '      <td class="padding10">' . $scoreBoardData['team2Data']->team_name . '</td>';

                                    $id1 = 'id' . $scoreBoardData['team2StartingLineup']->player3 . '_1';
                                    $id2 = 'id' . $scoreBoardData['team2StartingLineup']->player3 . '_2';
                                    $id3 = 'id' . $scoreBoardData['team2StartingLineup']->player3 . '_3';
                                    $id4 = 'id' . $scoreBoardData['team2StartingLineup']->player3 . '_4';
                                    $id5 = 'id' . $scoreBoardData['team2StartingLineup']->player3 . '_5';
                                    $id6 = 'id' . $scoreBoardData['team2StartingLineup']->player3 . '_6';
                                    $id7 = 'id' . $scoreBoardData['team2StartingLineup']->player3 . '_7';
                                    $id8 = 'id' . $scoreBoardData['team2StartingLineup']->player3 . '_setplay';
                                    $id9 = 'id' . $scoreBoardData['team2StartingLineup']->player3 . '_comment';
                                    $value1 = $getScoreDataTeam2Query[2]->heat1;
                                    $value2 = $getScoreDataTeam2Query[2]->heat2;
                                    $value3 = $getScoreDataTeam2Query[2]->heat3;
                                    $value4 = $getScoreDataTeam2Query[2]->heat4;
                                    $value5 = $getScoreDataTeam2Query[2]->heat5;
                                    $value6 = $getScoreDataTeam2Query[2]->heat6;
                                    $value7 = $getScoreDataTeam2Query[2]->heat7;
                                    $value8 = $getScoreDataTeam2Query[2]->setplays;
                                    $value9 = $getScoreDataTeam2Query[2]->comment;

                                    $heat1 = $isForm ? '<input name="' . $id1 . '" id="' . $id1 . '" class="heatInput" type="text" value="' . $value1 . '">' : $value1;
                                    $heat2 = $isForm ? '<input name="' . $id2 . '" id="' . $id2 . '" class="heatInput" type="text" value="' . $value2 . '">' : $value2;
                                    $heat3 = $isForm ? '<input name="' . $id3 . '" id="' . $id3 . '" class="heatInput" type="text" value="' . $value3 . '">' : $value3;
                                    $heat4 = $isForm ? '<input name="' . $id4 . '" id="' . $id4 . '" class="heatInput" type="text" value="' . $value4 . '">' : $value4;
                                    $heat5 = $isForm ? '<input name="' . $id5 . '" id="' . $id5 . '" class="heatInput" type="text" value="' . $value5 . '">' : $value5;
                                    $heat6 = $isForm ? '<input name="' . $id6 . '" id="' . $id6 . '" class="heatInput" type="text" value="' . $value6 . '">' : $value6;
                                    $heat7 = $isForm ? '<input name="' . $id7 . '" id="' . $id7 . '" class="heatInput" type="text" value="' . $value7 . '">' : $value7;
                                    $setplayPoint = $isForm ? '<input name="' . $id8 . '" id="' . $id8 . '" class="heatInput" type="text" value="' . $value8 . '">' : $value8;
                                    $comment = $isForm ? '<input name="' . $id9 . '" id="' . $id9 . '" class="commentInput" type="text" value="' . $value9 . '">' : $value9;

                                    echo '      <td class="padding10">' . $heat1 . '</td>';
                                    echo '      <td class="padding10">' . $heat2 . '</td>';
                                    echo '      <td class="padding10">' . $heat3 . '</td>';
                                    echo '      <td class="padding10">' . $heat4 . '</td>';
                                    echo '      <td class="padding10">' . $heat5 . '</td>';
                                    echo '      <td class="padding10">' . $heat6 . '</td>';
                                    echo '      <td class="padding10">' . $heat7 . '</td>';
                                    echo '      <td class="padding10">' . $setplayPoint . '</td>';
                                    echo '      <td class="padding10">' . $comment . '</td>';
                                    echo '  </tr>';
                                }

                                if ($scoreBoardData['player4DataTeam2']->ekstraliga_player_name != '') {
                                    $playerNoTeam2++;
                                    echo '  <tr class="odd">';
                                    echo '      <td class="padding10">' . $scoreBoardData['player4DataTeam2']->ekstraliga_player_name . '</td>';
                                    echo '      <td class="padding10">' . $scoreBoardData['team2Data']->team_name . '</td>';

                                    $id1 = 'id' . $scoreBoardData['team2StartingLineup']->player4 . '_1';
                                    $id2 = 'id' . $scoreBoardData['team2StartingLineup']->player4 . '_2';
                                    $id3 = 'id' . $scoreBoardData['team2StartingLineup']->player4 . '_3';
                                    $id4 = 'id' . $scoreBoardData['team2StartingLineup']->player4 . '_4';
                                    $id5 = 'id' . $scoreBoardData['team2StartingLineup']->player4 . '_5';
                                    $id6 = 'id' . $scoreBoardData['team2StartingLineup']->player4 . '_6';
                                    $id7 = 'id' . $scoreBoardData['team2StartingLineup']->player4 . '_7';
                                    $id8 = 'id' . $scoreBoardData['team2StartingLineup']->player4 . '_setplay';
                                    $id9 = 'id' . $scoreBoardData['team2StartingLineup']->player4 . '_comment';
                                    $value1 = $getScoreDataTeam2Query[3]->heat1;
                                    $value2 = $getScoreDataTeam2Query[3]->heat2;
                                    $value3 = $getScoreDataTeam2Query[3]->heat3;
                                    $value4 = $getScoreDataTeam2Query[3]->heat4;
                                    $value5 = $getScoreDataTeam2Query[3]->heat5;
                                    $value6 = $getScoreDataTeam2Query[3]->heat6;
                                    $value7 = $getScoreDataTeam2Query[3]->heat7;
                                    $value8 = $getScoreDataTeam2Query[3]->setplays;
                                    $value9 = $getScoreDataTeam2Query[3]->comment;

                                    $heat1 = $isForm ? '<input name="' . $id1 . '" id="' . $id1 . '" class="heatInput" type="text" value="' . $value1 . '">' : $value1;
                                    $heat2 = $isForm ? '<input name="' . $id2 . '" id="' . $id2 . '" class="heatInput" type="text" value="' . $value2 . '">' : $value2;
                                    $heat3 = $isForm ? '<input name="' . $id3 . '" id="' . $id3 . '" class="heatInput" type="text" value="' . $value3 . '">' : $value3;
                                    $heat4 = $isForm ? '<input name="' . $id4 . '" id="' . $id4 . '" class="heatInput" type="text" value="' . $value4 . '">' : $value4;
                                    $heat5 = $isForm ? '<input name="' . $id5 . '" id="' . $id5 . '" class="heatInput" type="text" value="' . $value5 . '">' : $value5;
                                    $heat6 = $isForm ? '<input name="' . $id6 . '" id="' . $id6 . '" class="heatInput" type="text" value="' . $value6 . '">' : $value6;
                                    $heat7 = $isForm ? '<input name="' . $id7 . '" id="' . $id7 . '" class="heatInput" type="text" value="' . $value7 . '">' : $value7;
                                    $setplayPoint = $isForm ? '<input name="' . $id8 . '" id="' . $id8 . '" class="heatInput" type="text" value="' . $value8 . '">' : $value8;
                                    $comment = $isForm ? '<input name="' . $id9 . '" id="' . $id9 . '" class="commentInput" type="text" value="' . $value9 . '">' : $value9;

                                    echo '      <td class="padding10">' . $heat1 . '</td>';
                                    echo '      <td class="padding10">' . $heat2 . '</td>';
                                    echo '      <td class="padding10">' . $heat3 . '</td>';
                                    echo '      <td class="padding10">' . $heat4 . '</td>';
                                    echo '      <td class="padding10">' . $heat5 . '</td>';
                                    echo '      <td class="padding10">' . $heat6 . '</td>';
                                    echo '      <td class="padding10">' . $heat7 . '</td>';
                                    echo '      <td class="padding10">' . $setplayPoint . '</td>';
                                    echo '      <td class="padding10">' . $comment . '</td>';
                                    echo '  </tr>';
                                    echo '  </tr>';
                                }

                                if ($scoreBoardData['player5DataTeam2']->ekstraliga_player_name != '') {
                                    $playerNoTeam2++;
                                    echo '  <tr class="even">';
                                    echo '      <td class="padding10">' . $scoreBoardData['player5DataTeam2']->ekstraliga_player_name . '</td>';
                                    echo '      <td class="padding10">' . $scoreBoardData['team2Data']->team_name . '</td>';
                                    $id1 = 'id' . $scoreBoardData['team2StartingLineup']->player5 . '_1';
                                    $id2 = 'id' . $scoreBoardData['team2StartingLineup']->player5 . '_2';
                                    $id3 = 'id' . $scoreBoardData['team2StartingLineup']->player5 . '_3';
                                    $id4 = 'id' . $scoreBoardData['team2StartingLineup']->player5 . '_4';
                                    $id5 = 'id' . $scoreBoardData['team2StartingLineup']->player5 . '_5';
                                    $id6 = 'id' . $scoreBoardData['team2StartingLineup']->player5 . '_6';
                                    $id7 = 'id' . $scoreBoardData['team2StartingLineup']->player5 . '_7';
                                    $id8 = 'id' . $scoreBoardData['team2StartingLineup']->player5 . '_setplay';
                                    $id9 = 'id' . $scoreBoardData['team2StartingLineup']->player5 . '_comment';
                                    $value1 = $getScoreDataTeam2Query[4]->heat1;
                                    $value2 = $getScoreDataTeam2Query[4]->heat2;
                                    $value3 = $getScoreDataTeam2Query[4]->heat3;
                                    $value4 = $getScoreDataTeam2Query[4]->heat4;
                                    $value5 = $getScoreDataTeam2Query[4]->heat5;
                                    $value6 = $getScoreDataTeam2Query[4]->heat6;
                                    $value7 = $getScoreDataTeam2Query[4]->heat7;
                                    $value8 = $getScoreDataTeam2Query[4]->setplays;
                                    $value9 = $getScoreDataTeam2Query[4]->comment;

                                    $heat1 = $isForm ? '<input name="' . $id1 . '" id="' . $id1 . '" class="heatInput" type="text" value="' . $value1 . '">' : $value1;
                                    $heat2 = $isForm ? '<input name="' . $id2 . '" id="' . $id2 . '" class="heatInput" type="text" value="' . $value2 . '">' : $value2;
                                    $heat3 = $isForm ? '<input name="' . $id3 . '" id="' . $id3 . '" class="heatInput" type="text" value="' . $value3 . '">' : $value3;
                                    $heat4 = $isForm ? '<input name="' . $id4 . '" id="' . $id4 . '" class="heatInput" type="text" value="' . $value4 . '">' : $value4;
                                    $heat5 = $isForm ? '<input name="' . $id5 . '" id="' . $id5 . '" class="heatInput" type="text" value="' . $value5 . '">' : $value5;
                                    $heat6 = $isForm ? '<input name="' . $id6 . '" id="' . $id6 . '" class="heatInput" type="text" value="' . $value6 . '">' : $value6;
                                    $heat7 = $isForm ? '<input name="' . $id7 . '" id="' . $id7 . '" class="heatInput" type="text" value="' . $value7 . '">' : $value7;
                                    $setplayPoint = $isForm ? '<input name="' . $id8 . '" id="' . $id8 . '" class="heatInput" type="text" value="' . $value8 . '">' : $value8;
                                    $comment = $isForm ? '<input name="' . $id9 . '" id="' . $id9 . '" class="commentInput" type="text" value="' . $value9 . '">' : $value9;

                                    echo '      <td class="padding10">' . $heat1 . '</td>';
                                    echo '      <td class="padding10">' . $heat2 . '</td>';
                                    echo '      <td class="padding10">' . $heat3 . '</td>';
                                    echo '      <td class="padding10">' . $heat4 . '</td>';
                                    echo '      <td class="padding10">' . $heat5 . '</td>';
                                    echo '      <td class="padding10">' . $heat6 . '</td>';
                                    echo '      <td class="padding10">' . $heat7 . '</td>';
                                    echo '      <td class="padding10">' . $setplayPoint . '</td>';
                                    echo '      <td class="padding10">' . $comment . '</td>';
                                    echo '  </tr>';
                                }

                                echo '  <tr class="odd">';
                                echo '      <td class="padding10">Trener</td>';
                                echo '      <td class="padding10">' . $scoreBoardData['team2Data']->team_name . '</td>';
                                $id1 = 'id_trainerTeam2_1';
                                $id2 = 'id_trainerTeam2_2';
                                $id3 = 'id_trainerTeam2_3';
                                $id4 = 'id_trainerTeam2_4';
                                $id5 = 'id_trainerTeam2_5';
                                $id6 = 'id_trainerTeam2_6';
                                $id7 = 'id_trainerTeam2_7';
                                $id8 = 'id_trainerTeam2_setplay';
                                $id9 = 'id_trainerTeam2_comment';
                                $value1 = $getScoreDataTeam2TrainerQuery[0]->heat1;
                                $value2 = $getScoreDataTeam2TrainerQuery[0]->heat2;
                                $value3 = $getScoreDataTeam2TrainerQuery[0]->heat3;
                                $value4 = $getScoreDataTeam2TrainerQuery[0]->heat4;
                                $value5 = $getScoreDataTeam2TrainerQuery[0]->heat5;
                                $value6 = $getScoreDataTeam2TrainerQuery[0]->heat6;
                                $value7 = $getScoreDataTeam2TrainerQuery[0]->heat7;
                                $value8 = $getScoreDataTeam2TrainerQuery[0]->setplays;
                                $value9 = $getScoreDataTeam2TrainerQuery[0]->comment;

                                $heat1 = $isForm ? '<input name="' . $id1 . '" id="' . $id1 . '" class="heatInput" type="text" value="' . $value1 . '">' : $value1;
                                $heat2 = $isForm ? '<input name="' . $id2 . '" id="' . $id2 . '" class="heatInput" type="text" value="' . $value2 . '">' : $value2;
                                $heat3 = $isForm ? '<input name="' . $id3 . '" id="' . $id3 . '" class="heatInput" type="text" value="' . $value3 . '">' : $value3;
                                $heat4 = $isForm ? '<input name="' . $id4 . '" id="' . $id4 . '" class="heatInput" type="text" value="' . $value4 . '">' : $value4;
                                $heat5 = $isForm ? '<input name="' . $id5 . '" id="' . $id5 . '" class="heatInput" type="text" value="' . $value5 . '">' : $value5;
                                $heat6 = $isForm ? '<input name="' . $id6 . '" id="' . $id6 . '" class="heatInput" type="text" value="' . $value6 . '">' : $value6;
                                $heat7 = $isForm ? '<input name="' . $id7 . '" id="' . $id7 . '" class="heatInput" type="text" value="' . $value7 . '">' : $value7;
                                $setplayPoint = $isForm ? '<input name="' . $id8 . '" id="' . $id8 . '" class="heatInput" type="text" value="' . $value8 . '">' : $value8;
                                $comment = $isForm ? '<input name="' . $id9 . '" id="' . $id9 . '" class="commentInput" type="text" value="' . $value9 . '">' : $value9;

                                echo '      <td class="padding10">' . $heat1 . '</td>';
                                echo '      <td class="padding10">' . $heat2 . '</td>';
                                echo '      <td class="padding10">' . $heat3 . '</td>';
                                echo '      <td class="padding10">' . $heat4 . '</td>';
                                echo '      <td class="padding10">' . $heat5 . '</td>';
                                echo '      <td class="padding10">' . $heat6 . '</td>';
                                echo '      <td class="padding10">' . $heat7 . '</td>';
                                echo '      <td class="padding10">' . $setplayPoint . '</td>';
                                echo '      <td class="padding10">' . $comment . '</td>';
                                echo '  </tr>';

                                echo '</table>';

                                if ($isForm) {
                                    echo ' <input type="hidden" name="id_schedule" value="' . $scoreBoardData['id_schedule'] . '">';
                                    echo ' <input type="hidden" name="id_starting_lineup_team1" value="' . $scoreBoardData['team1StartingLineup']->id_starting_lineup . '">';
                                    echo ' <input type="hidden" name="id_starting_lineup_team2" value="' . $scoreBoardData['team2StartingLineup']->id_starting_lineup . '">';
                                    echo ' <input type="hidden" name="id_user_team1" value="' . $scoreBoardData['id_user_team1'] . '">';
                                    echo ' <input type="hidden" name="id_user_team2" value="' . $scoreBoardData['id_user_team2'] . '">';

                                    if ($playerNoTeam1 == 5 && $playerNoTeam2 == 5) {
                                        echo '  <div>';
                                        echo '      <input type="submit" name="submitScore" value="Zatwierdź wynik">';
                                        echo '  </div>';
                                    }

                                    echo '</form>';
                                }
                                echo '</div>';
                            }

                            function getAllGameData($query, $round_number)
                            {
                                global $wpdb;
                                $returnData = array();
                                $i = 0;

                                foreach ($query as $gameField) {
                                    $game = array();

                                    //save the reference to id_schedule of given game, so that it will be possible to retrieve score data for given game and player
                                    $game['id_schedule'] = $gameField->id_schedule;
                                    $game['id_user_team1'] = $gameField->id_user_team1;
                                    $game['id_user_team2'] = $gameField->id_user_team2;

                                    //get data related with team 1
                                    $getTeam1DataQuery = $wpdb->get_results('SELECT megaliga_team_names.name as "team_name" FROM megaliga_team_names, megaliga_user_data WHERE megaliga_user_data.ID = ' . $gameField->id_user_team1 . ' AND megaliga_user_data.team_names_id = megaliga_team_names.team_names_id');
                                    $game['team1Data'] = $getTeam1DataQuery[0];

                                    //get team's 1 starting lineup for the game
                                    $getTeam1StartingLineupQuery = $wpdb->get_results('SELECT id_starting_lineup, player1, player2, player3, player4, player5, setplays FROM megaliga_starting_lineup_playoff WHERE megaliga_starting_lineup_playoff.ID = ' . $gameField->id_user_team1 . ' AND megaliga_starting_lineup_playoff.round_number = ' . $round_number);
                                    $game['team1StartingLineup'] = $getTeam1StartingLineupQuery[0];

                                    //get data related with team2
                                    $getTeam2DataQuery = $wpdb->get_results('SELECT megaliga_team_names.name as "team_name" FROM megaliga_team_names, megaliga_user_data WHERE megaliga_user_data.ID = ' . $gameField->id_user_team2 . ' AND megaliga_user_data.team_names_id = megaliga_team_names.team_names_id');
                                    $game['team2Data'] = $getTeam2DataQuery[0];

                                    //get team's 2 starting lineup for the game
                                    $getTeam2StartingLineupQuery = $wpdb->get_results('SELECT id_starting_lineup, player1, player2, player3, player4, player5, setplays FROM megaliga_starting_lineup_playoff WHERE megaliga_starting_lineup_playoff.ID = ' . $gameField->id_user_team2 . ' AND megaliga_starting_lineup_playoff.round_number = ' . $round_number);
                                    $game['team2StartingLineup'] = $getTeam2StartingLineupQuery[0];

                                    //get data for players of team1
                                    $getPlayer1DataTeam1Query = $wpdb->get_results('SELECT ekstraliga_player_name FROM megaliga_players WHERE player_id = "' . $getTeam1StartingLineupQuery[0]->player1 . '"');
                                    $getPlayer2DataTeam1Query = $wpdb->get_results('SELECT ekstraliga_player_name FROM megaliga_players WHERE player_id = "' . $getTeam1StartingLineupQuery[0]->player2 . '"');
                                    $getPlayer3DataTeam1Query = $wpdb->get_results('SELECT ekstraliga_player_name FROM megaliga_players WHERE player_id = "' . $getTeam1StartingLineupQuery[0]->player3 . '"');
                                    $getPlayer4DataTeam1Query = $wpdb->get_results('SELECT ekstraliga_player_name FROM megaliga_players WHERE player_id = "' . $getTeam1StartingLineupQuery[0]->player4 . '"');
                                    $getPlayer5DataTeam1Query = $wpdb->get_results('SELECT ekstraliga_player_name FROM megaliga_players WHERE player_id = "' . $getTeam1StartingLineupQuery[0]->player5 . '"');
                                    $game['player1DataTeam1'] = $getPlayer1DataTeam1Query[0];
                                    $game['player2DataTeam1'] = $getPlayer2DataTeam1Query[0];
                                    $game['player3DataTeam1'] = $getPlayer3DataTeam1Query[0];
                                    $game['player4DataTeam1'] = $getPlayer4DataTeam1Query[0];
                                    $game['player5DataTeam1'] = $getPlayer5DataTeam1Query[0];

                                    //get data for players of team2
                                    $getPlayer1DataTeam2Query = $wpdb->get_results('SELECT ekstraliga_player_name FROM megaliga_players WHERE player_id = "' . $getTeam2StartingLineupQuery[0]->player1 . '"');
                                    $getPlayer2DataTeam2Query = $wpdb->get_results('SELECT ekstraliga_player_name FROM megaliga_players WHERE player_id = "' . $getTeam2StartingLineupQuery[0]->player2 . '"');
                                    $getPlayer3DataTeam2Query = $wpdb->get_results('SELECT ekstraliga_player_name FROM megaliga_players WHERE player_id = "' . $getTeam2StartingLineupQuery[0]->player3 . '"');
                                    $getPlayer4DataTeam2Query = $wpdb->get_results('SELECT ekstraliga_player_name FROM megaliga_players WHERE player_id = "' . $getTeam2StartingLineupQuery[0]->player4 . '"');
                                    $getPlayer5DataTeam2Query = $wpdb->get_results('SELECT ekstraliga_player_name FROM megaliga_players WHERE player_id = "' . $getTeam2StartingLineupQuery[0]->player5 . '"');
                                    $game['player1DataTeam2'] = $getPlayer1DataTeam2Query[0];
                                    $game['player2DataTeam2'] = $getPlayer2DataTeam2Query[0];
                                    $game['player3DataTeam2'] = $getPlayer3DataTeam2Query[0];
                                    $game['player4DataTeam2'] = $getPlayer4DataTeam2Query[0];
                                    $game['player5DataTeam2'] = $getPlayer5DataTeam2Query[0];

                                    $returnData[$i] = $game;
                                    $i++;
                                }

                                return $returnData;
                            }

                            $stage = $round_number == 1 ? 'stage = "semifinal"' : 'stage in ("3rdplace", "final")';

                            //get teams that reached playoff stage for given round
                            $getSchedule4PlayoffTeam1 = $wpdb->get_results('SELECT megaliga_team_names.name as "team_name", megaliga_schedule_playoff.team1_score, megaliga_schedule_playoff.id_user_team1, megaliga_user_data.logo_url FROM megaliga_user_data, megaliga_team_names, megaliga_schedule_playoff WHERE megaliga_user_data.ID = megaliga_schedule_playoff.id_user_team1 AND megaliga_user_data.team_names_id = megaliga_team_names.team_names_id AND megaliga_schedule_playoff.round_number = ' . $round_number . ' AND ' . $stage);

                            $getSchedule4PlayoffTeam2 = $wpdb->get_results('SELECT megaliga_team_names.name as "team_name", megaliga_schedule_playoff.team2_score, megaliga_schedule_playoff.id_user_team2, megaliga_user_data.logo_url FROM megaliga_user_data, megaliga_team_names, megaliga_schedule_playoff WHERE megaliga_user_data.ID = megaliga_schedule_playoff.id_user_team2 AND megaliga_user_data.team_names_id = megaliga_team_names.team_names_id AND megaliga_schedule_playoff.round_number = ' . $round_number . ' AND ' . $stage);

                            //get data for the scoreboard
                            //get all games for playoff for given round

                            $getGames4Playoff = $wpdb->get_results('SELECT id_schedule, id_user_team1, id_user_team2 FROM megaliga_schedule_playoff WHERE round_number = ' . $round_number . ' AND ' . $stage);

                            $scoreBoradPlayoffData = getAllGameData($getGames4Playoff, $round_number);

                            //content of the megaliga page
                            the_content();

                            //show button to generate schedule for playoff semifinals only if user with ID == 14 (mbaginski) || 48 (Gabbana) and round_number == 1 and user is logged in
                            if (($userId == 14 || $userId == 48) && $round_number == 1 && is_user_logged_in()) {
                                // get all records from megaliga_schedule_playoff to check if schedule has already been added
                                $getPlayOffSchedule = $wpdb->get_results('SELECT id_schedule FROM megaliga_schedule_playoff');

                                // get all records from megaliga_schedule to check if all scores have already been added
                                $getMegaligaScores = $wpdb->get_results('SELECT team1_score, team2_score FROM megaliga_schedule');
                                $megaligaScoresAdded = true;
                                foreach ($getMegaligaScores as $record) {
                                    if ($record->team1_score < 1 || $record->team2_score < 1) {
                                        $megaligaScoresAdded = false;
                                        break;
                                    }
                                }

                                // if playoff schedule has not been added and all scores for megaliga added, show button to generate playoff schedule R1
                                if (!count($getPlayOffSchedule) && $megaligaScoresAdded) {
                                    echo '<div class="generatePlayInScheduleWrapper marginTop10 marginBottom10">';
                                    echo '  <form action="" method="post">';
                                    echo '      <input type="submit" name="submitPlayOffScheduleR1" value="Generuj terminarz dla fazy play off (półfinał)">';
                                    echo '  </form>';
                                    echo '</div>';
                                }
                            }

                            //show button to generate schedule for playoff for final and 3rd place stage only if user with ID == 14 (mbaginski) || 48 (Gabbana) and round_number == 2 and user is logged in
                            if (($userId == 14 || $userId == 48) && $round_number == 2 && is_user_logged_in()) {
                                // get semifinals records from megaliga_schedule_playoff to check if at least one score is not added -> do not show generation button
                                $getPlayOffSemifinalsScores = $wpdb->get_results('SELECT team1_score, team2_score FROM megaliga_schedule_playoff WHERE stage = "semifinal"');

                                $semifinalsScoresAdded = true;
                                foreach ($getPlayOffSemifinalsScores as $record) {
                                    if (!$record->team1_score || $record->team1_score == 0 || !$record->team2_score ||          $record->team2_score == 0) {
                                        $semifinalsScoresAdded = false;
                                        break;
                                    }
                                }

                                // get finals and 3rd place records from megaliga_schedule_playoff to check if at least one score is added -> do not show generation button
                                $getPlayOffFinalScores = $wpdb->get_results('SELECT team1_score, team2_score FROM megaliga_schedule_playoff WHERE stage = "final" OR stage = "3rdplace"');

                                if ($semifinalsScoresAdded && !count($getPlayOffFinalScores)) {
                                    echo '<div class="generatePlayInScheduleWrapper marginTop10 marginBottom10">';
                                    echo '  <form action="" method="post">';
                                    echo '      <input type="submit" name="submitPlayOffScheduleR2" value="Generuj terminarz dla fazy play off (finał)">';
                                    echo '  </form>';
                                    echo '</div>';
                                }
                            }

                            echo '<div class="scheduleContainer">';
                            drawSchedule($getSchedule4PlayoffTeam1, $getSchedule4PlayoffTeam2, $getGames4Playoff, 'left', $round_number);

                            echo '</div>';
                            echo '<div>';
                            echo '  <div class="scoreTtitleContainer">';
                            echo '      <span class="scoreTableName">Wyniki</span>';
                            echo '  </div>';
                            foreach ($scoreBoradPlayoffData as $gameData) {
                                drawScoreBoard($gameData, $userId);
                            }
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
    <script type="text/javascript">
        (function() {
            var title = document.querySelector('#primary > div.container > div > div > h1');
            title.innerHTML = 'playoffs - ' + title.innerHTML;
        })();
    </script>
    <?php get_footer(); ?>
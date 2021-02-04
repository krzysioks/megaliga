<?php
/*
Template Name: Megaliga ver 1.0
 */
?>
<?php get_header(); ?>
<main id="content">
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
<header class="header">
<h1 class="entry-title"><?php the_title('megaliga: '); ?></h1> <?php edit_post_link(); ?>
</header>
<div class="entry-content">
<?php if (has_post_thumbnail()) {
    the_post_thumbnail();
} ?>

<?php 
global $wpdb;
$title = the_title('', '', false);
$current_user = wp_get_current_user();
//8 - length of "kolejka" string which is in every title of składy subpage
$round_number = substr($title, 0, strlen($title) - 8);
$userId = $current_user->ID;
//$userId = 48;//14;

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
                //perform validation for egxisting id (omit null, 0 which indicates that given //starting lineup position is not used yet)
                if ($col) {
                    $checkIfExistQuery = $wpdb->get_results('SELECT id_scores FROM megaliga_scores WHERE id_schedule = ' . $_POST['id_schedule'] . ' AND id_starting_lineup = ' . $_POST[$idStartingLineup] . ' AND id_player = ' . $col);

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
                        $wpdb->update('megaliga_scores', $submitDataArray, $where);
                    } else {
                        $wpdb->insert('megaliga_scores', $submitDataArray);
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
            $wpdb->update('megaliga_trainer_score', $submitDataArray, $where);
        } else {
            $wpdb->insert('megaliga_trainer_score', $submitDataArray);
        }

        return $score;
    }

    //get players' id for team1
    $getTeam1PlayersIdQuery = $wpdb->get_results('SELECT player1, player2, player3, player4, player5 FROM megaliga_starting_lineup WHERE id_starting_lineup = ' . $_POST['id_starting_lineup_team1']);
    
    //get players' id for team2
    $getTeam2PlayersIdQuery = $wpdb->get_results('SELECT player1, player2, player3, player4, player5 FROM megaliga_starting_lineup WHERE id_starting_lineup = ' . $_POST['id_starting_lineup_team2']);

    //verify if for given player id add new record or edit existing one to megaliga_score table
    $selectedActionTeam1 = getDatabaseActionOnRow($getTeam1PlayersIdQuery, $playerKeyList, 'id_starting_lineup_team1');
    $selectedActionTeam2 = getDatabaseActionOnRow($getTeam2PlayersIdQuery, $playerKeyList, 'id_starting_lineup_team2');

    //verify if to add or update trainer score data
    $checkIfExistTrainerTeam1Query = $wpdb->get_results('SELECT id_trainer FROM megaliga_trainer_score WHERE id_schedule = ' . $_POST['id_schedule'] . ' AND ID = ' . $_POST['id_user_team1']);

    $checkIfExistTrainerTeam2Query = $wpdb->get_results('SELECT id_trainer FROM megaliga_trainer_score WHERE id_schedule = ' . $_POST['id_schedule'] . ' AND ID = ' . $_POST['id_user_team2']);

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
    $wpdb->update('megaliga_schedule', array('team1_score' => $team1Outcome), $where);
    $wpdb->update('megaliga_schedule', array('team2_score' => $team2Outcome), $where);
}

function drawSchedule($queryTeam1Result, $queryTeam2Result, $gameIdentificationData, $groupName, $side, $round_number)
{
    $margin = $side == 'left' ? 'marginRight40' : '';
    echo '<table class="scheduleTable ' . $margin . '" border="0">';
    echo '  <tr><td colspan="6" class="scheduleTableName textLeft">Grupa ' . ucfirst($groupName) . '</td></tr>';
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

    if ($isForm) {
        echo '<form action="" method="post">';
    }
    echo '<table class="scoreBoardTable" border="0">';
    echo '  <tr><td colspan="11" class="teamOverviewContent textLeft">' . $scoreBoardData['team1Data']->team_name . ' : ' . $scoreBoardData['team2Data']->team_name . '</td></tr>';
    echo '  <tr><td colspan="6" class="textLeft"><span class="scoreTableName">Zagrywki: </span><span class="setplayName">' . $scoreBoardData['team1Data']->team_name . ' - ' . $setplayTeam1 . '</span></td><td colspan="5" class="setplayName textLeft">' . $scoreBoardData['team2Data']->team_name . ' - ' . $setplayTeam2 . '</td></tr>';
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
    $getScoreDataTeam1Query = $wpdb->get_results('SELECT heat1, heat2, heat3, heat4, heat5, heat6, heat7, setplays, comment FROM megaliga_scores WHERE id_schedule = ' . $scoreBoardData['id_schedule'] . ' AND id_starting_lineup = ' . $idStartingLineup . ' ORDER BY starting_order ASC');

    //get score for team1 trainer
    $getScoreDataTeam1TrainerQuery = $wpdb->get_results('SELECT heat1, heat2, heat3, heat4, heat5, heat6, heat7, setplays, comment FROM megaliga_trainer_score WHERE id_schedule = ' . $scoreBoardData['id_schedule'] . ' AND ID = ' . $scoreBoardData['id_user_team1']);

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
    echo '      <td class="padding10"></td>';
    echo '      <td class="padding10"></td>';
    echo '      <td class="padding10"></td>';
    echo '      <td class="padding10"></td>';
    echo '      <td class="padding10"></td>';
    echo '      <td class="padding10"></td>';
    echo '      <td class="padding10"></td>';
    echo '      <td class="padding10"></td>';
    echo '      <td class="padding10"></td>';
    echo '      <td class="padding10"></td>';
    echo '      <td class="padding10"></td>';
    echo '  </tr>';

    //get score data for team2
    $idStartingLineup2 = ($scoreBoardData['team2StartingLineup']->id_starting_lineup) ? $scoreBoardData['team2StartingLineup']->id_starting_lineup : 0;
    $getScoreDataTeam2Query = $wpdb->get_results('SELECT heat1, heat2, heat3, heat4, heat5, heat6, heat7, setplays, comment FROM megaliga_scores WHERE id_schedule = ' . $scoreBoardData['id_schedule'] . ' AND id_starting_lineup = ' . $idStartingLineup2 . ' ORDER BY starting_order ASC');

    //get score for team2 trainer
    $getScoreDataTeam2TrainerQuery = $wpdb->get_results('SELECT heat1, heat2, heat3, heat4, heat5, heat6, heat7, setplays, comment FROM megaliga_trainer_score WHERE id_schedule = ' . $scoreBoardData['id_schedule'] . ' AND ID = ' . $scoreBoardData['id_user_team2']);

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
        $getTeam1StartingLineupQuery = $wpdb->get_results('SELECT id_starting_lineup, player1, player2, player3, player4, player5, setplays FROM megaliga_starting_lineup WHERE megaliga_starting_lineup.ID = ' . $gameField->id_user_team1 . ' AND megaliga_starting_lineup.round_number = ' . $round_number);
        $game['team1StartingLineup'] = $getTeam1StartingLineupQuery[0];
    
        //get data related with team2
        $getTeam2DataQuery = $wpdb->get_results('SELECT megaliga_team_names.name as "team_name" FROM megaliga_team_names, megaliga_user_data WHERE megaliga_user_data.ID = ' . $gameField->id_user_team2 . ' AND megaliga_user_data.team_names_id = megaliga_team_names.team_names_id');
        $game['team2Data'] = $getTeam2DataQuery[0];
    
        //get team's 2 starting lineup for the game
        $getTeam2StartingLineupQuery = $wpdb->get_results('SELECT id_starting_lineup, player1, player2, player3, player4, player5, setplays FROM megaliga_starting_lineup WHERE megaliga_starting_lineup.ID = ' . $gameField->id_user_team2 . ' AND megaliga_starting_lineup.round_number = ' . $round_number);
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

//get teams for Dolce ligue
$getSchedule4DolceTeam1Query = $wpdb->get_results('SELECT megaliga_team_names.name as "team_name", megaliga_schedule.team1_score, megaliga_schedule.id_user_team1, megaliga_user_data.logo_url FROM megaliga_user_data, megaliga_team_names, megaliga_schedule WHERE megaliga_user_data.ID = megaliga_schedule.id_user_team1 AND megaliga_user_data.ligue_groups_id = megaliga_schedule.id_ligue_group AND megaliga_schedule.id_ligue_group = 1 AND megaliga_user_data.team_names_id = megaliga_team_names.team_names_id AND megaliga_schedule.round_number = ' . $round_number);

$getSchedule4DolceTeam2Query = $wpdb->get_results('SELECT megaliga_team_names.name as "team_name", megaliga_schedule.team2_score, megaliga_schedule.id_user_team2, megaliga_user_data.logo_url FROM megaliga_user_data, megaliga_team_names, megaliga_schedule WHERE megaliga_user_data.ID = megaliga_schedule.id_user_team2 AND megaliga_user_data.ligue_groups_id = megaliga_schedule.id_ligue_group AND megaliga_schedule.id_ligue_group = 1 AND megaliga_user_data.team_names_id = megaliga_team_names.team_names_id AND megaliga_schedule.round_number = ' . $round_number);

//get teams for Gabbama ligue
$getSchedule4GabbanaTeam1Query = $wpdb->get_results('SELECT megaliga_team_names.name as "team_name", megaliga_schedule.team1_score, megaliga_schedule.id_user_team1, megaliga_user_data.logo_url FROM megaliga_user_data, megaliga_team_names, megaliga_schedule WHERE megaliga_user_data.ID = megaliga_schedule.id_user_team1 AND megaliga_user_data.ligue_groups_id = megaliga_schedule.id_ligue_group AND megaliga_schedule.id_ligue_group = 2 AND megaliga_user_data.team_names_id = megaliga_team_names.team_names_id AND megaliga_schedule.round_number = ' . $round_number);

$getSchedule4GabbanaTeam2Query = $wpdb->get_results('SELECT megaliga_team_names.name as "team_name", megaliga_schedule.team2_score, megaliga_schedule.id_user_team2, megaliga_user_data.logo_url FROM megaliga_user_data, megaliga_team_names, megaliga_schedule WHERE megaliga_user_data.ID = megaliga_schedule.id_user_team2 AND megaliga_user_data.ligue_groups_id = megaliga_schedule.id_ligue_group AND megaliga_schedule.id_ligue_group = 2 AND megaliga_user_data.team_names_id = megaliga_team_names.team_names_id AND megaliga_schedule.round_number = ' . $round_number);

//get data for the scoreboard
//get all games for Dolce for given round
$getGames4DolceQuery = $wpdb->get_results('SELECT id_schedule, id_user_team1, id_user_team2 FROM megaliga_schedule WHERE id_ligue_group = 1 AND round_number = ' . $round_number);
$scoreBoradDolceData = getAllGameData($getGames4DolceQuery, $round_number);

//get all games for Gabbana for given round
$getGames4GabbanaQuery = $wpdb->get_results('SELECT id_schedule, id_user_team1, id_user_team2 FROM megaliga_schedule WHERE id_ligue_group = 2 AND round_number = ' . $round_number);
$scoreBoradGabbanaData = getAllGameData($getGames4GabbanaQuery, $round_number);

//content of the megaliga page
the_content();
echo '<div class="scheduleContainer">';
drawSchedule($getSchedule4DolceTeam1Query, $getSchedule4DolceTeam2Query, $getGames4DolceQuery, 'dolce', 'left', $round_number);
drawSchedule($getSchedule4GabbanaTeam1Query, $getSchedule4GabbanaTeam2Query, $getGames4GabbanaQuery, 'gabbana', 'right', $round_number);
echo '</div>';
echo '<div>';
echo '  <div class="marginTop10">';
echo '      <span class="scoreTableName">Wyniki Dolce</span>';
echo '  </div>';
foreach ($scoreBoradDolceData as $gameData) {
    drawScoreBoard($gameData, $userId);
}
echo '  <div class="marginTop10">';
echo '      <span class="scoreTableName">Wyniki Gabbana</span>';
echo '  </div>';
foreach ($scoreBoradGabbanaData as $gameData) {
    drawScoreBoard($gameData, $userId);
}
echo '</div>';
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
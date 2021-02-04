<?php
/*
Template Name: Standings Playoff
Description: Shows standings of the playoff phase for one group in the ligue. Shows the playoff ladder, which consists of 4 phases. Phase 1-3 (relative to 1, 2, 3 kolejka) are the rounds where each team plays 1 game with all teams. Phase 4 - final  (first and second team) and game for 3rd place (third and fourth team)
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

function getStageData($stage)
{
    global $wpdb;

    $returnData = array('team1Name' => '', 'team2Name' => '', 'scoreTeam1Round1' => 0, 'scoreTeam2Round1' => 0, 'scoreTeam1Round2' => 0, 'scoreTeam2Round2' => 0, 'totalTeam1' => null, 'totalTeam2' => null, 'seedNumberTeam1' => 0, 'seedNumberTeam2' => 0, 'winner' => 'noone');

    //get team names of both users
    $getTeam1Name = $wpdb->get_results('SELECT megaliga_team_names.name as "team_name" FROM megaliga_team_names, megaliga_user_data WHERE megaliga_team_names.team_names_id = megaliga_user_data.team_names_id AND megaliga_user_data.reached_playoff = 1 AND megaliga_user_data.ID = ' . $stage->id_user_team1);
    $getTeam2Name = $wpdb->get_results('SELECT megaliga_team_names.name as "team_name" FROM megaliga_team_names, megaliga_user_data WHERE megaliga_team_names.team_names_id = megaliga_user_data.team_names_id AND megaliga_user_data.reached_playoff = 1 AND megaliga_user_data.ID = ' . $stage->id_user_team2);

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
    }

    return $returnData;
}

function drawSecondStageMatchup($data, $title, $marginSize)
{
    $totalScoreTeam1 = $data['totalScoreTeam1'] == 0 ? '' : $data['totalScoreTeam1'];
    $totalScoreTeam2 = $data['totalScoreTeam2'] == 0 ? '' : $data['totalScoreTeam2'];

    echo '<div class="playoffPhaseTitlePosition marginTop' . $marginSize . '"><span class="playoffPhaseTitle">' . $title . '</span></div>';

    echo '<div class="pairLadderContainer flexDirectionColumn">';

    echo '  <div class="pairLegendContainer">';
    echo '      <div class="pairLegend legendRound1Margin">1</div><div class="pairLegend legendRound2Margin">2</div><div class="pairLegend marginRight20"></div>';
    echo '  </div>';

    echo '  <div class="teamLadderContainer marginBottom10">';
    echo '      <div class="seedNumberContainer">' . $data['seedNumberTeam1'] . '</div>';
    echo '      <div class="teamNameContainer">';
    echo '          <span class="playoffLadderContent">' . $data['team1Name'] . '</span>';
    echo '          <span class="score">' . $data['scoreTeam1Round1'] . '</span>';
    echo '          <span class="score">' . $data['scoreTeam1Round2'] . '</span>';
    echo '          <span class="score">' . $totalScoreTeam1 . '</span>';
    echo '      </div>';
    echo '  </div>';
    echo '  <div class="teamLadderContainer">';
    echo '      <div class="seedNumberContainer">' . $data['seedNumberTeam2'] . '</div>';
    echo '      <div class="teamNameContainer">';
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
        $semifinalData[$i] = getStageData($stage);
        $i++;
    }

    //draw semifinal stage
    echo '<div class="phaseContainer">';
    echo '<div class="playoffPhaseTitlePosition marginTop20"><span class="playoffPhaseTitle">półfinał</span></div>';

    for ($j = 0; $j < 2; $j++) {
        echo '<div class="pairLadderContainer flexDirectionColumn">';

        echo '  <div class="pairLegendContainer">';
        echo '      <div class="pairLegend legendRound1Margin">1</div><div class="pairLegend legendRound2Margin">2</div><div class="pairLegend marginRight20"></div>';
        echo '  </div>';

        $team1AddedStyle = ($semifinalData[$j]['winner'] == 'team1') ? ' winner' : '';
        $team2AddedStyle = ($semifinalData[$j]['winner'] == 'team2') ? ' winner' : '';
        $totalScoreTeam1 = $semifinalData[$j]['totalScoreTeam1'] == 0 ? '' : $semifinalData[$j]['totalScoreTeam1'];
        $totalScoreTeam2 = $semifinalData[$j]['totalScoreTeam2'] == 0 ? '' : $semifinalData[$j]['totalScoreTeam2'];

        echo '  <div class="teamLadderContainer marginBottom10">';
        echo '      <div class="seedNumberContainer">' . $semifinalData[$j]['seedNumberTeam1'] . '</div>';
        echo '      <div class="teamNameContainer' . $team1AddedStyle . '">';
        echo '          <span class="playoffLadderContent">' . $semifinalData[$j]['team1Name'] . '</span>';
        echo '          <span class="score">' . $semifinalData[$j]['scoreTeam1Round1'] . '</span>';
        echo '          <span class="score">' . $semifinalData[$j]['scoreTeam1Round2'] . '</span>';
        echo '          <span class="score">' . $totalScoreTeam1 . '</span>';
        echo '      </div>';
        echo '  </div>';
        echo '  <div class="teamLadderContainer">';
        echo '      <div class="seedNumberContainer">' . $semifinalData[$j]['seedNumberTeam2'] . '</div>';
        echo '      <div class="teamNameContainer' . $team2AddedStyle . '">';
        echo '          <span class="playoffLadderContent">' . $semifinalData[$j]['team2Name'] . '</span>';
        echo '          <span class="score">' . $semifinalData[$j]['scoreTeam2Round1'] . '</span>';
        echo '          <span class="score">' . $semifinalData[$j]['scoreTeam2Round2'] . '</span>';
        echo '          <span class="score">' . $totalScoreTeam2 . '</span>';
        echo '      </div>';
        echo '  </div>';

        echo '</div>';
    }

    echo '</div>';


    //get data for final and 3rd place matchup stage
    $finalData = array();
    $thridPlaceData = array();

    $getfinalStage = $wpdb->get_results('SELECT id_user_team1, id_user_team2, stage, id_schedule_round1, id_schedule_round2, seed_number_team1, seed_number_team2 FROM megaliga_playoff_ladder WHERE stage = "final"');

    $getThirdPlaceStage = $wpdb->get_results('SELECT id_user_team1, id_user_team2, stage, id_schedule_round1, id_schedule_round2, seed_number_team1, seed_number_team2 FROM megaliga_playoff_ladder WHERE stage = "3rdplace"');

    $finalData = getStageData($getfinalStage[0]);
    $thridPlaceData = getStageData($getThirdPlaceStage[0]);

    //draw 2nd stage
    echo '<div class="phaseContainer">';
    //draw final stage
    drawSecondStageMatchup($finalData, 'finał', '20');
    //draw 3rd place stage
    drawSecondStageMatchup($thridPlaceData, 'mecz o 3 miejsce', '40');
    echo '</div>';

    //draw results
    //draw results only when winner of each matchup is complete
    if ($finalData['winner'] != 'noone' && $thridPlaceData['winner'] != 'noone') {
        //get data
        $winnerId = $finalData['winner'] == 'team1' ? $getfinalStage[0]->id_user_team1 : $getfinalStage[0]->id_user_team2;

        $getWinnerData = $wpdb->get_results('SELECT logo_url FROM megaliga_user_data WHERE reached_playoff = 1 AND ID = ' . $winnerId);

        $winnerTeamNameKey = $finalData['winner'] . 'Name';
        $runnerUpTeamNameKey = $finalData['winner'] == 'team1' ? 'team2Name' : 'team1Name';
        $secondRunnerUpTeamNameKey = $thridPlaceData['winner'] . 'Name';

        echo '<div class="phaseContainer winnerMargin">';
        echo '  <div class="playoffPhaseTitlePosition marginTop20"><span class="playoffPhaseTitle">mistrz megaligi</span></div>';
        echo '  <div class="winnerContainer">';
        echo '      <div>';
        echo '          <img src="' . $getWinnerData[0]->logo_url . '" width="75px" height="75px">';
        echo '      </div>';
        echo '      <span class="winnerName">' . $finalData[$winnerTeamNameKey] . '</span>';
        echo '  </div>';
        echo '  <div class="playoffPhaseTitlePosition marginTop100"><span class="playoffPhaseTitle">2 miejsce</span></div>';
        echo '  <div class="winnerContainer">';
        echo '      <span class="playoffLadderContent runnerUpName">' . $finalData[$runnerUpTeamNameKey] . '</span>';
        echo '  </div>';
        echo '  <div class="playoffPhaseTitlePosition"><span class="playoffPhaseTitle">3 miejsce</span></div>';
        echo '  <div class="winnerContainer">';
        echo '      <span class="playoffLadderContent runnerUpName">' . $thridPlaceData[$secondRunnerUpTeamNameKey] . '</span>';
        echo '  </div>';
        echo '</div>';
    }
}

$getSchedulePlayoff = $wpdb->get_results('SELECT team1_score, team2_score, id_user_team1, id_user_team2, round_number FROM megaliga_schedule_playoff ORDER BY round_number');

$getPlayoffLadderData = $wpdb->get_results('SELECT id_user_team1, id_user_team2, stage, id_schedule_round1, id_schedule_round2 FROM megaliga_playoff_ladder');

//content of the team page
echo '<div>';
echo '  <div class="playoffLadder displayFlex playoffLadderflexDirection">';
drawLadder($getSchedulePlayoff);
echo '  </div>';
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
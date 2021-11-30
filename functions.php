<?php
function my_theme_enqueue_styles()
{

    $parent_style = 'services-style'; // This is 'servicesstyle' for the Services theme.

    wp_enqueue_style($parent_style, get_template_directory_uri() . '/style.css');
    wp_enqueue_style(
        'child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array($parent_style),
        wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'my_theme_enqueue_styles');

add_action('login_head', 'hide_login_nav');
function hide_login_nav()
{
    echo '<style>#nav{display:none}</style>';
}

function custom_paginate_comments_links($args = array())
{
    global $wp_rewrite;
    if (!is_singular())
        return;

    $page = get_query_var('cpage');

    if (!$page)
        $page = 1;
    $max_page = get_comment_pages_count();
    //changed add_fragment from original #comments -> #contents to show 1 paginated page and to focus page on top of the page
    $defaults = array(
        'base' => add_query_arg('cpage', '%#%'),
        'format' => '',
        'total' => $max_page,
        'current' => $page,
        'echo' => true,
        'add_fragment' => '#contents'
    );
    if ($wp_rewrite->using_permalinks())
        $defaults['base'] = user_trailingslashit(trailingslashit(get_permalink()) . $wp_rewrite->comments_pagination_base . '-%#%', 'commentpaged');

    $args = wp_parse_args($args, $defaults);
    $page_links = paginate_links($args);

    if ($args['echo'])
        echo $page_links;
    else
        return $page_links;
}
add_filter('paginate_comments_links', 'custom_paginate_comments_links');


function add_custom_role($bbp_roles)
{

    $bbp_roles['bbp_megaligaParticipant'] = array(
        'name' => 'megaligaParticipant',
        'capabilities' => bbp_get_caps_for_role(bbp_get_participant_role()) // the same capabilities as participants
    );

    return $bbp_roles;
}
add_filter('bbp_get_dynamic_roles', 'add_custom_role', 1);

if (!function_exists('custom_reverse_comments')) {
    function custom_reverse_comments($comments)
    {
        return array_reverse($comments);
    }
}
add_filter('comments_array', 'custom_reverse_comments');

function my_login_logo_one()
{
?>
    <style type="text/css">
        body.login div#login h1 a {
            background-image: url(http://megaliga.eu/wp-content/uploads/2018/02/logo.png);
            padding-bottom: 60px;
            background-size: 151px 56px;
            width: 151px;
            height: 56px;
        }
    </style>
<?php
}
add_action('login_enqueue_scripts', 'my_login_logo_one');

add_action('admin_post_handle_csv_export', 'handle_csv_export_callback');
// add_action('admin_post_nopriv_handle_csv_export', 'handle_csv_export_callback');

function handle_csv_export_callback()
{
    $current_user = wp_get_current_user();
    $userId = $current_user->ID;
    global $wpdb;

    if (($_POST['submitExportMegaliga'] || $_POST['submitEksportPlayoff']) && is_user_logged_in() && ($userId == 14 || $userId == 48 || $userId == 1)) {
        //TODO round number will be taken from forEach loop (14 - megaliga; 4 - playoff)
        //TODO 1st step - logic for 1 round of megaliga
        //TODO 2nd step - add logic to fetch all 14 rounds at once
        //TODO 3rd step - add logic for playoff

        //export scoreboard data for given game
        function getScoreBoardExportData($scoreBoardData, $f, $delimiter, $round_number, $dbName)
        {
            global $wpdb;

            //get score data for team1
            $idStartingLineup = ($scoreBoardData['team1StartingLineup']->id_starting_lineup) ? $scoreBoardData['team1StartingLineup']->id_starting_lineup : 0;
            $getScoreDataTeam1Query = $wpdb->get_results('SELECT heat1, heat2, heat3, heat4, heat5, heat6, heat7, setplays, comment FROM megaliga_scores' . $dbName . ' WHERE id_schedule = ' . $scoreBoardData['id_schedule'] . ' AND id_starting_lineup = ' . $idStartingLineup . ' ORDER BY starting_order ASC');

            //get score for team1 trainer
            $getScoreDataTeam1TrainerQuery = $wpdb->get_results('SELECT heat1, heat2, heat3, heat4, heat5, heat6, heat7, setplays, comment FROM megaliga_trainer_score' . $dbName . ' WHERE id_schedule = ' . $scoreBoardData['id_schedule'] . ' AND ID = ' . $scoreBoardData['id_user_team1']);

            if ($scoreBoardData['player1DataTeam1']->ekstraliga_player_name != '') {
                $row = array($round_number, $scoreBoardData['player1DataTeam1']->ekstraliga_player_name, $scoreBoardData['team1Data']->team_name, $getScoreDataTeam1Query[0]->heat1, $getScoreDataTeam1Query[0]->heat2, $getScoreDataTeam1Query[0]->heat3, $getScoreDataTeam1Query[0]->heat4, $getScoreDataTeam1Query[0]->heat5, $getScoreDataTeam1Query[0]->heat6, $getScoreDataTeam1Query[0]->heat7, $getScoreDataTeam1Query[0]->setplays, $getScoreDataTeam1Query[0]->comment);
                fputcsv($f, $row, $delimiter);
            }

            if ($scoreBoardData['player2DataTeam1']->ekstraliga_player_name != '') {
                $row = array($round_number, $scoreBoardData['player2DataTeam1']->ekstraliga_player_name, $scoreBoardData['team1Data']->team_name, $getScoreDataTeam1Query[1]->heat1, $getScoreDataTeam1Query[1]->heat2, $getScoreDataTeam1Query[1]->heat3, $getScoreDataTeam1Query[1]->heat4, $getScoreDataTeam1Query[1]->heat5, $getScoreDataTeam1Query[1]->heat6, $getScoreDataTeam1Query[1]->heat7, $getScoreDataTeam1Query[1]->setplays, $getScoreDataTeam1Query[1]->comment);
                fputcsv($f, $row, $delimiter);
            }

            if ($scoreBoardData['player3DataTeam1']->ekstraliga_player_name != '') {
                $row = array($round_number, $scoreBoardData['player3DataTeam1']->ekstraliga_player_name, $scoreBoardData['team1Data']->team_name, $getScoreDataTeam1Query[2]->heat1, $getScoreDataTeam1Query[2]->heat2, $getScoreDataTeam1Query[2]->heat3, $getScoreDataTeam1Query[2]->heat4, $getScoreDataTeam1Query[2]->heat5, $getScoreDataTeam1Query[2]->heat6, $getScoreDataTeam1Query[2]->heat7, $getScoreDataTeam1Query[2]->setplays, $getScoreDataTeam1Query[2]->comment);
                fputcsv($f, $row, $delimiter);
            }

            if ($scoreBoardData['player4DataTeam1']->ekstraliga_player_name != '') {
                $row = array($round_number, $scoreBoardData['player4DataTeam1']->ekstraliga_player_name, $scoreBoardData['team1Data']->team_name, $getScoreDataTeam1Query[3]->heat1, $getScoreDataTeam1Query[3]->heat2, $getScoreDataTeam1Query[3]->heat3, $getScoreDataTeam1Query[3]->heat4, $getScoreDataTeam1Query[3]->heat5, $getScoreDataTeam1Query[3]->heat6, $getScoreDataTeam1Query[3]->heat7, $getScoreDataTeam1Query[3]->setplays, $getScoreDataTeam1Query[3]->comment);
                fputcsv($f, $row, $delimiter);
            }

            if ($scoreBoardData['player5DataTeam1']->ekstraliga_player_name != '') {
                $row = array($round_number, $scoreBoardData['player5DataTeam1']->ekstraliga_player_name, $scoreBoardData['team1Data']->team_name, $getScoreDataTeam1Query[4]->heat1, $getScoreDataTeam1Query[4]->heat2, $getScoreDataTeam1Query[4]->heat3, $getScoreDataTeam1Query[4]->heat4, $getScoreDataTeam1Query[4]->heat5, $getScoreDataTeam1Query[4]->heat6, $getScoreDataTeam1Query[4]->heat7, $getScoreDataTeam1Query[4]->setplays, $getScoreDataTeam1Query[4]->comment);
                fputcsv($f, $row, $delimiter);
            }

            //get data for trainer team1
            $row = array($round_number, 'Trener', $scoreBoardData['team1Data']->team_name, $getScoreDataTeam1TrainerQuery[0]->heat1, $getScoreDataTeam1TrainerQuery[0]->heat2, $getScoreDataTeam1TrainerQuery[0]->heat3, $getScoreDataTeam1TrainerQuery[0]->heat4, $getScoreDataTeam1TrainerQuery[0]->heat5, $getScoreDataTeam1TrainerQuery[0]->heat6, $getScoreDataTeam1TrainerQuery[0]->heat7, $getScoreDataTeam1TrainerQuery[0]->setplays, $getScoreDataTeam1TrainerQuery[0]->comment);
            fputcsv($f, $row, $delimiter);

            //get score data for team2
            $idStartingLineup2 = ($scoreBoardData['team2StartingLineup']->id_starting_lineup) ? $scoreBoardData['team2StartingLineup']->id_starting_lineup : 0;
            $getScoreDataTeam2Query = $wpdb->get_results('SELECT heat1, heat2, heat3, heat4, heat5, heat6, heat7, setplays, comment FROM megaliga_scores' . $dbName . ' WHERE id_schedule = ' . $scoreBoardData['id_schedule'] . ' AND id_starting_lineup = ' . $idStartingLineup2 . ' ORDER BY starting_order ASC');

            //get score for team2 trainer
            $getScoreDataTeam2TrainerQuery = $wpdb->get_results('SELECT heat1, heat2, heat3, heat4, heat5, heat6, heat7, setplays, comment FROM megaliga_trainer_score' . $dbName . ' WHERE id_schedule = ' . $scoreBoardData['id_schedule'] . ' AND ID = ' . $scoreBoardData['id_user_team2']);

            if ($scoreBoardData['player1DataTeam2']->ekstraliga_player_name != '') {
                $row = array($round_number, $scoreBoardData['player1DataTeam2']->ekstraliga_player_name, $scoreBoardData['team2Data']->team_name, $getScoreDataTeam2Query[0]->heat1, $getScoreDataTeam2Query[0]->heat2, $getScoreDataTeam2Query[0]->heat3, $getScoreDataTeam2Query[0]->heat4, $getScoreDataTeam2Query[0]->heat5, $getScoreDataTeam2Query[0]->heat6, $getScoreDataTeam2Query[0]->heat7, $getScoreDataTeam2Query[0]->setplays, $getScoreDataTeam2Query[0]->comment);
                fputcsv($f, $row, $delimiter);
            }

            if ($scoreBoardData['player2DataTeam2']->ekstraliga_player_name != '') {
                $row = array($round_number, $scoreBoardData['player2DataTeam2']->ekstraliga_player_name, $scoreBoardData['team2Data']->team_name, $getScoreDataTeam2Query[1]->heat1, $getScoreDataTeam2Query[1]->heat2, $getScoreDataTeam2Query[1]->heat3, $getScoreDataTeam2Query[1]->heat4, $getScoreDataTeam2Query[1]->heat5, $getScoreDataTeam2Query[1]->heat6, $getScoreDataTeam2Query[1]->heat7, $getScoreDataTeam2Query[1]->setplays, $getScoreDataTeam2Query[1]->comment);
                fputcsv($f, $row, $delimiter);
            }

            if ($scoreBoardData['player3DataTeam2']->ekstraliga_player_name != '') {
                $row = array($round_number, $scoreBoardData['player3DataTeam2']->ekstraliga_player_name, $scoreBoardData['team2Data']->team_name, $getScoreDataTeam2Query[2]->heat1, $getScoreDataTeam2Query[2]->heat2, $getScoreDataTeam2Query[2]->heat3, $getScoreDataTeam2Query[2]->heat4, $getScoreDataTeam2Query[2]->heat5, $getScoreDataTeam2Query[2]->heat6, $getScoreDataTeam2Query[2]->heat7, $getScoreDataTeam2Query[2]->setplays, $getScoreDataTeam2Query[2]->comment);
                fputcsv($f, $row, $delimiter);
            }

            if ($scoreBoardData['player4DataTeam2']->ekstraliga_player_name != '') {
                $row = array($round_number, $scoreBoardData['player4DataTeam2']->ekstraliga_player_name, $scoreBoardData['team2Data']->team_name, $getScoreDataTeam2Query[3]->heat1, $getScoreDataTeam2Query[3]->heat2, $getScoreDataTeam2Query[3]->heat3, $getScoreDataTeam2Query[3]->heat4, $getScoreDataTeam2Query[3]->heat5, $getScoreDataTeam2Query[3]->heat6, $getScoreDataTeam2Query[3]->heat7, $getScoreDataTeam2Query[3]->setplays, $getScoreDataTeam2Query[3]->comment);
                fputcsv($f, $row, $delimiter);
            }

            if ($scoreBoardData['player5DataTeam2']->ekstraliga_player_name != '') {
                $row = array($round_number, $scoreBoardData['player5DataTeam2']->ekstraliga_player_name, $scoreBoardData['team2Data']->team_name, $getScoreDataTeam2Query[4]->heat1, $getScoreDataTeam2Query[4]->heat2, $getScoreDataTeam2Query[4]->heat3, $getScoreDataTeam2Query[4]->heat4, $getScoreDataTeam2Query[4]->heat5, $getScoreDataTeam2Query[4]->heat6, $getScoreDataTeam2Query[4]->heat7, $getScoreDataTeam2Query[4]->setplays, $getScoreDataTeam2Query[4]->comment);
                fputcsv($f, $row, $delimiter);
            }

            $row = array($round_number, 'Trener', $scoreBoardData['team2Data']->team_name, $getScoreDataTeam2TrainerQuery[0]->heat1, $getScoreDataTeam2TrainerQuery[0]->heat2, $getScoreDataTeam2TrainerQuery[0]->heat3, $getScoreDataTeam2TrainerQuery[0]->heat4, $getScoreDataTeam2TrainerQuery[0]->heat5, $getScoreDataTeam2TrainerQuery[0]->heat6, $getScoreDataTeam2TrainerQuery[0]->heat7, $getScoreDataTeam2TrainerQuery[0]->setplays, $getScoreDataTeam2TrainerQuery[0]->comment);
            fputcsv($f, $row, $delimiter);
        }

        function getAllGameData($query, $round_number, $dbName)
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
                $getTeam1StartingLineupQuery = $wpdb->get_results('SELECT id_starting_lineup, player1, player2, player3, player4, player5, setplays FROM megaliga_starting_lineup' . $dbName . ' WHERE megaliga_starting_lineup' . $dbName . '.ID = ' . $gameField->id_user_team1 . ' AND megaliga_starting_lineup' . $dbName . '.round_number = ' . $round_number);
                $game['team1StartingLineup'] = $getTeam1StartingLineupQuery[0];

                //get data related with team2
                $getTeam2DataQuery = $wpdb->get_results('SELECT megaliga_team_names.name as "team_name" FROM megaliga_team_names, megaliga_user_data WHERE megaliga_user_data.ID = ' . $gameField->id_user_team2 . ' AND megaliga_user_data.team_names_id = megaliga_team_names.team_names_id');
                $game['team2Data'] = $getTeam2DataQuery[0];

                //get team's 2 starting lineup for the game
                $getTeam2StartingLineupQuery = $wpdb->get_results('SELECT id_starting_lineup, player1, player2, player3, player4, player5, setplays FROM megaliga_starting_lineup' . $dbName . ' WHERE megaliga_starting_lineup' . $dbName . '.ID = ' . $gameField->id_user_team2 . ' AND megaliga_starting_lineup' . $dbName . '.round_number = ' . $round_number);
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

        $typeOfExport = $_POST['submitExportMegaliga'] == 'Eksport megaliga' ? 'megaliga' : 'playoff';
        $dbName = $typeOfExport == 'megaliga' ? '' : '_playoff';
        $delimiter = ',';
        $fileName = 'export_' . $typeOfExport . '.csv';
        // Create a file pointer
        $f = fopen('php://output', 'w');
        fputs($f, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

        // Set column headers
        $header = array('Runda', 'Zawodnik', 'Drużyna', 'Bieg 1', 'Bieg 2', 'Bieg 3', 'Bieg 4', 'Bieg 5', 'Bieg 6', 'Bieg 7', 'Zagrywki', 'Komentarz');
        fputcsv($f, $header, $delimiter);

        $roundSize = $typeOfExport == 'megaliga' ? 14 : 4;

        if ($typeOfExport == 'megaliga') {
            for ($round_number = 1; $round_number <= $roundSize; $round_number++) {
                //get data for the scoreboard
                //get all games for Dolce for given round
                $getGames4Dolce = $wpdb->get_results('SELECT id_schedule, id_user_team1, id_user_team2 FROM megaliga_schedule' . $dbName . ' WHERE id_ligue_group = 1 AND round_number = ' . $round_number);
                $scoreBoradDolceData = getAllGameData($getGames4Dolce, $round_number, $dbName);

                //get all games for Gabbana for given round
                $getGames4Gabbana = $wpdb->get_results('SELECT id_schedule, id_user_team1, id_user_team2 FROM megaliga_schedule' . $dbName . ' WHERE id_ligue_group = 2 AND round_number = ' . $round_number);
                $scoreBoradGabbanaData = getAllGameData($getGames4Gabbana, $round_number, $dbName);

                //export data for Dolce 
                $groupNameDolce = array('Dolce', '', '', '', '', '', '', '', '', '', '', '');
                fputcsv($f, $groupNameDolce, $delimiter);
                foreach ($scoreBoradDolceData as $gameData) {
                    getScoreBoardExportData($gameData, $f, $delimiter, $round_number, $dbName);
                }

                //export data for Gabbana
                $groupNameGabbana = array('Gabbana', '', '', '', '', '', '', '', '', '', '', '');
                fputcsv($f, $groupNameGabbana, $delimiter);
                foreach ($scoreBoradGabbanaData as $gameData) {
                    getScoreBoardExportData($gameData, $f, $delimiter, $round_number, $dbName);
                }
            }
        } else {
            for ($round_number = 1; $round_number <= $roundSize; $round_number++) {
                //get data for the scoreboard
                //get all games for playoff for given round
                $getGames4Playoff = $wpdb->get_results('SELECT id_schedule, id_user_team1, id_user_team2 FROM megaliga_schedule_playoff WHERE round_number = ' . $round_number);
                $scoreBoradPlayoffData = getAllGameData($getGames4Playoff, $round_number, $dbName);

                //export data
                foreach ($scoreBoradPlayoffData as $gameData) {
                    getScoreBoardExportData($gameData, $f, $delimiter, $round_number, $dbName);
                }
            }
        }


        // Set headers to download file rather than displayed 
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $fileName . '";');

        //output all remaining data on a file pointer 
        fpassthru($f);
        fclose($f);
    }
}

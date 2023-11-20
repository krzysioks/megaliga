<?php
/*
Template Name: Roster Play in
Description: Shows play in roster for the teams for two groups in the ligue.
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
                            //set up constant vars
                            // global $wpdb;
                            $title = the_title('', '', false);
                            // $current_user = wp_get_current_user();
                            // //8 - length of "kolejka" string which is in every title of składy subpage
                            $round_number = substr($title, 0, strlen($title) - 8);
                            echo 'PLay in ' . $title . ' ' . $round_number;
                            // $userId = $current_user->ID;
                            // // $userId = 14;

                            // //handling submision of form's status
                            // if ($_POST['submitFormStatus']) {
                            //     $submitDataArray = array();
                            //     $submitDataArray['is_open'] = $_POST['is_open'];
                            //     $where = array('round_number' => $round_number, 'season_stage' => 'regular');
                            //     $wpdb->update('megaliga_starting_lineup_status', $submitDataArray, $where);
                            // }

                            // //defining if roster submission form should be
                            // $isUserMegaligaMemberQuery = $wpdb->get_results('SELECT user_data_id FROM megaliga_user_data WHERE ID = ' . $userId);

                            // //check if roster form is enabled (this option prevent from resubmission of roster by user after score for given round has been calculated)
                            // $isFormEnabled = $wpdb->get_results('SELECT is_open FROM megaliga_starting_lineup_status WHERE round_number = ' . $round_number . ' AND season_stage = "regular"');

                            // $showRosterForm = $userId != 0 && count($isUserMegaligaMemberQuery) == 1 && $isFormEnabled[0]->is_open;

                            // //handling submission
                            // if ($_POST['submitStartingLineup']) {
                            //     $getGroupName = $wpdb->get_results('SELECT megaliga_ligue_groups.name FROM megaliga_ligue_groups, megaliga_user_data WHERE megaliga_user_data.ID = ' . $_POST['userId'] . ' AND megaliga_user_data.ligue_groups_id = megaliga_ligue_groups.ligue_groups_id');

                            //     //get list of all available players in the team
                            //     $getRosterQuery = $wpdb->get_results('SELECT player_id FROM megaliga_players WHERE id_user_' . $getGroupName[0]->name . ' = ' . $_POST['userId']);
                            //     $i = 1;
                            //     $submitDataArray = array();
                            //     //iterate through complete roster of the team
                            //     foreach ($getRosterQuery as $rosterField) {
                            //         //for each player check if selected AND number of slected players is not above 5      
                            //         if (isset($_POST['player' . $rosterField->player_id]) && $i <= 5) {
                            //             //save to array under playerN (N=1,2,3..5) key (1-5 defines starting number which is taken from the spinner input) id of the selected player
                            //             $submitDataArray['player' . $_POST['startingNumber' . $rosterField->player_id]] = $_POST['player' . $rosterField->player_id];
                            //             $i++;
                            //         }
                            //     }

                            //     //if not all players selected fill remaning ones with 0 to clear any previous selections
                            //     for ($i = 1; $i <= 5; $i++) {
                            //         if (!array_key_exists('player' . $i, $submitDataArray)) {
                            //             $submitDataArray['player' . $i] = 0;
                            //         }
                            //     }

                            //     //check if to insert new record or update already existing
                            //     $checkIfRecordExist = $wpdb->get_results('SELECT id_starting_lineup, player1, player2, player3, player4, player5 FROM megaliga_starting_lineup WHERE round_number = ' . $round_number . ' AND ID = ' . $_POST['userId']);

                            //     $submitDataArray['round_number'] = $round_number;
                            //     $submitDataArray['ID'] = $_POST['userId'];
                            //     $submitDataArray['setplays'] = $_POST['setplayInput'];

                            //     if (count($checkIfRecordExist) == 1) {
                            //         //if starting lineup has already been set for the given round -> before update remove currently selected players from megaliga_scores. So there want be redundant records
                            //         $checkIfPlayerScoreExistQuery = $wpdb->get_results('SELECT id_scores FROM megaliga_scores WHERE id_starting_lineup = ' . $checkIfRecordExist[0]->id_starting_lineup . ' AND (id_player = ' . $checkIfRecordExist[0]->player1 . ' OR id_player = ' . $checkIfRecordExist[0]->player2 . ' OR id_player = ' . $checkIfRecordExist[0]->player3 . ' OR id_player = ' . $checkIfRecordExist[0]->player4 . ' OR id_player = ' . $checkIfRecordExist[0]->player5 . ')');

                            //         //remove players from megaliga_scores
                            //         foreach ($checkIfPlayerScoreExistQuery as $playerToRemove) {
                            //             $wpdb->delete('megaliga_scores', array('id_scores' => $playerToRemove->id_scores));
                            //         }

                            //         //clear schedule score
                            //         $getIdScheduleQuery = $wpdb->get_results('SELECT id_schedule FROM megaliga_schedule WHERE round_number = ' . $round_number . ' AND (id_user_team1 = ' . $_POST['userId'] . ' OR id_user_team2 = ' . $_POST['userId'] . ')');

                            //         $removeScoreWhere = array('id_schedule' => $getIdScheduleQuery[0]->id_schedule);
                            //         $wpdb->update('megaliga_schedule', array('team1_score' => null, 'team2_score' => null), $removeScoreWhere);

                            //         $where = array('id_starting_lineup' => $checkIfRecordExist[0]->id_starting_lineup);
                            //         $wpdb->update('megaliga_starting_lineup', $submitDataArray, $where);
                            //     } else {
                            //         $wpdb->insert('megaliga_starting_lineup', $submitDataArray);
                            //     }
                            // }

                            // //draws roster submission form
                            // function drawRosterForm($queryResult, $userId, $round_number)
                            // {
                            //     global $wpdb;

                            //     //get list of all available players in the team
                            //     $getRosterQuery = $wpdb->get_results('SELECT player_id, ekstraliga_player_name FROM megaliga_players WHERE id_user_' . $queryResult[0]->group_name . ' = ' . $userId);

                            //     //get data about already selected players, setplays
                            //     $getStartingLineupDataQuery = $wpdb->get_results('SELECT player1, player2, player3, player4, player5, setplays FROM megaliga_starting_lineup WHERE round_number = ' . $round_number . ' AND ID = ' . $userId);
                            //     $selectedPlayers = array($getStartingLineupDataQuery[0]->player1, $getStartingLineupDataQuery[0]->player2, $getStartingLineupDataQuery[0]->player3, $getStartingLineupDataQuery[0]->player4, $getStartingLineupDataQuery[0]->player5);

                            //     echo '<form action="" method="post">';
                            //     echo '<div class="rosterContainer teamContainerDimentions">';
                            //     echo '  <div class="teamContainerRow1">';
                            //     echo '      <div class="teamImgContainer">';
                            //     echo '          <img src="' . $queryResult[0]->logo_url . '" width="200px" height="200px">';
                            //     echo '      </div>';
                            //     echo '      <div class="teamOverviewContainerForm">';
                            //     echo '          <div class="teamOverviewRow">';
                            //     echo '              <span class="teamOverviewLabel">drużyna:</span>';
                            //     echo '              <span class="teamOverviewTeamName">' . $queryResult[0]->team_name . '</span>';
                            //     echo '          </div>';
                            //     echo '          <div class="teamOverviewRow">';
                            //     echo '              <span class="teamOverviewLabel">grupa:</span>';
                            //     echo '              <span class="teamOverviewContent">' . $queryResult[0]->group_name . '</span>';
                            //     echo '          </div>';
                            //     echo '          <div class="teamOverviewRow">';
                            //     echo '              <span class="teamOverviewLabel">trener:</span>';
                            //     echo '              <span class="teamOverviewContent">' . $queryResult[0]->user_login . '</span>';
                            //     echo '          </div>';
                            //     echo '      </div>';
                            //     echo '      <div class="teamRosterContainerForm">';
                            //     echo '          <span class="teamOverviewRosterLabel">wybierz skład (dokładnie 5 zawodników):</span>';
                            //     echo '              <ul class="noDecoration">';
                            //     echo '                  <li>';
                            //     echo '                      <span class="teamOverviewRosterTeamName">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;nr startowy&nbsp;&nbsp; zawodnik</span>';
                            //     echo '                  </li>';
                            // 
                            ?>
                            // <script>
                                //         function clearSpinner(spinnerId, checkboxId) {
                                //             if (!document.getElementById(checkboxId).checked) {
                                //                 document.getElementById(spinnerId).value = '';
                                //             }
                                //         }

                                //         function handleNumberInputOnChange(spinnerId, checkboxId) {
                                //             var spinner = document.getElementById(spinnerId);
                                //             document.getElementById(checkboxId).checked = spinner.value > 0;
                                //             if (spinner.value === '0') {
                                //                 spinner.value = '';
                                //             }
                                //         }
                                //     
                            </script>
                            // <?php
                                //     foreach ($getRosterQuery as $rosterField) {
                                //         $checked = in_array($rosterField->player_id, $selectedPlayers) ? 'checked' : '';
                                //         //if $selectedPlayers array contains player's id -> set the starting order number for this player which is the same as the position on whiich it is stored in $selectedPlayers array
                                //         $key = array_search($rosterField->player_id, $selectedPlayers);
                                //         $startingOrder = $key || $key === 0 ? $key + 1 : '';

                                //         echo '              <li>
                                //     <input class="pointer teamRosterCheckbox" type="checkbox" id="player' . $rosterField->player_id . '" ' . $checked . ' name="player' . $rosterField->player_id . '" value="' . $rosterField->player_id . '" onchange="clearSpinner(\'startingNumber' . $rosterField->player_id . '\', \'player' . $rosterField->player_id . '\')">
                                //     <input type="number" class="spinner" name="startingNumber' . $rosterField->player_id . '" id="startingNumber' . $rosterField->player_id . '" onchange="handleNumberInputOnChange(\'startingNumber' . $rosterField->player_id . '\', \'player' . $rosterField->player_id . '\')" min="0" max="5" value="' . $startingOrder . '">
                                //     <label for="player' . $rosterField->player_id . '" class="pointer teamOverviewRosterPlayerName">' . $rosterField->ekstraliga_player_name . '</label>
                                // </li>';
                                //     }

                                //     echo '              <ul>';
                                //     echo '      </div>';
                                //     echo '  </div>';
                                //     echo '  <div class="setplayContainer">';
                                //     echo '      <span class="teamOverviewRosterLabel">wybrane zagrywki:</span>';
                                //     echo '      <input name="setplayInput" type="text" maxlength="200" class="setplayInput" value="' . $getStartingLineupDataQuery[0]->setplays . '">';
                                //     echo '  </div>';
                                //     echo '  <div>';
                                //     echo '      <input type="submit" name="submitStartingLineup" value="Zatwierdź skład">';
                                //     echo '      <input type="hidden" name="userId" value="' . $userId . '">';
                                //     echo '  </div>';
                                //     echo '</div>';
                                //     echo '</form>';
                                // }

                                // function drawToggleFormStatusButton($isFormEnabled)
                                // {
                                //     $buttonTitle = $isFormEnabled[0]->is_open ? 'Zablokuj wybór składów' : 'Odblokuj wybór składów';

                                //     echo '<form action="" method="post">';
                                //     echo '  <div class="marginLeft1em marginBottom20">';
                                //     echo '      <input type="submit" name="submitFormStatus" value="' . $buttonTitle . '">';
                                //     echo '      <input type="hidden" name="is_open" value="' . !$isFormEnabled[0]->is_open . '">';
                                //     echo '  </div>';
                                //     echo '</form>';
                                // }

                                // function drawEmergencyTeamSelectionForm($selectedValue)
                                // {
                                //     global $wpdb;
                                //     $getTeams = $wpdb->get_results('SELECT megaliga_team_names.name, megaliga_user_data.ID FROM megaliga_team_names, megaliga_user_data WHERE megaliga_team_names.team_names_id = megaliga_user_data.team_names_id');

                                //     echo '<form action="" method="post">';
                                //     echo '<div class="rosterContainer teamContainerDimentions">';
                                //     echo '  <div>';
                                //     echo '      <span class="emergencyTeamSelectionTitle">Formularz awaryjnego przydziału składu</span>';
                                //     echo '  </div>';
                                //     echo '  <div class="displayFlex flexDirectionColumn marginTop20">';
                                //     echo '      <div><span class="teamOverviewLabel">Drużyna:</span></div>';
                                //     echo '            <select class="teamSelect" name="team" id="selectTeam">';
                                //     foreach ($getTeams as $option) {
                                //         if ($selectedValue == $option->ID) {
                                //             echo '            <option selected value="' . $option->ID . '">' . $option->name . '</option>';
                                //         } else {
                                //             echo '            <option value="' . $option->ID . '">' . $option->name . '</option>';
                                //         }
                                //     }
                                //     echo '            </select>';
                                //     echo '  </div>';
                                //     echo '  <div class="submitEmergencyTeamSelectionContainer">';
                                //     echo '      <input type="submit" name="submitEmergencyTeamSelection" value="Wybierz">';
                                //     echo '  </div>';
                                //     echo '</div>';
                                //     echo '</form>';
                                // }

                                // //draws rosters for all teams for given ligue group
                                // function drawRosters($queryResult, $round_number)
                                // {
                                //     foreach ($queryResult as $field) {
                                //         global $wpdb;

                                //         //get list of all available players in the team
                                //         $getRosterQuery = $wpdb->get_results('SELECT player_id, ekstraliga_player_name FROM megaliga_players WHERE id_user_' . $field->group_name . ' = ' . $field->ID);

                                //         //get data about already selected players, setplays
                                //         $getStartingLineupDataQuery = $wpdb->get_results('SELECT player1, player2, player3, player4, player5, setplays FROM megaliga_starting_lineup WHERE round_number = ' . $round_number . ' AND ID = ' . $field->ID);
                                //         $selectedPlayers = array($getStartingLineupDataQuery[0]->player1, $getStartingLineupDataQuery[0]->player2, $getStartingLineupDataQuery[0]->player3, $getStartingLineupDataQuery[0]->player4, $getStartingLineupDataQuery[0]->player5);

                                //         echo '<div class="rosterContainer teamContainerDimentions">';
                                //         echo '  <div class="teamContainerRow1">';
                                //         echo '      <div class="teamImgContainer">';
                                //         echo '          <img src="' . $field->logo_url . '" width="200px" height="200px">';
                                //         echo '      </div>';
                                //         echo '      <div class="teamOverviewContainer">';
                                //         echo '          <div class="teamOverviewRow">';
                                //         echo '              <span class="teamOverviewLabel">drużyna:</span>';
                                //         echo '              <span class="teamOverviewTeamName">' . $field->team_name . '</span>';
                                //         echo '          </div>';
                                //         echo '          <div class="teamOverviewRow">';
                                //         echo '              <span class="teamOverviewLabel">grupa:</span>';
                                //         echo '              <span class="teamOverviewContent">' . $field->group_name . '</span>';
                                //         echo '          </div>';
                                //         echo '          <div class="teamOverviewRow">';
                                //         echo '              <span class="teamOverviewLabel">trener:</span>';
                                //         echo '              <span class="teamOverviewContent">' . $field->user_login . '</span>';
                                //         echo '          </div>';
                                //         echo '          <div class="teamOverviewRow">';
                                //         echo '              <span class="teamOverviewLabel setplay">wybrane zagrywki:</span>';
                                //         echo '              <span class="teamOverviewContent">' . $getStartingLineupDataQuery[0]->setplays . '</span>';
                                //         echo '          </div>';
                                //         echo '      </div>';
                                //         echo '      <div class="teamRosterContainer">';
                                //         echo '          <span class="teamOverviewRosterLabel">skład:</span>';
                                //         echo '              <ul>';
                                //         echo '                  <li>';
                                //         echo '                      <span class="teamOverviewRosterTeamName">nr startowy&nbsp;&nbsp; zawodnik</span>';
                                //         echo '                  </li>';

                                //         //get from roster players, that are selected for given round
                                //         $array2sort = array();
                                //         foreach ($getRosterQuery as $rosterField) {
                                //             //if $selectedPlayers array contains player's id -> set the starting order number for this player which is the same as the position on whiich it is stored in $selectedPlayers array
                                //             $key = array_search($rosterField->player_id, $selectedPlayers);
                                //             if ($key || $key === 0) {
                                //                 $key++;
                                //             }
                                //             if ($key > 0) {
                                //                 $array2sort[$key] = $rosterField;
                                //             }
                                //         }

                                //         //sort array with selected players by starting order (which reflects the key of the array (1,2,3,4,5))
                                //         $keys = array_keys($array2sort);
                                //         sort($keys);
                                //         if (count($array2sort)) {
                                //             foreach ($keys as $key) {
                                //                 echo '          <li>
                                //         <span class="teamOverviewRosterPlayerName">' . $key . '</span>
                                //         <span class="teamOverviewRosterPlayerName">' . $array2sort[$key]->ekstraliga_player_name . '</span>
                                //     </li>';
                                //             }
                                //         } else {
                                //             echo '              <li>
                                //             <span class="teamOverviewRosterLabel">Skład nie został wybrany.</span>
                                //     </li>';
                                //         }

                                //         echo '              <ul>';
                                //         echo '      </div>';
                                //         echo '  </div>';
                                //         echo '</div>';
                                //     }
                                // }

                                // if ($_POST['submitEmergencyTeamSelection']) {
                                //     $getRosterSubmissionFormDataQuery = $wpdb->get_results('SELECT wp_users.user_login, megaliga_team_names.name as "team_name", megaliga_user_data.logo_url, megaliga_ligue_groups.name as "group_name" FROM megaliga_user_data, wp_users, megaliga_team_names, megaliga_ligue_groups WHERE megaliga_user_data.ID = wp_users.ID AND megaliga_user_data.ID = ' . $_POST['team'] . ' AND megaliga_user_data.team_names_id = megaliga_team_names.team_names_id AND megaliga_ligue_groups.ligue_groups_id = megaliga_user_data.ligue_groups_id');

                                //     echo '<div class="rosterContainer teamContainerDimentions">';
                                //     drawEmergencyTeamSelectionForm($_POST['team']);
                                //     drawRosterForm($getRosterSubmissionFormDataQuery, $_POST['team'], $round_number);
                                //     echo '</div>';
                                // }

                                // //content of the roster page
                                // echo '<div>';

                                // if (!$isFormEnabled[0]->is_open) {
                                //     echo '<div class="marginLeft1em marginBottom20">';
                                //     echo '<span class="scoreTableName">Wybór składów w tej rundzie został zakończony.</Span>';
                                //     echo '</div>';
                                // }

                                // //draw emergencyTeamSelection form if user is admin and team has not already been chosen
                                // if ($userId == 14 || $userId == 58) {
                                //     drawToggleFormStatusButton($isFormEnabled);
                                //     //draw emergencyTeamSelection form if user is admin and team has not already been chosen
                                //     if (!isset($_POST['submitEmergencyTeamSelection'])) {
                                //         drawEmergencyTeamSelectionForm('');
                                //     }
                                // }


                                // if ($showRosterForm) {
                                //     $getRosterSubmissionFormDataQuery = $wpdb->get_results('SELECT wp_users.user_login, megaliga_team_names.name as "team_name", megaliga_user_data.logo_url, megaliga_ligue_groups.name as "group_name" FROM megaliga_user_data, wp_users, megaliga_team_names, megaliga_ligue_groups WHERE megaliga_user_data.ID = wp_users.ID AND megaliga_user_data.ID = ' . $userId . ' AND megaliga_user_data.team_names_id = megaliga_team_names.team_names_id AND megaliga_ligue_groups.ligue_groups_id = megaliga_user_data.ligue_groups_id');
                                //     drawRosterForm($getRosterSubmissionFormDataQuery, $userId, $round_number);
                                // }

                                // //get roster for all teams
                                // $getRosterForRound = $wpdb->get_results('SELECT wp_users.user_login, megaliga_team_names.name as "team_name", megaliga_user_data.logo_url, megaliga_user_data.ID, megaliga_ligue_groups.name as "group_name" FROM megaliga_user_data, wp_users, megaliga_team_names, megaliga_ligue_groups WHERE megaliga_user_data.ID = wp_users.ID AND megaliga_user_data.team_names_id = megaliga_team_names.team_names_id AND megaliga_ligue_groups.ligue_groups_id = megaliga_user_data.ligue_groups_id ORDER BY megaliga_ligue_groups.name');

                                // drawRosters($getRosterForRound, $round_number);

                                // echo '</div>';
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
    <!-- <script type="text/javascript">
        (function() {
            var title = document.querySelector('#primary > div.container > div > div > h1');
            title.innerHTML = 'składy - ' + title.innerHTML;
        })();
    </script> -->
    <?php get_footer(); ?>
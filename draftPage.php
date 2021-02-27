<?php
/*
Template Name: Draft
Description: Shows draft form for regular season for two groups in the ligue
 */
?>
<?php get_header(); ?>
<main id="content">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="header">
                    <h1 class="entry-title">
                        <?php the_title('draft: '); ?>
                    </h1>
                    <?php edit_post_link(); ?>
                </header>
                <div class="entry-content">
                    <?php if (has_post_thumbnail()) {
                        the_post_thumbnail();
                    } ?>

                    <?php
                    global $wpdb;
                    $current_user = wp_get_current_user();
                    $userId = $current_user->ID;
                    // $userId = 20; //46; //14;
                    // $userId = 46;
                    //check if draft window is open
                    $getDraftWindowState = $wpdb->get_results('SELECT draft_window_open, draft_credit_enabled, draft_round1_order_lottery_open FROM megaliga_draft_data');

                    //function sets next draft round - needed to know for who draftForm will be available
                    function setNextDraftRound($userId)
                    {
                        global $wpdb;
                        $getGroupName = $wpdb->get_results('SELECT megaliga_ligue_groups.name FROM megaliga_ligue_groups, megaliga_user_data WHERE megaliga_user_data.ID = ' . $userId . ' AND megaliga_user_data.ligue_groups_id = megaliga_ligue_groups.ligue_groups_id');
                        $getDraftCurrentRound = $wpdb->get_results('SELECT draft_current_round_' . $getGroupName[0]->name . ' as "draft_current_round" FROM megaliga_draft_data');
                        $getDraftTurnUserId = $wpdb->get_results('SELECT ID FROM megaliga_season_draft_order_' . $getGroupName[0]->name . ' WHERE id_season_draft_order = ' . $getDraftCurrentRound[0]->draft_current_round);

                        //prevention against form resubmission - do not increment current round number if id of logged user differs from id of user which draft round is now
                        if ($getDraftTurnUserId[0]->ID != $userId) {
                            return;
                        }

                        //check how many rounds are defined in db.
                        $getNumberOfRounds = $wpdb->get_results('SELECT COUNT(*) as "round_number" FROM megaliga_season_draft_order_' . $getGroupName[0]->name);

                        //if current round == number of defined rounds -> start from 1st round else increment round number
                        $nextRoundNumber = $getDraftCurrentRound[0]->draft_current_round == $getNumberOfRounds[0]->round_number ? 1 : $getDraftCurrentRound[0]->draft_current_round + 1;

                        $updateCurrentRound = array();
                        $updateCurrentRound['draft_current_round_' . $getGroupName[0]->name] = $nextRoundNumber;
                        $whereCurrentRound = array('id_draft' => 1);
                        $wpdb->update('megaliga_draft_data', $updateCurrentRound, $whereCurrentRound);
                    }

                    //handle submission (player drafted)
                    if ($_POST['submitDraft']) {
                        $getGroupName = $wpdb->get_results('SELECT megaliga_ligue_groups.name FROM megaliga_ligue_groups, megaliga_user_data WHERE megaliga_user_data.ID = ' . $userId . ' AND megaliga_user_data.ligue_groups_id = megaliga_ligue_groups.ligue_groups_id');
                        //check if system tries to subimt same data again
                        $getCheckIfUserAlreadyChosenPlayer = $wpdb->get_results('SELECT id_user_' . $getGroupName[0]->name . ' as "id_user" FROM megaliga_players WHERE player_id = ' . $_POST['draftPlayer']);

                        //prevention against form resubmission - update only those players who has no user assigned.
                        if (!$getCheckIfUserAlreadyChosenPlayer[0]->id_user) {
                            //number with which player is drafted is important in the game -> get it for given user and assign to drafted player. It will help to sort team's roster from the first drafted player to the last
                            $getNumberWithWhichPlayerIsDrafted = $wpdb->get_results('SELECT player_draft_number FROM megaliga_user_data WHERE ID = ' . $userId);

                            //update megaliga_players table
                            $data = array();
                            $data['id_user_' . $getGroupName[0]->name] = $userId;
                            $data['drafted_with_number_' . $getGroupName[0]->name] = $getNumberWithWhichPlayerIsDrafted[0]->player_draft_number;
                            $where = array('player_id' => $_POST['draftPlayer']);
                            $wpdb->update('megaliga_players', $data, $where);

                            //increment number with which player in next round will be drafted will be drafted
                            $player_next_draft_number = $getNumberWithWhichPlayerIsDrafted[0]->player_draft_number + 1;

                            //update number with which player in next round will be drafted for given user
                            $data = array(
                                'player_draft_number' => $player_next_draft_number
                            );
                            $where = array('ID' => $userId);
                            $wpdb->update('megaliga_user_data', $data, $where);

                            //calculate credit data if draft credit mode is on
                            if ($getDraftWindowState[0]->draft_credit_enabled) {
                                //get team's left credit to calculate new one
                                $getTeamCreditBalance = $wpdb->get_results('SELECT credit_balance FROM megaliga_user_data WHERE megaliga_user_data.ID = ' . $userId);

                                $getDraftedPlayerCredit = $wpdb->get_results('SELECT credit FROM megaliga_players WHERE player_id = ' . $_POST['draftPlayer']);

                                //calculate new Credit Balance of the team
                                $newTeamCreditBalance = $getTeamCreditBalance[0]->credit_balance - $getDraftedPlayerCredit[0]->credit;

                                //prevents from setting the negative value of given team  credit balance
                                if ($newTeamCreditBalance < 0) {
                                    $newTeamCreditBalance = 0;
                                }

                                //update megaliga_user_data
                                $updateCreditData = array(
                                    'credit_balance' => $newTeamCreditBalance
                                );
                                $whereUpdateCredit = array('ID' => $userId);
                                $wpdb->update('megaliga_user_data', $updateCreditData, $whereUpdateCredit);
                            }
                            setNextDraftRound($userId);
                        }
                    }

                    //handle submission (user decide to leave his turn)
                    if ($_POST['passDraft']) {
                        setNextDraftRound($userId);
                    }

                    if ($_POST['submitDraftLotteryOrderDraw']) {
                        //check if user has already draw draft order; prevent from redrawing draft order when F5 or reload of page is done
                        $getUserData = $wpdb->get_results('SELECT is_draw_round1_draft_order, ligue_groups_id FROM megaliga_user_data WHERE ID = ' . $userId);
                        $getUserName = $wpdb->get_results('SELECT user_login FROM wp_users WHERE wp_users.ID = ' . $userId);

                        if (!$getUserData[0]->is_draw_round1_draft_order) {
                            $score = random_int(1, 100);
                            //translating score to draft lottery draw order
                            //get left number of order position to draw
                            $getOrderPositionToDraw = $wpdb->get_results('SELECT position_name FROM megaliga_draft_order WHERE is_selected = 0 AND ligue_groups_id = ' . $getUserData[0]->ligue_groups_id);

                            $numberOfOrderPositionToDraw = count($getOrderPositionToDraw);

                            $drawPosition = 0;
                            $stageNumber = 1;
                            //max score to achive - is lowering with each stage
                            $availablePoints = 100;
                            // echo 'score: ' . $score . '</br>';
                            foreach ($getOrderPositionToDraw as $orderPosition) {
                                // echo '$stageNumber: ' . $stageNumber . '</br>';
                                // echo '$availablePoints: ' . $availablePoints . '</br>';
                                $stageLimit = 100 - ((100 / $numberOfOrderPositionToDraw) * $stageNumber);
                                // echo '$stageLimit: ' . $stageLimit . ' $numberOfOrderPositionToDraw: ' . $numberOfOrderPositionToDraw . '</br>';
                                if ($score <= $availablePoints && $score > $stageLimit) {
                                    $drawPosition = $orderPosition->position_name;
                                    break;
                                } else {
                                    $availablePoints = $stageLimit;
                                    $stageNumber++;
                                }
                                // echo '</br></br>';
                            }
                            // echo '$drawPosition: ' . $drawPosition;

                            //save ID of user to table to column representing draw position; this table is used to generate table with draft order in all rounds of draft
                            $position2orderMapper = array('1' => 'one', '2' => 'two', '3' => 'three', '4' => 'four', '5' => 'five', '6' => 'six');
                            $updateUserPositionOrder = array();
                            $updateUserPositionOrder[$position2orderMapper[$drawPosition]] = $userId;

                            $where = array('ligue_groups_id' => $getUserData[0]->ligue_groups_id);
                            $wpdb->update('megaliga_1round_draft_order_lottery_outcome', $updateUserPositionOrder, $where);

                            //lock position order draw by the user, so that it want be draw by other users
                            $updateLockPositionOrder = array(
                                'is_selected' => '1'
                            );
                            $where = array('position_name' => $drawPosition, 'ligue_groups_id' => $getUserData[0]->ligue_groups_id);
                            $wpdb->update('megaliga_draft_order', $updateLockPositionOrder, $where);

                            //indicate, that user has already draw draft order, so that draft order form will not be shown again
                            $updateDraftOrderDrawDoneFlag = array(
                                'is_draw_round1_draft_order' => '1'
                            );
                            $where = array('ID' => $userId);
                            $wpdb->update('megaliga_user_data', $updateDraftOrderDrawDoneFlag, $where);

                            $helmetMapper = array('1' => 'biały', '2' => 'fioletowy', '3' => 'niebieski', '4' => 'żółty', '5' => 'czerwony', '6' => 'pomarańczowy', '7' => 'zielony', '8' => 'czarny');
                            $orderMapper = array('1' => 'pierwszy', '2' => 'drugi', '3' => 'trzeci', '4' => 'czwarty', '5' => 'piąty', '6' => 'szósty', '7' => 'siódmy', '8' => 'ósmy');

                            //presentation of draw outcome for user
                            echo '<div class="displayFlex center flexDirectionColumn">';
                            echo '  <div class="marginBottom10">';
                            echo '      <span class="scoreTableName">' . $getUserName[0]->user_login . ', wylosowałeś:</span>';
                            echo '  </div>';
                            echo '  <div class="displayFlex flexDirectionRow center marginBottom10">';
                            echo '      <img src="http://megaliga.eu/wp-content/uploads/2019/03/kask' . $drawPosition . '.png" class="draftHelmet">';
                            echo '      <span class="draftPositionPresentation marginLeft10">' . $drawPosition . '</span>';
                            echo '  </div>';
                            echo '  <div class="marginBottom40">';
                            echo '  <span class="scoreTableName">' . $helmetMapper[$drawPosition] . ' kask. Jesteś ' . $orderMapper[$drawPosition] . ' w kolejności w 1 rundzie draftu</span>';
                            echo '  </div>';
                            echo '</div>';
                        } else {
                            echo '<div class="marginBottom20">';
                            echo '  <span class="left scoreTableName">' . $getUserName[0]->user_login . ', dokonałeś już losowania kolejności wyboru w pierwszej rundzie draftu</span>';
                            echo ' </div>';
                        }
                    }

                    function drawDraftForm($draftWindowState, $userId)
                    {
                        global $wpdb;

                        $getGroupName = $wpdb->get_results('SELECT megaliga_ligue_groups.name FROM megaliga_ligue_groups, megaliga_user_data WHERE megaliga_user_data.ID = ' . $userId . ' AND megaliga_user_data.ligue_groups_id = megaliga_ligue_groups.ligue_groups_id');

                        //draft form will be shown if user taking part in game is logged in and draft window is open and is megaliga member...
                        // echo 'isLoggedIn: ' . is_user_logged_in() . ' </br>draft open: ' . $draftWindowState[0]->draft_window_open . '</br> userId: ' . $userId . '</br>is user megaliga member: ' . count($getGroupName);
                        if (is_user_logged_in() && $draftWindowState[0]->draft_window_open && count($getGroupName)) {
                            //check whose turn for draft is now
                            $getDraftCurrentRound = $wpdb->get_results('SELECT draft_current_round_' . $getGroupName[0]->name . ' as "draft_current_round" FROM megaliga_draft_data');
                            $getDraftTurnUserId = $wpdb->get_results('SELECT ID FROM megaliga_season_draft_order_' . $getGroupName[0]->name . ' WHERE id_season_draft_order = ' . $getDraftCurrentRound[0]->draft_current_round);

                            //...and it is his turn to draft player
                            if ($getDraftTurnUserId[0]->ID == $userId) {
                                //$draftWindowState[0]->draft_credit_enabled - flag indicates if draftCredit mode is on (players in draft has value and user cannot draft player he cannot afford)
                                $getUserData = $wpdb->get_results('SELECT megaliga_user_data.credit_balance, megaliga_team_names.name, wp_users.user_login FROM megaliga_user_data, megaliga_team_names, wp_users WHERE megaliga_user_data.team_names_id = megaliga_team_names.team_names_id AND megaliga_user_data.ID = wp_users.ID AND megaliga_user_data.ID = ' . $userId);

                                if ($draftWindowState[0]->draft_credit_enabled) {
                                    $getPlayersToDraft = $wpdb->get_results('SELECT player_id, ekstraliga_player_name, credit FROM megaliga_players WHERE id_user_' . $getGroupName[0]->name . ' IS NULL AND credit <= ' . $getUserData[0]->credit_balance . ' ORDER BY credit DESC');
                                } else {
                                    $getPlayersToDraft = $wpdb->get_results('SELECT player_id, ekstraliga_player_name FROM megaliga_players WHERE id_user_' . $getGroupName[0]->name . ' IS NULL ORDER BY ekstraliga_player_name');
                                }

                                //create option list for select with players to draft
                                if ($draftWindowState[0]->draft_credit_enabled) {
                                    foreach ($getPlayersToDraft as $player) {
                                        $options[] = array('label' => $player->ekstraliga_player_name . ' (' . $player->credit . ')', 'value' => $player->player_id);
                                    }
                                } else {
                                    foreach ($getPlayersToDraft as $player) {
                                        $options[] = array('label' => $player->ekstraliga_player_name, 'value' => $player->player_id);
                                    }
                                }

                                //show draft if there are players to draft
                                if (!count($options)) {
                                    return;
                                }

                                $getAlreadyDrafterPlayers = $wpdb->get_results('SELECT ekstraliga_player_name, credit, drafted_with_number_' . $getGroupName[0]->name . ' FROM megaliga_players WHERE id_user_' . $getGroupName[0]->name . ' = ' . $userId . ' ORDER BY drafted_with_number_' . $getGroupName[0]->name . ' ASC');

                                if ($draftWindowState[0]->draft_credit_enabled) {
                                    $creditBalanceCss = $getUserData[0]->credit_balance > 0 ? 'draftCreditBalanceOk' : 'teamOverviewContent';
                                }

                                // $getSeasonName = $wpdb->get_results('SELECT season_name FROM megaliga_season WHERE current = 1');
                                echo '<div class="draftFormContainer">';
                                echo '  <div class="displayFlex flexDirectionColumn draftCol1">';
                                echo '      <div class="displayFlex flexDirectionRow">';
                                echo '          <span class="left scoreTableName marginRight5 marginBottom5">Draft do zespołu:</span><span class="teamOverviewContent marginBottom5">' . $getUserData[0]->name . '</span>';
                                echo '      </div>';
                                echo '      <div class="displayFlex flexDirectionRow">';

                                if ($draftWindowState[0]->draft_credit_enabled) {
                                    echo '          <span class="left scoreTableName marginRight5 marginBottom5">Pozostały kredyt:</span><span class="' . $creditBalanceCss . ' marginBottom5">' . $getUserData[0]->credit_balance . '</span>';
                                }

                                echo '      </div>';
                                echo '      <div class="displayFlex flexDirectionColumn">';
                                echo '          <span class="left scoreTableName marginRight5 marginBottom5">Dotychczas wybrani zawodnicy:</span>';

                                $i = 1;
                                if ($draftWindowState[0]->draft_credit_enabled) {
                                    foreach ($getAlreadyDrafterPlayers as $player) {
                                        echo '<span class="fontSize12">' . $i . '. ' . $player->ekstraliga_player_name . ' (' . $player->credit . ')</span>';
                                        $i++;
                                    }
                                } else {
                                    foreach ($getAlreadyDrafterPlayers as $player) {
                                        echo '<span class="fontSize12">' . $i . '. ' . $player->ekstraliga_player_name . '</span>';
                                        $i++;
                                    }
                                }

                                echo '      </div>';
                                echo '  </div>';
                                echo '  <div class="draftCol2">';
                                echo '      <form action="" method="post">';
                                echo '          <div class="displayFlex flexDirectionColumn">';
                                echo '              <span class="left scoreTableName marginBottom5">' . $getUserData[0]->user_login . ' wybierz zawodnika w tej turze draftu: </span>';
                                echo '              <select class="draftPlayer" name="draftPlayer" id="draftPlayer">';
                                foreach ($options as $option) {
                                    echo '              <option value="' . $option['value'] . '">' . $option['label'] . '</option>';
                                }
                                echo '              </select>';
                                echo '              <div class="flexDirectionRow">';
                                echo '                  <input class="submitDraftPlayer" type="submit" name="submitDraft" value="Wybierz">';
                                echo '                  <input class="submitDraftPlayer" type="submit" name="passDraft" value="Pas">';
                                echo '              </div>';
                                echo '          </div>';
                                echo '      </form>';
                                echo '  </div>';
                                echo '</div>';
                            }
                        }
                    }

                    function drawDraftLotteryRound1Form($draftWindowState, $userId)
                    {
                        global $wpdb;

                        $getIfUserAlreadyDrawDfratLotteryOrder = $wpdb->get_results('SELECT is_draw_round1_draft_order FROM megaliga_user_data WHERE ID = ' . $userId);

                        //show form if user logged in and round 1 draft lottery is open and user is megaliga member
                        if (is_user_logged_in() && $draftWindowState[0]->draft_round1_order_lottery_open && count($getIfUserAlreadyDrawDfratLotteryOrder)) {

                            //if previous condition met, check if user has not already draw draft order
                            if (!$getIfUserAlreadyDrawDfratLotteryOrder[0]->is_draw_round1_draft_order) {
                                $getUserName = $wpdb->get_results('SELECT user_login FROM wp_users WHERE wp_users.ID = ' . $userId);

                                echo '<div class="draftFormContainer">';
                                echo '  <form action="" method="post">';
                                echo '      <div class="displayFlex flexDirectionColumn">';
                                echo '          <span class="left scoreTableName marginBottom5">' . $getUserName[0]->user_login . ', naciśnij guzik "Losuj" aby wylosować kolejność wyboru w pierwszej rundzie draftu: </span>';
                                echo '          <input class="submitDraftPlayer" type="submit" name="submitDraftLotteryOrderDraw" value="Losuj">';
                                echo '          </div>';
                                echo '  </form>';
                                echo '</div>';
                            }
                        }
                    }

                    drawDraftLotteryRound1Form($getDraftWindowState, $userId);
                    //content of the draft page
                    drawDraftForm($getDraftWindowState, $userId);
                    the_content();
                    ?>

                    <div class="entry-links">
                        <?php wp_link_pages(); ?>
                    </div>
                </div>
            </article>
            <?php if (!post_password_required()) comments_template('', true); ?>
    <?php endwhile;
    endif; ?>
</main>
<?php get_sidebar(); ?>
<?php get_footer(); ?>
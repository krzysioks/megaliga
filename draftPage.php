<?php
 /*
Template Name: Draft ver 2.0
Description: Shows draft for regular season for one group in the ligue
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

            //check if draft window is open
            $getDraftWindowState = $wpdb->get_results('SELECT draft_window_open, draft_credit_enabled, draft_round1_order_lottery_open FROM megaliga_draft_data');

            //handle submission
            if ($_POST['submitDraft']) {
                //check if system tries to subimt same data again
                $getCheckIfUserAlreadyChosenPlayer = $wpdb->get_results('SELECT id_user FROM megaliga_players WHERE player_id = ' . $_POST['draftPlayer']);

                //prevention against form resubmission - update only those players who has no user assigned.
                if (!$getCheckIfUserAlreadyChosenPlayer[0]->id_user) {
                    //update megaliga_players table
                    $data = array(
                        'id_user' => $userId
                    );
                    $where = array('player_id' => $_POST['draftPlayer']);
                    $wpdb->update('megaliga_players', $data, $where);

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
                }
            }

            if ($_POST['submitDraftLotteryOrderDraw']) {
                //check if user has already draw draft order; prevent from redrawing draft order when F5 or reload of page is done
                $getIfUserAlreadyDrawDfratLotteryOrder = $wpdb->get_results('SELECT is_draw_round1_draft_order FROM megaliga_user_data WHERE ID = ' . $userId);
                $getUserName = $wpdb->get_results('SELECT user_login FROM wp_users WHERE wp_users.ID = ' . $userId);

                if (!$getIfUserAlreadyDrawDfratLotteryOrder[0]->is_draw_round1_draft_order) {
                    //calculate name of previous season based on current season substructed by 1.
                    $getCurrentSeasonName = $wpdb->get_results('SELECT season_name FROM megaliga_season WHERE current = 1');

                    $previousSeason = $getCurrentSeasonName[0]->season_name - 1;

                    $getLastYearPosition = $wpdb->get_results('SELECT one, two, four, five, six, seven, eight FROM megaliga_seasons_standings_temp WHERE season = ' . $previousSeason);

                    $keys = array('one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight');
                    $key2valueMapper = array('one' => '1', 'two' => '2', 'three' => '3', 'four' => '4', 'five' => '5', 'six' => '6', 'seven' => '7', 'eight' => '8');

                    $pos = 0;
                    $i = 0;
                    foreach ($getLastYearPosition[0] as $id) {
                        if ($id == $userId) {
                            $pos = $key2valueMapper[$keys[$i]];
                        }
                        $i++;
                    }

                    //calculate points for previous season position. The highier pos the lower points
                    $previousSeasonScorePart = $pos * 12.5;
                    $randomScorePart = mt_rand(0, 100);

                    $score = 0.2 * $previousSeasonScorePart + 0.8 * $randomScorePart;

                    //translating score to draft lottery draw order
                    //get left number of order position to draw
                    $getOrderPositionToDraw = $wpdb->get_results('SELECT position_name FROM megaliga_draft_order WHERE is_selected = 0');

                    $numberOfOrderPositionToDraw = count($getOrderPositionToDraw);

                    $drawPosition = 0;
                    $stageNumber = 1;
                    //max score to achive - is lowering with each stage
                    $availablePoints = 100;
                    // echo 'score: ' . $score;
                    foreach ($getOrderPositionToDraw as $orderPosition) {
                        // echo '$stageNumber: ' . $stageNumber . '</br>';
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

                    //save user name to table to column representing position he draw; this table is used to present the outcome of draw lottery
                    $position2orderMapper = array('1' => 'one', '2' => 'two', '3' => 'three', '4' => 'four', '5' => 'five', '6' => 'six', '7' => 'seven', '8' => 'eight');
                    $updateUserPositionOrder = array();
                    $updateUserPositionOrder[$position2orderMapper[$drawPosition]] = $getUserName[0]->user_login;

                    $where = array('id_draft_order_lottery_outcome' => '1');
                    $wpdb->update('megaliga_1round_draft_order_lottery_outcome', $updateUserPositionOrder, $where);

                    //lock position order draw by the user, so that it want be draw by other users
                    $updateLockPositionOrder = array(
                        'is_selected' => '1'
                    );
                    $where = array('position_name' => $drawPosition, 'round_number' => '1');
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

                //draft form will be shown if user taking part in game is logged in and draft window is open
                if (is_user_logged_in() && $draftWindowState[0]->draft_window_open) {
                    //$draftWindowState[0]->draft_credit_enabled - flag indicates if draftCredit mode is on (players in draft has value and user cannot draft player he cannot afford)

                    $getUserData = $wpdb->get_results('SELECT megaliga_user_data.credit_balance, megaliga_team_names.name, wp_users.user_login FROM megaliga_user_data, megaliga_team_names, wp_users WHERE megaliga_user_data.team_names_id = megaliga_team_names.team_names_id AND megaliga_user_data.ID = wp_users.ID AND megaliga_user_data.ID = ' . $userId);

                    if ($draftWindowState[0]->draft_credit_enabled) {
                        $getPlayersToDraft = $wpdb->get_results('SELECT player_id, ekstraliga_player_name, credit FROM megaliga_players WHERE id_user IS NULL AND credit <= ' . $getUserData[0]->credit_balance . ' ORDER BY credit DESC');
                    } else {
                        $getPlayersToDraft = $wpdb->get_results('SELECT player_id, ekstraliga_player_name FROM megaliga_players WHERE id_user IS NULL');
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

                    $getAlreadyDrafterPlayers = $wpdb->get_results('SELECT ekstraliga_player_name, credit FROM megaliga_players WHERE id_user = ' . $userId . ' ORDER BY credit DESC');

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
                    echo '              <input class="submitDraftPlayer" type="submit" name="submitDraft" value="Wybierz">';
                    echo '          </div>';
                    echo '      </form>';
                    echo '  </div>';
                    echo '</div>';
                }
            }

            function drawDraftLotteryRound1Form($draftWindowState, $userId)
            {
                global $wpdb;

                //show form if user logged in and round 1 draft lottery is open
                if (is_user_logged_in() && $draftWindowState[0]->draft_round1_order_lottery_open) {
                    $getIfUserAlreadyDrawDfratLotteryOrder = $wpdb->get_results('SELECT is_draw_round1_draft_order FROM megaliga_user_data WHERE ID = ' . $userId);

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
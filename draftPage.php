<?php
/*
Template Name: Draft ver 3.0
Description: Shows draft form for regular season for one group in the ligue
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
                    $getDraftWindowState = $wpdb->get_results('SELECT draft_window_open, draft_credit_enabled FROM megaliga_draft_data');

                    //function sets next draft round - needed to know for who draftForm will be available
                    function setNextDraftRound($userId)
                    {
                        global $wpdb;

                        $getDraftCurrentRound = $wpdb->get_results('SELECT draft_current_round FROM megaliga_draft_data');
                        $getDraftTurnUserId = $wpdb->get_results('SELECT ID FROM megaliga_season_draft_order WHERE id_season_draft_order = ' . $getDraftCurrentRound[0]->draft_current_round);

                        //prevention against form resubmission - do not increment current round number if id of logged user differs from id of user which draft round is now
                        if ($getDraftTurnUserId[0]->ID != $userId) {
                            return;
                        }

                        //check how many rounds are defined in db.
                        $getNumberOfRounds = $wpdb->get_results('SELECT COUNT(*) as "round_number" FROM megaliga_season_draft_order');

                        //if current round == number of defined rounds -> start from 1st round else increment round number
                        $nextRoundNumber = $getDraftCurrentRound[0]->draft_current_round == $getNumberOfRounds[0]->round_number ? 1 : $getDraftCurrentRound[0]->draft_current_round + 1;

                        //update draft_current_round in megaliga_draft_data
                        $updateCurrentRound = array(
                            'draft_current_round' => $nextRoundNumber
                        );
                        $whereCurrentRound = array('id_draft' => 1);
                        $wpdb->update('megaliga_draft_data', $updateCurrentRound, $whereCurrentRound);
                    }

                    //handle submission (player drafted)
                    if ($_POST['submitDraft']) {
                        //check if system tries to subimt same data again
                        $getCheckIfUserAlreadyChosenPlayer = $wpdb->get_results('SELECT id_user FROM megaliga_players WHERE player_id = ' . $_POST['draftPlayer']);

                        //prevention against form resubmission - update only those players who has no user assigned.
                        if (!$getCheckIfUserAlreadyChosenPlayer[0]->id_user) {
                            //number with which player is drafted is important in the game -> get it for given user and assign to drafted player. It will help to sort team's roster from the first drafted player to the last
                            $getNumberWithWhichPlayerIsDrafted = $wpdb->get_results('SELECT player_draft_number FROM megaliga_user_data WHERE ID = ' . $userId);
                            //update megaliga_players table
                            $data = array(
                                'id_user' => $userId,
                                'drafted_with_number' => $getNumberWithWhichPlayerIsDrafted[0]->player_draft_number
                            );
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

                    function drawDraftForm($draftWindowState, $getDraftTurnUserId, $userId)
                    {
                        global $wpdb;

                        //draft form will be shown if user taking part in game is logged in and draft window is open and it is his turn to draft player
                        // echo 'isLoggedIn: ' . is_user_logged_in() . ' </br>draft open: ' . $draftWindowState[0]->draft_window_open . '</br> draftTurnUserId: ' . $getDraftTurnUserId[0]->ID . '</br> userId: ' . $userId;
                        if (is_user_logged_in() && $draftWindowState[0]->draft_window_open && $getDraftTurnUserId[0]->ID == $userId) {
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

                    //check whose turn for draft is now
                    $getDraftCurrentRound = $wpdb->get_results('SELECT draft_current_round FROM megaliga_draft_data');
                    $getDraftTurnUserId = $wpdb->get_results('SELECT ID FROM megaliga_season_draft_order WHERE id_season_draft_order = ' . $getDraftCurrentRound[0]->draft_current_round);

                    //content of the draft page
                    drawDraftForm($getDraftWindowState, $getDraftTurnUserId, $userId);
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
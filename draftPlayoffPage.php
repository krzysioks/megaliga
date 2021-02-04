<?php
/*
Template Name: Draft Playoff ver 2.0
Description: Shows draft for playoff stage for one group in the ligue
 */
?>
<?php get_header(); ?>
<main id="content">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="header">
                    <h1 class="entry-title"><?php the_title('draft: '); ?></h1> <?php edit_post_link(); ?>
                </header>
                <div class="entry-content">
                    <?php if (has_post_thumbnail()) {
                        the_post_thumbnail();
                    } ?>

                    <?php
                    global $wpdb;
                    $current_user = wp_get_current_user();
                    $userId = $current_user->ID;
                    // $userId = 20; //46 //14;

                    //check if draft window is open
                    $getDraftWindowState = $wpdb->get_results('SELECT playoff_draft_window_open, playoff_draft_credit_enabled FROM megaliga_draft_data');

                    $getCheckIfUserReachedPlayoff = $wpdb->get_results('SELECT reached_playoff FROM megaliga_user_data WHERE ID = ' . $userId);

                    //handle submission
                    if ($_POST['submitDraft']) {
                        //update megaliga_players table
                        $data = array(
                            'id_user_playoff' => $userId
                        );
                        $where = array('player_id' => $_POST['draftPlayer']);
                        $wpdb->update('megaliga_players', $data, $where, array('%d'), array('%d'));

                        //calculate credit data if draft credit mode is on
                        if ($getDraftWindowState[0]->playoff_draft_credit_enabled) {
                            //get team's left credit to calculate new one
                            $getTeamCreditBalance = $wpdb->get_results('SELECT credit_balance_playoff FROM megaliga_user_data WHERE megaliga_user_data.ID = ' . $userId);

                            $getDraftedPlayerCredit = $wpdb->get_results('SELECT credit_playoff FROM megaliga_players WHERE player_id = ' . $_POST['draftPlayer']);

                            //calculate new Credit Balance of the team
                            $newTeamCreditBalance = $getTeamCreditBalance[0]->credit_balance_playoff - $getDraftedPlayerCredit[0]->credit_playoff;

                            //prevents from setting the negative value of given team  credit balance
                            if ($newTeamCreditBalance < 0) {
                                $newTeamCreditBalance = 0;
                            }

                            //update megaliga_user_data
                            $updateCreditData = array(
                                'credit_balance_playoff' => $newTeamCreditBalance
                            );
                            $where = array('ID' => $userId);
                            $wpdb->update('megaliga_user_data', $updateCreditData, $where, array('%d'), array('%d'));
                        }
                    }

                    function drawDraftForm($draftWindowState, $getCheckIfUserReachedPlayoff, $userId)
                    {
                        global $wpdb;

                        //draft form will be shown if user taking part in game is logged in and draft window is open and user reached playoff stage
                        if (is_user_logged_in() && $draftWindowState[0]->playoff_draft_window_open && $getCheckIfUserReachedPlayoff[0]->reached_playoff) {
                            //$draftWindowState[0]->playoff_draft_credit_enabled - flag indicates if draftCredit mode is on (players in draft has value and user cannot draft player he cannot afford)

                            $getUserData = $wpdb->get_results('SELECT megaliga_user_data.credit_balance_playoff, megaliga_team_names.name, wp_users.user_login FROM megaliga_user_data, megaliga_team_names, wp_users WHERE megaliga_user_data.team_names_id = megaliga_team_names.team_names_id AND megaliga_user_data.ID = wp_users.ID AND megaliga_user_data.ID = ' . $userId);

                            if ($draftWindowState[0]->playoff_draft_credit_enabled) {
                                $getPlayersToDraft = $wpdb->get_results('SELECT player_id, ekstraliga_player_name, credit_playoff FROM megaliga_players WHERE id_user_playoff IS NULL AND credit <= ' . $getUserData[0]->credit_balance_playoff . ' ORDER BY credit_playoff DESC');
                            } else {
                                $getPlayersToDraft = $wpdb->get_results('SELECT player_id, ekstraliga_player_name FROM megaliga_players WHERE id_user_playoff IS NULL');
                            }

                            //create option list for select with players to draft
                            if ($draftWindowState[0]->playoff_draft_credit_enabled) {
                                foreach ($getPlayersToDraft as $player) {
                                    $options[] = array('label' => $player->ekstraliga_player_name . ' (' . $player->credit_playoff . ')', 'value' => $player->player_id);
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

                            $getAlreadyDrafterPlayers = $wpdb->get_results('SELECT ekstraliga_player_name, credit_playoff FROM megaliga_players WHERE id_user_playoff = ' . $userId . ' ORDER BY credit_playoff DESC');

                            if ($draftWindowState[0]->playoff_draft_credit_enabled) {
                                $creditBalanceCss = $getUserData[0]->credit_balance_playoff > 0 ? 'draftCreditBalanceOk' : 'teamOverviewContent';
                            }

                            // $getSeasonName = $wpdb->get_results('SELECT season_name FROM megaliga_season WHERE current = 1');
                            echo '<div class="draftFormContainer">';
                            echo '  <div class="displayFlex flexDirectionColumn draftCol1">';
                            echo '      <div class="displayFlex flexDirectionRow">';
                            echo '          <span class="left scoreTableName marginRight5 marginBottom5">Draft do zespołu:</span><span class="teamOverviewContent marginBottom5">' . $getUserData[0]->name . '</span>';
                            echo '      </div>';
                            echo '      <div class="displayFlex flexDirectionRow">';

                            if ($draftWindowState[0]->playoff_draft_credit_enabled) {
                                echo '          <span class="left scoreTableName marginRight5 marginBottom5">Pozostały kredyt:</span><span class="' . $creditBalanceCss . ' marginBottom5">' . $getUserData[0]->credit_balance_playoff . '</span>';
                            }

                            echo '      </div>';
                            echo '      <div class="displayFlex flexDirectionColumn">';
                            echo '          <span class="left scoreTableName marginRight5 marginBottom5">Dotychczas wybrani zawodnicy:</span>';

                            $i = 1;
                            if ($draftWindowState[0]->playoff_draft_credit_enabled) {
                                foreach ($getAlreadyDrafterPlayers as $player) {
                                    echo '<span class="fontSize12">' . $i . '. ' . $player->ekstraliga_player_name . ' (' . $player->credit_playoff . ')</span>';
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
                            echo '              <span class="left scoreTableName marginBottom5">' . $getUserData[0]->user_login . ' wybierz zawodnika w tej turze draftu do fazy play-off: </span>';
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

                    //content of the draft page
                    drawDraftForm($getDraftWindowState, $getCheckIfUserReachedPlayoff, $userId);
                    the_content();
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
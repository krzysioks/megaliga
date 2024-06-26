<?php
/*
Template Name: Grand Prix Bet
Description: Shows Grand Prix's form with list of players for which user is betting the finish position for given round
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
                            //8 - length of "kolejka" string which is in every title of bet subpage
                            $round_number = substr($title, 0, strlen($title) - 8);
                            $userId = $current_user->ID;
                            // $userId = 14;
                            // $userId = 46;

                            if ($_POST['submitBet']) {
                                $betValueList = array();
                                $isEmpty = false;
                                for ($i = 1; $i <= 16; $i++) {
                                    $value =  $_POST['betPosplayer_' . $i];

                                    if (!$value) {
                                        $isEmpty = true;
                                    }

                                    array_push($betValueList, $value);
                                }

                                $uniqueValueList = array_unique($betValueList);

                                //case if form invalidated
                                if ($isEmpty || count($uniqueValueList) != 16) {
                                    echo "<div class='displayFlex marginTop20 marginBottom20 messageError gpEmergencyForm'>";
                                    echo "Nie wytypowałeś wszystkich zawodników lub to samo miejsce podałeś wielokrotnie. Spróbuj jeszcze raz";
                                    echo "</div>";
                                    // case when all data provided correctly
                                } else {
                                    $checkIfRecordsExists = $wpdb->get_results('SELECT id_bet FROM megaliga_grandprix_bets WHERE ID = ' . $_POST['userId'] . ' AND round_number = ' . $round_number);
                                    $submitDataArray = array();
                                    $submitDataArray['round_number'] = $round_number;
                                    $submitDataArray['ID'] = $_POST['userId'];
                                    $i = 1;
                                    foreach ($uniqueValueList as $value) {
                                        $submitDataArray['player_' . $i] = $value;
                                        $i++;
                                    }

                                    if (count($checkIfRecordsExists) > 0) {
                                        $where = array('id_bet' => $checkIfRecordsExists[0]->id_bet);
                                        $wpdb->update('megaliga_grandprix_bets', $submitDataArray, $where);
                                    } else {
                                        $wpdb->insert('megaliga_grandprix_bets', $submitDataArray);
                                    }

                                    echo "<div class='displayFlex marginTop20 marginBottom20 schedulePlayinGeneratorSuccess gpEmergencyForm'>";
                                    echo "Wyniki wytypowane poprawnie";
                                    echo "</div>";
                                }
                            }

                            //handling submision of form's status
                            if ($_POST['submitFormStatus']) {
                                $submitDataArray = array();
                                $submitDataArray['is_open'] = $_POST['is_open'];
                                $where = array('round_number' => $round_number);
                                $wpdb->update('megaliga_grandprix_bet_status', $submitDataArray, $where);
                            }

                            function drawToggleFormStatusButton($isFormEnabled)
                            {
                                $buttonTitle = $isFormEnabled[0]->is_open ? 'Zablokuj typowanie wyników' : 'Odblokuj typowanie wyników';

                                echo '<form action="" method="post">';
                                echo '  <div class="marginBottom20 gpToggleFormStatus">';
                                echo '      <input type="submit" name="submitFormStatus" value="' . $buttonTitle . '">';
                                echo '      <input type="hidden" name="is_open" value="' . !$isFormEnabled[0]->is_open . '">';
                                echo '  </div>';
                                echo '</form>';
                            }

                            function emergencyBetSelectionForm($selectedValue)
                            {
                                global $wpdb;
                                $getTeams = $wpdb->get_results('SELECT wp_users.user_login, megaliga_user_data.ID FROM wp_users, megaliga_user_data WHERE wp_users.ID = megaliga_user_data.ID');

                                echo '<form action="" method="post">';
                                echo '<div class="rosterContainer gpEmergencyForm">';
                                echo '  <div>';
                                echo '      <span class="emergencyTeamSelectionTitle">Formularz awaryjnego typowania</span>';
                                echo '  </div>';
                                echo '  <div class="displayFlex flexDirectionColumn marginTop20">';
                                echo '      <div><span class="teamOverviewLabel">Trener:</span></div>';
                                echo '            <select class="trainerSelect" name="trainer" id="trainerSelect">';
                                foreach ($getTeams as $option) {
                                    if ($selectedValue == $option->ID) {
                                        echo '            <option selected value="' . $option->ID . '">' . $option->user_login . '</option>';
                                    } else {
                                        echo '            <option value="' . $option->ID . '">' . $option->user_login . '</option>';
                                    }
                                }
                                echo '            </select>';
                                echo '  </div>';
                                echo '  <div class="submitEmergencyTeamSelectionContainer">';
                                echo '      <input type="submit" name="submitEmergencyTrainerSelection" value="Wybierz">';
                                echo '  </div>';
                                echo '</div>';
                                echo '</form>';
                            }

                            function drawPlayerBetForm($playersResult, $userId, $isOpen, $round_number)
                            {
                                global $wpdb;

                                //defining if bet form should be on
                                $isUserMegaligaMemberQuery = $wpdb->get_results('SELECT user_data_id FROM megaliga_user_data WHERE ID = ' . $userId);

                                $isForm = $userId != 0 && count($isUserMegaligaMemberQuery) == 1 && $isOpen && is_user_logged_in();

                                if ($isForm) {
                                    echo '<form action="" method="post">';
                                }

                                echo '  <div class="gpPlayersWrapper">';

                                $i = 0;
                                foreach ($playersResult as $player) {
                                    //get position that user bet for given player
                                    $fieldName = $player->field_name;
                                    $getBetPositionQuery = $wpdb->get_results('SELECT ' . $fieldName . ' as "betPosition" FROM megaliga_grandprix_bets WHERE ID=' . $userId . ' AND round_number = ' . $round_number);

                                    $betPosition = count($getBetPositionQuery) > 0 ? $getBetPositionQuery[0]->betPosition : '';

                                    // in case submit form was triggered show values typed in. This is usefull to keep user's selection if form was invalidated
                                    if ($_POST['submitBet']) {
                                        $betPosition = $_POST['betPos' . $fieldName];
                                    }

                                    $playerGridPos = $i % 2 == 0 ? 'gpBetPosLeft' : 'gpBetPosRight';

                                    echo '<div class="gpPlayerWrapper ' . $playerGridPos . '">';
                                    echo '<div class="gpPlayerBorder"></div>';
                                    echo '  <div class="playerImageWrapper">';

                                    if ($player->bio_url) {
                                        echo '    <a href="' . htmlspecialchars($player->bio_url) . '">';
                                    }

                                    echo '      <img src="' . $player->photo_url . '" width="250px" height="162px" />';

                                    if ($player->bio_url) {
                                        echo '    </a>';
                                    }

                                    echo '  </div>';

                                    if ($isForm) {
                                        echo '  <div class="playerBetPositionTitleWrapper">';
                                        echo '    <span class="playerBetPositionLabel">Miejsce:</span>';
                                        echo '  </div>';
                                        echo '  <div class="playerBetPositionWrapper">';
                                        echo '<input type="number" class="spinner gpSpinner" name="betPos' . $fieldName . '" id="betPos' . $fieldName . '" min="1" max="16" value="' . $betPosition . '">';
                                        echo '  </div>';
                                    }

                                    echo '  <div class="playerTitleWrapper">';
                                    if ($player->bio_url) {
                                        echo '    <a class="playerNameLink" href="' . htmlspecialchars($player->bio_url) . '">';
                                    } else {
                                        echo '  <span class="playerNameLink">';
                                    }

                                    echo $player->player_name;

                                    if ($player->bio_url) {
                                        echo '</a>';
                                    } else {
                                        echo '</span>';
                                    }

                                    echo '  </div>';
                                    echo '  <div class="playerNationality">';

                                    if ($player->flag_url) {
                                        echo '      <img class="playerFlagImg" src="' . $player->flag_url . '" width="53px" height="40px" />';
                                    }

                                    echo '  </div>';
                                    echo '</div>';

                                    $i++;
                                }

                                if ($isForm) {
                                    echo '<div class="gpBetSubmitButtonWrapper">';
                                    echo '    <input type="submit" name="submitBet" value="Typuj">';
                                    echo '    <input type="hidden" name="userId" value="' . $userId . '">';
                                    echo '</div>';
                                }

                                echo '  </div>';

                                if ($isForm) {
                                    echo '</form>';
                                }
                            }

                            // player data
                            $getPlayersQuery = $wpdb->get_results('SELECT player_name, photo_url, flag_url, bio_url, field_name FROM megaliga_grandprix_players');

                            //check if bet form is enabled (this option prevent from resubmission of betting player final position in the given round by user after position of players in real Grand Prix for given round has been announced)
                            $isFormEnabled = $wpdb->get_results('SELECT is_open FROM megaliga_grandprix_bet_status WHERE round_number = ' . $round_number);

                            if ($_POST['submitEmergencyTrainerSelection']) {
                                echo '<div class="rosterContainer teamContainerDimentions">';
                                emergencyBetSelectionForm($_POST['trainer']);
                                drawPlayerBetForm($getPlayersQuery, $_POST['trainer'], $isFormEnabled[0]->is_open, $round_number);
                                echo '</div>';
                            }

                            echo "<div>";

                            if (!$isFormEnabled[0]->is_open) {
                                echo '<div class="marginLeft1em marginBottom20">';
                                echo '<span class="scoreTableName">Typowanie wyników w tej rundzie zostało zakończone.</Span>';
                                echo '</div>';
                            }

                            if (($userId == 14 || $userId == 48) && !$_POST['submitEmergencyTrainerSelection']) {
                                drawToggleFormStatusButton($isFormEnabled);
                                //draw emergencyBetSelectionForm form if user is admin and betting has not already been set    
                                if (!isset($_POST['submitEmergencyBetSelection'])) {
                                    emergencyBetSelectionForm('');
                                }
                            }

                            drawPlayerBetForm($getPlayersQuery, $userId, $isFormEnabled[0]->is_open, $round_number);

                            echo "</div>";

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
            title.innerHTML = 'Grand Prix - typuj - ' + title.innerHTML;
        })();
    </script>
    <?php get_footer(); ?>
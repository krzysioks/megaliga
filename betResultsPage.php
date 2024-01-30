<?php
/*
Template Name: Grand Prix Bet Results
Description: Shows Grand Prix's real results for given round. Allowes admin's to submit real life results. Shows all trainers bets after bet for given round is closed.
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
                            // $userId = 20;

                            if ($_POST['submitResults']) {
                                $betValueList = array();
                                $isEmpty = false;
                                for ($i = 1; $i <= 16; $i++) {
                                    $value =  $_POST['posplayer_' . $i];

                                    if (!$value) {
                                        $isEmpty = true;
                                    }

                                    array_push($betValueList, $value);
                                }

                                $uniqueValueList = array_unique($betValueList);

                                //case if form invalidated
                                if ($isEmpty || count($uniqueValueList) != 16) {
                                    echo "<div class='displayFlex marginTop20 marginBottom20 messageError gpEmergencyForm'>";
                                    echo "Nie wpisałeś wyników wszystkich zawodników lub to samo miejsce przypisałeś wielokrotnie. Spróbuj jeszcze raz";
                                    echo "</div>";
                                    // case when all data provided correctly
                                } else {
                                    $checkIfRecordsExists = $wpdb->get_results('SELECT id_result FROM megaliga_grandprix_results WHERE round_number = ' . $round_number);
                                    $submitDataArray = array();
                                    $submitDataArray['round_number'] = $round_number;
                                    $i = 1;
                                    foreach ($uniqueValueList as $value) {
                                        $submitDataArray['player_' . $i] = $value;
                                        $i++;
                                    }

                                    if (count($checkIfRecordsExists) > 0) {
                                        $where = array('id_result' => $checkIfRecordsExists[0]->id_result);
                                        $wpdb->update('megaliga_grandprix_results', $submitDataArray, $where);
                                    } else {
                                        $wpdb->insert('megaliga_grandprix_results', $submitDataArray);
                                    }

                                    echo "<div class='displayFlex marginTop20 marginBottom20 schedulePlayinGeneratorSuccess gpEmergencyForm'>";
                                    echo "Wyniki wprowadzone poprawnie";
                                    echo "</div>";
                                }
                            }

                            function drawPlayerBetForm($playersResult, $userId, $isOpen, $round_number)
                            {
                                global $wpdb;

                                //show form only for user with ID == 14 (mbaginski)
                                $isForm = $userId == 14 && is_user_logged_in();

                                if ($isForm) {
                                    echo '<form action="" method="post">';
                                }

                                echo '  <div class="gpPlayersWrapper">';

                                $i = 0;
                                foreach ($playersResult as $player) {
                                    //get position that user bet for given player
                                    $fieldName = $player->field_name;
                                    $getResultPositionQuery = $wpdb->get_results('SELECT ' . $fieldName . ' as "position" FROM megaliga_grandprix_results WHERE round_number = ' . $round_number);

                                    $position = count($getResultPositionQuery) > 0 ? $getResultPositionQuery[0]->position : '';

                                    // in case submit form was triggered show values typed in. This is usefull to keep user's selection if form was invalidated
                                    if ($_POST['submitResults']) {
                                        $position = $_POST['pos' . $fieldName];
                                    }

                                    $playerGridPos = $i % 2 == 0 ? 'gpBetPosLeft' : 'gpBetPosRight';

                                    echo '<div class="gpPlayerWrapper ' . $playerGridPos . '">';
                                    echo '<div class="gpPlayerBorder"></div>';
                                    echo '  <div class="playerImageWrapper">';
                                    echo '      <img class="playerImage" src="' . $player->photo_url . '" width="280px" height="181px" />';
                                    echo '  </div>';

                                    // if not a form and $isOpen == false -> show bets of other trainers
                                    if (!$isForm && !$isOpen) {
                                        $getTrainersBetResultsQuery = $wpdb->get_results('SELECT megaliga_grandprix_bets.' . $fieldName . ' as "position", wp_users.display_name FROM megaliga_grandprix_bets, wp_users WHERE wp_users.ID = megaliga_grandprix_bets.ID AND round_number = ' . $round_number);

                                        echo '  <div class="trainersBetResultsWrapper">';
                                        echo '  <div class="trainerBetTitle">Typy trenerów:</div>';

                                        $j = 0;
                                        foreach ($getTrainersBetResultsQuery as $trainer) {
                                            $successBetClass =  $trainer->position == $position ? 'class="trainerBetValue trainerBetSuccess"' : '';
                                            $areaClass = $j % 2 == 0 ? 'tCol1' : 'tCol2';
                                            echo '<div class="' . $areaClass . '">';
                                            echo '  <span ' . $successBetClass . '>' . $trainer->display_name . ': ' . $trainer->position . '</span>';
                                            echo '</div>';

                                            $j++;
                                        }

                                        echo '  </div>';
                                    }

                                    if ($isForm) {
                                        echo '  <div class="playerBetPositionTitleWrapper">';
                                        echo '    <span class="playerBetPositionLabel">Miejsce:</span>';
                                        echo '  </div>';
                                        echo '  <div class="playerBetPositionWrapper">';
                                        echo '<input type="number" class="spinner gpSpinner" name="pos' . $fieldName . '" id="pos' . $fieldName . '" min="1" max="16" value="' . $position . '">';
                                        echo '  </div>';
                                    }

                                    echo '  <div class="playerTitleResultsWrapper">';
                                    echo '    <div class="playerNameResults posLeft">';
                                    echo $position;
                                    echo '    </div>';
                                    echo '    <div class="playerNameResults posRight">';
                                    echo $player->player_name;
                                    echo '    </div>';

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
                                    echo '    <input type="submit" name="submitResults" value="Zapisz wyniki kolejki GP">';
                                    echo '</div>';
                                }

                                echo '  </div>';

                                if ($isForm) {
                                    echo '</form>';
                                }
                            }

                            // player data
                            $getPlayersQuery = $wpdb->get_results('SELECT player_name, photo_url, flag_url, field_name FROM megaliga_grandprix_players');

                            //check if bet form for given round is still enabled -> do not show bet results of other trainers
                            $isFormEnabled = $wpdb->get_results('SELECT is_open FROM megaliga_grandprix_bet_status WHERE round_number = ' . $round_number);

                            echo '<div>';
                            echo '<div class="gpResultsTitleWrapper">';
                            echo '<p class="scoreTableName">Wyniki Grand Prix:</p>';
                            echo '</div>';

                            drawPlayerBetForm($getPlayersQuery, $userId, $isFormEnabled[0]->is_open, $round_number);

                            echo '</div>';

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
            title.innerHTML = 'Grand Prix - wyniki - ' + title.innerHTML;
        })();
    </script>
    <?php get_footer(); ?>
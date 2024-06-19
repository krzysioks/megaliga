<?php
/*
Template Name: Grand Prix Standings
Description: Shows standings of Grand Prix
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
                            <div class="<?php echo esc_attr($wrap_class); ?>">
                                <?php
                                do_action('hestia_before_page_content');

                                //custom code starts here
                                global $wpdb;

                                function compareTrainers($a, $b)
                                {
                                    // sort by points
                                    $retval = strnatcmp($b['points'], $a['points']);

                                    // if points are identical, sort number of completed GP's
                                    if (!$retval) {
                                        $retval = (int)$b['gamesPlayed'] - (int)$a['gamesPlayed'];
                                    }

                                    return $retval;
                                }

                                function calculateStandingsData()
                                {
                                    global $wpdb;

                                    $getUserDataQuery = $wpdb->get_results('SELECT wp_users.user_login, megaliga_user_data.ID FROM wp_users, megaliga_user_data WHERE wp_users.ID = megaliga_user_data.ID');

                                    $getGrandPrixResultsQuery = $wpdb->get_results('SELECT * FROM megaliga_grandprix_results');

                                    $standingsData = array();
                                    foreach ($getUserDataQuery as $user) {
                                        $standingsData[$user->ID] = array('trainerName' => $user->user_login, 'ID' => $user->ID, 'gamesPlayed' => 0, 'points' => 0);
                                    }

                                    foreach ($getGrandPrixResultsQuery as $result) {
                                        // DEBUG DATA
                                        // echo '</br>';
                                        // echo '</br>';
                                        // echo 'round: ' . $result->round_number;
                                        // echo '</br>';
                                        // echo '</br>';

                                        $getTrainersBetsQuery = $wpdb->get_results('SELECT * FROM megaliga_grandprix_bets WHERE round_number =' . $result->round_number);

                                        foreach ($getTrainersBetsQuery as $trainerBet) {
                                            // for given trainer add played grand prix to "gamesPlayed"
                                            $standingsData[$trainerBet->ID]['gamesPlayed'] = $standingsData[$trainerBet->ID]['gamesPlayed'] + 1;
                                            // DEBUG DATA
                                            // echo 'trainer id: ' . $trainerBet->ID . ' trainerName: ' . $standingsData[$trainerBet->ID]['trainerName'];
                                            // echo '</br>';
                                            // echo '</br>';

                                            // za dobre obstawienie każdego z miejsc (1-16) jest 1 punkt, za wytypowanie zawodnika w półfinale (miejsca 1-8) jest kolejny punkt (nie musi być dokładne miejsce tylko to, że typowaliśmy że zawodnik znajdzie się w ósemce i rzeczywiście zajął miejsce od 1-8. przykład: typ: 3, zawodnik zajął 7 czyli +1),  za wytypowanie zawodnika w finale (miejsca 1-4) jest kolejny punkt i za dobre obstawienie miejsca 1 jest dodatkowo 1 punkt (czyli razem 4)

                                            $fieldNameList = array('player_1', 'player_2', 'player_3', 'player_4', 'player_5', 'player_6', 'player_7', 'player_8', 'player_9', 'player_10', 'player_11', 'player_12', 'player_13', 'player_14', 'player_15', 'player_16');

                                            for ($i = 0; $i < 16; $i++) {
                                                // DEBUG DATA
                                                // echo 'result: ' . $result->{$fieldNameList[$i]};
                                                // echo '</br>';
                                                // echo 'bet: ' . $trainerBet->{$fieldNameList[$i]};
                                                // echo '</br>';
                                                // echo '</br>';

                                                if ($trainerBet->{$fieldNameList[$i]} == $result->{$fieldNameList[$i]}) {
                                                    // if trainer bet correctly position of given player -> add 1 point
                                                    // echo 'position correct' . $fieldNameList[$i];
                                                    // echo '</br>';
                                                    $standingsData[$trainerBet->ID]['points'] = $standingsData[$trainerBet->ID]['points'] + 1;

                                                    if ($trainerBet->{$fieldNameList[$i]} == 1) {
                                                        // echo 'player won ' . $fieldNameList[$i];
                                                        // echo '</br>';
                                                        //additionally if correctly bet position is exactly 1st place -> add additional 1 point
                                                        $standingsData[$trainerBet->ID]['points'] = $standingsData[$trainerBet->ID]['points'] + 1;
                                                    }
                                                }

                                                //additionally if trainer correctly estimateed position, that is from place 1-4 -> add additional 1 point
                                                if ($trainerBet->{$fieldNameList[$i]} >= 1 && $trainerBet->{$fieldNameList[$i]} <= 4 && $result->{$fieldNameList[$i]} >= 1 && $result->{$fieldNameList[$i]} <= 4) {
                                                    // echo 'final correct ' . $fieldNameList[$i];
                                                    // echo '</br>';
                                                    $standingsData[$trainerBet->ID]['points'] = $standingsData[$trainerBet->ID]['points'] + 1;
                                                }

                                                //additionally if correctly estimateed position, that is from place 1-8 -> add additional 1 point
                                                if ($trainerBet->{$fieldNameList[$i]} >= 1 && $trainerBet->{$fieldNameList[$i]} <= 8 && $result->{$fieldNameList[$i]} >= 1 && $result->{$fieldNameList[$i]} <= 8) {
                                                    // echo 'semifinal correct ' . $fieldNameList[$i];
                                                    // echo '</br>';
                                                    $standingsData[$trainerBet->ID]['points'] = $standingsData[$trainerBet->ID]['points'] + 1;
                                                }
                                            }
                                            // DEBUG DATA
                                            // echo 'trainer id: ' . $trainerBet->ID . ' trainerName: ' . $standingsData[$trainerBet->ID]['trainerName'] . ' points received after round ' . $result->round_number . ': ' . $standingsData[$trainerBet->ID]['points'];
                                            // echo '</br>';
                                            // echo '</br>';
                                        }
                                    }

                                    uasort($standingsData, 'compareTrainers');

                                    // when results of all 16 GP's rounds are filled in -> save info about GP Champion
                                    if (count($getGrandPrixResultsQuery) == 16) {
                                        $firstKey = array_key_first($standingsData);

                                        //define if insert or update record of megaliga_grandprix_champion
                                        $checkIfRecordExist = $wpdb->get_results('SELECT COUNT(*) as "champion" FROM megaliga_grandprix_champion');

                                        // prepare data for submission
                                        $submitDataArray = array();
                                        $submitDataArray['user_name'] = $standingsData[$firstKey]['trainerName'];

                                        if ($checkIfRecordExist[0]->champion == 1) {
                                            //update record
                                            $where = array('id_champion' => '1');
                                            $wpdb->update('megaliga_grandprix_champion', $submitDataArray, $where);
                                        } else {
                                            //insert record
                                            $wpdb->insert('megaliga_grandprix_champion', $submitDataArray);
                                        }
                                    }



                                    return $standingsData;
                                }

                                function drawStandings($standings)
                                {
                                    echo '<table class="scheduleTable" border="0">';
                                    echo '  <tr>
                                    <th class="scheduleHeader textLeft">miejsce</th>
                                    <th class="scheduleHeader standingsHeader textLeft">trener</th>
                                    <th class="scheduleHeader standingsHeader textLeft">rozegrane GP</th>
                                    <th class="scheduleHeader standingsHeader textLeft">pkt</th>
                                </tr>';
                                    $i = 1;
                                    foreach ($standings as $trainer) {
                                        $trClass = $i % 2 == 0 ? 'even' : 'odd';
                                        echo '<tr class="' . $trClass . '">
                                    <td class="scheduleTdImg paddingLeft10">' . $i . '</td>
                                    <td class="scheduleTdImg">' . $trainer['trainerName'] . '</td>
                                    <td class="scheduleTdImg">' . $trainer['gamesPlayed'] . '</td>
                                    <td class="scheduleTdImg">' . $trainer['points'] . '</td>';
                                        echo '</tr>';

                                        $i++;
                                    }

                                    echo '</table>';
                                }

                                $standings = calculateStandingsData();
                                $getGpChamptionQuery = $wpdb->get_results('SELECT user_name FROM megaliga_grandprix_champion');

                                //content of the team page
                                echo '<div class="scheduleContainer flexDirectionColumn">';

                                if (count($getGpChamptionQuery) > 0) {
                                    echo '<div class="displayFlex flexDirectionColumn justifyContentCenter">';
                                    echo '  <div class="gpWinnerContainer">';
                                    echo '      <div class="displayFlex">';
                                    echo '          <img src="https://megaliga.eu/wp-content/uploads/2024/02/pucharGP.png" width="75px" height="100px">';
                                    echo '      </div>';
                                    echo '      <div class="marginLeft10 displayFlex flexDirectionColumn">';
                                    echo '        <span class="gpChampionTitle">Mistrz Grand Prix</span>';
                                    echo '        <span class="gpWinnerName">' . $getGpChamptionQuery[0]->user_name . '</span>';
                                    echo '      </div>';
                                    echo '  </div>';
                                    echo '</div>';
                                }


                                echo '  <div class="standingsTableContainer">';
                                drawStandings($standings);
                                echo '  </div>';
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
    <script type="text/javascript">
        (function() {
            var title = document.querySelector('#primary > div.container > div > div > h1');
            title.innerHTML = 'tabela - ' + title.innerHTML;
        })();
    </script>
    <?php get_footer(); ?>
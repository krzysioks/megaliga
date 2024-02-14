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
                                        // echo '-----result-----';
                                        // echo '</br>';
                                        // print_r($result);
                                        // echo '</br>';
                                        // echo '</br>';

                                        $getTrainersBetsQuery = $wpdb->get_results('SELECT * FROM megaliga_grandprix_bets WHERE round_number =' . $result->round_number);

                                        // stdClass Object ( [id_result] => 3 [round_number] => 1 [player_1] => 1 [player_2] => 2 [player_3] => 3 [player_4] => 4 [player_5] => 5 [player_6] => 6 [player_7] => 14 [player_8] => 15 [player_9] => 13 [player_10] => 12 [player_11] => 11 [player_12] => 10 [player_13] => 9 [player_14] => 8 [player_15] => 7 [player_16] => 16 )

                                        // stdClass Object ( [id_bet] => 20 [ID] => 46 [round_number] => 1 [player_1] => 1 [player_2] => 2 [player_3] => 3 [player_4] => 4 [player_5] => 5 [player_6] => 6 [player_7] => 7 [player_8] => 8 [player_9] => 9 [player_10] => 10 [player_11] => 11 [player_12] => 12 [player_13] => 13 [player_14] => 14 [player_15] => 15 [player_16] => 16 )

                                        // stdClass Object ( [id_bet] => 21 [ID] => 44 [round_number] => 1 [player_1] => 16 [player_2] => 2 [player_3] => 3 [player_4] => 4 [player_5] => 5 [player_6] => 6 [player_7] => 7 [player_8] => 8 [player_9] => 9 [player_10] => 10 [player_11] => 11 [player_12] => 12 [player_13] => 13 [player_14] => 14 [player_15] => 15 [player_16] => 1 )

                                        // stdClass Object ( [id_bet] => 22 [ID] => 26 [round_number] => 1 [player_1] => 16 [player_2] => 15 [player_3] => 14 [player_4] => 13 [player_5] => 12 [player_6] => 11 [player_7] => 10 [player_8] => 9 [player_9] => 8 [player_10] => 7 [player_11] => 6 [player_12] => 5 [player_13] => 4 [player_14] => 3 [player_15] => 2 [player_16] => 1 )

                                        foreach ($getTrainersBetsQuery as $trainerBet) {
                                            // for given trainer add played grand prix to "gamesPlayed"
                                            $standingsData[$trainerBet->ID]['gamesPlayed'] = $standingsData[$trainerBet->ID]['gamesPlayed'] + 1;
                                            // print_r($trainerBet);
                                            // echo '</br>';
                                            // echo '</br>';

                                            // za dobre obstawienie każdego z miejsc (1-16) jest 1 punkt, za dobre obstawienie miejsc 1-8 jest kolejny punkt za każde dobre obstawienie,  za dobre obstawienie miejsc 1-4 jest kolejny punkt za każde dobre obstawienie (czyli łącznie 3 za każde dobre obstawienie miejsc 1-4) i za dobre obstawienie miejsca 1 jest dodatkowo 1 punkt (czyli razem 4)

                                            $fieldNameList = array('player_1', 'player_2', 'player_3', 'player_4', 'player_5', 'player_6', 'player_7', 'player_8', 'player_9', 'player_10', 'player_11', 'player_12', 'player_13', 'player_14', 'player_15', 'player_16');

                                            for ($i = 0; $i < 16; $i++) {
                                                // echo 'result: ' . $result->{$fieldNameList[$i]};
                                                // echo '</br>';
                                                // echo 'bet: ' . $trainerBet->{$fieldNameList[$i]};

                                                if ($trainerBet->{$fieldNameList[$i]} == $result->{$fieldNameList[$i]}) {
                                                    // if trainer bet correctly position of given player -> add 1 point
                                                    $standingsData[$trainerBet->ID]['points'] = $standingsData[$trainerBet->ID]['points'] + 1;

                                                    if ($trainerBet->{$fieldNameList[$i]} == 1) {
                                                        //additionally if correctly bet position is exactly 1st place -> add additional 1 point
                                                        $standingsData[$trainerBet->ID]['points'] = $standingsData[$trainerBet->ID]['points'] + 1;
                                                    }

                                                    if ($trainerBet->{$fieldNameList[$i]} >= 1 && $trainerBet->{$fieldNameList[$i]} <= 4) {
                                                        //additionally if correctly bet position is from place 1-4 -> add additional 1 point
                                                        $standingsData[$trainerBet->ID]['points'] = $standingsData[$trainerBet->ID]['points'] + 1;
                                                    }

                                                    if ($trainerBet->{$fieldNameList[$i]} >= 1 && $trainerBet->{$fieldNameList[$i]} <= 8) {
                                                        //additionally if correctly bet position is from place 1-8 -> add additional 1 point
                                                        $standingsData[$trainerBet->ID]['points'] = $standingsData[$trainerBet->ID]['points'] + 1;
                                                    }
                                                }
                                            }
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
                                    // Array ( [46] => Array ( [trainerName] => Maro [ID] => 46 [gamesPlayed] => 2 [points] => 48 ) [44] => Array ( [trainerName] => Olo [ID] => 44 [gamesPlayed] => 2 [points] => 38 ) [20] => Array ( [trainerName] => Boggias [ID] => 20 [gamesPlayed] => 0 [points] => 0 ) [26] => Array ( [trainerName] => Vitoy [ID] => 26 [gamesPlayed] => 2 [points] => 0 ) [27] => Array ( [trainerName] => LUKASZ_ENKO [ID] => 27 [gamesPlayed] => 0 [points] => 0 ) [40] => Array ( [trainerName] => Gruby [ID] => 40 [gamesPlayed] => 0 [points] => 0 ) [47] => Array ( [trainerName] => zdruch [ID] => 47 [gamesPlayed] => 0 [points] => 0 ) [51] => Array ( [trainerName] => TORBYD [ID] => 51 [gamesPlayed] => 0 [points] => 0 ) [52] => Array ( [trainerName] => Kubi [ID] => 52 [gamesPlayed] => 0 [points] => 0 ) [55] => Array ( [trainerName] => Klopsy2 [ID] => 55 [gamesPlayed] => 0 [points] => 0 ) [56] => Array ( [trainerName] => Bizon [ID] => 56 [gamesPlayed] => 0 [points] => 0 ) [57] => Array ( [trainerName] => luk [ID] => 57 [gamesPlayed] => 0 [points] => 0 ) )
                                    global $wpdb;
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
                                // echo '</br>';
                                // echo '</br>';
                                // echo 'standings: ';
                                // echo '</br>';
                                // print_r($standings);

                                //content of the team page
                                echo '<div class="scheduleContainer flexDirectionColumn">';
                                // TODOKP render here info about GP Champion if exists
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
<?php
 /*
Template Name: Draft Position Order Draw ver 2.0
Description: Shows draft position order draw outcome for all users and form to draw position order for round 2 and highier for admins
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
            // $userId = 46; //14;
            // $userId = 14;

            if ($_POST['submitDrawPositionOrder']) {
                //check if draw of position order for given round has been already executed; prevent from redrawing positiob order when F5, reload of page is done or user tryies to draw for the same round
                $getCheckIfDrawHasBeenExecuted = $wpdb->get_results('SELECT id_next_round_draft_order_lottery_outcome FROM megaliga_next_round_draft_order_lottery_outcome WHERE round_number = ' . $_POST['roundNumber']);

                //if there is no record for given round in data base -> save the draw outcome
                if (count($getCheckIfDrawHasBeenExecuted) == 0) {
                    $getUserData = $wpdb->get_results('SELECT wp_users.user_login, megaliga_user_data.ID, megaliga_user_data.credit_balance FROM wp_users, megaliga_user_data WHERE wp_users.ID = megaliga_user_data.ID');

                    //calculate name of previous season based on current season substructed by 1.
                    $getCurrentSeasonName = $wpdb->get_results('SELECT season_name FROM megaliga_season WHERE current = 1');

                    $previousSeason = $getCurrentSeasonName[0]->season_name - 1;

                    $getLastYearPosition = $wpdb->get_results('SELECT one, two, three, four, five, six, seven, eight FROM megaliga_seasons_standings_temp WHERE season = ' . $previousSeason);

                    $lastYearPosKeys = array('one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight');
                    $key2valueMapper = array('one' => '1', 'two' => '2', 'three' => '3', 'four' => '4', 'five' => '5', 'six' => '6', 'seven' => '7', 'eight' => '8');

                    //array with achivied score of each team. It will be sorted desc
                    $scoreArray = array();
                    //draw position order for each user
                    foreach ($getUserData as $user) {
                        //calculaing points for previous season position. The highier pos the lower points
                        //find user previous position
                        $pos = 0;
                        $i = 0;
                        foreach ($getLastYearPosition[0] as $id) {
                            if ($id == $user->ID) {
                                $pos = $key2valueMapper[$lastYearPosKeys[$i]];
                            }
                            $i++;
                        }

                        $previousSeasonScorePart = $pos * 12.5;

                        //calculating random part
                        $randomScorePart = mt_rand(0, 100);

                        //calculating credit balance part; the more spent the less points user get for this part
                        $dbCreditVal = $user->credit_balance; //wartosc kredytu danej druzyny z megaliag_user_data
                        $dbMinCreditVal = 0; //min wartosc kredytu w baze danych = 0
                        $dbMaxCreditVal = 1020; //max wartosc kredytu w baze danych = 1020
                        $minPartialCreditVal = 0; //min wartosc skladowej = 0 
                        $maxPartialCreditVal = 100; //max wartosc skladowej = 100

                        $creditBalancePart = (($dbCreditVal - $dbMinCreditVal) * ($maxPartialCreditVal - $minPartialCreditVal)) / ($dbMaxCreditVal - $dbMinCreditVal + $minPartialCreditVal);

                        $score = 0 * $previousSeasonScorePart + 0 * $randomScorePart + 1 * $creditBalancePart;

                        $scoreArray[$user->user_login] = $score;

                        // echo 'user: ' . $user->user_login . '</br>';
                        // echo '$previousSeasonScorePart: ' . $previousSeasonScorePart . '</br>$randomScorePart: ' . $randomScorePart . '</br>$creditBalancePart: ' . $creditBalancePart . '</br>$score: ' . $score;
                        // echo '</br></br>';
                    }

                    //sort desc achievied score of users to map score to draft order position for given draft round
                    arsort($scoreArray);
                    $submitDataArray = array();
                    $k = 0;
                    $scoreArrayKeys = array_keys($scoreArray);

                    foreach ($scoreArrayKeys as $scoreKey) {
                        $submitDataArray[$lastYearPosKeys[$k]] = $scoreKey;
                        $k++;
                    }

                    $submitDataArray['round_number'] = $_POST['roundNumber'];
                    $wpdb->insert('megaliga_next_round_draft_order_lottery_outcome', $submitDataArray);
                } else {
                    //if draw outcome already exists for given round -> show notification
                    echo '<div class="marginBottom20">';
                    echo '  <span class="left scoreTableName"> Losowanie dla rundy ' . $_POST['roundNumber'] . ' już się odbyło.</span>';
                    echo ' </div>';
                }
            }

            function drawDraftLotteryNextRoundForm($userId)
            {
                global $wpdb;

                //show form if user logged in and only for user with ID == 14 (mbaginski) || 48 (Gabbana)
                if (is_user_logged_in() && ($userId == 14 || $userId == 48)) {
                    //get number of rounds for which draw of position order has been completed
                    $getNumberOfDrawRounds = $wpdb->get_results('SELECT COUNT(*) as "length" FROM megaliga_next_round_draft_order_lottery_outcome');

                    //set the min round number for which draw has not been executed yet
                    $round = $getNumberOfDrawRounds[0]->length + 2;

                    echo '<div class="draftFormContainer">';
                    echo '  <form action="" method="post">';
                    echo '      <div class="displayFlex flexDirectionColumn">';
                    echo '          <div class="displayFlex flexDirectionRow">';
                    echo '              <span class="left marginRight20">';
                    echo '                  Losuj kolejność wyboru dla rundy: ' . $round;
                    echo '              </span>';
                    echo '          </div>';
                    echo '          <input class="submitDrawPositionOrder" type="submit" name="submitDrawPositionOrder" value="Losuj">';
                    echo '          </div>';
                    echo '<input type="hidden" name="roundNumber" id="roundNumber" value="' . $round . '">';
                    echo '  </form>';
                    echo '</div>';
                }
            }

            //content of the draft order position draw page
            drawDraftLotteryNextRoundForm($userId);
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
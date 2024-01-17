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
                            // $userId = 20;

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
                                echo '  <div class="marginLeft1em marginBottom20">';
                                echo '      <input type="submit" name="submitFormStatus" value="' . $buttonTitle . '">';
                                echo '      <input type="hidden" name="is_open" value="' . !$isFormEnabled[0]->is_open . '">';
                                echo '  </div>';
                                echo '</form>';
                            }

                            function emergencyBetSelectionForm($selectedValue)
                            {
                                echo '<div>emergencyBetSelectionForm</div>';
                                // global $wpdb;
                                // $getTeams = $wpdb->get_results('SELECT megaliga_team_names.name, megaliga_user_data.ID FROM megaliga_team_names, megaliga_user_data WHERE megaliga_team_names.team_names_id = megaliga_user_data.team_names_id');

                                // echo '<form action="" method="post">';
                                // echo '<div class="rosterContainer teamContainerDimentions">';
                                // echo '  <div>';
                                // echo '      <span class="emergencyTeamSelectionTitle">Formularz awaryjnego przydziału składu</span>';
                                // echo '  </div>';
                                // echo '  <div class="displayFlex flexDirectionColumn marginTop20">';
                                // echo '      <div><span class="teamOverviewLabel">Drużyna:</span></div>';
                                // echo '            <select class="teamSelect" name="team" id="selectTeam">';
                                // foreach ($getTeams as $option) {
                                //     if ($selectedValue == $option->ID) {
                                //         echo '            <option selected value="' . $option->ID . '">' . $option->name . '</option>';
                                //     } else {
                                //         echo '            <option value="' . $option->ID . '">' . $option->name . '</option>';
                                //     }
                                // }
                                // echo '            </select>';
                                // echo '  </div>';
                                // echo '  <div class="submitEmergencyTeamSelectionContainer">';
                                // echo '      <input type="submit" name="submitEmergencyTeamSelection" value="Wybierz">';
                                // echo '  </div>';
                                // echo '</div>';
                                // echo '</form>';
                            }

                            function drawPlayerBetForm($playersResult, $userId, $isOpen, $round_number)
                            {
                                global $wpdb;

                                echo '<div class="gpPlayersWrapper">';
                                echo '  <form action="" method="post">';

                                foreach ($playersResult as $player) {
                                    //get position that user bet for given player
                                    $fieldName = $player->field_name;
                                    $getBetPositionQuery = $wpdb->get_results('SELECT ' . $fieldName . ' as "betPosition" FROM megaliga_grandprix_bets WHERE ID=' . $userId . ' AND round_number = ' . $round_number);

                                    $betPosition = count($getBetPositionQuery) > 0 ? $getBetPositionQuery[0]->betPosition : '';

                                    echo '<div class="gpPlayerWrapper">';
                                    echo '  <div class="gpPlayerRow">';
                                    echo '      <div class="playerImageWrapper>';
                                    echo '      </div>';
                                    echo '      <div class="playerBetPosition>';
                                    echo '      </div>';
                                    echo '  </div>';
                                    echo '  <div class="row2">';
                                    echo '      <div class="playerTitleWrapper">';
                                    echo '          <a class="playerNameLink"> </a>';
                                    echo '      </div>';
                                    echo '      <div class="playerNationality">';
                                    echo '      </div>';
                                    echo '  </div>';
                                    echo '</div>';

                                    // print_r($getBetPositionQuery);

                                    // echo '$betPosition: ' . $betPosition;

                                    // echo '<br/>';
                                }

                                echo '  </form>';
                                echo '</div>';
                            }

                            //check if bet form is enabled (this option prevent from resubmission of betting player final position in the given round by user after position of players in real Grand Prix for given round has been announced)
                            $isFormEnabled = $wpdb->get_results('SELECT is_open FROM megaliga_grandprix_bet_status WHERE round_number = ' . $round_number);

                            // player data
                            $getPlayersQuery = $wpdb->get_results('SELECT player_name, photo_url, flag_url, bio_url, field_name FROM megaliga_grandprix_players');

                            echo "<div>";

                            if (!$isFormEnabled[0]->is_open) {
                                echo '<div class="marginLeft1em marginBottom20">';
                                echo '<span class="scoreTableName"Typowanie wyników w tej rundzie zostało zakończone.</Span>';
                                echo '</div>';
                            }

                            if ($userId == 14) {
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
            title.innerHTML = 'megaliga - ' + title.innerHTML;
        })();
    </script>
    <?php get_footer(); ?>
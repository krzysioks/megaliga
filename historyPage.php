<?php
/*
Template Name: History
Description: Shows score tables for regular and playoff (were applicable) part of season from previous seasons (without current). Data is stored in megaliga_hisotry table. To know which season is current get megaliga_season.current. If 1 -> season is current; 0 -> not current
 *
 *HARDCODED PART - need change if in the future groups names will change, or its number will change
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

                            function drawStandings($standings, $side, $groupName, $isPlayoff)
                            {
                                $margin = $side == 'left' ? 'marginRight40' : '';
                                echo '<table class="scheduleTable marginTop10 ' . $margin . '" border="0">';

                                if ($groupName != '') {
                                    echo '  <tr><td colspan="9" class="scheduleTableName textLeft">Grupa ' . ucfirst($groupName) . '</td></tr>';
                                }

                                echo '  <tr>
                                            <th class="scheduleHeader textLeft">m</th>
                                            <th class="scheduleHeader standingsHeader textLeft">drużyna</th>
                                            <th class="scheduleHeader standingsHeader textLeft">m</th>
                                            <th class="scheduleHeader standingsHeader textLeft">z</th>
                                            <th class="scheduleHeader standingsHeader textLeft">r</th>
                                            <th class="scheduleHeader standingsHeader textLeft">p</th>
                                            <th class="scheduleHeader standingsHeader textLeft">suma</th>
                                            <th class="scheduleHeader standingsHeader textLeft">+/-</th>
                                            <th class="scheduleHeader standingsHeader textLeft">pkt</th>
                                        </tr>';

                                $i = 1;
                                foreach ($standings as $team) {
                                    $trClass = $i % 2 == 0 ? 'even' : 'odd';
                                    $medal = ($i == 1 || $i == 2 || $i == 3) && $isPlayoff ? '<img class="marginLeft1em" src="http://megaliga.eu/wp-content/uploads/2022/03/medal' . $i . '.png">' : '';

                                    echo '<tr class="' . $trClass . '">
                                            <td class="scheduleTdImg paddingLeft10">' . $team->place . '</td>
                                            <td class="scheduleTd textLeft">' . $team->team_name . $medal . '</td>
                                            <td class="scheduleTdImg">' . $team->played . '</td>
                                            <td class="scheduleTdImg">' . $team->win . '</td>
                                            <td class="scheduleTdImg">' . $team->draw . '</td>
                                            <td class="scheduleTdImg">' . $team->defeat . '</td>
                                            <td class="scheduleTdImg">' . $team->totalScore . '</td>
                                            <td class="scheduleTdImg">' . $team->balance . '</td>
                                            <td class="scheduleTdImg">' . $team->points . '</td>';
                                    echo '</tr>';

                                    $i++;
                                }

                                echo '</table>';
                            }

                            function drawGrandPrixStandings($standings)
                            {
                                echo '<table class="scheduleTable marginTop10" border="0">';
                                echo '  <tr>
                                            <th class="scheduleHeader textLeft">miejsce</th>
                                            <th class="scheduleHeader standingsHeader textLeft">trener</th>
                                            <th class="scheduleHeader standingsHeader textLeft">rozegrane GP</th>
                                            <th class="scheduleHeader standingsHeader textLeft">pkt</th>
                                        </tr>';

                                $i = 1;
                                foreach ($standings as $trainer) {
                                    $trClass = $i % 2 == 0 ? 'even' : 'odd';
                                    $medal = $i == 1 ? '<img class="marginLeft1em" src="http://megaliga.eu/wp-content/uploads/2022/03/medal' . $i . '.png">' : '';

                                    echo '<tr class="' . $trClass . '">
                                            <td class="scheduleTdImg paddingLeft10">' . $trainer->place . '</td>
                                            <td class="scheduleTd textLeft">' . $trainer->user_name . $medal . '</td>
                                            <td class="gpTd">' . $trainer->games_played . '</td>
                                            <td class="scheduleTdImg">' . $trainer->points . '</td>';
                                    echo '</tr>';

                                    $i++;
                                }

                                echo '</table>';
                            }

                            $getSeasonIdToShow = $wpdb->get_results('SELECT id_season, season_name, number_of_groups FROM megaliga_season WHERE current = 0 ORDER BY season_name DESC');

                            $getGrandPrixSeasonIdToShow = $wpdb->get_results('SELECT id_season, season_name FROM megaliga_grandprix_season WHERE current = 0 ORDER BY season_name DESC');

                            //content of the history page
                            echo '<div>';
                            foreach ($getSeasonIdToShow as $season) {
                                echo '<div class="marginBottom20 marginTop20">';
                                echo '  <div class="marginBottom20 marginLeft1em">';
                                echo '      <span class="teamOverviewTeamName">sezon: ' . $season->season_name . '</span>';
                                echo '  </div>';
                                echo '  <div>';
                                echo '      <span class="roundName marginLeft1em">runda: zasadnicza</span>';
                                echo '  </div>';
                                echo '  <div class="scheduleContainer marginBottom2em historyTableContainer">';
                                //HARDCODED PART - need change if in the future groups names will change, or its number will change
                                //if 2 groups in season present -> names are dolce, gabbana; if 1 group -> dolce&gabbana
                                //get data for regular season and draw it
                                if ($season->number_of_groups == 2) {
                                    $standingsDolce = $wpdb->get_results('SELECT place, team_name, played, win, draw, defeat, totalScore, balance, points  FROM megaliga_history WHERE id_season = ' . $season->id_season . ' AND ligue_group = "dolce" AND table_type = "regular" ORDER BY place');
                                    $standingsGabbana = $wpdb->get_results('SELECT place, team_name, played, win, draw, defeat, totalScore, balance, points  FROM megaliga_history WHERE id_season = ' . $season->id_season . ' AND ligue_group = "gabbana" AND table_type = "regular" ORDER BY place');

                                    drawStandings($standingsDolce, 'left', 'dolce', false);
                                    drawStandings($standingsGabbana, 'right', 'gabbana', false);
                                } else {
                                    $standings = $wpdb->get_results('SELECT place, team_name, played, win, draw, defeat, totalScore, balance, points  FROM megaliga_history WHERE id_season = ' . $season->id_season . ' AND ligue_group = "dolce&gabbana" AND table_type = "regular" ORDER BY place');

                                    drawStandings($standings, 'none', 'Dolce&Gabbana', false);
                                }

                                echo '  </div>';

                                //get playin data
                                $playinStandings = $wpdb->get_results('SELECT place, team_name, played, win, draw, defeat, totalScore, balance, points  FROM megaliga_history WHERE id_season = ' . $season->id_season . ' AND table_type = "playin" ORDER BY place');

                                //draw playin data
                                if (count($playinStandings) != 0) {
                                    echo '<div class="marginLeft1em">';
                                    echo '  <span class="roundName">runda: playin</span>';
                                    echo '</div>';
                                    echo '  <div class="historyTableContainer marginBottom20">';
                                    drawStandings($playinStandings, 'none', '', false);
                                    echo '</div>';
                                }

                                //get playoff data
                                $playoffStandings = $wpdb->get_results('SELECT place, team_name, played, win, draw, defeat, totalScore, balance, points  FROM megaliga_history WHERE id_season = ' . $season->id_season . ' AND table_type = "playoff" ORDER BY place');

                                //draw playoff data
                                if (count($playoffStandings) != 0) {
                                    echo '<div class="marginLeft1em">';
                                    echo '  <span class="roundName">runda: playoff</span>';
                                    echo '</div>';
                                    echo '  <div class="historyTableContainer">';
                                    drawStandings($playoffStandings, 'none', '', true);
                                    echo '</div>';
                                }

                                // get Grand Prix data
                                $isGrandPrixSeasonPresent = false;
                                foreach ($getGrandPrixSeasonIdToShow as $gpSeason) {
                                    if ($gpSeason->season_name == $season->season_name) {
                                        $isGrandPrixSeasonPresent = true;
                                    }
                                }

                                if ($isGrandPrixSeasonPresent) {
                                    $grandPrixStandings = $wpdb->get_results('SELECT place, user_name, games_played, points  FROM megaliga_grandprix_history WHERE id_season = ' . $gpSeason->id_season . ' ORDER BY place');

                                    //draw playoff data
                                    if (count($grandPrixStandings) != 0) {
                                        echo '<div class="marginLeft1em marginTop2em">';
                                        echo '  <span class="roundName">Grand Prix</span>';
                                        echo '</div>';
                                        echo '  <div class="historyTableContainer">';
                                        drawGrandPrixStandings($grandPrixStandings);
                                        echo '</div>';
                                    }
                                }
                            }

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

    <?php get_footer(); ?>
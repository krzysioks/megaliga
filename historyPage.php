<?php
/*
Template Name: History
Description: Shows score tables for regular and playoff (were applicable) part of season from previous seasons (without current). Data is stored in megaliga_hisotry table. To know which season is current get megaliga_season.current. If 1 -> season is current; 0 -> not current
 *
 *HARDCODED PART - need change if in the future groups names will change, or its number will change
 */
?>
<?php get_header(); ?>
<main id="content">
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
<header class="header">
<h1 class="entry-title"><?php the_title(); ?></h1> <?php edit_post_link(); ?>
</header>
<div class="entry-content">
<?php if (has_post_thumbnail()) {
    the_post_thumbnail();
} ?>

<?php 
global $wpdb;

function drawStandings($standings, $side, $groupName)
{
    global $wpdb;
    $margin = $side == 'left' ? 'marginRight40' : '';
    echo '<table class="scheduleTable marginTop10 ' . $margin . '" border="0">';

    if ($groupName != '') {
        echo '  <tr><td colspan="8" class="scheduleTableName textLeft">Grupa ' . ucfirst($groupName) . '</td></tr>';
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
        echo '<tr class="' . $trClass . '">
                <td class="scheduleTdImg paddingLeft10">' . $team->place . '</td>
                <td class="scheduleTd textLeft">' . $team->team_name . '</td>
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

$getSeasonIdToShow = $wpdb->get_results('SELECT id_season, season_name, number_of_groups FROM megaliga_season WHERE current = 0');

//content of the history page
echo '<div>';
foreach ($getSeasonIdToShow as $season) {
    echo '<div class="marginBottom20">';
    echo '  <div class="marginBottom20">';
    echo '      <span class="teamOverviewTeamName">sezon: ' . $season->season_name . '</span>';
    echo '  </div>';
    echo '  <div>';
    echo '      <span class="roundName">runda: zasadnicza</span>';
    echo '  </div>';
    echo '  <div class="scheduleContainer">';
    //HARDCODED PART - need change if in the future groups names will change, or its number will change
    //if 2 groups in season present -> names are dolce, gabbana; if 1 group -> dolce&gabbana
    //get data for regular season and draw it
    if ($season->number_of_groups == 2) {
        $standingsDolce = $wpdb->get_results('SELECT place, team_name, played, win, draw, defeat, totalScore, balance, points  FROM megaliga_history WHERE id_season = ' . $season->id_season . ' AND ligue_group = "dolce" AND table_type = "regular" ORDER BY place');
        $standingsGabbana = $wpdb->get_results('SELECT place, team_name, played, win, draw, defeat, totalScore, balance, points  FROM megaliga_history WHERE id_season = ' . $season->id_season . ' AND ligue_group = "gabbana" AND table_type = "regular" ORDER BY place');

        drawStandings($standingsDolce, 'left', 'dolce');
        drawStandings($standingsGabbana, 'right', 'gabbana');
    } else {
        $standings = $wpdb->get_results('SELECT place, team_name, played, win, draw, defeat, totalScore, balance, points  FROM megaliga_history WHERE id_season = ' . $season->id_season . ' AND ligue_group = "dolce&gabbana" AND table_type = "regular" ORDER BY place');

        drawStandings($standings, 'left', 'Dolce&Gabbana');
    }

    echo '  </div>';

    //get playoff data
    $playoffStandings = $wpdb->get_results('SELECT place, team_name, played, win, draw, defeat, totalScore, balance, points  FROM megaliga_history WHERE id_season = ' . $season->id_season . ' AND table_type = "playoff" ORDER BY place');

    //draw playoff data
    if (count($playoffStandings) != 0) {
        echo '<div>';
        echo '  <span class="roundName">runda: playoff</span>';
        echo '</div>';
        echo '  <div>';
        drawStandings($playoffStandings, 'left', '');
        echo '</div>';
    }
    echo '</div>';
}

echo '</div>';

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
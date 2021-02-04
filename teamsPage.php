<?php
/*
Template Name: Teams
 */
?>
<?php get_header(); ?>
<main id="content">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="header">
                    <h1 class="entry-title">
                        <?php the_title(); ?>
                    </h1>
                    <?php edit_post_link(); ?>
                </header>
                <div class="entry-content">
                    <?php if (has_post_thumbnail()) {
                        the_post_thumbnail();
                    } ?>

                    <?php
                    global $wpdb;

                    function drawTeam($queryResult, $groupName)
                    {
                        foreach ($queryResult as $field) {
                            global $wpdb;
                            //get regular season roster for given team
                            $getRegularSeasonRoster = $wpdb->get_results('SELECT ekstraliga_player_name FROM megaliga_players WHERE id_user = ' . $field->ID . ' ORDER BY drafted_with_number ASC');

                            //get playoff roster for given team
                            $getPlayoffRoster = $wpdb->get_results('SELECT ekstraliga_player_name FROM megaliga_players WHERE id_user_playoff = ' . $field->ID . ' ORDER BY drafted_with_number_playoff ASC');

                            echo '<div class="teamContainer teamContainerDimentions">';
                            echo '  <div class="teamImgContainer">';
                            echo '      <img src="' . $field->logo_url . '" width="200px" height="200px">';
                            echo '  </div>';
                            echo '  <div class="teamOverviewContainer">';
                            echo '      <div class="teamOverviewRow">';
                            echo '          <span class="teamOverviewLabel">drużyna:</span>';
                            echo '          <span class="teamOverviewTeamName">' . $field->team_name . '</span>';
                            echo '      </div>';
                            echo '      <div class="teamOverviewRow">';
                            echo '          <span class="teamOverviewLabel">grupa:</span>';
                            echo '          <span class="teamOverviewContent">' . $groupName . '</span>';
                            echo '      </div>';
                            echo '      <div class="teamOverviewRow">';
                            echo '          <span class="teamOverviewLabel">trener:</span>';
                            echo '          <span class="teamOverviewContent">' . $field->user_login . '</span>';
                            echo '      </div>';
                            echo '  </div>';
                            echo '  <div class="teamRosterContainer">';
                            echo '      <span class="teamOverviewRosterLabel">skład runda zasadnicza:</span>';
                            echo '          <ul>';

                            foreach ($getRegularSeasonRoster as $rosterField) {
                                echo '          <li><span class="teamOverviewRosterPlayerName">' . $rosterField->ekstraliga_player_name . '</span></li>';
                            }
                            echo '         <ul>';
                            echo '  </div>';

                            if (count($getPlayoffRoster) != 0) {
                                echo '  <div class="teamRosterContainer">';
                                echo '      <span class="teamOverviewRosterLabel">skład playoff:</span>';
                                echo '          <ul>';

                                foreach ($getPlayoffRoster as $rosterField) {
                                    echo '          <li><span class="teamOverviewRosterPlayerName">' . $rosterField->ekstraliga_player_name . '</span></li>';
                                }
                                echo '         <ul>';
                                echo '  </div>';
                            }

                            echo '</div>';
                        }
                    }

                    //get teams for Dolce&Gabbana ligue
                    $getUserData = $wpdb->get_results('SELECT wp_users.user_login, megaliga_team_names.name as "team_name", megaliga_user_data.ID, megaliga_user_data.logo_url FROM megaliga_user_data, wp_users, megaliga_team_names WHERE megaliga_user_data.ID = wp_users.ID AND megaliga_user_data.ligue_groups_id = 3 AND megaliga_user_data.team_names_id = megaliga_team_names.team_names_id');

                    //content of the team page
                    echo '<div>';
                    drawTeam($getUserData, 'Dolce&Gabbana');
                    echo '</div>';
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
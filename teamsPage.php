<?php
/*
Template Name: Teams
Description: Shows teams for 2 groups
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

                            $current_user = wp_get_current_user();
                            $userId = $current_user->ID;
                            $userId = 56;

                            //get conditions to show group lottery form
                            $getGroupLotteryDataQuery = $wpdb->get_results('SELECT group_lottery_open, countRookies FROM megaliga_draft_data');

                            //form submission
                            if ($_POST['submitLottery']) {
                                //check if system tries to subimt same data again and group lottery is open
                                $getCheckIfUserTeamAlreadyAssignedToGroup = $wpdb->get_results('SELECT ligue_groups_id FROM megaliga_user_data WHERE ID = ' . $userId);

                                if ($getCheckIfUserTeamAlreadyAssignedToGroup[0]->ligue_groups_id == 4 && $getGroupLotteryDataQuery[0]->group_lottery_open == 1) {
                                    function assignTeam($getDolceTeamNumber, $getGabbanaTeamNumber, $limit, $userId)
                                    {
                                        global $wpdb;

                                        $lotteryNumber = random_int(0, 10);
                                        $updateUserGroupAssigmentData = array();
                                        $whereUpdateUserGroupAssigment = array('ID' => $userId);

                                        //team is assigned to dolce if $lotteryNumber <= 5 and there is still space in dolce group OR $lotteryNumber > 5 and there is no space in Gabbana group
                                        if ($lotteryNumber <= 5 && $getDolceTeamNumber[0]->dolce < $limit || $lotteryNumber > 5 && $getGabbanaTeamNumber[0]->gabbana == $limit) {
                                            $updateUserGroupAssigmentData['ligue_groups_id'] = 1;
                                        } else {
                                            $updateUserGroupAssigmentData['ligue_groups_id'] = 2;
                                        }

                                        $wpdb->update('megaliga_user_data', $updateUserGroupAssigmentData, $whereUpdateUserGroupAssigment);
                                    }

                                    //check if in given season there are any rookies
                                    if ($getGroupLotteryDataQuery[0]->countRookies) {
                                        //check if user is rookie (this influence from which bucket will be draw)
                                        $getIsRookie = $wpdb->get_results('SELECT is_rookie FROM megaliga_user_data WHERE ID = ' . $userId);

                                        //get number of already assigned teams from 1st bucket for dolce
                                        $getDolceFirstBucketTeamNumber = $wpdb->get_results('SELECT COUNT(*) as "dolce" FROM megaliga_user_data WHERE ligue_groups_id = 1 AND is_rookie = 0');
                                        //get number of already assigned teams from 2nd bucket for dolce
                                        $getDolceSecondBucketTeamNumber = $wpdb->get_results('SELECT COUNT(*) as "dolce" FROM megaliga_user_data WHERE ligue_groups_id = 1 AND is_rookie = 1');

                                        //get number of already assigned teams from 1st bucket for dolce
                                        $getGabbanaFirstBucketTeamNumber = $wpdb->get_results('SELECT COUNT(*) as "gabbana" FROM megaliga_user_data WHERE ligue_groups_id = 2 AND is_rookie = 0');
                                        //get number of already assigned teams from 2nd bucket for dolce
                                        $getGabbanaSecondBucketTeamNumber = $wpdb->get_results('SELECT COUNT(*) as "gabbana" FROM megaliga_user_data WHERE ligue_groups_id = 2 AND is_rookie = 1');

                                        //if user is rookie -> there cannot be more than 2 rookie teams in the group
                                        if ($getIsRookie[0]->is_rookie) {
                                            assignTeam($getDolceSecondBucketTeamNumber,  $getGabbanaSecondBucketTeamNumber, 2, $userId);
                                        } else {
                                            assignTeam($getDolceFirstBucketTeamNumber,  $getGabbanaFirstBucketTeamNumber, 4, $userId);
                                        }
                                    } else {
                                        //get number of already assigned teams from 1st bucket for dolce
                                        $getDolceFirstBucketTeamNumber = $wpdb->get_results('SELECT COUNT(*) as "dolce" FROM megaliga_user_data WHERE ligue_groups_id = 1 AND is_rookie = 0');

                                        //get number of already assigned teams from 1st bucket for dolce
                                        $getGabbanaFirstBucketTeamNumber = $wpdb->get_results('SELECT COUNT(*) as "gabbana" FROM megaliga_user_data WHERE ligue_groups_id = 2 AND is_rookie = 0');

                                        assignTeam($getDolceFirstBucketTeamNumber,  $getGabbanaFirstBucketTeamNumber, 6, $userId);
                                    }
                                }
                            }


                            function drawTeam($queryResult)
                            {
                                foreach ($queryResult as $field) {
                                    global $wpdb;
                                    //get regular season roster for given team
                                    $getRegularSeasonRoster = $wpdb->get_results('SELECT ekstraliga_player_name FROM megaliga_players WHERE id_user_' . $field->group_name . ' = ' . $field->ID . ' ORDER BY drafted_with_number_' . $field->group_name . ' ASC');

                                    //get playoff roster for given team
                                    $getPlayoffRoster = $wpdb->get_results('SELECT ekstraliga_player_name FROM megaliga_players WHERE id_user_playoff = ' . $field->ID . ' ORDER BY drafted_with_number_playoff ASC');

                                    echo '<div class="teamContainer teamContainerDimentions overflowXhidden">';
                                    echo '  <div class="teamImgContainer">';
                                    echo '      <img src="' . $field->logo_url . '" width="200px" height="200px">';
                                    echo '  </div>';
                                    echo '  <div class="teamOverviewTeamPageContainer">';
                                    echo '      <div class="teamOverviewRow">';
                                    echo '          <span class="teamOverviewLabel">drużyna:</span>';
                                    echo '          <span class="teamOverviewTeamName">' . $field->team_name . '</span>';
                                    echo '      </div>';
                                    echo '      <div class="teamOverviewRow">';
                                    echo '          <span class="teamOverviewLabel">grupa:</span>';
                                    echo '          <span class="teamOverviewContent">' . $field->group_name . '</span>';
                                    echo '      </div>';
                                    echo '      <div class="teamOverviewRow">';
                                    echo '          <span class="teamOverviewLabel">trener:</span>';
                                    echo '          <span class="teamOverviewContent">' . $field->user_login . '</span>';
                                    echo '      </div>';
                                    echo '  </div>';
                                    echo '  <div class="teamRosterTeamPageContainer">';
                                    echo '      <span class="teamOverviewRosterLabel">skład megaliga:</span>';
                                    echo '          <ul class="noDecoration">';

                                    foreach ($getRegularSeasonRoster as $rosterField) {
                                        echo '          <li><span class="teamOverviewRosterPlayerName">' . $rosterField->ekstraliga_player_name . '</span></li>';
                                    }
                                    echo '         <ul>';
                                    echo '  </div>';

                                    if (count($getPlayoffRoster) != 0) {
                                        echo '  <div class="teamRosterTeamPageContainer">';
                                        echo '      <span class="teamOverviewRosterLabel">skład playoffs:</span>';
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

                            function drawGroupLotteryForm($isGroupLotteryOpenQuery, $userId)
                            {
                                global $wpdb;
                                $megaligaUserDataQuery = $wpdb->get_results('SELECT user_data_id, ligue_groups_id FROM megaliga_user_data WHERE ID = ' . $userId);
                                //show group lottery form if user is logged in and is megaliga player and group lottery is open 
                                $showGroupLotteryForm = is_user_logged_in() && $isGroupLotteryOpenQuery[0]->group_lottery_open == 1 && count($megaligaUserDataQuery) == 1;

                                if ($showGroupLotteryForm) {
                                    $getUserName = $wpdb->get_results('SELECT user_login FROM wp_users WHERE ID = ' . $userId);

                                    //show form lottery if user has not yet choosen group                        
                                    if ($megaligaUserDataQuery[0]->ligue_groups_id == 4) {
                                        echo '<form action="" method="post">';
                                        echo '  <div class="displayFlex flexDirectionColumn marginLeft1em">';
                                        echo '    <div class="displayFlex flexDirectionRow alignItemsBaseline">';
                                        echo '      <span class="teamOverviewContent">' . $getUserName[0]->user_login . '</span><span>, wylosouj przydział do grupy</span>';
                                        echo '    </div>';
                                        echo '    <input class="submitDraftPlayer" type="submit" name="submitLottery" value="Wylosuj">';
                                        echo '  </div>';
                                        echo '</form>';
                                    } else {
                                        //show notification about group to which user has been added
                                        $getGroupName = $wpdb->get_results('SELECT name FROM megaliga_ligue_groups WHERE ligue_groups_id = ' . $megaligaUserDataQuery[0]->ligue_groups_id);

                                        echo '<div class="displayFlex flexDirectionColumn marginY20 marginLeft1em">';
                                        echo '  <div class="displayFlex flexDirectionRow alignItemsBaseline">';
                                        echo '    <span class="teamOverviewContent">' . $getUserName[0]->user_login . '</span><span>, Twoja drużyna została przydzielona do grupy:</span>';
                                        echo '  </div>';
                                        echo '  <div class="marginTop10">';
                                        echo '    <span class="teamOverviewTeamName">' . $getGroupName[0]->name . '</span>';
                                        echo '  </div>';
                                        echo '</div>';
                                    }
                                }
                            }

                            //get teams for Dolce and Gabbana ligue groups
                            $getUserData = $wpdb->get_results('SELECT wp_users.user_login, megaliga_team_names.name as "team_name", megaliga_user_data.ID, megaliga_user_data.logo_url, megaliga_ligue_groups.name as "group_name" FROM megaliga_user_data, wp_users, megaliga_team_names, megaliga_ligue_groups WHERE megaliga_user_data.ID = wp_users.ID AND megaliga_user_data.team_names_id = megaliga_team_names.team_names_id AND megaliga_ligue_groups.ligue_groups_id = megaliga_user_data.ligue_groups_id ORDER BY megaliga_ligue_groups.name');


                            //content of the team page
                            echo '<div>';
                            drawGroupLotteryForm($isGroupLotteryOpenQuery, $userId);
                            drawTeam($getUserData);
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

    <?php get_footer(); ?>
<?php
/*
Template Name: Draft Order
Description: Shows draft order table for two group in the ligue
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

                            //check if draft order lottery is finished for dolce..
                            $getCheckIfDraftOrderEstablishedDolce = $wpdb->get_results('SELECT COUNT(*) as "dolce" FROM megaliga_user_data WHERE is_draw_round1_draft_order = 1 AND ligue_groups_id = 1');
                            //... for gabbana
                            $getCheckIfDraftOrderEstablishedGabbana = $wpdb->get_results('SELECT COUNT(*) as "gabbana" FROM megaliga_user_data WHERE is_draw_round1_draft_order = 1 AND ligue_groups_id = 2');

                            $getUserData = $wpdb->get_results('SELECT ID, team_names_id FROM megaliga_user_data');

                            function updateDraftOrderTable($getDraftOrder, $draftOrderTableName, $userId2teamIdMapper)
                            {
                                global $wpdb;

                                //map user id to order position
                                $draftOrder2userIdMapper = array();
                                $i = 1;
                                foreach ($getDraftOrder as $data) {
                                    $draftOrder2userIdMapper[$i] = $data;
                                    $i++;
                                }

                                //order in which teams will be drafting players
                                $draftOrderArray = array(1, 2, 3, 4, 5, 6, 6, 5, 4, 3, 2, 1, 1, 2, 3, 4, 5, 6, 6, 5, 4, 3, 2, 1, 1, 2, 3, 4, 5, 6, 6, 5, 4, 3, 2, 1, 1, 2, 3, 4, 5, 6, 6, 5, 4, 3, 2, 1, 1, 2, 3, 4, 5, 6, 6, 5, 4, 3, 2, 1, 1, 2, 3, 4, 5, 6, 6, 5, 4, 3, 2, 1, 1, 2, 3, 4, 5, 6, 6, 5, 4, 3, 2, 1);

                                //check if $draftOrderTableName has already records -> UPDATE or is empty -> INSERT
                                $getNumberOfRounds = $wpdb->get_results('SELECT COUNT(*) as "size" FROM ' . $draftOrderTableName);

                                $i = 1;
                                foreach ($draftOrderArray as $value) {
                                    // prepare data for submission
                                    $userId = $draftOrder2userIdMapper[$value];
                                    $submitDataArray = array();
                                    $submitDataArray['draft_order'] = $i;
                                    $submitDataArray['ID'] = $userId;
                                    $submitDataArray['team_names_id'] = $userId2teamIdMapper[$userId];

                                    if (!$getNumberOfRounds[0]->size) {
                                        $wpdb->insert($draftOrderTableName, $submitDataArray);
                                    } else {
                                        //update if records already exists
                                        $where = array('draft_order' => $i);
                                        $wpdb->update($draftOrderTableName, $submitDataArray, $where);
                                    }

                                    $i++;
                                }
                            }

                            //only if draft order lottery is finished -> update table with draft order (megaliga_season_draft_order_dolce/gabbana)
                            if ($getCheckIfDraftOrderEstablishedDolce[0]->dolce == 6 && $getCheckIfDraftOrderEstablishedGabbana[0]->gabbana == 6) {
                                $getDraftOrder = $wpdb->get_results('SELECT one, two, three, four, five, six FROM megaliga_1round_draft_order_lottery_outcome');
                                $getUserData = $wpdb->get_results('SELECT ID, team_names_id FROM megaliga_user_data');

                                $userId2teamIdMapper = array();
                                foreach ($getUserData as $userData) {
                                    $userId2teamIdMapper[$userData->ID] = $userData->team_names_id;
                                }

                                updateDraftOrderTable($getDraftOrder[0], 'megaliga_season_draft_order_dolce', $userId2teamIdMapper);
                                updateDraftOrderTable($getDraftOrder[1], 'megaliga_season_draft_order_gabbana', $userId2teamIdMapper);
                            }



                            //get info about whose turn is now if draft is open for Dolce group...
                            $getDraftWindowState = $wpdb->get_results('SELECT draft_window_open, draft_current_round_dolce, draft_current_round_gabbana FROM megaliga_draft_data');
                            $getTeamIdDolce = $wpdb->get_results('SELECT team_names_id FROM megaliga_season_draft_order_dolce WHERE draft_order = ' . $getDraftWindowState[0]->draft_current_round_dolce);
                            $getTeamDolce = $wpdb->get_results('SELECT name FROM megaliga_team_names WHERE team_names_id = ' . $getTeamIdDolce[0]->team_names_id);

                            //... for Gabbana group
                            $getTeamIdGabbana = $wpdb->get_results('SELECT team_names_id FROM megaliga_season_draft_order_gabbana WHERE draft_order = ' . $getDraftWindowState[0]->draft_current_round_gabbana);
                            $getTeamGabbana = $wpdb->get_results('SELECT name FROM megaliga_team_names WHERE team_names_id = ' . $getTeamIdGabbana[0]->team_names_id);

                            if ($getDraftWindowState[0]->draft_window_open) {

                                echo '<div class="displayFlex playoffLadderflexDirection">';
                                echo '  <div class="width_100 padding10 marginLeft1em">';
                                echo "      <span class='padding10'>W grupie Dolce, w rundzie <span class='playoffPhaseTitle'>" . $getDraftWindowState[0]->draft_current_round_dolce . "</span> teraz wybiera zespół: <span class='playoffPhaseTitle'>" . $getTeamDolce[0]->name . "</span></span>";
                                echo '  </div>';
                                echo '  <div class="width_100 padding10 marginLeft1em">';
                                echo "      <span class='padding10'>W grupie Gabbana, w rundzie <span class='playoffPhaseTitle'>" . $getDraftWindowState[0]->draft_current_round_gabbana . "</span> teraz wybiera zespół: <span class='playoffPhaseTitle'>" . $getTeamGabbana[0]->name . "</span></span>";
                                echo '  </div>';
                                echo '</div>';
                            }

                            the_content();
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
        document.querySelector('div.jsgrid').style = {};
        document.querySelector('div.padding10.second div.jsgrid').style = {};
        addEventListener('resize', event => {
            document.querySelector('div.jsgrid').style = {};
            document.querySelector('div.padding10.second div.jsgrid').style = {};
        });
    </script>

    <?php get_footer(); ?>
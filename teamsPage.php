<?php
/*
Template Name: Teams
*/
?>
<?php get_header(); ?>
<main id="content">
<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
<header class="header">
<h1 class="entry-title"><?php the_title(); ?></h1> <?php edit_post_link(); ?>
</header>
<div class="entry-content">
<?php if ( has_post_thumbnail() ) { the_post_thumbnail(); } ?>

<?php 
global $wpdb;

function drawTeam($queryResult, $groupName) {
    foreach ( $queryResult as $field ) {
        global $wpdb;
        //get roster for given team
        $idGroupName = 'id_user_'.$groupName;
        $getRosterQuery = $wpdb->get_results('SELECT ekstraliga_player_name, ekstraliga_player_club_name FROM megaliga_players WHERE '.$idGroupName.' = '.$field->ID);
    
        echo '<div class="teamContainer teamContainerDimentions">';
        echo '  <div class="teamImgContainer">';
        echo '      <img src="'.$field->logo_url.'" width="200px" height="200px">';
        echo '  </div>';
        echo '  <div class="teamOverviewContainer">';
        echo '      <div class="teamOverviewRow">';
        echo '          <span class="teamOverviewLabel">drużyna:</span>';
        echo '          <span class="teamOverviewTeamName">'.$field->team_name.'</span>';
        echo '      </div>';
        echo '      <div class="teamOverviewRow">';
        echo '          <span class="teamOverviewLabel">grupa:</span>';
        echo '          <span class="teamOverviewContent">'.ucfirst($groupName).'</span>';
        echo '      </div>';
        echo '      <div class="teamOverviewRow">';
        echo '          <span class="teamOverviewLabel">trener:</span>';
        echo '          <span class="teamOverviewContent">'.$field->user_login.'</span>';
        echo '      </div>';
        echo '  </div>';
        echo '  <div class="teamRosterContainer">';
        echo '      <span class="teamOverviewRosterLabel">skład:</span>';
        echo '          <ul>';
    
    
        foreach ($getRosterQuery as $rosterField) {
            echo '          <li><span class="teamOverviewRosterPlayerName">'.$rosterField->ekstraliga_player_name.'</span><span class="teamOverviewRosterTeamName"> ('.$rosterField->ekstraliga_player_club_name.')</span></li>';
        }
        echo '              <ul>';
    
        echo '  </div>';
        echo '</div>';
    }
}

//get teams for Dolce ligue
$getUserData4DolceQuery = $wpdb->get_results('SELECT wp_users.user_login, megaliga_team_names.name as "team_name", megaliga_user_data.ID, megaliga_user_data.logo_url FROM megaliga_user_data, wp_users, megaliga_team_names WHERE megaliga_user_data.ID = wp_users.ID AND megaliga_user_data.ligue_groups_id = 1 AND megaliga_user_data.team_names_id = megaliga_team_names.team_names_id');

//get teams for Gabbama ligue
$getUserData4GabbanaQuery = $wpdb->get_results('SELECT wp_users.user_login, megaliga_team_names.name as "team_name", megaliga_user_data.ID, megaliga_user_data.logo_url FROM megaliga_user_data, wp_users, megaliga_team_names WHERE megaliga_user_data.ID = wp_users.ID AND megaliga_user_data.ligue_groups_id = 2 AND megaliga_user_data.team_names_id = megaliga_team_names.team_names_id');

//content of the team page
echo '<div>';
drawTeam($getUserData4DolceQuery, 'dolce');
drawTeam($getUserData4GabbanaQuery, 'gabbana');
echo '</div>';
?>


<div class="entry-links"><?php wp_link_pages(); ?></div>
</div>
</article>
<?php if ( ! post_password_required() ) comments_template( '', true ); ?>
<?php endwhile; endif; ?>
</main>
<?php get_sidebar(); ?>
<?php get_footer(); ?>
<?php
function my_theme_enqueue_styles()
{

    $parent_style = 'services-style'; // This is 'servicesstyle' for the Services theme.

    wp_enqueue_style($parent_style, get_template_directory_uri() . '/style.css');
    wp_enqueue_style(
        'child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array($parent_style),
        wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'my_theme_enqueue_styles');

add_action('login_head', 'hide_login_nav');
function hide_login_nav()
{
    echo '<style>#nav{display:none}</style>';
}

function custom_paginate_comments_links($args = array())
{
    global $wp_rewrite;
    if (!is_singular())
        return;

    $page = get_query_var('cpage');

    if (!$page)
        $page = 1;
    $max_page = get_comment_pages_count();
    //changed add_fragment from original #comments -> #contents to show 1 paginated page and to focus page on top of the page
    $defaults = array(
        'base' => add_query_arg('cpage', '%#%'),
        'format' => '',
        'total' => $max_page,
        'current' => $page,
        'echo' => true,
        'add_fragment' => '#contents'
    );
    if ($wp_rewrite->using_permalinks())
        $defaults['base'] = user_trailingslashit(trailingslashit(get_permalink()) . $wp_rewrite->comments_pagination_base . '-%#%', 'commentpaged');

    $args = wp_parse_args($args, $defaults);
    $page_links = paginate_links($args);

    if ($args['echo'])
        echo $page_links;
    else
        return $page_links;
}
add_filter('paginate_comments_links', 'custom_paginate_comments_links');


function add_custom_role($bbp_roles)
{

    $bbp_roles['bbp_megaligaParticipant'] = array(
        'name' => 'megaligaParticipant',
        'capabilities' => bbp_get_caps_for_role(bbp_get_participant_role()) // the same capabilities as participants
    );

    return $bbp_roles;
}
add_filter('bbp_get_dynamic_roles', 'add_custom_role', 1);

if (!function_exists('custom_reverse_comments')) {
    function custom_reverse_comments($comments)
    {
        return array_reverse($comments);
    }
}
add_filter('comments_array', 'custom_reverse_comments');

function my_login_logo_one()
{
    ?>
<style type="text/css">
    body.login div#login h1 a {
        background-image: url(http://megaliga.eu/wp-content/uploads/2018/02/logo.png);
        padding-bottom: 60px;
        background-size: 151px 56px;
        width: 151px;
        height: 56px;
    }
</style>
<?php 
}
add_action('login_enqueue_scripts', 'my_login_logo_one');

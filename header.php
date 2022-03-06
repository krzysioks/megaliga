<?php

/**
 * The template for displaying the header
 *
 * Displays all of the head element and everything up until the page header div.
 *
 * @package Hestia
 * @since Hestia 1.0
 */
$wrapper_div_classes = 'wrapper ';
if (is_single()) {
	$wrapper_div_classes .= join(' ', get_post_class());
}

$layout               = apply_filters('hestia_header_layout', get_theme_mod('hestia_header_layout', 'default'));
$disabled_frontpage   = get_theme_mod('disable_frontpage_sections', false);
$wrapper_div_classes .=
	(
		(is_front_page() && !is_page_template() && !is_home() && false === (bool) $disabled_frontpage) ||
		(class_exists('WooCommerce', false) && (is_product() || is_product_category())) ||
		(is_archive() && (class_exists('WooCommerce', false) && !is_shop()))
	) ? '' : ' ' . $layout . ' ';

$header_class = '';
$hide_top_bar = get_theme_mod('hestia_top_bar_hide', true);
if ((bool) $hide_top_bar === false) {
	$header_class .= 'header-with-topbar';
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset='<?php bloginfo('charset'); ?>'>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<?php if (is_singular() && pings_open(get_queried_object())) : ?>
		<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>">
	<?php endif; ?>
	<?php wp_head(); ?>
</head>

<body onload="megaligaOnLoad()" <?php body_class(); ?>>

	<?php

	// login modal
	$loginFormProps = array(
		'label_username'    => 'Użytkownik',
		'label_password' => 'Hasło',
		'label_remember'      => 'zapamiętaj mnie',
		'label_log_in' => 'Zaloguj'
	);

	echo '<div id="megaligaLoginModal" class="megaliga-modal">';
	echo "	<div class='megaliga-modal__content'>";
	echo '		<div class="modal-header displayFlex flexDirectionRow">';
	echo '			<div class="modal-title__text">';
	echo    '			<h5 class="modal-title modal-title__lg">Logowanie do megaligi</h5>';
	echo    '			<h5 class="modal-title modal-title__sm">Logowanie</h5>';
	echo '			</div>';
	echo '			<div class="modal-title__close">';
	echo    '			<button type="button" class="close" onclick="closeLoginModal()" data-dismiss="modal" aria-label="Close">';
	echo      '				<span aria-hidden="true">&times;</span>';
	echo    '			</button>';
	echo '			</div>';
	echo  '		</div>';
	echo '		<div class="megaliga-login-form">';
	wp_login_form($loginFormProps);
	echo '		</div>';
	echo '	</div>';
	echo '</div>';
	?>

	<?php wp_body_open(); ?>
	<div class="<?php echo esc_attr($wrapper_div_classes); ?>">
		<header class="header <?php echo esc_attr($header_class); ?>">
			<?php
			hestia_before_header_trigger();
			do_action('hestia_do_top_bar');
			do_action('hestia_do_header');
			hestia_after_header_trigger();
			?>
		</header>
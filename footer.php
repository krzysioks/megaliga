<?php

/**
 * The template for displaying the footer
 *
 * Contains the closing of the "wrapper" div and all content after.
 *
 * @package Hestia
 * @since Hestia 1.0
 */
?>
<?php do_action('hestia_do_footer'); ?>
</div>
</div>
<?php wp_footer(); ?>
<script type="text/javascript">
	function closeLoginModal() {
		var loginModal = document.getElementById('megaligaLoginModal');
		loginModal.style.display = "none";
	}
</script>
</body>

</html>
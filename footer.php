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

	function megaligaOnLoad() {
		var loginMenuItem = document.querySelector('#menu-item-2283 a[title="Zaloguj"]');
		var loginModal = document.getElementById('megaligaLoginModal');

		if (loginMenuItem) {
			loginMenuItem.setAttribute('href', '#');
			loginMenuItem.onclick = function() {
				loginModal.style.display = "block";
			}
		}

		var copyrightItem = document.querySelector('div.copyright');
		copyrightItem.classList.remove("pull-right");
	}

	window.onclick = function(event) {
		var loginModal = document.getElementById('megaligaLoginModal');
		if (event.target == loginModal) {
			loginModal.style.display = "none";
		}
	}
</script>
</body>

</html>
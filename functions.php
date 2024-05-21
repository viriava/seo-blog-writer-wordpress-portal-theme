<?php

require_once get_stylesheet_directory() . '/env.php';
require_once get_stylesheet_directory() . '/actlys/actlys.php';

function kadence_child_enqueue_styles() {
	$parent_style = 'kadence'; // This is the handle used for the parent theme's stylesheet

	wp_enqueue_style($parent_style, get_template_directory_uri() . '/style.css');
	wp_enqueue_style('kadence-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		array($parent_style),
		filemtime(__DIR__.'/style.css')
	);
}
add_action('wp_enqueue_scripts', 'kadence_child_enqueue_styles');

add_action('template_redirect', 'actlys_redirect_account_setup_to_licenses');
function actlys_redirect_account_setup_to_licenses() {
	// Check if the user is logged in and if the current page is 'account-setup'
	if (is_user_logged_in() && is_page('account-setup')) {
		// Redirect to /licenses/
		wp_redirect(home_url('/licenses/'));
		exit; // Ensure no further code is executed after the redirect
	}
}
?>
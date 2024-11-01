<?php
/**
 * This file is a template.
 *
 * @author     Alessio Catania
 * @since      0.1.0
 * @package    Wubtitle\Dashboard\Templates
 */

/**
 * This template displays the update plan page.
 */

if ( ! defined( 'WP_ADMIN' ) ) {
	define( 'WP_ADMIN', true );
}
wp_cache_delete( 'wubtitle_plan', 'options' );
wp_cache_delete( 'wubtitle_free', 'options' );
wp_cache_delete( 'wubtitle_plan_rank', 'options' );
wp_cache_delete( 'wubtitle_is_first_month', 'options' );

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Update Payment</title>
</head>
<body>
	<div id="update-form"></div>
	<?php wp_footer(); ?>
</body>
</html>

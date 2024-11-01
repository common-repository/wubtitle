<?php
/**
 * This file is a template.
 *
 * @author     Nicola Palermo
 * @since      0.1.0
 * @package    Wubtitle\Dashboar\Templates
 */

/**
 * This template displays cancel page.
 */
if ( ! defined( 'WP_ADMIN' ) ) {
	define( 'WP_ADMIN', true );
}

require WUBTITLE_DIR . 'includes/Dashboard/Templates/plans_array.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Cancel subscription</title>
</head>
<body>
	<div class="container">
		<div class="row">
			<div class="column">
				<div class="unsubscribe-section">
					<h1 class="title"><?php echo esc_html_e( 'Are you sure you want to unsubscribe?', 'wubtitle' ); ?></h1>
					<p><?php echo esc_html_e( 'Are you sure you want to cancel your subscription? If you choose to continue, when the subscription expires your plan will return to free version and you will lose all the additional features', 'wubtitle' ); ?></p>
					<div class="buttons">
						<div class="button unsubscribe" id="unsubscribeButton"><?php echo esc_html_e( 'Return to free version', 'wubtitle' ); ?></div>
						<div class="button" id="close"><?php echo esc_html_e( 'Forget it', 'wubtitle' ); ?></div>
					</div>
					<div id="message"><!-- From JS --></div>
				</div>
			</div>
		</div>
		<h1 class="title"><?php echo esc_html_e( 'Or choose another plan', 'wubtitle' ); ?></h1>
		<div class="row">
		<?php
		if ( isset( $plans ) ) :
			foreach ( $plans as $key_plan => $plan ) :
				?>
			<div class="column one-quarter">
				<div class="card <?php echo $plan['zoom'] ? 'zoom' : ''; ?>">
					<div class="card__header">
						<h2 class="card__title">
							<?php echo esc_html( $plan['name'] ); ?>
						</h2>
						<img class="card__logo" src="<?php echo esc_url( WUBTITLE_URL . 'assets/img/' . $plan['icon'] ); ?>">
					</div>
					<div class="card__price">
						<?php echo esc_html_e( 'Per month', 'wubtitle' ); ?>
						<p class="price">
							<?php
							if ( isset( $price_info_object ) ) {
								echo esc_html( '€' . $price_info_object[ $key_plan ]->price );
							}
							?>
						</p>
					</div>
					<?php
					foreach ( $plan['features'] as $key => $feature ) :
						?>
					<p class="card__features">
						<span><?php echo esc_html( $key ); ?></span>
						<?php echo esc_html( $feature ); ?>
					</p>
						<?php
					endforeach;
					?>
					<div class="<?php echo esc_attr( $plan['class_button'] ); ?>" plan="<?php echo esc_html( $key_plan ); ?>">
						<?php echo esc_html( $plan['message_button'] ); ?>
					</div>
				</div>
			</div>
				<?php
		endforeach;
			?>
		<ul class="features-list">
			<li>
				<p><strong><?php echo esc_html_e( 'Supported languages:', 'wubtitle' ); ?> </strong></p>
				<?php echo esc_html( $plans[0]['dotlistV4'] ); ?>
			</li>
		</ul>
			<?php
		endif;
		?>
		</div>
	</div>
	<?php wp_footer(); ?>
</body>
</html>

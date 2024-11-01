<?php
/**
 * Confirm plan downgrade template.
 *
 * @author     Alessio Catania
 * @since      1.0.0
 * @package    Wubtitle\Dashboar\Templates
 */

/**
 * Downgrade page template.
 */
require WUBTITLE_DIR . 'includes/Dashboard/Templates/plans_array.php';
$amount_preview = isset( $amount_preview ) ? number_format( - (float) $amount_preview, 2 ) : 0.00;
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Payment</title>
</head>
<body>
	<div class="container">
		<h1 class="title"><?php esc_html_e( 'Subscription plan downgrade', 'wubtitle' ); ?></h1>
		<p class="paragraph-center"> <?php esc_html_e( 'Downgrading now, you will earn a credit that will be billed to you at the next charge', 'wubtitle' ); ?> </p>
		<div class="row margin_medium">
			<?php if ( isset( $plans, $wanted_plan_rank ) ) : ?> 
			<div class="column one-quarter">
				<img class="card_plan" src="<?php echo esc_url( WUBTITLE_URL . 'assets/img/' . $plans[ $wanted_plan_rank ]['icon'] ); ?>">
				<h1 class="title" >  <?php echo esc_html( $plans[ $wanted_plan_rank ]['name'] ); ?> </h1>
			</div>
			<?php endif; ?>
			<div class="column one-quarter">
				<h1 style="text-align:center; margin-top:64px;"> <span class="refund"><?php echo esc_html( $amount_preview . '€ ' . __( 'credit earnings', 'wubtitle' ) ); ?></span> </h1>
		<img class="arrowdown" src="<?php echo esc_url( WUBTITLE_URL . 'assets/img/arrowdown.svg' ); ?>">
			</div>
			<?php if ( isset( $plans, $current_rank ) ) : ?>
			<div class="column one-quarter">
				<img class="card_plan" src="<?php echo esc_url( WUBTITLE_URL . 'assets/img/' . $plans[ $current_rank ]['icon'] ); ?>">
				<h1 class="title" > <?php echo esc_html( $plans[ $current_rank ]['name'] ); ?> </h1>
			</div>
			<?php endif; ?>
		</div>
		<div class="confirm-change-section">
			<p class="confirm-paragraph"><?php esc_html_e( 'Domain:', 'wubtitle' ); ?> <strong> <?php echo ' ' . esc_html( get_option( 'siteurl' ) ); ?> </strong> </p>
			<p class="confirm-paragraph"> <?php esc_html_e( 'The subtitles already created and the minutes already used will be counted on the new subscription plan', 'wubtitle' ); ?> </p>
			<div class="buttons">
				<div class="button unsubscribe" id="confirm_changes"><?php esc_html_e( 'Downgrade Now', 'wubtitle' ); ?></div>
				<div class="button" id="forget" ><?php esc_html_e( 'Forget it', 'wubtitle' ); ?></div>
			</div>
		</div>
	</div>
	<?php wp_footer(); ?>
</body>
</html>

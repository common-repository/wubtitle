<?php
/**
 * This file is a array template.
 *
 * @author     Nicola Palermo
 * @since      0.1.0
 * @package    Wubtitle\Dashboar\Templates
 */

/**
 * This array describes all avaiable plans for users.
 */

wp_cache_delete( 'wubtitle_plan', 'options' );
wp_cache_delete( 'wubtitle_free', 'options' );
wp_cache_delete( 'wubtitle_plan_rank', 'options' );
wp_cache_delete( 'wubtitle_all_plans', 'options' );
wp_cache_delete( 'wubtitle_is_first_month', 'options' );

$plans          = get_option( 'wubtitle_all_plans' );
$current_plan   = get_option( 'wubtitle_plan' );
$current_rank   = get_option( 'wubtitle_plan_rank' );
$is_first_month = get_option( 'wubtitle_is_first_month' );

$disable_downgrade_message = __( 'Unable to select this plan during the first month of subscription for current plan', 'wubtitle' );

foreach ( $plans as $key => $plan ) {
	$max_lenght                      = $plans[ $key ]['totalSeconds'] < 3600 ? date_i18n( 'i', $plans[ $key ]['totalSeconds'] ) . ' ' . __( 'Minutes', 'wubtitle' ) : date_i18n( 'g', $plans[ $key ]['totalSeconds'] ) . ' ' . __( 'Hours', 'wubtitle' );
	$video_formats                   = array( 'all' ) === $plans[ $key ]['supportedFormats'] ? __( 'All WordPress video formats', 'wubtitle' ) : 'MP4';
	$plans[ $key ]['current_plan']   = false;
	$plans[ $key ]['zoom']           = false;
	$plans[ $key ]['class_button']   = 'button-choose-plan';
	$plans[ $key ]['message_button'] = __( 'Choose this plan', 'wubtitle' );
	$plans[ $key ]['features']       = array(
		__( 'Number of video', 'wubtitle' )        => $plans[ $key ]['totalJobs'],
		__( 'Total length of videos', 'wubtitle' ) => $max_lenght,
		__( 'Video format allowed', 'wubtitle' )   => $video_formats,
	);
	if ( $is_first_month && $key < $current_rank ) {
		$plans[ $key ]['class_button']   = 'disable-downgrade';
		$plans[ $key ]['message_button'] = $disable_downgrade_message;
	}
	if ( $key === $current_rank + 1 ) {
		$plans[ $key ]['zoom'] = true;
	}
}

$plans[ $current_rank ]['current_plan']   = true;
$plans[ $current_rank ]['class_button']   = 'current-plan';
$plans[ $current_rank ]['message_button'] = __( 'Your plan', 'wubtitle' );

<?php

add_filter( 'badgeos_achievement_data_meta_box_fields', function ( $fields ) {
	$prefix   = "_badgeos_";
	$fields[] = array(
		'name' => __( 'Time limit', 'timelimit-add-on-for-badgeos' ),
		'desc' => ' ' . __( 'Number of minutes this badge cannot be earned after it has been awarded. (set to 0 for unlimited).', 'timelimit-add-on-for-badgeos' ),
		'id'   => $prefix . 'time_limit',
		'type' => 'text_small',
		'std'  => '0',
	);

	return $fields;
} );

add_filter( 'user_deserves_achievement', function ( $return, $user_id, $achievement_id ) {

	// If we're not working with a step, bail
	if ( 'step' != get_post_type( $achievement_id ) ) {
		return $return;
	}

	// grab the achievement
	$parent_achievement = badgeos_get_parent_of_achievement( $achievement_id );
	if ( ! $parent_achievement ) {
		return $return;
	}
	$achievement_id = $parent_achievement->ID;

	$timelimit = absint( get_post_meta( $achievement_id, '_badgeos_time_limit', true ) );

	$last_activity = badgeos_achievement_last_user_activity( absint( $achievement_id ), absint( $user_id ) );
	if ( $timelimit && $last_activity &&
	     ( time() - $last_activity ) < ( $timelimit * 60 )
	) {
		return false;
	}

	return $return;
}, 15, 3 );
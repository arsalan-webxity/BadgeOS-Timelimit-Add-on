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

/* Removing filter function to override and add the check of time limit. */
remove_filter( 'badgeos_notifications_submission_approved_messages', 'badgeos_set_submission_status_submission_approved', 10 );
add_filter('badgeos_notifications_submission_approved_messages', function ( $messages, $args ) {
	
	$achievement_id = isset( $args['achievement_id'] ) ? $args['achievement_id'] : 0;
	$user_id        = isset( $args['user_id'] ) ? $args['user_id'] : 0;

	if( !$achievement_id || !$user_id )
		return false;

	$timelimit = absint( get_post_meta( $achievement_id, '_badgeos_time_limit', true ) );

	$last_activity = badgeos_achievement_last_user_activity( absint( $achievement_id ), absint( $user_id ) );
	if ( $timelimit && $last_activity &&
	     ( time() - $last_activity ) > ( $timelimit * 60 )
	) {
		// Award achievement
		badgeos_award_achievement_to_user( $args[ 'achievement_id' ], $args[ 'user_id' ] );
	}	

	// Check if user can be notified
	if ( !badgeos_can_notify_user( $args[ 'user_data' ]->ID ) ) {
		return $messages;
	}

	$email = $args[ 'user_data' ]->user_email;

	$message_id = 'badgeos_submission_approved';

	if ( $args[ 'auto' ] ) {
		$message_id = 'badgeos_submission_auto_approved';

		$email = $args[ 'submission_email_addresses' ];

		$subject = sprintf( __( 'Approved Submission: %1$s from %2$s', 'badgeos' ), get_the_title( $args[ 'achievement_id' ] ), $args[ 'user_data' ]->display_name );

		// set the email message
		$message = sprintf( __( 'A new submission has been received and auto-approved:

			In response to: %1$s
			Submitted by: %2$s

			To view all submissions, including this one, visit: %3$s', 'badgeos' ),
			get_the_title( $args[ 'achievement_id' ] ),
			$args[ 'user_data' ]->display_name,
			admin_url( 'edit.php?post_type=submission' )
		);
	}
	else {
		$subject = sprintf( __( 'Submission Approved: %s', 'badgeos' ), get_the_title( $args[ 'achievement_id' ] ) );

		// set the email message
		$message = sprintf( __( 'Your submission has been approved:

			In response to: %1$s
			Submitted by: %2$s
			%3$s', 'badgeos' ),
			get_the_title( $args[ 'achievement_id' ] ),
			$args[ 'user_data' ]->display_name,
			get_permalink( $args[ 'achievement_id' ] )
		);
	}

	$messages[ $message_id ] = array(
		'email' => $email,
		'subject' => $subject,
		'message' => $message
	);

	return $messages;
}, 15, 2);
<?php
namespace ET\Emails;


if ( ! defined( 'ABSPATH' ) ) { exit; }


class Mailer {
public function hooks() {
add_action( 'et/task_submitted', [ $this, 'notify_manager_on_submit' ] );
add_action( 'et/task_status_changed', [ $this, 'notify_employee_on_status' ], 10, 3 );
}


public function notify_manager_on_submit( $task_id ) {
$manager_emails = $this->get_manager_emails();
if ( empty( $manager_emails ) ) { return; }
$subject = __( 'New task submitted', 'etracker' );
$body = sprintf( __( 'A new task (ID #%d) has been submitted today. Please review and approve.', 'etracker' ), (int) $task_id );
wp_mail( $manager_emails, $subject, $body );
}


public function notify_employee_on_status( $task_id, $status, $comment ) {
global $wpdb; $table = $wpdb->prefix . 'et_tasks';
$user_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM $table WHERE id=%d", $task_id ) );
$user = get_user_by( 'id', $user_id );
if ( ! $user ) { return; }
$subject = __( 'Your task was updated', 'etracker' );
$body = sprintf( __( 'Your task (ID #%1$d) status is now %2$s. Manager comment: %3$s', 'etracker' ), (int) $task_id, sanitize_text_field( $status ), wp_strip_all_tags( (string) $comment ) );
wp_mail( $user->user_email, $subject, $body );
}


private function get_manager_emails() : array {
$users = get_users( [ 'role' => 'et_manager', 'fields' => [ 'user_email' ] ] );
return array_map( static function( $u ) { return $u->user_email; }, $users );
}
}
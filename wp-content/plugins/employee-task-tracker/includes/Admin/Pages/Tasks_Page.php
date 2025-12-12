<?php
namespace ET\Admin\Pages;


if ( ! defined( 'ABSPATH' ) ) { exit; }


class Tasks_Page {
public function render() {
if ( ! current_user_can( 'et_manage_tasks' ) ) { wp_die( esc_html__( 'Access denied', 'etracker' ) ); }
$this->handle_actions();
$this->table();
}


private function handle_actions() {
if ( empty( $_POST['et_task_action'] ) ) { return; }
check_admin_referer( 'et_task_action', 'et_task_nonce' );
if ( ! current_user_can( 'et_approve_tasks' ) ) { return; }
global $wpdb; $table = $wpdb->prefix . 'et_tasks';
$task_id = intval( $_POST['task_id'] );
$comment = wp_kses_post( wp_unslash( $_POST['manager_comment'] ?? '' ) );
$status = in_array( $_POST['et_task_action'], [ 'approved', 'rejected' ], true ) ? $_POST['et_task_action'] : 'submitted';
$wpdb->update( $table, [ 'status' => $status, 'manager_comment' => $comment, 'updated_at' => current_time( 'mysql' ) ], [ 'id' => $task_id ] );
do_action( 'et/task_status_changed', $task_id, $status, $comment );
}


private function table() {
	global $wpdb; $table = $wpdb->prefix . 'et_tasks';
	$date = isset( $_GET['date'] ) ? sanitize_text_field( wp_unslash( $_GET['date'] ) ) : current_time( 'Y-m-d' );
	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT t.*, u.display_name, p.name as project_name
		 FROM $table t
		 JOIN {$wpdb->users} u ON u.ID = t.user_id
		 JOIN {$wpdb->prefix}et_projects p ON p.id = t.project_id
		 WHERE t.task_date = %s
		 ORDER BY t.id DESC", $date
	) );

	echo '<div class="wrap et-wrap">';
	echo '<h1>' . esc_html__( 'Tasks', 'etracker' ) . '</h1>';
	echo '<form method="get"><input type="hidden" name="page" value="et-tasks"><label>' . esc_html__( 'Date', 'etracker' ) . ' <input type="date" name="date" value="' . esc_attr( $date ) . '"></label> <button class="button">' . esc_html__( 'Filter', 'etracker' ) . '</button></form>';

	echo '<table class="widefat fixed striped"><thead><tr>';
	echo '<th>ID</th>';
	echo '<th>' . esc_html__( 'Employee', 'etracker' ) . '</th>';
	echo '<th>' . esc_html__( 'Project', 'etracker' ) . '</th>';
	echo '<th>' . esc_html__( 'Date', 'etracker' ) . '</th>';
	echo '<th>' . esc_html__( 'Title', 'etracker' ) . '</th>';
	echo '<th>' . esc_html__( 'Description', 'etracker' ) . '</th>'; // NEW
	echo '<th>' . esc_html__( 'Hours', 'etracker' ) . '</th>';
	echo '<th>' . esc_html__( 'Status', 'etracker' ) . '</th>';
	echo '<th>' . esc_html__( 'Manager Comment', 'etracker' ) . '</th>';
	echo '<th>' . esc_html__( 'Actions', 'etracker' ) . '</th>';
	echo '</tr></thead><tbody>';

	foreach ( $rows as $r ) {
		echo '<tr>';
		echo '<td>' . intval( $r->id ) . '</td>';
		echo '<td>' . esc_html( $r->display_name ) . '</td>';
		echo '<td>' . esc_html( $r->project_name ) . '</td>';
		echo '<td>' . esc_html( $r->task_date ) . '</td>';
		echo '<td>' . esc_html( $r->title ) . '</td>';

		// NEW: safe + trimmed description
		$desc = wp_strip_all_tags( (string) $r->description );
		if ( strlen( $desc ) > 140 ) { $desc = substr( $desc, 0, 140 ) . '…'; }
		echo '<td title="' . esc_attr( wp_strip_all_tags( (string) $r->description ) ) . '">' . esc_html( $desc ) . '</td>';

		echo '<td>' . esc_html( $r->hours ) . '</td>';
		echo '<td>' . esc_html( ucfirst( $r->status ) ) . '</td>';
		echo '<td>' . esc_html( wp_strip_all_tags( $r->manager_comment ) ) . '</td>';

		echo '<td>';
		if ( current_user_can( 'et_approve_tasks' ) ) {
			echo '<form method="post" style="display:flex;gap:6px;align-items:center">';
			wp_nonce_field( 'et_task_action', 'et_task_nonce' );
			echo '<input type="hidden" name="task_id" value="' . intval( $r->id ) . '">';
			echo '<input type="text" name="manager_comment" placeholder="' . esc_attr__( 'Add comment', 'etracker' ) . '" value="' . esc_attr( $r->manager_comment ) . '" />';
			echo '<button name="et_task_action" value="approved" class="button button-primary">' . esc_html__( 'Approve', 'etracker' ) . '</button>';
			echo '<button name="et_task_action" value="rejected" class="button">' . esc_html__( 'Reject', 'etracker' ) . '</button>';
			echo '</form>';
		}
		echo '</td>';

		echo '</tr>';
	}
	echo '</tbody></table></div>';
}

}
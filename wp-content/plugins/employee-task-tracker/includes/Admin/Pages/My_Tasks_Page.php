<?php
namespace ET\Admin\Pages;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class My_Tasks_Page {

	public function render() {
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html__( 'Please log in.', 'etracker' ) );
		}

		$user = wp_get_current_user();
		// Only employees (allow admin if needed)
		if ( ! in_array( 'employee', (array) $user->roles, true ) && ! current_user_can( 'administrator' ) ) {
			wp_die( esc_html__( 'Access denied.', 'etracker' ) );
		}

		$this->handle_save( $user->ID );

		echo '<div class="wrap et-wrap">';
		echo '<h1>' . esc_html__( 'My Tasks', 'etracker' ) . '</h1>';

		$today   = current_time( 'Y-m-d' );
		$edit_id = isset( $_GET['edit_task'] ) ? intval( $_GET['edit_task'] ) : 0;
		$task    = $edit_id ? $this->get_user_task( $user->ID, $edit_id ) : null;

		if ( $task && $task->task_date !== $today ) {
			echo '<div class="et-alert et-alert--error">' . esc_html__( "You can edit only today's task.", 'etracker' ) . '</div>';
			$task = null;
		}

		$this->render_form( $today, $task );
		$this->render_table( $user->ID, $today );

		echo '</div>';
	}

	private function render_form( string $today, $task = null ) : void {
		global $wpdb; $projects_table = $wpdb->prefix . 'et_projects';
		$projects = $wpdb->get_results( "SELECT id, name FROM $projects_table WHERE status='active' ORDER BY name ASC" );
		$current_project = $task ? (int) $task->project_id : 0;

		echo '<div class="et-card">';
		echo '<h2>' . ( $task ? esc_html__( 'Update Today\'s Task', 'etracker' ) : esc_html__( 'Add Today\'s Task', 'etracker' ) ) . '</h2>';
		echo '<form method="post">';
		wp_nonce_field( 'et_my_task_save', 'et_my_task_nonce' );
		echo '<input type="hidden" name="task_id" value="' . esc_attr( $task ? (int) $task->id : 0 ) . '">';

		echo '<p><label>' . esc_html__( 'Date', 'etracker' ) . ' ';
		echo '<input type="date" name="task_date" value="' . esc_attr( $today ) . '" readonly>';
		echo '</label></p>';

		echo '<p><label>' . esc_html__( 'Project', 'etracker' ) . ' ';
		echo '<select name="project_id" required>';
		echo '<option value="">' . esc_html__( 'Select project', 'etracker' ) . '</option>';
		foreach ( $projects as $p ) {
			echo '<option value="' . intval( $p->id ) . '" ' . selected( $current_project, (int) $p->id, false ) . '>' . esc_html( $p->name ) . '</option>';
		}
		echo '</select></label></p>';

		echo '<p><label>' . esc_html__( 'Title', 'etracker' ) . ' ';
		echo '<input type="text" name="title" class="regular-text" maxlength="190" value="' . esc_attr( $task ? $task->title : '' ) . '" required>';
		echo '</label></p>';

		echo '<p><label>' . esc_html__( 'Description', 'etracker' ) . ' ';
		echo '<textarea name="description" rows="4">' . esc_textarea( $task ? $task->description : '' ) . '</textarea>';
		echo '</label></p>';

		echo '<p><label>' . esc_html__( 'Hours (e.g., 7.5)', 'etracker' ) . ' ';
		echo '<input type="number" name="hours" min="0" max="24" step="0.25" value="' . esc_attr( $task ? $task->hours : '' ) . '">';
		echo '</label></p>';

		echo '<p><button class="button button-primary">' . ( $task ? esc_html__( 'Update Task', 'etracker' ) : esc_html__( 'Submit Task', 'etracker' ) ) . '</button></p>';

		echo '</form>';
		echo '</div>';
	}

	// private function render_table( int $user_id, string $today ) : void {
	// 	global $wpdb;
	// 	$tasks_table    = $wpdb->prefix . 'et_tasks';
	// 	$projects_table = $wpdb->prefix . 'et_projects';

	// 	$rows = $wpdb->get_results( $wpdb->prepare(
	// 		"SELECT t.*, p.name AS project_name
	// 		 FROM $tasks_table t
	// 		 JOIN $projects_table p ON p.id = t.project_id
	// 		 WHERE t.user_id = %d
	// 		 ORDER BY t.task_date DESC, t.id DESC",
	// 		$user_id
	// 	) );

	// 	echo '<div class="et-card">';
	// 	echo '<h2>' . esc_html__( 'All My Tasks', 'etracker' ) . '</h2>';

	// 	if ( ! $rows ) {
	// 		echo '<p>' . esc_html__( 'No tasks yet.', 'etracker' ) . '</p></div>';
	// 		return;
	// 	}

	// 	echo '<table class="widefat fixed striped"><thead><tr>';
	// 	echo '<th>' . esc_html__( 'Date', 'etracker' ) . '</th>';
	// 	echo '<th>' . esc_html__( 'Project', 'etracker' ) . '</th>';
	// 	echo '<th>' . esc_html__( 'Title', 'etracker' ) . '</th>';
	// 	echo '<th>' . esc_html__( 'Hours', 'etracker' ) . '</th>';
	// 	echo '<th>' . esc_html__( 'Status', 'etracker' ) . '</th>';
	// 	echo '<th>' . esc_html__( 'Manager Comment', 'etracker' ) . '</th>';
	// 	echo '<th>' . esc_html__( 'Actions', 'etracker' ) . '</th>';
	// 	echo '</tr></thead><tbody>';

	// 	foreach ( $rows as $r ) {
	// 		echo '<tr>';
	// 		echo '<td>' . esc_html( $r->task_date ) . '</td>';
	// 		echo '<td>' . esc_html( $r->project_name ) . '</td>';
	// 		echo '<td>' . esc_html( $r->title ) . '</td>';
	// 		echo '<td>' . esc_html( $r->hours ) . '</td>';
	// 		echo '<td>' . esc_html( ucfirst( $r->status ) ) . '</td>';
	// 		echo '<td>' . esc_html( wp_strip_all_tags( (string) $r->manager_comment ) ) . '</td>';
	// 		echo '<td>';
	// 		if ( $r->task_date === $today ) {
	// 			$edit_url = esc_url( add_query_arg( 'page', 'et-my-tasks', admin_url( 'admin.php' ) ) );
	// 			$edit_url = esc_url( add_query_arg( 'edit_task', intval( $r->id ), $edit_url ) );
	// 			echo '<a class="button" href="' . $edit_url . '">' . esc_html__( "Edit Today's Task", 'etracker' ) . '</a>';
	// 		} else {
	// 			echo '<em>' . esc_html__( 'Locked', 'etracker' ) . '</em>';
	// 		}
	// 		echo '</td>';
	// 		echo '</tr>';
	// 	}

	// 	echo '</tbody></table></div>';
	// }

    private function render_table( int $user_id, string $today ) : void {
	global $wpdb;
	$tasks_table    = $wpdb->prefix . 'et_tasks';
	$projects_table = $wpdb->prefix . 'et_projects';

	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT t.*, p.name AS project_name
		 FROM $tasks_table t
		 JOIN $projects_table p ON p.id = t.project_id
		 WHERE t.user_id = %d
		 ORDER BY t.task_date DESC, t.id DESC",
		$user_id
	) );

	echo '<div class="et-card">';
	echo '<h2>' . esc_html__( 'All My Tasks', 'etracker' ) . '</h2>';

	if ( ! $rows ) {
	 echo '<p>' . esc_html__( 'No tasks yet.', 'etracker' ) . '</p></div>';
	 return;
	}

	echo '<table class="widefat fixed striped"><thead><tr>';
	echo '<th>' . esc_html__( 'Date', 'etracker' ) . '</th>';
	echo '<th>' . esc_html__( 'Project', 'etracker' ) . '</th>';
	echo '<th>' . esc_html__( 'Title', 'etracker' ) . '</th>';
	echo '<th>' . esc_html__( 'Description', 'etracker' ) . '</th>'; // NEW
	echo '<th>' . esc_html__( 'Hours', 'etracker' ) . '</th>';
	echo '<th>' . esc_html__( 'Status', 'etracker' ) . '</th>';
	echo '<th>' . esc_html__( 'Manager Comment', 'etracker' ) . '</th>';
	echo '<th>' . esc_html__( 'Actions', 'etracker' ) . '</th>';
	echo '</tr></thead><tbody>';

	foreach ( $rows as $r ) {
		echo '<tr>';
		echo '<td>' . esc_html( $r->task_date ) . '</td>';
		echo '<td>' . esc_html( $r->project_name ) . '</td>';
		echo '<td>' . esc_html( $r->title ) . '</td>';

		// NEW: safe + trimmed description
		$desc = wp_strip_all_tags( (string) $r->description );
		if ( strlen( $desc ) > 140 ) { $desc = substr( $desc, 0, 140 ) . '…'; }
		echo '<td title="' . esc_attr( wp_strip_all_tags( (string) $r->description ) ) . '">' . esc_html( $desc ) . '</td>';

		echo '<td>' . esc_html( $r->hours ) . '</td>';
		echo '<td>' . esc_html( ucfirst( $r->status ) ) . '</td>';
		echo '<td>' . esc_html( wp_strip_all_tags( (string) $r->manager_comment ) ) . '</td>';
		echo '<td>';
		if ( $r->task_date === $today ) {
			$edit_url = esc_url( add_query_arg( 'page', 'et-my-tasks', admin_url( 'admin.php' ) ) );
			$edit_url = esc_url( add_query_arg( 'edit_task', intval( $r->id ), $edit_url ) );
			echo '<a class="button" href="' . $edit_url . '">' . esc_html__( "Edit Today's Task", 'etracker' ) . '</a>';
		} else {
			echo '<em>' . esc_html__( 'Locked', 'etracker' ) . '</em>';
		}
		echo '</td>';
		echo '</tr>';
	}

	echo '</tbody></table></div>';
}


	private function handle_save( int $user_id ) : void {
		if ( empty( $_POST['et_my_task_nonce'] ) || ! wp_verify_nonce( $_POST['et_my_task_nonce'], 'et_my_task_save' ) ) {
			return;
		}

		$today     = current_time( 'Y-m-d' );
		$task_date = sanitize_text_field( wp_unslash( $_POST['task_date'] ?? $today ) );
		$task_id   = intval( $_POST['task_id'] ?? 0 );

		if ( $task_date !== $today ) {
			echo '<div class="et-alert et-alert--error">' . esc_html__( 'You can only submit or edit tasks for today.', 'etracker' ) . '</div>';
			return;
		}

		global $wpdb; $table = $wpdb->prefix . 'et_tasks';
		$project_id = intval( $_POST['project_id'] ?? 0 );
		$title      = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
		$desc       = wp_kses_post( wp_unslash( $_POST['description'] ?? '' ) );
		$hours      = isset( $_POST['hours'] ) ? floatval( $_POST['hours'] ) : null;

		$data = [
			'user_id'     => $user_id,
			'project_id'  => $project_id,
			'task_date'   => $today,
			'title'       => $title,
			'description' => $desc,
			'hours'       => $hours,
			'updated_at'  => current_time( 'mysql' ),
		];

		if ( $task_id ) {
			// Ensure it's today's task and belongs to the current user
			$owned_today = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM $table WHERE id=%d AND user_id=%d AND task_date=%s",
				$task_id, $user_id, $today
			) );
			if ( ! $owned_today ) {
				echo '<div class="et-alert et-alert--error">' . esc_html__( 'Not allowed.', 'etracker' ) . '</div>';
				return;
			}
			$wpdb->update( $table, $data, [ 'id' => $task_id ] );
			do_action( 'et/task_submitted', $task_id );
			echo '<div class="et-alert et-alert--success">' . esc_html__( 'Task updated.', 'etracker' ) . '</div>';
			return;
		}

		$data['created_at'] = current_time( 'mysql' );
		$ok = $wpdb->insert( $table, $data );
		if ( $ok ) {
			do_action( 'et/task_submitted', (int) $wpdb->insert_id );
			echo '<div class="et-alert et-alert--success">' . esc_html__( 'Task created.', 'etracker' ) . '</div>';
		} else {
			echo '<div class="et-alert et-alert--error">' . esc_html__( 'Unable to save task.', 'etracker' ) . '</div>';
		}
	}

	private function get_user_task( int $user_id, int $task_id ) {
		global $wpdb; $table = $wpdb->prefix . 'et_tasks';
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $table WHERE id=%d AND user_id=%d",
			$task_id, $user_id
		) );
	}
}

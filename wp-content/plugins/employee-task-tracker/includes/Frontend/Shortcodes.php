<?php
namespace ET\Frontend;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Shortcodes {
	public function hooks() {
		add_shortcode( 'et_task_form', [ $this, 'task_form' ] );   // submit / edit today's task
		add_shortcode( 'et_my_tasks', [ $this, 'my_tasks' ] );     // list all my tasks (edit allowed only for today)
	}

	/**
	 * Task create/update (today only)
	 */
	public function task_form( $atts ) {
		if ( ! is_user_logged_in() ) {
			return '<p>' . esc_html__( 'Please log in to submit tasks.', 'etracker' ) . '</p>';
		}
		$user = wp_get_current_user();
		if ( ! in_array( 'employee', (array) $user->roles, true ) && ! in_array( 'et_manager', (array) $user->roles, true ) && ! current_user_can( 'administrator' ) ) {
			return '<p>' . esc_html__( 'You do not have permission to submit tasks.', 'etracker' ) . '</p>';
		}

		$today   = current_time( 'Y-m-d' );
		$out     = '';
		$task    = null;

		// If we came from My Tasks "Edit" link
		$edit_id = isset( $_GET['edit_task'] ) ? intval( $_GET['edit_task'] ) : 0;
		if ( $edit_id ) {
			$task = $this->get_user_task( $user->ID, $edit_id );
			if ( ! $task ) {
				$out .= '<div class="et-alert et-alert--error">' . esc_html__( 'Task not found.', 'etracker' ) . '</div>';
			} elseif ( $task->task_date !== $today ) {
				$out .= '<div class="et-alert et-alert--error">' . esc_html__( 'You can edit only today\'s task.', 'etracker' ) . '</div>';
				$task = null; // disallow editing
			}
		}

		// Handle submission (create or update), strictly for today
		if ( isset( $_POST['et_task_nonce'] ) && wp_verify_nonce( $_POST['et_task_nonce'], 'et_task_submit' ) ) {
			$task_date = sanitize_text_field( wp_unslash( $_POST['task_date'] ?? $today ) );
			$task_id   = intval( $_POST['task_id'] ?? 0 );
			if ( $task_date !== $today ) {
				$out .= '<div class="et-alert et-alert--error">' . esc_html__( 'You can only submit or edit tasks for today.', 'etracker' ) . '</div>';
			} else {
				$done = $this->save_task( $user->ID, $task_id );
				if ( $done ) {
					$out .= '<div class="et-alert et-alert--success">' . esc_html__( 'Task saved.', 'etracker' ) . '</div>';
				} else {
					$out .= '<div class="et-alert et-alert--error">' . esc_html__( 'Unable to save task.', 'etracker' ) . '</div>';
				}
			}
		}

		$out .= $this->render_form( $today, $task );
		return $out;
	}

	/**
	 * My Tasks list (employee sees all own tasks).
	 * Only today's row will show "Edit Today's Task" button.
	 */
	public function my_tasks() {
		if ( ! is_user_logged_in() ) {
			return '<p>' . esc_html__( 'Please log in to view your tasks.', 'etracker' ) . '</p>';
		}
		$user  = wp_get_current_user();
		$today = current_time( 'Y-m-d' );

		global $wpdb;
		$tasks_table    = $wpdb->prefix . 'et_tasks';
		$projects_table = $wpdb->prefix . 'et_projects';

		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT t.*, p.name AS project_name
			 FROM $tasks_table t
			 JOIN $projects_table p ON p.id = t.project_id
			 WHERE t.user_id = %d
			 ORDER BY t.task_date DESC, t.id DESC",
			$user->ID
		) );

		if ( ! $rows ) {
			return '<div class="et-card"><p>' . esc_html__( 'No tasks yet.', 'etracker' ) . '</p></div>';
		}

		ob_start();
		echo '<div class="et-card">';
		echo '<h3>' . esc_html__( 'My Tasks', 'etracker' ) . '</h3>';
		echo '<table class="widefat fixed striped"><thead><tr>';
		echo '<th>' . esc_html__( 'Date', 'etracker' ) . '</th>';
		echo '<th>' . esc_html__( 'Project', 'etracker' ) . '</th>';
		echo '<th>' . esc_html__( 'Title', 'etracker' ) . '</th>';
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
			echo '<td>' . esc_html( $r->hours ) . '</td>';
			echo '<td>' . esc_html( ucfirst( $r->status ) ) . '</td>';
			echo '<td>' . esc_html( wp_strip_all_tags( (string) $r->manager_comment ) ) . '</td>';
			echo '<td>';
			if ( $r->task_date === $today ) {
				$edit_url = esc_url( add_query_arg( 'edit_task', intval( $r->id ), get_permalink() ) );
				echo '<a class="button" href="' . $edit_url . '">' . esc_html__( 'Edit Today\'s Task', 'etracker' ) . '</a>';
			} else {
				echo '<em>' . esc_html__( 'Locked', 'etracker' ) . '</em>';
			}
			echo '</td>';
			echo '</tr>';
		}

		echo '</tbody></table></div>';
		return (string) ob_get_clean();
	}

	private function render_form( $today, $task = null ) {
		global $wpdb; $projects_table = $wpdb->prefix . 'et_projects';
		$projects = $wpdb->get_results( "SELECT id, name FROM $projects_table WHERE status='active' ORDER BY name ASC" );

		$current_project = $task ? (int) $task->project_id : 0;
		$project_options = '';
		foreach ( $projects as $p ) {
			$selected = selected( $current_project, (int) $p->id, false );
			$project_options .= '<option value="' . intval( $p->id ) . '" ' . $selected . '>' . esc_html( $p->name ) . '</option>';
		}

		$title   = $task ? $task->title       : '';
		$desc    = $task ? $task->description : '';
		$hours   = $task ? $task->hours       : '';
		$task_id = $task ? (int) $task->id     : 0;

		ob_start();
		?>
		<form method="post" class="et-task-form et-card">
			<?php wp_nonce_field( 'et_task_submit', 'et_task_nonce' ); ?>
			<input type="hidden" name="task_id" value="<?php echo esc_attr( $task_id ); ?>">
			<p>
				<label><?php echo esc_html__( 'Date', 'etracker' ); ?>
					<input type="date" name="task_date" value="<?php echo esc_attr( $today ); ?>" readonly>
				</label>
			</p>
			<p>
				<label><?php echo esc_html__( 'Project', 'etracker' ); ?>
					<select name="project_id" required>
						<option value=""><?php echo esc_html__( 'Select project', 'etracker' ); ?></option>
						<?php echo $project_options; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</select>
				</label>
			</p>
			<p>
				<label><?php echo esc_html__( 'Title', 'etracker' ); ?>
					<input type="text" name="title" class="regular-text" maxlength="190" value="<?php echo esc_attr( $title ); ?>" required>
				</label>
			</p>
			<p>
				<label><?php echo esc_html__( 'Description', 'etracker' ); ?>
					<textarea name="description" rows="4"><?php echo esc_textarea( $desc ); ?></textarea>
				</label>
			</p>
			<p>
				<label><?php echo esc_html__( 'Hours (e.g., 7.5)', 'etracker' ); ?>
					<input type="number" name="hours" min="0" max="24" step="0.25" value="<?php echo esc_attr( $hours ); ?>">
				</label>
			</p>
			<p>
				<button class="et-btn et-btn--primary">
					<?php echo $task_id ? esc_html__( 'Update Task', 'etracker' ) : esc_html__( 'Submit Task', 'etracker' ); ?>
				</button>
			</p>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	private function get_user_task( int $user_id, int $task_id ) {
		global $wpdb; $table = $wpdb->prefix . 'et_tasks';
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $table WHERE id=%d AND user_id=%d",
			$task_id, $user_id
		) );
	}

	/**
	 * Save/Update ONLY for today.
	 * - If $task_id provided: update allowed only when task belongs to user AND task_date == today.
	 * - If not provided: create today's task.
	 */
	private function save_task( int $user_id, int $task_id = 0 ) : bool {
		global $wpdb; $table = $wpdb->prefix . 'et_tasks';
		$project_id = intval( $_POST['project_id'] ?? 0 );
		$title      = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
		$desc       = wp_kses_post( wp_unslash( $_POST['description'] ?? '' ) );
		$hours      = isset( $_POST['hours'] ) ? floatval( $_POST['hours'] ) : null;
		$today      = current_time( 'Y-m-d' );

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
			// Ensure the task belongs to user and is for today
			$owned_today = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM $table WHERE id=%d AND user_id=%d AND task_date=%s",
				$task_id, $user_id, $today
			) );
			if ( ! $owned_today ) { return false; }
			$wpdb->update( $table, $data, [ 'id' => $task_id ] );
			do_action( 'et/task_submitted', $task_id );
			return true;
		}

		$data['created_at'] = current_time( 'mysql' );
		$ok = $wpdb->insert( $table, $data );
		if ( $ok ) {
			do_action( 'et/task_submitted', (int) $wpdb->insert_id );
		}
		return (bool) $ok;
	}
}

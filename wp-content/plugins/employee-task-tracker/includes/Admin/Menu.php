<?php
namespace ET\Admin;

use ET\Admin\Pages\Projects_Page;
use ET\Admin\Pages\Tasks_Page;
use ET\Admin\Pages\My_Tasks_Page; // IMPORTANT

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Menu {
	public function hooks() {
		add_action( 'admin_menu', [ $this, 'register' ] );
	}

	public function register() {
		$cap_projects = 'et_manage_projects';
		$cap_tasks    = 'et_manage_tasks';

		add_menu_page(
			__( 'Task Tracker', 'etracker' ),
			__( 'Task Tracker', 'etracker' ),
			$cap_tasks,
			'et-dashboard',
			[ $this, 'render_dashboard' ],
			'dashicons-clipboard',
			26
		);

		// Employee-only top level menu: "My Tasks"
		if ( $this->is_employee_user() ) {
			add_menu_page(
				__( 'My Tasks', 'etracker' ),
				__( 'My Tasks', 'etracker' ),
				'read', // employees already have 'read'
				'et-my-tasks',
				[ new My_Tasks_Page(), 'render' ],
				'dashicons-yes-alt',
				27
			);
		}

		add_submenu_page( 'et-dashboard', __( 'Projects', 'etracker' ), __( 'Projects', 'etracker' ), $cap_projects, 'et-projects', [ new Projects_Page(), 'render' ] );
		add_submenu_page( 'et-dashboard', __( 'Tasks', 'etracker' ), __( 'Tasks', 'etracker' ), $cap_tasks, 'et-tasks', [ new Tasks_Page(), 'render' ] );
		add_submenu_page( 'et-dashboard', __( 'Export', 'etracker' ), __( 'Export', 'etracker' ), 'et_export_tasks', 'et-export', [ $this, 'render_export' ] );
	}

	public function render_dashboard() {
		echo '<div class="wrap et-wrap"><h1>' . esc_html__( 'Employee Task Tracker', 'etracker' ) . '</h1>';
		echo '<p>' . esc_html__( 'Manage projects, review daily tasks, approve/verify, and export to CSV (Excel).', 'etracker' ) . '</p>';
		echo '</div>';
	}

	// public function render_export() {
	// 	if ( ! current_user_can( 'et_export_tasks' ) ) { wp_die( esc_html__( 'Access denied', 'etracker' ) ); }
	// 	echo '<div class="wrap et-wrap"><h1>' . esc_html__( 'Export Tasks', 'etracker' ) . '</h1>';
	// 	echo '<form method="post">';
	// 	wp_nonce_field( 'et_export', 'et_export_nonce' );
	// 	echo '<label>' . esc_html__( 'Date (YYYY-MM-DD)', 'etracker' ) . ' <input type="date" name="task_date" required></label> ';
	// 	echo '<button class="button button-primary">' . esc_html__( 'Download CSV', 'etracker' ) . '</button>';
	// 	echo '</form></div>';

	// 	if ( isset( $_POST['et_export_nonce'] ) && wp_verify_nonce( $_POST['et_export_nonce'], 'et_export' ) ) {
	// 		$task_date = sanitize_text_field( wp_unslash( $_POST['task_date'] ?? '' ) );
	// 		do_action( 'et/export_csv', $task_date );
	// 	}
	// }
		public function render_export() {
			if ( ! current_user_can( 'et_export_tasks' ) ) { wp_die( esc_html__( 'Access denied', 'etracker' ) ); }

			echo '<div class="wrap et-wrap"><h1>' . esc_html__( 'Export Tasks', 'etracker' ) . '</h1>';

			echo '<form method="get" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			echo '<input type="hidden" name="action" value="et_export_csv">';
			// NONCE must match your handler's action
			wp_nonce_field( 'et_export_csv' );
			echo '<label>' . esc_html__( 'Date', 'etracker' ) . ' <input type="date" name="date" required></label> ';
			echo '<button class="button button-primary">' . esc_html__( 'Download CSV', 'etracker' ) . '</button>';
			echo '</form>';

			echo '</div>';
		}



	private function is_employee_user(): bool {
		$user = wp_get_current_user();
		if ( ! $user || empty( $user->roles ) ) { return false; }
		$roles = (array) $user->roles;
		// show only to employees (hide for managers/admins)
		return in_array( 'employee', $roles, true ) && ! current_user_can( 'et_manage_tasks' );
	}
}

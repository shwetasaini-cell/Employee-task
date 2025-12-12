<?php
namespace ET\Admin\Pages;


if ( ! defined( 'ABSPATH' ) ) { exit; }


class Projects_Page {
public function render() {
if ( ! current_user_can( 'et_manage_projects' ) ) { wp_die( esc_html__( 'Access denied', 'etracker' ) ); }


$this->handle_post();
echo '<div class="wrap et-wrap"><h1>' . esc_html__( 'Projects', 'etracker' ) . '</h1>';
echo '<form method="post" class="et-card">';
wp_nonce_field( 'et_project_save', 'et_project_nonce' );
echo '<input type="hidden" name="id" value="' . ( isset( $_GET['edit'] ) ? intval( $_GET['edit'] ) : 0 ) . '">';
echo '<p><label>' . esc_html__( 'Name', 'etracker' ) . ' <input type="text" name="name" class="regular-text" required></label></p>';
echo '<p><label>' . esc_html__( 'Description', 'etracker' ) . ' <textarea name="description" rows="3"></textarea></label></p>';
echo '<p><label>' . esc_html__( 'Status', 'etracker' ) . ' <select name="status"><option value="active">Active</option><option value="archived">Archived</option></select></label></p>';
echo '<p><button class="button button-primary">' . esc_html__( 'Save Project', 'etracker' ) . '</button></p>';
echo '</form>';


$this->list_table();
echo '</div>';
}


private function handle_post() {
if ( ! isset( $_POST['et_project_nonce'] ) ) { return; }
if ( ! wp_verify_nonce( $_POST['et_project_nonce'], 'et_project_save' ) ) { return; }
if ( ! current_user_can( 'et_manage_projects' ) ) { return; }


global $wpdb; $table = $wpdb->prefix . 'et_projects';
$data = [
'name' => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
'description' => wp_kses_post( wp_unslash( $_POST['description'] ?? '' ) ),
'status' => sanitize_text_field( wp_unslash( $_POST['status'] ?? 'active' ) ),
'updated_at' => current_time( 'mysql' ),
];
$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
if ( $id ) {
$wpdb->update( $table, $data, [ 'id' => $id ] );
} else {
$data['created_at'] = current_time( 'mysql' );
$wpdb->insert( $table, $data );
}
}


private function list_table() {
global $wpdb; $table = $wpdb->prefix . 'et_projects';
$items = $wpdb->get_results( "SELECT * FROM $table ORDER BY id DESC" );
echo '<div class="et-card"><h2>' . esc_html__( 'All Projects', 'etracker' ) . '</h2>';
echo '<table class="widefat fixed striped"><thead><tr><th>ID</th><th>Name</th><th>Status</th><th>Updated</th></tr></thead><tbody>';
foreach ( $items as $it ) {
echo '<tr><td>' . intval( $it->id ) . '</td><td>' . esc_html( $it->name ) . '</td><td>' . esc_html( $it->status ) . '</td><td>' . esc_html( $it->updated_at ) . '</td></tr>';
}
echo '</tbody></table></div>';
}
}
<?php
namespace ET\Exports;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Exporter {

    public function hooks() {
        // Logged-in only (recommended for exporting)
        add_action( 'admin_post_et_export_csv', [ $this, 'handle_export' ] );

        // If you ever need to allow non-logged-in, also add:
        // add_action( 'admin_post_nopriv_et_export_csv', [ $this, 'handle_export' ] );
    }

    /**
     * URL to trigger:
     *   /wp-admin/admin-post.php?action=et_export_csv&date=YYYY-MM-DD&_wpnonce=...
     * Build the link with: wp_nonce_url( admin_url('admin-post.php?action=et_export_csv&date=2025-11-09'), 'et_export_csv' )
     */
    public function handle_export() {
        // Capability check
        if ( ! current_user_can( 'et_export_tasks' ) ) {
            wp_die( esc_html__( 'You do not have permission to export.', 'et' ) );
        }

        // Nonce check (required for admin-post)
        check_admin_referer( 'et_export_csv' );

        // Date param
        $date = isset( $_GET['date'] ) ? sanitize_text_field( wp_unslash( $_GET['date'] ) ) : '';
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
            wp_die( esc_html__( 'Invalid date.', 'et' ) );
        }

        global $wpdb;
        $table_tasks    = $wpdb->prefix . 'et_tasks';
        $table_projects = $wpdb->prefix . 'et_projects';

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT t.*, u.user_email, u.display_name, p.name AS project_name
                 FROM {$table_tasks} t
                 JOIN {$wpdb->users} u ON u.ID = t.user_id
                 JOIN {$table_projects} p ON p.id = t.project_id
                 WHERE t.task_date = %s
                 ORDER BY t.id ASC",
                $date
            ),
            ARRAY_A
        );

        // --- CRITICAL: kill any output before headers ---
        while ( ob_get_level() ) { ob_end_clean(); }

        // Helpful for Excel with UTF-8 (Hindi/Hinglish etc.)
        $filename = 'tasks-' . sanitize_file_name( $date ) . '.csv';

        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        // UTF-8 BOM so Excel shows characters correctly
        echo "\xEF\xBB\xBF";

        $fh = fopen( 'php://output', 'w' );

        // Header row
        fputcsv( $fh, [ 'ID', 'Date', 'Employee', 'Email', 'Project', 'Title', 'Description', 'Hours', 'Status', 'Manager Comment', 'Created', 'Updated' ] );

        foreach ( $rows as $r ) {
            fputcsv( $fh, [
                $r['id'],
                $r['task_date'],
                $r['display_name'],
                $r['user_email'],
                $r['project_name'],
                $r['title'],
                wp_strip_all_tags( (string) $r['description'] ),
                $r['hours'],
                $r['status'],
                wp_strip_all_tags( (string) $r['manager_comment'] ),
                $r['created_at'],
                $r['updated_at'],
            ] );
        }

        fclose( $fh );
        exit; // Important: stop WP from rendering anything else
    }
}

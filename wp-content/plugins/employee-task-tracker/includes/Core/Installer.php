<?php
namespace ET\Core;


use WP_Roles;


if ( ! defined( 'ABSPATH' ) ) { exit; }


class Installer {

const DB_VERSION = '1.0.0';


public static function activate() {
self::create_tables();
self::add_roles_caps();
add_option( 'et_db_version', self::DB_VERSION );
}

public static function deactivate() {
// Keep data by default; if you need cleanup, add it here.
}

public static function maybe_update() {
$current = get_option( 'et_db_version' );
if ( version_compare( (string) $current, self::DB_VERSION, '<' ) ) {
self::create_tables();
self::add_roles_caps();
update_option( 'et_db_version', self::DB_VERSION );
}
}

private static function create_tables() {
global $wpdb;
require_once ABSPATH . 'wp-admin/includes/upgrade.php';


$charset = $wpdb->get_charset_collate();
$projects = $wpdb->prefix . 'et_projects';
$tasks = $wpdb->prefix . 'et_tasks';


$sql_projects = "CREATE TABLE $projects (
id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
name VARCHAR(190) NOT NULL,
description TEXT NULL,
status VARCHAR(20) NOT NULL DEFAULT 'active',
created_at DATETIME NOT NULL,
updated_at DATETIME NOT NULL,
PRIMARY KEY (id),
KEY status (status)
) $charset;";


$sql_tasks = "CREATE TABLE $tasks (
id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
user_id BIGINT UNSIGNED NOT NULL,
project_id BIGINT UNSIGNED NOT NULL,
task_date DATE NOT NULL,
title VARCHAR(190) NOT NULL,
description TEXT NULL,
hours DECIMAL(5,2) NULL,
status VARCHAR(20) NOT NULL DEFAULT 'submitted',
manager_comment TEXT NULL,
created_at DATETIME NOT NULL,
updated_at DATETIME NOT NULL,
PRIMARY KEY (id),
KEY user_date (user_id, task_date),
KEY project_date (project_id, task_date),
CONSTRAINT fk_et_tasks_project FOREIGN KEY (project_id) REFERENCES $projects(id) ON DELETE CASCADE
) $charset;";


dbDelta( $sql_projects );
dbDelta( $sql_tasks );
}


private static function add_roles_caps() {
$roles = wp_roles();
// Employee role
if ( ! $roles->is_role( 'employee' ) ) {
$roles->add_role( 'employee', __( 'Employee', 'etracker' ), [ 'read' => true ] );
}
// Manager role
if ( ! $roles->is_role( 'et_manager' ) ) {
$roles->add_role( 'et_manager', __( 'Task Manager', 'etracker' ), [ 'read' => true ] );
}
// Capabilities (map_meta_cap will harden)
$caps = [
'et_manage_projects',
'et_manage_tasks',
'et_approve_tasks',
'et_export_tasks',
];
foreach ( $caps as $cap ) {
$roles->add_cap( 'administrator', $cap );
$roles->add_cap( 'et_manager', $cap );
}
}
}
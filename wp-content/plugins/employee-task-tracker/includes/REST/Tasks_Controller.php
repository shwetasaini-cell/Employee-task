<?php
namespace ET\REST;


use WP_REST_Server;
use WP_REST_Request;


if ( ! defined( 'ABSPATH' ) ) { exit; }


class Tasks_Controller {
public function hooks() {
add_action( 'rest_api_init', [ $this, 'register_routes' ] );
}


public function register_routes() {
register_rest_route( 'et/v1', '/tasks/(?P<id>\\d+)/lock', [
'methods' => WP_REST_Server::EDITABLE,
'callback' => [ $this, 'lock_task' ],
'permission_callback' => function() { return current_user_can( 'et_approve_tasks' ); },
'args' => [ 'id' => [ 'type' => 'integer', 'required' => true ] ],
] );
}


public function lock_task( WP_REST_Request $request ) {
global $wpdb; $table = $wpdb->prefix . 'et_tasks';
$id = (int) $request['id'];
$wpdb->update( $table, [ 'status' => 'approved', 'updated_at' => current_time( 'mysql' ) ], [ 'id' => $id ] );
return [ 'success' => true ];
}
}
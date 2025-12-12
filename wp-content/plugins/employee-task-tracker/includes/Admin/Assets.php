<?php
namespace ET\Admin;


if ( ! defined( 'ABSPATH' ) ) { exit; }


class Assets {
public function hooks() {
add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
}
public function enqueue( $hook ) {
// Only load on our pages
if ( strpos( $hook, 'et-' ) === false ) { return; }
wp_enqueue_style( 'et-admin', ET_PLUGIN_URL . 'assets/admin.css', [], '1.0.0' );
wp_enqueue_script( 'et-admin', ET_PLUGIN_URL . 'assets/admin.js', [ 'jquery' ], '1.0.0', true );
wp_localize_script( 'et-admin', 'ET', [
'nonce' => wp_create_nonce( 'et_nonce' ),
'rest' => esc_url_raw( rest_url( 'et/v1' ) ),
] );
}
}
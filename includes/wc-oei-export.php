<?php


function wc_oei_order_actions( $actions ) {
	$actions['export'] = __( 'Export', 'wc_oei' );

	return $actions;
}
add_filter( 'bulk_actions-edit-shop_order', 'wc_oei_order_actions' );


function wc_oei_order_export_handler( $redirect_to, $doaction, $post_ids ) {
	if( $doaction === 'export' ) {
		global $wpdb;

		if( ! empty( $post_ids ) ) {
			$export = new WC_OEI_Export_Handler;
			$export->load_orders( $post_ids );
			$export->run();
			$export->generate();


			if( ! empty( $results ) ) {

			}else {
				add_action( 'admin_notices', 'wc_oei_admin_notice_export_error' );
			}
		}else {
			add_action( 'admin_notices', 'wc_oei_admin_notice_no_orders' );
		}
	}

	return $redirect_to;
}
add_filter( 'handle_bulk_actions-edit-shop_order', 'wc_oei_order_export_handler', 10, 3 );


function wc_oei_admin_notice_export_error() {
	?>
    <div class="notice notice-error is-dismissible">
        <p><?php _e( 'An error occured during export.', 'wc-oei' ); ?></p>
    </div>
    <?php
}


function wc_oei_admin_notice_no_orders() {
	?>
    <div class="notice notice-error is-dismissible">
        <p><?php _e( 'An error occured during export.', 'wc-oei' ); ?></p>
    </div>
    <?php
}
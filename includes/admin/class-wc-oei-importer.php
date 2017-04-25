<?php

if( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once ABSPATH . 'wp-admin/includes/import.php';

if( ! class_exists( 'WP_Importer' ) ) {
	$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';

	if( file_exists( $class_wp_importer ) ) {
		require $class_wp_importer;
	}
}

if( ! class_exists( 'WP_Importer' ) ) {
	return;
}

class WC_OEI_Importer extends WP_Importer {

	public $file_id;

	public $json;

	public $current = 0;

	public function __construct() {
		
	}

	public function handle_upload() {
		$file = wp_import_handle_upload();

		if( isset( $file['error'] ) ) {
			$this->import_error( $file['error'] );
		}

		$this->file_id = absint( $file['id'] );

		return true;
	}

	public function import() {
		check_admin_referer( 'import-upload' );

		if( $this->handle_upload() ) {
			$file = get_attached_file( $this->file_id );

			if ( ! is_file( $file ) ) {
				$this->import_error( __( 'The file does not exist, please try again.', 'wc-oei' ) );
			}

			wc_set_time_limit( 0 );
			@ob_flush();
			@flush();

			$contents = file_get_contents( $file );

			$json = json_decode( $contents, true );

			if( empty( $json ) ) {
				$this->import_error( __( 'An error occured while decoding JSON.', 'wc-oei' ) );
			}else {
				$this->json = array_values( $json );
			}
			?>
			

			<div class="media-progress-bar"><div></div></div>

			<div class="wc-oei-log" id="log-viewer"></div>

			<input type="hidden" name="wc-oei_file_id" value="<?php print $this->file_id; ?>" />

			<script>jQuery( document ).ready( function() { WC_OEI_Import_Processing( <?php print sizeof( $this->json ); ?> ); });</script>

			<?php
		}
	}


	public function process_import() {
		if( ! current_user_can( apply_filters( 'wc_oei_import_capabilities', 'manage_options' ) ) )
			exit;

		$file = get_attached_file( $_POST['file_id'] );

		if( ! is_file( $file ) ) {
			_e( 'The file does not exist, please try again.', 'wc-oei' );
			exit;
		}

		$contents = file_get_contents( $file );

		$json = json_decode( $contents, true );

		if( empty( $json ) ) {
			_e( 'An error occured while decoding JSON.', 'wc-oei' );
			exit;
		}
		
		$json = array_values( $json );

		$_order = $json[ $_POST['current'] ];

		$order = wc_create_order( array(
			'status' 			=> $_order['status'],
			'parent' 			=> $_order['parent'],
		) );

		wp_update_post( array(
			'ID'				=> $order->get_id(),
			'post_date'			=> $_order['post_date'],
			'post_date_gmt'		=> $_order['post_date_gmt'],
			'post_modified'		=> $_order['post_modified'],
			'post_modified_gmt' => $_order['post_modified_gmt'],
			'menu_order'		=> $_order['menu_order'],
		) );

		if( $order && ! is_wp_error( $order ) ) {
			if( ! empty( $_order['meta'] ) && is_array( $_order['meta'] ) ) {
				foreach( $_order['meta'] as $meta_key => $meta_value ) {
					update_post_meta( $order->get_id(), $meta_key, $meta_value );
				}
			}

			if( ! empty( $_order['order_items'] ) && is_array( $_order['order_items'] ) ) {
				foreach( $_order['order_items'] as $id => $args ) {
					$item = wc_add_order_item( $order->get_id(), array(
						'order_item_name' => isset( $args['order_item_name'] ) ? $args['order_item_name'] : '',
						'order_item_type' => isset( $args['order_item_type'] ) ? $args['order_item_type'] : '',
					) );

					if( ! empty( $args['meta'] ) && is_array( $args['meta'] ) ) {
						foreach( $args['meta'] as $meta_key => $meta_value ) {
							wc_update_order_item_meta( $item, $meta_key, $meta_value );
						}
					}
				}
			}

			if( ! empty( $_order['notes'] ) && is_array( $_order['notes'] ) ) {
				foreach( $_order['notes'] as $note ) {
					$comment = $order->add_order_note( $note['comment_content'] );

					global $wpdb;

					$wpdb->update(
						$wpdb->comments,
						array( 
							'comment_date'		=> $note['comment_date'], 
							'comment_date_gmt'  => $note['comment_date_gmt'],
							'user_id'			=> $note['user_id'],
						),
						array( 'comment_ID' => $comment ),
						array(
							'%s',
							'%s',
							'%d',
						)
					);
				}
			}

			print 'Imported successfully: ' . $order->get_id();
		}else {
			print 'Error';
		}

		exit;
	}

	public function wc_oei_filter_comment_args( $args ) {
		global $note;

		print_r( $note );
		
		$args['comment_date']     = $note['comment_date'];
		$args['comment_date_gmt'] = $note['comment_date_gmt'];

		return $args;
	}


	private function import_error( $message = '' ) {
		echo '<p><strong>' . __( 'Sorry, there has been an error.', 'wc-oei' ) . '</strong><br />';
		if ( $message ) {
			echo esc_html( $message );
		}
		echo '</p>';
		die();
	}

}
<?php


class WC_OEI_Export_Handler {

	public $order_ids;

	public $orders;

	public function load_orders( $orders = '' ) {
		if( empty( $orders ) ) {
			$this->order_ids = '';
		}else {
			if( is_array( $orders ) ) {
				$orders = implode( ',', $orders );
			}

			$this->order_ids = $orders;
		}
	}

	protected function get_orders() {
		global $wpdb;

		$orders = $wpdb->get_results( "
			SELECT * 
			FROM  $wpdb->posts
			WHERE post_type = 'shop_order'
			AND   ID IN ( {$this->order_ids} )
		" );

		if( ! empty( $orders ) ) {
			foreach( $orders as $order ) {
				$postmeta = $wpdb->get_results(
					$wpdb->prepare( "
						SELECT meta_key, meta_value
						FROM {$wpdb->postmeta}
						WHERE post_id = %d
						",
						$order->ID 
					)
				);

				$this->orders[ $order->ID ] = array(
					'ID'				=> $order->ID,
					'status'     		=> $order->post_status,
					'parent'			=> $order->post_parent,
					'post_date'			=> $order->post_date,
					'post_date_gmt'		=> $order->post_date_gmt,
					'post_modified'		=> $order->post_modified,
					'post_modified_gmt' => $order->post_modified_gmt,
					'menu_order'		=> $order->menu_order
				);

				$skip_meta = array(
					'_edit_lock',
					'_edit_last',
				);

				if( ! empty( $postmeta ) ) {
					foreach( $postmeta as $meta ) {

						if( in_array( $meta->meta_key, $skip_meta ) )
							continue;
						$this->orders[ $order->ID ]['meta'][ $meta->meta_key ] = $meta->meta_value;
					}
				}
			}
		}
	}

	public function get_order_items() {
		global $wpdb;

		if( ! empty( $this->orders ) ) {
			$order_ids           = implode( ',', array_keys( $this->orders ) );
			$order_items_grouped = array();

			$order_items = $wpdb->get_results( "
				SELECT *
				FROM   {$wpdb->prefix}woocommerce_order_items
				WHERE  order_id IN ( $order_ids )
			" );

			$order_items_ids  = wp_list_pluck( $order_items, 'order_item_id' );

			if( ! empty( $order_items_ids ) ) {
				$order_items_ids = implode( ',', $order_items_ids );

				$order_items_meta = $wpdb->get_results( "
					SELECT *
					FROM   {$wpdb->prefix}woocommerce_order_itemmeta
					WHERE  order_item_id IN ( $order_items_ids )
				" );

				if( ! empty( $order_items ) ) {
					foreach( $order_items as $order_item ) {
						$order_items_grouped[ $order_item->order_item_id ] = array(
							'order_item_name' => $order_item->order_item_name,
							'order_item_type' => $order_item->order_item_type,
							'order_id'		  => $order_item->order_id,
							'meta'			  => array()
						);
					}
				}
				
				if( ! empty( $order_items_meta ) ) {
					foreach( $order_items_meta as $meta ) {
						$order_items_grouped[ $meta->order_item_id ]['meta'][ $meta->meta_key ] = $meta->meta_value;
					}
				}


				if( ! empty( $order_items_grouped ) ) {
					foreach( $order_items_grouped as $order_item ) {
						$this->orders[ $order_item['order_id'] ]['order_items'][] = $order_item;
					}
				}

			}

			
		}
	}

	public function get_order_notes() {
		global $wpdb;

		// Filter the new order note args to modify the date
		//add_filter( 'woocommerce_new_order_note_data', array( $this, 'filter_comment_args' ), 99 );

		$notes = $wpdb->get_results( "
			SELECT * 
			FROM   $wpdb->comments
			WHERE  comment_post_ID IN ( {$this->order_ids} )
		" );

		if( ! empty( $notes ) ) {
			foreach( $notes as $note ) {
				$this->orders[ $note->comment_post_ID ]['notes'][] = array(
					'comment_content'   => $note->comment_content,
					'comment_date'	    => $note->comment_date,
					'comment_date_gmt'  => $note->comment_date_gmt,
					'user_id'			=> $note->user_id
				);
			}
		}
	}

	/*
	public function filter_comment_args( $args ) {
		$args['comment_date'] = 

		return $args;
	}
	*/


	public function run( $load_meta = true ) {
		$this->get_orders();
		$this->get_order_items();
		$this->get_order_notes();
		
	}

	public function generate() {
		// Clean output buffers
		if( ob_get_level() ) {
			$levels = ob_get_level();
			for ( $i = 0; $i < $levels; $i++ ) {
				@ob_end_clean();
			}
		}else {
			@ob_end_clean();
		}

		// Prevent caching headers
		nocache_headers();

		header( "X-Robots-Tag: noindex, nofollow", true );
		header( "Content-Type: application/json" );
		header( "Content-Description: File Transfer" );
		header( "Content-Disposition: attachment; filename=\"wc-export-orders-" . time() . ".json\";" );
		header( "Content-Transfer-Encoding: binary" );

		print json_encode( $this->orders );
	}

}
<?php
/**
 * Admin and user display hooks.
 *
 * @package Details and File Upload
 */

namespace DetailsAndFileUploadPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin and user display hooks.
 */
class Display {
	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_filter(
			'plugin_action_links_' . plugin_basename( DETAILS_AND_FILE_UPLOAD_PLUGIN_FILE ),
			[ __CLASS__, 'action_links' ]
		);
		add_filter( 'woocommerce_checkout_fields', [ __CLASS__, 'add_fields' ], 1 );
		add_filter( 'woocommerce_form_field_dfu_file_upload', [ __CLASS__, 'create_upload_field' ], 10, 3 );
		add_action( 'woocommerce_after_order_details', [ __CLASS__, 'show_order' ] );
		add_action( 'add_meta_boxes', [ __CLASS__, 'add_edit_order_box' ] );
		add_action( 'woocommerce_email_after_order_table', [ __CLASS__, 'email_table' ] );
	}

	/**
	 * Get action links.
	 *
	 * @param string[] $links Array of plugin actions.
	 * @return string[] New plugin actions.
	 */
	public static function action_links( $links ) {
		$action_links = [
			'settings' => '<a href="'
				. admin_url( 'admin.php?page=details-and-file-uploads-settings' )
				. '">'
				. esc_html__( 'Settings', 'default' )
				. '</a>',
		];

		return array_merge( $action_links, $links );
	}

	/**
	 * Add checkout fields.
	 *
	 * @param array[] $field_groups Checkout field groups.
	 * @return array[] New checkout field groups.
	 */
	public static function add_fields( $field_groups ) {
		$fields     = get_option( 'details_and_file_uploads_fields', [] );
		$hide_notes = get_option( 'details_and_file_uploads_hide_notes', false );

		if ( $hide_notes ) {
			unset( $field_groups['order']['order_comments'] );
		}

		$processed     = [];
		$product_ids   = array_column( WC()->cart->get_cart(), 'product_id' );
		$variation_ids = array_column( WC()->cart->get_cart(), 'variation_id' );
		$category_ids  = array_merge(
			...array_map(
				function ( $product_id ) {
					return wc_get_product( $product_id )->get_category_ids();
				},
				$product_ids
			)
		);

		foreach ( $fields as &$field ) {
			if (
				( ! $field['products'] && ! $field['categories'] ) ||
				array_intersect( $product_ids, $field['products'] ) ||
				array_intersect( $variation_ids, $field['products'] ) ||
				array_intersect( $category_ids, $field['categories'] )
			) {
				$options = $field['options'] ?? null;

				if ( $options ) {
					$options = array_merge(
						[ '' => '— Select —' ],
						array_combine( $options, $options )
					);
				}

				$processed[ $field['id'] ] = [
					'type'        => 'file' === $field['type'] ? 'dfu_file_upload' : $field['type'],
					'label'       => $field['label'],
					'input_class' => 'select' === $field['type'] ? [ 'input-text' ] : [],
					'required'    => $field['required'],
					'clear'       => false,
					'label_class' => '',
					'options'     => $options,
					'placeholder' => $field['placeholder'] ?? null,
					'multiple'    => $field['multiple'] ?? null,
				];
			}
		}

		$field_groups['order'] = array_merge( $processed, $field_groups['order'] );

		return $field_groups;
	}

	/**
	 * Create a checkout upload field.
	 *
	 * @param array $field Field HTML.
	 * @param array $key Field key.
	 * @param array $args Arguments.
	 * @return string New Field HTML.
	 */
	public static function create_upload_field( $field, $key, $args ) {
		wp_enqueue_style(
			'dfu_checkout_styles',
			plugin_dir_url( DETAILS_AND_FILE_UPLOAD_PLUGIN_FILE ) . 'src/css/checkout.css',
			null,
			DETAILS_AND_FILE_UPLOAD_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'dfu_checkout_script',
			plugin_dir_url( DETAILS_AND_FILE_UPLOAD_PLUGIN_FILE ) . 'src/js/checkout.js',
			[ 'jquery' ],
			DETAILS_AND_FILE_UPLOAD_PLUGIN_VERSION,
			[ 'in_footer' => true ]
		);

		wp_localize_script(
			'dfu_checkout_script',
			'dfu_checkout_params',
			[
				'file_upload_endpoint' => admin_url( 'admin-ajax.php' ),
				'file_upload_nonce'    => wp_create_nonce( 'dfu-file-upload' ),
			]
		);

		$out = $field;

		if ( $args['required'] ) {
			$required = '&nbsp;<abbr class="required" title="' . esc_attr__( 'required', 'woocommerce' ) . '">*</abbr>';
		} else {
			$required = '&nbsp;<span class="optional">(' . esc_html__( 'optional', 'woocommerce' ) . ')</span>';
		}

		$out .= '<label for="' . esc_attr( $key ) . '">' . esc_html( $args['label'] ) . $required . '</label>';

		$out .= '<span class="woocommerce-input-wrapper">';
		$out .= '<input type="file" ';
		$out .= 'id="' . esc_attr( $key ) . '" ';
		$out .= 'class="input-text input-dfu-file-upload" ';
		$out .= 'data-name="' . esc_attr( $key ) . '" ';

		if ( $args['multiple'] ?? false ) {
			$out .= 'multiple ';
		}

		$out .= '>';
		$out .= '</span>';

		return '<p class="form-row">' . $out . '</p>';
	}

	/**
	 * Return if the given order has fields.
	 *
	 * @param \WC_Order $order The order to check.
	 * @return bool True if the order has fields.
	 */
	public static function order_has_fields( $order ) {
		$meta_data = $order->get_meta( 'details_and_file_uploads' ) ?: [];

		return count( $meta_data ) > 0;
	}

	/**
	 * Echo the fields for an order.
	 *
	 * @param \WC_Order $order The order to show fields for.
	 * @param bool      $show_objects Weather to show HTML objects.
	 */
	public static function show_fields_for_order( $order, $show_objects = true ) {
		wp_enqueue_style(
			'dfu_order_details_styles',
			plugin_dir_url( DETAILS_AND_FILE_UPLOAD_PLUGIN_FILE ) . 'src/css/order.css',
			null,
			DETAILS_AND_FILE_UPLOAD_PLUGIN_VERSION
		);

		$fields        = get_option( 'details_and_file_uploads_fields', [] );
		$key_index_map = array_flip( array_column( $fields, 'id' ) );
		$meta_data     = $order->get_meta( 'details_and_file_uploads' ) ?: [];

		echo '<div class="dfu-order-details">';

		foreach ( $meta_data as $key => $data ) {
			$label = array_key_exists( $key, $key_index_map )
				? $fields[ $key_index_map[ $key ] ]['label']
				: '{' . $key . '}';

			echo '<div class="dfu-order-detail">';

			if ( 'file' === $data['type'] && $show_objects ) {
				echo '<div>' . esc_html( $label ) . ':</div>';
				echo '<div class="dfu-file-field">';

				foreach ( $data['data'] as $file ) {
					echo '<figure>';
					echo '<a target="_blank" href="' . esc_attr( $file['url'] ) . '">';
					echo '<object data="' . esc_attr( $file['url'] ) . '"></object>';
					echo '</a>';
					echo '<figcaption>';
					echo '<a target="_blank" href="' . esc_attr( $file['url'] ) . '">';
					echo esc_html( $file['name'] );
					echo '</a>';
					echo '</figcaption>';
					echo '</figure>';
				}

				echo '</div>';
			} elseif ( 'file' === $data['type'] ) {
				echo '<div>';
				echo '<span>' . esc_html( $label ) . ':</span> ';

				echo implode(
					', ',
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					array_map(
						function ( $file ) {
							$out = '';

							$out .= '<a target="_blank" href="' . esc_attr( $file['url'] ) . '">';
							$out .= esc_html( $file['name'] );
							$out .= '</a>';

							return $out;
						},
						$data['data']
					)
				);

				echo '</div>';
			} else {
				echo '<div>';
				echo '<span>' . esc_html( $label ) . ':</span> ';
				echo '<span>' . esc_html( $data['data'] ) . '</span>';
				echo '</div>';
			}

			echo '</div>';
		}

		if ( ! count( $meta_data ) ) {
			echo '<div class="dfu-order-detail">';
			echo 'No details found.';
			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * Show fields on order details page.
	 *
	 * @param \WC_Order $order The order.
	 */
	public static function show_order( $order ) {
		if ( ! self::order_has_fields( $order ) ) {
			return;
		}

		echo '<h2 class="woocommerce-column__title">Details and files</h2>';

		self::show_fields_for_order( $order );
	}

	/**
	 * Add meta box to order editor.
	 */
	public static function add_edit_order_box() {
		add_meta_box(
			'dfu_order_meta_box',
			'Details and files',
			[ __CLASS__, 'edit_order_meta_box' ],
			'shop_order',
			'side'
		);
	}

	/**
	 * Show meta box content.
	 *
	 * @param \WP_Post $post The post.
	 */
	public static function edit_order_meta_box( $post ) {
		self::show_fields_for_order( wc_get_order( $post->ID ) );
	}

	/**
	 * Generate details table for emails
	 *
	 * @param \WC_Order $order The order.
	 */
	public static function email_table( $order ) {
		if ( ! self::order_has_fields( $order ) ) {
			return;
		}

		echo '<h2>Details and files</h2>';

		ob_start();
		self::show_fields_for_order( $order, false );
		$fields = ob_get_contents();
		ob_end_clean();

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo str_replace(
			'class="dfu-order-details"',
			'style="margin-bottom:40px;padding:12px;color:#636363;border:1px solid #e5e5e5;"',
			$fields
		);
	}
}

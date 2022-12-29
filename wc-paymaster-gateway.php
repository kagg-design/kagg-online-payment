<?php
/**
 * Plugin Name: WooCommerce Paymaster Payment Gateway
 * Plugin URI: mailto:ovsyannikov.ivan@gmail.com
 * Description: WooCommerce Paymaster Payment Gateway
 * Version: 1.2
 * Author: Ivan Ovsyannikov, KAGG Design
 * Author URI: mailto:ovsyannikov.ivan@gmail.com
 *
 * @package wc-paymaster-payment-gateway
 */

add_action( 'woocommerce_product_options_general_product_data', 'paymaster_admin_payment_available' );
add_action( 'woocommerce_process_product_meta', 'paymaster_admin_payment_available_save' );
add_filter( 'manage_edit-product_columns', 'paymaster_column_into_product_list' );
add_action( 'manage_product_posts_custom_column', 'paymaster_rows_into_product_list', 10, 2 );
add_action( 'quick_edit_custom_box', 'paymaster_quickedit_checkbox', 10, 2 );
add_action( 'admin_footer-edit.php', 'paymaster_admin_footer_js_script', 11 );
add_action( 'save_post', 'paymaster_admin_payment_available_save', 10, 3 );
add_action( 'woocommerce_add_to_cart', 'check_cart_payment_available' );
add_action( 'woocommerce_cart_updated', 'check_cart_payment_available' );

function paymaster_admin_payment_available() {
	global $post;

	$product_meta = (object) get_post_meta( $post->ID );
	$value        = ( isset( $product_meta->_paymaster_payment_available ) && 'yes' !== $product_meta->_paymaster_payment_available[0] ) ? '' : 'yes';

	echo '<div class="options_group">';

	woocommerce_wp_checkbox(
		[
			'id'          => '_paymaster_payment_available',
			'value'       => $value,
			'cbvalue'     => 'yes',
			'label'       => 'Он-лайн оплата',
			'description' => 'Разрешить оплату товара он-лайн',
		]
	);

	echo '</div>';
}

function paymaster_admin_payment_available_save( $post_id ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	$paymaster_payment_available = isset( $_POST['_paymaster_payment_available'] ) ?
		sanitize_text_field( wp_unslash( $_POST['_paymaster_payment_available'] ) ) :
		'';
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	update_post_meta( $post_id, '_paymaster_payment_available', $paymaster_payment_available );
}

function paymaster_column_into_product_list( $defaults ) {
	$defaults['_paymaster_payment_available'] = 'Он-лайн оплата';

	return $defaults;
}

function paymaster_rows_into_product_list( $column, $post_id ) {
	if ( '_paymaster_payment_available' !== $column ) {
		return;
	}

	$arr_value = get_post_meta( $post_id, '_paymaster_payment_available', false );
	$value     = ( isset( $arr_value[0] ) && 'yes' !== $arr_value[0] ) ?
		'<span class="paymaster-payment-available" data-status="no" style="color: #e1360c;">Запрещена</span>' :
		'<span class="paymaster-payment-available" data-status="yes">Разрешена</span>';

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $value;
}

/**
 * Quick edit checkbox.
 *
 * @param string $col  Column name.
 * @param string $type Type.
 *
 * @return void
 */
function paymaster_quickedit_checkbox( $col, $type ) {
	if ( 'product' !== $type ) {
		return;
	}

	if ( '_paymaster_payment_available' === $col ) {
		?>
		<fieldset class="inline-edit-col-right">
			<div class="inline-edit-col">
				<h4>Он-лайн оплата</h4>
				<label class="alignleft">
					<input
							type="checkbox" class="payment-available-checkbox" name="_paymaster_payment_available"
							value="yes">
					<span class="checkbox-title">Разрешить оплату товара он-лайн</span>
				</label>
			</div>
		</fieldset>
		<?php
	}
}

/**
 * Admin footer script.
 *
 * @return void
 */
function paymaster_admin_footer_js_script() {
	$slug = 'product';

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ( isset( $_GET['page'] ) && $_GET['page'] === $slug ) || ( isset( $_GET['post_type'] ) && $_GET['post_type'] === $slug ) ) {
		?>
		<script type="text/javascript">
			(function ($) {
				var _wp_inline_edit = inlineEditPost.edit;
				inlineEditPost.edit = function (id) {
					_wp_inline_edit.apply(this, arguments);
					var _post_id = 0;
					if (typeof (id) == 'object') _post_id = parseInt(this.getId(id));
					if (_post_id > 0) {
						var _post_slug = $('#post-' + _post_id),
							_edit_slug = $('#edit-' + _post_id),
							_payment_available_checkbox = $('.payment-available-checkbox', _edit_slug),
							_payment_available_status = $('td._paymaster_payment_available > span', _post_slug).attr('data-status');
						if (_payment_available_status === 'yes') _payment_available_checkbox.prop('checked', true);
						else _payment_available_checkbox.prop('checked', false);
					}
				};
			})(jQuery);
		</script>
		<?php
	}
}

/**
 * Check cart items for availability of online payments.
 *
 * @return void
 */
function check_cart_payment_available() {
	global $woocommerce;

	$cart                    = $woocommerce->cart->get_cart();
	$check_payment_available = true;

	foreach ( $cart as $item ) {
		$arr_value               = get_post_meta( $item['product_id'], '_paymaster_payment_available' );
		$check_payment_available = isset( $arr_value[0] ) && 'yes' === $arr_value[0];

		if ( ! $check_payment_available ) {
			break;
		}
	}

	if ( ! $check_payment_available ) {
		add_filter( 'woocommerce_payment_gateways', 'online_payment_not_available' );
	}
}

/**
 * Make online payment methods not available.
 *
 * @param array $methods Payment methods.
 *
 * @return array
 */
function online_payment_not_available( $methods ) {
	$online_payment_methods = [
		'WC_PAYMASTER',
		'WC_RBSPayment',
	];

	foreach ( $methods as $key => $value ) {
		if ( in_array( $value, $online_payment_methods, true ) ) {
			unset( $methods[ $key ] );
		}
	}

	return $methods;
}

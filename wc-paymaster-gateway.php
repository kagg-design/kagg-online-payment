<?php
/*
Plugin Name: WooCommerce Paymaster Payment Gateway
Plugin URI: mailto:ovsyannikov.ivan@gmail.com
Description: WooCommerce Paymaster Payment Gateway
Version: 1.1
Author: Ivan Ovsyannikov, KAGG Design
Author URI: mailto:ovsyannikov.ivan@gmail.com
*/

define('PAYMASTER_PROCESS_URL', 'https://paymaster.ru/Payment/Init');

//add_action('plugins_loaded', 'woocommerce_paymaster_init', 0);
add_action('woocommerce_product_options_general_product_data', 'paymaster_admin_payment_available');
add_action('woocommerce_process_product_meta', 'paymaster_admin_payment_available_save');
add_filter('manage_edit-product_columns', 'paymaster_column_into_product_list');
add_action('manage_product_posts_custom_column', 'paymaster_rows_into_product_list', 10, 2);
add_action('quick_edit_custom_box', 'paymaster_quickedit_checkbox', 10, 2);
add_action('admin_footer-edit.php', 'paymaster_admin_footer_js_script', 11);
add_action('save_post','paymaster_admin_payment_available_save', 10, 3);
add_action('woocommerce_add_to_cart', 'check_cart_payment_available');
add_action('woocommerce_cart_updated', 'check_cart_payment_available');

function paymaster_admin_payment_available() {
	global $post;
	$product_meta = (object) get_post_meta($post->ID);
	$value = (isset($product_meta->_paymaster_payment_available) && $product_meta->_paymaster_payment_available[0] != 'yes') ? '' : 'yes';
	echo '<div class="options_group">';
	woocommerce_wp_checkbox(array(
		'id' => '_paymaster_payment_available',
		'value' => $value,
		'cbvalue' => 'yes',
		'label' => 'Он-лайн оплата',
		'description' => 'Разрешить/запретить оплату товара он-лайн через PayMaster'
	));
	echo '</div>';
}

function paymaster_admin_payment_available_save($post_id) {
	$paymaster_payment_available = isset($_POST['_paymaster_payment_available']) ? $_POST['_paymaster_payment_available'] : '';
	update_post_meta($post_id, '_paymaster_payment_available', $paymaster_payment_available);
}

function paymaster_column_into_product_list($defaults) {
	$defaults['_paymaster_payment_available'] = 'Он-лайн оплата';
	return $defaults;
}

function paymaster_rows_into_product_list($column, $post_id) {
	switch ($column) {
		case '_paymaster_payment_available':
			$arr_value = get_post_meta($post_id, '_paymaster_payment_available', false);
			$value = (isset($arr_value[0]) && $arr_value[0] != 'yes') ? '<span class="paymaster-payment-available" data-status="no" style="color: #e1360c;">Запрещена</span>' : '<span class="paymaster-payment-available" data-status="yes">Разрешена</span>';
			echo $value;
			break;
	}
}

function paymaster_quickedit_checkbox($col, $type) {
	if ($type != 'product') return;
	if ($col == '_paymaster_payment_available') {
		?>
		<fieldset class="inline-edit-col-right">
			<div class="inline-edit-col">
				<h4>Он-лайн оплата</h4>
				<label class="alignleft">
					<input type="checkbox" class="payment-available-checkbox" name="_paymaster_payment_available" value="yes">
					<span class="checkbox-title">Разрешить/запретить оплату товара он-лайн через PayMaster</span>
				</label>
			</div>
		</fieldset>
		<?php
	}
}

function paymaster_admin_footer_js_script() {
	$slug = 'product';
	if ((isset($_GET['page']) && $_GET['page'] == $slug) || (isset($_GET['post_type']) && $_GET['post_type'] == $slug)) {
		?>
		<script type="text/javascript">
		(function($) {
			var _wp_inline_edit = inlineEditPost.edit;
			inlineEditPost.edit = function(id) {
				_wp_inline_edit.apply(this, arguments);
				var _post_id = 0;
				if (typeof(id) == 'object') _post_id = parseInt(this.getId(id));
				if (_post_id > 0) {
					var _post_slug = $('#post-' + _post_id),
					_edit_slug = $('#edit-' + _post_id),
					_payment_available_checkbox = $('.payment-available-checkbox', _edit_slug),
					_payment_available_status = $('td._paymaster_payment_available > span', _post_slug).attr('data-status');
					if (_payment_available_status == 'yes') _payment_available_checkbox.prop('checked', true);
					else _payment_available_checkbox.prop('checked', false);
				}
			}
		})(jQuery);
		</script>
		<?php
	}
}

function check_cart_payment_available() {
	global $woocommerce;
	$cart = $woocommerce->cart->get_cart();
	$check_payment_available = true;
	foreach ($cart as $key => $item) {
		if ($check_payment_available == false) break;
		$arr_value = get_post_meta($item['product_id'], '_paymaster_payment_available', false);
		$check_payment_available = (isset($arr_value[0]) && $arr_value[0] != 'yes') ? false : true;
	}
	if ($check_payment_available == false && class_exists('WC_PAYMASTER')) {
		add_filter('woocommerce_payment_gateways', 'paymaster_payment_not_available');
	}
}

function paymaster_payment_not_available($methods) {
	foreach($methods as $key => $value) {
		if ($value == 'WC_PAYMASTER') unset($methods[$key]);
	}
	return $methods;
}
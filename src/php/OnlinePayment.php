<?php
/**
 * OnlinePayment class file.
 *
 * @package kagg-online-payment
 */

namespace KAGG\OnlinePayment;

/**
 * OnlinePayment class.
 */
class OnlinePayment {

	/**
	 * Online payment available meta.
	 */
	private const ONLINE_PAYMENT_AVAILABLE = '_paymaster_payment_available';

	/**
	 * Init class.
	 *
	 * @return void
	 */
	public function init(): void {
		$this->init_hooks();
	}

	/**
	 * Init hooks.
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		add_action( 'woocommerce_product_options_general_product_data', [ $this, 'admin_data' ] );
		add_action( 'woocommerce_process_product_meta', [ $this, 'admin_data_save' ] );
		add_action( 'save_post', [ $this, 'admin_data_save' ], 10, 3 );
		add_filter( 'manage_edit-product_columns', [ $this, 'column_into_product_list' ] );
		add_action( 'manage_product_posts_custom_column', [ $this, 'custom_column' ], 10, 2 );
		add_action( 'quick_edit_custom_box', [ $this, 'quick_edit_checkbox' ], 10, 2 );
		add_action( 'admin_footer-edit.php', [ $this, 'admin_footer_script' ], 11 );
		add_action( 'woocommerce_add_to_cart', [ $this, 'check_cart_payment_available' ] );
		add_action( 'woocommerce_cart_updated', [ $this, 'check_cart_payment_available' ] );
	}

	/**
	 * Add product data in admin.
	 *
	 * @return void
	 */
	public function admin_data(): void {
		global $post;

		$product_meta = (object) get_post_meta( $post->ID );
		$meta_name = self::ONLINE_PAYMENT_AVAILABLE;
		$value        = ( isset( $product_meta->$meta_name[0] ) && 'yes' !== $product_meta->$meta_name[0] ) ? '' : 'yes';

		echo '<div class="options_group">';

		woocommerce_wp_checkbox(
			[
				'id'          => self::ONLINE_PAYMENT_AVAILABLE,
				'value'       => $value,
				'cbvalue'     => 'yes',
				'label'       => 'Он-лайн оплата',
				'description' => 'Разрешить оплату товара он-лайн',
			]
		);

		echo '</div>';
	}

	/**
	 * Save admin payment available value.
	 *
	 * @param int $post_id Post id.
	 *
	 * @return void
	 */
	public function admin_data_save( int $post_id ): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$meta_value = isset( $_POST[ self::ONLINE_PAYMENT_AVAILABLE ] ) ?
			sanitize_text_field( wp_unslash( $_POST[ self::ONLINE_PAYMENT_AVAILABLE ] ) ) :
			'';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		update_post_meta( $post_id, self::ONLINE_PAYMENT_AVAILABLE, $meta_value );
	}

	/**
	 * Column list filter.
	 *
	 * @param array $defaults Defaults.
	 *
	 * @return array
	 */
	public function column_into_product_list( array $defaults ): array {
		$defaults[ self::ONLINE_PAYMENT_AVAILABLE ] = 'Он-лайн оплата';

		return $defaults;
	}

	/**
	 * Output custom column.
	 *
	 * @param string $column  Column.
	 * @param int    $post_id Post id.
	 *
	 * @return void
	 */
	public function custom_column( string $column, int $post_id ): void {
		if ( self::ONLINE_PAYMENT_AVAILABLE !== $column ) {
			return;
		}

		$arr_value = get_post_meta( $post_id, self::ONLINE_PAYMENT_AVAILABLE );
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
	public function quick_edit_checkbox( string $col, string $type ): void {
		if ( self::ONLINE_PAYMENT_AVAILABLE !== $col || 'product' !== $type ) {
			return;
		}

		?>
		<fieldset>
			<div class="inline-edit-col">
				<label class="alignleft">
					<input
						type="checkbox" class="payment-available-checkbox"
						name="<?php echo self::ONLINE_PAYMENT_AVAILABLE; ?>"
						value="yes">
					<span class="checkbox-title">Разрешить оплату товара он-лайн</span>
				</label>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Admin footer script.
	 *
	 * @return void
	 */
	public function admin_footer_script(): void {
		$slug = 'product';

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ( ! isset( $_GET['page'] ) || $_GET['page'] !== $slug ) && ( ! isset( $_GET['post_type'] ) || $_GET['post_type'] !== $slug ) ) {
			return;
		}

		?>
		<script type="text/javascript">
			(function ($) {
				const _wp_inline_edit = inlineEditPost.edit;

				inlineEditPost.edit = function (id) {
					_wp_inline_edit.apply(this, arguments);
					let _post_id = 0;

					if (typeof (id) === 'object') {
						_post_id = parseInt(this.getId(id));
					}

					if (_post_id <= 0) {
						return;
					}

					const _post_slug = $('#post-' + _post_id),
						_edit_slug = $('#edit-' + _post_id),
						_input = $('.payment-available-checkbox', _edit_slug),
						_payment_available_element = 'td.<?php echo self::ONLINE_PAYMENT_AVAILABLE; ?>',
						_payment_available_status = $( _payment_available_element + ' > span', _post_slug).attr('data-status');

					if (_payment_available_status === 'yes') {
						_input.prop('checked', true);
					} else {
						_input.prop('checked', false);
					}
				};
			})(jQuery);
		</script>
		<?php
	}

	/**
	 * Check cart items for availability of online payments.
	 *
	 * @return void
	 */
	public function check_cart_payment_available(): void {
		global $woocommerce;

		$cart                    = $woocommerce->cart->get_cart();
		$check_payment_available = true;

		foreach ( $cart as $item ) {
			$arr_value               = get_post_meta( $item['product_id'], self::ONLINE_PAYMENT_AVAILABLE );
			$check_payment_available = isset( $arr_value[0] ) && 'yes' === $arr_value[0];

			if ( ! $check_payment_available ) {
				break;
			}
		}

		if ( ! $check_payment_available ) {
			add_filter( 'woocommerce_payment_gateways', [ $this, 'online_payment_not_available' ] );
		}
	}

	/**
	 * Make online payment methods not available.
	 *
	 * @param array $methods Payment methods.
	 *
	 * @return array
	 */
	public function online_payment_not_available( array $methods ): array {
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
}

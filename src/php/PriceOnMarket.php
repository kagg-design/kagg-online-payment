<?php
/**
 * PriceOnMarket class file.
 *
 * @package kagg-online-payment
 */

namespace KAGG\OnlinePayment;

/**
 * PriceOnMarket class.
 */
class PriceOnMarket {

	/**
	 * Online payment available meta.
	 */
	private const PRICE_ON_MARKET = '_price_on_market';

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
	}

	/**
	 * Add product data in admin.
	 *
	 * @return void
	 */
	public function admin_data(): void {
		global $post;

		$product_meta = (object) get_post_meta( $post->ID );
		$meta_name    = self::PRICE_ON_MARKET;
		$value        = $product_meta->$meta_name[0] ?? '';

		echo '<div class="options_group">';

		woocommerce_wp_text_input(
			[
				'id'          => self::PRICE_ON_MARKET,
				'class'       => 'short wc_input_price ' . self::PRICE_ON_MARKET,
				'value'       => $value,
				'label'       => 'Цена на Маркете' . ' (' . get_woocommerce_currency_symbol() . ')',
				'description' => 'Цена на Яндекс Маркете',
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
		$meta_value = isset( $_POST[ self::PRICE_ON_MARKET ] ) ?
			sanitize_text_field( wp_unslash( $_POST[ self::PRICE_ON_MARKET ] ) ) :
			'';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		update_post_meta( $post_id, self::PRICE_ON_MARKET, $meta_value );
	}

	/**
	 * Column list filter.
	 *
	 * @param array $defaults Defaults.
	 *
	 * @return array
	 */
	public function column_into_product_list( array $defaults ): array {
		$defaults[ self::PRICE_ON_MARKET ] = 'Цена на Маркете';

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
		if ( self::PRICE_ON_MARKET !== $column ) {
			return;
		}

		$arr_value = get_post_meta( $post_id, self::PRICE_ON_MARKET );
		$value     = $arr_value[0] ?? '';

		echo '<span data-value="' . (int) $value . '">' . (int) $value . '</span>';
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
		if ( self::PRICE_ON_MARKET !== $col || 'product' !== $type ) {
			return;
		}

		?>
		<fieldset>
			<div class="inline-edit-col">
				<label class="alignleft">
					<span class="title">Цена на Маркете</span>
					<span class="input-text-wrap">
						<input type="text" name="<?php echo self::PRICE_ON_MARKET; ?>" class="short wc_input_price" value="">
					</span>
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
						_input = $('[name=<?php echo self::PRICE_ON_MARKET; ?>]', _edit_slug),
						_element = 'td.<?php echo self::PRICE_ON_MARKET; ?>',
						_value = $( _element + ' > span', _post_slug).attr('data-value');

					_input.val( _value );
				};
			})(jQuery);
		</script>
		<?php
	}
}

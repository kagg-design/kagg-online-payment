<?php
/**
 * Main class file.
 *
 * @package kagg-online-payment
 */

namespace KAGG\OnlinePayment;

/**
 * Main class.
 */
class Main {

	/**
	 * Init class.
	 *
	 * @return void
	 */
	public function init(): void {
		( new PriceOnMarket() )->init();
		( new OnlinePayment() )->init();
	}
}

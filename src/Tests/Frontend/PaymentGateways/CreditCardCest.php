<?php

namespace SkyVerge\Lumiere\Tests\Frontend\PaymentGateways;

use Codeception\Actor;
use Codeception\Module\WPWebDriver;
use SkyVerge\Lumiere\Page\Frontend\Product;
use SkyVerge\Lumiere\Page\Frontend\Checkout;
use SkyVerge\Lumiere\Tests\PaymentGatewaysBase;

abstract class CreditCardCest extends PaymentGatewaysBase {


	/**
	 * Runs before each test.
	 *
	 * @param WPWebDriver|Actor $I tester instance
	 */
	public function _before( $I ) {

		parent::_before( $I );
	}


	/**
	 * @param Product $single_product_page Product page object
	 * @param Checkout $checkout_page Checkout page object
	 */
	public function try_custom_name_is_shown( Product $single_product_page, Checkout $checkout_page ) {

		$this->tester->havePaymentGatewaySettingsInDatabase( $this->get_gateway_id(), [ 'title' => 'My Credit Card' ] );

		$this->add_shippable_product_to_cart_and_go_to_checkout( $single_product_page );

		$checkout_page->seePaymentMethodTitle( $this->get_gateway_id(), 'My Credit Card' );
	}


	/**
	 * @param Product $single_product_page Product page object
	 * @param Checkout $checkout_page Checkout page object
	 */
	public function try_successful_transaction_for_shippable_product( Product $single_product_page, Checkout $checkout_page ) {

		$this->add_shippable_product_to_cart_and_go_to_checkout( $single_product_page );

		$checkout_page->fillBillingDetails();

		$this->place_order( $checkout_page );
		$this->see_order_received();
	}


}

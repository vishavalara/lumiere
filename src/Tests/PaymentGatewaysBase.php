<?php

namespace SkyVerge\Lumiere\Tests;

use Codeception\Actor;
use Codeception\Module\WPWebDriver;
use SkyVerge\Lumiere\Page\Frontend\Checkout;
use SkyVerge\Lumiere\Page\Frontend\Product;

abstract class PaymentGatewaysBase extends AcceptanceBase {


	/** @var \WC_Product_Simple a shippable product */
	protected $shippable_product;

	/** @var string the number of credit cards saved during the current test */
	protected $saved_cards_count = 0;


	/**
	 * Runs before each test.
	 *
	 * @param WPWebDriver|Actor $I tester instance
	 */
	public function _before( $I ) {

		parent::_before( $I );

		$this->saved_cards_count = 0;

		// TODO: consider creating these products as a run-once-per-suite action or using WP-CLI in wp-bootstrap.php {WV 2020-03-29}
		$this->shippable_product = $this->tester->haveSimpleProductInDatabase( [ 'name' => 'Shippable 1' ] );
	}


	/**
	 * Gets the payment gateway instance.
	 *
	 * @return object
	 */
	protected abstract function get_gateway();


	/**
	 * Gets the ID of the payment gateway being tested.
	 *
	 * @return string
	 */
	protected function get_gateway_id() {

		return $this->get_gateway()->get_id();
	}


	/**
	 * Adds a shippable product to the cart and redirects to the Checkout page.
	 *
	 * @param Product $single_product_page Product page object
	 */
	protected function add_shippable_product_to_cart_and_go_to_checkout( Product $single_product_page ) {

		$this->tester->amOnPage( Product::route( $this->shippable_product ) );

		$single_product_page->addSimpleProductToCart( $this->shippable_product );

		$this->tester->amOnPage( Checkout::route() );
	}


	/**
	 * Places an order and ticks the Securely Save to Account checkbox.
	 *
	 * @param Checkout $checkout_page Checkout page object
	 */
	protected function place_order_and_tokenize_payment_method( Checkout $checkout_page ) {

		$this->check_tokenize_payment_method_field( $checkout_page );
		$this->place_order( $checkout_page );
	}


	/**
	 * Performs the necessary steps to tick the Securely Save to Account checkbox for the current gateway.
	 *
	 * @param Checkout $checkout_page Checkout page object
	 */
	protected function check_tokenize_payment_method_field( Checkout $checkout_page ) {

		try {
			$this->tester->waitForText( 'Use a new card' );
			$this->tester->click( 'form input[id$=use-new-payment-method]' );
		} catch ( \Exception $exception ) {
			// do nothing
		}

		$this->tester->tryToCheckOption( str_replace( '{gateway_id}', $this->get_gateway_id(), Checkout::FIELD_TOKENIZE_PAYMENT_METHOD ) );
	}


	/**
	 * Waits 30 seconds to see the Order received message.
	 */
	protected function see_order_received() {

		$this->tester->waitForElementVisible( '.woocommerce-order-details', 30 );
		$this->tester->see( 'Order received', '.entry-title' );
	}


	/**
	 * Gets the raw token of the last saved payment method.
	 *
	 * Not using WooCommerceDB::grabPaymentTokenFromDatabase() because it always return the first token matching the criteria.
	 *
	 * @return string
	 */
	protected function get_tokenized_payment_method_token() {

		$tokens = $this->tester->grabColumnFromDatabase( $this->tester->grabPrefixedTableNameFor( 'woocommerce_payment_tokens' ), 'token_id', [
			// TODO: get the admin username from the configuration and make the test user configurable {WV 2020-07-30}
			'user_id'    => $this->tester->grabUserIdFromDatabase( 'admin' ),
			'gateway_id' => $this->get_gateway_id(),
		] );

		$token_id = $tokens[ count( $tokens ) - 1 ];
		$token    = \WC_Payment_Tokens::get( (int) $token_id );

		return $token ? $token->get_token() : null;
	}


	/**
	 * Performs the necessary steps to place a new order from the Checkout page.
	 *
	 * Normally clicking the Place Order button is the only necessary step.
	 * Payment gateways may overwrite this method to perform extra steps, like entering a particular credit card number or test amount.
	 *
	 * @param Checkout $checkout_page Checkout page object
	 */
	protected function place_order( Checkout $checkout_page ) {

		$this->tester->tryToClick( Checkout::BUTTON_PLACE_ORDER );
	}


	/**
	 * Gets data for a new credit card payment.
	 *
	 * It uses the $saved_cards_count counter to return different data for each new payment in the same test.
	 *
	 * If using this method on a gateway, you must overwrite PaymentGatewaysBase::get_credit_cards_data() to return valid credit card data.
	 *
	 * @return array
	 */
	protected function get_new_credit_card_data() {

		$cards = $this->get_credit_cards_data();

		return ( count( $cards ) > $this->saved_cards_count ) ? current( array_slice( $cards, $this->saved_cards_count ) ) : reset( $cards );
	}


	/**
	 * Gets data used to create new credit card payments.
	 *
	 * Subclasses can overwrite this method to return appropriate data for each gateway.
	 *
	 * @return array
	 */
	protected function get_credit_cards_data() {

		$next_year = (int) date( 'y' ) + 1;

		return [
			'visa'       => [
				'number' => '4111111111111111',
				'expiry' => "12/{$next_year}",
				'cvv'    => '123',
			],
			'mastercard' => [
				'number' => '5100000010001004',
				'expiry' => "12/{$next_year}",
				'cvv'    => '123',
			],
		];
	}


}


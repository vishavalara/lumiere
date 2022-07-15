<?php

namespace SkyVerge\Lumiere\Tests\Frontend\PaymentGateways;

use Codeception\Actor;
use Codeception\Module\WPWebDriver;
use Codeception\Scenario;
use SkyVerge\Lumiere\Page\Admin\PaymentTokenEditor;
use SkyVerge\Lumiere\Page\Frontend\AddPaymentMethod;
use SkyVerge\Lumiere\Page\Frontend\Product;
use SkyVerge\Lumiere\Page\Frontend\Checkout;
use SkyVerge\Lumiere\Page\Frontend\PaymentMethods;

abstract class CreditCardTokenizationCest extends CreditCardCest {


	/**
	 * Runs before each test.
	 *
	 * @param WPWebDriver|Actor $I tester instance
	 */
	public function _before( $I ) {

		parent::_before( $I );

		$this->tester->loginAsAdmin();
	}


	/**
	 * @param Product $single_product_page Product page object
	 * @param Checkout $checkout_page Checkout page object
	 * @param PaymentMethods $payment_methods_page Payment Methods page object
	 */
	public function try_successful_transaction_for_shippable_product_saving_the_payment_method( Product $single_product_page, Checkout $checkout_page, PaymentMethods $payment_methods_page ) {

		$this->add_shippable_product_to_cart_and_go_to_checkout( $single_product_page );

		$checkout_page->fillBillingDetails();

		// place an order and save the payment method
		$this->place_order_and_tokenize_payment_method( $checkout_page );
		$this->see_order_received();

		$token = $this->get_tokenized_payment_method_token();

		$this->tester->amOnPage( PaymentMethods::route() );
		$this->tester->waitForElementVisible( PaymentMethods::SELECTOR_PAYMENT_METHODS_TABLE );

		// confirm the payment method is visible in the Payment Methods page
		$this->see_tokenize_payment_method( $token, $payment_methods_page );
	}


	/**
	 * Performs the necessary steps to add a new payment method from the Add payment method page.
	 *
	 * Sometimes clicking the Add payment method button is the only necessary step.
	 * Payment gateways may overwrite this method to perform extra steps, like entering a particular credit card number or test amount.
	 *
	 * @param AddPaymentMethod $add_payment_method_page Add payment method page object
	 */
	protected function add_payment_method( AddPaymentMethod $add_payment_method_page ) {

		$this->tester->tryToClick( AddPaymentMethod::BUTTON_ADD );
	}


	/**
	 * Confirms that a payment method row is visible in the Payment Methods table
	 *
	 * @param string $token the payment method token
	 * @param PaymentMethods $payment_methods_page Payment Methods page object
	 */
	protected function see_tokenize_payment_method( string $token, PaymentMethods $payment_methods_page ) {

		$payment_methods_page->seePaymentMethod( $token );
	}


	/**
	 * @param Product $single_product_page Product page object
	 * @param Checkout $checkout_page Checkout page object
	 * @param PaymentMethods $payment_methods_page Payment Methods page object
	 */
	public function try_successful_transaction_for_shippable_product_with_saved_payment_method( Product $single_product_page, Checkout $checkout_page, PaymentMethods $payment_methods_page ) {

		$this->add_shippable_product_to_cart_and_go_to_checkout( $single_product_page );

		$checkout_page->fillBillingDetails();

		// place an order and save the payment method
		$this->place_order_and_tokenize_payment_method( $checkout_page );
		$this->see_order_received();

		$this->add_shippable_product_to_cart_and_go_to_checkout( $single_product_page );

		$checkout_page->fillBillingDetails();

		// place an order using the saved payment method
		$this->place_order_using_tokenized_payment_method( $this->get_tokenized_payment_method_token(), $checkout_page );
		$this->see_order_received();
	}


	/**
	 * Places an order using a saved payment method.
	 *
	 * @param string $token payment method token
	 * @param Checkout $checkout_page Checkout page object
	 */
	protected function place_order_using_tokenized_payment_method( string $token, Checkout $checkout_page ) {

		$this->tester->tryToSelectOption( $this->get_saved_payment_method_selector( $token ), $token );
		$this->tester->tryToClick( Checkout::BUTTON_PLACE_ORDER );
	}


	/**
	 * Gets the selector for a saved payment method.
	 *
	 * @param string $token payment method token
	 */
	protected function get_saved_payment_method_selector( string $token ) {

		return str_replace( [ '{gateway_id}', '{token}' ], [ $this->get_gateway()->get_id_dasherized(), $token ], Checkout::FIELD_SAVED_PAYMENT_METHOD );
	}


	/**
	 * @param Product $single_product_page Product page object
	 * @param Checkout $checkout_page Checkout page object
	 * @param PaymentMethods $payment_methods_page Payment Methods page object
	 */
	public function try_editing_a_saved_payment_method( Product $single_product_page, Checkout $checkout_page, PaymentMethods $payment_methods_page ) {

		$this->add_shippable_product_to_cart_and_go_to_checkout( $single_product_page );

		$checkout_page->fillBillingDetails();

		// place an order and save the payment method
		$this->place_order_and_tokenize_payment_method( $checkout_page );
		$this->see_order_received();

		// set a nickname for the payment method
		$token = $this->get_tokenized_payment_method_token();
		$nickname = 'My Saved Card';

		$this->tester->amOnPage( PaymentMethods::route() );

		$payment_methods_page->setPaymentMethodNickname( $token, $nickname );
		$payment_methods_page->seePaymentMethodNickname( $token, $nickname );

		$this->tester->reloadPage();

		$payment_methods_page->seePaymentMethodNickname( $token, $nickname );

		// delete the payment method
		$payment_methods_page->deletePaymentMethod( $token );
		$payment_methods_page->dontSeePaymentMethod( $token );

		$this->tester->reloadPage();

		$payment_methods_page->dontSeePaymentMethod( $token );
	}


	/**
	 * @param \Codeception\Scenario $scenario Test scenario
	 * @param PaymentTokenEditor $user_profile_page User profile page object
	 * @param AddPaymentMethod $add_payment_method_page Add payment method page object
	 * @param PaymentMethods $payment_methods_page Payment Methods page object
	 *
	 * @throws \Codeception\Exception\ModuleException
	 */
	public function try_adding_a_saved_payment_method( Scenario $scenario, PaymentTokenEditor $user_profile_page, AddPaymentMethod $add_payment_method_page, PaymentMethods $payment_methods_page ) {

		if ( ! $this->get_gateway()->supports_add_payment_method() ) {

			$scenario->skip( 'This gateway does not support this feature' );
			return;
		}

		$this->tester->amOnPage( AddPaymentMethod::route() );
		$this->add_payment_method( $add_payment_method_page );
		$this->tester->waitForText( 'New payment method added' );

		$this->tester->amOnPage( PaymentMethods::route() );
		$token = $this->get_tokenized_payment_method_token();
		$payment_methods_page->seePaymentMethod( $token );
	}


	/**
	 * @param \Codeception\Scenario $scenario Test scenario
	 * @param Product $single_product_page Product page object
	 * @param Checkout $checkout_page Checkout page object
	 * @param PaymentTokenEditor $token_editor Payment Token Editor page object
	 */
	public function try_seeing_a_saved_payment_method_in_the_payment_tokens_editor( Scenario $scenario, Product $single_product_page, Checkout $checkout_page, PaymentTokenEditor $token_editor ) {

		if ( ! $this->get_gateway()->supports_token_editor() ) {

			$scenario->skip( 'This gateway does not support this feature' );
			return;
		}

		$this->add_shippable_product_to_cart_and_go_to_checkout( $single_product_page );

		$checkout_page->fillBillingDetails();

		// place an order and save the payment method
		$this->place_order_and_tokenize_payment_method( $checkout_page );
		$this->see_order_received();

		$this->tester->amOnPage( PaymentTokenEditor::route( 1 ) );

		$token_editor->seePaymentToken( $this->get_tokenized_payment_method_token() );
	}


}

<?php

namespace SkyVerge\Lumiere\Page\Frontend;

use Codeception\Actor;
use Codeception\Module\WPWebDriver;
use Codeception\Util\Locator;

/**
 * Payment Methods page object.
 */
class PaymentMethods {


	/** @var string default URL for the Payment methods page */
	const URL = '/my-account/payment-methods/';

	/** @var string selector for the Payment Methods table */
	const SELECTOR_PAYMENT_METHODS_TABLE = '.woocommerce-MyAccount-paymentMethods';

	/** @var string selector for the row for payment method with ID equal to {token} */
	const SELECTOR_PAYMENT_METHOD_ROW = "//tr[contains(concat(' ', normalize-space(@class), ' '), ' payment-method ')][descendant::input[@name = 'token-id' and @value = {token}]]";


	/** @var WPWebDriver|Actor our tester */
	protected $tester;


	/**
	 * Constructor.
	 *
	 * @param WPWebDriver|Actor $I tester instance
	 */
	public function __construct( \FrontendTester $I ) {

		$this->tester = $I;
	}


	/**
	 * Returns the URL to the Payment Methods page.
	 *
	 * @return string
	 */
	public static function route() {

		return self::URL;
	}


	/**
	 * Gets the selector for payment method row in the Payment Methods table.
	 *
	 * @param string $token the payment method token
	 * @return string
	 */
	public function getPaymentMethodRowSelector( string $token ) {

		if ( is_numeric( $token ) ) {
			$replacement = $token;
		} else {
			// add quotation marks
			$replacement = "'$token'";
		}

		return str_replace( '{token}', $replacement, self::SELECTOR_PAYMENT_METHOD_ROW );
	}


	/**
	 * Builds a selector for an element inside a payment method row.
	 *
	 * @param string $token the payment method token
	 * @param string $selector child element selector
	 * @return string
	 */
	public function getPaymentMethodElementSelector( string $token, $selector ) {

		return sprintf( "%s//%s", $this->getPaymentMethodRowSelector( $token ), $selector );
	}


	/**
	 * Checks that a payment method row is visible the Payment Methods table
	 *
	 * @param string $token the payment method token
	 */
	public function seePaymentMethod( string $token ) {

		$selector = $this->getPaymentMethodRowSelector( $token );

		$this->tester->waitForElementVisible( $selector );
		$this->tester->seeElement( $selector );
	}


	/**
	 * Checks that a payment method row is not visible in the Payment Methods table.
	 *
	 * @param string $token the payment method token
	 */
	public function dontSeePaymentMethod( string $token ) {

		$selector = $this->getPaymentMethodRowSelector( $token );

		$this->tester->waitForElementNotVisible( $selector );
		$this->tester->dontSeeElement( $selector );
	}


	/**
	 * Checks that the payment method has the specified nickname.
	 *
	 * @param string $token the payment method token
	 * @param string $nickname nickname for the payment method
	 */
	public function seePaymentMethodNickname( string $token, string $nickname ) {

		$selector = $this->getPaymentMethodElementSelector( $token, Locator::contains( 'div', $nickname ) );

		$this->tester->waitForElementVisible( $selector );
		$this->tester->seeElement( $selector );
	}


	/**
	 * Performs the steps to set the nickname for the given payment method.
	 *
	 * @param string $token the payment method token
	 * @param string $nickname nickname for the payment method
	 */
	public function setPaymentMethodNickname( string $token, string $nickname ) {

		// click the Edit button
		$this->tester->tryToClick( $this->getPaymentMethodElementSelector( $token, "a[contains(concat(' ', normalize-space(@class), ' '), ' edit ')]" ) );

		// fill the Nickname field
		$this->tester->fillField( $this->getPaymentMethodElementSelector( $token, "input[@name = 'nickname']" ), $nickname );

		// click the Save button
		$this->tester->tryToClick( $this->getPaymentMethodElementSelector( $token, "a[contains(concat(' ', normalize-space(@class), ' '), ' save ')]" ) );
	}


	/**
	 * Performs the steps to delete a payment method.
	 *
	 * @param string $token the payment method token
	 */
	public function deletePaymentMethod( string $token ) {

		// click the Delete button
		$this->tester->tryToClick( $this->getPaymentMethodElementSelector( $token, "a[contains(concat(' ', normalize-space(@class), ' '), ' delete ')]" ) );

		$this->tester->acceptPopup();

		$this->tester->waitForText( 'Payment method deleted.' );
	}


}

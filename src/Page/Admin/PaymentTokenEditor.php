<?php

namespace SkyVerge\Lumiere\Page\Admin;

use Codeception\Actor;
use Codeception\Module\WPWebDriver;

/**
 * Payment Token Editor page object.
 */
class PaymentTokenEditor {


	/** @var string base URL for the user profile page */
	const URL = '/wp-admin/user-edit.php?user_id={user_id}';

	/** @var string selector for the Payment Tokens table */
	const SELECTOR_PAYMENT_TOKENS_TABLE = '.sv_wc_payment_gateway_token_editor';

	/** @var string selector for the row for payment token with ID equal to {token} */
	const SELECTOR_PAYMENT_TOKEN_ROW = "//tr[contains(concat(' ', normalize-space(@class), ' '), ' token ')][descendant::input[@value = {token}]]";

	/** @var string selector for the row for a new payment token */
	const SELECTOR_NEW_PAYMENT_TOKEN_ROW = '.sv_wc_payment_gateway_token_editor tr.new-token';

	/** @var string selector for the default payment token field */
	const FIELD_NAME_DEFAULT_PAYMENT_TOKEN = 'wc_payment_gateway_{gateway_id}_tokens_default';

	/** @var string selector for the Add New button */
	const BUTTON_ADD_NEW = '.sv_wc_payment_gateway_token_editor [data-action="add-new"]';

	/** @var string selector for the Save button */
	const BUTTON_SAVE = '.sv_wc_payment_gateway_token_editor [data-action="save"]';

	/** @var string base selector for the Remove button */
	const BUTTON_REMOVE = '.sv_wc_payment_gateway_token_editor [data-action="remove"][data-token-id="{token}"]';


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
	 * Returns the URL to the user profile page.
	 *
	 * @return string
	 */
	public static function route( $user_id ) {

		return str_replace( '{user_id}', $user_id, self::URL );
	}


	/**
	 * Gets the selector for the row for a payment token identified by $token
	 *
	 * @param string $token the payment method token
	 * @return string
	 */
	public function getPaymentTokenSelector( string $token ) {

		return self::SELECTOR_PAYMENT_TOKENS_TABLE . " [value=\"{$token}\"]";
	}


	/**
	 * Gets the selector for a field in the row for a new payment token.
	 *
	 * Uses XPath instead of CSS because payment token rows don't have an identifier that we can use to limit the selector well enough.
	 *
	 * @param string $token the payment method token
	 * @param string $name the name of the field
	 * @return string
	 */
	public function getPaymentTokenFieldSelector( string $token, string $name ) {

		// use XPath to find a field that is a descendant of the the payment token row and has a name attribute ending with [$name]
		return str_replace( '{token}', $token, self::SELECTOR_PAYMENT_TOKEN_ROW . "//*[substring(@name, string-length(@name) - string-length('[{$name}]') + 1) = '[{$name}]']" );
	}


	/**
	 * Gets the selector for a field in the row for a new payment token.
	 *
	 * @param string $name the name of the field
	 * @return string
	 */
	public function getNewPaymentTokenFieldSelector( string $name ) {

		return self::SELECTOR_NEW_PAYMENT_TOKEN_ROW . " [name$=\"[{$name}]\"]";
	}


	/**
	 * Gets the name fort he default payment token radio field.
	 *
	 * @param string $gateway_id payment gateway ID
	 * @return string
	 */
	public function getDefaultPaymentTokenFieldName( string $gateway_id ) {

		return str_replace( '{gateway_id}', $gateway_id, self::FIELD_NAME_DEFAULT_PAYMENT_TOKEN );
	}


	/**
	 * Gets the selector for the default payment token radio field.
	 *
	 * @param string $gateway_id
	 * @return string
	 */
	public function getDefaultPaymentTokenFieldSelector( string $gateway_id ) {

		return sprintf( '[name="%s"]', $this->getDefaultPaymentTokenFieldName( $gateway_id ) );
	}


	/**
	 * Gets the selector for the Remove button in a payment token row.
	 *
	 * @param string $token payment method token
	 */
	public function getRemovePaymentTokenButtonSelector( string $token ) {

		return str_replace( '{token}', $token, self::BUTTON_REMOVE );
	}


	/**
	 * Scrolls the window to show the Payment Tokens table.
	 */
	public function scrollToPaymentTokensTable() {

		$this->tester->scrollTo( PaymentTokenEditor::SELECTOR_PAYMENT_TOKENS_TABLE, 0, -250 );
	}

	/**
	 * Shows the fields used to add a new payment token.
	 */
	public function showNewPaymentTokenFields() {

		$this->tester->tryToClick( PaymentTokenEditor::BUTTON_ADD_NEW );

		$this->tester->waitForElementClickable( PaymentTokenEditor::SELECTOR_NEW_PAYMENT_TOKEN_ROW );
	}

	/**
	 * Clicks the Save button and waits for the page to start loading.
	 */
	public function saveChanges() {

		$this->tester->tryToClick( self::BUTTON_SAVE );

		// wait for the page to reload
		$this->tester->waitForText( 'Profile updated' );
	}


	/**
	 * Selects the specified payment token as the default one.
	 *
	 * The method selects the appropriate radio button to mark the payment token as default but does not save changes.
	 *
	 * @param string $gateway_id payment gateway ID
	 * @param string $token payment method token
	 */
	public function selectPaymentTokenAsDefault( string $gateway_id, string $token ) {

		$this->tester->tryToSelectOption( $this->getDefaultPaymentTokenFieldSelector( $gateway_id ), $token );
	}


	/**
	 * Clicks the Remove button, accepts the popup, and waits for the page to start loading.
	 *
	 * @param string $token payment method token
	 */
	public function deletePaymentToken( string $token ) {

		$this->tester->tryToClick( $this->getRemovePaymentTokenButtonSelector( $token ) );

		$this->tester->acceptPopup();

		$this->tester->waitForJqueryAjax();

		$this->tester->waitForElementNotVisible( $this->getPaymentTokenSelector( $token ) );
	}


	/**
	 * Checks that the payments table includes a row for the given payment token.
	 *
	 * @param string $token payment method token
	 */
	public function seePaymentToken( string $token ) {

		$this->tester->waitForElement( $this->getPaymentTokenSelector( $token ) );
		$this->tester->seeElement( $this->getPaymentTokenSelector( $token ) );
	}


	/**
	 * Checks that the payments table does not include a row for the given payment token.
	 *
	 * @param string $token payment method token
	 */
	public function dontSeePaymentToken( string $token ) {

		$this->tester->waitForElementNotVisible( $this->getPaymentTokenSelector( $token ) );
		$this->tester->dontSeeElement( $this->getPaymentTokenSelector( $token ) );
	}


	/**
	 * Checks that the payments table includes a row for the given payment token and is marked as default.
	 *
	 * @param string $gateway_id payment gateway ID
	 * @param string $token payment method token
	 */
	public function seeDefaultPaymentToken( string $gateway_id, string $token ) {

		$this->seePaymentToken( $token );

		$this->tester->seeOptionIsSelected( $this->getDefaultPaymentTokenFieldSelector( $gateway_id ), $token );
	}


}

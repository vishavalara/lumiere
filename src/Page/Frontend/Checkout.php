<?php

namespace SkyVerge\Lumiere\Page\Frontend;

use Codeception\Actor;
use Codeception\Module\WPWebDriver;

/**
 * Checkout page object.
 */
class Checkout {


	/** @var string default URL for the Checkout page */
	const URL = '/checkout';

	/** @var string selector for the Billing First Name field */
	const FIELD_BILLING_FIRST_NAME = '[name="billing_first_name"]';

	/** @var string selector for the Billing Last Name field */
	const FIELD_BILLING_LAST_NAME = '[name="billing_last_name"]';

	/** @var string selector for the Billing Address field */
	const FIELD_BILLING_ADDRESS_1 = '[name="billing_address_1"]';

	/** @var string selector for the Billing City field */
	const FIELD_BILLING_CITY = '[name="billing_city"]';

	/** @var string selector for the Billing Postcode field */
	const FIELD_BILLING_POSTCODE = '[name="billing_postcode"]';

	/** @var string selector for the Billing Phone field */
	const FIELD_BILLING_PHONE = '[name="billing_phone"]';

	/** @var string selector for the Billing Email field */
	const FIELD_BILLING_EMAIL = '[name="billing_email"]';

	/** @var string selector for the Securely Save to Account checkbox field */
	const FIELD_TOKENIZE_PAYMENT_METHOD = '.payment_method_{gateway_id} .js-sv-wc-tokenize-payment';

	/** @var string selector for a saved payment method option */
	const FIELD_SAVED_PAYMENT_METHOD = '#wc-{gateway_id}-payment-token-{token}';

	/** @var string selector for the Place Order button */
	const BUTTON_PLACE_ORDER = '[name="woocommerce_checkout_place_order"]';


    /** @var WPWebDriver|Actor our tester */
	protected $tester;


	/**
	 * Constructor for Checkout page object.
	 *
	 * @param WPWebDriver|Actor $I tester instance
	 */
    public function __construct( \FrontendTester $I ) {

        $this->tester = $I;
	}


    /**
	 * Returns the URL to the Checkout page.
	 *
	 * @return string
     */
    public static function route() {

        return self::URL;
	}


	/**
	 * Fills the billing fields with default values.
	 */
	public function fillBillingDetails() {

		$this->tester->fillField( self::FIELD_BILLING_FIRST_NAME, 'John' );
		$this->tester->fillField( self::FIELD_BILLING_LAST_NAME,  'Doe' );
		$this->tester->fillField( self::FIELD_BILLING_ADDRESS_1,  'Ste 2B' );
		$this->tester->fillField( self::FIELD_BILLING_CITY,       'Boston' );
		$this->tester->fillField( self::FIELD_BILLING_POSTCODE,   '02115' );
		$this->tester->fillField( self::FIELD_BILLING_PHONE,      '800-970-1259' );
		$this->tester->fillField( self::FIELD_BILLING_EMAIL,      'john@example.com' );
	}


	/**
	 * Checks that the label of the payment method matches the provided title.
	 *
	 * @param string $gateway_id the ID of the gateway
	 * @param string $title the expected title
	 */
	public function seePaymentMethodTitle( string $gateway_id, string $title ) {

		$label_selector = sprintf( 'label[for="payment_method_%s"]' , $gateway_id );

		$this->tester->waitForElementClickable( $label_selector );
		$this->tester->see( $title, $label_selector );
	}


}

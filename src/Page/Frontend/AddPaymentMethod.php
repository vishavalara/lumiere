<?php

namespace SkyVerge\Lumiere\Page\Frontend;

use Codeception\Actor;
use Codeception\Module\WPWebDriver;

/**
 * Add Payment Method page object.
 */
class AddPaymentMethod {


	/** @var string default URL for the Add payment method page */
	const URL = '/my-account/add-payment-method/';

	/** @var string selector for the Add payment method button */
	const BUTTON_ADD = '[id="place_order"]';


    /** @var WPWebDriver|Actor our tester */
	protected $tester;


	/**
	 * Constructor for Add payment method page object.
	 *
	 * @param WPWebDriver|Actor $I tester instance
	 */
    public function __construct( \FrontendTester $I ) {

        $this->tester = $I;
	}


    /**
	 * Returns the URL to the Add payment method page.
	 *
	 * @return string
     */
    public static function route() {

        return self::URL;
	}


}

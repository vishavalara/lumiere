<?php

namespace SkyVerge\Lumiere\Page\Frontend;

use Codeception\Actor;
use Codeception\Module\WPWebDriver;

/**
 * Product page object.
 */
class Product {


    /** @var WPWebDriver|Actor our tester */
	protected $tester;


    /**
     * Returns the URL to a product page.
	 *
	 * @param \WC_Product $product the product
	 *
	 * @return string
     */
    public static function route( \WC_Product $product )  {

		return str_replace( home_url(), '', $product->get_permalink() );
    }


	/**
	 * Constructor for the Product page object.
	 *
	 * @param WPWebDriver|Actor $I tester instance
	 */
	public function __construct( \FrontendTester $I ) {

		$this->tester = $I;
	}


	/**
	 * Adds a product to the cart from the product page.
	 *
	 * @param \WC_Product $product the product
	 * @return int
	 */
	public function addSimpleProductToCart( \WC_Product $product ) {

		// am I on product page?
		$this->tester->see( $product->get_name() );

		// add product to cart (but don't visit cart page yet)
		$this->tester->click( sprintf( '//button[@name="add-to-cart" and @value=%d]', $product->get_id() ) );

		return $product->get_id();
	}


}

<?php

namespace Codeception\Module;

use Facebook\WebDriver\Exception\UnknownErrorException;

/**
 * The WooCommerce Browser module.
 *
 * Extends WPWebDriver to add WooCommerce-specific methods for easier shop navigation.
 */
class WooCommerceBrowser extends WPWebDriver {


	/**
	 * Attempts multiple times to perform an action on a clickable element.
	 *
	 * The method ignores "Element is not clickable" exceptions and tries to run the actions again.
	 *
	 * @param \Closure $action a closure that executes one or more actions
	 * @param int $attempts number of times to try the action before throwing an exception
	 * @param \Closure $exception_handler a closure used to determine whether an exception should be ignored
	 */
	public function tryAction( \Closure $action, $attempts = 3, \Closure $exception_handler = null ) {

		do {

			$attempts--;

			try {

				$this->waitForJqueryAjax();

				$action();

				break;

			} catch ( UnknownErrorException $e ) {

				// rethrow the exception on the last attempt
				if ( $attempts <= 0 ) {
					throw $e;
				}

				// try to click the element again if the element was not clickable for this attempt
				if ( false !== strpos( $e->getMessage(), 'is not clickable at point' ) ) {
					continue;
				}

				if ( $exception_handler && $exception_handler( $e ) ) {
					continue;
				}

				throw $e;
			}

		} while ( $attempts > 0 );
	}


	/**
	 * Attempts to click an element multiple times.
	 *
	 * The method ignores "Element is not clickable" exceptions and tries to click again.
	 *
	 * @param mixed $element the element to click
	 * @param mixed $context CSS or XPath locator to narrow search
	 * @param int $attempts number of times to try the action before throwing an exception
	 */
	public function tryToClick( $element, $context = null, int $attempts = 3 ) {

		$this->tryAction(
			function() use( $element, $context ) {
				$this->waitForElementClickable( $element );
				$this->click( $element, $context );
			},
			$attempts
		);
	}


	/**
	 * Attempts to tick a checkbox multiple times.
	 *
	 * The method ignores "Element is not clickable" exceptions and tries to tick the checkbox again.
	 *
	 * @param mixed $element the checkbox field
	 * @param int $attempts number of times to try the action before throwing an exception
	 */
	public function tryToCheckOption( $element, $attempts = 3 ) {

		$this->tryAction(
			function() use( $element ) {
				$this->waitForElementClickable( $element );
				$this->checkOption( $element );
			},
			$attempts
		);
	}


	/**
	 * Attempts to select an option multiple times.
	 *
	 * The method ignores "Element is not clickable" exceptions and tries to select the option again.
	 *
	 * @param mixed $element the select or radio field
	 * @param mixed $option the value of the option or radio field to select
	 * @param int $attempts number of times to try the action before throwing an exception
	 */
	public function tryToSelectOption( $element, $option, $attempts = 3 ) {

		$this->tryAction(
			function() use( $element, $option ) {
				$this->waitForElementClickable( $element );
				$this->selectOption( $element, $option );
			},
			$attempts
		);
	}


	/**
	 * Directs the test user to the cart page.
	 */
	public function amOnCartPage() {

		$this->amOnUrl( wc_get_cart_url() );
	}


}

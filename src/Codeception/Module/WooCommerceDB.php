<?php
namespace Codeception\Module;

use Codeception\TestInterface;

/**
 * The WooCommerce DB module.
 *
 * Extends WPDb to add WooCommerce-specific methods for easier shop data creation.
 */
class WooCommerceDB extends WPDb {


	/**
	 * Runs before each test.
	 *
	 * Performs any base WooCommerce configuration to avoid the need to maintain them in a SQL dump.
	 *
	 * @param TestInterface $test
	 */
	public function _before( TestInterface $test ) {

		parent::_before( $test );

		// ensure the base pages are set
		\WC_Install::create_pages();
	}


	/**
	 * Updates the settings for the specified payment gateway.
	 *
	 * TODO: move to a trait with PaymentGateway or PaymentGateway\Settings related methods.
	 *
	 * @param string $gateway_id the ID of the payment gateway
	 * @param array $settings the new settings
	 */
	public function havePaymentGatewaySettingsInDatabase( string $gateway_id, array $settings ) {

		$setting_name     = sprintf( 'woocommerce_%s_settings', $gateway_id );
		$current_settings = get_option( $setting_name, [] );

		update_option( $setting_name, array_merge( $current_settings, $settings ) );
	}


	/**
	 * Creates a simple product in the database.
	 *
	 * @param array $props product properties
	 * @return \WC_Product_Simple
	 */
	public function haveSimpleProductInDatabase( array $props = [] ) : \WC_Product_Simple {

		$props = wp_parse_args( $props, [
			'name'          => 'Simple Product',
			'regular_price' => 1.00,
			'virtual'       => false,
		] );

		$product = new \WC_Product_Simple();

		$product->set_props( $props );

		$product->save();

		return $product;
	}


	/**
	 * Gets the ID of the first payment token in the database that matches the given criteria.
	 *
	 * @param array $args search criteria
	 * @return null|\WC_Payment_Token
	 */
	public function grabPaymentTokenFromDatabase( $args ) {

		$entries = $this->grabAllFromDatabase( $this->grabPrefixedTableNameFor( 'woocommerce_payment_tokens' ), 'token_id', $args );
		$token   = null;

		if ( is_array( $entries ) && isset( $entries[0]['token_id'] ) ) {
			$token = \WC_Payment_Tokens::get( (int) $entries['0']['token_id'] );
		}

		return $token;
	}


}

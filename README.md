## Getting started
Lumiere is designed to get a functioning test suite up and running with minimal plugin-level configuration. The majority of the options defined in Codeception's `.yml` configuration files is the same for all plugins, so this consolidates them into a set of [shared configurations](configs). Improvements can be inherited by all plugins with a composer update.

### Prerequisites
- [Docker](https://docs.docker.com/get-docker/) Lumiere includes a [docker-compose](docker/docker-compose.yml) file to define a series of services that can be used to run all test suites
- A fresh and isolated WordPress installation if you don't want to use Docker. **IMPORTANT:** <u>Do not use this installation for any other purpose, including performing manual tests. It should be reserved to acceptance tests</u>. Any changes might cause a problem with the test suite; any tests run by the suite might be destructive of the changes you made as well. To run manual tests you should dedicate a different installation.
- [Selenium](https://www.seleniumhq.org/download/) for acceptance tests without Docker

### Installation

Lumiere is available as a Composer package. To install it, you should update your `composer.json` file to use a custom repository.

1. Add Lumiere repository to composer:

    ```
    {
      "type": "vcs",
      "url": "git@github.com:gdcorp-partners/lumiere.git"
    }
    ```

1. Require `skyverge/lumiere` as a development dependency:

    ```
    composer require codeception/module-db codeception/module-webdriver lucatume/wp-browser:2.4.8 skyverge/lumiere --update-with-all-dependencies --dev
    ```

    Prepend `COMPOSER_MEMORY_LIMIT=-1` to the command above if you get a memory related Fatal error.

You should now be able to use `vendor/bin/lumiere` to configure your testing environment or run test suites.

### Configuration

Follow these steps to initialize Lumiere on a new plugin or after cloning a project where Lumiere was already initialized. This will ensure all configuration files and parameters are set.

After installing via composer:

1. `$ vendor/bin/lumiere up`
1. Answer a series of configuration questions about your local WordPress installation(s) &mdash; the defaults work out of the box with the Docker services

For new installations, make sure to commit all of the resulting generated files. Local files will already be ignored when appropriate.

If you are using the Docker environment:

1. Create a `docker-compose.yml` file if not already included in the repo
    ```
    version: '2'

    services:
      codeception:
        volumes:
          - $PWD:/project
          - $PWD:/wordpress/wp-content/plugins/$PLUGIN_DIR

      wordpress:
        volumes:
          - $PWD:/var/www/html/wp-content/plugins/$PLUGIN_DIR
    ```
1. Remove existing services:
    `docker-compose -f vendor/skyverge/lumiere/docker/docker-compose.yml -f docker-compose.yml --env-file=.env.lumiere.dist --project-name=lumiere down --volumes`
1. Bootstrap fresh services:
    `docker-compose -f vendor/skyverge/lumiere/docker/docker-compose.yml -f docker-compose.yml --env-file=.env.lumiere.dist --project-name=lumiere run --rm codeception bootstrap`

If you are using a fresh WordPress installation:

1. `cd` or SSH into your local WordPress installation:
1. If WooCommerce is not already installed, `$ wp plugin install woocommerce --activate`
1. Copy a build of your plugin to the WordPress install
1. Activate the plugin: `$ wp plugin activate {your-plugin-slug}`
1. Make any further database changes that the plugin requires for _every_ acceptance test, e.g. enabling pretty permalinks
1. Dump the database: `$ wp db export path/to/your/plugin/repo/tests/_data/dump.sql`

Now add some tests or run existing ones using [these commands](#commands).

### Commands

#### Docker

The following commands can be used to run tests:

- `docker-compose -f vendor/skyverge/lumiere/docker/docker-compose.yml -f docker-compose.yml --env-file=.env.lumiere.dist --project-name=lumiere run --workdir /wordpress/wp-content/plugins/{plugin_dir} --rm codeception vendor/bin/codecept run admin`
- `docker-compose -f vendor/skyverge/lumiere/docker/docker-compose.yml -f docker-compose.yml --env-file=.env.lumiere.dist --project-name=lumiere run --workdir /wordpress/wp-content/plugins/{plugin_dir} --rm codeception vendor/bin/codecept run frontend`
- `docker-compose -f vendor/skyverge/lumiere/docker/docker-compose.yml -f docker-compose.yml --env-file=.env.lumiere.dist --project-name=lumiere run --workdir /wordpress/wp-content/plugins/{plugin_dir} --rm codeception vendor/bin/codecept run integration`
- `docker-compose -f vendor/skyverge/lumiere/docker/docker-compose.yml -f docker-compose.yml --env-file=.env.lumiere.dist --project-name=lumiere run --workdir /wordpress/wp-content/plugins/{plugin_dir} --rm codeception vendor/bin/codecept run unit`

The following commands can be used to control the Docker environment

- Bootstrap Lumiere services

    `docker-compose -f vendor/skyverge/lumiere/docker/docker-compose.yml -f docker-compose.yml --env-file=.env.lumiere.dist --project-name=lumiere run --rm codeception bootstrap`
- Stop and remove all containers for Lumiere services

    `docker-compose -f vendor/skyverge/lumiere/docker/docker-compose.yml -f docker-compose.yml --env-file=.env.lumiere.dist --project-name=lumiere down --volumes`
- Access a bash shell in the container

    `docker-compose -f vendor/skyverge/lumiere/docker/docker-compose.yml -f docker-compose.yml --env-file=.env.lumiere.dist --project-name=lumiere run --rm codeception /bin/bash`

#### Other

For now, tests can be run using standard Codeception commands:
- `vendor/bin/codecept run admin`
- `vendor/bin/codecept run frontend`
- `vendor/bin/codecept run integration`
- `vendor/bin/codecept run unit`

See the underlying [Codeception commands](https://codeception.com/docs/reference/Commands) for generating new tests, environments, or suites.

## Details

### Suites
On installation, this library configures 4 common suites for running different types of tests:
- Unit tests
- Integration tests
- Admin acceptance tests
- Frontend acceptance tests

As with the Codeception library that this library is built on, any number or combination of test suites can be created and configured. The above four are likely to be the most common, but aren't required.

### Configuration
A number of configuration files are generated automatically, and all can be overridden as needed.

#### Global configuration
- `codeception.dist.yml`
    - Holds any configuration values that are specific to the plugin and should also be default for everyone running tests
    - Inherits and overrides the base `configs/codeception.yml` configuration in this package
    - Should be committed
- `codeception.yml`
    - Holds any configuration values that are specific to your local environment
    - Inherits and overrides `codeception.dist.yml`
    - Should not be committed
    
#### Suite configuration
Each generated test suite has its own set of configuration files.

- `{suite}.suite.dist.yml`
    - Holds any configuration values that are specific to the plugin and should also be default for everyone running the test suite
    - Inherits and overrides the base `configs/{suite}.suite.yml` configuration in this package
    - Should be committed
- `{suite}.suite.yml`
    - Holds any configuration values that are specific to your local environment
    - Inherits and overrides `{suite}.suite.dist.yml`
    - Should not be committed
    
#### Environment configuration
- `.env.lumiere`
    - Holds all variables for your local WordPress installation
    - Should not be committed
- `.env.lumiere.dist`
    - Holds all variables that are specific to the plugin and should also be available for everyone running the test suite
    - Should be committed
- `docker-compose.yml`
    - Extends the `docker-compose.yml` file included in Lumiere to map plugin's source code the appropriate directories inside the container

    The following file should work for most plugins:

    ```docker-compose
    version: '2'

    services:
    codeception:
        volumes:
        - $PWD:/project
        - $PWD:/wordpress/wp-content/plugins/$PLUGIN_DIR

    wordpress:
        volumes:
        - $PWD:/var/www/html/wp-content/plugins/$PLUGIN_DIR
    ```


- `wp-bootstrap.sh`
    - A bash script that is sourced while the `codeception` service is being configured and can be used to customize the WordPress installation with WP CLI.

    Here is an example file:

    ```bash
    source .env.lumiere # define TEST_MERCHANT_ID and TEST_API_PASSCODE

    cd /wordpress

    wp wc payment_gateway update bambora_credit_card --enabled=true --user=admin

    wp option patch insert woocommerce_bambora_credit_card_settings debug_mode "log"
    wp option patch insert woocommerce_bambora_credit_card_settings test_merchant_id "$TEST_MERCHANT_ID"
    wp option patch insert woocommerce_bambora_credit_card_settings test_api_passcode "$TEST_API_PASSCODE"
    ```
    
### Modules

#### [WooCommerceDB](src/Codeception/Module/WooCommerceDB.php)
A wrapper for WPDb, this provides common methods that are often used in acceptance tests to generate and interact with WooCommerce database data. This can be used for things like creating products and orders.

#### [WooCommerceBrowser](src/Codeception/Module/WooCommerceBrowser.php)
A wrapper for WPWebDriver, this adds common methods for various WooCommerce-related acceptance test actions like going directly to the card or product pages.

### Payment Gateway Tests

Lumiere includes a collection of abstract Cest classes that implement tests for common payment gateway operations. To run those tests, plugins should add new Cest to the plugin `frontend` or `admin` test suites, extend one of the shared Cest classes, and implement the abstract methods.

#### [CreditCardCest](src/Tests/Frontend/PaymentGateways/CreditCardCest.php)
An abstract Cest with common tests for credit card transactions (without tokenization). It includes tests that:

- Confirm the custom name of the payment gateway is displayed
- Place an order for a shippable product

#### [CreditCardTokenizationCest](src/Tests/Frontend/PaymentGateways/CreditCardTokenizationCest.php)
An abstract Cest with common tests for credit card tokenization transactions. It includes all tests from `CreditCardCest` and tests that:

- Place an order saving the payment method and using it again to place another order
- Place an order saving the payment method and checking that it shows up in the payment methods table
- Edit the nickname of a payment method in the Payment Methods page
- Delete a payment method from the Payment Methods page

### VNC

To see the tests as they happen using the VNC server: use Cmd + Space, and then type vnc://localhost:5900 to access. The password, if needed, is `secret`.

#!/bin/bash

###############################################################################
# Downloads a private binary release file from GitHub in bash
#
# https://gist.github.com/josh-padnick/fdae42c07e648c798fc27dec2367da21
#
# Arguments:
#   GitHub repo owner
#   GitHub repo name
#   GitHub tag
#   Asset filename (uses ${github_repo_name}.${github_tag}.zip as default)
#   GitHub OAuth token (uses the value $GITHUB_API_KEY as default)
###############################################################################
download_github_release() {

	readonly github_repo_owner="$1"
	readonly github_repo_name="$2"
	readonly github_tag="$3"
	readonly asset_filename="${4-${github_repo_name}.${github_tag}.zip}"
	readonly github_oauth_token="${5-$GITHUB_API_KEY}"

	# Get the "github tag id" of this release
	github_tag_id=$(curl --silent --show-error \
	                     --header "Authorization: token $github_oauth_token" \
		                 --request GET \
						 "https://api.github.com/repos/$github_repo_owner/$github_repo_name/releases" \
		                 | jq --raw-output ".[] | select(.tag_name==\"1.17.4\").id")

	# Get the download URL of our desired asset
	download_url=$(curl --silent --show-error \
	                    --header "Authorization: token $github_oauth_token" \
	                    --header "Accept: application/vnd.github.v3.raw" \
	                    --location \
	                    --request GET \
						"https://api.github.com/repos/$github_repo_owner/$github_repo_name/releases/$github_tag_id" \
	                    | jq --raw-output ".assets[] | select(.name==\"$asset_filename\").url")

	# Get GitHub's S3 redirect URL
	# Why not just curl's built-in "--location" option to auto-redirect? Because curl then wants to include all the original
	# headers we added for the GitHub request, which makes AWS complain that we're trying strange things to authenticate.
	redirect_url=$(curl --silent --show-error \
	                    --header "Authorization: token $github_oauth_token" \
	                    --header "Accept: application/octet-stream" \
	                    --request GET \
	                    --write-out "%{redirect_url}" \
	                    "$download_url")

	# Finally download the actual binary
	curl --silent --show-error \
	     --header "Accept: application/octet-stream" \
	     --output "$asset_filename" \
	     --request GET \
	     "$redirect_url"
}


wp_bootstrap() {

	WP_DOMAIN=wp.test
	WP_URL="https://$WP_DOMAIN"
	WP_ADMIN_USERNAME=admin
	WP_ADMIN_PASSWORD=password
	WP_ADMIN_EMAIL="admin@$WP_DOMAIN"
	DB_HOST=mysql
	DB_NAME=acceptance_tests
	DB_USER=root
	DB_PASSWORD=root
	TABLE_PREFIX=wp_

	echo "Preparing WordPress"

	cd /wordpress

	echo "Making sure permissions are correct"

	# make sure permissions are correct (maybe can be avoided with https://stackoverflow.com/a/56990338).
	chown www-data:www-data /wordpress /wordpress/wp-content /wordpress/wp-content/plugins
	chmod 755 /wordpress /wordpress /wordpress/wp-content /wordpress/wp-content/plugins

	echo "Making sure the database server is up and running"

	while ! mysqladmin ping -h$DB_HOST --silent; do

		echo "Waiting for the database server (host: $DB_HOST)"
		sleep 1
	done

	echo 'The database server is ready'

	echo "Creating acceptance_tests database if it doesn't exist"
	mysql -h$DB_HOST -u$DB_USER -p$DB_PASSWORD -e "CREATE DATABASE IF NOT EXISTS acceptance_tests"

	echo "Creating integration_tests database if it doesn't exist"
	mysql -h$DB_HOST -u$DB_USER -p$DB_PASSWORD -e "CREATE DATABASE IF NOT EXISTS integration_tests"

	if [ ! -f wp-config.php ]; then

		echo "Creating wp-config.php"

		# we can't use wp core commands if the wp-config.php file is not present
		wp config create --dbhost=$DB_HOST --dbname=$DB_NAME --dbuser=$DB_USER --dbpass=$DB_PASSWORD
	fi

	# Make sure WordPress is installed.
	if ! $(wp core is-installed); then

		echo "Installing WordPress"

		wp core install --url=$WP_URL --title=tests --admin_user=$WP_ADMIN_USERNAME --admin_password=$WP_ADMIN_PASSWORD --admin_email=$WP_ADMIN_EMAIL

		# overwrite existing configuration to make sure we are using the correct values
		wp core config --dbhost=$DB_HOST --dbname=$DB_NAME --dbuser=$DB_USER --dbpass=$DB_PASSWORD --dbprefix=$TABLE_PREFIX --force --extra-php <<'PHP'
// allows URLs to work while accessing the WordPress service from the host using mapped ports
if ( 8443 === (int) $_SERVER['SERVER_PORT'] || 8080 === (int) $_SERVER['SERVER_PORT'] ) {

	$protocol = 8443 === (int) $_SERVER['SERVER_PORT'] ? 'https' : 'http';

	define( 'WP_HOME', "{$protocol}://{$_SERVER['HTTP_HOST']}" );
	define( 'WP_SITEURL', "{$protocol}://{$_SERVER['HTTP_HOST']}" );

// allow URLs to work with ngrok
} elseif ( isset( $_SERVER['HTTP_X_ORIGINAL_HOST'] ) ) {

	$protocol = isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ? $_SERVER['HTTP_X_FORWARDED_PROTO'] : 'http';

	define( 'WP_HOME', "{$protocol}://{$_SERVER['HTTP_X_ORIGINAL_HOST']}" );
	define( 'WP_SITEURL', "{$protocol}://{$_SERVER['HTTP_X_ORIGINAL_HOST']}" );
}

if ( isset( $protocol ) && 'https' === $protocol ) {
	$_SERVER['HTTPS'] = 'on';
}
PHP
	fi

	wp core update
	wp core update-db

	wp rewrite structure '/%postname%/' --hard

	wp db export fresh-install.sql


	echo "Installing and configuring WooCommerce"

	wp plugin install woocommerce --activate

	wp option update woocommerce_store_address "177 Huntington Ave Ste 1700"
	wp option update woocommerce_store_address_2 "70640"
	wp option update woocommerce_store_city "Boston"
	wp option update woocommerce_store_postcode "02115-3153"
	wp option update woocommerce_default_country "US:MA"
	wp option update woocommerce_currency "USD"

	# remove WooCommerce admin notices
	wp option update woocommerce_admin_notices [] --format=json

	wp user meta add 1 billing_first_name "John"
	wp user meta add 1 billing_last_name "Doe"
	wp user meta add 1 billing_address_1 "Ste 2B"
	wp user meta add 1 billing_city "Boston"
	wp user meta add 1 billing_state "MA"
	wp user meta add 1 billing_postcode "02115"
	wp user meta add 1 billing_country "US"
	wp user meta add 1 billing_email "john@example.com"
	wp user meta add 1 billing_phone "800-970-1259"

	wp wc tool run db_update_routine --user=admin
	wp wc tool run install_pages --user=admin

	# prevent WooCommerce redirection to Setup Wizard
	wp transient delete _wc_activation_redirect

	# run action-scheduler to make sure all necessary tables are created
	wp action-scheduler run

	wp theme install --activate storefront

	# disable Storefront admin notice
	wp option set storefront_nux_dismissed yes

	echo "Preparing plugin"

	cd /project

	# install vendor
	if [ -e composer.json ]; then
		composer install --prefer-dist
	fi

	# add support for projects with a struture similar to woocommerce-memberships-for-teams
	if [ -e ../composer.json ]; then
		composer install --prefer-dist --working-dir=..
	fi

	wp plugin activate $PLUGIN_DIR --path=/wordpress

	# allow each plugin to configure the WordPress instance
	if [ -f wp-bootstrap.sh ]; then
		source wp-bootstrap.sh
	fi


	echo "Exporting acceptance_tests database into tests/_data/dump.sql"

	cd /wordpress

	mkdir -p /project/tests/_data
	chown www-data.www-data /project/tests/_data

	wp db export /project/tests/_data/dump.sql


	echo "Importing tests/_data/dump.sql into integration_tests database"

	wp db import --dbuser=$DB_USER --dbpass=$DB_PASSWORD --host=$DB_HOST --database=integration_tests /project/tests/_data/dump.sql


	echo "WordPress is ready"
}


if [[ "$1" == bootstrap ]]; then

	wp_bootstrap

elif [[ "$1" == start ]]; then

	wp_bootstrap

	# keep the service running...
	exec tail -f /dev/null

else

	# allow one-off command execution
	exec "$@"

fi
